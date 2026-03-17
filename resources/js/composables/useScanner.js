/**
 * @fileoverview useScanner.js — Composable for the Guard Terminal QR Scanner.
 *
 * Encapsulates all camera lifecycle management, QR decode handling,
 * duplicate-scan debouncing, audio feedback, and attendance feed logic
 * so that GuardScanner.vue only contains UI template code.
 *
 * Usage:
 *   import { useScanner } from '@/composables/useScanner';
 *   const { cameraStatus, scanMessage, attendanceList, stats, ... } = useScanner();
 */

import { ref, onMounted, onUnmounted, nextTick } from 'vue';
import { Html5Qrcode } from 'html5-qrcode';
import { scanAttendancePublic, fetchRecentAttendancePublic, fetchGuardStatsPublic } from '../services/attendanceService';

// ─── Constants ────────────────────────────────────────────────────────────────

/**
 * Minimum milliseconds between accepting the same QR value.
 * Prevents the camera from re-scanning the same code 30× per second.
 */
const DEBOUNCE_MS = 2500;

/** How often (ms) the live feed and stats are re-fetched in the background. */
const REFRESH_INTERVAL_MS = 8000;

/** Seconds to wait before automatically retrying after a camera failure. */
const AUTO_RETRY_DELAY_S = 8;

// ─── Composable ───────────────────────────────────────────────────────────────

