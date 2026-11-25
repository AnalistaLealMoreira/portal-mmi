@extends('layouts.app')
@section('title', 'Painel')
@section('content')

@if (! empty($mostrarPainelAdmin))
<div class="row g-3 mb-4">
    @foreach ([
        ['bi-building', $totalEmpresas, 'Empresas Cadastradas'],
        ['bi-people-fill', $totalUsuarios, 'Total de Usuários'],
        ['bi-person-check-fill', $usuariosOnline, 'Usuários Online'],
        ['bi-bar-chart-fill', $totalLinks, 'Total de Links Cadastrados'],
    ] as [$icone, $valor, $rotulo])
    <div class="col-6 col-lg-3">
        <div class="card h-100 text-center border-0 shadow-sm">
            <div class="card-body">
                <div class="kpi-icon mx-auto mb-2"><i class="bi {{ $icone }}"></i></div>
                <div class="fs-2 fw-bold">{{ $valor }}</div>
                <div class="fw-semibold">{{ $rotulo }}</div>
            </div>
        </div>
    </div>
    @endforeach
</div>

<form method="get" class="filtros">
    <label class="filtros-data">De <input type="date" name="de" value="{{ $filtro['de'] }}" class="form-control form-control-sm"></label>
    <label class="filtros-data">Até <input type="date" name="ate" value="{{ $filtro['ate'] }}" class="form-control form-control-sm"></label>
    <select name="setor" class="form-select form-select-sm" style="width: auto;" aria-label="Área">
        <option value="">Todas as áreas</option>
        @foreach ($areas as $area)
        <option value="{{ $area->id }}" @selected($filtro['setor'] === $area->id)>{{ $area->empresa }} · {{ $area->nome }}</option>
        @endforeach
    </select>
    <button type="submit" class="btn btn-primary btn-sm">Filtrar</button>
    @if (request()->hasAny(['de', 'ate', 'setor']))
    <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary btn-sm">Limpar</a>
    @endif
</form>

<div class="row g-3">
    <div class="col-lg-6">
        <div class="card h-100 border-0 shadow-sm">
            <div class="card-body">
                <h2 class="h5 mb-1">Acessos por Usuário</h2>
                <p class="text-muted small">Os 15 usuários com mais acessos no período.</p>
                @if ($acessosPorUsuario->isEmpty())
                <p class="text-muted small">Nenhum acesso no período.</p>
                @else
                <canvas id="acessosPorUsuarioChart" height="260"></canvas>
                @endif
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card h-100 border-0 shadow-sm">
            <div class="card-body">
                <h2 class="h5 mb-1">Acessos por Link</h2>
                <p class="text-muted small">Os 15 links mais acessados no período.</p>
                @if ($acessosPorLink->isEmpty())
                <p class="text-muted small">Nenhum acesso no período.</p>
                @else
                <canvas id="acessosPorLinkChart" height="260"></canvas>
                @endif
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card h-100 border-0 shadow-sm">
            <div class="card-body">
                <h2 class="h5 mb-1">Acessos por Perfil</h2>
                <p class="text-muted small">Distribuição dos acessos do período por perfil de usuário.</p>
                @if ($acessosPorPerfil->isEmpty())
                <p class="text-muted small">Nenhum acesso no período.</p>
                @else
                <div class="mx-auto" style="max-width: 340px;">
                    <canvas id="acessosPorPerfilChart"></canvas>
                </div>
                @endif
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card h-100 border-0 shadow-sm">
            <div class="card-body">
                <h2 class="h5 mb-1">Acessos por Mês</h2>
                <p class="text-muted small">Últimos 12 meses até {{ end($acessosPorMes)['nome'] }}, na área selecionada.</p>
                <canvas id="acessosPorMesChart" height="200"></canvas>
            </div>
        </div>
    </div>
