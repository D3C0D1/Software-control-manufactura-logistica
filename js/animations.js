document.addEventListener('DOMContentLoaded', () => {
    // Asegurarse que GSAP y ScrollTrigger estén cargados
    if (typeof gsap === 'undefined' || typeof ScrollTrigger === 'undefined') {
        console.error('GSAP o ScrollTrigger no se han cargado.');
        return;
    }

    gsap.registerPlugin(ScrollTrigger);

    // --- Animación de Cursor Personalizado (Nuevo Método) ---
    const cursorDot = document.querySelector('.cursor-dot');
    const cursorOutline = document.querySelector('.cursor-outline');
    const root = document.documentElement;

    document.addEventListener('mousemove', e => {
        root.style.setProperty('--cursor-x', e.clientX + 'px');
        root.style.setProperty('--cursor-y', e.clientY + 'px');
    });

    document.addEventListener('mouseenter', () => {
        cursorDot.style.opacity = '1';
        cursorOutline.style.opacity = '1';
    });

    document.addEventListener('mouseleave', () => {
        cursorDot.style.opacity = '0';
        cursorOutline.style.opacity = '0';
    });

    document.querySelectorAll('a, button, .service-card-interactive').forEach(el => {
        el.addEventListener('mouseenter', () => {
            cursorOutline.style.transform = 'translate(-50%, -50%) scale(1.5)';
            cursorOutline.style.backgroundColor = 'var(--secondary-color-light)';
            cursorOutline.style.borderColor = 'var(--secondary-color)';
        });
        el.addEventListener('mouseleave', () => {
            cursorOutline.style.transform = 'translate(-50%, -50%) scale(1)';
            cursorOutline.style.backgroundColor = 'transparent';
            cursorOutline.style.borderColor = 'var(--secondary-color-light)';
        });
    });

    // --- Animación de Títulos de Sección ---
    gsap.utils.toArray('.section-title').forEach(title => {
        gsap.from(title, {
            scrollTrigger: {
                trigger: title,
                start: 'top 90%',
                toggleActions: 'play none none none',
            },
            opacity: 0,
            y: 50,
            duration: 1,
            ease: 'power3.out'
        });
    });

    // --- Animación de Galería con Revelado ---
    gsap.utils.toArray('.gallery-item').forEach(item => {
        gsap.from(item, {
            scrollTrigger: {
                trigger: item,
                start: 'top 85%',
                toggleActions: 'play none none none'
            },
            opacity: 0,
            scale: 1.1,
            duration: 1.2,
            ease: 'power4.out'
        });
    });

    // --- Animación del Hero Section --- (sin cambios)
    gsap.from('.hero-content h1', { duration: 1, y: 50, opacity: 0, ease: 'power3.out', delay: 0.2 });
    gsap.from('.hero-content p', { duration: 1.2, y: 60, opacity: 0, ease: 'power4.out', delay: 0.5 });
    gsap.from('.hero-content .cta-button', { duration: 1.2, y: 60, opacity: 0, ease: 'power4.out', delay: 0.7 });

    // Parallax para el fondo del hero
    gsap.to('.hero-section', {
        backgroundPosition: '50% 100%',
        ease: 'none',
        scrollTrigger: {
            trigger: '.hero-section',
            start: 'top top',
            end: 'bottom top',
            scrub: true
        }
    });

    // --- Animación de las tarjetas de servicio con efecto 3D en hover ---
    const serviceCards = gsap.utils.toArray('.service-card-interactive');
    serviceCards.forEach(card => {
        gsap.from(card, {
            scrollTrigger: {
                trigger: card,
                start: 'top 85%',
                toggleActions: 'play none none none',
            },
            opacity: 0,
            y: 80,
            duration: 1,
            ease: 'power4.out',
        });

        card.addEventListener('mousemove', (e) => {
            const { left, top, width, height } = card.getBoundingClientRect();
            const x = (e.clientX - left - width / 2) / 15;
            const y = (e.clientY - top - height / 2) / 15;
            gsap.to(card, { rotationY: x, rotationX: -y, transformPerspective: 500, ease: 'power1.out' });
        });

        card.addEventListener('mouseleave', () => {
            gsap.to(card, { rotationY: 0, rotationX: 0, ease: 'power3.out', duration: 0.8 });
        });
    });

    // --- Animación del portafolio con más dinamismo ---
    gsap.from('.portfolio-item-interactive', {
        scrollTrigger: {
            trigger: '.portfolio-grid-interactive',
            start: 'top 80%',
            toggleActions: 'play none none none',
        },
        opacity: 0,
        scale: 0.8,
        duration: 0.8,
        stagger: 0.2,
        ease: 'power3.out'
    });

    // --- Animación del boletín ---
    gsap.from('.newsletter-container > *', {
        scrollTrigger: {
            trigger: '.newsletter-section',
            start: 'top 75%',
            toggleActions: 'play none none none',
        },
        opacity: 0,
        y: 50,
        duration: 1,
        stagger: 0.2,
        ease: 'expo.out'
    });

    // --- Animación de la línea de tiempo del proceso ---
    gsap.utils.toArray('.timeline-item').forEach(item => {
        gsap.from(item, {
            scrollTrigger: {
                trigger: item,
                start: 'top 85%',
                toggleActions: 'play none none none',
            },
            opacity: 0,
            x: item.classList.contains('right') ? 100 : -100,
            duration: 1,
            ease: 'power3.out'
        });
    });

    // --- Animación de Testimonios ---
    gsap.from('.testimonial-card', {
        scrollTrigger: {
            trigger: '.testimonials-section',
            start: 'top 70%',
            toggleActions: 'play none none none',
        },
        opacity: 0,
        y: 50,
        duration: 1,
        stagger: 0.3,
        ease: 'power3.out'
    });

    // --- Animación de Galería Interactiva ---
    gsap.from('.gallery-item', {
        scrollTrigger: {
            trigger: '.gallery-section',
            start: 'top 75%',
            toggleActions: 'play none none none',
        },
        opacity: 0,
        scale: 0.9,
        duration: 0.8,
        stagger: 0.2,
        ease: 'back.out(1.7)'
    });

    // Animación para tarjetas de servicio, proceso y testimonios
    const animatedCards = document.querySelectorAll('.service-card, .step-card, .testimonial-card');

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('is-visible');
            } 
        });
    }, {
        threshold: 0.1
    });

    animatedCards.forEach(card => {
        observer.observe(card);
    });
});