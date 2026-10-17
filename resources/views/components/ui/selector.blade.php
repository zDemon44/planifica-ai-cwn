@props(['model', 'label'])
<div class="field">
    <label for="{{ str_replace('.', '-', $model) }}">{{ $label }}</label>
    <select id="{{ str_replace('.', '-', $model) }}" wire:model="{{ $model }}" {{ $attributes }}>{{ $slot }}</select>
    @error($model)<small class="field-error">{{ $message }}</small>@enderror
</div>
