<!-- General JS Scripts -->
<script src="{{ asset('admin-assets/modules/jquery.min.js') }}"></script>
<script src="{{ asset('admin-assets/modules/popper.js') }}"></script>
<script src="{{ asset('admin-assets/modules/tooltip.js') }}"></script>
<script src="{{ asset('admin-assets/modules/bootstrap/js/bootstrap.min.js') }}"></script>
<script src="{{ asset('admin-assets/modules/nicescroll/jquery.nicescroll.min.js') }}"></script>
<script src="{{ asset('admin-assets/modules/moment.min.js') }}"></script>
<script src="{{ asset('admin-assets/js/stisla.js') }}"></script>

<!-- DataTables bundle (loaded once) and the shared AJAX/CSRF setup -->
<script src="{{ asset('admin-assets/modules/datatables/datatables.min.js') }}"></script>
<script src="{{ asset('admin-assets/js/admin-ajax.js') }}"></script>

<!-- SweetAlert2 + global confirm handler (the only confirm/alert UI in the admin area) -->
<script src="{{ asset('admin-assets/modules/sweetalert2/sweetalert2.all.min.js') }}"></script>
<script src="{{ asset('admin-assets/js/admin-confirm.js') }}"></script>

@stack('scripts')

<!-- Template JS File -->
<script src="{{ asset('admin-assets/js/scripts.js') }}"></script>
<script src="{{ asset('admin-assets/js/custom.js') }}"></script>
