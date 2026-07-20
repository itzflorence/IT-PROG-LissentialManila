function moveCarousel(buttonElement, direction) {
    const wrapper = buttonElement.closest('.post-media-carousel');
    const container = wrapper.querySelector('.carousel-container');
    const scrollAmount = container.offsetWidth * direction;
    
    container.scrollBy({
        left: scrollAmount,
        behavior: 'smooth'
    });
}

// automatically hides arrows on single-slide carousels
function initializeCarousels() {
    const carousels = document.querySelectorAll('.post-media-carousel');
    
    carousels.forEach(carousel => {
        const slides = carousel.querySelectorAll('.carousel-slide');
        
        // If there is only 1 slide (or zero), hide the arrow controls
        if (slides.length <= 1) {
            const buttons = carousel.querySelectorAll('.carousel-btn');
            buttons.forEach(btn => btn.classList.add('carousel-hidden'));
        }
    });
}
initializeCarousels();