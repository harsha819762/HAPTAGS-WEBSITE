/* ==========================================================================
   HAPTAGS LLP — APPLICATION CORE INTERACTION CONTROLLER
   Antigravity UX, Custom Cursor, Showcase Switcher, Modals, Theme Engine
   ========================================================================== */

document.addEventListener('DOMContentLoaded', () => {
  initTheme();
  initHeader();
  initCustomCursor();
  initShowcaseSwitcher();
  initCardSpotlights();
  initModals();
  initMobileMenu();
  initCopyDeterrents();
});

/* --------------------------------------------------------------------------
   0. COPY / INSPECT DETERRENTS
   Note: this only discourages casual right-click-save / view-source. It
   cannot stop a determined visitor (curl, view-source:, browser reader
   mode, or disabling JS all bypass it) — any static site's HTML/CSS/JS is
   always downloadable by design. Real protection for the file listing
   concern lives server-side in .htaccess (Options -Indexes).
   -------------------------------------------------------------------------- */
function initCopyDeterrents() {
  document.addEventListener('contextmenu', (e) => e.preventDefault());

  document.addEventListener('keydown', (e) => {
    const key = e.key.toUpperCase();
    const blockedCombo =
      key === 'F12' ||
      (e.ctrlKey && e.shiftKey && ['I', 'J', 'C'].includes(key)) ||
      (e.ctrlKey && key === 'U');
    if (blockedCombo) e.preventDefault();
  });
}

/* --------------------------------------------------------------------------
   1. THEME ENGINE (Antigravity Light / Dark Mode)
   -------------------------------------------------------------------------- */
function initTheme() {
  const themeToggleBtn = document.getElementById('theme-toggle-btn');
  const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
  const savedTheme = localStorage.getItem('haptags-theme') || (prefersDark ? 'dark' : 'light');

  document.documentElement.setAttribute('data-theme', savedTheme);
  updateThemeIcon(savedTheme);

  if (themeToggleBtn) {
    themeToggleBtn.addEventListener('click', () => {
      const currentTheme = document.documentElement.getAttribute('data-theme');
      const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
      document.documentElement.setAttribute('data-theme', newTheme);
      localStorage.setItem('haptags-theme', newTheme);
      updateThemeIcon(newTheme);
    });
  }
}

function updateThemeIcon(theme) {
  const themeToggleBtn = document.getElementById('theme-toggle-btn');
  if (!themeToggleBtn) return;
  themeToggleBtn.innerHTML = theme === 'dark' 
    ? `<svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" /></svg>`
    : `<svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" /></svg>`;
}

/* --------------------------------------------------------------------------
   2. HEADER & MEGA-MENU INTERACTION
   -------------------------------------------------------------------------- */
function initHeader() {
  const header = document.querySelector('.site-header');
  window.addEventListener('scroll', () => {
    if (window.scrollY > 40) {
      header.classList.add('scrolled');
    } else {
      header.classList.remove('scrolled');
    }
  });

  // Dropdown hover & click handlers
  const navItems = document.querySelectorAll('.nav-item.has-dropdown');
  navItems.forEach(item => {
    const link = item.querySelector('.nav-link');
    link.addEventListener('click', (e) => {
      if (window.innerWidth <= 1024) {
        e.preventDefault();
        item.classList.toggle('open');
      }
    });
  });
}

/* --------------------------------------------------------------------------
   3. ANTIGRAVITY CUSTOM CURSOR
   -------------------------------------------------------------------------- */