</div>
@else
<div class="row g-3 mb-4">
    @foreach ([
        ['bi-bar-chart-fill', $totalLinksAtivos, 'Relatórios Ativos', 'Disponíveis para consulta'],
        ['bi-building', $totalSetores, 'Setores Conectados', 'Áreas com permissão'],
        ['bi-people-fill', $usuariosOnline, 'Usuários Online', 'Conectados agora'],
        ['bi-activity', $conexoesRecentes, 'Conexões Recentes', 'Últimas 24h'],
    ] as [$icone, $valor, $rotulo, $legenda])
    <div class="col-6 col-lg-3">
        <div class="card h-100 text-center border-0 shadow-sm">
            <div class="card-body">
                <div class="kpi-icon mx-auto mb-2"><i class="bi {{ $icone }}"></i></div>
                <div class="fs-2 fw-bold">{{ $valor }}</div>
                <div class="fw-semibold">{{ $rotulo }}</div>
                <div class="text-muted small">{{ $legenda }}</div>
            </div>
        </div>
    </div>
    @endforeach
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <h2 class="h5 mb-1">Indicadores de Alerta</h2>
        <p class="text-muted small">Análise detalhada de acessos e performance do sistema</p>

        <ul class="nav nav-tabs mb-3">
            <li class="nav-item">
                <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-ranking" type="button">Ranking de Setores</button>
            </li>
            <li class="nav-item">
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-links" type="button">Relatórios Mais Acessados</button>
            </li>
            <li class="nav-item">
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-frequencia" type="button">Frequência de Acessos</button>
            </li>
        </ul>

        <div class="tab-content">
            <div class="tab-pane fade show active" id="tab-ranking">
                <div class="row g-4">
                    <div class="col-lg-6">
                        <h3 class="h6">Ranking Geral de Setores</h3>
                        <p class="text-muted small">Setores com mais acessos aos links de BI.</p>
                        @forelse ($rankingSetores as $item)
                        <div class="ranking-item">
                            <span>
                                <span class="ranking-pos {{ [1 => 'top1', 2 => 'top2', 3 => 'top3'][$loop->iteration] ?? '' }}">{{ $loop->iteration }}</span>
                                {{ $item['nome'] }}
                            </span>
                            <span class="pill">{{ $item['total'] }} acessos</span>
                        </div>
                        @empty
                        <p class="text-muted small">Nenhum acesso registrado ainda.</p>
                        @endforelse
                    </div>
                    <div class="col-lg-6">
                        <h3 class="h6">Visualização Gráfica</h3>
                        <canvas id="rankingChart" height="220"></canvas>
                    </div>
                </div>
            </div>

            <div class="tab-pane fade" id="tab-links">
                <div class="row g-4">
                    <div class="col-lg-6">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead><tr><th>Link</th><th>Acessos</th></tr></thead>
                                <tbody>
                                @forelse ($topLinks as $item)
                                <tr><td>{{ $item['nome'] }}</td><td>{{ $item['total'] }}</td></tr>
                                @empty
                                <tr><td colspan="2" class="text-muted">Nenhum acesso registrado ainda.</td></tr>
                                @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <canvas id="topLinksChart" height="220"></canvas>
                    </div>
                </div>
            </div>

            <div class="tab-pane fade" id="tab-frequencia">
                <canvas id="frequenciaChart" height="100" class="mb-3"></canvas>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead><tr><th>Dia</th><th>Acessos</th></tr></thead>
                        <tbody>
                        @foreach ($frequencia as $item)
                        <tr><td>{{ $item['dia'] }}</td><td>{{ $item['total'] }}</td></tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endif

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script>
// Paleta da marca: carvão e bege, com grade e eixos discretos.
var COR = { carvao: '#2f2b27', bege: '#b08d62', begeFundo: 'rgba(176, 141, 98, 0.14)', grade: '#efebe5', texto: '#77726b' };
Chart.defaults.font.family = '"Inter", "Segoe UI", system-ui, sans-serif';
Chart.defaults.font.size = 12;
Chart.defaults.color = COR.texto;
Chart.defaults.borderColor = COR.grade;
Chart.defaults.plugins.tooltip.backgroundColor = '#23211e';
Chart.defaults.plugins.tooltip.padding = 10;
Chart.defaults.plugins.tooltip.cornerRadius = 8;
Chart.defaults.plugins.tooltip.displayColors = false;
Chart.defaults.scale.border = { display: false };

// Rótulo de dados em todo gráfico de barras: o valor na ponta de cada barra.
Chart.register({
    id: 'rotulosNasBarras',
    afterDatasetsDraw: function (chart) {
        if (chart.config.type !== 'bar') return;
        var horizontal = chart.options.indexAxis === 'y';
        var ctx = chart.ctx;
        ctx.save();
        ctx.font = '600 12px ' + Chart.defaults.font.family;
        ctx.fillStyle = COR.carvao;
        ctx.textAlign = horizontal ? 'left' : 'center';
        ctx.textBaseline = horizontal ? 'middle' : 'bottom';
        chart.data.datasets.forEach(function (dataset, d) {
            var meta = chart.getDatasetMeta(d);
            if (meta.hidden) return;
            meta.data.forEach(function (barra, i) {
                var valor = dataset.data[i];
                if (valor === null || valor === undefined) return;
                ctx.fillText(valor, horizontal ? barra.x + 6 : barra.x, horizontal ? barra.y : barra.y - 4);
            });
        });
        ctx.restore();
    },
});

function graficoDeBarras(id, dados, cor, horizontal) {
    var eixoValor = horizontal ? 'x' : 'y';
    var escalas = {};
    escalas[eixoValor] = { beginAtZero: true, grace: '12%', ticks: { precision: 0 } };
    escalas[horizontal ? 'y' : 'x'] = { grid: { display: false } };
    new Chart(document.getElementById(id), {
        type: 'bar',
        data: {
            labels: dados.map(function (i) { return i.nome; }),
            datasets: [{ label: 'Acessos', data: dados.map(function (i) { return i.total; }), backgroundColor: cor, borderRadius: 6, maxBarThickness: 36 }],
        },
        options: {
            indexAxis: horizontal ? 'y' : 'x',
            responsive: true,
            plugins: { legend: { display: false } },
            scales: escalas,
        },
    });
}

