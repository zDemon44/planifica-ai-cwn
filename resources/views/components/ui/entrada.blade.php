@props(['model', 'label', 'type' => 'text', 'required' => false])
<div class="field">
    <label for="{{ str_replace('.', '-', $model) }}">{{ $label }} @if($required)*@endif</label>
    <input id="{{ str_replace('.', '-', $model) }}" type="{{ $type }}" wire:model="{{ $model }}" @required($required) {{ $attributes }}>
    @error($model)<small class="field-error">{{ $message }}</small>@enderror
</div>
