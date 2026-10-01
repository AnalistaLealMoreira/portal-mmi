@extends('layouts.app')
@section('title', 'Rede permitida')
@section('titulo', $rede->exists ? 'Editar rede permitida' : 'Nova rede permitida')
@section('content')
<div class="card form-card">
    <div class="card-body">
        <form method="post" action="{{ $rede->exists ? route('redes.update', $rede) : route('redes.store') }}">
            @csrf
            <x-campo.texto name="rede" label="IP ou rede (CIDR)" :value="$rede->rede" maxlength="43" required
                           help="Exemplos: 192.168.1.0/24 ou 200.10.20.30/32" />
            <x-campo.texto name="descricao" label="Descrição" :value="$rede->descricao" maxlength="120" />
            <div class="mb-3 form-check">
                <input type="hidden" name="ativo" value="0">
                <input type="checkbox" name="ativo" value="1" id="id_ativo" class="form-check-input"
                       @checked(old('ativo', $rede->exists ? $rede->ativo : true))>
                <label class="form-check-label" for="id_ativo">Ativa</label>
            </div>
            <div class="form-acoes">
                <button type="submit" class="btn btn-primary">Salvar</button>
                <a href="{{ route('redes.index') }}" class="btn btn-outline-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>
@endsection
