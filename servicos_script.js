document.addEventListener("DOMContentLoaded", () => {

    // ==============================
    // FAVORITOS AJAX
    // ==============================
    document.querySelectorAll(".fav-btn").forEach(btn => {
        btn.addEventListener("click", () => {
            const card = btn.closest(".cat-card");
            const profId = card.dataset.profissional;
            const ativo = btn.classList.contains("ativo") ? 0 : 1;

            fetch("favoritos_ajax.php", {
                    method: "POST",
                    headers: { "Content-Type": "application/x-www-form-urlencoded" },
                    body: `profissional_id=${profId}&acao=${ativo}`
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        btn.classList.toggle("ativo");
                    } else {
                        alert("Erro ao atualizar favoritos");
                    }
                });
        });
    });

    // ==============================
    // CHAT MODAL
    // ==============================
    const modalChat = document.getElementById("modalChat");
    const chatNome = document.getElementById("chatNomeProfissional");
    const chatBox = document.getElementById("chatMensagens");
    const chatForm = document.getElementById("formChat");
    const chatInput = document.getElementById("chat_mensagem");
    const chatProfId = document.getElementById("chat_profissional_id");

    window.abrirChat = (id, nome) => {
        chatProfId.value = id;
        chatNome.textContent = nome;
        chatBox.innerHTML = "";
        modalChat.style.display = "block";
        carregarMensagens();
    }

    window.fecharChat = () => modalChat.style.display = "none";

    chatForm.addEventListener("submit", e => {
        e.preventDefault();
        const msg = chatInput.value.trim();
        if (msg === "") return;

        fetch("chat_ajax.php", {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: `profissional_id=${chatProfId.value}&mensagem=${encodeURIComponent(msg)}`
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    chatBox.innerHTML += `<div class="msg usuario">${msg}</div>`;
                    chatInput.value = "";
                    chatBox.scrollTop = chatBox.scrollHeight;
                }
            });
    });

    function carregarMensagens() {
        const profId = chatProfId.value;
        fetch(`chat_ajax.php?profissional_id=${profId}`)
            .then(res => res.json())
            .then(data => {
                chatBox.innerHTML = "";
                data.mensagens.forEach(msg => {
                    const classe = msg.de === "usuario" ? "usuario" : "profissional";
                    chatBox.innerHTML += `<div class="msg ${classe}">${msg.texto}</div>`;
                });
                chatBox.scrollTop = chatBox.scrollHeight;
            });
    }

    // ==============================
    // SCROLL INFINITO / CARREGAR MAIS
    // ==============================
    let pagina = 1;
    const grid = document.querySelector(".grid-categorias");
    const carregarMais = () => {
        pagina++;
        const categoria = document.querySelector("select[name=categoria]").value;
        const pesquisa = document.querySelector("input[name=pesquisa]").value;

        fetch(`servicos_ajax.php?pagina=${pagina}&categoria=${categoria}&pesquisa=${pesquisa}`)
            .then(res => res.text())
            .then(html => {
                if (html.trim() !== "") {
                    grid.insertAdjacentHTML("beforeend", html);
                } else {
                    window.removeEventListener("scroll", scrollHandler);
                }
            });
    }

    const scrollHandler = () => {
        if (window.innerHeight + window.scrollY >= document.body.offsetHeight - 300) {
            carregarMais();
        }
    }
    window.addEventListener("scroll", scrollHandler);

});