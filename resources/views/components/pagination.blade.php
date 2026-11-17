{{--
    Pagination bergaya tema dashboard: "Halaman [pilih halaman ▾] dari N".
    Pemakaian: <x-pagination :paginator="$alerts" />
    Query string (filter, dll.) ikut terbawa karena paginator memakai withQueryString().
    Tidak tampil bila hanya ada satu halaman.
--}}
@props(['paginator'])

@if ($paginator->lastPage() > 1)
    <nav aria-label="Pagination" class="flex items-center gap-3 text-sm text-muted">
        <span>Halaman</span>

        <div class="relative">
            <select aria-label="Pilih halaman"
                    onchange="window.location.href = this.value"
                    class="appearance-none rounded-2xl border border-line bg-white py-2 pr-9 pl-4 text-sm font-medium text-ink tabular-nums outline-none transition-colors hover:border-primary/40 focus:border-primary focus:ring-2 focus:ring-primary/15">
                @for ($page = 1; $page <= $paginator->lastPage(); $page++)
                    <option value="{{ $paginator->url($page) }}" @selected($page === $paginator->currentPage())>{{ $page }}</option>
                @endfor
            </select>
            <svg class="pointer-events-none absolute top-1/2 right-3 size-4 -translate-y-1/2 text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 9l6 6 6-6"></path>
            </svg>
        </div>

        <span>dari <span class="font-medium text-ink tabular-nums">{{ $paginator->lastPage() }}</span></span>
    </nav>
@endif
