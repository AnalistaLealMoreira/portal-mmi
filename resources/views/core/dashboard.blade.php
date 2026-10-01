@extends('layouts.app')
@section('title', 'Painel')
@section('content')

@if (! empty($mostrarPainelAdmin))
<div class="row g-3 mb-4">
    @foreach ([
        ['bi-building', $totalEmpresas, 'Empresas Cadastradas'],
        ['bi-people-fill', $totalUsuarios, 'Total de Usuários'],
        ['bi-bar-chart-fill', $totalLinks, 'Total de Links Cadastrados'],
    ] as [$icone, $valor, $rotulo])
    <div class="col-6 col-lg-4">
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

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <h2 class="h5 mb-1">Links por Empresa</h2>
        <p class="text-muted small">Quantidade de links de BI cadastrados em cada empresa.</p>
        <canvas id="linksPorEmpresaChart" height="100"></canvas>
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

function graficoDeBarras(id, dados, cor, horizontal) {
    var eixoValor = horizontal ? 'x' : 'y';
    var escalas = {};
    escalas[eixoValor] = { beginAtZero: true, ticks: { precision: 0 } };
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
var linksPorEmpresa = @json($linksPorEmpresa);
new Chart(document.getElementById('linksPorEmpresaChart'), {
    type: 'bar',
    data: {
        labels: linksPorEmpresa.map(function (i) { return i.empresa; }),
        datasets: [{ label: 'Links', data: linksPorEmpresa.map(function (i) { return i.total; }), backgroundColor: COR.carvao, borderRadius: 6, maxBarThickness: 48 }],
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true, ticks: { precision: 0 } }, x: { grid: { display: false } } },
    },
});
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
