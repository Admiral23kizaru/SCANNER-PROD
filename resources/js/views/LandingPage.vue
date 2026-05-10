<template>
  <div class="landing">
    <nav class="navbar" :class="{ 'nav-open': isMobileMenuOpen }">
      <button
        class="hamburger-btn"
        type="button"
        @click.stop="toggleMobileMenu"
        :aria-expanded="isMobileMenuOpen ? 'true' : 'false'"
        aria-label="Toggle navigation menu"
      >
        <span></span>
        <span></span>
        <span></span>
      </button>

      <div
        v-if="isMobileMenuOpen"
        class="mobile-nav-backdrop"
        @click="closeMobileMenu"
      ></div>

      <div class="navbar-left">
        <!-- GOVPH -->
        <a href="https://www.gov.ph" target="_blank" class="nav-govph" rel="noreferrer">GOVPH</a>

        <!-- Home -->
        <div class="nav-item">
          <a href="https://ozamiz.deped.gov.ph/" target="_blank" class="nav-trigger" rel="noreferrer">Home</a>
        </div>

        <!-- About -->
        <div class="nav-item" :class="{ open: openDropdown === 'about' }">
          <button class="nav-trigger" @click.stop="toggleDropdown('about')">
            About <span class="nav-arrow">▼</span>
          </button>
          <div class="dropdown-menu" v-show="openDropdown === 'about'">
            <div class="dropdown-section">Overview</div>
            <a href="https://ozamiz.deped.gov.ph/history/" target="_blank" rel="noreferrer">History</a>
            <a
              href="https://ozamiz.deped.gov.ph/vision-mission-core-values-and-mandate/"
              target="_blank"
              rel="noreferrer"
            >Vision, Mission, Core Values, and Mandate</a>
            <a href="https://ozamiz.deped.gov.ph/division-map/" target="_blank" rel="noreferrer">Division Map</a>

            <div class="dropdown-section">Organizational Structure</div>
            <a href="https://ozamiz.deped.gov.ph/organizational-structure/" target="_blank" rel="noreferrer">
              DepEd Ozamiz City Executive Committee
            </a>

            <div class="dropdown-section">DepEd Data Privacy</div>
            <a href="https://ozamiz.deped.gov.ph/data-privacy-notice/" target="_blank" rel="noreferrer">Data Privacy Notice</a>

            <div class="dropdown-divider"></div>
            <a href="https://ozamiz.deped.gov.ph/citizens-charter/" target="_blank" rel="noreferrer" class="direct-link">
              Citizen's Charter
            </a>
            <a href="https://ozamiz.deped.gov.ph/freedom-of-information/" target="_blank" rel="noreferrer" class="direct-link">
              Freedom of Information
            </a>

            <div class="dropdown-section">Careers</div>
            <a href="https://ozamiz.deped.gov.ph/careers/job-opportunities/" target="_blank" rel="noreferrer">Job Opportunities</a>
            <a href="https://ozamiz.deped.gov.ph/careers/notice-of-appointment/" target="_blank" rel="noreferrer">Notice of Appointment</a>
          </div>
        </div>

        <!-- Resources -->
        <div class="nav-item" :class="{ open: openDropdown === 'resources' }">
          <button class="nav-trigger" @click.stop="toggleDropdown('resources')">
            Resources <span class="nav-arrow">▼</span>
          </button>
          <div class="dropdown-menu" v-show="openDropdown === 'resources'">
            <div class="dropdown-section">Issuances</div>
            <a href="https://ozamiz.deped.gov.ph/category/divisional-memo/" target="_blank" rel="noreferrer">Divisional Memoranda</a>
            <a href="https://ozamiz.deped.gov.ph/category/divisional-advisories/" target="_blank" rel="noreferrer">Divisional Advisories</a>
            <a href="https://ozamiz.deped.gov.ph/category/unnumbered-memo/" target="_blank" rel="noreferrer">Unnumbered Memoranda</a>

            <div class="dropdown-section">Downloadables</div>
            <a href="https://ozamiz.deped.gov.ph/administrative-services/" target="_blank" rel="noreferrer">Administrative Services</a>
            <a href="https://ozamiz.deped.gov.ph/property-and-supply-services/" target="_blank" rel="noreferrer">Property and Supply Services</a>
            <a
              href="https://ozamiz.deped.gov.ph/information-and-communications-technology-office/"
              target="_blank"
              rel="noreferrer"
            >Information and Communications Technology Services</a>
          </div>
        </div>

        <!-- Procurement -->
        <div class="nav-item" :class="{ open: openDropdown === 'procurement' }">
          <button class="nav-trigger" @click.stop="toggleDropdown('procurement')">
            Procurement <span class="nav-arrow">▼</span>
          </button>
          <div class="dropdown-menu" v-show="openDropdown === 'procurement'">
            <a href="https://ozamiz.deped.gov.ph/category/bid-opportunity/" target="_blank" rel="noreferrer" class="direct-link">
              Bid Opportunities
            </a>
            <a href="https://ozamiz.deped.gov.ph/category/award-notice/" target="_blank" rel="noreferrer" class="direct-link">
              Award Notices
            </a>
          </div>
        </div>

        <!-- Contact Us -->
        <div class="nav-item" :class="{ open: openDropdown === 'contact' }">
          <button class="nav-trigger" @click.stop="toggleDropdown('contact')">
            Contact Us <span class="nav-arrow">▼</span>
          </button>
          <div class="dropdown-menu" v-show="openDropdown === 'contact'">
            <a href="https://rb.gy/86zo4" target="_blank" rel="noreferrer" class="direct-link">Request Form</a>
            <a href="https://rb.gy/62zeg" target="_blank" rel="noreferrer" class="direct-link">Request Form Status</a>
            <a
              href="https://docs.google.com/forms/d/13ZMWbDV2yjnbrMcJobupSf_K_iU3WuBW34a2LHlfv84/viewform?edit_requested=true"
              target="_blank"
              rel="noreferrer"
              class="direct-link"
            >Locator Slip Request</a>
            <a
              href="https://docs.google.com/spreadsheets/d/1Y9XKfT_biqEdEHxHrGQhCihb_78fxhZlpneaB-IF-j8/edit?gid=699627690#gid=699627690"
              target="_blank"
              rel="noreferrer"
              class="direct-link"
            >Print Locator Slip</a>
            <a href="https://rb.gy/5jm8j0" target="_blank" rel="noreferrer" class="direct-link">Travel Authority Auto-Tagging</a>
            <a
              href="https://docs.google.com/spreadsheets/d/1qwHUam829jOMJZKxZhLWmtchIwrEBLGScy92Y_YzJ-g/edit?gid=234499877#gid=234499877"
              target="_blank"
              rel="noreferrer"
              class="direct-link"
            >Print Travel Authority</a>
            <a
              href="https://docs.google.com/forms/d/18KdatsQG3-St6mskBcl3TdBGGwegPnW_YFx7LfxqhCg/edit"
              target="_blank"
              rel="noreferrer"
              class="direct-link"
            >Request for Vehicle</a>
            <a
              href="https://docs.google.com/spreadsheets/d/166wz4hp1Cn8qdtPdkrJvu0pCxH8UKRYLGqs0CnSBhKQ/edit?gid=1562769871#gid=1562769871"
              target="_blank"
              rel="noreferrer"
              class="direct-link"
            >Print Request for Vehicle</a>
          </div>
        </div>
      </div>

      <!-- Right side -->
      <div class="navbar-right">
        <router-link to="/login" class="login-btn">Login</router-link>
        <input
          v-model="searchQuery"
          type="text"
          placeholder="Search..."
          class="search-bar"
          @keyup="handleSearch"
        />
        <AccessibilityToolbar />
      </div>
    </nav>

    <!-- Section 2 — Department banner -->
    <section id="home" class="banner" aria-label="Division banner">
      <img
        class="banner-header-img"
        :src="landingHeaderImg"
        alt="Department of Education Division of Ozamiz City"
        width="1920"
        height="360"
        loading="eager"
        decoding="async"
      />
      <div class="banner-overlay">
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
        <div class="banner-time" aria-live="polite">
          <div class="pst-label">Philippine Standard Time</div>
          <div class="pst-time">{{ pstNow }}</div>
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
          <a class="sys-card" href="http://58.69.118.16:88/dts/" target="_blank" rel="noopener noreferrer">
            <div class="sys-logo">
              <img :src="doctracsImg" alt="DocTracS" class="sys-icon-img" />
            </div>
            <div class="sys-desc">Document Tracking System</div>
          </a>
          <a class="sys-card" href="http://58.69.118.16:83/ehris/" target="_blank" rel="noopener noreferrer">
            <div class="sys-logo sys-logo--dawn">
              <img :src="dawnImg" alt="D.A.W.N. Protocol" class="sys-icon-img" />
            </div>
            <div class="sys-desc">Disaster & safety protocol</div>
          </a>
          <a class="sys-card" href="https://ozamiz.deped.gov.ph/crystal/" target="_blank" rel="noopener noreferrer">
            <div class="sys-logo sys-logo--crys">
              <img :src="crystalImg" alt="CRYSTaL" class="sys-icon-img" />
            </div>
            <div class="sys-desc">Reporting & analytics</div>
          </a>
          <a
            class="sys-card"
            href="https://dtsnew.deped10.com/login"
            target="_blank"
            rel="noopener noreferrer"
          >
            <div class="sys-logo">
              <img :src="roxsCmssImg" alt="ROX-CMSSS" class="sys-icon-img" />
            </div>
            <div class="sys-desc">Curriculum Management Survey System</div>
          </a>
        </div>
      </div>
    </section>

    <!-- Section 5 — Content Row: Carousel + YouTube + Sidebar -->
    <section class="content-row-wrapper">
      <div class="content-row">
        <!-- Left: Carousel + YouTube -->
        <div class="content-left">
          <!-- Image Carousel -->
          <div class="carousel">
            <div class="carousel-slides">
              <div
                v-for="(slide, idx) in carouselSlides"
                :key="idx"
                class="carousel-slide"
                :class="{ active: currentSlide === idx }"
              >
                <img :src="slide.src" :alt="slide.alt" @error="onFooterImgError($event, slide.filename)" />
              </div>
            </div>
            <button class="carousel-btn prev" @click="prevSlide" aria-label="Previous slide">&#8249;</button>
            <button class="carousel-btn next" @click="nextSlide" aria-label="Next slide">&#8250;</button>
            <div class="carousel-dots">
              <button
                v-for="(slide, idx) in carouselSlides"
                :key="idx"
                class="carousel-dot"
                :class="{ active: currentSlide === idx }"
                @click="goToSlide(idx)"
                :aria-label="'Go to slide ' + (idx + 1)"
              ></button>
            </div>
          </div>

          <!-- YouTube Embed -->
          <iframe
            width="100%"
            height="400"
            src="https://www.youtube.com/embed/2d-kepxpafU?si=rajCjyiBAlEXh8dZ"
            title="YouTube video player"
            frameborder="0"
            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
            referrerpolicy="strict-origin-when-cross-origin"
            allowfullscreen
            class="youtube-embed"
          ></iframe>
        </div>

        <!-- Right: Sidebar widgets -->
        <div class="content-right">
          <!-- CSM QR Code -->
          <a href="https://ozamiz.deped.gov.ph/csm/" target="_blank" rel="noreferrer">
            <img
              :src="imageTempSrc('cms-qr.jpg')"
              @error="onFooterImgError($event, 'cms-qr.jpg')"
              alt="Division of Ozamiz City Client Satisfaction Measurement"
              style="width:100%; border-radius:8px;"
            />
          </a>

          <!-- Facebook Page Embed -->
          <iframe
            src="https://www.facebook.com/plugins/page.php?href=https%3A%2F%2Fwww.facebook.com%2Fdepedtayoozamiz&tabs=timeline&width=340&height=500&small_header=false&adapt_container_width=true&hide_cover=false&show_facepile=true&appId"
            width="100%"
            height="500"
            class="facebook-embed"
            scrolling="no"
            frameborder="0"
            allowfullscreen="true"
            allow="autoplay; clipboard-write; encrypted-media; picture-in-picture; web-share"
          ></iframe>
        </div>
      </div>
    </section>

    <!-- Section 6 — How it works -->
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

    <!-- Section 7 — Hero -->
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
          <div class="hero-illustration">
            <!-- Student figure -->
            <div class="illust-student">
              <div class="illust-head"></div>
              <div class="illust-body">
                <div class="illust-arm illust-arm-l"></div>
                <div class="illust-arm illust-arm-r"></div>
              </div>
            </div>

            <!-- ID Card held by student -->
            <div class="illust-card">
              <div class="illust-card-photo"></div>
              <div class="illust-card-line"></div>
              <div class="illust-card-line illust-card-line-m"></div>
              <div class="illust-card-line illust-card-line-s"></div>
              <div class="illust-qr-grid">
                <span></span><span class="q-b"></span><span></span><span class="q-b"></span>
                <span class="q-b"></span><span></span><span class="q-b"></span><span></span>
                <span></span><span class="q-b"></span><span class="q-b"></span><span></span>
                <span class="q-b"></span><span></span><span></span><span class="q-b"></span>
              </div>
              <!-- Scan laser line -->
              <div class="illust-scan-line"></div>
            </div>

            <!-- Scanner device -->
            <div class="illust-scanner">
              <div class="illust-scanner-screen"></div>
              <div class="illust-scanner-base"></div>
            </div>

            <!-- Success checkmark -->
            <div class="illust-check">✓</div>

            <!-- Notification bubble -->
            <div class="illust-notify">✓ Parent Notified</div>
          </div>
        </div>
      </div>
    </section>

    <!-- Section 8 — Stats -->
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

    <!-- Section 8 — Footer -->
    <footer class="footer">
      <div class="systems-banner">
        <img
          :src="imageTempSrc('EBEIS.png')"
          @error="onFooterImgError($event, 'EBEIS.png')"
          alt="Enhanced Basic Education Information System banner"
        />
        <img
          :src="imageTempSrc('LIS.png')"
          @error="onFooterImgError($event, 'LIS.png')"
          alt="Learner Information System banner"
        />
        <img
          :src="imageTempSrc('LRMDS.png')"
          @error="onFooterImgError($event, 'LRMDS.png')"
          alt="Learning Resource Management and Development System banner"
        />
        <img
          :src="imageTempSrc('dpds.png')"
          @error="onFooterImgError($event, 'dpds.png')"
          alt="DepEd Partnership Database System banner"
        />
      </div>

      <div class="seals-row">
        <div class="seal-item">
          <img
            :src="imageTempSrc('foi-logo-160x160-4.png')"
            @error="onFooterImgError($event, 'foi-logo-160x160-4.png')"
            alt="Freedom of Information Philippines seal"
          />
        </div>
        <div class="seal-item">
          <img
            :src="imageTempSrc('transparency-seal-160x160-2.png')"
            @error="onFooterImgError($event, 'transparency-seal-160x160-2.png')"
            alt="Philippine Transparency Seal"
          />
        </div>
        <div class="seal-item">
          <img
            :src="imageTempSrc('depedlogo-1.png')"
            @error="onFooterImgError($event, 'depedlogo-1.png')"
            alt="Kagawaran ng Edukasyon seal"
          />
        </div>
        <div class="seal-item">
          <img
            :src="imageTempSrc('region-1.png')"
            @error="onFooterImgError($event, 'region-1.png')"
            alt="DepEd Region X seal"
          />
        </div>
      </div>

      <div class="footer-main">
        <div class="footer-col">
          <h3 class="footer-title">REPUBLIC OF THE PHILIPPINES</h3>
          <img
            class="footer-deped-logo"
            :src="imageTempSrc('depedlogo-1.png')"
            @error="onFooterImgError($event, 'depedlogo-1.png')"
            alt="Department of Education logo"
          />
          <p class="footer-note">
            All content is in the public domain unless otherwise stated.
          </p>
        </div>

        <div class="footer-col">
          <h3 class="footer-title">ABOUT GOVPH</h3>
          <p class="footer-about">
            Learn more about the Philippine government, its structure, how government works and the people behind it.
          </p>
          <a class="footer-link" href="https://www.gov.ph" target="_blank" rel="noreferrer">GOV.PH</a>
          <a class="footer-link" href="https://data.gov.ph" target="_blank" rel="noreferrer">Open Data Portal</a>
          <a class="footer-link" href="https://www.officialgazette.gov.ph" target="_blank" rel="noreferrer">Official Gazette</a>
        </div>

        <div class="footer-col">
          <h3 class="footer-title">GOVERNMENT LINKS</h3>
          <a class="footer-link" href="https://www.president.gov.ph" target="_blank" rel="noreferrer">Office of the President</a>
          <a class="footer-link" href="https://www.ovp.gov.ph" target="_blank" rel="noreferrer">Office of the Vice President</a>
          <a class="footer-link" href="https://www.senate.gov.ph" target="_blank" rel="noreferrer">Senate of the Philippines</a>
          <a class="footer-link" href="https://www.congress.gov.ph" target="_blank" rel="noreferrer">House of Representatives</a>
          <a class="footer-link" href="https://sc.judiciary.gov.ph" target="_blank" rel="noreferrer">Supreme Court</a>
          <a class="footer-link" href="https://ca.judiciary.gov.ph" target="_blank" rel="noreferrer">Court of Appeals</a>
          <a class="footer-link" href="https://www.sandiganbayan.gov.ph" target="_blank" rel="noreferrer">Sandiganbayan</a>
        </div>
      </div>

      <div class="footer-bottom">© 2026 ScanUp · DepEd Division of Ozamiz City</div>
    </footer>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { RouterLink, useRouter } from 'vue-router';
