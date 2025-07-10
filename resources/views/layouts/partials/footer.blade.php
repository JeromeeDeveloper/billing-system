<script src="{{ asset('vendor/global/global.min.js') }}"></script>
<script src="{{ asset('js/quixnav-init.js') }}"></script>
<script src="{{ asset('js/custom.min.js') }}"></script>
<script src="{{ asset('vendor/raphael/raphael.min.js') }}"></script>
<script src="{{ asset('vendor/morris/morris.min.js') }}"></script>
<script src="{{ asset('vendor/circle-progress/circle-progress.min.js') }}"></script>
<script src="{{ asset('vendor/chart.js/Chart.bundle.min.js') }}"></script>
<script src="{{ asset('vendor/gaugeJS/dist/gauge.min.js') }}"></script>
<script src="{{ asset('vendor/flot/jquery.flot.js') }}"></script>
<script src="{{ asset('vendor/flot/jquery.flot.resize.js') }}"></script>
<script src="{{ asset('vendor/owl-carousel/js/owl.carousel.min.js') }}"></script>
<script src="{{ asset('vendor/jqvmap/js/jquery.vmap.min.js') }}"></script>
<script src="{{ asset('vendor/jqvmap/js/jquery.vmap.usa.js') }}"></script>
<script src="{{ asset('vendor/jquery.counterup/jquery.counterup.min.js') }}"></script>

{{-- External CDN --}}
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

{{-- Stack for additional scripts --}}
@stack('scripts')
