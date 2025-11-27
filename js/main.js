document.addEventListener('DOMContentLoaded', function() {

    // Header scroll effect
    const header = document.querySelector('.main-header');
    window.addEventListener('scroll', () => {
        if (window.scrollY > 50) {
            header.classList.add('scrolled');
        } else {
            header.classList.remove('scrolled');
        }
    });

    // Hero Carousel
    const carousel = document.querySelector('.hero-carousel');
    if (carousel) {
        const slides = carousel.querySelectorAll('.carousel-item');
        const prevBtn = carousel.querySelector('.prev');
        const nextBtn = carousel.querySelector('.next');
        let currentSlide = 0;

        function showSlide(index) {
            slides.forEach((slide, i) => {
                slide.classList.remove('active');
                if (i === index) {
                    slide.classList.add('active');
                }
            });
        }

        function nextSlide() {
            currentSlide = (currentSlide + 1) % slides.length;
            showSlide(currentSlide);
        }

        if (prevBtn && nextBtn) {
            prevBtn.addEventListener('click', () => {
                currentSlide = (currentSlide - 1 + slides.length) % slides.length;
                showSlide(currentSlide);
            });

            nextBtn.addEventListener('click', nextSlide);
        }

        setInterval(nextSlide, 5000); // Auto-play carousel
    }

    // Animate on scroll
    const scrollElements = document.querySelectorAll('.animate-on-scroll');

    const elementInView = (el, dividend = 1) => {
        const elementTop = el.getBoundingClientRect().top;

        return (
            elementTop <= (window.innerHeight || document.documentElement.clientHeight) / dividend
        );
    };

    const displayScrollElement = (element) => {
        element.classList.add('is-visible');
    };

    const hideScrollElement = (element) => {
        element.classList.remove('is-visible');
    };

    const handleScrollAnimation = () => {
        scrollElements.forEach((el) => {
            if (elementInView(el, 1.25)) {
                displayScrollElement(el);
            } else {
                hideScrollElement(el);
            }
        })
    }

    window.addEventListener('scroll', () => {
        handleScrollAnimation();
    });

    // Loader global para pedidos: creación y helpers
    (function() {
        const existing = document.getElementById('loading-overlay');
        if (!existing) {
            const overlay = document.createElement('div');
            overlay.id = 'loading-overlay';
            overlay.style.display = 'none';
            overlay.style.alignItems = 'center';
            overlay.style.justifyContent = 'center';
            const box = document.createElement('div');
            box.className = 'loader-box';
            const spinner = document.createElement('div');
            spinner.className = 'spinner';
            const text = document.createElement('div');
            text.className = 'loader-text';
            text.textContent = 'Cargando pedidos...';
            box.appendChild(spinner);
            box.appendChild(text);
            overlay.appendChild(box);
            document.body.appendChild(overlay);
        }
        window.showLoader = function() {
            const el = document.getElementById('loading-overlay');
            if (el) el.style.display = 'flex';
        };
        window.hideLoader = function() {
            const el = document.getElementById('loading-overlay');
            if (el) el.style.display = 'none';
        };
    })();

    // Envolver fetch para mostrar loader en llamadas a get_pedidos.php
    (function() {
        if (!window._fetchWrapped) {
            const origFetch = window.fetch.bind(window);
            window.fetch = function(...args) {
                try {
                    const url = args[0];
                    const isPedidos = (typeof url === 'string' && url.includes('php/get_pedidos.php')) ||
                        (typeof url === 'object' && url && typeof url.url === 'string' && url.url.includes('php/get_pedidos.php'));
                    if (isPedidos && typeof window.showLoader === 'function') {
                        window.showLoader();
                    }
                    const p = origFetch(...args);
                    if (isPedidos && typeof window.hideLoader === 'function') {
                        return p.finally(() => window.hideLoader());
                    }
                    return p;
                } catch (e) {
                    // Si algo falla, no bloquear fetch
                    return origFetch(...args);
                }
            };
            window._fetchWrapped = true;
        }
    })();

});