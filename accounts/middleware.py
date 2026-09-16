import logging

from django.contrib import messages
from django.db import DatabaseError
from django.http import HttpResponseRedirect
from django.shortcuts import render
from django.utils import timezone

from .models import RedePermitida, Usuario

INTERVALO_ATUALIZACAO = 60  # segundos; evita um UPDATE a cada request

logger = logging.getLogger(__name__)


class AtualizarUltimoAcessoMiddleware:
    def __init__(self, get_response):
        self.get_response = get_response

    def __call__(self, request):
        response = self.get_response(request)
        user = getattr(request, "user", None)
        if user is not None and user.is_authenticated:
            agora = timezone.now()
            if not user.last_seen or (agora - user.last_seen).total_seconds() > INTERVALO_ATUALIZACAO:
                Usuario.objects.filter(pk=user.pk).update(last_seen=agora)
        return response


class RestringirAcessoPorRedeMiddleware:
    """Diretores e administradores acessam de qualquer rede.
    Usuários normais só acessam quando o IP pertence a uma rede ativa cadastrada.
    """

    def __init__(self, get_response):
        self.get_response = get_response

    def __call__(self, request):
        user = getattr(request, "user", None)
        if (
            user is not None
            and user.is_authenticated
            and user.is_normal
            and not request.path.startswith("/accounts/logout/")
            and not self.ip_permitido(request)
        ):
            return render(
                request,
                "accounts/acesso_rede_negado.html",
                {"endereco_ip": request.META.get("REMOTE_ADDR", "desconhecido")},
                status=403,
            )
        return self.get_response(request)

    @staticmethod
    def ip_permitido(request):
        endereco_ip = request.META.get("REMOTE_ADDR")
        if not endereco_ip:
            return False
        return any(
            rede.pertence(endereco_ip)
            for rede in RedePermitida.objects.filter(ativo=True).only("rede")
        )


class TratarErroDeBancoMiddleware:
    """Rede de segurança para qualquer CRUD: se uma view deixar subir um erro de
    banco (registro duplicado, chave protegida etc.) sem tratar, evita a página
    de erro do Django e volta para a página anterior com um aviso discreto."""

    def __init__(self, get_response):
        self.get_response = get_response

    def __call__(self, request):
        return self.get_response(request)

    def process_exception(self, request, exception):
        if not isinstance(exception, DatabaseError):
            return None
        logger.exception("Erro de banco de dados não tratado em %s", request.path)
        messages.error(
            request,
            "Não foi possível concluir a operação: os dados informados geram um conflito "
            "(ex.: registro duplicado ou vinculado a outro). Nada foi salvo.",
        )
        destino = request.META.get("HTTP_REFERER") or "/"
        return HttpResponseRedirect(destino)
