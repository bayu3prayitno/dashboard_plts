@props([
    // Batas tertinggi titik puncak (px dari tepi atas hero). Kecil = puncak lebih tinggi.
    'peakClear' => 38,
])
{{--
    Langit hero: matahari terbit dari kiri & terbenam ke kanan mengikuti jam; malam hari bulan + bintang.
    Pemakaian: letakkan <x-hero-sky /> di dalam elemen hero yang memiliki atribut data-hero dan class
    hero-surface + relative + overflow-hidden, dengan gambar rumah ber-atribut data-hero-img.
    Opsional: data-hero-title / data-hero-subtitle pada teks agar berubah putih saat malam.
    Jam mengikuti zona waktu Pengaturan. Pratinjau: ?jam=05:45 di URL.
--}}
<div data-hero-sky data-peak-clear="{{ (int) $peakClear }}" class="hero-sky" aria-hidden="true">
    <div data-tint="day" class="hero-sky__tint hero-sky__tint--day"></div>
    <div data-tint="night" class="hero-sky__tint hero-sky__tint--night"></div>
    <div data-tint="warm" class="hero-sky__tint hero-sky__tint--warm"></div>
    <div data-stars></div>
    <div data-body="moon" class="hero-sky__body hero-moon"></div>
    <div data-body="sun" class="hero-sky__body hero-sun"></div>
</div>

@once
@if (app()->isLocal())
    {{-- DEV ONLY: panel simulasi jam langit (tidak dirender di production). Hapus class "hidden" untuk menampilkan. --}}
    <div data-sky-sim class="fixed bottom-4 left-4 z-[55] w-72 rounded-2xl border border-line bg-white/95 p-3 text-xs text-ink shadow-xl backdrop-blur hidden">
        <div class="flex items-center justify-between gap-2">
            <button type="button" data-sim-toggle class="flex items-center gap-2 font-semibold">
                <x-icon name="clock" :size="14" /> Simulasi jam
            </button>
            <span class="flex items-center gap-2">
                <span data-sim-time class="text-sm font-semibold tabular-nums">--:--</span>
                <span data-sim-mode></span>
            </span>
        </div>
        <div data-sim-body class="mt-3 space-y-3">
            <input type="range" min="0" max="1439" step="5" aria-label="Jam simulasi" class="w-full accent-lime-dark">
            <div class="grid grid-cols-4 gap-1">
                @foreach (['05:50' => 'Fajar', '12:00' => 'Siang', '17:50' => 'Senja', '21:00' => 'Malam'] as $jam => $label)
                    @php([$h, $i] = explode(':', $jam))
                    <button type="button" data-sim-preset="{{ $h * 60 + $i }}" class="rounded-lg bg-canvas px-1 py-1.5 font-medium hover:bg-primary-soft">{{ $label }}</button>
                @endforeach
            </div>
            <div class="grid grid-cols-2 gap-1">
                <button type="button" data-sim-play class="rounded-lg bg-primary px-2 py-1.5 font-medium text-white hover:bg-primary-dark">▶ Time-lapse</button>
                <button type="button" data-sim-real class="rounded-lg border border-line px-2 py-1.5 font-medium hover:bg-canvas">Jam asli</button>
            </div>
            <p class="text-[10px] text-muted">Hanya tampil di APP_ENV=local. Juga bisa lewat URL <code>?jam=05:45</code>.</p>
        </div>
    </div>
@endif

