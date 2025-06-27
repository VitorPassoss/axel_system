

// Função para alternar a visibilidade do modal
function toggleModal() {
    const modal = document.getElementById("adicionarServicoModal");
    modal.classList.toggle("hidden");
}

// Abrir o modal (quando o botão for clicado)
const modalToggleBtns = document.querySelectorAll('[data-modal-toggle="adicionarServicoModal"]');
modalToggleBtns.forEach(btn => {
    btn.addEventListener("click", () => toggleModal()); // Ajustando para chamar sem o evento
});



let servicos = []; // Array para armazenar os serviços adicionados

// Manipulando o envio do formulário da OS
document.querySelector("form").addEventListener("submit", async function (e) {
    e.preventDefault();

    const form = e.target;
    const formData = new FormData(form);

    // Adiciona o array de serviços à FormData
    formData.append("servicos", JSON.stringify(servicos));

    const url = './create.php';

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
                gravity: "top",
                position: "right",
                backgroundColor: "#10b981",
                close: true
            }).showToast();

            form.reset();
            servicos = []; // Limpa os serviços após envio
            window.location.href = './index.php';
        } else {
            Toastify({
                text: "Operação com Erro! Por Favor Consulte o Suporte",
                duration: 3000,
                gravity: "top",
                position: "right",
                backgroundColor: "#ef4444",
                close: true
            }).showToast();
        }
    } catch (error) {
        alert('Erro na requisição: ' + error.message);
    }
});

// Função para adicionar serviço ao array e ao DOM
function adicionarServicoAoDOM(servico) {
    // Adiciona o serviço ao array
    servicos.push(servico);

    const servicosContainer = document.getElementById("servicosContainer");

    const servicoDiv = document.createElement("div");
    servicoDiv.classList.add("bg-[#F3F5F7]", "p-4", "rounded-lg", "flex", "justify-between", "items-center");

    servicoDiv.innerHTML = `
        <div>
            <h3 class="font-semibold text-lg">${servico.nome}</h3>
            <p class="text-sm text-gray-600">Unidade: ${servico.unidade}</p>
            <p class="text-sm text-gray-600">Quantidade: ${servico.quantidade}</p>
            <p class="text-sm text-gray-600">Tipo: ${servico.tipo}</p>
            <p class="text-sm text-gray-600">Executor: ${servico.executor}</p>
            <p class="text-sm text-gray-600">Data Inicio: ${servico.dt_inicio}</p>
        </div>
        <button class="text-red-500 hover:text-red-700" onclick="removerServico(this)">Remover</button>
    `;

    servicosContainer.appendChild(servicoDiv);
}

// Função para remover o serviço da lista
function removerServico(button) {
    const servicoDiv = button.closest("div");
    const servicoNome = servicoDiv.querySelector("h3").textContent;

    // Remove o serviço do array
    servicos = servicos.filter(servico => servico.nome !== servicoNome);

    servicoDiv.remove();
}

document.getElementById("formAdicionarServico").addEventListener("submit", function (event) {
    event.preventDefault(); // Previne o envio normal do formulário

    const inputServico = document.getElementById("servicos");
    const nomeDigitado = inputServico.value;
    const opcoes = document.getElementById("lista-servicos").options;

    let servicoDataSet = {};
    for (let i = 0; i < opcoes.length; i++) {
        if (opcoes[i].value === nomeDigitado) {
            servicoDataSet = opcoes[i].dataset;
            break;
        }
    }

    const servico = {
        nome: nomeDigitado,
        unidade: document.getElementById("und_do_servico").value,
        quantidade: document.getElementById("quantidade").value,
        tipo: document.getElementById("tipo_servico").value,
        executor: document.getElementById("executor").value,
        servicoId: servicoDataSet.servicoId || null,
        categoria: servicoDataSet.categoria || null,
        dt_inicio: document.getElementById("dt_inicio").value,
        dt_final: document.getElementById("dt_final").value,

    };

    adicionarServicoAoDOM(servico);
    toggleModal();
    document.getElementById("formAdicionarServico").reset();
});
