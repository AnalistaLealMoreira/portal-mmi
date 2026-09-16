from django.contrib import admin

from .models import RedePermitida


@admin.register(RedePermitida)
class RedePermitidaAdmin(admin.ModelAdmin):
    list_display = ("rede", "descricao", "ativo", "criado_em")
    list_filter = ("ativo",)
    search_fields = ("rede", "descricao")
from django.contrib.auth.admin import UserAdmin

from .models import Usuario


@admin.register(Usuario)
class UsuarioAdmin(UserAdmin):
    fieldsets = UserAdmin.fieldsets + (("Papel", {"fields": ("role",)}),)
    list_display = ("username", "email", "first_name", "last_name", "role", "is_staff")
    list_filter = UserAdmin.list_filter + ("role",)
