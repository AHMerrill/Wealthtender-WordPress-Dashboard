/**
 * Wealthtender Analytics - Methodology Page
 * Accordion expand/collapse behavior for methodology sections
 */

document.addEventListener('DOMContentLoaded', function() {
    // Initialize accordion behavior
    initAccordion();

    // Initialize expand/collapse all buttons
    initExpandCollapseButtons();
});

/**
 * Initialize accordion expand/collapse behavior
 */
function initAccordion() {
    const headers = document.querySelectorAll('.wt-methodology-header');

    headers.forEach(function(header, index) {
        // Add click handler
        header.addEventListener('click', function(e) {
            e.preventDefault();

            const section = this.closest('.wt-methodology-section');
            if (section) {
                toggleSection(section);
            }
        });

        // Expand first section by default
        if (index === 0) {
            const section = header.closest('.wt-methodology-section');
            if (section) {
                section.classList.add('active');
            }
        }
    });
}

/**
 * Toggle the active state of a section
 */
function toggleSection(section) {
    section.classList.toggle('active');
}

/**
 * Initialize expand/collapse all buttons
 */
function initExpandCollapseButtons() {
    const expandAllBtn = document.getElementById('expand-all-btn');
    const collapseAllBtn = document.getElementById('collapse-all-btn');

    if (expandAllBtn) {
        expandAllBtn.addEventListener('click', function(e) {
            e.preventDefault();
            expandAll();
        });
    }

    if (collapseAllBtn) {
        collapseAllBtn.addEventListener('click', function(e) {
            e.preventDefault();
            collapseAll();
        });
    }
}

/**
 * Expand all sections
 */
function expandAll() {
    const sections = document.querySelectorAll('.wt-methodology-section');
    sections.forEach(function(section) {
        section.classList.add('active');
    });
}

/**
 * Collapse all sections
 */
function collapseAll() {
    const sections = document.querySelectorAll('.wt-methodology-section');
    sections.forEach(function(section) {
        section.classList.remove('active');
    });
}
