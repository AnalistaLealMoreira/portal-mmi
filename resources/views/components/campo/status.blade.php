{{-- Rádio "ativado/desativado" (equivale ao status_field do portal Django). --}}
@props(['name' => 'ativo', 'label' => 'Status', 'ativado' => 'Ativado', 'desativado' => 'Desativado', 'value' => true])
@php($atual = old($name, $value ? '1' : '0'))
<div class="mb-3">
    <label class="form-label">{{ $label }}</label>
    <div>
        <div class="form-check form-check-inline">
            <input class="form-check-input" type="radio" name="{{ $name }}" id="id_{{ $name }}_0" value="1" @checked((string) $atual === '1')>
            <label class="form-check-label" for="id_{{ $name }}_0">{{ $ativado }}</label>
        </div>
        <div class="form-check form-check-inline">
            <input class="form-check-input" type="radio" name="{{ $name }}" id="id_{{ $name }}_1" value="0" @checked((string) $atual === '0')>
            <label class="form-check-label" for="id_{{ $name }}_1">{{ $desativado }}</label>
        </div>
    </div>
    <x-campo.erros :name="$name" />
</div>
