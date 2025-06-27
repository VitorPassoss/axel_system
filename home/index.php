<?php
include '../backend/auth.php';
include '../layout/imports.php';
include '../backend/dbconn.php'; // Incluindo a conexão com o banco de dados

// --- 1. BUSCA E PROCESSAMENTO DE COTAÇÕES PENDENTES ---

$sql_pendentes = "
    SELECT 
        c.id, c.sc_id, c.status,
        sc.descricao as sc_descricao,
        e.nome as empresa_nome,
        o.nome as obra_nome,
        ct.numero_contrato as contrato_numero,
        ci.id as item_id,
        ci.valor_final as item_valor_final,
        ci.quantidade as item_quantidade,
        ci.und_medida as item_unidade,
        ci.valor_item as item_valor_unitario,
        ci.descricao_tecnica as item_descricao_tecnica,
        ins.nome as insumo_nome,
        f.nome_fantasia as fornecedor_nome
    FROM cotacao c
    JOIN solicitacao_compras sc ON c.sc_id = sc.id
    LEFT JOIN empresas e ON sc.empresa_id = e.id
    LEFT JOIN obras o ON c.obra_id = o.id
    LEFT JOIN contratos ct ON o.contrato_id = ct.id
    LEFT JOIN cotacao_item ci ON ci.cotacao_id = c.id
    LEFT JOIN insumos ins ON ci.insumo_id = ins.id
    LEFT JOIN fornecedores f ON ci.fornecedor_id = f.id
    WHERE c.status = 'pendente'
      AND c.sc_id NOT IN (SELECT sc_id FROM cotacao WHERE status = 'aprovado')
    ORDER BY c.sc_id, c.id
";

$result_pendentes = $conn->query($sql_pendentes);

$cotacoes_agrupadas = [];
if ($result_pendentes && $result_pendentes->num_rows > 0) {
    while ($row = $result_pendentes->fetch_assoc()) {
        $sc_id = $row['sc_id'];
        $cotacao_id = $row['id'];

        // Agrupa os dados principais da cotação, inicializando o valor total
        if (!isset($cotacoes_agrupadas[$sc_id][$cotacao_id])) {
            $cotacoes_agrupadas[$sc_id][$cotacao_id] = [
                'id' => $row['id'],
                'sc_id' => $row['sc_id'],
                'sc_descricao' => $row['sc_descricao'],
                'valor_total' => 0, // **MODIFICAÇÃO: Inicializa o valor total com 0**
                'empresa_nome' => $row['empresa_nome'],
                'obra_nome' => $row['obra_nome'],
                'contrato_numero' => $row['contrato_numero'],
                'itens' => []
            ];
        }

        // Adiciona os itens e SOMA seus valores no valor_total
        if ($row['item_id']) {
            $item_valor_final = (float)$row['item_valor_final'];
            $cotacoes_agrupadas[$sc_id][$cotacao_id]['itens'][] = [
                'insumo_nome' => $row['insumo_nome'],
                'descricao_tecnica' => $row['item_descricao_tecnica'],
                'fornecedor_nome' => $row['fornecedor_nome'],
                'valor_final' => $item_valor_final
            ];
            // **MODIFICAÇÃO: Soma o valor de cada item ao total da cotação**
            $cotacoes_agrupadas[$sc_id][$cotacao_id]['valor_total'] += $item_valor_final;
        }
    }
}

// **MODIFICAÇÃO: Ordena cada grupo de cotações pelo 'valor_total' calculado**
foreach ($cotacoes_agrupadas as $sc_id => &$grupo_cotacoes) {
    uasort($grupo_cotacoes, function ($a, $b) {
        return $a['valor_total'] <=> $b['valor_total'];
    });
}
unset($grupo_cotacoes); // Limpa a referência

// Estrutura final para a renderização dos cards pendentes
$cards_de_aprovacao = [];
foreach ($cotacoes_agrupadas as $sc_id => $grupo_cotacoes) {
    // A primeira cotação do grupo já é a vencedora devido à ordenação anterior
    $vencedora = array_shift($grupo_cotacoes);
    
    $cards_de_aprovacao[] = [
        'winner' => $vencedora,
        'parallel' => array_values($grupo_cotacoes)
    ];
}


// --- 2. BUSCA E PROCESSAMENTO DE COTAÇÕES APROVADAS (NOVO) ---

