/* ============================================================
   NK FLOWER DREAMS — script.js
   Animations: GSAP ScrollTrigger · Parallax Door Reveal ·
                Floating Petals · Content Reveals · Custom Cursor
   ============================================================ */

// ─────────────────────────────────────────────
// 0. Register plugins
// ─────────────────────────────────────────────
gsap.registerPlugin(ScrollTrigger);

// ─────────────────────────────────────────────
// 1. Custom Cursor
// ─────────────────────────────────────────────
(function initCursor() {
    const cursor = document.createElement('div');
    cursor.id = 'custom-cursor';
    document.body.appendChild(cursor);

    let mx = window.innerWidth / 2, my = window.innerHeight / 2;
    let cx = mx, cy = my;

    document.addEventListener('mousemove', e => {
        mx = e.clientX;
        my = e.clientY;
    });

    // Smooth follow
    function tick() {
        cx += (mx - cx) * 0.15;
        cy += (my - cy) * 0.15;
        cursor.style.left = cx + 'px';
        cursor.style.top  = cy + 'px';
        requestAnimationFrame(tick);
    }
    tick();

    // Hover state on interactive elements
    document.querySelectorAll('a, button, .product-item, .product-card, .feature-card, .details-card').forEach(el => {
        el.addEventListener('mouseenter', () => cursor.classList.add('hovered'));
        el.addEventListener('mouseleave', () => cursor.classList.remove('hovered'));
    });
})();


// ─────────────────────────────────────────────
// 2. Floating Petals Canvas
// ─────────────────────────────────────────────
(function initPetals() {
    const canvas = document.getElementById('petals-canvas');
    const ctx    = canvas.getContext('2d');

    function resize() {
        canvas.width  = window.innerWidth;
        canvas.height = window.innerHeight;
    }
    resize();
    window.addEventListener('resize', resize);

    const PETAL_COUNT = 22;
    const petals = [];

    const COLOURS = [
        'rgba(192, 90, 58, 0.55)',
        'rgba(200,147,74, 0.45)',
        'rgba(176, 96, 40, 0.4)',
        'rgba(212,169,106, 0.5)',
        'rgba(216,140,130, 0.45)',
        'rgba(240,200,180, 0.35)',
    ];

    class Petal {
        constructor() { this.reset(true); }

        reset(init = false) {
            this.x      = Math.random() * canvas.width;
            this.y      = init ? Math.random() * canvas.height : -30;
            this.size   = 6 + Math.random() * 12;
            this.speed  = 0.5 + Math.random() * 1.4;
            this.drift  = (Math.random() - 0.5) * 0.8;
            this.spin   = (Math.random() - 0.5) * 0.04;
            this.angle  = Math.random() * Math.PI * 2;
            this.colour = COLOURS[Math.floor(Math.random() * COLOURS.length)];
            this.wobble = Math.random() * Math.PI * 2;
            this.wobbleSpeed = 0.02 + Math.random() * 0.03;
        }

        update() {
            this.wobble += this.wobbleSpeed;
            this.x     += this.drift + Math.sin(this.wobble) * 0.5;
            this.y     += this.speed;
            this.angle += this.spin;
            if (this.y > canvas.height + 40) this.reset();
        }

        draw() {
            ctx.save();
            ctx.translate(this.x, this.y);
            ctx.rotate(this.angle);
            ctx.fillStyle = this.colour;
            ctx.beginPath();
            // Draw an organic petal shape
            ctx.ellipse(0, 0, this.size * 0.5, this.size, 0, 0, Math.PI * 2);
            ctx.fill();
            ctx.restore();
        }
    }

    for (let i = 0; i < PETAL_COUNT; i++) petals.push(new Petal());

    function loop() {
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        petals.forEach(p => { p.update(); p.draw(); });
        requestAnimationFrame(loop);
    }
    loop();
})();


