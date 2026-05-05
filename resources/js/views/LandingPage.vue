<template>
  <div class="landing">
    <!-- Section 1 — Top navbar -->
    <header class="top-nav">
      <a class="govph" href="https://www.gov.ph" target="_blank" rel="noreferrer">GOVPH</a>

      <nav class="nav-links" aria-label="Primary">
        <a href="#home" @click.prevent="scrollTo('home')">Home</a>
        <a href="#about" @click.prevent="scrollTo('about')">About</a>
        <a href="#how" @click.prevent="scrollTo('how')">How It Works</a>
        <a href="#schools" @click.prevent="scrollTo('schools')">Schools</a>
        <a href="#contact" @click.prevent="scrollTo('contact')">Contact</a>
      </nav>

      <div class="nav-right">
        <div class="search-wrap">
          <FontAwesomeIcon :icon="faMagnifyingGlass" class="search-icon" />
          <input v-model="search" class="search-input" type="search" placeholder="Search" aria-label="Search" />
        </div>
        <RouterLink class="login-btn" to="/login">Login</RouterLink>
        <AccessibilityToolbar variant="light" />
      </div>
    </header>

    <!-- Section 2 — Orange header banner -->
    <section id="home" class="banner">
      <div class="banner-inner">
        <div class="banner-left">
          <div class="seal">
            <img :src="depedLogo" alt="DepEd Ozamiz City Seal" />
          </div>
          <div class="banner-text">
            <div class="rp">Republic of the Philippines</div>
            <div class="dept">Department of Education - Division of Ozamiz City</div>
            <div class="motto">Para sa Diyos, Para sa Bata, Para sa Bayan</div>
          </div>
        </div>

        <div class="banner-right" aria-live="polite">
          <div class="pst-label">Philippine Standard Time:</div>
          <div class="pst-time">
            <FontAwesomeIcon :icon="faClock" class="pst-icon" />
            <span>{{ pstNow }}</span>
          </div>
        </div>
      </div>
    </section>

    <!-- Section 3 — Contact bar -->
    <section id="contact" class="contact-bar">
      <div class="contact-inner">
        <div class="contact-item">
          <FontAwesomeIcon :icon="faPhone" class="contact-icon" />
          <span>088-545-0998 | 088-545-0990</span>
        </div>
        <div class="contact-item">
          <FontAwesomeIcon :icon="faEnvelope" class="contact-icon" />
          <span>ozamiz.city@deped.gov.ph</span>
        </div>
        <div class="contact-item">
          <FontAwesomeIcon :icon="faFacebook" class="contact-icon" />
          <span>www.fb.com/depedtayoozamiz</span>
        </div>
      </div>
    </section>

    <!-- Section 4 — Systems banner -->
    <section id="schools" class="systems">
      <div class="systems-inner">
        <div class="systems-grid">
          <div class="sys-card">
            <div class="sys-logo sys-logo--doc">DT</div>
            <div class="sys-name">DocTracS</div>
            <div class="sys-desc">Document Tracking System</div>
          </div>
          <div class="sys-card">
            <div class="sys-logo sys-logo--dawn">DP</div>
            <div class="sys-name">D.A.W.N. Protocol</div>
            <div class="sys-desc">Disaster & safety protocol</div>
          </div>
          <div class="sys-card">
            <div class="sys-logo sys-logo--crys">CR</div>
            <div class="sys-name">CRYSTaL</div>
            <div class="sys-desc">Reporting & analytics</div>
          </div>
          <RouterLink class="sys-card sys-card--scanup" to="/scanner">
            <div class="sys-logo sys-logo--scan">
              <FontAwesomeIcon :icon="faQrcode" />
            </div>
            <div class="sys-name">ScanUp</div>
            <div class="sys-desc">QR Attendance System</div>
          </RouterLink>
        </div>
      </div>
    </section>

    <!-- Section 5 — Hero -->
    <section id="about" class="hero">
      <div class="hero-inner">
        <div class="hero-left">
          <h1>Ozamiz Schools QR-ID Attendance System</h1>
          <p>
            Automated student attendance recording via QR code scanning for schools in the Division of Ozamiz City, DepEd
            Region X
          </p>
          <div class="hero-actions">
            <RouterLink class="btn btn-primary" to="/scanner">Get Started</RouterLink>
            <RouterLink class="btn btn-secondary" to="/login">Admin Login</RouterLink>
          </div>
        </div>
        <div class="hero-right" aria-hidden="true">
          <div class="qr-badge">
            <FontAwesomeIcon :icon="faQrcode" class="qr-icon" />
          </div>
        </div>
      </div>
    </section>

    <!-- Section 6 — Stats -->
    <section class="stats">
      <div class="stats-inner">
        <div class="stat-card">
          <div class="stat-label">Total Schools</div>
          <div class="stat-value">58</div>
        </div>
        <div class="stat-card">
          <div class="stat-label">Students Enrolled</div>
          <div class="stat-value">{{ studentsEnrolledText }}</div>
        </div>
        <div class="stat-card">
          <div class="stat-label">Scans Today</div>
          <div class="stat-value">{{ scansTodayText }}</div>
        </div>
      </div>
    </section>

    <!-- Section 7 — How it works -->
    <section id="how" class="how">
      <div class="how-inner">
        <h2>How ScanUp Works</h2>
        <div class="how-steps">
          <div class="step">
            <div class="step-num">1</div>
            <div class="step-icon">
              <svg viewBox="0 0 24 24" aria-hidden="true">
                <path d="M7 7h10v10H7z" fill="none" stroke="currentColor" stroke-width="1.7" />
                <path d="M9 11h6M9 14h4" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" />
              </svg>
            </div>
            <div class="step-title">Student presents QR ID</div>
            <div class="step-desc">The learner shows the QR-ID card at the gate.</div>
          </div>
          <div class="step">
            <div class="step-num">2</div>
            <div class="step-icon">
              <svg viewBox="0 0 24 24" aria-hidden="true">
                <path d="M4 8h16v10H4z" fill="none" stroke="currentColor" stroke-width="1.7" />
                <path d="M8 12h2M14 12h2M10 15h4" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" />
              </svg>
            </div>
            <div class="step-title">Guard scans the QR</div>
            <div class="step-desc">The scanner records attendance instantly and reliably.</div>
          </div>
          <div class="step">
            <div class="step-num">3</div>
            <div class="step-icon">
              <svg viewBox="0 0 24 24" aria-hidden="true">
                <path d="M4 6h16v12H4z" fill="none" stroke="currentColor" stroke-width="1.7" />
                <path d="M4 8l8 6 8-6" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" />
              </svg>
            </div>
            <div class="step-title">Parents receive notification</div>
            <div class="step-desc">SMS/Email can be sent right after the scan.</div>
          </div>
        </div>
      </div>
    </section>

    <!-- Section 8 — Footer -->
    <footer class="footer">
      <div class="footer-inner">
        <div class="footer-col">
          <div class="footer-title">Republic of the Philippines</div>
          <div class="footer-logo">
            <img :src="depedLogo" alt="DepEd logo" />
          </div>
          <div class="footer-text">All content is in the public domain unless otherwise stated</div>
        </div>
        <div class="footer-col">
          <div class="footer-title">About ScanUp</div>
          <div class="footer-text">
            ScanUp is a QR-ID attendance system designed to help schools in the Division of Ozamiz City record attendance
            quickly, accurately, and with optional parent notifications.
          </div>
        </div>
        <div class="footer-col">
          <div class="footer-title">Government Links</div>
          <ul class="footer-links">
            <li><a href="https://www.deped.gov.ph" target="_blank" rel="noreferrer">DepEd Central</a></li>
            <li><a href="https://region10.deped.gov.ph" target="_blank" rel="noreferrer">DepEd Region X</a></li>
            <li><a href="https://ozamiz.deped.gov.ph" target="_blank" rel="noreferrer">Division of Ozamiz City</a></li>
            <li><a href="https://www.gov.ph" target="_blank" rel="noreferrer">GOVPH</a></li>
          </ul>
        </div>
      </div>
      <div class="footer-bottom">© {{ new Date().getFullYear() }} ScanUp · DepEd Division of Ozamiz City</div>
    </footer>
  </div>
