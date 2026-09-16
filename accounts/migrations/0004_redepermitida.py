from django.db import migrations, models


class Migration(migrations.Migration):
    dependencies = [
        ("accounts", "0003_alter_usuario_role"),
    ]

    operations = [
        migrations.CreateModel(
            name="RedePermitida",
            fields=[
                ("id", models.BigAutoField(auto_created=True, primary_key=True, serialize=False, verbose_name="ID")),
                ("rede", models.CharField(max_length=43, unique=True)),
                ("descricao", models.CharField(blank=True, max_length=120)),
                ("ativo", models.BooleanField(default=True)),
                ("criado_em", models.DateTimeField(auto_now_add=True)),
            ],
            options={
                "ordering": ["rede"],
                "verbose_name": "Rede permitida",
                "verbose_name_plural": "Redes permitidas",
            },
        ),
    ]