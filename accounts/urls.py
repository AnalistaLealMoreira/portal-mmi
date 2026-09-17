from django.urls import path

from . import views

app_name = "accounts"

urlpatterns = [
    path("login/", views.PortalLoginView.as_view(), name="login"),
    path("logout/", views.PortalLogoutView.as_view(), name="logout"),
    path("redes/", views.RedePermitidaListView.as_view(), name="redes"),
    path("redes/nova/", views.RedePermitidaCreateView.as_view(), name="rede_criar"),
    path("redes/<int:pk>/editar/", views.RedePermitidaUpdateView.as_view(), name="rede_editar"),
    path("redes/<int:pk>/excluir/", views.RedePermitidaDeleteView.as_view(), name="rede_excluir"),
]
