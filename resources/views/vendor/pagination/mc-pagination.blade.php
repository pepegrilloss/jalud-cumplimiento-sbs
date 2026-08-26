<div class="mc-pg-wrap">
    @if ($paginator->hasPages())
        <nav role="navigation">
            <div style="display:flex;align-items:center;gap:6px;justify-content:center;flex-wrap:wrap;">
                {{-- Previous --}}
                @if ($paginator->onFirstPage())
                    <span class="mc-pg-btn mc-pg-disabled" aria-disabled="true">
                        <x-heroicon-o-chevron-left style="width:16px;height:16px;" />
                    </span>
                @else
                    <a href="{{ $paginator->previousPageUrl() }}" class="mc-pg-btn" rel="prev">
                        <x-heroicon-o-chevron-left style="width:16px;height:16px;" />
                    </a>
                @endif

                {{-- Pages --}}
                @php
                    $window = 2;
                    $current = $paginator->currentPage();
                    $last = $paginator->lastPage();
                    $start = max(1, $current - $window);
                    $end = min($last, $current + $window);
                @endphp

                @if ($start > 1)
                    <a href="{{ $paginator->url(1) }}" class="mc-pg-btn">1</a>
                    @if ($start > 2)<span class="mc-pg-dots">...</span>@endif
                @endif

                @for ($i = $start; $i <= $end; $i++)
                    @if ($i == $current)
                        <span class="mc-pg-btn mc-pg-active">{{ $i }}</span>
                    @else
                        <a href="{{ $paginator->url($i) }}" class="mc-pg-btn">{{ $i }}</a>
                    @endif
                @endfor

                @if ($end < $last)
                    @if ($end < $last - 1)<span class="mc-pg-dots">...</span>@endif
                    <a href="{{ $paginator->url($last) }}" class="mc-pg-btn">{{ $last }}</a>
                @endif

                {{-- Next --}}
                @if ($paginator->hasMorePages())
                    <a href="{{ $paginator->nextPageUrl() }}" class="mc-pg-btn" rel="next">
                        <x-heroicon-o-chevron-right style="width:16px;height:16px;" />
                    </a>
                @else
                    <span class="mc-pg-btn mc-pg-disabled" aria-disabled="true">
                        <x-heroicon-o-chevron-right style="width:16px;height:16px;" />
                    </span>
                @endif
            </div>
        </nav>
    @endif
</div>
