/**
 * Wealthtender Analytics - Splash Page
 * Initialization script for the splash/home page
 */

document.addEventListener('DOMContentLoaded', function() {
    // Check API health
    checkApiHealth();

    // Initialize navigation card click handlers
    initNavigationCards();
});

/**
 * Check if the API is reachable
 */
async function checkApiHealth() {
    const splashHero = document.querySelector('.wt-splash-hero');

    if (!splashHero) {
        return;
    }

    // Call the health check endpoint
    if (typeof WT !== 'undefined' && typeof WT.api === 'function') {
        try {
            const response = await WT.api('health');
            if (response && response.status === 'ok') {
                // API is reachable
                splashHero.classList.add('wt-api-connected');
                splashHero.classList.remove('wt-api-offline');
            } else {
                // API is offline
                splashHero.classList.add('wt-api-offline');
                splashHero.classList.remove('wt-api-connected');
            }
        } catch (error) {
            // API is offline
            splashHero.classList.add('wt-api-offline');
            splashHero.classList.remove('wt-api-connected');
        }
    } else {
        // WT API not available
        splashHero.classList.add('wt-api-offline');
    }
}

/**
 * Initialize navigation card click handlers
 */
function initNavigationCards() {
    const navCards = document.querySelectorAll('.wt-nav-card');

    navCards.forEach(function(card) {
        card.addEventListener('click', function(e) {
            e.preventDefault();

            const href = this.getAttribute('href') || this.getAttribute('data-href');
            if (href) {
                window.location.href = href;
            }
        });
    });
}
