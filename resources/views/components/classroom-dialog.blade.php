{{--
    Pop-up form on the Classrooms page. One dialog adds and edits a kind of
    record; public/js/classrooms.js fills it in and sets its address.
--}}
@props(['kind', 'storeUrl' => null, 'updateUrl' => null])

<dialog
    class="classroom-dialog"
    data-dialog="{{ $kind }}"
    @if($storeUrl) data-store-url="{{ $storeUrl }}" @endif
    @if($updateUrl) data-update-url="{{ $updateUrl }}" @endif
    aria-labelledby="dialog-{{ $kind }}-title"
>

    <form method="POST" class="form-card">

        @csrf

        <input type="hidden" name="_method" value="PUT" data-dialog-method>
        <input type="hidden" name="_dialog" value="{{ $kind }}">
        <input type="hidden" name="_record" value="" data-dialog-record>

        <h2 id="dialog-{{ $kind }}-title" data-dialog-title></h2>

        @if($errors->any() && old('_dialog') === $kind)
            <div class="alert alert-error" data-dialog-errors>
                @foreach($errors->all() as $message)
                    <div>{{ $message }}</div>
                @endforeach
            </div>
        @endif

        {{ $slot }}

        <div class="form-actions">
            <button type="button" class="btn btn-secondary" data-dialog-close>Cancel</button>
            <button type="submit" class="btn btn-primary">Save</button>
        </div>

    </form>

</dialog>
