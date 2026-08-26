@if ($paginator->hasPages())
    <nav role="navigation" aria-label="{{ __('Pagination Navigation') }}">

        <div class="flex gap-2 items-center justify-between sm:hidden">

            @if ($paginator->onFirstPage())
                <span class="inline-flex items-center px-4 py-2 text-sm font-medium text-netral-600 bg-white border border-netral-400 cursor-not-allowed leading-5 rounded-md dark:text-netral-400 dark:bg-arang-500 dark:border-netral-600">
                    {!! __('pagination.previous') !!}
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="inline-flex items-center px-4 py-2 text-sm font-medium text-arang-700 bg-white border border-netral-400 leading-5 rounded-md hover:text-arang-500 focus:outline-none focus:ring ring-netral-400 focus:border-jingga-400 active:bg-netral-100 active:text-arang-700 transition ease-in-out duration-150 dark:bg-arang-700 dark:border-netral-600 dark:text-netral-300 dark:focus:border-jingga-500 dark:active:bg-arang-500 dark:active:text-netral-400 hover:bg-netral-100 dark:hover:bg-arang-800 dark:hover:text-netral-300">
                    {!! __('pagination.previous') !!}
                </a>
            @endif

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="inline-flex items-center px-4 py-2 text-sm font-medium text-arang-700 bg-white border border-netral-400 leading-5 rounded-md hover:text-arang-500 focus:outline-none focus:ring ring-netral-400 focus:border-jingga-400 active:bg-netral-100 active:text-arang-700 transition ease-in-out duration-150 dark:bg-arang-700 dark:border-netral-600 dark:text-netral-300 dark:focus:border-jingga-500 dark:active:bg-arang-500 dark:active:text-netral-400 hover:bg-netral-100 dark:hover:bg-arang-800 dark:hover:text-netral-300">
                    {!! __('pagination.next') !!}
                </a>
            @else
                <span class="inline-flex items-center px-4 py-2 text-sm font-medium text-netral-600 bg-white border border-netral-400 cursor-not-allowed leading-5 rounded-md dark:text-netral-400 dark:bg-arang-500 dark:border-netral-600">
                    {!! __('pagination.next') !!}
                </span>
            @endif

        </div>

        <div class="hidden sm:flex-1 sm:flex sm:gap-2 sm:items-center sm:justify-between">

            <div>
                <p class="text-sm text-arang-500 leading-5 dark:text-netral-600">
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
                </p>
            </div>

            <div>
                <span class="inline-flex rtl:flex-row-reverse shadow-sm rounded-md">

                    {{-- Previous Page Link --}}
                    @if ($paginator->onFirstPage())
                        <span aria-disabled="true" aria-label="{{ __('pagination.previous') }}">
                            <span class="inline-flex items-center px-2 py-2 text-sm font-medium text-netral-1000 bg-white border border-netral-400 cursor-not-allowed rounded-l-md leading-5 dark:bg-arang-500 dark:border-netral-600 dark:text-netral-500" aria-hidden="true">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                                </svg>
                            </span>
                        </span>
                    @else
                        <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="inline-flex items-center px-2 py-2 text-sm font-medium text-netral-1000 bg-white border border-netral-400 rounded-l-md leading-5 hover:text-netral-500 focus:outline-none focus:ring ring-netral-400 focus:border-jingga-400 active:bg-netral-100 active:text-netral-1000 transition ease-in-out duration-150 dark:bg-arang-700 dark:border-netral-600 dark:active:bg-arang-500 dark:dark:focus:border-jingga-600 dark:text-netral-400 dark:hover:bg-arang-800 dark:hover:text-netral-400" aria-label="{{ __('pagination.previous') }}">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                            </svg>
                        </a>
                    @endif

                    {{-- Pagination Elements --}}
                    @foreach ($elements as $element)
                        {{-- "Three Dots" Separator --}}
                        @if (is_string($element))
                            <span aria-disabled="true">
                                <span class="inline-flex items-center px-4 py-2 -ml-px text-sm font-medium text-arang-500 bg-white border border-netral-400 cursor-default leading-5 dark:bg-arang-700 dark:border-netral-600 dark:text-netral-400">{{ $element }}</span>
                            </span>
                        @endif

                        {{-- Array Of Links --}}
                        @if (is_array($element))
                            @foreach ($element as $page => $url)
                                @if ($page == $paginator->currentPage())
                                    <span aria-current="page">
                                        <span class="inline-flex items-center px-4 py-2 -ml-px text-sm font-medium text-arang-500 bg-netral-300 border border-netral-400 cursor-default leading-5 dark:bg-arang-500 dark:border-netral-600 dark:text-netral-400">{{ $page }}</span>
                                    </span>
                                @else
                                    <a href="{{ $url }}" class="inline-flex items-center px-4 py-2 -ml-px text-sm font-medium text-arang-500 bg-white border border-netral-400 leading-5 hover:text-arang-500 focus:outline-none focus:ring ring-netral-400 focus:border-jingga-400 active:bg-netral-100 active:text-arang-500 transition ease-in-out duration-150 dark:bg-arang-700 dark:border-netral-600 dark:text-netral-400 dark:hover:text-netral-400 dark:active:bg-arang-500 dark:dark:focus:border-jingga-600 hover:bg-netral-100 dark:hover:bg-arang-800" aria-label="{{ __('Go to page :page', ['page' => $page]) }}">
                                        {{ $page }}
                                    </a>
                                @endif
                            @endforeach
                        @endif
                    @endforeach

                    {{-- Next Page Link --}}
                    @if ($paginator->hasMorePages())
                        <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="inline-flex items-center px-2 py-2 -ml-px text-sm font-medium text-netral-1000 bg-white border border-netral-400 rounded-r-md leading-5 hover:text-netral-500 focus:outline-none focus:ring ring-netral-400 focus:border-jingga-400 active:bg-netral-100 active:text-netral-1000 transition ease-in-out duration-150 dark:bg-arang-700 dark:border-netral-600 dark:active:bg-arang-500 dark:dark:focus:border-jingga-600 dark:text-netral-400 dark:hover:bg-arang-800 dark:hover:text-netral-400" aria-label="{{ __('pagination.next') }}">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                            </svg>
                        </a>
                    @else
                        <span aria-disabled="true" aria-label="{{ __('pagination.next') }}">
                            <span class="inline-flex items-center px-2 py-2 -ml-px text-sm font-medium text-netral-1000 bg-white border border-netral-400 cursor-not-allowed rounded-r-md leading-5 dark:bg-arang-500 dark:border-netral-600 dark:text-netral-500" aria-hidden="true">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                                </svg>
                            </span>
                        </span>
                    @endif
                </span>
            </div>
        </div>
    </nav>
@endif
