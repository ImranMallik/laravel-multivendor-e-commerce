@props(['paginator'])

@if ($paginator->hasPages())
    <div class="row">
        <div class="col-12">
            <section id="pagination">
                <nav aria-label="Page navigation">
                    <ul class="pagination">
                        <li class="page-item {{ $paginator->onFirstPage() ? 'disabled' : '' }}">
                            <a class="page-link" href="{{ $paginator->previousPageUrl() ?? '#' }}" aria-label="Previous">
                                <i class="fas fa-chevron-left"></i>
                            </a>
                        </li>
                        @foreach ($paginator->getUrlRange(1, $paginator->lastPage()) as $page => $url)
                            <li class="page-item">
                                <a class="page-link {{ $page === $paginator->currentPage() ? 'page_active' : '' }}" href="{{ $url }}">{{ $page }}</a>
                            </li>
                        @endforeach
                        <li class="page-item {{ $paginator->hasMorePages() ? '' : 'disabled' }}">
                            <a class="page-link" href="{{ $paginator->nextPageUrl() ?? '#' }}" aria-label="Next">
                                <i class="fas fa-chevron-right"></i>
                            </a>
                        </li>
                    </ul>
                </nav>
            </section>
        </div>
    </div>
@endif
