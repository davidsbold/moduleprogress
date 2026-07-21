define([], function() {
    return {
        init: function(canvasId, percent) {
            const canvas = document.getElementById(canvasId);

            if (!canvas) {
                return;
            }

            const ctx = canvas.getContext('2d');

            const centerX = canvas.width / 2;
            const centerY = 170;
            const radius = 135;

            const navy = '#06133D';
            const yellow = '#FFF04A';
            const grey = '#D9DDE3';
            const tick = '#9AA3AF';

            ctx.clearRect(0, 0, canvas.width, canvas.height);

            function arc(start, end, color, lineWidth) {
                const a1 = Math.PI + Math.PI * (start / 100);
                const a2 = Math.PI + Math.PI * (end / 100);

                ctx.beginPath();
                ctx.arc(centerX, centerY, radius, a1, a2);
                ctx.lineWidth = lineWidth;
                ctx.strokeStyle = color;
                ctx.lineCap = 'butt';
                ctx.stroke();
            }

            arc(0, 70, grey, 20);
            arc(70, 80, yellow, 20);
            arc(80, 90, navy, 20);
            arc(90, 100, navy, 20);

            arc(69.5, 70.5, '#FFFFFF', 24);
            arc(79.5, 80.5, '#FFFFFF', 24);
            arc(89.5, 90.5, '#FFFFFF', 24);

            for (let i = 0; i <= 100; i += 10) {
                const angle = Math.PI + Math.PI * (i / 100);
                const inner = radius - 34;
                const outer = radius - 20;

                ctx.beginPath();
                ctx.moveTo(centerX + Math.cos(angle) * inner, centerY + Math.sin(angle) * inner);
                ctx.lineTo(centerX + Math.cos(angle) * outer, centerY + Math.sin(angle) * outer);
                ctx.lineWidth = 2;
                ctx.strokeStyle = tick;
                ctx.stroke();
            }

            const safe = Math.max(0, Math.min(100, percent));
            const angle = Math.PI + Math.PI * (safe / 100);
            const length = 105;

            ctx.beginPath();
            ctx.moveTo(centerX, centerY);
            ctx.lineTo(centerX + Math.cos(angle) * length, centerY + Math.sin(angle) * length);
            ctx.lineWidth = 6;
            ctx.strokeStyle = yellow;
            ctx.lineCap = 'round';
            ctx.stroke();

            ctx.beginPath();
            ctx.arc(centerX, centerY, 15, 0, Math.PI * 2);
            ctx.fillStyle = '#FFFFFF';
            ctx.fill();
            ctx.lineWidth = 5;
            ctx.strokeStyle = yellow;
            ctx.stroke();

            ctx.beginPath();
            ctx.arc(centerX, centerY, 5, 0, Math.PI * 2);
            ctx.fillStyle = yellow;
            ctx.fill();
        }
    };
});

define(['jquery'], function($) {
    return {
        initToggle: function() {
            $('#toggle-block-btn').on('click', function() {
                var $content = $('#moduleprogress-content-body');
                var $icon = $(this).find('.toggle-icon');
                var $text = $(this).find('.toggle-text');

                $content.slideToggle(300, function() {
                    if ($content.is(':visible')) {
                        $text.text('Modulfortschritt ausblenden');
                        $icon.removeClass('fa-chevron-down').addClass('fa-chevron-up');
                    } else {
                        $text.text('Modulfortschritt einblenden');
                        $icon.removeClass('fa-chevron-up').addClass('fa-chevron-down');
                    }
                });
            });
        }
    };
});