import axios from 'axios';
import AccessibilityToolbar from '../components/AccessibilityToolbar.vue';
import { assetPath } from '../composables/useAsset';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { faPhone, faEnvelope, faMagnifyingGlass, faQrcode } from '@fortawesome/free-solid-svg-icons';
import { faFacebook } from '@fortawesome/free-brands-svg-icons';

const depedLogo = assetPath('/logo/depedozamiz.png');
const landingHeaderImg = assetPath('/images/landing-header.jpg');
const doctracsImg = assetPath('/images/doctracs.png');
const crystalImg = assetPath('/images/crystal2.png');
const dawnImg = assetPath('/images/dawn.png');
const roxsCmssImg = assetPath('/images/roxs-cmss.png');
const scanupImg = assetPath('/images/scanup.png');

const openDropdown = ref(null);
const searchQuery = ref('');
const isMobileMenuOpen = ref(false);
const router = useRouter();
const pstNow = ref('');
let tick = null;

// Carousel state
const currentSlide = ref(0);
let carouselInterval = null;
const carouselSlides = [
  { src: imageTempSrc('MATATAG-Cover-Photo.jpg'), alt: 'MATATAG Cover Photo', filename: 'MATATAG-Cover-Photo.jpg' },
  { src: imageTempSrc('BER-banner-2048x759-2.jpg'), alt: 'BER Banner', filename: 'BER-banner-2048x759-2.jpg' },
  { src: imageTempSrc('Web-banner-01-4-1.png'), alt: 'Web Banner', filename: 'Web-banner-01-4-1.png' },
];