<script>
(function () {
    const init = () => {
        const sky = document.querySelector('[data-hero-sky]');
        const hero = sky?.closest('[data-hero]');
        const img = hero?.querySelector('[data-hero-img]');
        if (!sky || !hero || !img) return;

        const TZ = @js(\App\Http\Controllers\DashboardController::tz());
        const SUNRISE = 6 * 60, SUNSET = 18 * 60;   // menit sejak 00:00
        const TWILIGHT = 40;                         // menit transisi fajar/senja
        const sun = sky.querySelector('[data-body="sun"]');
        const moon = sky.querySelector('[data-body="moon"]');
        const warm = sky.querySelector('[data-tint="warm"]');
        const night = sky.querySelector('[data-tint="night"]');
        const day = sky.querySelector('[data-tint="day"]');

        // Bintang: posisi tetap (pseudo-acak dengan seed) agar tidak "melompat" tiap muat ulang
        const starBox = sky.querySelector('[data-stars]');
        let seed = 7;
        const rand = () => ((seed = (seed * 9301 + 49297) % 233280) / 233280);
        for (let i = 0; i < 40; i++) {
            const s = document.createElement('span');
            s.className = 'hero-star';
            s.style.left = (rand() * 100).toFixed(2) + '%';
            s.style.top = (rand() * 62).toFixed(2) + '%';
            s.style.animationDelay = (rand() * 3).toFixed(2) + 's';
            if (rand() > 0.8) s.style.width = s.style.height = '3px';
            starBox.appendChild(s);
        }
        const stars = starBox.children;

        // Jam simulasi (null = jam asli). Diatur lewat ?jam=HH:MM atau panel simulasi (khusus local).
        const override = new URLSearchParams(location.search).get('jam')?.match(/^(\d{1,2}):(\d{2})$/);
        let simMinutes = override ? (+override[1] % 24) * 60 + +override[2] : null;
        const minutesNow = () => {
            if (simMinutes !== null) return simMinutes;
            const parts = new Intl.DateTimeFormat('en-GB', { timeZone: TZ, hour: '2-digit', minute: '2-digit', hourCycle: 'h23' })
                .formatToParts(new Date());
            const get = (t) => +parts.find((p) => p.type === t).value;
            return get('hour') * 60 + get('minute');
        };

        // Lintasan busur di sekitar gambar rumah: terbit dari balik pohon kiri, melengkung di atas atap,
        // terbenam di balik pohon kanan. Dihitung dari kotak gambar agar pas di semua ukuran layar.
        // plts-3D.png: pohon kiri x≈0.08–0.20, pohon kanan x≈0.80–0.92, pucuk pohon tertinggi y≈0.19
        const ARC = { left: 0.11, right: 0.89, horizon: 0.45, peak: 0.06 };
        const PEAK_CLEAR = +sky.dataset.peakClear || 0;
        const place = (el, t) => {
            const h = hero.getBoundingClientRect();
            const i = img.getBoundingClientRect();
            const ix = i.left - h.left, iy = i.top - h.top;
            const x0 = ix + i.width * ARC.left, x1 = ix + i.width * ARC.right;
            const yHorizon = iy + i.height * ARC.horizon;
            const yPeak = Math.min(yHorizon - 40, Math.max(PEAK_CLEAR, iy + i.height * ARC.peak));
            const alt = Math.sin(Math.PI * t);
            const x = x0 + (x1 - x0) * t;
            const y = yHorizon - (yHorizon - yPeak) * alt;
            el.style.left = x + 'px';
            el.style.top = y + 'px';
            // Dalam % agar bisa dipakai gradien cahaya
            return { x: (x / h.width) * 100, y: (y / h.height) * 100, alt };
        };
        const clamp = (v) => Math.min(1, Math.max(0, v));

        const update = () => {
            const m = minutesNow();
            const isDay = m >= SUNRISE && m < SUNSET;
            let moonPos = null;

            if (isDay) {
                const { x, y, alt } = place(sun, (m - SUNRISE) / (SUNSET - SUNRISE));
                // Cahaya hangat siang mengikuti matahari
                day.style.setProperty('--sun-x', x + '%');
                day.style.setProperty('--sun-y', y + '%');
                // Dekat cakrawala: matahari lebih jingga
                sun.style.filter = `hue-rotate(${-18 * (1 - alt)}deg) saturate(${1 + 0.5 * (1 - alt)})`;
                sun.style.opacity = 1;
                moon.style.opacity = 0;
                warm.style.setProperty('--glow-x', x + '%');
            } else {
                const nightLen = 24 * 60 - (SUNSET - SUNRISE);
                moonPos = place(moon, ((m - SUNSET + 24 * 60) % (24 * 60)) / nightLen);
                sun.style.opacity = 0;
                moon.style.opacity = 1;
            }

            // Jarak (menit) ke terbit/terbenam terdekat → intensitas fajar/senja
            const edge = Math.min(Math.abs(m - SUNRISE), Math.abs(m - SUNSET));
            const twilight = clamp(1 - edge / TWILIGHT);
            warm.style.opacity = isDay ? twilight : twilight * 0.6;
            if (!isDay) warm.style.setProperty('--glow-x', moonPos.x + '%'); // sisa senja / fajar di sisi bulan
            const darkness = isDay ? 0 : clamp(0.35 + (1 - twilight) * 0.65);
            night.style.opacity = darkness;
            // Cahaya siang penuh di tengah hari, memudar saat fajar/senja agar jingga tetap terlihat
            day.style.opacity = isDay ? 1 - twilight * 0.7 : 0;
            for (const s of stars) s.style.opacity = isDay ? 0 : darkness;

            const phase = isDay ? 'day' : 'night';
            sky.dataset.phase = phase;
            hero.dataset.phase = phase;
            document.documentElement.dataset.heroPhase = phase; // untuk teks header yang menumpang di hero
        };

        // Posisi dihitung tanpa transisi (tidak meluncur dari tengah saat dibuka / di-resize)
        const relayout = () => {
            sky.classList.add('no-anim');
            update();
            requestAnimationFrame(() => requestAnimationFrame(() => sky.classList.remove('no-anim')));
        };
        relayout();
        setInterval(update, 60 * 1000);
        if (!img.complete) img.addEventListener('load', relayout, { once: true });
        new ResizeObserver(relayout).observe(hero);

        // Hemat GPU: kelip bintang berhenti saat hero tidak terlihat
        new IntersectionObserver(([entry]) => sky.classList.toggle('is-paused', !entry.isIntersecting)).observe(hero);

        // Dipakai panel simulasi jam
        window.heroSky = {
            set(minutes) { simMinutes = minutes === null ? null : ((Math.round(minutes) % 1440) + 1440) % 1440; update(); },
            get: () => minutesNow(),
            isSimulated: () => simMinutes !== null,
        };

        @if (app()->isLocal())
        // ===== Panel simulasi jam (DEV ONLY) =====
        const panel = document.querySelector('[data-sky-sim]');
        if (!panel) return;
        const range = panel.querySelector('input[type="range"]');
        const label = panel.querySelector('[data-sim-time]');
        const mode = panel.querySelector('[data-sim-mode]');
        const playBtn = panel.querySelector('[data-sim-play]');
        const body = panel.querySelector('[data-sim-body]');
        const fmt = (m) => String(Math.floor(m / 60)).padStart(2, '0') + ':' + String(m % 60).padStart(2, '0');
        let timer = null;

        const render = () => {
            const m = heroSky.get();
            range.value = m;
            label.textContent = fmt(m);
            mode.textContent = heroSky.isSimulated() ? 'simulasi' : 'jam asli';
            mode.className = 'rounded-full px-2 py-0.5 text-[10px] font-semibold ' +
                (heroSky.isSimulated() ? 'bg-amber-100 text-amber-700' : 'bg-primary-soft text-lime-dark');
            playBtn.textContent = timer ? '⏸ Jeda' : '▶ Time-lapse';
        };
        const stop = () => { clearInterval(timer); timer = null; };
        const setTime = (m) => { heroSky.set(m); render(); };

        range.addEventListener('input', () => { stop(); setTime(+range.value); });
        panel.querySelectorAll('[data-sim-preset]').forEach((b) =>
            b.addEventListener('click', () => { stop(); setTime(+b.dataset.simPreset); }));
        panel.querySelector('[data-sim-real]').addEventListener('click', () => { stop(); setTime(null); });
        // Time-lapse: +10 menit tiap 150 ms ≈ 1 hari dalam ~22 detik
        playBtn.addEventListener('click', () => {
            if (timer) { stop(); return render(); }
            timer = setInterval(() => setTime(heroSky.get() + 10), 150);
            render();
        });
        panel.querySelector('[data-sim-toggle]').addEventListener('click', () => body.classList.toggle('hidden'));
        render();
        setInterval(() => { if (!timer) render(); }, 30 * 1000); // ikuti jam asli saat tidak disimulasikan
        @endif
    };

    // Komponen dirender sebelum gambar & teks hero: tunggu DOM lengkap
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
    else init();
})();
</script>
@endonce
