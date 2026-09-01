/* ==========================================================================
   HAPTAGS LLP — INTERACTIVE ESTIMATOR & CONFIGURATOR
   ========================================================================== */

const VERTICAL_DATA = {
  interior: {
    name: "Luxury Interior Solutions",
    sliderLabel: "Area / Space Size",
    unit: "Sq. Ft.",
    minVal: 500,
    maxVal: 10000,
    step: 250,
    defaultVal: 2400,
    rates: {
      standard: 1850,
      premium: 2950,
      bespoke: 4500
    },
    baseTimelineWeeks: 6,
    timelinePer1000: 2,
    deliverables: [
      "Custom 3D Photorealistic Architectural Renders",
      "Bespoke Italian Modular Kitchen & Wardrobes",
      "Full False Ceiling & Recessed Lighting Automation",
      "Curated Calacatta Marble & Wooden Paneling",
      "Complete 5-Year Turnkey Craftsmanship Warranty"
    ]
  },
  construction: {
    name: "Turnkey Construction & Civil Engineering",
    sliderLabel: "Built-up Construction Area",
    unit: "Sq. Ft.",
    minVal: 1000,
    maxVal: 35000,
    step: 500,
    defaultVal: 5500,
    rates: {
      standard: 2200,
      premium: 3400,
      bespoke: 5200
    },
    baseTimelineWeeks: 16,
    timelinePer1000: 3,
    deliverables: [
      "Full Structural & Foundation Engineering",
      "Grade-53 TMT & RMC Certified Concrete Execution",
      "Smart Building HVAC & High-Efficiency MEP",
      "Thermal Insulation & Acoustic Glass Facade",
      "Green Building Certification & Compliance BOQ"
    ]
  },
  realestate: {
    name: "Real Estate Development & Acquisition",
    sliderLabel: "Target Property Value",
    unit: "Lakhs",
    minVal: 50,
    maxVal: 2500,
    step: 25,
    defaultVal: 350,
    rates: {
      standard: 0.02,
      premium: 0.035,
      bespoke: 0.05
    },
    isPercentage: true,
    baseTimelineWeeks: 4,
    timelinePer1000: 1,
    deliverables: [
      "Exclusive High-Yield Land & Asset Sourcing",
      "Comprehensive Title Due-Diligence & Legal Clearances",
      "Architectural Feasibility & Max-FSI Planning",
      "Institutional Investor & NRI Syndication Deck",
      "End-to-End Escrow & Registry Facilitation"
    ]
  },
  materials: {
    name: "Building & Interior Materials Trading",
    sliderLabel: "Procurement Volume",
    unit: "Sq. Ft. Coverage",
    minVal: 1000,
    maxVal: 20000,
    step: 500,
    defaultVal: 4000,
    rates: {
      standard: 450,
      premium: 950,
      bespoke: 1800
    },
    baseTimelineWeeks: 2,
    timelinePer1000: 0.5,
    deliverables: [
      "Direct Quarry-Sourced Italian & Indian Marble",
      "Large-Format GVT & Porcelain Engineered Tiles",
      "Acoustic Wooden Slats & Fire-Rated Wall Panels",
      "Architectural Grade Brass & Concealed Hardware",
      "Direct Site Logistics with Breakage-Proof Crating"
    ]
  },
  itdev: {
    name: "Web & Mobile App Development",
    sliderLabel: "Engineering Scope & Modules",
    unit: "Key Feature Screens",
    minVal: 5,
    maxVal: 60,
    step: 1,
    defaultVal: 18,
    rates: {
      standard: 12000,
      premium: 22000,
      bespoke: 38000
    },
    baseTimelineWeeks: 4,
    timelinePer1000: 0.5,
    deliverables: [
      "Custom Next.js / React Modern Frontend Architecture",
      "High-Performance iOS & Android Mobile Apps",
      "Scalable Cloud Backend, PostgreSQL & REST/GraphQL API",
      "Figma UX/UI Design System with Interactive Prototype",
      "Automated CI/CD, Enterprise Security & 1-Year SLA Support"
    ]
  },
  marketing: {
    name: "Social Media & Performance Marketing",
    sliderLabel: "Monthly Growth Target & Spend",
    unit: "Campaign Scale Index",
    minVal: 1,
    maxVal: 20,
    step: 1,
    defaultVal: 6,
    rates: {
      standard: 45000,
      premium: 90000,
      bespoke: 175000
    },
    baseTimelineWeeks: 4,
    timelinePer1000: 0,
    deliverables: [
      "High-Conversion Real Estate & Brand Lead Funnels",
      "Cinematic Video Production, 3D Reels & Motion Graphics",
      "Meta Ads, Google Search & Performance Remarketing",
      "Influencer Outreach & High-Net-Worth Targeting",
      "Live Real-Time ROI & Conversion Analytics Dashboard"
    ]
  }
};

class HaptagsEstimator {
  constructor() {
    this.currentVertical = 'interior';
    this.currentTier = 'premium';
    this.currentScope = 2400;

    this.cacheElements();
    this.bindEvents();
    this.updateUI();
  }