function nextSlide() {
  currentSlide.value = (currentSlide.value + 1) % carouselSlides.length;
}
function prevSlide() {
  currentSlide.value = (currentSlide.value - 1 + carouselSlides.length) % carouselSlides.length;
}
function goToSlide(idx) {
  currentSlide.value = idx;
}
function startCarousel() {
  carouselInterval = setInterval(nextSlide, 3000);
}
function stopCarousel() {
  if (carouselInterval) clearInterval(carouselInterval);
  carouselInterval = null;
}

const primaryImageTempBase = '/image_temp/';
const secondaryImageTempBase = '/SCANNER_PROD1/SCANNER-PROD/public/image_temp/';

function imageTempSrc(filename) {
  // Keep slashes, encode spaces/special chars in filename.
  return primaryImageTempBase + encodeURIComponent(filename).replace(/%2F/g, '/');
}

function imageTempFallbackSrc(filename) {
  return secondaryImageTempBase + encodeURIComponent(filename).replace(/%2F/g, '/');
}

function onFooterImgError(event, filename) {
  const img = event?.target;
  if (!img || img.dataset?.fallbackApplied === '1') return;
  img.dataset.fallbackApplied = '1';
  img.src = imageTempFallbackSrc(filename);
}

const scansToday = ref(null);
const studentsEnrolled = ref(null);