export function useScanner() {
    // ── Reactive state ──────────────────────────────────────────────────────

    /** Camera element reference (bound to the #qr-reader div). */
    const qrReaderEl = ref(null);

    /** Current Html5Qrcode instance. Null when camera is off. */
    const scanner = ref(null);

    /** Camera lifecycle status: '' | 'starting' | 'active' | 'error' */
    const cameraStatus = ref('');

    /** Triggers the green pulse overlay for 600ms on successful scan. */
    const successPulse = ref(false);

    /** Inline status message shown below the camera view. */
    const scanMessage = ref({ text: '', type: 'success' });

    /** Live attendance feed (max 50 entries, newest first). */
    const attendanceList = ref([]);

    /** Guard against concurrent loadRecent() calls. */
    const loadingRecent = ref(false);

    /** Timestamp of the last successfully processed QR decode. */
    const lastScannedAt = ref(0);

    /** The last decoded QR value used to detect rapid re-scans. */
    const lastScannedValue = ref('');

    /** Countdown timer value shown during auto-retry delay. */
    const autoRetryCountdown = ref(0);

    /** Live-updating clock string shown in the header. */
    const currentTime = ref('');

    /** Live date string shown below the clock. */
    const currentDate = ref('');

    /** Attendance stat cards (total, present, late, absent). */
    const stats = ref({ total_today: 0, present_count: 0, late_count: 0, absent_count: 0 });

    /** Last successfully scanned person's details (student or teacher). */
    const lastScanDetails = ref(null);

    /** Triggers the "Unknown ID" red overlay for 3 seconds. */
    const unknownAlert = ref(false);

    // ── Internal timers (not reactive — no need to be refs) ─────────────────
    let clockInterval     = null;
    let highlightTimer    = null;
    let unknownTimer      = null;
    let refreshTimer      = null;
    let autoRetryTimer    = null;
    let countdownInterval = null;
    let destroyed         = false;

    // ── Helpers ──────────────────────────────────────────────────────────────

    /**
     * Format an ISO 8601 timestamp to a human-readable 12-hour time string.
     *
     * @param {string} iso - ISO date string (e.g. from `scanned_at`).
     * @returns {string} e.g. "08:32:15 AM"
     */
    function formatTime(iso) {
        if (!iso) return '—';
        const d = new Date(iso);
        return d.toLocaleTimeString('en-PH', { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: true });
    }

    /** Tick the clock display once per second. */
    function updateClock() {
        const now = new Date();
        currentTime.value = now.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: true });
        currentDate.value = now.toLocaleDateString('en-US', { weekday: 'long', month: 'long', day: 'numeric', year: 'numeric' });
    }

    /**
     * Generate a consistent colour from a name string (deterministic hash).
     * Used as avatar background colour fallback.
     *
     * @param {string} name - Full name or any string.
     * @returns {string} Hex colour code.
     */
    function getInitialsColor(name) {
        if (!name) return '#475569';
        const colors = ['#e11d48','#db2777','#c026d3','#9333ea','#7c3aed','#4f46e5','#3b82f6','#0ea5e9','#06b6d4','#0d9488','#059669','#16a34a','#65a30d','#ca8a04','#d97706','#ea580c'];
        let hash = 0;
        for (let i = 0; i < name.length; i++) hash = name.charCodeAt(i) + ((hash << 5) - hash);
        return colors[Math.abs(hash) % colors.length];
    }

    /**
     * Play a synthesised audio beep via the Web Audio API.
     *
     * Beep types:
     *   - 'success' → Single high-pitched sine tone (scan OK)
     *   - 'warning' → Two short beeps (already scanned this session)
     *   - 'error'   → Low sawtooth buzz (unknown ID / network error)
     *
     * Silently fails if the browser blocks AudioContext (e.g. no user gesture).
     *
     * @param {'success'|'warning'|'error'} [type='success']
     */
    function playBeep(type = 'success') {
        try {
            const AudioContext = window.AudioContext || window.webkitAudioContext;
            if (!AudioContext) return;
            const audioCtx = new AudioContext();

            const playNote = (freq, start, duration, wave = 'sine') => {
                const osc  = audioCtx.createOscillator();
                const gain = audioCtx.createGain();
                osc.type = wave;
                osc.frequency.setValueAtTime(freq, audioCtx.currentTime + start);
                gain.gain.setValueAtTime(0.1, audioCtx.currentTime + start);
                gain.gain.exponentialRampToValueAtTime(0.01, audioCtx.currentTime + start + duration);
                osc.connect(gain);
                gain.connect(audioCtx.destination);
                osc.start(audioCtx.currentTime + start);
                osc.stop(audioCtx.currentTime + start + duration);
            };

            if (type === 'success') {
                playNote(880, 0, 0.15, 'sine');
            } else if (type === 'warning') {
                playNote(600, 0, 0.1, 'sine');
                playNote(600, 0.15, 0.1, 'sine');
            } else {
                playNote(220, 0, 0.4, 'sawtooth');
            }
        } catch (_) { /* AudioContext blocked — fail silently */ }
    }

    /**
     * Normalise a storage-relative photo path into a public URL.
     *
     * Handles the following formats:
     *   - Already absolute: 'https://...'
     *   - Relative with storage prefix: 'storage/students/lrn.png'
     *   - Relative without prefix: 'students/lrn.png'
     *
     * @param {string|null} path - Raw photo_path value from the API.
     * @returns {string} An absolute URL or the default avatar fallback.
     */
    function getPhotoUrl(path) {
        if (!path) return '/images/default-avatar.png';
        if (typeof path === 'string' && path.startsWith('http')) return path;
        const cleanPath = String(path).replace(/^\/?storage\//, '').replace(/^\//, '');
        return '/storage/' + cleanPath;
    }

    /** Show the "last scan" card in the result panel and auto-clear after 3.5 s. */
    function triggerHighlight(student, attendance) {
        lastScanDetails.value = {
            ...student,
            status:    attendance.status,
            full_name: student.full_name || `${student.first_name} ${student.last_name}`,
        };
        if (highlightTimer) clearTimeout(highlightTimer);
        highlightTimer = setTimeout(() => { lastScanDetails.value = null; }, 3500);
    }

    /** Flash the "Unknown ID" red overlay and auto-dismiss after 3 s. */
    function triggerUnknownAlert() {
        unknownAlert.value = true;
        if (unknownTimer) clearTimeout(unknownTimer);
        unknownTimer = setTimeout(() => { unknownAlert.value = false; }, 3000);
    }

    /**
     * Display a status message below the camera view and auto-clear.
     *
     * @param {string} text     - The message to display.
     * @param {'success'|'warning'|'error'} [type='success']
     * @param {number} [duration=4000] - Auto-clear delay in ms. Pass 0 to keep persistent.
     */
    function showMessage(text, type = 'success', duration = 4000) {
        scanMessage.value = { text, type };
        if (duration > 0) {
            setTimeout(() => {
                if (scanMessage.value.text === text) scanMessage.value = { text: '', type: 'success' };
            }, duration);
        }
    }

    /** Trigger the green camera-overlay pulse animation for 600 ms. */
    function triggerSuccessPulse() {
        successPulse.value = true;
        setTimeout(() => { successPulse.value = false; }, 600);
    }

    /**
     * Check if the same QR value was processed within the debounce window.
     *
     * This is the client-side "rapid-fire" guard. The server has its own
     * 5-second cooldown and per-session lock — this simply prevents hammering
     * the API when the camera detects the same code multiple frames per second.
     *
     * @param {string} value - Decoded QR string.
     * @returns {boolean} True = still within cooldown, skip this scan.
     */
    function isDebounceLocked(value) {
        const now = Date.now();
        return value === lastScannedValue.value && (now - lastScannedAt.value) < DEBOUNCE_MS;
    }

    /**
     * Prepend a new attendance entry to the live feed and cap at 50 entries.
     *
     * @param {object} student    - Student data from the scan response.
     * @param {object} attendance - Attendance record from the scan response.
     */
    function prependAttendance(student, attendance) {
        const fullName = student.full_name || `${student.first_name} ${student.last_name}`;
        attendanceList.value = [{
            id:            attendance.id || Date.now(),
            full_name:     fullName,
            first_name:    student.first_name,
            last_name:     student.last_name,
            grade_section: student.grade_section || '—',
            time_in:       attendance.scanned_at || new Date().toISOString(),
            status:        attendance.status || 'on_time',
            photo_path:    student.photo_path,
        }, ...attendanceList.value].slice(0, 50);
    }

    // ── Scan processing ──────────────────────────────────────────────────────

    /**
     * Handle a decoded QR result from html5-qrcode.
     *
     * Processing pipeline:
     *  1. Strip whitespace and extract LRN from common QR text formats.
     *  2. Client-side debounce check (DEBOUNCE_MS = 2500ms).
     *  3. POST to /api/attendance/scan (scanAttendancePublic).
     *  4. Branch on response status: success / already_scanned / invalid / network error.
     *  5. Update stats, prepend to feed, play audio, show message.
     *
     * @param {string} decodedText - Raw text decoded from the QR image.
     */
    async function onScanSuccess(decodedText) {
        let raw = String(decodedText).trim();
        if (!raw) return;

        // Sanitize: extract numeric LRN from formatted strings like "LRN: 123456789012"
        const lrnMatch = raw.match(/LRN:\s*([\w\d-]+)/i);
        if (lrnMatch?.[1]) {
            raw = lrnMatch[1];
        } else if (raw.includes('\n')) {
            // Multi-line QR: grab the first long numeric sequence
            const numericMatch = raw.match(/\d{5,}/);
            if (numericMatch) raw = numericMatch[0];
        }

        // Client-side duplicate guard — same code within DEBOUNCE_MS is silently dropped
        if (isDebounceLocked(raw)) return;

        lastScannedValue.value = raw;
        lastScannedAt.value    = Date.now();

        try {
            const res = await scanAttendancePublic(raw);

            if (res?.status !== 'success') {
                if (res.status === 'already_scanned') {
                    playBeep('warning');
                    showMessage(res.message, 'warning');
                    return;
                }
                playBeep('error');
                if (res.status === 'invalid') triggerUnknownAlert();
                showMessage(res.message || 'Scan failed.', 'error');
                return;
            }

            const { student, attendance } = res;
            playBeep('success');
            triggerSuccessPulse();
            triggerHighlight(student, attendance);

            // Use inline stats from the response if provided (avoids a second API call)
            if (res.stats) stats.value = res.stats;
            else refreshStats();

            prependAttendance(student, attendance);
            showMessage(`${student.first_name} recorded.`, 'success');

        } catch (err) {
            playBeep('error');
            if (err.response?.status === 404) triggerUnknownAlert();
            showMessage(err.response?.data?.message || 'Scan failed.', 'error');
        }
    }

    // ── Data refresh ────────────────────────────────────────────────────────

    /** Reload the recent attendance feed from the server. */
    async function loadRecent() {
        if (loadingRecent.value) return;
        loadingRecent.value = true;
        try {
            const res = await fetchRecentAttendancePublic();
            attendanceList.value = res.data || [];
        } catch (_) { /* non-critical — feed will refresh on next interval */ }
        finally { loadingRecent.value = false; }
    }

    /** Reload the stat card counts from the server. */
    async function refreshStats() {
        try {
            const data = await fetchGuardStatsPublic();
            if (data) stats.value = data;
        } catch (_) { /* non-critical */ }
    }

    function startRefreshTimer() {
        stopRefreshTimer();
        refreshTimer = setInterval(() => {
            loadRecent();
            refreshStats();
        }, REFRESH_INTERVAL_MS);
    }

    function stopRefreshTimer() {
        if (refreshTimer) { clearInterval(refreshTimer); refreshTimer = null; }
    }

    // ── Camera lifecycle ────────────────────────────────────────────────────

    /**
     * Force-release any camera stream that may still be active from a prior session.
     *
     * When the page is hot-reloaded or the component is remounted, the browser may
     * not have released the hardware camera yet. Requesting and immediately stopping
     * a dummy stream forces the OS to free the camera lock.
     */
    async function forceReleaseCameraStream() {
        try {
            if (!navigator.mediaDevices?.getUserMedia) return;
            const stream = await navigator.mediaDevices.getUserMedia({ video: true });
            stream.getTracks().forEach(t => t.stop());
            await new Promise(r => setTimeout(r, 400));
        } catch (_) {}
    }

    /**
     * Start the QR scanner with auto-fallback across camera facingModes.
     *
     * Tries 'environment' (rear camera) first, then 'user' (front camera).
     * On failure, schedules an auto-retry after AUTO_RETRY_DELAY_S seconds.
     */
    async function startCamera() {
        if (destroyed) return;
        clearAutoRetry();
        cameraStatus.value = 'starting';

        await nextTick();
        const container = document.getElementById('qr-reader');
        if (container) container.innerHTML = '';

        await forceReleaseCameraStream();

        const html5Qr = new Html5Qrcode('qr-reader', { verbose: false, formatsToSupport: [0] });
        scanner.value = html5Qr;

        const config = {
            fps: 15,
            aspectRatio: 1.0,
            disableFlip: false,
            useBarCodeDetectorIfSupported: true,
            videoConstraints: { width: { ideal: 640 }, height: { ideal: 480 } },
        };

        let started = false;
        for (const constraint of [{ facingMode: 'environment' }, { facingMode: 'user' }]) {
            try {
                await html5Qr.start(constraint, config, onScanSuccess, () => {});
                started = true;
                break;
            } catch (_) {
                await new Promise(r => setTimeout(r, 400));
            }
        }

        if (started && !destroyed) {
            cameraStatus.value = 'active';
            loadRecent();
            refreshStats();
            startRefreshTimer();
        } else {
            cameraStatus.value = 'error';
            scheduleAutoRetry();
        }
    }

    /** Gracefully stop and release the camera. */
    async function stopScannerAndRelease() {
        try {
            if (scanner.value?.isScanning) await scanner.value.stop();
            await scanner.value?.clear();
            scanner.value = null;
        } catch (_) {}
    }

    /**
     * Schedule an automatic camera restart after AUTO_RETRY_DELAY_S seconds.
     * The countdown is exposed reactively so the UI can show a timer.
     */
    function scheduleAutoRetry() {
        clearAutoRetry();
        autoRetryCountdown.value = AUTO_RETRY_DELAY_S;
        countdownInterval = setInterval(() => {
            autoRetryCountdown.value = Math.max(0, autoRetryCountdown.value - 1);
        }, 1000);
        autoRetryTimer = setTimeout(() => {
            if (cameraStatus.value === 'error') startCamera();
        }, AUTO_RETRY_DELAY_S * 1000);
    }

    function clearAutoRetry() {
        if (autoRetryTimer)    clearTimeout(autoRetryTimer);
        if (countdownInterval) clearInterval(countdownInterval);
        autoRetryCountdown.value = 0;
    }

    /** Manually trigger a camera restart (e.g. from the UI retry button). */
    function manualRetry() { startCamera(); }

    // ── Lifecycle ────────────────────────────────────────────────────────────

    onMounted(() => {
        destroyed = false;
        updateClock();
        clockInterval = setInterval(updateClock, 1000);
        startCamera();
    });

    onUnmounted(async () => {
        destroyed = true;
        clearInterval(clockInterval);
        stopRefreshTimer();
        clearAutoRetry();
        await stopScannerAndRelease();
    });

    // ── Public API ───────────────────────────────────────────────────────────

    return {
        // State
        qrReaderEl,
        cameraStatus,
        successPulse,
        scanMessage,
        attendanceList,
        loadingRecent,
        autoRetryCountdown,
        currentTime,
        currentDate,
        stats,
        lastScanDetails,
        unknownAlert,
        // Utilities
        formatTime,
        getPhotoUrl,
        getInitialsColor,
        manualRetry,
    };
}
