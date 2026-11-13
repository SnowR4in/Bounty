(function () {
    'use strict';

    window.showToast = function (type, message) {
        const container = document.getElementById('toast-container');
        if (!container) return;

        const toast = document.createElement('div');
        toast.className = 'toast toast-' + type;

        const icon = type === 'success'
            ? '<i class="fa-solid fa-circle-check text-2xl"></i>'
            : '<i class="fa-solid fa-circle-exclamation text-2xl"></i>';

        toast.innerHTML = icon +
            '<div class="flex-1 text-sm font-medium text-zinc-200">' + message + '</div>' +
            '<button class="text-zinc-500 hover:text-zinc-300 outline-none" data-toast-close><i class="fa-solid fa-xmark"></i></button>' +
            '<div class="toast-progress"></div>';

        toast.querySelector('[data-toast-close]').addEventListener('click', function () {
            toast.remove();
        });

        container.appendChild(toast);

        setTimeout(function () {
            if (toast.parentElement) {
                toast.classList.add('hiding');
                setTimeout(function () { toast.remove(); }, 300);
            }
        }, 3000);
    };

    function initFlashToasts() {
        const flash = document.getElementById('flash-messages');
        if (!flash) return;
        try {
            const messages = JSON.parse(flash.getAttribute('data-messages') || '[]');
            messages.forEach(function (item) {
                window.showToast(item.type, item.message);
            });
        } catch (e) { /* invalid payload — ignore */ }
    }

    function initSidebar() {
        const sidebarOpen = document.getElementById('sidebar-open');
        const sidebarClose = document.getElementById('sidebar-close');
        const sidebar = document.getElementById('admin-sidebar');
        const overlay = document.getElementById('mobile-overlay');

        if (!sidebar || !overlay) return;

        function openSidebar() {
            overlay.classList.remove('hidden');
            setTimeout(function () {
                overlay.classList.remove('opacity-0');
                sidebar.classList.remove('-translate-x-full');
            }, 10);
        }

        function closeSidebar() {
            sidebar.classList.add('-translate-x-full');
            overlay.classList.add('opacity-0');
            setTimeout(function () {
                overlay.classList.add('hidden');
            }, 300);
        }

        if (sidebarOpen) sidebarOpen.addEventListener('click', openSidebar);
        if (sidebarClose) sidebarClose.addEventListener('click', closeSidebar);
        overlay.addEventListener('click', closeSidebar);
    }

    document.addEventListener('DOMContentLoaded', function () {
        initFlashToasts();
        initSidebar();
    });
})();