function toggleDropdown(name) {
  openDropdown.value = openDropdown.value === name ? null : name;
}

function closeAll() {
  openDropdown.value = null;
}

function toggleMobileMenu() {
  isMobileMenuOpen.value = !isMobileMenuOpen.value;
}

function closeMobileMenu() {
  isMobileMenuOpen.value = false;
}

function handleSearch(e) {
  if (e.key === 'Enter' && searchQuery.value.trim()) {
    window.open(
      `https://ozamiz.deped.gov.ph/?s=${encodeURIComponent(searchQuery.value)}`,
      '_blank',
    );
  }
}

function handleClickOutside(e) {
  if (!e.target.closest('.navbar')) {
    closeMobileMenu();
  }
  if (!e.target.closest('.nav-item') && !e.target.closest('.nav-trigger')) {
    closeAll();
  }
}

const scansTodayText = computed(() => (scansToday.value == null ? '—' : String(scansToday.value)));
const studentsEnrolledText = computed(() => (studentsEnrolled.value == null ? '—' : String(studentsEnrolled.value)));

function formatPhilippineTime(date) {
  const parts = new Intl.DateTimeFormat('en-US', {
    timeZone: 'Asia/Manila',
    weekday: 'long',
    month: 'short',
    day: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
    second: '2-digit',
    hour12: true,
  }).formatToParts(date);

  const map = Object.fromEntries(parts.map((p) => [p.type, p.value]));
  return `${map.weekday}, ${map.month} ${map.day}, ${map.year}, ${map.hour}:${map.minute}:${map.second} ${map.dayPeriod}`;
}