  cacheElements() {
    this.verticalTabs = document.querySelectorAll('.estimator-vertical-tab');
    this.tierBtns = document.querySelectorAll('.tier-btn');
    this.scopeSlider = document.getElementById('scope-slider');
    this.scopeValueDisplay = document.getElementById('scope-value-display');
    this.scopeLabel = document.getElementById('scope-slider-label');
    
    this.totalCostEl = document.getElementById('estimated-total-cost');
    this.estimatedTimeEl = document.getElementById('estimated-timeline');
    this.rateUnitEl = document.getElementById('rate-unit-display');
    this.deliverablesList = document.getElementById('estimator-deliverables-list');
  }

  bindEvents() {
    if (this.verticalTabs) {
      this.verticalTabs.forEach(tab => {
        tab.addEventListener('click', (e) => {
          this.verticalTabs.forEach(t => t.classList.remove('active'));
          e.currentTarget.classList.add('active');
          this.currentVertical = e.currentTarget.dataset.vertical;
          
          const vData = VERTICAL_DATA[this.currentVertical];
          this.scopeSlider.min = vData.minVal;
          this.scopeSlider.max = vData.maxVal;
          this.scopeSlider.step = vData.step;
          this.scopeSlider.value = vData.defaultVal;
          this.currentScope = vData.defaultVal;
          
          this.updateUI();
        });
      });
    }

    if (this.tierBtns) {
      this.tierBtns.forEach(btn => {
        btn.addEventListener('click', (e) => {
          this.tierBtns.forEach(b => b.classList.remove('active'));
          e.currentTarget.classList.add('active');
          this.currentTier = e.currentTarget.dataset.tier;
          this.updateUI();
        });
      });
    }

    if (this.scopeSlider) {
      this.scopeSlider.addEventListener('input', (e) => {
        this.currentScope = parseFloat(e.target.value);
        this.updateUI();
      });
    }
  }

  formatCurrency(num) {
    if (num >= 10000000) {
      return '₹' + (num / 10000000).toFixed(2) + ' Cr';
    } else if (num >= 100000) {
      return '₹' + (num / 100000).toFixed(2) + ' Lakhs';
    } else {
      return '₹' + num.toLocaleString('en-IN');
    }
  }

  updateUI() {
    const data = VERTICAL_DATA[this.currentVertical];
    if (!data) return;

    // Update Slider Labels
    if (this.scopeLabel) {
      this.scopeLabel.textContent = data.sliderLabel;
    }
    if (this.scopeValueDisplay) {
      if (this.currentVertical === 'realestate') {
        this.scopeValueDisplay.textContent = '₹' + this.currentScope + ' Lakhs';
      } else if (this.currentVertical === 'marketing') {
        this.scopeValueDisplay.textContent = 'Tier Scale ' + this.currentScope + 'x';
      } else {
        this.scopeValueDisplay.textContent = this.currentScope.toLocaleString() + ' ' + data.unit;
      }
    }

    // Compute Cost
    const rate = data.rates[this.currentTier];
    let totalCost = 0;

    if (data.isPercentage) {
      totalCost = (this.currentScope * 100000) * rate;
    } else if (this.currentVertical === 'marketing') {
      totalCost = rate * (1 + (this.currentScope - 1) * 0.35);
    } else {
      totalCost = this.currentScope * rate;
    }

    // Compute Timeline
    const weeks = Math.round(data.baseTimelineWeeks + (this.currentScope / 1000) * data.timelinePer1000);

    // Update Output Elements
    if (this.totalCostEl) {
      this.totalCostEl.textContent = this.formatCurrency(Math.round(totalCost));
    }
    if (this.estimatedTimeEl) {
      this.estimatedTimeEl.textContent = `${weeks} - ${weeks + 3} Weeks`;
    }
    if (this.rateUnitEl) {
      if (data.isPercentage) {
        this.rateUnitEl.textContent = `${(rate * 100).toFixed(1)}% Advisory & Mgmt`;
      } else if (this.currentVertical === 'marketing') {
        this.rateUnitEl.textContent = `₹${rate.toLocaleString()}/mo Base Campaign`;
      } else if (this.currentVertical === 'itdev') {
        this.rateUnitEl.textContent = `₹${rate.toLocaleString()}/Module`;
      } else {
        this.rateUnitEl.textContent = `₹${rate.toLocaleString()} / ${data.unit}`;
      }
    }

    // Update Deliverables
    if (this.deliverablesList) {
      this.deliverablesList.innerHTML = data.deliverables.map(item => `
        <li style="display:flex; align-items:center; gap:10px; font-size:0.88rem; color:var(--text-secondary); margin-bottom:8px;">
          <svg style="width:16px; height:16px; color:var(--brand-emerald); flex-shrink:0;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
          </svg>
          ${item}
        </li>
      `).join('');
    }
  }
}

document.addEventListener('DOMContentLoaded', () => {
  window.haptagsEstimator = new HaptagsEstimator();
});
