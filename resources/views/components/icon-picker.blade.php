@php
    $selectedValue = $selectedIcon(old($name, $value));
@endphp
<div class="cms-icon-picker" data-cms-icon-picker>
    @if ($label)
        <label class="form-label" for="{{ $inputId() }}">{{ $label }}</label>
    @endif
    <input id="{{ $inputId() }}" type="hidden" name="{{ $name }}" value="{{ $selectedValue }}" data-cms-icon-picker-input>
    <button class="btn btn-outline-secondary cms-icon-picker__toggle" type="button" aria-haspopup="listbox" aria-expanded="false" data-cms-icon-picker-toggle>
        <span class="cms-icon-picker__preview" data-cms-icon-picker-preview>
            @if ($selectedValue)
                @include('cms-core::components.icons.catalog', ['name' => $selectedValue])
            @endif
        </span>
        <span data-cms-icon-picker-label>{{ $selectedValue ? $options[$selectedValue] : $emptyLabel }}</span>
        <span aria-hidden="true">⌄</span>
    </button>
    <div class="cms-icon-picker__options" role="listbox" hidden data-cms-icon-picker-options>
        <button class="cms-icon-picker__option" type="button" role="option" data-cms-icon-option="" data-label="{{ $emptyLabel }}" aria-selected="{{ $selectedValue === '' ? 'true' : 'false' }}">
            <span class="cms-icon-picker__option-icon" aria-hidden="true">—</span><span>{{ $emptyLabel }}</span>
        </button>
        @foreach ($options as $icon => $iconLabel)
            <button class="cms-icon-picker__option" type="button" role="option" data-cms-icon-option="{{ $icon }}" data-label="{{ $iconLabel }}" aria-selected="{{ $selectedValue === $icon ? 'true' : 'false' }}">
                <span class="cms-icon-picker__option-icon" aria-hidden="true">@include('cms-core::components.icons.catalog', ['name' => $icon])</span>
                <span>{{ $iconLabel }}</span>
            </button>
        @endforeach
    </div>
</div>