function updateClock() {
  pstNow.value = formatPhilippineTime(new Date());
}

async function loadStats() {
  try {
    const { data } = await axios.get('/api/attendance/public/stats', { headers: { Accept: 'application/json' } });
    scansToday.value = Number(data?.total_today ?? 0);
  } catch {
    scansToday.value = null;
  }
}

onMounted(() => {
  document.addEventListener('click', handleClickOutside);
  updateClock();
  tick = setInterval(updateClock, 1000);
  loadStats();
  startCarousel();
});

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside);
  if (tick) clearInterval(tick);
  tick = null;
  stopCarousel();
});
</script>

<style scoped>
.landing {
  background: #ffffff;
  color: #1f2937;
  width: 100%;
  overflow-x: hidden;
}

/* Navbar (dropdown, govph style) */
.navbar {
  display: flex;
  align-items: center;
  background: white;
  border-bottom: 1px solid #e0e0e0;
  padding: 0 16px;
  position: sticky;
  top: 0;
  z-index: 999;
  height: 56px;
  box-shadow: 0 1px 4px rgba(0,0,0,0.08);
}
.hamburger-btn {
  display: none;
  width: 40px;
  height: 40px;
  margin-right: 10px;
  border: 1px solid #e5e7eb;
  border-radius: 10px;
  background: #fff;
  cursor: pointer;
  align-items: center;
  justify-content: center;
  flex-direction: column;
  gap: 5px;
  z-index: 1002;
}
.hamburger-btn span {
  width: 18px;
  height: 2px;
  background: #1f2937;
  border-radius: 2px;
  display: block;
}
.mobile-nav-backdrop {
  position: fixed;
  inset: 0;
  background: rgba(15, 23, 42, 0.4);
  z-index: 995;
}
.navbar-left {
  display: flex;
  align-items: center;
  flex: 1;
  height: 100%;
}
.navbar-right {
  display: flex;
  align-items: center;
  gap: 10px;
  height: 100%;
}
.nav-govph {
  font-weight: 700;
  font-size: 14px;
  color: #333;
  text-decoration: none;
  padding: 0 14px;
  height: 100%;
  display: flex;
  align-items: center;
  border-right: 1px solid #e0e0e0;
}
.nav-govph:hover {
  background: #f5f5f5;
}
.nav-item {
  position: relative;
  height: 56px;
  display: flex;
  align-items: center;
}
.nav-trigger {
  padding: 0 14px;
  height: 100%;
  display: flex;
  align-items: center;
  gap: 5px;
  color: #333;
  font-size: 14px;
  font-weight: 500;
  text-decoration: none;
  border: none;
  background: none;
  cursor: pointer;
  white-space: nowrap;
  font-family: inherit;
}
.nav-trigger:hover,
.nav-item.open .nav-trigger {
  background: #f0f0f0;
}
.nav-arrow {
  font-size: 9px;
  transition: transform 0.2s ease;
  display: inline-block;
  margin-top: 1px;
}
.nav-item.open .nav-arrow {
  transform: rotate(180deg);
}
.dropdown-menu {
  position: absolute;
  top: 56px;
  left: 0;
  background: white;
  min-width: 300px;
  box-shadow: 0 6px 20px rgba(0,0,0,0.12);
  z-index: 1000;
  border-top: 3px solid #F97316;
  padding: 6px 0 10px;
}
.dropdown-section {
  padding: 10px 20px 4px;
  font-size: 10px;
  font-weight: 700;
  text-transform: uppercase;
  color: #aaa;
  letter-spacing: 0.8px;
  margin-top: 6px;
}
.dropdown-section:first-child {
  margin-top: 0;
}
.dropdown-divider {
  height: 1px;
  background: #f0f0f0;
  margin: 6px 0;
}
.dropdown-menu a {
  display: block;
  padding: 8px 20px 8px 28px;
  color: #444;
  text-decoration: none;
  font-size: 13px;
  line-height: 1.5;
}
.dropdown-menu a.direct-link {
  padding-left: 20px;
  font-weight: 500;
  color: #333;
}
.dropdown-menu a:hover {
  background: #fff5ee;
  color: #F97316;
}
.login-btn {
  color: #333;
  font-size: 14px;
  font-weight: 500;
  text-decoration: none;
  padding: 6px 12px;
  white-space: nowrap;
  border-radius: 4px;
}
.login-btn:hover {
  color: #F97316;
  background: #fff5ee;
}
.search-bar {
  border: 1px solid #ccc;
  border-radius: 20px;
  padding: 5px 14px;
  font-size: 13px;
  width: 150px;
  outline: none;
  font-family: inherit;
}
.search-bar:focus {
  border-color: #F97316;
  box-shadow: 0 0 0 2px rgba(249,115,22,0.15);
}

