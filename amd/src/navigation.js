// This file is part of Moodle - https://moodle.org/
// @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
/** @module local_glpisupport/navigation */
define([], function() {
    'use strict';
    var started = false;
    var selector = '.primary-navigation .navbar-nav, #main-menu .nav, #main-menu .navbar-nav, ' +
        '#main-navbar .nav, .navbar .navbar-nav, .navbar .nav, header .navbar-nav';

    function sameDestination(anchor, target) {
        try {
            var current = new URL(anchor.href, window.location.href);
            var expected = new URL(target, window.location.href);
            return current.origin === expected.origin && current.pathname === expected.pathname;
        } catch (error) {
            return false;
        }
    }

    function addShortcut(config) {
        var containers = Array.prototype.slice.call(document.querySelectorAll(selector));
        containers.forEach(function(container) {
            if (container.closest('.dropdown-menu') || container.closest('.glpisupport-summary')) {
                return;
            }
            // Ignore nested duplicates of a navigation list.
            if (containers.some(function(parent) { return parent !== container && parent.contains(container); })) {
                return;
            }
            var anchors = Array.prototype.slice.call(container.querySelectorAll('a'));
            var existing = anchors.find(function(anchor) { return sameDestination(anchor, config.url); });
            if (existing) {
                if (config.active) {
                    existing.classList.add('active');
                    existing.setAttribute('aria-current', 'page');
                }
                return;
            }
            var item = document.createElement('li');
            item.className = 'nav-item local-glpisupport-navitem';
            item.setAttribute('data-local-glpisupport-shortcut', '1');
            var link = document.createElement('a');
            link.className = 'nav-link local-glpisupport-navlink';
            link.href = config.url;
            link.textContent = config.label;
            if (config.active) {
                item.classList.add('active');
                link.classList.add('active');
                link.setAttribute('aria-current', 'page');
            }
            item.appendChild(link);
            container.appendChild(item);
        });
    }

    return {
        init: function(config) {
            if (started || !config || !config.url || !config.label) {
                return;
            }
            started = true;
            function initialise() {
                addShortcut(config);
                // Bounded observation accommodates theme initialisation order.
                if (window.MutationObserver) {
                    var scheduled = false;
                    var observer = new MutationObserver(function() {
                        if (!scheduled) {
                            scheduled = true;
                            window.requestAnimationFrame(function() {
                                scheduled = false;
                                addShortcut(config);
                            });
                        }
                    });
                    observer.observe(document.body, {childList: true, subtree: true});
                    window.setTimeout(function() { observer.disconnect(); }, 15000);
                }
            }
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', initialise, {once: true});
            } else {
                initialise();
            }
        }
    };
});
