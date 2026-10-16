/**
 * Student Hub Portal - Theme Management System
 * Handles Dark and Light mode toggling with persistent state across all pages.
 */

(function () {
    'use strict';

    const THEME_KEY = 'student_portal_theme';

    /**
     * Get the active or stored theme preference
     */
    function getStoredTheme() {
        const stored = localStorage.getItem(THEME_KEY);
        if (stored === 'dark' || stored === 'light') {
            return stored;
        }
        // Fallback to system preference if user hasn't chosen yet
        if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
            return 'dark';
        }
        return 'light';
    }

    /**
     * Apply theme to DOM and sync UI components
     */
    function applyTheme(theme, persist = true) {
        const targetTheme = (theme === 'dark') ? 'dark' : 'light';

        // Set attribute on root HTML element
        document.documentElement.setAttribute('data-theme', targetTheme);

        // Optionally persist to localStorage
        if (persist) {
            try {
                localStorage.setItem(THEME_KEY, targetTheme);
            } catch (e) {
                console.warn('Unable to persist theme to localStorage', e);
            }
        }

        // Update all theme toggle buttons across the page
        const toggleButtons = document.querySelectorAll('.theme-toggle-btn, #theme-toggle');
        toggleButtons.forEach(btn => {
            btn.setAttribute('aria-pressed', targetTheme === 'dark');
            btn.setAttribute('title', targetTheme === 'dark' ? 'Switch to Light Mode' : 'Switch to Dark Mode');
            
            const textEl = btn.querySelector('.theme-toggle-text');
            if (textEl) {
                textEl.textContent = targetTheme === 'dark' ? 'Dark' : 'Light';
            }
        });

        // Update settings option buttons if present
        const optionButtons = document.querySelectorAll('[data-theme-set]');
        optionButtons.forEach(btn => {
            const btnTheme = btn.getAttribute('data-theme-set');
            if (btnTheme === targetTheme) {
                btn.classList.add('active');
            } else {
                btn.classList.remove('active');
            }
        });

        // Dispatch a custom event for any other scripts or components
        window.dispatchEvent(new CustomEvent('themechange', { detail: { theme: targetTheme } }));
    }

    /**
     * Toggle between light and dark themes
     */
    function toggleTheme() {
        const currentTheme = document.documentElement.getAttribute('data-theme') || getStoredTheme();
        const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
        applyTheme(newTheme, true);
    }

    // Immediately ensure root data-theme attribute is accurate before DOM paints
    const initialTheme = getStoredTheme();
    document.documentElement.setAttribute('data-theme', initialTheme);

    // Initialize interactive event listeners once DOM is ready
    function initTheme() {
        // Re-apply to ensure any newly loaded buttons reflect current theme
        applyTheme(getStoredTheme(), false);

        // Event delegation for toggle buttons
        document.addEventListener('click', function (e) {
            const toggleBtn = e.target.closest('.theme-toggle-btn, #theme-toggle');
            if (toggleBtn) {
                e.preventDefault();
                toggleTheme();
                return;
            }

            const themeOption = e.target.closest('[data-theme-set]');
            if (themeOption) {
                e.preventDefault();
                const chosen = themeOption.getAttribute('data-theme-set');
                if (chosen === 'dark' || chosen === 'light') {
                    applyTheme(chosen, true);
                }
            }
        });

        // Listen for storage events (syncs across other open tabs/windows in real time)
        window.addEventListener('storage', function (e) {
            if (e.key === THEME_KEY && e.newValue) {
                applyTheme(e.newValue, false);
            }
        });

        // Listen for system theme changes if no explicit user preference is stored
        if (window.matchMedia) {
            window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', function (e) {
                if (!localStorage.getItem(THEME_KEY)) {
                    applyTheme(e.matches ? 'dark' : 'light', false);
                }
            });
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initTheme);
    } else {
        initTheme();
    }

    // Expose helpers on window for programmatic access
    window.StudentHubTheme = {
        getTheme: function () {
            return document.documentElement.getAttribute('data-theme') || getStoredTheme();
        },
        setTheme: applyTheme,
        toggleTheme: toggleTheme
    };
})();