</template>

<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { RouterLink } from 'vue-router';
import axios from 'axios';
import AccessibilityToolbar from '../components/AccessibilityToolbar.vue';
import { assetPath } from '../composables/useAsset';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { faPhone, faEnvelope, faMagnifyingGlass, faClock, faQrcode } from '@fortawesome/free-solid-svg-icons';
import { faFacebook } from '@fortawesome/free-brands-svg-icons';

const depedLogo = assetPath('/logo/depedozamiz.png');

const search = ref('');
const pstNow = ref('');
let tick = null;

const scansToday = ref(null);
const studentsEnrolled = ref(null);

function formatPhilippineTime(date) {
  const parts = new Intl.DateTimeFormat('en-US', {
    timeZone: 'Asia/Manila',
    weekday: 'long',
    month: 'long',
    day: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
    second: '2-digit',
    hour12: true,
  }).formatToParts(date);

  const map = Object.fromEntries(parts.map((p) => [p.type, p.value]));
  return `${map.weekday}, ${map.month} ${map.day}, ${map.year}  ${map.hour}:${map.minute}:${map.second} ${map.dayPeriod}`;
}

function updateClock() {
  pstNow.value = formatPhilippineTime(new Date());
}

function scrollTo(id) {
  const el = document.getElementById(id);
  if (!el) return;
  el.scrollIntoView({ behavior: 'smooth', block: 'start' });
}