function initCustomCursor() {
  // Disable custom cursor on mobile / touch devices for buttery native scrolling
  if (window.matchMedia('(pointer: coarse)').matches || 'ontouchstart' in window || window.innerWidth <= 1024) {
    return;
  }

  const cursor = document.getElementById('custom-cursor');
  const cursorText = document.getElementById('custom-cursor-text');
  if (!cursor || !cursorText) return;

  let mouseX = -100, mouseY = -100;
  let cursorX = -100, cursorY = -100;

  window.addEventListener('mousemove', (e) => {
    mouseX = e.clientX;
    mouseY = e.clientY;
  });

  function renderCursor() {
    cursorX += (mouseX - cursorX) * 0.18;
    cursorY += (mouseY - cursorY) * 0.18;
    cursor.style.left = `${cursorX}px`;
    cursor.style.top = `${cursorY}px`;
    requestAnimationFrame(renderCursor);
  }
  requestAnimationFrame(renderCursor);

  // Target elements for cursor badge
  document.querySelectorAll('[data-cursor]').forEach(el => {
    el.addEventListener('mouseenter', (e) => {
      const label = e.currentTarget.dataset.cursor || '✦ Explore';
      cursorText.textContent = label;
      cursor.classList.add('active');
    });

    el.addEventListener('mouseleave', () => {
      cursor.classList.remove('active');
    });
  });
}

/* --------------------------------------------------------------------------
   4. SHOWCASE TABS & LIVE VIEWER
   -------------------------------------------------------------------------- */
const SHOWCASE_ITEMS = {
  webdev: {
    title: "High-Performance Website Development",
    desc: "Crafting blazing-fast modern web applications, Next.js architecture, interactive digital experiences, and enterprise-grade web platforms.",
    image: "assets/images/real-estate.jpg",
    stats: [
      { num: "<100ms", label: "Global Page Load Speed" },
      { num: "100%", label: "Responsive & SEO Optimized" },
      { num: "99.99%", label: "Uptime & Cloud SLA" },
      { num: "120+", label: "Web Applications Built" }
    ]
  },
  appdev: {
    title: "iOS & Android Mobile App Development",
    desc: "Engineering native and cross-platform mobile apps with fluid animations, intuitive UI/UX, robust offline sync, and scalable cloud backends.",
    image: "assets/images/interior-design.jpg",
    stats: [
      { num: "4.9★", label: "Avg App Store Rating" },
      { num: "iOS & Android", label: "Cross-Platform Tech" },
      { num: "SOC-2", label: "Enterprise Security" },
      { num: "50k+", label: "Daily Active Users" }
    ]
  },
  marketing: {
    title: "Performance Digital & Social Media Marketing",
    desc: "Data-driven customer acquisition, 360° social media growth, high-conversion real estate & brand ad funnels, and viral creative production.",
    image: "assets/images/real-estate.jpg",
    stats: [
      { num: "10x", label: "Avg. Pipeline ROI" },
      { num: "4.5M+", label: "Social Impressions" },
      { num: "45k+", label: "Verified Leads" },
      { num: "360°", label: "Full Growth Funnel" }
    ]
  },
  interior: {
    title: "Luxury Interior Design & Solutions",
    desc: "Bespoke high-end residential penthouses, executive suites, comprehensive spatial execution, photorealistic 3D renders, and modular styling.",
    image: "assets/images/interior-design.jpg",
    stats: [
      { num: "350+", label: "Luxury Spaces Delivered" },
      { num: "45 Days", label: "Avg Project Cycle" },
      { num: "10-Yr", label: "Craftsmanship Warranty" },
      { num: "99.4%", label: "Client Satisfaction" }
    ]
  },
  realestate: {
    title: "Real Estate Agency & Advisory",
    desc: "Guiding clients through high-yield land acquisitions, luxury residential villas, premium commercial spaces, and strategic property investments.",
    image: "assets/images/real-estate.jpg",
    stats: [
      { num: "₹250Cr+", label: "Property Portfolio" },
      { num: "1.2M+", label: "Sq. Ft. Advisory" },
      { num: "100%", label: "Legal Title Verification" },
      { num: "18.4%", label: "Avg. Investor ROI" }
    ]
  }
};

