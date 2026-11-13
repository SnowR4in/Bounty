(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        var buttons = document.querySelectorAll('.faq-btn');

        buttons.forEach(function (btn) {
            btn.addEventListener('click', function () {
                var content = btn.nextElementSibling;
                var icon = btn.querySelector('i');
                var isExpanded = btn.getAttribute('aria-expanded') === 'true';

                document.querySelectorAll('.faq-content').forEach(function (el) {
                    el.classList.add('hidden');
                });
                document.querySelectorAll('.faq-btn').forEach(function (el) {
                    el.setAttribute('aria-expanded', 'false');
                    el.querySelector('i').style.transform = 'rotate(0deg)';
                    el.parentElement.classList.remove('ring-2', 'ring-primary/20', 'border-primary/30');
                });

                if (!isExpanded) {
                    content.classList.remove('hidden');
                    btn.setAttribute('aria-expanded', 'true');
                    icon.style.transform = 'rotate(180deg)';
                    btn.parentElement.classList.add('ring-2', 'ring-primary/20', 'border-primary/30');
                }
            });
        });
    });
})();