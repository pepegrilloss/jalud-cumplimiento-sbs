@php
use Illuminate\Support\Facades\DB;

$user = filament()->auth()->user();
$usuarioRegistrado = DB::table('MP_LGN_USUARIOS_REGISTRADOS')
    ->where('ID_MP_LGN_USUARIO', $user?->ID_MP_LGN_USUARIO)
    ->latest('FECHA')
    ->latest('HORA')
    ->first();

$personal = null;
if ($usuarioRegistrado) {
    $personal = DB::table('MP_LGN_PERSONAL')
        ->where('ID_MP_LGN_PERSONAL', $usuarioRegistrado->ID_MP_LGN_PERSONAL)
        ->first();
}

$fullName = $personal
    ? "{$personal->NOMBRES} {$personal->APELLIDO_PATERNO} {$personal->APELLIDO_MATERNO}"
    : $user?->name ?? __('visitas.user_fallback');

$initials = collect(explode(' ', $fullName))
    ->take(2)
    ->map(fn($word) => strtoupper(substr($word, 0, 1)))
    ->implode('');
@endphp

<style>
    @keyframes wave {
        0%, 60%, 100% { transform: rotate(0deg); }
        10% { transform: rotate(14deg); }
        20% { transform: rotate(-8deg); }
        30% { transform: rotate(14deg); }
        40% { transform: rotate(-4deg); }
        50% { transform: rotate(10deg); }
    }

    @keyframes fadeSlideIn {
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .welcome-widget {
        animation: fadeSlideIn 0.5s ease both;
    }

    .wave-hand {
        display: inline-block;
        animation: wave 2.5s ease-in-out infinite;
        transform-origin: 70% 70%;
    }

    .welcome-card {
        position: relative;
        overflow: hidden;
        border-radius: 0.75rem;
        background: #ffffff;
        border: 1px solid #f0f0f0;
        padding: 1.5rem 1.75rem;
        display: flex;
        align-items: center;
        gap: 1.25rem;
        box-shadow: 0 1px 4px rgba(0, 0, 0, 0.06);
        transition: box-shadow 0.2s ease;
    }

    .dark .welcome-card {
        background: #1e1e1e;
        border-color: #2a2a2a;
        box-shadow: 0 1px 4px rgba(0, 0, 0, 0.3);
    }

    .welcome-card:hover {
        box-shadow: 0 4px 16px rgba(0, 0, 0, 0.1);
    }

    .welcome-card::before {
        content: '';
        position: absolute;
        left: 0;
        top: 0;
        bottom: 0;
        width: 4px;
        background: #EDD484;
        border-radius: 4px 0 0 4px;
    }

    .avatar-circle {
        width: 56px;
        height: 56px;
        border-radius: 50%;
        background: linear-gradient(135deg, #EDD484 0%, #e0a800 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.1rem;
        font-weight: 700;
        color: #1a1a1a;
        flex-shrink: 0;
        letter-spacing: 0.5px;
        box-shadow: 0 2px 8px rgba(245, 184, 0, 0.35);
    }

    .avatar-circle img {
        width: 100%;
        height: 100%;
        border-radius: 50%;
        object-fit: cover;
    }

    .user-info {
        display: flex;
        flex-direction: column;
        gap: 0.15rem;
        min-width: 0;
    }

    .user-username {
        font-size: 1rem;
        font-weight: 700;
        color: #111827;
        letter-spacing: 0.3px;
        line-height: 1.3;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .dark .user-username {
        color: #f3f4f6;
    }

    .user-fullname {
        font-size: 0.8rem;
        color: #6b7280;
        font-weight: 400;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        text-transform: uppercase;
        letter-spacing: 0.6px;
    }

    .dark .user-fullname {
        color: #9ca3af;
    }

    .session-badge {
        margin-left: auto;
        display: flex;
        align-items: center;
        gap: 0.4rem;
        background: #fefce8;
        border: 1px solid #fde68a;
        color: #92400e;
        font-size: 0.7rem;
        font-weight: 600;
        padding: 0.3rem 0.65rem;
        border-radius: 999px;
        white-space: nowrap;
        letter-spacing: 0.4px;
    }

    .dark .session-badge {
        background: rgba(245, 184, 0, 0.12);
        border-color: rgba(245, 184, 0, 0.3);
        color: #EDD484;
    }

    .session-dot {
        width: 6px;
        height: 6px;
        border-radius: 50%;
        background: #EDD484;
        animation: pulse-dot 2s infinite;
    }

    @keyframes pulse-dot {
        0%, 100% { opacity: 1; transform: scale(1); }
        50% { opacity: 0.6; transform: scale(0.85); }
    }

    .welcome-heading {
        font-size: 0.8rem;
        font-weight: 600;
        color: #9ca3af;
        text-transform: uppercase;
        letter-spacing: 0.8px;
        margin-bottom: 0.75rem;
        display: flex;
        align-items: center;
        gap: 0.4rem;
    }
</style>

<x-filament-widgets::widget class="welcome-widget">
    <div class="welcome-heading">
        <span class="wave-hand">👋</span> {{ __('visitas.welcome') }}
    </div>

    <div class="welcome-card">
        <div class="avatar-circle">
            @if ($user?->avatar_url)
                <img src="{{ $user->avatar_url }}" alt="{{ $user->name }}" style="width: 100%; height: 100%; border-radius: 50%; object-fit: cover;" />
            @else
                {{ $initials ?: '?' }}
            @endif
        </div>

        <div class="user-info">
            <span class="user-username">{{ $user?->name ?? 'Usuario' }}</span>
            <span class="user-fullname">{{ $fullName }}</span>
        </div>

        <div class="session-badge">
            <span class="session-dot"></span>
            {{ __('visitas.active_session') }}
        </div>
    </div>
</x-filament-widgets::widget>