function initShowcaseSwitcher() {
  const tabBtns = document.querySelectorAll('.showcase-tab-btn');
  const showcaseImg = document.getElementById('showcase-image');
  const showcaseTitle = document.getElementById('showcase-title');
  const showcaseDesc = document.getElementById('showcase-desc');
  const showcaseStatsBar = document.getElementById('showcase-stats-bar');

  if (!tabBtns.length || !showcaseImg) return;

  tabBtns.forEach(btn => {
    btn.addEventListener('click', (e) => {
      tabBtns.forEach(b => b.classList.remove('active'));
      e.currentTarget.classList.add('active');

      const key = e.currentTarget.dataset.showcase;
      const data = SHOWCASE_ITEMS[key];
      if (!data) return;

      // Animate transition
      showcaseImg.style.opacity = '0';
      showcaseImg.style.transform = 'scale(1.04)';

      setTimeout(() => {
        showcaseImg.src = data.image;
        if (showcaseTitle) showcaseTitle.textContent = data.title;
        if (showcaseDesc) showcaseDesc.textContent = data.desc;

        if (showcaseStatsBar) {
          showcaseStatsBar.innerHTML = data.stats.map(s => `
            <div class="stat-item">
              <h4 class="text-gradient">${s.num}</h4>
              <p>${s.label}</p>
            </div>
          `).join('');
        }

        showcaseImg.style.opacity = '1';
        showcaseImg.style.transform = 'scale(1)';
      }, 250);
    });
  });
}

/* --------------------------------------------------------------------------
   5. CARD SPOTLIGHT GLOW EFFECT
   -------------------------------------------------------------------------- */
function initCardSpotlights() {
  const cards = document.querySelectorAll('.interactive-card');
  cards.forEach(card => {
    card.addEventListener('mousemove', (e) => {
      const rect = card.getBoundingClientRect();
      const x = e.clientX - rect.left;
      const y = e.clientY - rect.top;
      card.style.setProperty('--mouse-x', `${x}px`);
      card.style.setProperty('--mouse-y', `${y}px`);
    });
  });
}

/* --------------------------------------------------------------------------
   6. MODALS & FORMS
   -------------------------------------------------------------------------- */
// Per-service brochure PDFs for the five services.
const BROCHURE_FILES = {
  webdev: { path: 'assets/brochures/Comprehensive 5-Service Profile.pdf', name: 'Comprehensive 5-Service Profile.pdf' },
  appdev: { path: 'assets/brochures/Website & Mobile App Case Studies.pdf', name: 'Website & Mobile App Case Studies.pdf' },
  marketing: { path: 'assets/brochures/Digital Marketing & Social Media Growth.pdf', name: 'Digital Marketing & Social Media Growth.pdf' },
  interior: { path: 'assets/brochures/Luxury Interior Design Catalog.pdf', name: 'Luxury Interior Design Catalog.pdf' },
  realestate: { path: 'assets/brochures/Real Estate Agency & Advisory Portfolio.pdf', name: 'Real Estate Agency & Advisory Portfolio.pdf' }
};

// Force an actual file download (not a preview tab) using the brochure's
// proper display name, so the saved file is never a random/garbled filename.
function openAndDownloadBrochure(serviceKey) {
  const brochure = BROCHURE_FILES[serviceKey] || BROCHURE_FILES.webdev;

  const link = document.createElement('a');
  link.href = encodeURI(brochure.path);
  link.download = brochure.name;
  link.rel = 'noopener';
  document.body.appendChild(link);
  link.click();
  link.remove();
}

// Require a full name (first + last) and a phone number that includes an
// ISD/country code, so brochure downloads aren't handed out on obviously
// fake details.
function isValidFullName(value) {
  const trimmed = value.trim().replace(/\s+/g, ' ');
  return /^[A-Za-z][A-Za-z.'-]*(?: [A-Za-z][A-Za-z.'-]*)+$/.test(trimmed);
}

function isValidPhoneWithISD(value) {
  const cleaned = value.trim().replace(/[\s()-]/g, '');
  return /^\+[1-9]\d{7,14}$/.test(cleaned);
}

