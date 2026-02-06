/*!
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

(function() {
    'use strict';

    if (typeof piwik === 'undefined') {
        return;
    }

    var STORAGE_KEY = 'mtm_defaultSegmentApplied';

    function getStoredSegment() {
        try {
            return localStorage.getItem(STORAGE_KEY);
        } catch (e) {
            return null;
        }
    }

    function storeSegment(segment) {
        try {
            localStorage.setItem(STORAGE_KEY, segment);
        } catch (e) {}
    }

    function getCurrentUrlSegment() {
        var hash = broadcast.getHashFromUrl() || '';
        var hashHasSegment = (/[?&]segment(=|&|$)/).test(hash);
        var qsHasSegment = (/[?&]segment(=|&|$)/).test(window.location.search);

        if (hashHasSegment) {
            var match = hash.match(/[?&]segment=([^&]*)/);
            return match ? decodeURIComponent(match[1]) : '';
        }

        if (qsHasSegment) {
            var qsMatch = window.location.search.match(/[?&]segment=([^&]*)/);
            return qsMatch ? decodeURIComponent(qsMatch[1]) : '';
        }

        return null;
    }

    function navigateWithSegment(segment) {
        broadcast.propagateNewPage('segment=' + encodeURIComponent(segment), true);
    }

    function removeSegmentFromUrl() {
        broadcast.propagateNewPage('segment=', true);
    }

    function handleDefaultSegment() {
        if (piwik.userLogin === 'anonymous') {
            return;
        }

        if (!document.querySelector('.segmentEditorPanel')) {
            return;
        }

        var defaultSegment = piwik.defaultSegment || '';
        var storedSegment = getStoredSegment();
        var urlSegment = getCurrentUrlSegment();

        // First time running (no stored state yet)
        if (storedSegment === null) {
            if (defaultSegment && urlSegment === null) {
                navigateWithSegment(defaultSegment);
            }
            storeSegment(defaultSegment);
            return;
        }

        // Setting hasn't changed since last visit
        if (defaultSegment === storedSegment) {
            if (defaultSegment && urlSegment === null) {
                navigateWithSegment(defaultSegment);
            }
            return;
        }

        // Setting has changed: handle the transition
        if (urlSegment !== null && urlSegment === storedSegment) {
            // URL still has the previously auto-applied segment
            if (defaultSegment) {
                navigateWithSegment(defaultSegment);
            } else {
                removeSegmentFromUrl();
            }
        } else if (defaultSegment && urlSegment === null) {
            // New default set and no segment in URL
            navigateWithSegment(defaultSegment);
        }

        storeSegment(defaultSegment);
    }

    $(document).ready(function() {
        if (document.querySelector('.segmentEditorPanel')) {
            handleDefaultSegment();
            return;
        }

        var observer = new MutationObserver(function() {
            if (document.querySelector('.segmentEditorPanel')) {
                observer.disconnect();
                handleDefaultSegment();
            }
        });

        observer.observe(document.body, { childList: true, subtree: true });

        // Safety: stop observing after 10 seconds to prevent memory leaks
        setTimeout(function() { observer.disconnect(); }, 10000);
    });

})();
