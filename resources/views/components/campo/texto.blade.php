@props(['name', 'label', 'value' => null, 'type' => 'text', 'help' => null, 'required' => false])
<div class="mb-3">
    <label class="form-label" for="id_{{ $name }}">{{ $label }}</label>
    <input type="{{ $type }}" name="{{ $name }}" id="id_{{ $name }}"
           value="{{ $type === 'password' ? '' : old($name, $value) }}"
           @required($required)
           {{ $attributes->class(['form-control', 'is-invalid' => $errors->has($name)]) }}>
    @if ($help)<div class="form-text">{{ $help }}</div>@endif
    <x-campo.erros :name="$name" />
</div>
