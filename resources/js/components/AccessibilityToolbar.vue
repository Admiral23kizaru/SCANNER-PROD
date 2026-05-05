<template>
  <div
    ref="rootEl"
    class="accessibility-toolbar"
    :class="{
      'accessibility-toolbar--dark': variant === 'dark',
      'accessibility-toolbar--panel-anchor': panelAnchor,
    }"
  >
    <div class="accessibility-toolbar__inner">
      <div class="accessibility-toolbar__dropdown-wrap">
        <button
          type="button"
          class="accessibility-toolbar__circle-btn"
          :aria-expanded="a11yOpen"
          aria-haspopup="true"
          aria-controls="a11y-menu"
          @click.stop="toggleA11y"
        >
          <span class="accessibility-toolbar__sr-only">Accessibility menu</span>
          <FontAwesomeIcon :icon="byPrefixAndName.far['universal-access']" class="accessibility-toolbar__fa" />
        </button>
        <div
          v-show="a11yOpen"
          id="a11y-menu"
          class="accessibility-toolbar__panel"
          role="menu"
          @click.stop
        >
          <button type="button" class="accessibility-toolbar__item" role="menuitem" @click="goAccessibilityStatement">
            <FontAwesomeIcon :icon="byPrefixAndName.far['file-lines']" class="accessibility-toolbar__fa-item" />
            <span>Accessibility statement</span>
          </button>
          <button type="button" class="accessibility-toolbar__item" role="menuitem" @click="toggleContrast">
            <FontAwesomeIcon :icon="byPrefixAndName.far['eye']" class="accessibility-toolbar__fa-item" />
            <span>High contrast</span>
          </button>
          <button type="button" class="accessibility-toolbar__item" role="menuitem" @click="skipToMain">
            <FontAwesomeIcon :icon="byPrefixAndName.far['arrow-down']" class="accessibility-toolbar__fa-item" />
            <span>Skip to main content</span>
          </button>
          <button type="button" class="accessibility-toolbar__item" role="menuitem" @click="skipToEnd">
            <FontAwesomeIcon :icon="byPrefixAndName.far['arrows-down-to-line']" class="accessibility-toolbar__fa-item" />
            <span>Skip to end of page</span>
          </button>
        </div>
      </div>

      <div class="accessibility-toolbar__dropdown-wrap">
        <button
          type="button"
          class="font-size-btn"
          :aria-expanded="fontOpen"
          aria-haspopup="true"
          aria-controls="font-menu"
          @click.stop="toggleFont"
        >
          <span class="accessibility-toolbar__sr-only">Font size menu</span>
          A
        </button>
        <div
          v-show="fontOpen"
          id="font-menu"
          class="accessibility-toolbar__panel"
          role="menu"
          @click.stop
        >
          <button type="button" class="accessibility-toolbar__item accessibility-toolbar__item--text" role="menuitem" @click="fontBigger">
            +A Bigger
          </button>
          <button type="button" class="accessibility-toolbar__item accessibility-toolbar__item--text" role="menuitem" @click="fontSmaller">
            -A Smaller
          </button>
          <button type="button" class="accessibility-toolbar__item accessibility-toolbar__item--text" role="menuitem" @click="fontReset">
            Reset
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { faUniversalAccess, faArrowDown, faArrowsDownToLine } from '@fortawesome/free-solid-svg-icons';
import { faFileLines, faEye } from '@fortawesome/free-regular-svg-icons';
import { onMounted, onUnmounted, ref } from 'vue';

/**
 * Kit-style lookup. In Font Awesome Free 7, `universal-access` and several arrows exist only as solid icons;
 * they are exposed under `far` here so templates can use byPrefixAndName.far['…'] as requested.
 */
const byPrefixAndName = {
  far: {
    'universal-access': faUniversalAccess,
    'file-lines': faFileLines,
    'eye': faEye,
    'arrow-down': faArrowDown,
    'arrows-down-to-line': faArrowsDownToLine,
  },
};

