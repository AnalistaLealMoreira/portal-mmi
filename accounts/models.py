from django.contrib.auth.models import AbstractUser
from django.db import models

import ipaddress

from django.core.exceptions import ValidationError

class Usuario(AbstractUser):
    class Role(models.TextChoices):
        ADMIN = "ADMIN", "Administrador"
        ADMIN_EMPRESA = "ADMIN_EMPRESA", "Admin da Empresa"
        DIRETOR = "DIRETOR", "Diretor"
        ESPECIAL = "ESPECIAL", "Usuário Especial"
        NORMAL = "NORMAL", "Usuário Normal"

    role = models.CharField(max_length=20, choices=Role.choices, default=Role.NORMAL)
    last_seen = models.DateTimeField(null=True, blank=True)

    @property
    def is_admin(self):
        return self.role == self.Role.ADMIN

    @property
    def is_admin_empresa(self):
        return self.role == self.Role.ADMIN_EMPRESA

    @property
    def is_diretor(self):
        return self.role == self.Role.DIRETOR

    @property
    def is_especial(self):
        return self.role == self.Role.ESPECIAL

    @property
    def is_normal(self):
        return self.role == self.Role.NORMAL

    def __str__(self):
        return self.get_full_name() or self.username


class RedePermitida(models.Model):
    rede = models.CharField(max_length=43, unique=True)
    descricao = models.CharField(max_length=120, blank=True)
    ativo = models.BooleanField(default=True)
    criado_em = models.DateTimeField(auto_now_add=True)

    class Meta:
        ordering = ["rede"]
        verbose_name = "Rede permitida"
        verbose_name_plural = "Redes permitidas"

    def __str__(self):
        return f"{self.rede}{' - ' + self.descricao if self.descricao else ''}"

    def clean(self):
        try:
            self.rede = str(ipaddress.ip_network(self.rede.strip(), strict=False))
        except ValueError:
            raise ValidationError({"rede": "Informe um IP ou uma rede válida em formato CIDR."})

    def pertence(self, endereco_ip):
        try:
            return ipaddress.ip_address(endereco_ip) in ipaddress.ip_network(self.rede, strict=False)
        except ValueError:
            return False
