(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        const data = window.OW_ADMIN_DATA || {};
        if (data.loginError) {
            window.showToast('error', data.loginError);
        }
    });
})();