function setupCarousel() {
    const carousel = document.querySelector('.hero-carousel');
    if (!carousel) {
        return;
    }

    const items = carousel.querySelectorAll('.carousel-item');
    if (items.length <= 1) {
        return;
    }

    let currentIndex = 0;
    const totalItems = items.length;
    const intervalTime = 5000; // 5 seconds

    function showItem(index) {
        items.forEach((item, i) => {
            item.classList.remove('active');
            if (i === index) {
                item.classList.add('active');
            }
        });
    }

    function nextItem() {
        currentIndex = (currentIndex + 1) % totalItems;
        showItem(currentIndex);
    }

    setInterval(nextItem, intervalTime);
}

setupCarousel();