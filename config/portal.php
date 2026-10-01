<?php

return [

    /*
    | Fuso usado para exibir datas e agrupar acessos por dia. O banco continua
    | gravando em UTC, igual ao portal Django (USE_TZ=True).
    */
    'timezone' => env('PORTAL_TIMEZONE', 'America/Sao_Paulo'),

    /*
    | Iterações do PBKDF2 para novas senhas (padrão do Django 5.2).
    */
    'pbkdf2_iterations' => (int) env('PBKDF2_ITERATIONS', 1000000),

    /*
    | Produção: redireciona para HTTPS e envia HSTS (equivale a
    | SECURE_SSL_REDIRECT / SECURE_HSTS_SECONDS do Django).
    */
    'force_https' => (bool) env('PORTAL_FORCE_HTTPS', false),
    'hsts_seconds' => (int) env('PORTAL_HSTS_SECONDS', 31536000),

    'minutos_online' => 5,
    'intervalo_last_seen' => 60,

];
