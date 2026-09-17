from django.contrib.auth import views as auth_views
from django.urls import path, reverse_lazy

from . import views

app_name = "accounts"

urlpatterns = [
    path("login/", views.PortalLoginView.as_view(), name="login"),
    path(
        "esqueci-acesso/",
        auth_views.PasswordResetView.as_view(
            template_name="accounts/password_reset_form.html",
            email_template_name="accounts/password_reset_email.txt",
            subject_template_name="accounts/password_reset_subject.txt",
            success_url=reverse_lazy("accounts:password_reset_done"),
        ),
        name="password_reset",
    ),
    path(
        "esqueci-acesso/enviado/",
        auth_views.PasswordResetDoneView.as_view(
            template_name="accounts/password_reset_done.html"
        ),
        name="password_reset_done",
    ),
    path(
        "redefinir-senha/<uidb64>/<token>/",
        auth_views.PasswordResetConfirmView.as_view(
            template_name="accounts/password_reset_confirm.html",
            success_url=reverse_lazy("accounts:password_reset_complete"),
        ),
        name="password_reset_confirm",
    ),
    path(
        "redefinir-senha/concluido/",
        auth_views.PasswordResetCompleteView.as_view(
            template_name="accounts/password_reset_complete.html"
        ),
        name="password_reset_complete",
    ),
    path("logout/", views.PortalLogoutView.as_view(), name="logout"),
    path("redes/", views.RedePermitidaListView.as_view(), name="redes"),
    path("redes/nova/", views.RedePermitidaCreateView.as_view(), name="rede_criar"),
    path("redes/<int:pk>/editar/", views.RedePermitidaUpdateView.as_view(), name="rede_editar"),
    path("redes/<int:pk>/excluir/", views.RedePermitidaDeleteView.as_view(), name="rede_excluir"),
]
