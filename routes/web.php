<?php

use App\Http\Controllers\AuditoriaController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmpresaController;
use App\Http\Controllers\LinkBiAdminController;
use App\Http\Controllers\LinkBiController;
use App\Http\Controllers\RedePermitidaController;
use App\Http\Controllers\SetorController;
use App\Http\Controllers\UsuarioController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/accounts/login')->name('home');

Route::middleware('guest')->group(function () {
    Route::get('/accounts/login', [LoginController::class, 'show'])->name('login');
    Route::post('/accounts/login', [LoginController::class, 'login'])->middleware('throttle:10,1');
});

Route::post('/accounts/logout', [LoginController::class, 'logout'])->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    // Relatórios visíveis para o usuário logado.
    Route::get('/links', [LinkBiController::class, 'index'])->name('links.index');
    Route::get('/links/{link}/acessar', [LinkBiController::class, 'acessar'])->whereNumber('link')->name('links.acessar');

    // Redes permitidas (somente Administrador).
    Route::middleware('papel:ADMIN')->prefix('accounts/redes')->name('redes.')->controller(RedePermitidaController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/nova', 'create')->name('create');
        Route::post('/nova', 'store')->name('store');
        Route::get('/{rede}/editar', 'edit')->whereNumber('rede')->name('edit');
        Route::post('/{rede}/editar', 'update')->whereNumber('rede')->name('update');
        Route::get('/{rede}/excluir', 'confirmDelete')->whereNumber('rede')->name('delete');
        Route::post('/{rede}/excluir', 'destroy')->whereNumber('rede')->name('destroy');
    });

    // Empresas: CRUD somente Admin; o hub (detalhe) também para Admin de Empresa.
    Route::prefix('empresas')->name('empresas.')->controller(EmpresaController::class)->group(function () {
        Route::get('/{empresa}', 'show')->whereNumber('empresa')->middleware('papel:ADMIN,ADMIN_EMPRESA')->name('show');

        Route::middleware('papel:ADMIN')->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/nova', 'create')->name('create');
            Route::post('/nova', 'store')->name('store');
            Route::get('/{empresa}/editar', 'edit')->whereNumber('empresa')->name('edit');
            Route::post('/{empresa}/editar', 'update')->whereNumber('empresa')->name('update');
            Route::get('/{empresa}/excluir', 'confirmDelete')->whereNumber('empresa')->name('delete');
            Route::post('/{empresa}/excluir', 'destroy')->whereNumber('empresa')->name('destroy');
        });
    });

    // Setores e Links de BI dentro de uma empresa (Admin ou Admin da própria empresa).
    Route::prefix('empresas/{empresa}')->whereNumber('empresa')->middleware(['papel:ADMIN,ADMIN_EMPRESA', 'empresa.escopo'])->group(function () {
        Route::prefix('setores')->name('setores.')->controller(SetorController::class)->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/novo', 'create')->name('create');
            Route::post('/novo', 'store')->name('store');
            Route::get('/{setor}/editar', 'edit')->whereNumber('setor')->name('edit');
            Route::post('/{setor}/editar', 'update')->whereNumber('setor')->name('update');
            Route::get('/{setor}/excluir', 'confirmDelete')->whereNumber('setor')->name('delete');
            Route::post('/{setor}/excluir', 'destroy')->whereNumber('setor')->name('destroy');
        });

        Route::prefix('links')->name('links-admin.')->controller(LinkBiAdminController::class)->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/novo', 'create')->name('create');
            Route::post('/novo', 'store')->name('store');
            Route::get('/{link}/editar', 'edit')->whereNumber('link')->name('edit');
            Route::post('/{link}/editar', 'update')->whereNumber('link')->name('update');
            Route::get('/{link}/excluir', 'confirmDelete')->whereNumber('link')->name('delete');
            Route::post('/{link}/excluir', 'destroy')->whereNumber('link')->name('destroy');
        });
    });

    // Usuários (Admin: todas as empresas; Admin de Empresa: só a própria).
    Route::middleware('papel:ADMIN,ADMIN_EMPRESA')->prefix('usuarios')->name('usuarios.')->controller(UsuarioController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/novo', 'create')->name('create');
        Route::post('/novo', 'store')->name('store');
        Route::get('/{funcionario}/editar', 'edit')->whereNumber('funcionario')->name('edit');
        Route::post('/{funcionario}/editar', 'update')->whereNumber('funcionario')->name('update');
        Route::get('/{funcionario}/excluir', 'confirmDelete')->whereNumber('funcionario')->name('delete');
        Route::post('/{funcionario}/excluir', 'destroy')->whereNumber('funcionario')->name('destroy');
    });

    Route::get('/auditoria', [AuditoriaController::class, 'index'])->middleware('papel:ADMIN,DIRETOR')->name('auditoria.index');
});
