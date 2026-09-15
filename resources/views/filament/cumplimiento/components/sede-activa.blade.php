@php($sede = app(\App\Services\SedeContext::class)->sedeActiva())

@if ($sede)
    <a
        href="{{ \App\Filament\Cumplimiento\Pages\SelectSede::getUrl() }}"
        class="cumplimiento-sede-pill"
        title="Cambiar sede de trabajo"
    >
        <span class="cumplimiento-sede-fecha">{{ now()->format('d/m/Y') }}</span>
        <span class="cumplimiento-sede-divider"></span>
        <span class="cumplimiento-sede-nombre">{{ $sede->Nombre }}</span>
        <x-heroicon-o-arrow-path class="cumplimiento-sede-icon" />
    </a>

    <style>
        .cumplimiento-sede-pill {
            display: flex;
            min-width: 250px;
            height: 46px;
            align-items: center;
            margin-right: 1rem;
            overflow: hidden;
            border: 1px solid #d1d5db;
            border-radius: 9999px;
            background: #fff;
            box-shadow: 0 2px 6px rgba(0, 0, 0, .08);
            text-decoration: none;
            transition: border-color .2s, box-shadow .2s;
        }
        .cumplimiento-sede-pill:hover { border-color: #9fcb36; box-shadow: 0 3px 9px rgba(0, 0, 0, .11); }
        .cumplimiento-sede-fecha { padding: 0 1.25rem; color: #111827; font-size: .95rem; font-weight: 700; white-space: nowrap; }
        .cumplimiento-sede-divider { width: 1px; height: 26px; flex: none; background: #d1d5db; }
        .cumplimiento-sede-nombre { max-width: 130px; flex: 1; overflow: hidden; padding: 0 .5rem 0 1.25rem; color: #8fbd25; font-size: .95rem; font-weight: 700; text-overflow: ellipsis; white-space: nowrap; }
        .cumplimiento-sede-icon { width: 20px; height: 20px; flex: none; margin-right: 1rem; color: #9ca3af; }
        .dark .cumplimiento-sede-pill { border-color: #374151; background: #111827; }
        .dark .cumplimiento-sede-fecha { color: #fff; }
        .dark .cumplimiento-sede-divider { background: #4b5563; }
        @media (max-width: 639px) { .cumplimiento-sede-pill { display: none; } }
    </style>
@endif
