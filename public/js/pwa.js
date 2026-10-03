(function () {
    'use strict';

    var INSTALL_BTN_ID = 'pwa-install-btn';
    var IOS_HINT_ID = 'pwa-ios-hint';
    var IOS_HINT_DISMISS_ID = 'pwa-ios-hint-dismiss';
    var IOS_HINT_STORAGE_KEY = 'pkk-pwa-ios-hint-dismissed';

    function isSecureContextForSw() {
        return window.location.protocol === 'https:';
    }

    function registerServiceWorker() {
        if (!('serviceWorker' in navigator) || !isSecureContextForSw()) {
            return;
        }

        window.addEventListener('load', function () {
            navigator.serviceWorker.register('/sw.js').catch(function (err) {
                console.warn('[PWA] Pendaftaran service worker gagal:', err);
            });
        });
    }

    function getInstallButton() {
        return document.getElementById(INSTALL_BTN_ID);
    }

    function hideInstallButton() {
        var btn = getInstallButton();
        if (btn) {
            btn.hidden = true;
            btn.setAttribute('aria-hidden', 'true');
        }
    }

    function showInstallButton() {
        var btn = getInstallButton();
        if (btn) {
            btn.hidden = false;
            btn.removeAttribute('aria-hidden');
        }
    }

    function setupInstallPrompt() {
        var deferredPrompt = null;
        var btn = getInstallButton();

        if (!btn) {
            return;
        }

        hideInstallButton();

        window.addEventListener('beforeinstallprompt', function (e) {
            e.preventDefault();
            deferredPrompt = e;
            showInstallButton();
        });

        btn.addEventListener('click', function () {
            if (!deferredPrompt) {
                return;
            }

            deferredPrompt.prompt();
            deferredPrompt.userChoice.then(function (choice) {
                deferredPrompt = null;
                hideInstallButton();
                if (choice.outcome === 'dismissed') {
                    hideInstallButton();
                }
            }).catch(function () {
                deferredPrompt = null;
                hideInstallButton();
            });
        });

        window.addEventListener('appinstalled', function () {
            deferredPrompt = null;
            hideInstallButton();
        });
    }

    function isIosSafari() {
        var ua = window.navigator.userAgent;
        var isIOS = /iPad|iPhone|iPod/.test(ua) || (window.navigator.platform === 'MacIntel' && window.navigator.maxTouchPoints > 1);
        var isStandalone = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;
        return isIOS && !isStandalone;
    }

    function setupIosHint() {
        var hint = document.getElementById(IOS_HINT_ID);
        var dismiss = document.getElementById(IOS_HINT_DISMISS_ID);

        if (!hint || !isIosSafari()) {
            return;
        }

        try {
            if (localStorage.getItem(IOS_HINT_STORAGE_KEY) === '1') {
                return;
            }
        } catch (e) {
            return;
        }

        hint.hidden = false;
        hint.removeAttribute('aria-hidden');

        if (dismiss) {
            dismiss.addEventListener('click', function () {
                hint.hidden = true;
                hint.setAttribute('aria-hidden', 'true');
                try {
                    localStorage.setItem(IOS_HINT_STORAGE_KEY, '1');
                } catch (e) {
                    // abaikan jika penyimpanan tidak tersedia
                }
            });
        }
    }

    registerServiceWorker();
    setupInstallPrompt();
    setupIosHint();
})();
