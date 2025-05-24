@if ($paginator->hasPages())
    <div class="card card-default mt-4 p-2">
        <div class="row row-cols-2 align-items-center">
            <div class="col">
                <h6 class="mb-0 px-2">
                    {!! __('Showing') !!}
                    @if ($paginator->firstItem())
                        <span class="font-medium">{{ $paginator->firstItem() }}</span>
                        {!! __('to') !!}
                        <span class="font-medium">{{ $paginator->lastItem() }}</span>
                    @else
                        {{ $paginator->count() }}
                    @endif
                    {!! __('of') !!}
                    <span class="font-medium">{{ $paginator->total() }}</span>
                    {!! __('results') !!}
                </h6>
            </div>
            <div class="col">
                <ul class="pagination px-4 m-0" style="justify-content: end">
                    {{-- Previous Page Link --}}
                    @if ($paginator->onFirstPage())
                        <li class="page-item"><span class="page-link">Previous</span></li>
                    @else
                        <li class="page-item"><a class="page-link" href="{{ $paginator->previousPageUrl() }}"
                                rel="prev">Previous</a>
                        </li>
                    @endif
                    {{-- Pagination Links --}}
                    @foreach ($elements as $element)
                        @if (is_string($element))
                            <li class="page-item"><span class="page-link">{{ $element }}</span></li>
                        @endif
                        @if (is_array($element))
                            @foreach ($element as $page => $url)
                                @if ($page == $paginator->currentPage())
                                    <li class="page-item active"><span
                                            class="page-link active">{{ $page }}</span></li>
                                @else
                                    <li class="page-item"><a class="page-link"
                                            href="{{ $url }}">{{ $page }}</a></li>
                                @endif
                            @endforeach
                        @endif
                    @endforeach
                    {{-- Next Page Link --}}
                    @if ($paginator->hasMorePages())
                        <li class="page-item"><a class="page-link" href="{{ $paginator->nextPageUrl() }}"
                                rel="next">Next</a></li>
                    @else
                        <li class="page-item"><span class="page-link">Next</span></li>
                    @endif
                </ul>
            </div>
        </div>
    </div>
@endif
