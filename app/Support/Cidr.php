<?php

namespace App\Support;

use Symfony\Component\HttpFoundation\IpUtils;

/**
 * Equivalente ao `ipaddress.ip_network(valor, strict=False)` do Python usado no
 * portal Django: aceita IP isolado ou rede CIDR (IPv4/IPv6) e devolve a rede
 * normalizada (bits de host zerados; IP isolado vira /32 ou /128).
 */
class Cidr
{
    public static function normalizar(string $valor): ?string
    {
        $valor = trim($valor);
        if ($valor === '') {
            return null;
        }

        [$ip, $prefixo] = array_pad(explode('/', $valor, 2), 2, null);
        $binario = @inet_pton($ip);
        if ($binario === false || str_contains($ip, '%')) {
            return null;
        }

        $totalBits = strlen($binario) * 8;
        if ($prefixo === null) {
            $prefixo = $totalBits;
        } elseif (! ctype_digit($prefixo) || (int) $prefixo > $totalBits) {
            return null;
        }
        $prefixo = (int) $prefixo;

        $rede = '';
        foreach (str_split($binario) as $i => $byte) {
            $bitsNoByte = max(0, min(8, $prefixo - $i * 8));
            $mascara = $bitsNoByte === 0 ? 0 : (0xFF << (8 - $bitsNoByte)) & 0xFF;
            $rede .= chr(ord($byte) & $mascara);
        }

        return inet_ntop($rede).'/'.$prefixo;
    }

    public static function contem(string $rede, ?string $ip): bool
    {
        if (! $ip || @inet_pton($ip) === false) {
            return false;
        }

        return IpUtils::checkIp($ip, $rede);
    }
}
