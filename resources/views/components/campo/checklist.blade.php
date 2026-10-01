{{-- Lista de checkboxes recolhida num dropdown: mostra um resumo do que está marcado. --}}
@props(['name', 'label', 'opcoes', 'selecionados' => []])
@php($marcados = array_map('strval', old($name, $selecionados)))
<div class="mb-3">
    <label class="form-label">{{ $label }}</label>
    <div class="dropdown checklist-dropdown">
        <button class="btn btn-outline-secondary dropdown-toggle w-100 text-start" type="button"
                data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false">
            <span class="checklist-dropdown-summary">Nenhum selecionado</span>
        </button>
        <ul class="dropdown-menu w-100 p-2" style="max-height: 260px; overflow-y: auto;">
            @foreach ($opcoes as $i => $opcao)
            <li>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="{{ $name }}[]" id="id_{{ $name }}_{{ $i }}"
                           value="{{ $opcao['valor'] }}" @checked(in_array((string) $opcao['valor'], $marcados, true))>
                    <label class="form-check-label" for="id_{{ $name }}_{{ $i }}">{{ $opcao['rotulo'] }}</label>
                </div>
            </li>
            @endforeach
        </ul>
    </div>
    <script>
    (function () {
        var wrapper = document.currentScript.previousElementSibling;
        var summary = wrapper.querySelector(".checklist-dropdown-summary");
        var checkboxes = wrapper.querySelectorAll('input[type="checkbox"]');

        function atualizarResumo() {
            var marcados = Array.prototype.filter.call(checkboxes, function (cb) { return cb.checked; });
            if (marcados.length === 0) {
                summary.textContent = "Nenhum selecionado";
            } else if (marcados.length <= 2) {
                summary.textContent = Array.prototype.map.call(marcados, function (cb) {
                    var label = cb.nextElementSibling;
                    return label ? label.textContent.trim() : "";
                }).join(", ");
            } else {
                summary.textContent = marcados.length + " selecionados";
            }
        }

        checkboxes.forEach(function (cb) { cb.addEventListener("change", atualizarResumo); });
        atualizarResumo();
    })();
    </script>
    <x-campo.erros :name="$name" />
    @foreach ($errors->get($name.'.*') as $errosItem)
        @foreach ($errosItem as $erro)<div class="text-danger small mt-1">{{ $erro }}</div>@endforeach
    @endforeach
</div>
