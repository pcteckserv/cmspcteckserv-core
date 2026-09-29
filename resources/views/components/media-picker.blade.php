<div
    {{ $attributes->merge(['class' => 'cms-media-picker-field']) }}
    data-cms-media-picker
    data-library-url="{{ route('admin.media.library', ['type' => $type]) }}"
    data-upload-url="{{ route('admin.media.store') }}"
    data-type="{{ $type }}"
    data-empty-help="{{ $help }}"
    data-cms-media-picker-name="{{ $name }}"
    data-cms-media-picker-multiple="{{ $multiple ? 'true' : 'false' }}"
>
    @php($selectedMediaValue = old($name, $value ?? ($multiple ? [] : null)))
    @php($selectedMediaIds = is_array($selectedMediaValue) ? $selectedMediaValue : ($selectedMediaValue ? [$selectedMediaValue] : []))
    @php($selectedMediaId = $multiple ? null : $selectedMediaValue)

    @if ($label)
        <label class="form-label" for="{{ $displayId() }}">{{ $label }}</label>
    @endif

    <div class="input-group">
        @if ($multiple)
            <div data-cms-media-picker-values>
                @foreach ($selectedMediaIds as $selectedId)
                    <input type="hidden" name="{{ $name }}[]" value="{{ $selectedId }}" data-cms-media-picker-input>
                @endforeach
            </div>
        @else
            <input
                id="{{ $inputId() }}"
                name="{{ $name }}"
                type="hidden"
                value="{{ $selectedMediaId }}"
                data-cms-media-picker-input
            >
        @endif
        <input
            id="{{ $displayId() }}"
            type="text"
            class="form-control @error($name) is-invalid @enderror"
            value="{{ $multiple ? count($selectedMediaIds).' '.(count($selectedMediaIds) === 1 ? 'imagem selecionada' : 'imagens selecionadas') : $displayValue($selectedMediaId) }}"
            placeholder="{{ $emptyLabel }}"
            readonly
            data-cms-media-picker-display
        >
        <button class="btn btn-outline-primary" type="button" data-cms-media-picker-open>{{ $buttonLabel }}</button>
        @if ($clearable)
            <button class="btn btn-outline-secondary cms-media-icon-button cms-input-icon-button" type="button" title="{{ $clearLabel }}" aria-label="{{ $clearLabel }}" data-cms-media-picker-clear @disabled($multiple ? $selectedMediaIds === [] : ! $selectedMediaId)>@include('cms-core::components.icons.trash')</button>
        @endif
    </div>

    <div class="form-text" data-cms-media-picker-selected>{{ $multiple && $selectedMediaIds ? $displayValue($selectedMediaIds) : $help }}</div>

    @error($name)
        <div class="invalid-feedback d-block">{{ $message }}</div>
    @enderror
</div>
