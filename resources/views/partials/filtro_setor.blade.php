@if ($setores->count() > 1)
<form method="get" class="d-flex align-items-center gap-2">
    @if (request()->filled('q'))<input type="hidden" name="q" value="{{ request('q') }}">@endif
    <label for="filtro-setor" class="col-form-label col-form-label-sm text-muted mb-0">Setor</label>
    <select id="filtro-setor" name="setor" class="form-select form-select-sm" style="width: auto;" onchange="this.form.submit()">
        <option value="">Todos os setores</option>
        @foreach ($setores as $setor)
        <option value="{{ $setor->id }}" @selected($setorSelecionado === (string) $setor->id)>{{ $setor->nome }}</option>
        @endforeach
    </select>
</form>
@endif
