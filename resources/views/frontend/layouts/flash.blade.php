{{-- Session flash messages and validation errors as ONE centered SweetAlert2. Include after the scripts. --}}
@php
    $flash = null;

    foreach (['success' => 'success', 'status' => 'success', 'error' => 'error', 'warning' => 'warning', 'info' => 'info'] as $key => $icon) {
        if (session()->has($key)) {
            $flash = ['icon' => $icon, 'text' => session($key)];
            break;
        }
    }

    // Pages that render errors under each field declare the "inline-errors" section.
    if (! $flash && ! $__env->hasSection('inline-errors')) {
        // $errors is missing when the request never went through the web middleware group.
        $validationErrors = collect(isset($errors) ? $errors->getBags() : [])
            ->flatMap(fn ($bag) => $bag->all())->unique()->values();

        if ($validationErrors->isNotEmpty()) {
            $flash = ['icon' => 'error', 'text' => $validationErrors->implode("\n")];
        }
    }

    if ($flash) {
        $titles = ['success' => 'Success', 'error' => 'Error', 'warning' => 'Warning', 'info' => 'Notice'];
        $flash['title'] = $titles[$flash['icon']];
        $flash['timer'] = $flash['icon'] === 'success' ? 3000 : null;
    }
@endphp

@if ($flash)
    <script>
        Swal.fire({
            position: 'center',
            icon: @json($flash['icon']),
            title: @json($flash['title']),
            text: @json($flash['text']),
            confirmButtonColor: '#0088cc',
            timer: @json($flash['timer']),
            timerProgressBar: true
        });
    </script>
@endif
