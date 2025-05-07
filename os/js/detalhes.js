



function toggleModal() {
    const modal = document.getElementById("adicionarServicoModal");
    modal.classList.toggle("hidden");
}

// Abrir o modal (quando o botão for clicado)
const modalToggleBtns = document.querySelectorAll('[data-modal-toggle="adicionarServicoModal"]');
modalToggleBtns.forEach(btn => {
    btn.addEventListener("click", () => toggleModal()); // Ajustando para chamar sem o evento
});



document.querySelector("form").addEventListener("submit", async function (e) {
    e.preventDefault();


    const urlParams = new URLSearchParams(window.location.search);


    const form = e.target;
    const formData = new FormData(form);

    const url = './update.php';


    try {
        const response = await fetch(url, {
            method: 'POST',
            body: formData
        });

        const result = await response.json();

        if (result.success) {
            Toastify({
                text: "Operação realizada com sucesso!",
                duration: 3000,
                gravity: "top", // "top" ou "bottom"
                position: "right", // "left", "center" ou "right"
                backgroundColor: "#10b981", // Verde (tailwind: bg-green-500)
                close: true
            }).showToast();

            form.reset();

            window.location.href = './index.php'
        } else {
            Toastify({
                text: "Operação com Erro!. Por Favor Consulte o Suporte",
                duration: 3000,
                gravity: "top",
                position: "right",
                backgroundColor: "#ef4444", // Vermelho (tailwind: bg-red-500)
                close: true
            }).showToast();

        }
    } catch (error) {
        alert('Erro na requisição: ' + error.message);
    }
});

document.getElementById("addServicoBtn").addEventListener("click", function (e) {
    e.preventDefault();

    const form = document.getElementById("formAdicionarServico");
    const formData = new FormData(form);

    fetch('./adicionar_servico.php', {
        method: 'POST',
        body: formData
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                window.location.reload()
            } else {
                alert("Erro ao adicionar serviço.");
            }
        })
        .catch(error => {
            console.error("Erro:", error);
            alert("Erro ao enviar dados.");
        });
});

document.addEventListener("click", function (e) {
    if (e.target && e.target.classList.contains('removeServicoBtn')) {
        var servicoDiv = e.target.closest('.servicoItem');
        var servicoId = servicoDiv.dataset.servicoId;

        // Adiciona um log para verificar o ID do serviço
        console.log('Remover serviço com ID: ' + servicoId);

        fetch('remover_servico.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `servico_id=${servicoId}`
        })
            .then(response => response.json())
            .then(data => {
                console.log(data); // Log para depuração

                if (data.success) {
                    // Remover o serviço da interface
                    servicoDiv.remove();

                    // Exibir o toast de sucesso
                    Toastify({
                        text: "Serviço removido com sucesso!",
                        duration: 3000,
                        gravity: "top",
                        position: "right",
                        backgroundColor: "#10b981", // Verde
                        close: true
                    }).showToast();

                    // Atualizar a página após a remoção
                    setTimeout(function () {
                        window.location.reload(); // Atualiza a página
                    }, 3000);
                } else {
                    // Exibir o toast de erro
                    Toastify({
                        text: "Erro ao remover o serviço. Tente novamente.",
                        duration: 3000,
                        gravity: "top",
                        position: "right",
                        backgroundColor: "#ef4444", // Vermelho
                        close: true
                    }).showToast();
                }
            })
            .catch(error => {
                // Exibir o toast de erro se a requisição falhar
                Toastify({
                    text: "Erro de comunicação. Tente novamente.",
                    duration: 3000,
                    gravity: "top",
                    position: "right",
                    backgroundColor: "#ef4444", // Vermelho
                    close: true
                }).showToast();
            });
    }
});
