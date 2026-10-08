{{--
    Session flash messages as ONE centered SweetAlert2. Include after the scripts partial.
    Validation errors are not shown here: every admin form shows them under its fields
    (x-admin.form.* components).
--}}
@php
    $flash = null;

    foreach (['success' => 'success', 'status' => 'success', 'error' => 'error', 'warning' => 'warning', 'info' => 'info'] as $key => $icon) {
        if (session()->has($key)) {
            $flash = [
                'icon' => $icon,
                'text' => session($key),
                'title' => ['success' => 'Success', 'error' => 'Error', 'warning' => 'Warning', 'info' => 'Notice'][$icon],
                'timer' => $icon === 'success' ? 3000 : null,
            ];
            break;
        }
    }
@endphp

@if ($flash)
    <script>
        Swal.fire({
            position: 'center',
            icon: @json($flash['icon']),
            title: @json($flash['title']),
            text: @json($flash['text']),
            confirmButtonColor: '#6777ef',
            timer: @json($flash['timer']),
            timerProgressBar: true
        });
    </script>
@endif
