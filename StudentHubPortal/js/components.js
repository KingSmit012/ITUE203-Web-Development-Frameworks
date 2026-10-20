(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {

        /* ==========================================================================
           1. Notification Banner
           ========================================================================== */
        const notificationBanner = document.getElementById('notification-banner');
        const bannerCloseBtn = document.getElementById('banner-close-btn');

        if (notificationBanner && bannerCloseBtn) {
            bannerCloseBtn.addEventListener('click', function () {
                notificationBanner.style.opacity = '0';
                setTimeout(function () {
                    notificationBanner.style.display = 'none';
                }, 300);
            });
        }

        /* ==========================================================================
           2. Hamburger Menu (Responsive Navigation)
           ========================================================================== */
        const hamburgerBtn = document.getElementById('hamburger-btn');
        const navRightMenu = document.getElementById('simple-nav-menu');

        if (hamburgerBtn && navRightMenu) {
            hamburgerBtn.addEventListener('click', function (e) {
                e.stopPropagation();
                navRightMenu.classList.toggle('open');
                const isOpen = navRightMenu.classList.contains('open');
                hamburgerBtn.setAttribute('aria-expanded', isOpen);
                hamburgerBtn.textContent = isOpen ? '✕' : '☰';
            });

            // Close menu when clicking outside
            document.addEventListener('click', function (e) {
                if (!navRightMenu.contains(e.target) && !hamburgerBtn.contains(e.target)) {
                    navRightMenu.classList.remove('open');
                    hamburgerBtn.setAttribute('aria-expanded', 'false');
                    hamburgerBtn.textContent = '☰';
                }
            });
        }

        /* ==========================================================================
           3. Image Slider
           ========================================================================== */
        const sliderTrack = document.getElementById('slider-track');
        const slides = document.querySelectorAll('.slide');
        const prevBtn = document.getElementById('slider-prev');
        const nextBtn = document.getElementById('slider-next');
        const dotsContainer = document.getElementById('slider-dots');

        if (sliderTrack && slides.length > 0) {
            let currentSlide = 0;
            const totalSlides = slides.length;
            let autoSlideTimer = null;

            // Generate dot indicators dynamically
            if (dotsContainer) {
                dotsContainer.innerHTML = '';
                for (let i = 0; i < totalSlides; i++) {
                    const dot = document.createElement('span');
                    dot.classList.add('slider-dot');
                    if (i === 0) dot.classList.add('active');
                    dot.setAttribute('data-slide-index', i);
                    dot.setAttribute('aria-label', 'Slide ' + (i + 1));
                    dot.addEventListener('click', function () {
                        goToSlide(i);
                        resetAutoSlide();
                    });
                    dotsContainer.appendChild(dot);
                }
            }

            function updateDots() {
                const dots = document.querySelectorAll('.slider-dot');
                dots.forEach((dot, idx) => {
                    if (idx === currentSlide) {
                        dot.classList.add('active');
                    } else {
                        dot.classList.remove('active');
                    }
                });
            }

            function goToSlide(index) {
                if (index < 0) {
                    currentSlide = totalSlides - 1;
                } else if (index >= totalSlides) {
                    currentSlide = 0;
                } else {
                    currentSlide = index;
                }
                sliderTrack.style.transform = 'translateX(-' + (currentSlide * 100) + '%)';
                updateDots();
            }

            if (prevBtn) {
                prevBtn.addEventListener('click', function () {
                    goToSlide(currentSlide - 1);
                    resetAutoSlide();
                });
            }

            if (nextBtn) {
                nextBtn.addEventListener('click', function () {
                    goToSlide(currentSlide + 1);
                    resetAutoSlide();
                });
            }

            // Auto-slide every 4 seconds
            function startAutoSlide() {
                autoSlideTimer = setInterval(function () {
                    goToSlide(currentSlide + 1);
                }, 4000);
            }

            function stopAutoSlide() {
                if (autoSlideTimer) {
                    clearInterval(autoSlideTimer);
                    autoSlideTimer = null;
                }
            }

            function resetAutoSlide() {
                stopAutoSlide();
                startAutoSlide();
            }

            // Pause slider on hover
            const sliderContainer = document.getElementById('slider-container');
            if (sliderContainer) {
                sliderContainer.addEventListener('mouseenter', stopAutoSlide);
                sliderContainer.addEventListener('mouseleave', startAutoSlide);
            }

            startAutoSlide();
        }

        /* ==========================================================================
           4. Collapsible FAQ (Accordion)
           ========================================================================== */
        const faqQuestions = document.querySelectorAll('.faq-question');

        faqQuestions.forEach(function (btn) {
            btn.addEventListener('click', function () {
                const currentItem = this.closest('.faq-item');
                const isActive = currentItem.classList.contains('active');
                const icon = this.querySelector('.faq-icon');

                // Close other open FAQ items
                document.querySelectorAll('.faq-item').forEach(function (item) {
                    if (item !== currentItem) {
                        item.classList.remove('active');
                        item.querySelector('.faq-question')?.setAttribute('aria-expanded', 'false');
                        const otherIcon = item.querySelector('.faq-icon');
                        if (otherIcon) otherIcon.textContent = '+';
                    }
                });

                // Toggle current FAQ
                if (isActive) {
                    currentItem.classList.remove('active');
                    this.setAttribute('aria-expanded', 'false');
                    if (icon) icon.textContent = '+';
                } else {
                    currentItem.classList.add('active');
                    this.setAttribute('aria-expanded', 'true');
                    if (icon) icon.textContent = '−';
                }
            });
        });

        /* ==========================================================================
           5. Modal Popup
           ========================================================================== */
        const modal = document.getElementById('announcement-modal');
        const openModalBtns = document.querySelectorAll('[data-modal-open]');
        const closeModalBtns = document.querySelectorAll('[data-modal-close]');

        function openModal() {
            if (modal) {
                modal.classList.add('open');
                document.body.style.overflow = 'hidden'; // Prevent background scroll
            }
        }

        function closeModal() {
            if (modal) {
                modal.classList.remove('open');
                document.body.style.overflow = '';
            }
        }

        openModalBtns.forEach(function (btn) {
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                openModal();
            });
        });

        closeModalBtns.forEach(function (btn) {
            btn.addEventListener('click', function () {
                closeModal();
            });
        });

        // Close when clicking modal backdrop overlay outside the box
        if (modal) {
            modal.addEventListener('click', function (e) {
                if (e.target === modal) {
                    closeModal();
                }
            });
        }

        // Close on pressing Escape key
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && modal && modal.classList.contains('open')) {
                closeModal();
            }
        });

    });
})();
