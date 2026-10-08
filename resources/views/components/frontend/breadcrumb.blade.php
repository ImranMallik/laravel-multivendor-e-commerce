@props(['title', 'items' => []])

<section id="wsus__breadcrumb">
    <div class="wsus_breadcrumb_overlay">
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <h4>{{ $title }}</h4>
                    <ul>
                        <li><a href="{{ route('home') }}">home</a></li>
                        @foreach ($items as $label => $url)
                            <li>
                                @if ($url)
                                    <a href="{{ $url }}">{{ $label }}</a>
                                @else
                                    <a href="#" onclick="return false;">{{ $label }}</a>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    </div>
</section>
