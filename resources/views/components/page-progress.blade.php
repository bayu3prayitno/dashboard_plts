{{--
    Garis loading di tepi atas halaman saat berpindah halaman (klik link / kirim form).
    Letakkan tepat setelah <body>. Lewati link tertentu dengan atribut data-no-progress
    (link unduhan, URL berisi /export, dan atribut download otomatis dilewati).
    JS: window.pageProgress.start() / .done() untuk proses lain (mis. fetch).
--}}
<div data-page-progress aria-hidden="true"
    class="pointer-events-none fixed inset-x-0 top-0 z-[100] h-[3px] opacity-0 transition-opacity duration-300">
    <div data-bar class="h-full w-0 bg-lime-dark shadow-[0_0_8px_rgb(168_217_58/0.8)]"></div>
</div>
<script>
(function () {
    const root = document.querySelector('[data-page-progress]');
    const bar = root.querySelector('[data-bar]');
    const KEY = 'pageProgress';
    let value = 0, trickle = null, safety = null;

    const set = (v, ms = 300) => {
        value = v;
        bar.style.transition = `width ${ms}ms ease-out`;
        bar.style.width = v + '%';
    };

    const start = () => {
        clearInterval(trickle); clearTimeout(safety);
        root.style.opacity = 1;
        set(0, 0);
        requestAnimationFrame(() => set(25));
        // Merayap pelan mendekati 90% selama menunggu server
        trickle = setInterval(() => { if (value < 90) set(value + (90 - value) * 0.08, 400); }, 400);
        // Pengaman: bila halaman tidak berganti (mis. unduhan), selesaikan sendiri
        safety = setTimeout(done, 15000);
        try { sessionStorage.setItem(KEY, String(Date.now())); } catch (e) {}
    };

    const done = () => {
        clearInterval(trickle); clearTimeout(safety);
        root.style.opacity = 1;
        set(100, 250);
        setTimeout(() => { root.style.opacity = 0; }, 300);
        setTimeout(() => set(0, 0), 650);
        try { sessionStorage.removeItem(KEY); } catch (e) {}
    };

    // Halaman baru setelah navigasi: lanjutkan dari posisi tinggi lalu selesaikan
    let pending = null;
    try { pending = sessionStorage.getItem(KEY); } catch (e) {}
    if (pending && Date.now() - +pending < 30000) {
        root.style.opacity = 1;
        set(85, 0);
        if (document.readyState === 'complete') done();
        else window.addEventListener('load', done, { once: true });
    }

    const skipLink = (a, e) => {
        if (e.defaultPrevented || e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return true;
        if ((a.target && a.target !== '_self') || a.hasAttribute('download') || a.hasAttribute('data-no-progress')) return true;
        const url = new URL(a.href, location.href);
        if (url.origin !== location.origin || !/^https?:$/.test(url.protocol)) return true;
        if (/\/export/i.test(url.pathname)) return true;
        // Hanya pindah #hash di halaman yang sama
        if (url.pathname === location.pathname && url.search === location.search && url.hash) return true;
        return false;
    };

    // Fase bubble: handler lain (mis. preventDefault) sudah jalan lebih dulu
    document.addEventListener('click', (e) => {
        const a = e.target.closest?.('a[href]');
        if (a && !skipLink(a, e)) start();
    });
    document.addEventListener('submit', (e) => {
        const form = e.target;
        if (e.defaultPrevented || form.target === '_blank' || form.hasAttribute('data-no-progress')) return;
        start();
    });

    // Kembali lewat tombol Back (bfcache): pastikan garis tidak tertinggal
    window.addEventListener('pageshow', (e) => { if (e.persisted) done(); });

    window.pageProgress = { start, done };
})();
</script>
