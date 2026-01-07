(function () {
    'use strict';

    /**
     * About Us Evolution Timeline Interaction
     * Simple hover effects for timeline years
     */
    document.addEventListener('DOMContentLoaded', () => {
        const timelineYears = document.querySelectorAll('.about-evolution__year');

        if (!timelineYears.length) return;

        timelineYears.forEach((year) => {
            year.addEventListener('mouseenter', () => {
                // Remove active class from all years
                timelineYears.forEach((y) => y.classList.remove('is-active'));
                
                // Add active class to hovered year
                year.classList.add('is-active');
            });
        });
    });
})();
