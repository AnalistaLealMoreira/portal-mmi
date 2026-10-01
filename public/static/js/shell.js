(function () {
    "use strict";

    function persistColapso(colapsada) {
        try {
            localStorage.setItem("axion.portal.sidebar", colapsada ? "1" : "0");
        } catch (e) {
            /* localStorage indisponível (modo privado etc.) — só não persiste */
        }
    }

    document.querySelectorAll(".shell-collapse-toggle").forEach(function (btn) {
        btn.addEventListener("click", function () {
            var colapsada = document.documentElement.classList.toggle("shell-collapsed");
            persistColapso(colapsada);
        });
    });

    document.querySelectorAll(".shell-sector-toggle").forEach(function (toggle) {
        toggle.addEventListener("click", function () {
            var panel = document.getElementById(toggle.getAttribute("data-target"));
            if (!panel) return;
            var aberto = panel.hidden;
            panel.hidden = !aberto;
            toggle.setAttribute("aria-expanded", aberto ? "true" : "false");
            var chevron = toggle.querySelector(".shell-sector-chevron");
            if (chevron) {
                chevron.classList.toggle("bi-chevron-up", aberto);
                chevron.classList.toggle("bi-chevron-down", !aberto);
            }
        });
    });

    function configurarDropdown(toggleId, panelId) {
        var toggle = document.getElementById(toggleId);
        var panel = document.getElementById(panelId);
        if (!toggle || !panel) return;

        toggle.addEventListener("click", function (e) {
            e.stopPropagation();
            var abrir = panel.hidden;
            document.querySelectorAll(".shell-empresa-menu, .shell-user-menu-panel").forEach(function (p) {
                p.hidden = true;
            });
            panel.hidden = !abrir;
            toggle.setAttribute("aria-expanded", abrir ? "true" : "false");
        });
    }

    configurarDropdown("shellEmpresaToggle", "shellEmpresaMenu");
    configurarDropdown("shellUserMenuToggle", "shellUserMenuPanel");

    document.addEventListener("click", function (e) {
        document.querySelectorAll(".shell-empresa-menu, .shell-user-menu-panel").forEach(function (panel) {
            if (panel.hidden || panel.contains(e.target)) return;
            panel.hidden = true;
        });
    });
})();

/* Confirmação de exclusão em janela (os links .js-excluir apontam para a página de confirmação como fallback). */
(function () {
    "use strict";
    var modalEl = document.getElementById("modalExcluir");
    if (!modalEl || !window.bootstrap) return;
    var modal = bootstrap.Modal.getOrCreateInstance(modalEl);
    var form = document.getElementById("modalExcluirForm");
    var campo = function (nome) { return modalEl.querySelector('[data-campo="' + nome + '"]'); };

    document.addEventListener("click", function (ev) {
        var gatilho = ev.target.closest(".js-excluir");
        if (!gatilho || !gatilho.dataset.action) return;
        ev.preventDefault();
        var bloqueado = gatilho.dataset.bloqueado === "1";
        form.action = gatilho.dataset.action;
        campo("nome").textContent = '"' + gatilho.dataset.nome + '"';
        campo("aviso").textContent = gatilho.dataset.aviso || "Esta ação não pode ser desfeita.";
        campo("confirmar").hidden = bloqueado;
        modalEl.classList.toggle("is-bloqueado", bloqueado);
        modal.show();
    });
})();

/* Mensagens da sessão em toast. */
(function () {
    "use strict";
    if (!window.bootstrap) return;
    document.querySelectorAll(".toast-portal").forEach(function (el) {
        bootstrap.Toast.getOrCreateInstance(el).show();
    });
})();

/* Dicas (nome do item) no menu lateral, ativas só com a sidebar recolhida no desktop. */
(function () {
    "use strict";
    if (!window.bootstrap) return;
    var dicas = Array.prototype.map.call(document.querySelectorAll(".shell-sidebar [data-dica]"), function (el) {
        return new bootstrap.Tooltip(el, { title: el.dataset.dica, placement: "right", trigger: "hover focus", customClass: "dica-menu" });
    });
    var desktop = window.matchMedia("(min-width: 992px)");
    function atualizar() {
        var ativas = desktop.matches && document.documentElement.classList.contains("shell-collapsed");
        dicas.forEach(function (d) { if (ativas) { d.enable(); } else { d.hide(); d.disable(); } });
    }
    new MutationObserver(atualizar).observe(document.documentElement, { attributes: true, attributeFilter: ["class"] });
    desktop.addEventListener("change", atualizar);
    atualizar();
})();
