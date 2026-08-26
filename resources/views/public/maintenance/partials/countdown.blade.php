@if ($maintenance['show_countdown'] && $maintenance['end_at'])
    <div class="maintenance-countdown" data-maintenance-countdown="{{ $maintenance['end_at']->timestamp }}">
        <span data-days>00</span>
        <span data-hours>00</span>
        <span data-minutes>00</span>
        <span data-seconds>00</span>
    </div>
    <script>
        (() => {
            const countdown = document.querySelector('[data-maintenance-countdown]');
            if (!countdown) return;
            const end = Number(countdown.dataset.maintenanceCountdown) * 1000;
            const update = () => {
                const remaining = Math.max(0, end - Date.now());
                const total = Math.floor(remaining / 1000);
                countdown.querySelector('[data-days]').textContent = String(Math.floor(total / 86400)).padStart(2, '0');
                countdown.querySelector('[data-hours]').textContent = String(Math.floor((total % 86400) / 3600)).padStart(2, '0');
                countdown.querySelector('[data-minutes]').textContent = String(Math.floor((total % 3600) / 60)).padStart(2, '0');
                countdown.querySelector('[data-seconds]').textContent = String(total % 60).padStart(2, '0');
                if (remaining === 0) window.setTimeout(() => window.location.reload(), 30000);
            };
            update();
            window.setInterval(update, 1000);
        })();
    </script>
@endif
