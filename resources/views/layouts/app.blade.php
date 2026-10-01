<!DOCTYPE html>
<html lang="pt-br">
<head>
<script>
(function () {
    try {
        if (localStorage.getItem("axion.portal.sidebar") === "1") {
            document.documentElement.classList.add("shell-collapsed");
        }
    } catch (e) {}
})();
</script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Portal MMI')</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="{{ asset('static/css/portal.css') }}">
    <link rel="stylesheet" href="{{ asset('static/css/shell.css') }}?v=4">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('static/css/refino.css') }}?v=9">
</head>
<body>
@auth
@php($user = auth()->user())
<nav class="navbar d-lg-none px-3 shell-mobile-nav">
    <div class="d-flex align-items-center">
        <button class="navbar-toggler text-white border-0" type="button" data-bs-toggle="offcanvas" data-bs-target="#sidebarOffcanvas">
            <i class="bi bi-list text-white fs-3"></i>
        </button>
        <a class="navbar-brand ms-2" href="{{ route('dashboard') }}">
            <img src="{{ asset('static/img/logo-mmi.png') }}" alt="MMI Incorporações" height="28">
        </a>
    </div>
</nav>

<div class="shell">
    <aside class="offcanvas-lg offcanvas-start shell-sidebar" tabindex="-1" id="sidebarOffcanvas">
        <div class="offcanvas-header d-lg-none shell-offcanvas-header">
            <h5 class="offcanvas-title text-white">Menu</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" data-bs-target="#sidebarOffcanvas"></button>
        </div>
        <div class="offcanvas-body d-flex flex-column p-0">
            <div class="shell-sidebar-header d-none d-lg-flex">
                <a href="{{ route('dashboard') }}" class="d-flex align-items-center">
                    <img src="{{ asset('static/img/logo-mmi.png') }}" alt="MMI Incorporações" class="shell-logo-full">
                    <span class="shell-logo-mini">MMI</span>
                </a>
                <button type="button" class="shell-collapse-btn shell-collapse-toggle" title="Recolher/expandir menu" aria-label="Recolher/expandir menu">«</button>
            </div>

            @if ($user->isAdmin() || $user->isAdminEmpresa())
            <div class="shell-empresa-box">
                <p class="shell-label">Empresa ativa</p>
                @if ($user->isAdmin())
                <button type="button" class="shell-empresa-btn" id="shellEmpresaToggle" title="{{ $empresaAtiva?->nome ?? 'Nenhuma empresa cadastrada' }}" aria-expanded="false">
                    <span class="shell-empresa-avatar">{{ $empresaAtiva?->iniciais ?? '--' }}</span>
                    <span class="shell-empresa-info">
                        <span class="shell-empresa-nome">{{ $empresaAtiva?->nome ?? 'Nenhuma empresa' }}</span>
                        @if ($empresaAtiva)<span class="shell-empresa-cnpj">CNPJ {{ $empresaAtiva->cnpj }}</span>@endif
                    </span>
                    <span class="shell-empresa-chevron">▾</span>
                </button>
                <div class="shell-empresa-menu" id="shellEmpresaMenu" hidden>
                    @forelse ($empresasDisponiveis as $emp)
                    <a href="{{ route('empresas.show', $emp) }}" class="{{ $empresaAtiva?->id === $emp->id ? 'ativa' : '' }}">{{ $emp->nome }}</a>
                    @empty
                    <span class="text-white-50 small px-2">Nenhuma empresa cadastrada</span>
                    @endforelse
                </div>
                @else
                <div class="shell-empresa-btn" style="cursor: default;">
                    <span class="shell-empresa-avatar">{{ $empresaAtiva?->iniciais }}</span>
                    <span class="shell-empresa-info">
                        <span class="shell-empresa-nome">{{ $empresaAtiva?->nome }}</span>
                        <span class="shell-empresa-cnpj">CNPJ {{ $empresaAtiva?->cnpj }}</span>
                    </span>
                </div>
                @endif
            </div>

            <nav class="shell-nav">
                @foreach ($gruposNav as $grupo)
                <div class="shell-nav-group">
                    <p class="shell-label">{{ $grupo['titulo'] }}</p>
                    @foreach ($grupo['itens'] as $item)
                    <a href="{{ $item['url'] }}" class="shell-nav-link{{ $item['ativo'] ? ' active' : '' }}" data-dica="{{ $item['label'] }}" @if ($item['ativo']) aria-current="page" @endif>
                        <span class="shell-nav-icon bi {{ $item['icone'] }}"></span>
                        <span class="shell-nav-text">{{ $item['label'] }}</span>
                    </a>
                    @endforeach
                </div>
                @endforeach
            </nav>
            @else
            <nav class="shell-nav shell-nav-user">
                <div class="shell-nav-group shell-user-welcome">
                    <p class="shell-label">Empresa</p>
                    <div class="shell-user-company">
                        <span class="shell-empresa-avatar">{{ $empresaAtiva?->iniciais ?? '--' }}</span>
                        <span>{{ $empresaAtiva?->nome ?? 'Empresa não cadastrada' }}</span>
                    </div>
                    <p class="shell-user-greeting">Bem-vindo ao Portal MMI,<br><strong>{{ $user->first_name ?: $user->username }}</strong></p>
                </div>

                @forelse ($sidebarSetores as $setor)
                <div class="shell-nav-group shell-user-links">
                    <button type="button" class="shell-user-sector shell-sector-toggle" data-dica="{{ $setor->nome }}" data-target="shellSetor{{ $setor->id }}" aria-label="Abrir links de {{ $setor->nome }}" aria-expanded="false">
                        <i class="bi bi-diagram-3"></i><span>{{ $setor->nome }}</span><i class="shell-sector-chevron bi bi-chevron-down"></i>
                    </button>
                    <div id="shellSetor{{ $setor->id }}" class="shell-sector-links" hidden>
                        @forelse ($setor->linksVisiveis as $link)
                        <a href="{{ route('links.acessar', $link->id) }}" class="shell-nav-link" data-dica="{{ $link->nome }}">
                            <span class="shell-nav-icon bi bi-box-arrow-up-right"></span>
                            <span class="shell-nav-text">{{ $link->nome }}</span>
                        </a>
                        @empty
                        <span class="shell-nav-empty">Nenhum link liberado</span>
                        @endforelse
                    </div>
                </div>
                @empty
                <div class="shell-nav-group shell-user-links">
                    <p class="shell-label">Setor</p>
                    <span class="shell-nav-empty">Nenhum setor vinculado</span>
                </div>
                @endforelse
            </nav>
            @endif

            <div class="shell-sidebar-footer">
                <div class="shell-user-mini">
                    <span class="shell-user-avatar">{{ $user->iniciais }}<span class="dot"></span></span>
                    <span class="shell-user-info">
                        <span class="shell-user-nome">{{ $user->first_name ?: $user->username }}</span>
                        <span class="shell-user-papel">{{ $user->role_label }} · Online</span>
                    </span>
                </div>
                <form action="{{ route('logout') }}" method="post" class="mt-2 d-lg-none">
                    @csrf
                    <button type="submit" class="btn btn-outline-light btn-sm w-100">Sair</button>
                </form>
            </div>
        </div>
    </aside>

    <div class="shell-content flex-grow-1 d-flex flex-column" style="min-width: 0;">
        <header class="shell-topbar">
            <button type="button" class="shell-topbar-toggle shell-collapse-toggle d-none d-lg-flex" aria-label="Recolher/expandir menu">
                <i class="bi bi-list"></i>
            </button>
            <div class="flex-grow-1" style="min-width: 0;">
                <div class="shell-breadcrumb">
                    <span>Portal</span><span>›</span>
                    @hasSection('titulo')
                    <span>{{ $tituloPaginaShell }}</span><span>›</span><span class="current text-truncate">@yield('titulo')</span>
                    @else
                    <span class="current">{{ $tituloPaginaShell }}</span>
                    @endif
                </div>
                <h1 class="shell-page-title text-truncate">@yield('titulo', $tituloPaginaShell)</h1>
            </div>
            <div class="shell-user-menu">
                <button type="button" class="shell-user-menu-btn" id="shellUserMenuToggle" aria-expanded="false">
                    <span class="shell-user-menu-avatar">{{ $user->iniciais }}</span>
                    <span class="d-none d-sm-flex flex-column text-start" style="gap: 1px;">
                        <span style="font-size: 13px; font-weight: 700; color: #0f2440;">{{ $user->first_name ?: $user->username }}</span>
                        <span style="font-size: 11px; color: #6f88a8;">{{ $user->role_label }}</span>
                    </span>
                    <span style="color: #8ba0bb; font-size: 10px;">▾</span>
                </button>
                <div class="shell-user-menu-panel" id="shellUserMenuPanel" hidden>
                    <div class="info">
                        <p class="nome">{{ $user->nome_exibicao }}</p>
                        <p class="papel">{{ $user->email ?: $user->role_label }}</p>
                    </div>
                    <form action="{{ route('logout') }}" method="post">
                        @csrf
                        <button type="submit" class="shell-logout-btn"><i class="bi bi-box-arrow-right me-1"></i> Sair do portal</button>
                    </form>
                </div>
            </div>
        </header>

        <main class="shell-main">
            @include('partials.mensagens')
            @yield('content')
        </main>
    </div>
</div>
@include('partials.modal_excluir')
@else
<div class="auth-bg">
    <div class="auth-card">
        @include('partials.mensagens')
        @yield('content')
    </div>
</div>
@endauth

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="{{ asset('static/js/shell.js') }}?v=5"></script>
@stack('scripts')
</body>
</html>