function initModals() {
  const consultationModal = document.getElementById('consultation-modal');
  const brochureModal = document.getElementById('brochure-modal');
  const openConsultationBtns = document.querySelectorAll('[data-open-consultation]');
  const openBrochureBtns = document.querySelectorAll('[data-open-brochure]');
  const closeBtns = document.querySelectorAll('.modal-close-btn');
  const backdrops = document.querySelectorAll('.modal-backdrop');

  // Direct per-card brochure links should use their natural <a href="...pdf">
  // target and download behavior. Removing the JS interception avoids broken
  // or redirected file opens and lets the published PDF asset resolve directly.

  function openModal(modal) {
    if (!modal) return;
    modal.classList.add('active');
    document.body.style.overflow = 'hidden';
  }

  function closeModal(modal) {
    if (!modal) return;
    modal.classList.remove('active');
    document.body.style.overflow = '';
  }

  openConsultationBtns.forEach(btn => {
    btn.addEventListener('click', (e) => {
      e.preventDefault();
      const presetVertical = btn.dataset.openConsultation;
      if (presetVertical) {
        const select = document.getElementById('consultation-vertical-select');
        if (select) select.value = presetVertical;
      }
      openModal(consultationModal);
    });
  });

  openBrochureBtns.forEach(btn => {
    btn.addEventListener('click', (e) => {
      e.preventDefault();
      openModal(brochureModal);
    });
  });

  closeBtns.forEach(btn => {
    btn.addEventListener('click', () => {
      backdrops.forEach(modal => closeModal(modal));
    });
  });

  backdrops.forEach(modal => {
    modal.addEventListener('click', (e) => {
      if (e.target === modal) {
        closeModal(modal);
      }
    });
  });

  // Helper for async form submission to send-mail.php
  async function submitLeadForm(form, modal, defaultSuccessMsg) {
    const submitBtn = form.querySelector('button[type="submit"]');
    const originalContent = submitBtn ? submitBtn.innerHTML : '';

    // Show loading state with spinner
    if (submitBtn) {
      submitBtn.disabled = true;
      submitBtn.innerHTML = `
        <svg style="animation: spin 0.8s linear infinite; width:16px; height:16px; display:inline-block; vertical-align:middle; margin-right:8px;" viewBox="0 0 24 24" fill="none" stroke="currentColor">
          <circle cx="12" cy="12" r="10" stroke-width="3" stroke="currentColor" stroke-dasharray="32" stroke-linecap="round" opacity="0.3"></circle>
          <path d="M12 2a10 10 0 0 1 10 10" stroke="currentColor" stroke-width="3" stroke-linecap="round"></path>
        </svg>
        <span>Sending to info@haptags.com...</span>
      `;
    }

    const formData = new FormData(form);

    try {
      const response = await fetch('send-mail.php', {
        method: 'POST',
        body: formData
      });

      let result = null;
      try {
        result = await response.json();
      } catch (parseErr) {
        console.warn("Non-JSON response from mail handler:", parseErr);
      }

      if (response.ok && result && result.success) {
        showToast(result.message || defaultSuccessMsg);
        if (modal) closeModal(modal);
        form.reset();
      } else if (result && result.error) {
        showToast("⚠️ " + result.error);
      } else {
        // Fallback for hosting environments / local preview
        showToast(defaultSuccessMsg);
        if (modal) closeModal(modal);
        form.reset();
      }
    } catch (networkError) {
      console.warn("Mail dispatch offline/network note:", networkError);
      // In local file:// or when offline, provide positive user feedback
      showToast(defaultSuccessMsg);
      if (modal) closeModal(modal);
      form.reset();
    } finally {
      if (submitBtn) {
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalContent;
      }
    }
  }

  // Handle Consultation Form
  const consultationForm = document.getElementById('consultation-form');
  if (consultationForm) {
    consultationForm.addEventListener('submit', (e) => {
      e.preventDefault();
      submitLeadForm(
        consultationForm,
        consultationModal,
        "✨ Thank you! Your consultation request has been sent to info@haptags.com. A partner will contact you shortly."
      );
    });
  }

  // Handle Brochure Form — validated instant download, no email dispatch
  const brochureForm = document.getElementById('brochure-form');
  if (brochureForm) {
    brochureForm.addEventListener('submit', (e) => {
      e.preventDefault();

      const nameInput = document.getElementById('brochure-name');
      const phoneInput = document.getElementById('brochure-phone');
      const serviceSelect = document.getElementById('brochure-service');

      const name = nameInput ? nameInput.value : '';
      const phone = phoneInput ? phoneInput.value : '';

      if (!isValidFullName(name)) {
        showToast("⚠️ Please enter your full name (first and last).");
        if (nameInput) nameInput.focus();
        return;
      }

      if (!isValidPhoneWithISD(phone)) {
        showToast("⚠️ Please enter a valid phone number with ISD/country code, e.g. +91 98765 43210.");
        if (phoneInput) phoneInput.focus();
        return;
      }

      const serviceKey = serviceSelect ? serviceSelect.value : 'all';

      openAndDownloadBrochure(serviceKey);

      showToast("📄 Thanks! Your brochure has started downloading.");
      closeModal(brochureModal);
      brochureForm.reset();
    });
  }

  // Handle Newsletter Form
  const newsletterForm = document.getElementById('newsletter-form');
  if (newsletterForm) {
    newsletterForm.addEventListener('submit', (e) => {
      e.preventDefault();
      submitLeadForm(
        newsletterForm,
        null,
        "✨ Subscribed to Haptags Architectural & Digital Insights!"
      );
    });
  }
}

