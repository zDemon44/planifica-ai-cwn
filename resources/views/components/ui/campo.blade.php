@props(['name', 'label', 'type' => 'text', 'required' => false])
<div class="field">
    <label for="{{ $name }}">{{ $label }} @if($required)<span aria-hidden="true">*</span>@endif</label>
    <input id="{{ $name }}" type="{{ $type }}" wire:model="formulario.{{ $name }}" @required($required) {{ $attributes }} @error('formulario.'.$name) aria-invalid="true" aria-describedby="{{ $name }}-error" @enderror>
    @error('formulario.'.$name)<small id="{{ $name }}-error" class="field-error">{{ $message }}</small>@enderror
</div>