/* Header image banner */
.banner {
  position: relative;
  margin: 0;
  padding: 0;
  line-height: normal;
  background: #ea580c;
}

.banner-header-img {
  width: 100%;
  height: clamp(76px, 11vw, 120px);
  display: block;
  object-fit: cover;
  object-position: center center;
  filter: saturate(1.1);
}

.banner-overlay {
  position: absolute;
  inset: 0;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  padding: 8px 16px;
}

.banner-left {
  display: flex;
  align-items: center;
  gap: 10px;
  min-width: 0;
}

.seal {
  width: 56px;
  height: 56px;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.08);
  border: 1px solid rgba(255, 255, 255, 0.45);
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
}

.seal img {
  width: 52px;
  height: 52px;
  object-fit: contain;
}

.banner-text {
  color: #0f172a;
  text-shadow: 0 1px 0 rgba(255, 255, 255, 0.35);
}

.rp {
  font-weight: 700;
  font-size: 10px;
  line-height: 1.05;
  letter-spacing: 0.08em;
  text-transform: uppercase;
}

.dept {
  font-weight: 800;
  font-size: clamp(13px, 2.3vw, 18px);
  line-height: 1.15;
}

.motto {
  margin-top: 2px;
  font-size: clamp(10px, 1.6vw, 11px);
  font-weight: 700;
}

.banner-time {
  text-align: right;
  color: #111827;
  text-shadow: 0 1px 0 rgba(255, 255, 255, 0.35);
}

.pst-label {
  font-size: 11px;
  font-weight: 700;
}

.pst-time {
  font-size: 12px;
  font-weight: 700;
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
  grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
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
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.sys-card:hover {
  transform: translateY(-2px);
  border-color: rgba(234, 88, 12, 0.35);
  box-shadow: 0 12px 28px rgba(31, 41, 55, 0.1);
}

.sys-logo {
  width: 100%;
  height: 56px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 900;
  background: transparent;
  color: #1e3a5f;
  overflow: hidden;
}

.sys-icon-img {
  width: 100%;
  height: 100%;
  object-fit: contain;
  display: block;
}

.sys-logo--scan {
  background: transparent;
  color: inherit;
  font-size: unset;
}

.sys-desc {
  margin-top: 0;
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
  font-size: clamp(24px, 4.5vw, 34px);
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

/* ── Hero CSS Illustration ─────────────────────────────── */
.hero-illustration {
  position: relative;
  width: clamp(230px, 42vw, 300px);
  height: clamp(230px, 42vw, 300px);
  display: flex;
  align-items: flex-end;
  justify-content: center;
  background: rgba(249, 115, 22, 0.08);
  border-radius: 20px;
  overflow: visible;
  gap: 18px;
  padding-bottom: 30px;
}

/* Student figure */
.illust-student {
  display: flex;
  flex-direction: column;
  align-items: center;
  z-index: 2;
}
.illust-head {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  background: #FDBCB4;
  border: 2px solid #e8a99f;
  margin-bottom: -4px;
  z-index: 1;
}
.illust-body {
  width: 50px;
  height: 60px;
  background: #1a237e;
  border-radius: 10px 10px 6px 6px;
  position: relative;
}
.illust-arm {
  position: absolute;
  width: 12px;
  height: 35px;
  background: #1a237e;
  border-radius: 6px;
  top: 8px;
}
.illust-arm-l {
  left: -10px;
  transform: rotate(25deg);
}
.illust-arm-r {
  right: -10px;
  transform: rotate(-25deg);
}

/* ID Card */
.illust-card {
  width: 80px;
  height: 100px;
  background: #fff;
  border: 2.5px solid #F97316;
  border-radius: 8px;
  padding: 8px 6px;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 4px;
  transform: rotate(-5deg);
  position: relative;
  z-index: 3;
  box-shadow: 0 4px 16px rgba(0,0,0,0.1);
  margin-left: -18px;
  margin-bottom: 10px;
}
.illust-card-photo {
  width: 22px;
  height: 22px;
  border-radius: 50%;
  background: #e0e0e0;
}
.illust-card-line {
  width: 90%;
  height: 4px;
  background: #d0d0d0;
  border-radius: 2px;
}
.illust-card-line-m { width: 70%; }
.illust-card-line-s { width: 50%; }
.illust-qr-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 2px;
  width: 32px;
  height: 32px;
  margin-top: 2px;
}
.illust-qr-grid span {
  background: #e0e0e0;
  border-radius: 1px;
}
.illust-qr-grid .q-b {
  background: #222;
}

/* Scan laser */
.illust-scan-line {
  position: absolute;
  left: 4px;
  right: 4px;
  height: 2px;
  background: #00e676;
  box-shadow: 0 0 8px #00e676, 0 0 16px rgba(0,230,118,0.4);
  border-radius: 2px;
  animation: illust-scan 2s ease-in-out infinite;
}
@keyframes illust-scan {
  0%   { top: 10%; opacity: 1; }
  50%  { top: 80%; opacity: 0.8; }
  100% { top: 10%; opacity: 1; }
}

/* Scanner device */
.illust-scanner {
  display: flex;
  flex-direction: column;
  align-items: center;
  z-index: 1;
  margin-left: -10px;
}
.illust-scanner-screen {
  width: 50px;
  height: 60px;
  background: #263238;
  border-radius: 6px 6px 2px 2px;
  border: 2px solid #455a64;
  box-shadow: inset 0 0 12px rgba(0,230,118,0.25);
}
.illust-scanner-base {
  width: 60px;
  height: 10px;
  background: #90a4ae;
  border-radius: 0 0 4px 4px;
}

/* Success checkmark */
.illust-check {
  position: absolute;
  top: 30px;
  right: 50px;
  width: 32px;
  height: 32px;
  border-radius: 50%;
  background: #00c853;
  color: #fff;
  font-size: 18px;
  font-weight: 900;
  display: flex;
  align-items: center;
  justify-content: center;
  animation: illust-floatUp 3s ease-in-out infinite;
}
@keyframes illust-floatUp {
  0%   { opacity: 0; transform: translateY(0); }
  30%  { opacity: 1; }
  100% { opacity: 0; transform: translateY(-40px); }
}

/* Notification bubble */
.illust-notify {
  position: absolute;
  top: 20px;
  right: 15px;
  background: #fff;
  color: #F97316;
  font-size: 10px;
  font-weight: 800;
  padding: 5px 10px;
  border-radius: 10px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.12);
  white-space: nowrap;
  animation: illust-notify 4s ease-in-out infinite;
  animation-delay: 1s;
  opacity: 0;
}
@keyframes illust-notify {
  0%   { opacity: 0; transform: translateY(10px); }
  20%  { opacity: 1; transform: translateY(0); }
  80%  { opacity: 1; transform: translateY(-10px); }
  100% { opacity: 0; transform: translateY(-20px); }
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
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
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
  font-size: clamp(22px, 5vw, 30px);
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
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
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
  margin: 0;
  padding: 0;
  width: 100%;
  max-width: 100%;
  overflow: hidden;
}