const scansTodayText = computed(() => (scansToday.value == null ? '—' : String(scansToday.value)));
const studentsEnrolledText = computed(() => (studentsEnrolled.value == null ? '—' : String(studentsEnrolled.value)));

async function loadStats() {
  try {
    const { data } = await axios.get('/api/attendance/public/stats', { headers: { Accept: 'application/json' } });
    scansToday.value = Number(data?.total_today ?? 0);
  } catch {
    scansToday.value = null;
  }
}

onMounted(() => {
  updateClock();
  tick = setInterval(updateClock, 1000);
  loadStats();
});

onUnmounted(() => {
  if (tick) clearInterval(tick);
  tick = null;
});
</script>

<style scoped>
.landing {
  background: #ffffff;
  color: #1f2937;
}

/* Top navbar */
.top-nav {
  display: grid;
  grid-template-columns: 140px 1fr auto;
  align-items: center;
  gap: 16px;
  padding: 10px 16px;
  background: #ffffff;
  border-bottom: 1px solid rgba(31, 41, 55, 0.12);
  position: sticky;
  top: 0;
  z-index: 40;
}

.govph {
  color: #1e3a5f;
  font-weight: 800;
  text-decoration: none;
  letter-spacing: 0.08em;
}

.nav-links {
  display: flex;
  justify-content: center;
  gap: 18px;
  flex-wrap: wrap;
}

.nav-links a {
  color: #1f2937;
  text-decoration: none;
  font-weight: 600;
  padding: 6px 8px;
  border-radius: 6px;
}

.nav-links a:hover {
  background: rgba(249, 115, 22, 0.1);
  color: #ea580c;
}

.nav-right {
  display: flex;
  align-items: center;
  gap: 10px;
}

.search-wrap {
  position: relative;
  display: flex;
  align-items: center;
}

.search-icon {
  position: absolute;
  left: 10px;
  color: rgba(31, 41, 55, 0.65);
  width: 14px;
  height: 14px;
}

.search-input {
  width: 170px;
  padding: 8px 10px 8px 30px;
  border: 1px solid rgba(31, 41, 55, 0.2);
  border-radius: 999px;
  outline: none;
}

.search-input:focus {
  border-color: rgba(234, 88, 12, 0.65);
  box-shadow: 0 0 0 3px rgba(249, 115, 22, 0.18);
}

.login-btn {
  background: #ea580c;
  color: #ffffff;
  text-decoration: none;
  font-weight: 700;
  padding: 9px 12px;
  border-radius: 999px;
}

.login-btn:hover {
  background: #f97316;
}

