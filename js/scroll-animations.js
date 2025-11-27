document.addEventListener('DOMContentLoaded', () => {
    const animateOnScroll = (entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('is-visible');
                observer.unobserve(entry.target);
            }
        });
    };

    const options = {
        root: null,
        rootMargin: '0px',
        threshold: 0.1
    };

    const observer = new IntersectionObserver(animateOnScroll, options);

    const elements = document.querySelectorAll('.animate-on-scroll');
    elements.forEach(element => {
        observer.observe(element);
    });
});