.systems-banner {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
  width: 100%;
  max-width: 100%;
  overflow: hidden;
  margin: 0;
  padding: 0;
}

.systems-banner img {
  width: 100%;
  height: 60px;
  object-fit: fill;
  display: block;
  margin: 0;
  padding: 0;
}

.seals-row {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
  width: 100%;
  background: #f0f0f0;
  padding: 30px 0;
  margin: 0;
}

.seals-row .seal-item {
  display: flex;
  justify-content: center;
  align-items: center;
  padding: 10px;
}

.seals-row img {
  width: 110px;
  height: 110px;
  object-fit: contain;
}

.footer-main {
  background: #e8e8e8;
  padding: clamp(20px, 3.5vw, 40px);
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
  gap: 40px;
}

.footer-title {
  margin: 0 0 14px;
  font-size: 14px;
  font-weight: 800;
  color: #222222;
  letter-spacing: 0.02em;
}

.footer-deped-logo {
  width: 80px;
  height: 80px;
  object-fit: contain;
  display: block;
  margin-bottom: 10px;
}

.footer-note,
.footer-about {
  margin: 0 0 12px;
  font-size: 13px;
  color: #555555;
  line-height: 1.5;
}

.footer-link {
  display: block;
  margin-bottom: 8px;
  color: #333333;
  text-decoration: none;
  font-size: 13px;
}

.footer-link:hover {
  text-decoration: underline;
}

.footer-bottom {
  background: #333333;
  color: #ffffff;
  text-align: center;
  padding: 15px;
  font-size: 14px;
}

/* Content Row: Carousel + Sidebar */
.content-row-wrapper {
  background: #ffffff;
}
.content-row {
  display: grid;
  grid-template-columns: minmax(0, 2fr) minmax(0, 1fr);
  gap: 24px;
  padding: clamp(12px, 2.5vw, 20px);
  max-width: 1200px;
  margin: 0 auto;
}
.content-left {
  min-width: 0;
}
.content-right {
  min-width: 0;
}

