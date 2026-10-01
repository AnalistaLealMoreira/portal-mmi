{{-- $opcoes: lista de ['valor' => ..., 'rotulo' => ..., 'attrs' => [...]] --}}
@props(['name', 'label', 'opcoes', 'value' => null, 'required' => false, 'help' => null])
@php($selecionado = (string) old($name, $value))
<div class="mb-3">
    <label class="form-label" for="id_{{ $name }}">{{ $label }}</label>
    <select name="{{ $name }}" id="id_{{ $name }}" @required($required)
            {{ $attributes->class(['form-select', 'is-invalid' => $errors->has($name)]) }}>
        <option value="">---------</option>
        @foreach ($opcoes as $opcao)
        <option value="{{ $opcao['valor'] }}" @selected($selecionado === (string) $opcao['valor'])
            @foreach ($opcao['attrs'] ?? [] as $attr => $valorAttr) {{ $attr }}="{{ $valorAttr }}" @endforeach>{{ $opcao['rotulo'] }}</option>
        @endforeach
    </select>
    @if ($help)<div class="form-text">{{ $help }}</div>@endif
    <x-campo.erros :name="$name" />
</div>