/* Banner */
.banner {
  background: linear-gradient(90deg, #f97316, #ea580c);
  color: #ffffff;
}

.banner-inner {
  max-width: 1200px;
  margin: 0 auto;
  padding: 18px 16px;
  display: grid;
  grid-template-columns: 1fr 360px;
  gap: 18px;
  align-items: center;
}

.banner-left {
  display: flex;
  align-items: center;
  gap: 14px;
}

.seal {
  width: 64px;
  height: 64px;
  border-radius: 12px;
  background: rgba(255, 255, 255, 0.15);
  border: 1px solid rgba(255, 255, 255, 0.25);
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
}

.seal img {
  width: 58px;
  height: 58px;
  object-fit: contain;
}

.rp {
  font-weight: 800;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  font-size: 12px;
}

.dept {
  font-weight: 800;
  font-size: 16px;
  margin-top: 2px;
}

.motto {
  opacity: 0.92;
  margin-top: 4px;
  font-size: 13px;
}

.banner-right {
  background: rgba(30, 58, 95, 0.22);
  border: 1px solid rgba(255, 255, 255, 0.25);
  border-radius: 14px;
  padding: 12px 14px;
}

.pst-label {
  font-weight: 800;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  font-size: 11px;
  opacity: 0.95;
}

.pst-time {
  display: flex;
  align-items: center;
  gap: 10px;
  font-weight: 800;
  font-size: 14px;
  margin-top: 8px;
  white-space: nowrap;
}

.pst-icon {
  width: 16px;
  height: 16px;
}

/* Contact bar */
.contact-bar {
  background: #f3f4f6;
  border-bottom: 1px solid rgba(31, 41, 55, 0.12);
}

.contact-inner {
  max-width: 1200px;
  margin: 0 auto;
  padding: 10px 16px;
  display: flex;
  flex-wrap: wrap;
  gap: 14px 22px;
  align-items: center;
}

.contact-item {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  color: rgba(31, 41, 55, 0.9);
  font-weight: 600;
}

.contact-icon {
  color: #ea580c;
  width: 14px;
  height: 14px;
}

/* Systems cards */
.systems {
  padding: 18px 0;
}

.systems-inner {
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 16px;
}

.systems-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 12px;
}

.sys-card {
  background: #ffffff;
  border: 1px solid rgba(31, 41, 55, 0.12);
  border-radius: 14px;
  padding: 14px;
  text-decoration: none;
  color: inherit;
  box-shadow: 0 8px 20px rgba(31, 41, 55, 0.06);
  transition: transform 0.15s ease, box-shadow 0.15s ease, border-color 0.15s ease;
}

.sys-card:hover {
  transform: translateY(-2px);
  border-color: rgba(234, 88, 12, 0.35);
  box-shadow: 0 12px 28px rgba(31, 41, 55, 0.1);
}

.sys-logo {
  width: 44px;
  height: 44px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 900;
  background: rgba(30, 58, 95, 0.1);
  color: #1e3a5f;
}

.sys-logo--scan {
  background: rgba(249, 115, 22, 0.14);
  color: #ea580c;
  font-size: 18px;
}

.sys-name {
  margin-top: 10px;
  font-weight: 900;
}

.sys-desc {
  margin-top: 2px;
  color: rgba(31, 41, 55, 0.75);
  font-size: 13px;
}

.sys-card--scanup {
  border-color: rgba(234, 88, 12, 0.35);
}

/* Hero */
.hero {
  background: linear-gradient(135deg, rgba(249, 115, 22, 0.12), rgba(234, 88, 12, 0.06));
  border-top: 1px solid rgba(31, 41, 55, 0.08);
  border-bottom: 1px solid rgba(31, 41, 55, 0.08);
}

.hero-inner {
  max-width: 1200px;
  margin: 0 auto;
  padding: 44px 16px;
  display: grid;
  grid-template-columns: 1.2fr 0.8fr;
  gap: 18px;
  align-items: center;
}

.hero-left h1 {
  font-size: 34px;
  line-height: 1.15;
  margin: 0;
  color: #1e3a5f;
  font-weight: 1000;
}

.hero-left p {
  margin: 12px 0 0 0;
  color: rgba(31, 41, 55, 0.85);
  font-size: 15px;
  max-width: 620px;
}

.hero-actions {
  margin-top: 18px;
  display: flex;
  gap: 12px;
  flex-wrap: wrap;
}

.btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border-radius: 999px;
  padding: 10px 14px;
  text-decoration: none;
  font-weight: 800;
}

.btn-primary {
  background: #ea580c;
  color: #ffffff;
}

.btn-primary:hover {
  background: #f97316;
}

.btn-secondary {
  background: transparent;
  color: #1e3a5f;
  border: 2px solid rgba(30, 58, 95, 0.45);
}

.btn-secondary:hover {
  background: rgba(30, 58, 95, 0.06);
}

.hero-right {
  display: flex;
  justify-content: center;
}