// ─────────────────────────────────────────────
// 3. Hero Entrance (on page load)
// ─────────────────────────────────────────────
window.addEventListener('load', () => {
    const tl = gsap.timeline({ defaults: { ease: 'power3.out' } });

    tl
        // Shop closed image scales in gently
        .from('#img-closed', { scale: 1.06, duration: 2.2, ease: 'power2.out' }, 0)
        // Brand badge
        .to('.brand-badge',  { opacity: 1, y: 0, duration: 0.9 }, 0.4)
        // Title lines stagger up
        .to('.line-1', { opacity: 1, y: 0, duration: 1 }, 0.65)
        .to('.line-2', { opacity: 1, y: 0, duration: 1 }, 0.82)
        .to('.line-3', { opacity: 1, y: 0, duration: 1 }, 0.98)
        // Tagline
        .to('.hero-tagline', { opacity: 1, y: 0, duration: 0.8 }, 1.2)
        // Scroll cue
        .to('.scroll-cue',   { opacity: 1, duration: 0.8 }, 1.55);
});


// ─────────────────────────────────────────────
// 4. Main Parallax Hero — Pinned Closed-Door Zoom
// ─────────────────────────────────────────────
(function initParallax() {
    /*
     * HOW IT WORKS
     * ─────────────────────────────────────────
    * One visual state is used:
    * 1) #layer-closed (shop_closed.png)
    *
    * The closed image zooms from the doorway center.
    * Then the hero fades to reveal main content.
     */

    const master = gsap.timeline({
        scrollTrigger: {
            trigger: '#parallax-wrapper',
            start: 'top top',
            end: '+=3600',
            scrub: 1.6,
            pin: true,
            anticipatePin: 1,
        }
    });

    // ── Phase 1: Hero text fades out ──────────────────────────────
    master
        .to('#layer-hero-text', {
            opacity: 0, y: -65,
            duration: 1.2, ease: 'power2.in'
        }, 0)

    // Closed state zoom.
        .to('#img-closed', {
            scale: 6,
            duration: 6.2, ease: 'power1.inOut'
        }, 0)

    // ── Phase 2: Subtle vignette deepens as we approach ────────────
        .to('.hero-vignette', {
            opacity: 0.5,                       // already at 1, just hold
            duration: 2
        }, 1.5)

    // ── Phase 3: Fade hero layer out to content ────────────────────
        .to('#layer-closed', {
            opacity: 0,
            duration: 1.1, ease: 'power2.inOut'
        }, 5.1);

})();


// ─────────────────────────────────────────────
// 5. (Handled by master timeline above)
// ─────────────────────────────────────────────


// ─────────────────────────────────────────────
// 6. Content Section Reveal Animations
// ─────────────────────────────────────────────
(function initContentReveals() {

    // Generic fade
    gsap.utils.toArray('.reveal-fade').forEach(el => {
        gsap.to(el, {
            opacity: 1,
            duration: 1.2,
            ease: 'power2.out',
            scrollTrigger: {
                trigger: el,
                start: 'top 88%',
                toggleActions: 'play none none reverse'
            }
        });
    });

    // Fade + upward slide
    gsap.utils.toArray('.reveal-up').forEach(el => {
        gsap.to(el, {
            opacity: 1,
            y: 0,
            duration: 1.1,
            ease: 'power3.out',
            scrollTrigger: {
                trigger: el,
                start: 'top 86%',
                toggleActions: 'play none none reverse'
            }
        });
    });

    // Cards stagger
    gsap.utils.toArray('.reveal-card').forEach(el => {
        const delay = parseFloat(el.dataset.delay || 0) / 1000;
        gsap.to(el, {
            opacity: 1,
            y: 0,
            scale: 1,
            duration: 1.0,
            delay: delay,
            ease: 'power3.out',
            scrollTrigger: {
                trigger: el,
                start: 'top 88%',
                toggleActions: 'play none none reverse'
            }
        });
    });

})();


