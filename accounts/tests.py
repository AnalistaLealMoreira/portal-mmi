from django.core import mail
from django.test import RequestFactory, TestCase, override_settings
from django.urls import reverse

from .middleware import RestringirAcessoPorRedeMiddleware
from .models import RedePermitida, Usuario


class RedePermitidaTests(TestCase):
	def setUp(self):
		self.admin = Usuario.objects.create_user("admin_rede", password="senha12345", role=Usuario.Role.ADMIN)
		self.diretor = Usuario.objects.create_user("diretor_rede", password="senha12345", role=Usuario.Role.DIRETOR)
		self.especial = Usuario.objects.create_user("especial_rede", password="senha12345", role=Usuario.Role.ESPECIAL)
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
		for user in (self.admin, self.diretor, self.especial):
			request = self.factory.get("/dashboard/", REMOTE_ADDR="203.0.113.20")
			request.user = user
			response = RestringirAcessoPorRedeMiddleware(lambda request: "ok")(request)
			self.assertEqual(response, "ok")

	def test_crud_de_redes_so_permite_admin(self):
		self.client.force_login(self.normal)
		self.assertEqual(self.client.get(reverse("accounts:redes")).status_code, 403)
		self.client.force_login(self.admin)
		self.assertEqual(self.client.get(reverse("accounts:redes")).status_code, 200)


@override_settings(EMAIL_BACKEND="django.core.mail.backends.locmem.EmailBackend")
class RecuperacaoSenhaTests(TestCase):
	def setUp(self):
		self.usuario = Usuario.objects.create_user(
			username="Priscila Martins",
			email="priscila.martins@mminc.com.br",
			password="senha-antiga-123",
		)

	def test_login_direciona_falar_com_mmi_para_email_corporativo(self):
		response = self.client.get(reverse("accounts:login"))
		self.assertContains(
			response,
			"mailto:yuri.antonov@mminc.com.br?subject=Acesso%20ao%20Portal%20MMI",
		)

	def test_solicitacao_de_recuperacao_envia_link_por_email(self):
		response = self.client.post(
			reverse("accounts:password_reset"),
			{"email": "priscila.martins@mminc.com.br"},
		)
		self.assertRedirects(response, reverse("accounts:password_reset_done"))
		self.assertEqual(len(mail.outbox), 1)
		self.assertIn("Redefinição de senha", mail.outbox[0].subject)
		self.assertIn("redefinir a senha", mail.outbox[0].body)

	def test_link_de_recuperacao_permite_definir_nova_senha(self):
		self.client.post(
			reverse("accounts:password_reset"),
			{"email": "priscila.martins@mminc.com.br"},
		)
		link = mail.outbox[0].body.split("http://testserver", 1)[1].split()[0]
		response = self.client.get(link, follow=True)
		self.assertEqual(response.status_code, 200)
		response = self.client.post(
			response.request["PATH_INFO"],
			{"new_password1": "nova-senha-123", "new_password2": "nova-senha-123"},
		)
		self.assertRedirects(response, reverse("accounts:password_reset_complete"))
		self.usuario.refresh_from_db()
		self.assertTrue(self.usuario.check_password("nova-senha-123"))
