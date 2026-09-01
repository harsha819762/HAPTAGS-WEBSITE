/* ==========================================================================
   HAPTAGS LLP — ANTIGRAVITY PARTICLE PHYSICS ENGINE
   Inspired by antigravity.google dynamic particle vortex & constellation
   ========================================================================== */

(function () {
  const canvas = document.getElementById('antigravity-canvas');
  if (!canvas) return;

  const ctx = canvas.getContext('2d');
  let width, height, dpr;
  let particles = [];
  const particleCount = 175;

  // Antigravity vibrant palette (Google & Haptags Spectrum)
  const colors = [
    '#4285F4', // Blue
    '#EA4335', // Red
    '#FBBC05', // Amber / Gold
    '#34A853', // Emerald
    '#8E24AA', // Purple
    '#00ACC1', // Cyan
    '#FF6D00'  // Vibrant Orange
  ];

  // Mouse / Interaction State
  const mouse = {
    x: null,
    y: null,
    targetX: null,
    targetY: null,
    radius: 200,
    isHovering: false
  };

  // Resize Handler
  function resize() {
    dpr = Math.min(window.devicePixelRatio || 1, 2);
    width = canvas.parentElement.offsetWidth;
    height = canvas.parentElement.offsetHeight;

    canvas.width = width * dpr;
    canvas.height = height * dpr;
    canvas.style.width = width + 'px';
    canvas.style.height = height + 'px';
    ctx.scale(dpr, dpr);

    if (!mouse.x) {
      if (width < 768) {
        // On mobile, position focus towards lower-right to keep reading area quiet
        mouse.x = width * 0.82;
        mouse.y = height * 0.70;
      } else {
        mouse.x = width * 0.75;
        mouse.y = height * 0.45;
      }
      mouse.targetX = mouse.x;
      mouse.targetY = mouse.y;
    }
  }

  // Particle Class
  class Particle {
    constructor() {
      this.reset(true);
    }

    reset(initial = false) {
      this.x = initial ? Math.random() * width : width / 2 + (Math.random() - 0.5) * 100;
      this.y = initial ? Math.random() * height : height / 2 + (Math.random() - 0.5) * 100;
      
      this.originX = this.x;
      this.originY = this.y;

      // Antigravity orbital physics parameters
      this.angle = Math.random() * Math.PI * 2;
      this.distanceFromCenter = 40 + Math.random() * (Math.min(width, height) * 0.55);
      this.orbitalSpeed = (0.003 + Math.random() * 0.008) * (Math.random() > 0.5 ? 1 : -1);
      this.radialNoise = Math.random() * 20;

      // Particle style
      this.size = 2.0 + Math.random() * 2.8;
      this.color = colors[Math.floor(Math.random() * colors.length)];
      this.opacity = 0.35 + Math.random() * 0.55;
      this.isDash = Math.random() > 0.45;
      this.dashLength = 4 + Math.random() * 7;
      
      this.vx = (Math.random() - 0.5) * 0.5;
      this.vy = (Math.random() - 0.5) * 0.5;
    }

    update(time) {
      // Orbit around interaction focus
      this.angle += this.orbitalSpeed;
      
      // Calculate ideal orbital position around focus
      const focusX = mouse.x || (width < 768 ? width * 0.82 : width * 0.7);
      const focusY = mouse.y || (width < 768 ? height * 0.70 : height * 0.45);

      const targetX = focusX + Math.cos(this.angle) * (this.distanceFromCenter + Math.sin(time * 0.002 + this.radialNoise) * 15);
      const targetY = focusY + Math.sin(this.angle) * (this.distanceFromCenter * 0.65 + Math.cos(time * 0.002 + this.radialNoise) * 15);

      // Smooth lerp towards orbital trajectory
      this.x += (targetX - this.x) * 0.035 + this.vx;
      this.y += (targetY - this.y) * 0.035 + this.vy;

      // Mouse proximity repulsion / swirl
      if (mouse.isHovering && mouse.x !== null) {
        const dx = this.x - mouse.x;
        const dy = this.y - mouse.y;
        const dist = Math.sqrt(dx * dx + dy * dy);

        if (dist < mouse.radius) {
          const force = (1 - dist / mouse.radius) * 3;
          this.x += (dx / dist) * force;
          this.y += (dy / dist) * force;
        }
      }
    }

    draw() {
      ctx.save();
      // On mobile, keep opacity to ~30-40% to maintain clean reading contrast
      const alphaScale = width < 768 ? 0.55 : 1.0;
      ctx.globalAlpha = this.opacity * alphaScale;
      ctx.fillStyle = this.color;
      ctx.strokeStyle = this.color;
      ctx.lineWidth = 1.6;

      if (this.isDash) {
        // Draw directional dash aligned with orbital velocity
        const angle = this.angle + Math.PI / 2;
        const x2 = this.x + Math.cos(angle) * this.dashLength;
        const y2 = this.y + Math.sin(angle) * this.dashLength;

        ctx.beginPath();
        ctx.moveTo(this.x, this.y);
        ctx.lineTo(x2, y2);
        ctx.stroke();
      } else {
        // Draw crisp glowing particle dot
        ctx.beginPath();
        ctx.arc(this.x, this.y, this.size, 0, Math.PI * 2);
        ctx.fill();
      }
      ctx.restore();
    }
  }

  // Initialize Particles with mobile performance optimization
  function init() {
    resize();
    particles = [];
    const dynamicCount = width < 768 ? 48 : 175;
    for (let i = 0; i < dynamicCount; i++) {
      particles.push(new Particle());
    }
  }

  // Animation Loop
  let lastTime = 0;
  function animate(time) {
    ctx.clearRect(0, 0, width, height);

    // Smooth mouse position lerping
    if (mouse.targetX !== null && mouse.isHovering) {
      mouse.x += (mouse.targetX - mouse.x) * 0.05;
      mouse.y += (mouse.targetY - mouse.y) * 0.05;
    } else {
      if (width < 768) {
        // On mobile, subtle drift in lower-right quadrant
        mouse.x = width * 0.80 + Math.sin(time * 0.0008) * (width * 0.10);
        mouse.y = height * 0.70 + Math.cos(time * 0.001) * (height * 0.08);
      } else {
        // Desktop gentle autonomous floating focus
        mouse.x = width * 0.65 + Math.sin(time * 0.001) * (width * 0.15);
        mouse.y = height * 0.45 + Math.cos(time * 0.0012) * (height * 0.1);
      }
    }

    // Connect close particles with subtle constellation filaments
    const maxDist = width < 768 ? 65 : 75;
    for (let i = 0; i < particles.length; i++) {
      for (let j = i + 1; j < particles.length; j++) {
        const dx = particles[i].x - particles[j].x;
        const dy = particles[i].y - particles[j].y;
        const dist = Math.sqrt(dx * dx + dy * dy);

        if (dist < maxDist) {
          ctx.beginPath();
          ctx.moveTo(particles[i].x, particles[i].y);
          ctx.lineTo(particles[j].x, particles[j].y);
          const alpha = (1 - dist / maxDist) * (width < 768 ? 0.10 : 0.15);
          ctx.strokeStyle = `rgba(66, 133, 244, ${alpha})`;
          ctx.lineWidth = 0.8;
          ctx.stroke();
        }
      }
    }

    // Update and draw particles
    particles.forEach(p => {
      p.update(time);
      p.draw();
    });

    requestAnimationFrame(animate);
  }

  // Event Listeners
  window.addEventListener('resize', () => {
    resize();
  });

  const heroSection = document.querySelector('.hero-section');
  if (heroSection) {
    heroSection.addEventListener('mousemove', (e) => {
      const rect = heroSection.getBoundingClientRect();
      mouse.targetX = e.clientX - rect.left;
      mouse.targetY = e.clientY - rect.top;
      mouse.isHovering = true;
    });

    heroSection.addEventListener('mouseleave', () => {
      mouse.isHovering = false;
      mouse.targetX = width * 0.7;
      mouse.targetY = height * 0.45;
    });

    // Desktop Click Burst
    heroSection.addEventListener('click', (e) => {
      const rect = heroSection.getBoundingClientRect();
      const clickX = e.clientX - rect.left;
      const clickY = e.clientY - rect.top;

      particles.forEach(p => {
        const dx = p.x - clickX;
        const dy = p.y - clickY;
        const dist = Math.sqrt(dx * dx + dy * dy) || 1;
        if (dist < 300) {
          const force = (1 - dist / 300) * 15;
          p.vx = (dx / dist) * force;
          p.vy = (dy / dist) * force;
        }
      });
    });

    // Touch interaction for smartphones
    heroSection.addEventListener('touchstart', (e) => {
      if (e.touches.length > 0) {
        const rect = heroSection.getBoundingClientRect();
        const touch = e.touches[0];
        const clickX = touch.clientX - rect.left;
        const clickY = touch.clientY - rect.top;

        mouse.targetX = clickX;
        mouse.targetY = clickY;

        particles.forEach(p => {
          const dx = p.x - clickX;
          const dy = p.y - clickY;
          const dist = Math.sqrt(dx * dx + dy * dy) || 1;
          if (dist < 200) {
            const force = (1 - dist / 200) * 12;
            p.vx = (dx / dist) * force;
            p.vy = (dy / dist) * force;
          }
        });
      }
    }, { passive: true });
  }

  // Launch Engine
  init();
  requestAnimationFrame(animate);
})();
