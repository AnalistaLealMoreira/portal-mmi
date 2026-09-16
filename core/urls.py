from django.shortcuts import redirect
from django.urls import path

from . import views

app_name = "core"

urlpatterns = [
    path("", lambda request: redirect("accounts:login"), name="home"),
    path("dashboard/", views.DashboardView.as_view(), name="dashboard"),
]
