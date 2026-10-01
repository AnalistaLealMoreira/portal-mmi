<?php

use App\Http\Middleware\AtualizarUltimoAcesso;
use App\Http\Middleware\CabecalhosDeSeguranca;
use App\Http\Middleware\EscopoEmpresa;
use App\Http\Middleware\ExigirPapel;
use App\Http\Middleware\ExpirarSessaoAoFecharNavegador;
use App\Http\Middleware\RestringirAcessoPorRede;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->prepend(CabecalhosDeSeguranca::class);

        $middleware->web(append: [
            ExpirarSessaoAoFecharNavegador::class,
            RestringirAcessoPorRede::class,
            AtualizarUltimoAcesso::class,
        ]);

        $middleware->alias([
            'papel' => ExigirPapel::class,
            'empresa.escopo' => EscopoEmpresa::class,
        ]);

        $middleware->redirectGuestsTo(fn () => route('login'));
        $middleware->redirectUsersTo(fn () => route('dashboard'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        // Rede de segurança para qualquer CRUD: um erro de banco não tratado
        // (registro duplicado, chave vinculada etc.) volta para a página
        // anterior com um aviso, em vez de exibir a página de erro.
        $exceptions->render(function (QueryException $e, Request $request) {
            $anterior = $request->headers->get('referer');
            if ($request->expectsJson() || $request->isMethod('GET') || ! $anterior) {
                return null;
            }

            return redirect()->to($anterior)->withInput()->with(
                'error',
                'Não foi possível concluir a operação: os dados informados geram um conflito '
                .'(ex.: registro duplicado ou vinculado a outro). Nada foi salvo.'
            );
        });
    })->create();
