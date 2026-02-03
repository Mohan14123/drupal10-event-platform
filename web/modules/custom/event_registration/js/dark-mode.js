/**
 * @file
 * Dark mode toggle functionality with localStorage persistence.
 */

(function (Drupal) {
    'use strict';

    const STORAGE_KEY = 'event_registration_dark_mode';

    /**
     * Get system color scheme preference.
     * 
     * @return {boolean} True if system prefers dark mode.
     */
    function getSystemPreference() {
        return window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
    }

    /**
     * Get saved preference from localStorage.
     * 
     * @return {string|null} 'dark', 'light', or null if not set.
     */
    function getSavedPreference() {
        try {
            return localStorage.getItem(STORAGE_KEY);
        } catch (e) {
            return null;
        }
    }

    /**
     * Save preference to localStorage.
     * 
     * @param {string} mode - 'dark' or 'light'.
     */
    function savePreference(mode) {
        try {
            localStorage.setItem(STORAGE_KEY, mode);
        } catch (e) {
            // localStorage not available
        }
    }

    /**
     * Apply dark mode to the document.
     * 
     * @param {boolean} isDark - Whether to enable dark mode.
     */
    function applyDarkMode(isDark) {
        const root = document.documentElement;

        if (isDark) {
            root.classList.add('dark-mode');
            root.classList.remove('light-mode');
        } else {
            root.classList.remove('dark-mode');
            root.classList.add('light-mode');
        }

        // Update toggle button aria-label
        const toggle = document.querySelector('.dark-mode-toggle');
        if (toggle) {
            toggle.setAttribute('aria-pressed', isDark ? 'true' : 'false');
            toggle.setAttribute('aria-label', isDark ? 'Switch to light mode' : 'Switch to dark mode');
        }
    }

    /**
     * Initialize dark mode based on saved preference or system preference.
     */
    function initializeDarkMode() {
        const savedPreference = getSavedPreference();
        let isDark;

        if (savedPreference !== null) {
            isDark = savedPreference === 'dark';
        } else {
            isDark = getSystemPreference();
        }

        applyDarkMode(isDark);
    }

    /**
     * Toggle dark mode and save preference.
     */
    function toggleDarkMode() {
        const root = document.documentElement;
        const isDark = root.classList.contains('dark-mode');
        const newMode = isDark ? 'light' : 'dark';

        savePreference(newMode);
        applyDarkMode(newMode === 'dark');
    }

    /**
     * Create and inject the dark mode toggle button.
     */
    function createToggleButton() {
        if (document.querySelector('.dark-mode-toggle')) {
            return;
        }

        const button = document.createElement('button');
        button.className = 'dark-mode-toggle';
        button.type = 'button';
        button.setAttribute('aria-label', 'Toggle dark mode');
        button.setAttribute('aria-pressed', 'false');

        button.innerHTML = `
      <svg class="icon-moon" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
        <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/>
      </svg>
      <svg class="icon-sun" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
        <circle cx="12" cy="12" r="5"/>
        <line x1="12" y1="1" x2="12" y2="3" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
        <line x1="12" y1="21" x2="12" y2="23" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
        <line x1="4.22" y1="4.22" x2="5.64" y2="5.64" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
        <line x1="18.36" y1="18.36" x2="19.78" y2="19.78" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
        <line x1="1" y1="12" x2="3" y2="12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
        <line x1="21" y1="12" x2="23" y2="12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
        <line x1="4.22" y1="19.78" x2="5.64" y2="18.36" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
        <line x1="18.36" y1="5.64" x2="19.78" y2="4.22" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
      </svg>
    `;

        button.addEventListener('click', toggleDarkMode);
        document.body.appendChild(button);
    }

    /**
     * Listen for system color scheme changes.
     */
    function listenForSystemChanges() {
        if (!window.matchMedia) {
            return;
        }

        const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');

        mediaQuery.addEventListener('change', function (e) {
            // Only auto-switch if user hasn't set a preference
            if (getSavedPreference() === null) {
                applyDarkMode(e.matches);
            }
        });
    }

    /**
     * Drupal behavior for dark mode.
     */
    Drupal.behaviors.eventRegistrationDarkMode = {
        attach: function (context, settings) {
            // Only run once on initial page load
            if (context !== document) {
                return;
            }

            initializeDarkMode();
            createToggleButton();
            listenForSystemChanges();
        }
    };

})(Drupal);
