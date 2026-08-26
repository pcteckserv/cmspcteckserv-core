<div
    {{ $attributes->merge(['class' => 'cms-media-picker-field']) }}
    data-cms-media-picker
    data-library-url="{{ route('admin.media.library', ['type' => $type]) }}"
    data-upload-url="{{ route('admin.media.store') }}"
    data-type="{{ $type }}"
>
    @if ($label)
        <label class="form-label" for="{{ $inputId() }}">{{ $label }}</label>
    @endif

    <div class="input-group">
        <input
            id="{{ $inputId() }}"
            name="{{ $name }}"
            type="number"
            min="1"
            class="form-control @error($name) is-invalid @enderror"
            value="{{ old($name, $value) }}"
            placeholder="{{ $emptyLabel }}"
            data-cms-media-picker-input
        >
        <button class="btn btn-outline-primary" type="button" data-cms-media-picker-open>{{ $buttonLabel }}</button>
    </div>

    <div class="form-text" data-cms-media-picker-selected>{{ $help }}</div>

    @error($name)
        <div class="invalid-feedback d-block">{{ $message }}</div>
    @enderror
</div>
