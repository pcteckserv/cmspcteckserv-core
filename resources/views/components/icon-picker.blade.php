@php
    $selectedValue = $selectedIcon(old($name, $value));
@endphp
<div class="cms-icon-picker" data-cms-icon-picker data-empty-label="{{ $emptyLabel }}">
    @if ($label)
        <label class="form-label" for="{{ $inputId() }}-toggle">{{ $label }}</label>
    @endif
    <input id="{{ $inputId() }}" type="hidden" name="{{ $name }}" value="{{ $selectedValue }}" data-cms-icon-picker-input>
    <button id="{{ $inputId() }}-toggle" class="btn btn-outline-secondary cms-icon-picker__toggle" type="button" aria-haspopup="dialog" aria-controls="cms-icon-picker-modal" aria-expanded="false" data-cms-icon-picker-toggle>
        <span class="cms-icon-picker__preview" data-cms-icon-picker-preview>
            @if ($selectedValue)
                @include('cms-core::components.icons.catalog', ['name' => $selectedValue])
            @endif
        </span>
        <span data-cms-icon-picker-label>{{ $selectedValue ? $options[$selectedValue] : $emptyLabel }}</span>
        <span aria-hidden="true">⌄</span>
    </button>
</div>
@once
    @push('cms-icon-picker-modal')
        @include('cms-core::admin.partials.icon-picker-modal')
    @endpush
@endonce
