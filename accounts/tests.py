from django.test import RequestFactory, TestCase
from django.urls import reverse

from .middleware import RestringirAcessoPorRedeMiddleware
from .models import RedePermitida, Usuario


class RedePermitidaTests(TestCase):
	def setUp(self):
		self.admin = Usuario.objects.create_user("admin_rede", password="senha12345", role=Usuario.Role.ADMIN)
		self.diretor = Usuario.objects.create_user("diretor_rede", password="senha12345", role=Usuario.Role.DIRETOR)
		self.normal = Usuario.objects.create_user("normal_rede", password="senha12345", role=Usuario.Role.NORMAL)
		self.factory = RequestFactory()

	def test_formulario_de_rede_normaliza_cidr(self):
		from .forms import RedePermitidaForm

		form = RedePermitidaForm(data={"rede": "192.168.1.25/24", "descricao": "Escritório"})
		self.assertTrue(form.is_valid())
		self.assertEqual(form.cleaned_data["rede"], "192.168.1.0/24")

	def test_usuario_normal_e_bloqueado_fora_da_rede(self):
		request = self.factory.get("/dashboard/", REMOTE_ADDR="203.0.113.20")
		request.user = self.normal
		response = RestringirAcessoPorRedeMiddleware(lambda request: None)(request)
		self.assertEqual(response.status_code, 403)

	def test_usuario_normal_e_liberado_na_rede(self):
		RedePermitida.objects.create(rede="192.168.1.0/24", ativo=True)
		request = self.factory.get("/dashboard/", REMOTE_ADDR="192.168.1.25")
		request.user = self.normal
		response = RestringirAcessoPorRedeMiddleware(lambda request: "ok")(request)
		self.assertEqual(response, "ok")

	def test_diretor_e_admin_nao_dependem_de_rede(self):
		for user in (self.admin, self.diretor):
			request = self.factory.get("/dashboard/", REMOTE_ADDR="203.0.113.20")
			request.user = user
			response = RestringirAcessoPorRedeMiddleware(lambda request: "ok")(request)
			self.assertEqual(response, "ok")

	def test_crud_de_redes_so_permite_admin(self):
		self.client.force_login(self.normal)
		self.assertEqual(self.client.get(reverse("accounts:redes")).status_code, 403)
		self.client.force_login(self.admin)
		self.assertEqual(self.client.get(reverse("accounts:redes")).status_code, 200)
