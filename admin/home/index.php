<?php include '../backend/auth.php'; ?>
<?php include '../layout/imports.php'; ?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Início</title>
    <script src="https://cdn.tailwindcss.com"></script>

    <link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&family=Lato:ital,wght@0,100;0,300;0,400;0,700;0,900;1,100;1,300;1,400;1,700;1,900&family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * {
            font-family: "Poppins", sans-serif;
            font-style: normal;
        }
    </style>
</head>

<body class="bg-[#F2F4F7] min-h-screen flex">

    <?php include '../layout/sidemenu.php'; ?>

    <main class="flex-1 p-4 sm:p-8 space-y-4 sm:space-y-8">
        <div class="flex flex-row justify-between items-center shadow bg-[#FFFFFF] py-4 px-4 sm:py-6 sm:px-6 rounded-2xl">
            <div>
                <h1 class="text-2xl sm:text-3xl font-bold text-primary">Ações Rápidas</h1>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6">

            <a href="../os/form.php" class="bg-white hover:shadow-lg transition-all rounded-xl p-4 sm:p-6 flex items-start gap-3 sm:gap-4 border border-gray-200">
                <div class="p-2 sm:p-3 bg-blue-100 text-blue-600 rounded-full">
                    <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"></path>
                    </svg>
                </div>
                <div>
                    <h2 class="text-base sm:text-lg font-medium">Emitir O.S.</h2>
                    <p class="text-xs sm:text-sm text-gray-500">Criar nova ordem de serviço</p>
                </div>
            </a>

            <a href="../os/" class="bg-white hover:shadow-lg transition-all rounded-xl p-4 sm:p-6 flex items-start gap-3 sm:gap-4 border border-gray-200">
                <div class="p-2 sm:p-3 bg-green-100 text-green-600 rounded-full">
                    <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"></path>
                    </svg>
                </div>
                <div>
                    <h2 class="text-base sm:text-lg font-medium">Solicitar Compra</h2>
                    <p class="text-xs sm:text-sm text-gray-500">Abrir nova solicitação</p>
                </div>
            </a>

            <a href="../cotacoes" class="bg-white hover:shadow-lg transition-all rounded-xl p-4 sm:p-6 flex items-start gap-3 sm:gap-4 border border-gray-200">
                <div class="p-2 sm:p-3 bg-purple-100 text-purple-600 rounded-full">
                    <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 17v-2a4 4 0 00-4-4H3m0 0V7a4 4 0 014-4h10a4 4 0 014 4v4m-4 4h2a4 4 0 014 4v2m0 0H9"></path>
                    </svg>
                </div>
                <div>
                    <h2 class="text-base sm:text-lg font-medium">Gerar Cotação</h2>
                    <p class="text-xs sm:text-sm text-gray-500">Criar cotação com base na O.S.</p>
                </div>
            </a>
        </div>

        <div class="space-y-4">
            <h1 class="text-xl sm:text-3xl font-bold text-primary">Aguardando Aprovação</h1>
            <div id="quotation-list" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-3 gap-4">
                <p class="text-gray-500 text-center col-span-full" id="loading-message">Carregando cotações...</p>
            </div>
        </div>
    </main>

    <div id="modal-aprovar-cotacao" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 hidden p-4">
        <div class="bg-white p-6 rounded-lg shadow-lg w-full max-w-md">
            <h2 class="text-xl font-semibold mb-4 text-gray-800">Aprovar Cotação</h2>
            <input type="hidden" id="modal-id-cotacao">

            <div class="mb-4">
                <label for="input-aprovador-cotacao" class="block text-sm font-medium text-gray-700 mb-1">Nome de quem está aprovando:</label>
                <input type="text" id="input-aprovador-cotacao" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Digite seu nome">
            </div>

            <div class="mb-6">
                <label for="input-senha-cotacao" class="block text-sm font-medium text-gray-700 mb-1">PIN (senha):</label>
                <input type="password" id="input-senha-cotacao" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Digite seu PIN">
            </div>

            <div class="flex justify-end gap-3">
                <button onclick="fecharModalCotacao()" class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-5 py-2 rounded-md transition-colors duration-200">Cancelar</button>
                <button onclick="enviarAprovacaoCotacao()" class="bg-green-600 hover:bg-green-700 text-white px-5 py-2 rounded-md transition-colors duration-200">Aprovar</button>
            </div>
        </div>
    </div>

    <script>
        // Função para formatar valores monetários
        function formatCurrency(value) {
            return new Intl.NumberFormat('pt-BR', {
                style: 'currency',
                currency: 'BRL'
            }).format(value);
        }

        // Função para abrir o modal de aprovação
        function abrirModalCotacao(cotacaoId) {
            document.getElementById('modal-id-cotacao').value = cotacaoId;
            document.getElementById('modal-aprovar-cotacao').classList.remove('hidden');
        }

        // Função para fechar o modal de aprovação
        function fecharModalCotacao() {
            document.getElementById('modal-aprovar-cotacao').classList.add('hidden');
            document.getElementById('input-aprovador-cotacao').value = '';
            document.getElementById('input-senha-cotacao').value = '';
        }

        // Função para enviar a aprovação da cotação
        async function enviarAprovacaoCotacao() {
            const cotacaoId = document.getElementById('modal-id-cotacao').value;
            const aprovador = document.getElementById('input-aprovador-cotacao').value;
            const pin = document.getElementById('input-senha-cotacao').value;

            if (!aprovador || !pin) {
                alert('Por favor, preencha todos os campos.');
                return;
            }

            console.log(`Aprovando cotação ${cotacaoId} por ${aprovador} com PIN ${pin}`);

            try {
                const response = await fetch('../backend/aprovar_cotacao.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        cotacao_id: cotacaoId,
                        aprovador: aprovador,
                        pin: pin
                    })
                });

                const result = await response.json();

                if (result.success) {
                    alert('Cotação aprovada com sucesso!');
                    fecharModalCotacao();
                    loadQuotations(); // Recarrega a lista de cotações para refletir a mudança
                } else {
                    alert('Erro ao aprovar cotação: ' + result.message);
                }
            } catch (error) {
                console.error('Erro na requisição de aprovação:', error);
                alert('Ocorreu um erro ao tentar aprovar a cotação. Tente novamente.');
            }
        }

        // Função para carregar as cotações do backend
        async function loadQuotations() {
            const quotationListDiv = document.getElementById('quotation-list');
            const loadingMessage = document.getElementById('loading-message');
            loadingMessage.classList.remove('hidden');
            quotationListDiv.innerHTML = ''; // Limpa a lista existente

            try {
                const response = await fetch('../backend/get_all_cotacoes.php');
                const data = await response.json();

                if (data.success && data.data.length > 0) {
                    loadingMessage.classList.add('hidden');
                    data.data.forEach(cotacao => {
                        const totalValue = cotacao.itens.reduce((sum, item) => sum + parseFloat(item.valor_final), 0);
                        const cotacaoCard = `
                            <div class="bg-white rounded-lg shadow-md p-4 border border-gray-200 flex flex-col justify-between">
                                <div>
                                    <h3 class="text-lg font-bold text-gray-800 mb-2">Cotação #${cotacao.id}</h3>
                                    <p class="text-sm text-gray-600 mb-1">Status: <span class="font-semibold ${cotacao.status === 'pendente' ? 'text-yellow-600' : 'text-green-600'}">${cotacao.status}</span></p>

                                    ${cotacao.empresa_nome ? `<p class="text-sm text-gray-600 mb-1">Empresa: <span class="font-medium">${cotacao.empresa_nome}</span></p>` : ''}
                                    ${cotacao.obra_nome ? `<p class="text-sm text-gray-600 mb-1">Obra: <span class="font-medium">${cotacao.obra_nome}</span></p>` : ''}
                                    ${cotacao.contrato_numero ? `<p class="text-sm text-gray-600 mb-3">Contrato: <span class="font-medium">${cotacao.contrato_numero}</span></p>` : ''}
                                    
                                    <p class="text-md font-semibold text-gray-700 mb-3">Valor Total: ${formatCurrency(totalValue)}</p>

                                    <div class="border-t border-gray-200 pt-3 mt-3">
                                        <h4 class="text-md font-semibold mb-2">Itens da Cotação:</h4>
                                        <div class="space-y-2 max-h-60 overflow-y-auto pr-2">
                                            ${cotacao.itens.map(item => `
                                                <div class="p-2 bg-gray-50 rounded-md border border-gray-100">
                                                    <p class="text-sm font-medium text-gray-700">${item.insumo_nome || item.descricao_tecnica}</p>
                                                    <p class="text-xs text-gray-500">Fornecedor: ${item.fornecedor_nome || 'Não Informado'}</p>
                                                    <p class="text-xs text-gray-500">Qtd: ${item.quantidade} ${item.und_medida} - Valor Un: ${formatCurrency(item.valor_item)}</p>
                                                    <p class="text-xs font-semibold text-gray-600">Total Item: ${formatCurrency(item.valor_final)}</p>
                                                </div>
                                            `).join('')}
                                        </div>
                                    </div>
                                </div>

                                ${cotacao.status === 'pendente' ? `
                                    <div class="mt-4 flex justify-end">
                                        <button onclick="abrirModalCotacao(${cotacao.id})"
                                                class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md text-sm transition-colors duration-200 flex items-center">
                                            <i class="fas fa-check-circle mr-2"></i> Aprovar Cotação
                                        </button>
                                    </div>
                                ` : `
                                    <div class="mt-4 text-center text-green-700 font-semibold text-sm">
                                        <i class="fas fa-check-double mr-1"></i> Cotação já aprovada!
                                    </div>
                                `}
                            </div>
                        `;
                        quotationListDiv.innerHTML += cotacaoCard;
                    });
                } else {
                    loadingMessage.classList.remove('hidden');
                    loadingMessage.textContent = 'Nenhuma cotação pendente encontrada.';
                }
            } catch (error) {
                console.error('Erro ao carregar cotações:', error);
                loadingMessage.classList.remove('hidden');
                loadingMessage.textContent = 'Erro ao carregar cotações. Tente novamente.';
            }
        }

        // Carrega as cotações quando a página é carregada
        document.addEventListener('DOMContentLoaded', loadQuotations);
    </script>
</body>

</html>