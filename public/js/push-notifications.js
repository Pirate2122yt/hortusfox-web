/**
 * Web Push subscribe/unsubscribe/test wiring for the Profile page.
 *
 * This is a standalone file served directly from /public (not bundled by
 * webpack), so it's plain vanilla JS using fetch() rather than the app's
 * axios-based window.vue.ajaxRequest helper.
 *
 * Expects window.pushVapidPublicKey to already be set (inline, by
 * profile.php) before this file runs, and is invoked by layout.php's
 * service worker registration via window.pushNotificationsInit(registration)
 * once the service worker is ready.
 */
(function () {
    'use strict';

    let swRegistration = null;

    function urlBase64ToUint8Array(base64String) {
        const padding = '='.repeat((4 - (base64String.length % 4)) % 4);
        const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
        const rawData = window.atob(base64);
        const outputArray = new Uint8Array(rawData.length);

        for (let i = 0; i < rawData.length; ++i) {
            outputArray[i] = rawData.charCodeAt(i);
        }

        return outputArray;
    }

    function setStatus(text) {
        const el = document.getElementById('push-status');
        if (el) {
            el.innerText = text;
        }
    }

    function setButtonsVisible(subscribed) {
        const subscribeBtn = document.getElementById('push-subscribe-btn');
        const unsubscribeBtn = document.getElementById('push-unsubscribe-btn');
        const testBtn = document.getElementById('push-test-btn');

        if (subscribeBtn) {
            subscribeBtn.classList.toggle('is-hidden', subscribed);
        }

        if (unsubscribeBtn) {
            unsubscribeBtn.classList.toggle('is-hidden', !subscribed);
        }

        if (testBtn) {
            testBtn.classList.toggle('is-hidden', !subscribed);
        }
    }

    function refreshStatus() {
        if ((!swRegistration) || (!swRegistration.pushManager)) {
            setStatus(window.pushLang.unsupported);
            return;
        }

        swRegistration.pushManager.getSubscription().then(function (subscription) {
            if (subscription) {
                setStatus(window.pushLang.subscribed);
                setButtonsVisible(true);
            } else {
                setStatus(window.pushLang.notSubscribed);
                setButtonsVisible(false);
            }
        }).catch(function () {
            setStatus(window.pushLang.unsupported);
        });
    }

    function postJson(url, body) {
        return fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'same-origin',
            body: JSON.stringify(body || {})
        }).then(function (response) {
            return response.json();
        });
    }

    function subscribe() {
        if (!swRegistration) {
            return;
        }

        if (!window.pushVapidPublicKey) {
            alert(window.pushLang.notConfigured);
            return;
        }

        swRegistration.pushManager.subscribe({
            userVisibleOnly: true,
            applicationServerKey: urlBase64ToUint8Array(window.pushVapidPublicKey)
        }).then(function (subscription) {
            return postJson('/push/subscribe', subscription.toJSON());
        }).then(function (response) {
            if (response.code == 200) {
                refreshStatus();
            } else {
                alert(response.msg);
            }
        }).catch(function (err) {
            console.error(err);
            alert(window.pushLang.subscribeFailed);
        });
    }

    function unsubscribe() {
        if (!swRegistration) {
            return;
        }

        swRegistration.pushManager.getSubscription().then(function (subscription) {
            if (!subscription) {
                refreshStatus();
                return;
            }

            const endpoint = subscription.endpoint;

            subscription.unsubscribe().then(function () {
                postJson('/push/unsubscribe', { endpoint: endpoint }).then(function () {
                    refreshStatus();
                });
            });
        });
    }

    function sendTest(button) {
        const oldTxt = button.innerHTML;
        button.disabled = true;

        postJson('/push/test', {}).then(function (response) {
            button.disabled = false;
            button.innerHTML = oldTxt;

            if (response.code != 200) {
                alert(response.msg);
            }
        }).catch(function () {
            button.disabled = false;
            button.innerHTML = oldTxt;
        });
    }

    window.pushNotificationsInit = function (registration) {
        swRegistration = registration;

        const subscribeBtn = document.getElementById('push-subscribe-btn');
        const unsubscribeBtn = document.getElementById('push-unsubscribe-btn');
        const testBtn = document.getElementById('push-test-btn');

        if (subscribeBtn) {
            subscribeBtn.addEventListener('click', subscribe);
        }

        if (unsubscribeBtn) {
            unsubscribeBtn.addEventListener('click', unsubscribe);
        }

        if (testBtn) {
            testBtn.addEventListener('click', function () {
                sendTest(testBtn);
            });
        }

        refreshStatus();
    };
})();
