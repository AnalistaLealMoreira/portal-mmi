<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Entrar — MMI Incorporações</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('static/css/auth.css') }}">
    <link rel="stylesheet" href="{{ asset('static/css/refino.css') }}?v=9">
</head>
<body>
<div class="auth-page">
    <div class="auth-bg-gradient" aria-hidden="true"></div>
    <div class="auth-bg-grid" aria-hidden="true"></div>

    <div class="auth-grid">
        <div class="auth-intro">
            <img src="{{ asset('static/img/logo-mmi.png') }}" alt="MMI Incorporações">
            <p class="auth-kicker">Portal MMI</p>
            <h1 class="auth-title">Gestão clara para <span class="accent">decisões melhores.</span></h1>
            <p class="auth-lede">
                Acesse o portal corporativo da MMI Incorporações e encontre, em um só lugar,
                as informações que apoiam o seu trabalho.
            </p>
            <div class="auth-bullets">
                <div class="auth-bullet">
                    <span class="auth-bullet-icon" style="background: rgba(58, 206, 197, 0.16); color: #3acec5;">◎</span>
                    Informações da sua operação em um só lugar
                </div>
                <div class="auth-bullet">
                    <span class="auth-bullet-icon" style="background: rgba(78, 168, 255, 0.16); color: #4ea8ff;">⛨</span>
                    Acesso individual e protegido por perfil
                </div>
            </div>
        </div>

        <div class="auth-form-wrap">
            <form method="post" action="{{ route('login') }}" class="auth-form">
                @csrf
                <div>
                    <h2>Acessar o portal</h2>
                    <p class="auth-form-sub">Entre com suas credenciais corporativas.</p>
                </div>

                <label>Usuário ou e-mail
                    <input name="username" type="text" required autofocus autocomplete="username"
                           placeholder="seu.usuario ou voce@empresa.com" value="{{ old('username') }}">
                </label>
                @foreach ($errors->get('username') as $erro)
                <p class="auth-alert auth-alert-error">{{ $erro }}</p>
                @endforeach

                <label>Senha
                    <span class="auth-password-field">
                        <input id="senhaInput" name="password" type="password" required
                               autocomplete="current-password" placeholder="••••••••">
                        <button type="button" id="toggleSenha" class="auth-toggle-senha">Ver</button>
                    </span>
                </label>
                @foreach ($errors->get('password') as $erro)
                <p class="auth-alert auth-alert-error">{{ $erro }}</p>
                @endforeach

                <label class="auth-remember">
                    <input type="checkbox" name="lembrar" value="1" @checked(old('lembrar'))>
                    Manter conectado
                </label>

                <button type="submit" class="auth-submit">Acessar</button>
            </form>
        </div>
    </div>

    <div class="auth-page-footer">
        <span>&copy; {{ date('Y') }} MMI Incorporações</span>
        <a href="{{ route('home') }}">&larr; Voltar ao site</a>
    </div>
</div>

<script>
document.getElementById('toggleSenha').addEventListener('click', function () {
    var input = document.getElementById('senhaInput');
    var mostrar = input.type === 'password';
    input.type = mostrar ? 'text' : 'password';
    this.textContent = mostrar ? 'Ocultar' : 'Ver';
});
</script>
</body>
</html>
