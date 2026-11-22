@if (app()->environment('local') && config('app.debug'))
    <script src="https://cdn.jsdelivr.net/npm/eruda"></script>
    <script>
        eruda.init();
    </script>
@endif
