/**
 * Subtle parallax on landing hero background (pointer-driven, desktop-first).
 */
(function () {
    var root = document.getElementById('hawassaLanding');
    if (!root) return;

    var bg = root.querySelector('[data-parallax-bg]');
    if (!bg) return;

    var maxMove = 14;
    var scale = 1.08;

    function onMove(clientX, clientY) {
        if (window.innerWidth < 768) {
            bg.style.transform = 'scale(' + scale + ')';
            return;
        }
        var x = (clientX / window.innerWidth - 0.5) * 2 * maxMove;
        var y = (clientY / window.innerHeight - 0.5) * 2 * maxMove;
        bg.style.transform = 'scale(' + scale + ') translate(' + x + 'px,' + y + 'px)';
    }

    window.addEventListener('mousemove', function (e) {
        onMove(e.clientX, e.clientY);
    });

    window.addEventListener('resize', function () {
        onMove(window.innerWidth / 2, window.innerHeight / 2);
    });

    onMove(window.innerWidth / 2, window.innerHeight / 2);
})();
