{{--
    Desktop "All Categories" list. Same markup and classes as the template:
      level 1  li > a (.wsus__droap_arrow only when it has a dropdown) with an icon
      level 2  ul.wsus_menu_cat_droapdown > li > a (+ angle icon only when it has children)
      level 3  ul.wsus__sub_category > li > a
    $categoryMenu comes from CategoryMenuComposer (cached, active levels only).
--}}
@foreach ($categoryMenu as $category)
    <li>
        <a @class(['wsus__droap_arrow' => count($category['subs'])]) href="{{ route('category.show', $category['slug']) }}"><i class="{{ $category['icon'] }}"></i> {{ $category['name'] }}</a>
        @if (count($category['subs']))
            <ul class="wsus_menu_cat_droapdown">
                @foreach ($category['subs'] as $sub)
                    <li>
                        <a href="{{ route('category.sub.show', [$category['slug'], $sub['slug']]) }}">{{ $sub['name'] }}@if (count($sub['children'])) <i class="fas fa-angle-right"></i>@endif</a>
                        @if (count($sub['children']))
                            <ul class="wsus__sub_category">
                                @foreach ($sub['children'] as $child)
                                    <li><a href="{{ route('category.child.show', [$category['slug'], $sub['slug'], $child['slug']]) }}">{{ $child['name'] }}</a></li>
                                @endforeach
                            </ul>
                        @endif
                    </li>
                @endforeach
            </ul>
        @endif
    </li>
@endforeach
