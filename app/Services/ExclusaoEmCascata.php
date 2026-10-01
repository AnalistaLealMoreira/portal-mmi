<?php

namespace App\Services;

use App\Models\LinkBi;
use App\Models\Setor;
use App\Models\Usuario;
use Illuminate\Support\Facades\DB;

/**
 * Exclusão em cascata para Setor, LinkBI e Usuário.
 *
 * O Admin (e o Admin de Empresa, dentro da própria empresa) está autorizado a
 * excluir esses três por completo, junto com tudo que depende deles, inclusive
 * o histórico de auditoria (`AcessoLog`), que normalmente é protegido. Estes
 * métodos assumem essa autorização e não devem ser chamados fora de um
 * controller que já validou o papel do usuário.
 */
class ExclusaoEmCascata
{
    public static function link(LinkBi $link): void
    {
        DB::transaction(function () use ($link) {
            $link->acessos()->delete();
            $link->funcionariosLiberados()->detach();
            $link->delete();
        });
    }

    /** Exclui o usuário, o funcionário vinculado, os links que ele criou e o histórico de acesso. */
    public static function usuario(Usuario $usuario): void
    {
        DB::transaction(function () use ($usuario) {
            foreach ($usuario->linksCriados()->get() as $link) {
                self::link($link);
            }
            $usuario->acessos()->delete();
            if ($funcionario = $usuario->funcionario) {
                $funcionario->linksLiberados()->detach();
                $funcionario->delete();
            }
            $usuario->delete();
        });
    }

    /** Exclui o setor, seus links de BI (com logs) e os usuários vinculados a ele. */
    public static function setor(Setor $setor): void
    {
        DB::transaction(function () use ($setor) {
            foreach ($setor->linksBi()->get() as $link) {
                self::link($link);
            }
            foreach ($setor->funcionarios()->with('usuario')->get() as $funcionario) {
                self::usuario($funcionario->usuario);
            }
            $setor->delete();
        });
    }
}
