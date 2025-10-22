class SlideshowPresentation {
    constructor() {
        this.currentSlide = 1;
        this.totalSlides = 12;
        this.isNavigatorOpen = false;
        
        this.initElements();
        this.bindEvents();
        this.updateUI();
    }

    initElements() {
        // Navigation elements
        this.prevBtn = document.getElementById('prev-btn');
        this.nextBtn = document.getElementById('next-btn');
        this.navToggle = document.getElementById('nav-toggle');
        this.slideNavigator = document.getElementById('slide-navigator');
        
        // UI elements
        this.currentSlideSpan = document.getElementById('current-slide');
        this.totalSlidesSpan = document.getElementById('total-slides');
        this.progressFill = document.getElementById('progress-fill');
        
        // Slides
        this.slides = document.querySelectorAll('.slide');
        this.slideNavBtns = document.querySelectorAll('.slide-nav-btn');
    }

    bindEvents() {
        // Navigation button events
        this.prevBtn.addEventListener('click', () => this.previousSlide());
        this.nextBtn.addEventListener('click', () => this.nextSlide());
        
        // Navigator toggle
        this.navToggle.addEventListener('click', () => this.toggleNavigator());
        
        // Slide navigation buttons
        this.slideNavBtns.forEach(btn => {
            btn.addEventListener('click', (e) => {
                const slideNumber = parseInt(e.target.dataset.slide);
                this.goToSlide(slideNumber);
                this.toggleNavigator(); // Close navigator after selection
            });
        });

        // Keyboard navigation
        document.addEventListener('keydown', (e) => this.handleKeydown(e));
        
        // Close navigator when clicking outside
        document.addEventListener('click', (e) => {
            if (this.isNavigatorOpen && 
                !this.slideNavigator.contains(e.target) && 
                !this.navToggle.contains(e.target)) {
                this.toggleNavigator();
            }
        });

        // Touch/swipe support for mobile
        this.initTouchSupport();
    }

    initTouchSupport() {
        let startX = 0;
        let startY = 0;
        let endX = 0;
        let endY = 0;

        document.addEventListener('touchstart', (e) => {
            startX = e.touches[0].clientX;
            startY = e.touches[0].clientY;
        });

        document.addEventListener('touchend', (e) => {
            endX = e.changedTouches[0].clientX;
            endY = e.changedTouches[0].clientY;
            this.handleSwipe(startX, startY, endX, endY);
        });
    }

    handleSwipe(startX, startY, endX, endY) {
        const deltaX = endX - startX;
        const deltaY = endY - startY;
        const minSwipeDistance = 50;

        // Only handle horizontal swipes that are more significant than vertical
        if (Math.abs(deltaX) > Math.abs(deltaY) && Math.abs(deltaX) > minSwipeDistance) {
            if (deltaX > 0) {
                this.previousSlide();
            } else {
                this.nextSlide();
            }
        }
    }

    handleKeydown(e) {
        switch(e.key) {
            case 'ArrowLeft':
            case 'ArrowUp':
                e.preventDefault();
                this.previousSlide();
                break;
            case 'ArrowRight':
            case 'ArrowDown':
            case ' ': // Spacebar
                e.preventDefault();
                this.nextSlide();
                break;
            case 'Home':
                e.preventDefault();
                this.goToSlide(1);
                break;
            case 'End':
                e.preventDefault();
                this.goToSlide(this.totalSlides);
                break;
            case 'Escape':
                if (this.isNavigatorOpen) {
                    this.toggleNavigator();
                }
                break;
            default:
                // Check for number keys (1-9, 0 for 10)
                if (e.key >= '1' && e.key <= '9') {
                    const slideNumber = parseInt(e.key);
                    if (slideNumber <= this.totalSlides) {
                        this.goToSlide(slideNumber);
                    }
                } else if (e.key === '0' && this.totalSlides >= 10) {
                    this.goToSlide(10);
                }
                break;
        }
    }

    previousSlide() {
        if (this.currentSlide > 1) {
            this.goToSlide(this.currentSlide - 1);
        }
    }

    nextSlide() {
        if (this.currentSlide < this.totalSlides) {
            this.goToSlide(this.currentSlide + 1);
        }
    }

    goToSlide(slideNumber) {
        if (slideNumber < 1 || slideNumber > this.totalSlides) {
            return;
        }

        // Remove active class from current slide
        const currentSlideElement = document.querySelector(`.slide[data-slide="${this.currentSlide}"]`);
        if (currentSlideElement) {
            currentSlideElement.classList.remove('active');
            
            // Add prev class if going to a higher slide number
            if (slideNumber > this.currentSlide) {
                currentSlideElement.classList.add('prev');
            } else {
                currentSlideElement.classList.remove('prev');
            }
        }

        // Update current slide
        this.currentSlide = slideNumber;

        // Add active class to new slide
        const newSlideElement = document.querySelector(`.slide[data-slide="${this.currentSlide}"]`);
        if (newSlideElement) {
            newSlideElement.classList.add('active');
            newSlideElement.classList.remove('prev');
            
            // Trigger slide content animation
            const slideContent = newSlideElement.querySelector('.slide-content');
            if (slideContent) {
                slideContent.style.animation = 'none';
                slideContent.offsetHeight; // Trigger reflow
                slideContent.style.animation = 'slideInUp 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards';
            }
        }

        this.updateUI();
    }

    toggleNavigator() {
        this.isNavigatorOpen = !this.isNavigatorOpen;
        
        if (this.isNavigatorOpen) {
            this.slideNavigator.classList.remove('hidden');
            this.slideNavigator.classList.add('active');
        } else {
            this.slideNavigator.classList.remove('active');
            // Delay hiding to allow animation to complete
            setTimeout(() => {
                if (!this.isNavigatorOpen) {
                    this.slideNavigator.classList.add('hidden');
                }
            }, 250);
        }

        // Update nav toggle button appearance
        this.updateNavToggle();
    }

    updateNavToggle() {
        const spans = this.navToggle.querySelectorAll('span');
        if (this.isNavigatorOpen) {
            spans[0].style.transform = 'rotate(45deg) translate(5px, 5px)';
            spans[1].style.opacity = '0';
            spans[2].style.transform = 'rotate(-45deg) translate(7px, -6px)';
        } else {
            spans[0].style.transform = 'none';
            spans[1].style.opacity = '1';
            spans[2].style.transform = 'none';
        }
    }

    updateUI() {
        // Update slide counter
        this.currentSlideSpan.textContent = this.currentSlide;
        this.totalSlidesSpan.textContent = this.totalSlides;

        // Update progress bar
        const progressPercent = (this.currentSlide / this.totalSlides) * 100;
        this.progressFill.style.width = `${progressPercent}%`;

        // Update navigation buttons
        this.prevBtn.disabled = this.currentSlide === 1;
        this.nextBtn.disabled = this.currentSlide === this.totalSlides;

        // Update slide navigation buttons
        this.slideNavBtns.forEach(btn => {
            const slideNumber = parseInt(btn.dataset.slide);
            if (slideNumber === this.currentSlide) {
                btn.classList.add('active');
            } else {
                btn.classList.remove('active');
            }
        });

        // Update browser URL hash for deep linking
        history.replaceState(null, null, `#slide-${this.currentSlide}`);
    }

    // Initialize from URL hash if present
    initFromURL() {
        const hash = window.location.hash;
        const match = hash.match(/^#slide-(\d+)$/);
        if (match) {
            const slideNumber = parseInt(match[1]);
            if (slideNumber >= 1 && slideNumber <= this.totalSlides) {
                this.goToSlide(slideNumber);
                return;
            }
        }
        // Default to slide 1 if no valid hash
        this.goToSlide(1);
    }

    // Presentation mode utilities
    enterFullscreen() {
        if (document.documentElement.requestFullscreen) {
            document.documentElement.requestFullscreen();
        }
    }

    exitFullscreen() {
        if (document.exitFullscreen) {
            document.exitFullscreen();
        }
    }

    // Auto-advance functionality (optional)
    startAutoAdvance(intervalMs = 30000) {
        this.stopAutoAdvance();
        this.autoAdvanceInterval = setInterval(() => {
            if (this.currentSlide < this.totalSlides) {
                this.nextSlide();
            } else {
                this.stopAutoAdvance();
            }
        }, intervalMs);
    }

    stopAutoAdvance() {
        if (this.autoAdvanceInterval) {
            clearInterval(this.autoAdvanceInterval);
            this.autoAdvanceInterval = null;
        }
    }
}

// Initialize the slideshow when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    const slideshow = new SlideshowPresentation();
    
    // Initialize from URL hash
    slideshow.initFromURL();
    
    // Handle browser back/forward
    window.addEventListener('popstate', () => {
        slideshow.initFromURL();
    });

    // Prevent context menu on touch devices to avoid interference with swipes
    document.addEventListener('contextmenu', (e) => {
        if (e.touches && e.touches.length > 1) {
            e.preventDefault();
        }
    });

    // Add keyboard shortcuts info (optional - could be shown in help overlay)
    console.log('Keyboard shortcuts:');
    console.log('← → ↑ ↓ Space: Navigate slides');
    console.log('1-9, 0: Jump to slide');
    console.log('Home/End: First/Last slide');
    console.log('Escape: Close navigator');
    
    // Make slideshow globally accessible for debugging
    window.slideshow = slideshow;
});