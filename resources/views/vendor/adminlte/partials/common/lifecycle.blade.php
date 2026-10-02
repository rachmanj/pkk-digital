<script>
    (() => {
        'use strict';

        window._PkPanelReady = (callback) => {
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', callback, { once: true });
            } else {
                callback();
            }
        };

        window._PkPanelOnce = (key, callback) => {
            window._PkPanelOnceKeys = window._PkPanelOnceKeys || {};

            if (window._PkPanelOnceKeys[key]) {
                return;
            }

            window._PkPanelOnceKeys[key] = true;
            callback();
        };
    })();
</script>
