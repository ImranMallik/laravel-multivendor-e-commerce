<ul class="nav nav-pills nav-fill d-flex mb-3" role="tablist" aria-label="Login or sign up">
    <li class="nav-item flex-fill" role="presentation">
        <a class="nav-link w-100 text-center {{ $tab === 'login' ? 'active' : '' }}" href="{{ route('login') }}"
            role="tab" aria-selected="{{ $tab === 'login' ? 'true' : 'false' }}">Login</a>
    </li>
    <li class="nav-item flex-fill" role="presentation">
        <a class="nav-link w-100 text-center {{ $tab === 'signup' ? 'active' : '' }}" href="{{ route('register') }}"
            role="tab" aria-selected="{{ $tab === 'signup' ? 'true' : 'false' }}">Signup</a>
    </li>
</ul>