@if (! empty($mostrarPainelAdmin))
@if ($acessosPorUsuario->isNotEmpty())
graficoDeBarras('acessosPorUsuarioChart', @json($acessosPorUsuario), COR.carvao, true);
@endif
@if ($acessosPorLink->isNotEmpty())
graficoDeBarras('acessosPorLinkChart', @json($acessosPorLink), COR.bege, true);
@endif
var acessosPorMes = @json($acessosPorMes);
new Chart(document.getElementById('acessosPorMesChart'), {
    type: 'bar',
    data: {
        labels: acessosPorMes.map(function (i) { return i.nome; }),
        datasets: [{ label: 'Acessos', data: acessosPorMes.map(function (i) { return i.total; }), backgroundColor: COR.bege, borderRadius: 6, maxBarThickness: 36 }],
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true, grace: '12%', ticks: { precision: 0 } }, x: { grid: { display: false } } },
    },
});

@if ($acessosPorPerfil->isNotEmpty())
var acessosPorPerfil = @json($acessosPorPerfil);

// Escreve o total dentro de cada fatia, em branco ou carvão conforme o tom da fatia.
function corDeContraste(hex) {
    var n = parseInt(hex.replace('#', ''), 16);
    var canal = function (v) { v /= 255; return v <= 0.03928 ? v / 12.92 : Math.pow((v + 0.055) / 1.055, 2.4); };
    var lum = function (c) { return 0.2126 * canal((c >> 16) & 255) + 0.7152 * canal((c >> 8) & 255) + 0.0722 * canal(c & 255); };
    var fatia = lum(n);
    var escuro = lum(parseInt(COR.carvao.replace('#', ''), 16));
    // Usa a cor de texto com maior razão de contraste (WCAG) sobre a fatia.
    return (1.05 / (fatia + 0.05)) >= ((fatia + 0.05) / (escuro + 0.05)) ? '#ffffff' : COR.carvao;
}
var rotulosNasFatias = {
    id: 'rotulosNasFatias',
    afterDatasetsDraw: function (chart) {
        var ctx = chart.ctx;
        var meta = chart.getDatasetMeta(0);
        var dataset = chart.data.datasets[0];
        ctx.save();
        ctx.font = '600 13px ' + Chart.defaults.font.family;
        ctx.textAlign = 'center';
        ctx.textBaseline = 'middle';
        meta.data.forEach(function (arco, i) {
            if (arco.hidden || !dataset.data[i]) return;
            if (arco.endAngle - arco.startAngle < 0.3) return; // fatia estreita demais para o número
            var pos = arco.tooltipPosition();
            ctx.fillStyle = corDeContraste(dataset.backgroundColor[i % dataset.backgroundColor.length]);
            ctx.fillText(dataset.data[i], pos.x, pos.y);
        });
        ctx.restore();
    },
};

new Chart(document.getElementById('acessosPorPerfilChart'), {
    type: 'pie',
    plugins: [rotulosNasFatias],
    data: {
        labels: acessosPorPerfil.map(function (i) { return i.nome; }),
        datasets: [{
            data: acessosPorPerfil.map(function (i) { return i.total; }),
            backgroundColor: [COR.carvao, COR.bege, '#8a8178', '#d9c4a5', '#5c544c'],
            borderColor: '#fff',
            borderWidth: 2,
        }],
    },
    options: {
        responsive: true,
        plugins: {
            legend: { position: 'bottom', labels: { usePointStyle: true, padding: 14 } },
            tooltip: {
                displayColors: true,
                callbacks: {
                    label: function (ctx) {
                        var total = ctx.dataset.data.reduce(function (a, b) { return a + b; }, 0);
                        return ctx.label + ': ' + ctx.parsed + ' (' + Math.round(ctx.parsed * 100 / total) + '%)';
                    },
                },
            },
        },
    },
});
@endif
@else
graficoDeBarras('rankingChart', @json($rankingSetores), COR.carvao, false);
graficoDeBarras('topLinksChart', @json($topLinks), COR.bege, true);

var frequencia = @json($frequencia);
new Chart(document.getElementById('frequenciaChart'), {
    type: 'line',
    data: {
        labels: frequencia.map(function (i) { return i.dia; }),
        datasets: [{
            label: 'Acessos por dia',
            data: frequencia.map(function (i) { return i.total; }),
            borderColor: COR.bege,
            backgroundColor: COR.begeFundo,
            borderWidth: 2,
            pointRadius: 0,
            pointHoverRadius: 4,
            pointBackgroundColor: COR.bege,
            fill: true,
            tension: 0.25,
        }],
    },
    options: {
        responsive: true,
        interaction: { mode: 'index', intersect: false },
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true, ticks: { precision: 0 } }, x: { grid: { display: false } } },
    },
});
@endif
</script>
@endpush
