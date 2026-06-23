<!-- resources/views/partials/_error.blade.php -->
@if(session('error'))
    <div class="alert alert-danger">
        {{ session('error') }}
    </div>
@endif
