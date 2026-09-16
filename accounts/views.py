from django.contrib.auth.views import LoginView, LogoutView
from django.urls import reverse_lazy
from django.views.generic import CreateView, DeleteView, ListView, UpdateView

from .forms import PortalAuthenticationForm, RedePermitidaForm
from .mixins import AdminRequiredMixin
from .models import RedePermitida


class PortalLoginView(LoginView):
    template_name = "accounts/login.html"
    authentication_form = PortalAuthenticationForm

    def form_valid(self, form):
        response = super().form_valid(form)
        if not self.request.POST.get("lembrar"):
            self.request.session.set_expiry(0)
        return response


class PortalLogoutView(LogoutView):
    pass


class RedePermitidaListView(AdminRequiredMixin, ListView):
    model = RedePermitida
    template_name = "accounts/rede_list.html"
    context_object_name = "redes"


class RedePermitidaCreateView(AdminRequiredMixin, CreateView):
    model = RedePermitida
    form_class = RedePermitidaForm
    template_name = "accounts/rede_form.html"
    success_url = reverse_lazy("accounts:redes")


class RedePermitidaUpdateView(AdminRequiredMixin, UpdateView):
    model = RedePermitida
    form_class = RedePermitidaForm
    template_name = "accounts/rede_form.html"
    success_url = reverse_lazy("accounts:redes")


class RedePermitidaDeleteView(AdminRequiredMixin, DeleteView):
    model = RedePermitida
    template_name = "accounts/rede_confirm_delete.html"
    success_url = reverse_lazy("accounts:redes")