/* Carousel */
.carousel {
  position: relative;
  width: 100%;
  overflow: hidden;
  background: #000;
  border-radius: 8px;
}
.carousel-slides {
  position: relative;
  width: 100%;
  height: clamp(200px, 34vw, 300px);
}
.carousel-slide {
  position: absolute;
  top: 0; left: 0;
  width: 100%; height: 100%;
  opacity: 0;
  transition: opacity 0.6s ease;
}
.carousel-slide.active {
  opacity: 1;
}
.carousel-slide img {
  width: 100%;
  height: clamp(200px, 34vw, 300px);
  object-fit: cover;
  display: block;
}
.youtube-embed {
  display: block;
  margin-top: 20px;
  width: 100%;
  border: 0;
  border-radius: 8px;
  aspect-ratio: 16 / 9;
  height: auto;
  min-height: 220px;
}
.facebook-embed {
  border: none;
  overflow: hidden;
  margin-top: 20px;
  display: block;
  width: 100%;
  border-radius: 8px;
  min-height: 420px;
}
.carousel-btn {
  position: absolute;
  top: 50%;
  transform: translateY(-50%);
  background: rgba(0,0,0,0.4);
  color: white;
  border: none;
  padding: 12px 16px;
  cursor: pointer;
  font-size: 20px;
  z-index: 10;
  border-radius: 3px;
}
.carousel-btn:hover {
  background: rgba(0,0,0,0.7);
}
.carousel-btn.prev { left: 10px; }
.carousel-btn.next { right: 10px; }
.carousel-dots {
  position: absolute;
  bottom: 10px;
  left: 50%;
  transform: translateX(-50%);
  display: flex;
  gap: 8px;
  z-index: 10;
}
.carousel-dot {
  width: 10px;
  height: 10px;
  border-radius: 50%;
  background: rgba(255,255,255,0.5);
  cursor: pointer;
  border: none;
  padding: 0;
}
.carousel-dot.active {
  background: white;
}

/* Responsiveness */
@media (max-width: 980px) {
  .navbar {
    height: 56px;
    padding: 0 12px;
    justify-content: space-between;
  }
  .hamburger-btn {
    display: inline-flex;
  }
  .navbar-left {
    position: fixed;
    top: 56px;
    left: 0;
    right: 0;
    max-height: calc(100vh - 56px);
    overflow-y: auto;
    background: #fff;
    flex-direction: column;
    align-items: stretch;
    border-top: 1px solid #e5e7eb;
    transform: translateY(-120%);
    opacity: 0;
    pointer-events: none;
    transition: transform 0.2s ease, opacity 0.2s ease;
    z-index: 1001;
    height: auto;
    min-height: 0;
  }
  .landing .navbar-left,
  .landing .navbar-right {
    visibility: hidden;
  }
  .landing .navbar.nav-open .navbar-left,
  .landing .navbar.nav-open .navbar-right {
    visibility: visible;
  }
  .landing .navbar.nav-open .navbar-left {
    transform: translateY(0);
    opacity: 1;
    pointer-events: auto;
  }
  .nav-govph {
    width: 100%;
    border-right: none;
    border-bottom: 1px solid #e5e7eb;
    justify-content: flex-start;
    padding: 0 16px;
    height: 48px;
    min-height: 48px;
  }
  .nav-item {
    width: 100%;
    height: auto;
    min-height: 48px;
    border-bottom: 1px solid #f3f4f6;
  }
  .nav-trigger {
    width: 100%;
    justify-content: space-between;
    padding: 12px 16px;
    height: auto;
    min-height: 48px;
  }
  .navbar-right {
    position: static;
    width: auto;
    margin-left: auto;
    border: 0;
    padding: 0;
    background: transparent;
    transform: none;
    opacity: 1;
    pointer-events: auto;
    visibility: visible;
    flex-direction: row;
    align-items: center;
    gap: 8px;
  }
  .navbar-right .search-bar {
    display: none;
  }
  .navbar-right :deep(.a11y-toolbar),
  .navbar-right :deep(.accessibility-toolbar) {
    display: none;
  }
  .login-btn {
    padding: 6px 10px;
    font-size: 13px;
  }
  .banner-header-img {
    height: 120px;
  }
  .banner-overlay {
    position: static;
    padding: 10px 12px;
    background: #ea580c;
  }
  .banner-time {
    display: none;
  }
  .rp {
    font-size: 14px;
  }
  .dept {
    font-size: 17px;
  }
  .motto {
    font-size: 13px;
  }
  .systems-grid {
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
  }
  .hero-inner {
    grid-template-columns: 1fr;
  }
  .seals-row {
    gap: 20px;
  }
  .footer-main {
    grid-template-columns: 1fr;
  }
  .dropdown-menu {
    position: static;
    width: 100%;
    min-width: 0;
    border-top: none;
    box-shadow: none;
    border-left: 3px solid #f97316;
  }
}
@media (max-width: 768px) {
  .content-row {
    grid-template-columns: 1fr;
    gap: 16px;
    padding: 14px;
  }
  .content-right {
    display: block;
  }
  .seals-row {
    padding: 20px 0;
  }
  .seals-row img {
    width: 88px;
    height: 88px;
  }
  .footer-main {
    padding: 24px 16px;
    gap: 20px;
  }
}
@media (max-width: 520px) {
  .contact-inner {
    gap: 10px;
  }
  .contact-item {
    width: 100%;
    font-size: 13px;
  }
  .banner-overlay {
    flex-direction: column;
    align-items: flex-start;
  }
  .banner-time {
    display: block;
    text-align: left;
  }
  .step,
  .stat-card,
  .sys-card {
    padding: 14px;
  }
  .youtube-embed {
    min-height: 180px;
  }
  .facebook-embed {
    min-height: 360px;
  }
}
</style>