defineProps({
  variant: {
    type: String,
    default: 'light',
    validator: (v) => v === 'light' || v === 'dark',
  },
  /** Absolute top-right (16px) inside a position:relative panel — e.g. login right column */
  panelAnchor: {
    type: Boolean,
    default: false,
  },
});

const rootEl = ref(null);
const a11yOpen = ref(false);
const fontOpen = ref(false);

const FONT_STEP_PX = 2;
const FONT_MIN = 12;
const FONT_MAX = 24;

function parseRootFontPx() {
  const raw = document.documentElement.style.fontSize;
  if (!raw) {
    return 16;
  }
  const n = parseFloat(raw);
  return Number.isFinite(n) ? n : 16;
}

function toggleA11y() {
  fontOpen.value = false;
  a11yOpen.value = !a11yOpen.value;
}

function toggleFont() {
  a11yOpen.value = false;
  fontOpen.value = !fontOpen.value;
}

function closeAll() {
  a11yOpen.value = false;
  fontOpen.value = false;
}

function onDocumentClick(ev) {
  const el = rootEl.value;
  if (el && !el.contains(ev.target)) {
    closeAll();
  }
}

function goAccessibilityStatement() {
  closeAll();
  const node = document.getElementById('a11y-statement');
  if (node) {
    node.scrollIntoView({ behavior: 'smooth', block: 'start' });
    node.focus({ preventScroll: true });
  }
}

function toggleContrast() {
  closeAll();
  document.body.classList.toggle('high-contrast');
}

function skipToMain() {
  closeAll();
  const node = document.getElementById('main-content');
  if (node) {
    node.focus({ preventScroll: false });
    node.scrollIntoView({ behavior: 'smooth', block: 'start' });
  }
}

function skipToEnd() {
  closeAll();
  const node = document.getElementById('page-end');
  if (node) {
    node.focus({ preventScroll: false });
    node.scrollIntoView({ behavior: 'smooth', block: 'end' });
  }
}

function fontBigger() {
  closeAll();
  const next = Math.min(FONT_MAX, parseRootFontPx() + FONT_STEP_PX);
  document.documentElement.style.fontSize = `${next}px`;
}

function fontSmaller() {
  closeAll();
  const next = Math.max(FONT_MIN, parseRootFontPx() - FONT_STEP_PX);
  document.documentElement.style.fontSize = `${next}px`;
}

function fontReset() {
  closeAll();
  document.documentElement.style.fontSize = '16px';
}

onMounted(() => {
  document.addEventListener('click', onDocumentClick, true);
});

onUnmounted(() => {
  document.removeEventListener('click', onDocumentClick, true);
});
</script>

<style scoped>
.accessibility-toolbar {
  position: relative;
  z-index: 50;
}

.accessibility-toolbar--panel-anchor {
  position: absolute;
  top: 16px;
  right: 16px;
  z-index: 20;
}

.accessibility-toolbar__inner {
  display: flex;
  align-items: center;
  gap: 10px;
}

.accessibility-toolbar__dropdown-wrap {
  position: relative;
}

.accessibility-toolbar__circle-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 40px;
  height: 40px;
  padding: 0;
  border-radius: 50%;
  border: 2px solid #0f172a;
  background: #ffffff;
  color: #0f172a;
  cursor: pointer;
  transition: background 0.15s ease, border-color 0.15s ease, color 0.15s ease;
}

.accessibility-toolbar__circle-btn:hover {
  background: #f8fafc;
  border-color: #020617;
}

.accessibility-toolbar__circle-btn:focus {
  outline: 2px solid #0ea5e9;
  outline-offset: 2px;
}

.accessibility-toolbar--dark .accessibility-toolbar__circle-btn {
  border-color: rgba(248, 250, 252, 0.9);
  background: rgba(15, 23, 42, 0.35);
  color: #f8fafc;
}