// ─────────────────────────────────────────────
// 7. Section Background Parallax
//    (subtle image shifting on non-pinned sections)
// ─────────────────────────────────────────────
(function initSectionParallax() {

    // Intro section — decorative "NK" letter drifts
    gsap.to('.section-intro::after', {
        yPercent: -15,
        ease: 'none',
        scrollTrigger: {
            trigger: '#section-intro',
            start: 'top bottom',
            end: 'bottom top',
            scrub: true,
        }
    });

    // Product images — mild parallax inside cards/slides
    gsap.utils.toArray('.product-img:not([style*="object-position"])').forEach(img => {
        const triggerEl = img.closest('.carousel-slide, .product-item') || img;
        gsap.fromTo(img, {
            yPercent: -6,
        }, {
            yPercent: 6,
            ease: 'none',
            scrollTrigger: {
                trigger: triggerEl,
                start: 'top bottom',
                end: 'bottom top',
                scrub: 1.2,
            }
        });
    });

    // CTA background layer drifts
    gsap.fromTo('.cta-bg-layer', {
        yPercent: -8,
    }, {
        yPercent: 8,
        ease: 'none',
        scrollTrigger: {
            trigger: '#section-cta',
            start: 'top bottom',
            end: 'bottom top',
            scrub: 1.5,
        }
    });

})();


// ─────────────────────────────────────────────
// 8. Section Title Clip-Reveal Effect
// ─────────────────────────────────────────────
(function initTitleReveal() {
    gsap.utils.toArray('.section-title').forEach(title => {
        // Clip from below on enter
        gsap.from(title, {
            clipPath: 'inset(100% 0% 0% 0%)',
            opacity: 0,
            y: 30,
            duration: 1.3,
            ease: 'power4.out',
            scrollTrigger: {
                trigger: title,
                start: 'top 85%',
                toggleActions: 'play none none reverse'
            }
        });
    });
})();


// ─────────────────────────────────────────────
// 9. Features Grid — Staggered Entrance
// ─────────────────────────────────────────────
(function initFeaturesStagger() {
    const grid = document.querySelector('.features-grid');
    if (!grid) return;

    ScrollTrigger.create({
        trigger: grid,
        start: 'top 80%',
        onEnter: () => {
            gsap.to('.features-grid .reveal-card', {
                opacity: 1, y: 0, scale: 1,
                stagger: 0.14,
                duration: 0.9,
                ease: 'power3.out',
                overwrite: true,
            });
        },
        onLeaveBack: () => {
            gsap.to('.features-grid .reveal-card', {
                opacity: 0, y: 40, scale: 0.97,
                stagger: 0.08,
                duration: 0.5,
                ease: 'power2.in',
                overwrite: true,
            });
        }
    });
})();


// ─────────────────────────────────────────────
// 10. Stat Counter Animation
// ─────────────────────────────────────────────
(function initStatCounters() {
    ScrollTrigger.create({
        trigger: '.intro-stat-row',
        start: 'top 85%',
        once: true,
        onEnter: () => {
            // The stat numbers themselves don't need counting but
            // we do a nice scale-in reveal
            gsap.from('.stat-pill', {
                scale: 0.7,
                opacity: 0,
                stagger: 0.15,
                duration: 0.8,
                ease: 'back.out(1.8)',
            });
        }
    });
})();


