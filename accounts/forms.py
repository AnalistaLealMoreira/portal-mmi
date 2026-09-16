from django import forms
from django.contrib.auth.forms import AuthenticationForm
import ipaddress

from .models import RedePermitida, Usuario


class PortalAuthenticationForm(AuthenticationForm):
    username = forms.CharField(
        label="Usuário ou e-mail",
        widget=forms.TextInput(
            attrs={
                "autofocus": True,
                "autocomplete": "username",
                "placeholder": "seu.usuario ou voce@empresa.com",
            }
        ),
    )

    def clean(self):
        username = self.cleaned_data.get("username")
        if username and "@" in username:
            usuario = Usuario.objects.filter(email__iexact=username).first()
            if usuario:
                self.cleaned_data["username"] = usuario.get_username()
        return super().clean()


class RedePermitidaForm(forms.ModelForm):
    class Meta:
        model = RedePermitida
        fields = ["rede", "descricao", "ativo"]
        labels = {
            "rede": "IP ou rede (CIDR)",
            "descricao": "Descrição",
            "ativo": "Ativa",
        }
        help_texts = {
            "rede": "Exemplos: 192.168.1.0/24 ou 200.10.20.30/32",
        }

    def clean_rede(self):
        valor = self.cleaned_data["rede"].strip()
        try:
            return str(ipaddress.ip_network(valor, strict=False))
        except ValueError:
            raise forms.ValidationError("Informe um IP ou uma rede válida em formato CIDR.")