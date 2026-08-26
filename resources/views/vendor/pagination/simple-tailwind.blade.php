@if ($paginator->hasPages())
    <nav role="navigation" aria-label="{{ __('Pagination Navigation') }}" class="flex gap-2 items-center justify-between">

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

    </nav>
@endif
