<?php

use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

if (! function_exists('data_local')) {
    /**
     * As datas são gravadas em UTC (mesmo padrão do Django com USE_TZ=True);
     * na tela são exibidas no fuso do portal (America/Sao_Paulo).
     */
    function data_local(CarbonInterface|string|null $data = null, string $formato = 'd/m/Y H:i'): string
    {
        if ($data === null) {
            $data = Carbon::now();
        }
        if (is_string($data)) {
            $data = Carbon::parse($data, 'UTC');
        }

        return $data->copy()->setTimezone(config('portal.timezone'))->format($formato);
    }
}

if (! function_exists('qtd')) {
    /** "1 link" / "3 links". */
    function qtd(int $n, string $singular, string $plural): string
    {
        return $n.' '.($n === 1 ? $singular : $plural);
    }
}