// ─────────────────────────────────────────────
// 11. Horizontal marquee for intro section
//     (decorative ambient motion)
// ─────────────────────────────────────────────
(function initMarquee() {
    // Build a subtle scrolling text strip below intro stats
    const intro = document.querySelector('.section-intro');
    if (!intro) return;

    const strip = document.createElement('div');
    strip.className = 'marquee-strip';
    strip.innerHTML = `
        <div class="marquee-track">
            <span>✦ Handmade</span><span>·</span>
            <span>Island-wide Delivery</span><span>·</span>
            <span>Custom Orders</span><span>·</span>
            <span>Foam & Fabric Art</span><span>·</span>
            <span>Forever Blooms</span><span>·</span>
            <span>✦ Handmade</span><span>·</span>
            <span>Island-wide Delivery</span><span>·</span>
            <span>Custom Orders</span><span>·</span>
            <span>Foam & Fabric Art</span><span>·</span>
            <span>Forever Blooms</span><span>·</span>
        </div>
    `;
    intro.querySelector('.container').appendChild(strip);

    // Inject strip styles dynamically
    const style = document.createElement('style');
    style.textContent = `
        .marquee-strip {
            overflow: hidden;
            margin-top: 5rem;
            padding: 1.4rem 0;
            border-top: 1px solid rgba(26,16,10,0.07);
            border-bottom: 1px solid rgba(26,16,10,0.07);
        }
        .marquee-track {
            display: inline-flex;
            gap: 2.5rem;
            white-space: nowrap;
            animation: marqueeScroll 22s linear infinite;
            font-family: 'Cormorant Garamond', serif;
            font-style: italic;
            font-size: 1.15rem;
            color: rgba(26,16,10,0.3);
            letter-spacing: 0.04em;
        }
        .marquee-track span:not(:last-child) {
            color: rgba(200,147,74,0.55);
        }
        @keyframes marqueeScroll {
            from { transform: translateX(0); }
            to   { transform: translateX(-50%); }
        }
    `;
    document.head.appendChild(style);
})();


// ─────────────────────────────────────────────
// 12. Smooth scroll offset for anchors
// ─────────────────────────────────────────────
document.querySelectorAll('a[href^="#"]').forEach(a => {
    a.addEventListener('click', e => {
        const href = a.getAttribute('href');
        if (!href || href === '#') return;

        const target = document.querySelector(href);
        if (!target) return;

        e.preventDefault();
        const offset = window.matchMedia('(max-width: 900px)').matches ? 96 : 74;
        const top = target.getBoundingClientRect().top + window.scrollY - offset;
        window.scrollTo({
            top: Math.max(0, top),
            behavior: 'smooth'
        });
    });
});


// ─────────────────────────────────────────────
// 12b. Mobile Quick Nav (Contacts/Payments)
// ─────────────────────────────────────────────
(function initMobileQuickNav() {
    const quickLinks = document.querySelectorAll('.mobile-bottom-nav__item');
    if (!quickLinks.length) return;

    function jumpTo(hash) {
        const target = document.querySelector(hash);
        if (!target) return;

        const top = target.getBoundingClientRect().top + window.scrollY - 96;
        window.scrollTo({
            top: Math.max(0, top),
            behavior: 'smooth'
        });
    }

    quickLinks.forEach(link => {
        link.addEventListener('click', e => {
            const hash = link.getAttribute('href');
            if (!hash || !hash.startsWith('#')) return;
            e.preventDefault();
            jumpTo(hash);
        });

        link.addEventListener('touchend', e => {
            const hash = link.getAttribute('href');
            if (!hash || !hash.startsWith('#')) return;
            e.preventDefault();
            jumpTo(hash);
        }, { passive: false });
    });
})();