$sql_aprovadas = "
    SELECT 
        c.id, c.sc_id, c.status, c.aprovado_por,
        sc.descricao as sc_descricao,
        e.nome as empresa_nome,
        o.nome as obra_nome,
        ci.id as item_id,
        ci.valor_final as item_valor_final,
        ins.nome as insumo_nome
    FROM cotacao c
    JOIN solicitacao_compras sc ON c.sc_id = sc.id
    LEFT JOIN empresas e ON sc.empresa_id = e.id
    LEFT JOIN obras o ON c.obra_id = o.id
    LEFT JOIN cotacao_item ci ON ci.cotacao_id = c.id
    LEFT JOIN insumos ins ON ci.insumo_id = ins.id
    WHERE c.status = 'aprovado'
";

$result_aprovadas = $conn->query($sql_aprovadas);
$cotacoes_aprovadas_final = [];
if ($result_aprovadas && $result_aprovadas->num_rows > 0) {
    while ($row = $result_aprovadas->fetch_assoc()) {
        $cotacao_id = $row['id'];

        if (!isset($cotacoes_aprovadas_final[$cotacao_id])) {
            $cotacoes_aprovadas_final[$cotacao_id] = [
                'id' => $row['id'],
                'sc_id' => $row['sc_id'],
                'empresa_nome' => $row['empresa_nome'],
                'obra_nome' => $row['obra_nome'],
                'aprovado_por' => $row['aprovado_por'],
                'valor_total' => 0,
                'itens_preview' => []
            ];
        }

        if ($row['item_id']) {
            $item_valor = (float)$row['item_valor_final'];
            $cotacoes_aprovadas_final[$cotacao_id]['valor_total'] += $item_valor;
            // Adiciona nome do insumo para preview
            if (count($cotacoes_aprovadas_final[$cotacao_id]['itens_preview']) < 2) { // Limita a 2 itens para preview
                 $cotacoes_aprovadas_final[$cotacao_id]['itens_preview'][] = $row['insumo_nome'];
            }
        }
    }
}