.accessibility-toolbar--dark .accessibility-toolbar__circle-btn:hover {
  background: rgba(30, 41, 59, 0.55);
  border-color: #ffffff;
}

.accessibility-toolbar--panel-anchor.accessibility-toolbar--dark .accessibility-toolbar__circle-btn {
  background: rgba(2, 6, 23, 0.45);
  border-color: rgba(248, 250, 252, 0.95);
}

.accessibility-toolbar--panel-anchor.accessibility-toolbar--dark .accessibility-toolbar__circle-btn:hover {
  background: rgba(15, 23, 42, 0.65);
}

.font-size-btn {
  font-size: 20px;
  font-weight: bold;
  font-family: serif;
  border: 2px solid #333;
  padding: 5px 10px;
  cursor: pointer;
  background: transparent;
  border-radius: 4px;
  color: #0f172a;
  line-height: 1;
  box-sizing: border-box;
}

.font-size-btn:hover {
  background: rgba(15, 23, 42, 0.06);
}

.font-size-btn:focus {
  outline: 2px solid #0ea5e9;
  outline-offset: 2px;
}

.accessibility-toolbar--dark .font-size-btn {
  border-color: rgba(248, 250, 252, 0.95);
  color: #f8fafc;
  background: rgba(2, 6, 23, 0.35);
}

.accessibility-toolbar--dark .font-size-btn:hover {
  background: rgba(15, 23, 42, 0.55);
}

.accessibility-toolbar--panel-anchor.accessibility-toolbar--dark .font-size-btn {
  background: rgba(2, 6, 23, 0.45);
}

.accessibility-toolbar__fa {
  width: 1.2rem;
  height: 1.2rem;
  display: block;
}

.accessibility-toolbar__fa-item {
  width: 1.125rem;
  height: 1.125rem;
  flex-shrink: 0;
  display: block;
}

.accessibility-toolbar__panel {
  position: absolute;
  top: calc(100% + 6px);
  right: 0;
  min-width: 232px;
  padding: 6px 0;
  background: #ffffff;
  border-radius: 10px;
  box-shadow: 0 10px 30px rgba(15, 23, 42, 0.12), 0 2px 8px rgba(15, 23, 42, 0.08);
  border: 1px solid rgba(15, 23, 42, 0.08);
}

.accessibility-toolbar--dark .accessibility-toolbar__panel {
  background: #1e293b;
  border-color: rgba(148, 163, 184, 0.25);
  box-shadow: 0 12px 32px rgba(0, 0, 0, 0.45);
}

.accessibility-toolbar__item {
  display: flex;
  align-items: center;
  gap: 10px;
  width: 100%;
  padding: 10px 14px;
  border: none;
  background: transparent;
  color: #0f172a;
  font-size: 0.875rem;
  text-align: left;
  cursor: pointer;
  transition: background 0.12s ease;
}

.accessibility-toolbar__item--text {
  font-weight: 600;
}

.accessibility-toolbar--dark .accessibility-toolbar__item {
  color: #f1f5f9;
}

.accessibility-toolbar__item:hover {
  background: #f1f5f9;
}

.accessibility-toolbar--dark .accessibility-toolbar__item:hover {
  background: #334155;
}

.accessibility-toolbar__item:focus {
  outline: none;
  background: #e0f2fe;
}

.accessibility-toolbar--dark .accessibility-toolbar__item:focus {
  background: #475569;
}

.accessibility-toolbar__sr-only {
  position: absolute;
  width: 1px;
  height: 1px;
  padding: 0;
  margin: -1px;
  overflow: hidden;
  clip: rect(0, 0, 0, 0);
  white-space: nowrap;
  border: 0;
}
</style>

<style>
body.high-contrast {
  filter: contrast(1.2) brightness(1.05);
}

body.high-contrast a {
  text-decoration: underline;
}
</style>