// ─────────────────────────────────────────────
// 13. Products Carousel (left/right)
// ─────────────────────────────────────────────
(function initProductsCarousel() {
    const carousel = document.getElementById('products-carousel');
    if (!carousel) return;

    const viewport = carousel.querySelector('.carousel-viewport');
    const track = carousel.querySelector('.carousel-track');
    const slides = Array.from(carousel.querySelectorAll('.carousel-slide'));
    const prevBtn = carousel.querySelector('.carousel-btn--prev');
    const nextBtn = carousel.querySelector('.carousel-btn--next');

    if (!viewport || !track || !prevBtn || !nextBtn || slides.length === 0) return;

    let index = 0;
    let autoplayId = null;
    let swipeStartX = 0;
    let swipeStartY = 0;
    const AUTOPLAY_DELAY = 3200;
    const SWIPE_THRESHOLD = 45;

    function getMaxIndex() {
        return Math.max(0, slides.length - getSlidesPerView());
    }

    function getSlidesPerView() {
        const raw = getComputedStyle(carousel).getPropertyValue('--slides-per-view').trim();
        const value = parseInt(raw, 10);
        return Number.isNaN(value) || value < 1 ? 1 : value;
    }

    function update() {
        const slidesPerView = getSlidesPerView();
        const maxIndex = Math.max(0, slides.length - slidesPerView);
        index = Math.max(0, Math.min(index, maxIndex));

        track.style.transform = `translateX(-${(index * 100) / slidesPerView}%)`;
        prevBtn.disabled = false;
        nextBtn.disabled = false;
    }

    function stepBy(delta) {
        const maxIndex = getMaxIndex();
        if (maxIndex === 0) {
            index = 0;
            update();
            return;
        }

        index += delta;
        if (index > maxIndex) index = 0;
        if (index < 0) index = maxIndex;
        update();
    }

    function startAutoplay() {
        if (autoplayId || slides.length <= getSlidesPerView()) return;
        autoplayId = window.setInterval(() => stepBy(1), AUTOPLAY_DELAY);
    }

    function stopAutoplay() {
        if (!autoplayId) return;
        window.clearInterval(autoplayId);
        autoplayId = null;
    }

    prevBtn.addEventListener('click', () => {
        stepBy(-1);
    });

    nextBtn.addEventListener('click', () => {
        stepBy(1);
    });

    carousel.addEventListener('mouseenter', stopAutoplay);
    carousel.addEventListener('mouseleave', startAutoplay);

    viewport.addEventListener('pointerdown', e => {
        swipeStartX = e.clientX;
        swipeStartY = e.clientY;
    });

    viewport.addEventListener('pointerup', e => {
        const dx = e.clientX - swipeStartX;
        const dy = e.clientY - swipeStartY;

        if (Math.abs(dx) < SWIPE_THRESHOLD || Math.abs(dx) <= Math.abs(dy)) return;
        if (dx < 0) stepBy(1);
        if (dx > 0) stepBy(-1);
    });

    document.addEventListener('visibilitychange', () => {
        if (document.hidden) stopAutoplay();
        else startAutoplay();
    });

    window.addEventListener('resize', () => {
        stopAutoplay();
        update();
        startAutoplay();
    });

    update();
    startAutoplay();
})();


// ─────────────────────────────────────────────
// 14. Product Detail Modal
// ─────────────────────────────────────────────
(function initProductModal() {
    const modal = document.getElementById('product-modal');
    const title = document.getElementById('product-modal-title');
    const description = document.getElementById('product-modal-description');
    const price = document.getElementById('product-modal-price');
    const image = document.getElementById('product-modal-image');

    if (!modal || !title || !description || !price || !image) return;

    const closeTargets = modal.querySelectorAll('[data-modal-close]');
    const productCards = document.querySelectorAll('.product-card[data-product-name]');
    let lastFocusedElement = null;

    function openModal(card) {
        const name = card.dataset.productName || '';
        const descriptionText = card.dataset.productDescription || '';
        const priceValue = card.dataset.productPrice || '';
        const imagePath = card.dataset.productImage || '';

        title.textContent = name;
        description.textContent = descriptionText || 'Handcrafted floral design from NK Flower Dreams.';
        price.textContent = priceValue ? `Rs. ${priceValue}` : '';
        price.hidden = !priceValue;
        image.src = imagePath;
        image.alt = name;

        lastFocusedElement = document.activeElement;
        modal.classList.add('is-open');
        modal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('modal-open');

        const closeButton = modal.querySelector('.product-modal__close');
        if (closeButton) closeButton.focus();
    }

    function closeModal() {
        modal.classList.remove('is-open');
        modal.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('modal-open');

        if (lastFocusedElement && typeof lastFocusedElement.focus === 'function') {
            lastFocusedElement.focus();
        }
    }

    productCards.forEach(card => {
        card.addEventListener('click', () => openModal(card));
        card.addEventListener('keydown', e => {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                openModal(card);
            }
        });
    });

    closeTargets.forEach(target => {
        target.addEventListener('click', closeModal);
    });

    document.addEventListener('keydown', e => {
        if (e.key === 'Escape' && modal.classList.contains('is-open')) {
            closeModal();
        }
    });
})();