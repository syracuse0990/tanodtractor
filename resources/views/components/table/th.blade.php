@props([
    'sortable' => null,
    'direction' => null,
])
<th {{ $attributes->only('class') }}>
    @unless ($sortable)
        {{ $slot }}
    @else
        {{ $slot }}
        @if ($direction == 'asc')
            <i class="fa-solid fa-sort-down"></i>
        @elseif ($direction == 'desc')
            <i class="fa-solid fa-sort-up"></i>
        @else
            <i class="fa-solid fa-sort"></i>
        @endif
    @endunless
</th>