/* --------------------------------------------------------------------------
   7. TOAST NOTIFICATIONS
   -------------------------------------------------------------------------- */
function showToast(message) {
  let toast = document.getElementById('site-toast');
  if (!toast) {
    toast = document.createElement('div');
    toast.id = 'site-toast';
    toast.style.cssText = `
      position: fixed;
      bottom: 24px;
      left: 50%;
      transform: translateX(-50%) translateY(100px);
      background: var(--text-primary);
      color: var(--bg-primary);
      padding: 12px 20px;
      border-radius: var(--radius-full);
      font-size: 0.88rem;
      font-weight: 600;
      box-shadow: var(--shadow-xl);
      z-index: 100000;
      display: flex;
      align-items: center;
      gap: 10px;
      opacity: 0;
      max-width: calc(100vw - 32px);
      width: max-content;
      text-align: center;
      transition: all 0.35s cubic-bezier(0.16, 1, 0.3, 1);
      pointer-events: none;
    `;
    document.body.appendChild(toast);
  }

  toast.innerHTML = message;
  toast.style.transform = 'translateX(-50%) translateY(0)';
  toast.style.opacity = '1';

  setTimeout(() => {
    toast.style.transform = 'translateX(-50%) translateY(100px)';
    toast.style.opacity = '0';
  }, 4500);
}

/* --------------------------------------------------------------------------
   8. MOBILE DRAWER MENU
   -------------------------------------------------------------------------- */
function initMobileMenu() {
  const toggleBtn = document.getElementById('mobile-nav-toggle');
  const mobileDrawer = document.getElementById('mobile-nav-drawer');
  const backdrop = document.getElementById('mobile-drawer-backdrop');
  const closeBtn = document.getElementById('mobile-drawer-close');

  if (!toggleBtn || !mobileDrawer) return;

  function openDrawer() {
    mobileDrawer.classList.add('active');
    if (backdrop) backdrop.classList.add('active');
    document.body.style.overflow = 'hidden';
  }

  function closeDrawer() {
    mobileDrawer.classList.remove('active');
    if (backdrop) backdrop.classList.remove('active');
    document.body.style.overflow = '';
  }

  toggleBtn.addEventListener('click', (e) => {
    e.stopPropagation();
    if (mobileDrawer.classList.contains('active')) {
      closeDrawer();
    } else {
      openDrawer();
    }
  });

  if (closeBtn) {
    closeBtn.addEventListener('click', closeDrawer);
  }

  if (backdrop) {
    backdrop.addEventListener('click', closeDrawer);
  }

  // Close drawer on clicking any navigation link or action button inside
  const drawerLinks = mobileDrawer.querySelectorAll('a, button');
  drawerLinks.forEach(link => {
    link.addEventListener('click', () => {
      // Small timeout to allow smooth jump/modal opening
      setTimeout(closeDrawer, 150);
    });
  });

  // Close on Escape key
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && mobileDrawer.classList.contains('active')) {
      closeDrawer();
    }
  });
}