.qr-badge {
  width: 160px;
  height: 160px;
  border-radius: 26px;
  background: linear-gradient(145deg, #f97316, #ea580c);
  box-shadow: 0 18px 40px rgba(234, 88, 12, 0.25);
  display: flex;
  align-items: center;
  justify-content: center;
  animation: float 2.8s ease-in-out infinite;
}

.qr-icon {
  width: 72px;
  height: 72px;
  color: #ffffff;
}

@keyframes float {
  0%,
  100% {
    transform: translateY(0);
  }
  50% {
    transform: translateY(-8px);
  }
}

/* Stats section */
.stats {
  padding: 28px 0;
}

.stats-inner {
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 16px;
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 12px;
}

.stat-card {
  border: 1px solid rgba(31, 41, 55, 0.12);
  border-radius: 14px;
  padding: 16px;
  background: #ffffff;
  box-shadow: 0 8px 20px rgba(31, 41, 55, 0.06);
}

.stat-label {
  font-weight: 800;
  color: rgba(31, 41, 55, 0.75);
  text-transform: uppercase;
  letter-spacing: 0.08em;
  font-size: 11px;
}

.stat-value {
  margin-top: 8px;
  font-weight: 1000;
  font-size: 30px;
  color: #ea580c;
}

/* How section */
.how {
  background: #f3f4f6;
  padding: 34px 0;
}

.how-inner {
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 16px;
}

.how-inner h2 {
  margin: 0 0 16px 0;
  font-size: 22px;
  color: #1e3a5f;
  font-weight: 1000;
}

.how-steps {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 12px;
}

.step {
  background: #ffffff;
  border: 1px solid rgba(31, 41, 55, 0.12);
  border-radius: 14px;
  padding: 16px;
  box-shadow: 0 8px 20px rgba(31, 41, 55, 0.06);
}

.step-num {
  width: 34px;
  height: 34px;
  border-radius: 999px;
  background: #ea580c;
  color: #ffffff;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 1000;
}

.step-icon {
  margin-top: 12px;
  width: 44px;
  height: 44px;
  border-radius: 12px;
  background: rgba(249, 115, 22, 0.12);
  color: #ea580c;
  display: flex;
  align-items: center;
  justify-content: center;
}

.step-icon svg {
  width: 24px;
  height: 24px;
}

.step-title {
  margin-top: 10px;
  font-weight: 1000;
  color: #1e3a5f;
}

.step-desc {
  margin-top: 4px;
  color: rgba(31, 41, 55, 0.78);
  font-weight: 600;
  font-size: 13px;
}

/* Footer */
.footer {
  background: #1e3a5f;
  color: rgba(255, 255, 255, 0.9);
}

.footer-inner {
  max-width: 1200px;
  margin: 0 auto;
  padding: 28px 16px;
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 18px;
}

.footer-title {
  font-weight: 1000;
  margin-bottom: 10px;
  letter-spacing: 0.04em;
}

.footer-logo img {
  width: 54px;
  height: 54px;
  object-fit: contain;
  background: rgba(255, 255, 255, 0.1);
  border-radius: 10px;
  padding: 6px;
}

.footer-text {
  color: rgba(255, 255, 255, 0.82);
  font-weight: 600;
  font-size: 13px;
  line-height: 1.5;
}

.footer-links {
  list-style: none;
  padding: 0;
  margin: 0;
  display: grid;
  gap: 8px;
}

.footer-links a {
  color: rgba(255, 255, 255, 0.9);
  text-decoration: none;
  font-weight: 700;
}

.footer-links a:hover {
  text-decoration: underline;
}

.footer-bottom {
  border-top: 1px solid rgba(255, 255, 255, 0.18);
  padding: 12px 16px;
  text-align: center;
  font-weight: 700;
  color: rgba(255, 255, 255, 0.8);
}

/* Responsiveness */
@media (max-width: 980px) {
  .top-nav {
    grid-template-columns: 1fr;
    justify-items: start;
  }
  .nav-links {
    justify-content: flex-start;
  }
  .nav-right {
    width: 100%;
    justify-content: space-between;
  }
  .banner-inner {
    grid-template-columns: 1fr;
  }
  .systems-grid {
    grid-template-columns: repeat(2, 1fr);
  }
  .hero-inner {
    grid-template-columns: 1fr;
  }
  .stats-inner {
    grid-template-columns: 1fr;
  }
  .how-steps {
    grid-template-columns: 1fr;
  }
  .footer-inner {
    grid-template-columns: 1fr;
  }
  .search-input {
    width: 150px;
  }
}
</style>

