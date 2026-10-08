{{--
    Mobile category accordion (first tab of the off-canvas menu). Same markup and classes as the template.
    The template's mobile menu has two levels; child categories (level 3) use the same accordion pattern
    one level deeper. A category / sub category that has children also gets an "All ..." link first,
    because tapping its row only opens or closes it.
--}}
@foreach ($categoryMenu as $category)
    @if (count($category['subs']))
        <li>
            <a href="#" class="accordion-button collapsed" data-bs-toggle="collapse"
               data-bs-target="#mobile-cat-{{ $category['id'] }}" aria-expanded="false"
               aria-controls="mobile-cat-{{ $category['id'] }}"><i class="{{ $category['icon'] }}"></i> {{ $category['name'] }}</a>
            <div id="mobile-cat-{{ $category['id'] }}" class="accordion-collapse collapse"
                 data-bs-parent="#accordionFlushExample">
                <div class="accordion-body">
                    <ul>
                        <li><a href="{{ route('category.show', $category['slug']) }}">All {{ $category['name'] }}</a></li>
                        @foreach ($category['subs'] as $sub)
                            @if (count($sub['children']))
                                <li>
                                    <a href="#" class="accordion-button collapsed" data-bs-toggle="collapse"
                                       data-bs-target="#mobile-sub-{{ $sub['id'] }}" aria-expanded="false"
                                       aria-controls="mobile-sub-{{ $sub['id'] }}">{{ $sub['name'] }}</a>
                                    <div id="mobile-sub-{{ $sub['id'] }}" class="accordion-collapse collapse">
                                        <div class="accordion-body">
                                            <ul>
                                                <li><a href="{{ route('category.sub.show', [$category['slug'], $sub['slug']]) }}">All {{ $sub['name'] }}</a></li>
                                                @foreach ($sub['children'] as $child)
                                                    <li><a href="{{ route('category.child.show', [$category['slug'], $sub['slug'], $child['slug']]) }}">{{ $child['name'] }}</a></li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    </div>
                                </li>
                            @else
                                <li><a href="{{ route('category.sub.show', [$category['slug'], $sub['slug']]) }}">{{ $sub['name'] }}</a></li>
                            @endif
                        @endforeach
                    </ul>
                </div>
            </div>
        </li>
    @else
        <li><a href="{{ route('category.show', $category['slug']) }}"><i class="{{ $category['icon'] }}"></i> {{ $category['name'] }}</a></li>
    @endif
@endforeach
