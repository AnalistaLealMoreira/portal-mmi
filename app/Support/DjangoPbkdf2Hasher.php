<?php

namespace App\Support;

use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Support\Str;

/**
 * Hasher no mesmo formato do Django (`pbkdf2_sha256$<iterações>$<salt>$<hash>`).
 *
 * Mantém as senhas já cadastradas pelo portal Django válidas e permite que as
 * duas aplicações compartilhem o mesmo banco durante a transição: o que o
 * Laravel grava, o Django também consegue validar, e vice-versa.
 */
class DjangoPbkdf2Hasher implements Hasher
{
    public const ALGORITMO = 'pbkdf2_sha256';

    public function __construct(private int $iteracoes = 1000000)
    {
    }

    public function info($hashedValue)
    {
        $partes = $this->decompor($hashedValue);

        return [
            'algo' => $partes ? self::ALGORITMO : null,
            'algoName' => $partes ? self::ALGORITMO : 'unknown',
            'options' => $partes ? ['iterations' => $partes['iteracoes']] : [],
        ];
    }

    public function make($value, array $options = [])
    {
        $iteracoes = (int) ($options['iterations'] ?? $this->iteracoes);
        $salt = Str::random(22);

        return implode('$', [self::ALGORITMO, $iteracoes, $salt, $this->derivar($value, $salt, $iteracoes)]);
    }

    public function check($value, $hashedValue, array $options = [])
    {
        $partes = $this->decompor($hashedValue);
        if ($partes === null || $value === null) {
            return false;
        }

        $calculado = $this->derivar($value, $partes['salt'], $partes['iteracoes']);

        return hash_equals($partes['hash'], $calculado);
    }

    public function needsRehash($hashedValue, array $options = [])
    {
        $partes = $this->decompor($hashedValue);

        return $partes === null || $partes['iteracoes'] < (int) ($options['iterations'] ?? $this->iteracoes);
    }

    /** Usado pelo cast "hashed" do Eloquent para não aplicar hash duas vezes. */
    public function isHashed($value): bool
    {
        return $this->decompor($value) !== null;
    }

    private function derivar(string $senha, string $salt, int $iteracoes): string
    {
        return base64_encode(hash_pbkdf2('sha256', $senha, $salt, $iteracoes, 32, true));
    }

    private function decompor(?string $hash): ?array
    {
        if (! is_string($hash)) {
            return null;
        }
        $partes = explode('$', $hash, 4);
        if (count($partes) !== 4 || $partes[0] !== self::ALGORITMO || ! ctype_digit($partes[1])) {
            return null;
        }

        return ['iteracoes' => (int) $partes[1], 'salt' => $partes[2], 'hash' => $partes[3]];
    }
}
