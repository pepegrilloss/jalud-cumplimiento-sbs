<div class="sede-selection-container">
    <div class="selection-card-wrapper">
        <div class="selection-header">
            <h1 class="selection-title">Bienvenido, {{ auth()->user()->name }}</h1>
            <p class="selection-subtitle">Por favor, selecciona la sede con la que trabajarás hoy.</p>
        </div>

        <div class="selection-grid">
            @forelse ($this->getSedes() as $sede)
                <button type="button" wire:click="seleccionarSede({{ $sede->SedeID }})" wire:loading.attr="disabled" class="sede-card">
                    <span class="icon-circle">
                        <x-heroicon-o-building-office-2 class="sede-icon" />
                    </span>
                    <span class="sede-name">{{ $sede->Nombre }}</span>
                    <span class="sede-desc">Sede operativa</span>
                </button>
            @empty
                <div class="empty-state">Este usuario no tiene una sede operativa disponible.</div>
            @endforelse
        </div>
    </div>

    <style>
        .sede-selection-container { display: flex; align-items: center; justify-content: center; min-height: 100vh; padding: 1.5rem; background: #f3f4f6; }
        .dark .sede-selection-container { background: #111827; }
        .selection-card-wrapper { width: 100%; max-width: 64rem; }
        .selection-header { margin-bottom: 2.5rem; text-align: center; }
        .selection-title { color: #111827; font-size: 1.875rem; font-weight: 700; }
        .dark .selection-title { color: #fff; }
        .selection-subtitle { margin-top: 1rem; color: #4b5563; font-size: 1.125rem; }
        .dark .selection-subtitle { color: #9ca3af; }
        .selection-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem; }
        .sede-card { display: flex; min-height: 14rem; flex-direction: column; align-items: center; justify-content: center; padding: 2rem; border: 2px solid transparent; border-radius: 1rem; background: #fff; box-shadow: 0 1px 2px rgba(0, 0, 0, .05); cursor: pointer; transition: border-color .2s, box-shadow .2s, transform .2s; }
        .dark .sede-card { background: #1f2937; }
        .sede-card:hover { border-color: #9fcb36; box-shadow: 0 8px 18px rgba(0, 0, 0, .09); transform: translateY(-2px); }
        .sede-card:disabled { cursor: wait; opacity: .6; }
        .icon-circle { display: flex; width: 4rem; height: 4rem; align-items: center; justify-content: center; margin-bottom: 1rem; border-radius: 9999px; background: #f4f9e6; color: #9fcb36; transition: transform .2s; }
        .sede-card:hover .icon-circle { transform: scale(1.08); }
        .sede-icon { width: 2.5rem; height: 2.5rem; }
        .sede-name { color: #111827; font-size: 1.25rem; font-weight: 600; }
        .dark .sede-name { color: #fff; }
        .sede-desc { margin-top: .5rem; color: #6b7280; font-size: .875rem; }
        .empty-state { grid-column: 1 / -1; padding: 2rem; border-radius: .5rem; background: #fff; text-align: center; color: #92400e; }
        @media (max-width: 639px) { .sede-selection-container { align-items: flex-start; padding-top: 5rem; } .selection-title { font-size: 1.5rem; } .selection-subtitle { font-size: 1rem; } .sede-card { min-height: 11rem; } }
    </style>
</div>