?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Início - Aprovações</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
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
                    <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"></path></svg>
                </div>
                <div>
                    <h2 class="text-base sm:text-lg font-medium">Emitir O.S.</h2>
                    <p class="text-xs sm:text-sm text-gray-500">Criar nova ordem de serviço</p>
                </div>
            </a>
            <a href="../os/" class="bg-white hover:shadow-lg transition-all rounded-xl p-4 sm:p-6 flex items-start gap-3 sm:gap-4 border border-gray-200">
                <div class="p-2 sm:p-3 bg-green-100 text-green-600 rounded-full">
                    <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"></path></svg>
                </div>
                <div>
                    <h2 class="text-base sm:text-lg font-medium">Solicitar Compra</h2>
                    <p class="text-xs sm:text-sm text-gray-500">Abrir nova solicitação</p>
                </div>
            </a>
            <a href="../cotacoes" class="bg-white hover:shadow-lg transition-all rounded-xl p-4 sm:p-6 flex items-start gap-3 sm:gap-4 border border-gray-200">
                <div class="p-2 sm:p-3 bg-purple-100 text-purple-600 rounded-full">
                    <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" d="M9 17v-2a4 4 0 00-4-4H3m0 0V7a4 4 0 014-4h10a4 4 0 014 4v4m-4 4h2a4 4 0 014 4v2m0 0H9"></path></svg>
                </div>
                <div>
                    <h2 class="text-base sm:text-lg font-medium">Gerar Cotação</h2>
                    <p class="text-xs sm:text-sm text-gray-500">Criar cotação com base na O.S.</p>
                </div>
            </a>
        </div>

        <div class="space-y-4">
            <h1 class="text-xl sm:text-3xl font-bold text-primary">Aguardando Aprovação</h1>
            <div id="quotation-list" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-3 gap-6">
                
                <?php if (empty($cards_de_aprovacao)): ?>
                    <p class="text-gray-500 text-center col-span-full py-10">Nenhuma cotação pendente encontrada.</p>
                <?php else: ?>
                    <?php foreach ($cards_de_aprovacao as $grupo): ?>
                        <?php 
                            $vencedora = $grupo['winner'];
                            $paralelas = $grupo['parallel'];
                        ?>
                        <div class="bg-white rounded-lg shadow-lg p-5 border-t-4 border-green-500 flex flex-col justify-between">
                            <div>
                                <div class="flex justify-between items-center mb-2">
                                    <h3 class="text-lg font-bold text-gray-800">SC #<?= htmlspecialchars($vencedora['sc_id']) ?></h3>
                                    <span class="text-xs font-bold text-white bg-green-600 px-2 py-1 rounded-full">MELHOR OPÇÃO</span>
                                </div>
                                <h4 class="text-md font-semibold text-gray-700 mb-2">Cotação #<?= htmlspecialchars($vencedora['id']) ?></h4>

                                <?php if (!empty($vencedora['empresa_nome'])): ?><p class="text-sm text-gray-600 mb-1">Empresa: <span class="font-medium"><?= htmlspecialchars($vencedora['empresa_nome']) ?></span></p><?php endif; ?>
                                <?php if (!empty($vencedora['obra_nome'])): ?><p class="text-sm text-gray-600 mb-1">Obra: <span class="font-medium"><?= htmlspecialchars($vencedora['obra_nome']) ?></span></p><?php endif; ?>
                                <?php if (!empty($vencedora['contrato_numero'])): ?><p class="text-sm text-gray-600 mb-3">Contrato: <span class="font-medium"><?= htmlspecialchars($vencedora['contrato_numero']) ?></span></p><?php endif; ?>
                                
                                <p class="text-lg font-semibold text-gray-800 my-3">Valor Total: <span class="text-green-700"><?= 'R$ ' . number_format($vencedora['valor_total'], 2, ',', '.') ?></span></p>

                                <div class="border-t border-gray-200 pt-3 mt-3">
                                    <h4 class="text-md font-semibold mb-2">Itens Principais:</h4>
                                    <div class="space-y-2 max-h-40 overflow-y-auto pr-2">
                                        <?php foreach ($vencedora['itens'] as $item): ?>
                                            <div class="p-2 bg-gray-50 rounded-md border border-gray-200">
                                                <p class="text-sm font-medium text-gray-700"><?= htmlspecialchars($item['insumo_nome'] ?: $item['descricao_tecnica']) ?></p>
                                                <p class="text-xs text-gray-500">Fornecedor: <?= htmlspecialchars($item['fornecedor_nome'] ?: 'N/A') ?></p>
                                                <p class="text-xs font-semibold text-gray-600">Total Item: <?= 'R$ ' . number_format($item['valor_final'], 2, ',', '.') ?></p>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>

                                <?php if (!empty($paralelas)): ?>
                                <div class="border-t border-gray-200 pt-3 mt-3">
                                    <h5 class="text-sm font-semibold text-gray-500 mb-2">Outras Opções Analisadas:</h5>
                                    <ul class="text-xs text-gray-600 space-y-2">
                                        <?php foreach ($paralelas as $p): ?>
                                            <?php
                                                $fornecedores_paralela = !empty($p['itens']) ? array_unique(array_column($p['itens'], 'fornecedor_nome')) : [];
                                                $fornecedores_str = htmlspecialchars(implode(', ', $fornecedores_paralela));
                                            ?>
                                            <li class="p-2 bg-gray-100 rounded-md border border-gray-200 flex justify-between items-center">
                                                <div>
                                                    <span class="font-semibold">Cotação #<?= htmlspecialchars($p['id']) ?></span> - <?= 'R$ ' . number_format($p['valor_total'], 2, ',', '.') ?>
                                                    <span class="block text-xs text-gray-500">Fornecedor(es): <?= $fornecedores_str ?></span>
                                                </div>
                                                <a href="../cotacoes/detalhes.php?sc_id=<?= $p['sc_id'] ?>" 
                                                   target="_blank" 
                                                   class="text-xs bg-gray-600 hover:bg-gray-700 text-white font-semibold px-3 py-1 rounded-md shadow-sm transition-colors">
                                                   Analisar
                                                </a>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                                <?php endif; ?>
                            </div>

                            <div class="mt-4 flex justify-end items-center gap-2 border-t border-gray-200 pt-4">
                                <a href="../cotacoes/detalhes.php?sc_id=<?= $vencedora['sc_id'] ?>"
                                   target="_blank"
                                   class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-md text-sm transition-colors duration-200 flex items-center">
                                    <i class="fas fa-info-circle mr-2"></i> Análise Completa
                                </a>
                                <button onclick="abrirModalCotacao(<?= $vencedora['id'] ?>)" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md text-sm transition-colors duration-200 flex items-center">
                                    <i class="fas fa-check-circle mr-2"></i> Aprovar
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>

            </div>
        </div>
        
        <div class="space-y-4 pt-8">
            <h1 class="text-xl sm:text-3xl font-bold text-primary">Cotações Aprovadas</h1>
            <div id="approved-quotation-list" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-3 gap-6">
                
                <?php if (empty($cotacoes_aprovadas_final)): ?>
                    <p class="text-gray-500 text-center col-span-full py-10">Nenhuma cotação aprovada encontrada.</p>
                <?php else: ?>
                    <?php foreach ($cotacoes_aprovadas_final as $aprovada): ?>
                        <div class="bg-white rounded-lg shadow-md p-5 border-t-4 border-blue-500 flex flex-col justify-between">
                           <div>
                                <div class="flex justify-between items-center mb-2">
                                    <h3 class="text-lg font-bold text-gray-800">SC #<?= htmlspecialchars($aprovada['sc_id']) ?></h3>
                                    <span class="text-xs font-semibold text-white bg-blue-600 px-2 py-1 rounded-full">APROVADA</span>
                                </div>
                                <h4 class="text-md font-semibold text-gray-700 mb-2">Cotação #<?= htmlspecialchars($aprovada['id']) ?></h4>

                                <?php if (!empty($aprovada['empresa_nome'])): ?><p class="text-sm text-gray-600 mb-1">Empresa: <span class="font-medium"><?= htmlspecialchars($aprovada['empresa_nome']) ?></span></p><?php endif; ?>
                                <?php if (!empty($aprovada['obra_nome'])): ?><p class="text-sm text-gray-600 mb-3">Obra: <span class="font-medium"><?= htmlspecialchars($aprovada['obra_nome']) ?></span></p><?php endif; ?>

                                <p class="text-lg font-semibold text-gray-800 my-3">Valor Aprovado: <span class="text-blue-700"><?= 'R$ ' . number_format($aprovada['valor_total'], 2, ',', '.') ?></span></p>
                                
                                <div class="border-t border-gray-200 pt-3 mt-3 text-sm">
                                    <p class="text-gray-600">Aprovado por: <span class="font-medium"><?= htmlspecialchars($aprovada['aprovado_por'] ?: 'N/A') ?></span></p>
                                    <?php if(!empty($aprovada['itens_preview'])): ?>
                                        <p class="text-gray-600 mt-1">Itens: <span class="font-medium"><?= htmlspecialchars(implode(', ', $aprovada['itens_preview'])) . '...' ?></span></p>
                                    <?php endif; ?>
                                </div>
                           </div>
                           <div class="mt-4 flex justify-end items-center gap-2 border-t border-gray-200 pt-4">
                               <a href="../cotacoes/detalhes.php?sc_id=<?= $aprovada['sc_id'] ?>"
                                  target="_blank"
                                  class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-md text-sm transition-colors duration-200 flex items-center">
                                   <i class="fas fa-search mr-2"></i> Ver Detalhes
                               </a>
                           </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>

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
        function abrirModalCotacao(cotacaoId) {
            document.getElementById('modal-id-cotacao').value = cotacaoId;
            document.getElementById('modal-aprovar-cotacao').classList.remove('hidden');
        }

        function fecharModalCotacao() {
            document.getElementById('modal-aprovar-cotacao').classList.add('hidden');
            document.getElementById('input-aprovador-cotacao').value = '';
            document.getElementById('input-senha-cotacao').value = '';
        }

        async function enviarAprovacaoCotacao() {
            const cotacaoId = document.getElementById('modal-id-cotacao').value;
            const aprovador = document.getElementById('input-aprovador-cotacao').value;
            const pin = document.getElementById('input-senha-cotacao').value;

            if (!aprovador || !pin) {
                alert('Por favor, preencha todos os campos.');
                return;
            }

            try {
                const response = await fetch('../cotacoes/aprovar_cotacao.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        id: cotacaoId,
                        aprovador: aprovador,
                        senha: pin
                    })
                });

                const result = await response.json();

                if (result.success) {
                    alert('Cotação aprovada com sucesso!');
                    fecharModalCotacao();
                    window.location.reload()
                } else {
                    alert('Erro ao aprovar cotação: ' + result.message);
                }
            } catch (error) {
                console.error('Erro na requisição de aprovação:', error);
                alert('Ocorreu um erro ao tentar aprovar a cotação. Tente novamente.');
            }
        }
    </script>
</body>

</html>