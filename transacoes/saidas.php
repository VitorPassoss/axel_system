<?php
include '../backend/auth.php';
include '../backend/dbconn.php';
include '../layout/imports.php';

if ($conn->connect_error) {
  die("Conexão falhou: " . $conn->connect_error);
}

// Filtros
$filtro_empresa_id = isset($_GET['empresa_id']) ? intval($_GET['empresa_id']) : $_SESSION['empresa_id'];
$filtro_mes = isset($_GET['mes']) ? intval($_GET['mes']) : date('n');
$filtro_ano = isset($_GET['ano']) ? intval($_GET['ano']) : date('Y');

// Consulta com filtros aplicados
$sql = "SELECT t.*, b.nome AS banco, c.nome AS categoria
FROM transacoes t
LEFT JOIN bancos b ON t.banco_id = b.id
LEFT JOIN categorias c ON t.categoria_id = c.id
WHERE t.empresa_id = ?
 AND tipo_transacao = 'saida'
 AND MONTH(t.dt_vencimento) = ?  -- << ALTERADO AQUI
 AND YEAR(t.dt_vencimento) = ?   -- << E AQUI
ORDER BY t.criado_em DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("iii", $filtro_empresa_id, $filtro_mes, $filtro_ano);
$stmt->execute();
$result = $stmt->get_result();

// Carrega os dados para os selects
$empresas = $conn->query("SELECT id, nome FROM empresas");
$bancos = $conn->query("SELECT id, nome FROM bancos");
$categorias = $conn->query("SELECT id, nome FROM categorias");
$setores = $conn->query("SELECT id, nome FROM setores");


?>



<!DOCTYPE html>
<html lang="pt-BR">

<head>
  <meta charset="UTF-8">
  <title>Transações</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      theme: {
        extend: {
          colors: {
            primary: '#1B1E26', // blue-500
          }
        }
      }
    }
  </script>
</head>

<div id="modal-criar" class="fixed inset-0 bg-black bg-opacity-60 flex items-center justify-center z-50 hidden">
  <div class="relative bg-white  p-8 rounded-2xl shadow-2xl w-11/12 md:w-2/3 lg:w-1/2 animate-fadeIn">

    <!-- Botão Fechar -->
    <button onclick="toggleModal()" class="absolute top-4 right-4 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 text-3xl">
      &times;
    </button>

    <h2 class="text-3xl font-bold text-gray-800 dark:text-black mb-6 text-center">Registrar Compra</h2>

    <!-- Formulário -->
    <form id="form-transacao" class="space-y-6">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

        <div class="flex flex-col">
          <label class="text-sm font-medium mb-1 text-gray-700">Descrição</label>
          <input type="text" name="descricao" required class="rounded-lg p-3 border border-gray-300" />
        </div>



        <div class="flex flex-col">
          <label class="text-sm font-medium mb-1 text-gray-700">Status</label>
          <select name="status" class="rounded-lg p-3 border border-gray-300">
            <option value="pendente">Pendente</option>
            <option value="paga">Paga</option>
            <option value="cancelada">Cancelada</option>
            <option value="a vencer">A Vencer</option>
            <option value="em atraso">Em Atraso</option>
          </select>
        </div>

        <div class="flex flex-col">
          <label class="text-sm font-medium mb-1 text-gray-700">Valor</label>
          <input type="text" name="valor" class="rounded-lg p-3 border border-gray-300" id="valor-input" oninput="formatarValor()" />
        </div>

        <!-- Banco -->
        <div class="flex flex-col">
          <label class="text-sm font-medium mb-1 text-gray-700">Banco</label>
          <select name="banco_id" id="select-banco" class="rounded-lg p-3 border border-gray-300">
            <?php while ($banco = $bancos->fetch_assoc()) : ?>
              <option value="<?= $banco['id'] ?>"><?= htmlspecialchars($banco['nome']) ?></option>
            <?php endwhile; ?>
          </select>
        </div>

        <!-- Categoria -->
        <div class="flex flex-col">
          <label class="text-sm font-medium mb-1 text-gray-700">Categoria</label>
          <select name="categoria_id" id="select-categoria" class="rounded-lg p-3 border border-gray-300">
            <?php while ($categoria = $categorias->fetch_assoc()) : ?>
              <option value="<?= $categoria['id'] ?>"><?= htmlspecialchars($categoria['nome']) ?></option>
            <?php endwhile; ?>
          </select>
        </div>

        <div class="flex flex-col">
          <label class="text-sm font-medium mb-1 text-gray-700">Setor</label>
          <select name="setor_id" id="select-setor" class="rounded-lg p-3 border border-gray-300">
            <?php while ($setor = $setores->fetch_assoc()) : ?>
              <option value="<?= $setor['id'] ?>"><?= htmlspecialchars($setor['nome']) ?></option>
            <?php endwhile; ?>
          </select>
        </div>

        <div class="flex flex-col">
          <label class="text-sm font-medium mb-1 text-gray-700">Data de Pagamento</label>
          <input type="date" name="dt_pagamento" class="rounded-lg p-3 border border-gray-300" />
        </div>



        <input class="flex flex-col " type="file" id="anexos" name="anexos[]" multiple />


      </div>

      <div class="flex justify-end pt-4">
        <button type="submit" class="bg-primary text-white px-8 py-3 rounded-lg hover:bg-gray-800">
          Criar
        </button>
      </div>
    </form>

  </div>
</div>


<body class="bg-[#F2F4F7] min-h-screen flex">

  <?php include '../layout/sidemenu.php'; ?>

  <div class="flex-1 p-8 space-y-8">

    <!-- Cabeçalho -->
    <div class="flex flex-row justify-between items-center shadow bg-[#FFFFFF] py-4 px-6 rounded-2xl">
      <h1 class="text-3xl font-bold text-primary">Compras</h1>
      <button onclick="toggleModal()" class="bg-primary py-2 px-8 rounded-lg font-semibold transition text-white">
        + Adicionar Nova
      </button>
    </div>
    <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4 mt-6 bg-white p-4 rounded-xl shadow-sm border border-gray-200">
      <div class="flex flex-col">
        <label class="text-sm font-medium text-gray-600 mb-1">Empresa</label>
        <select name="empresa_id" class="h-10 px-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
          <?php while ($e = $empresas->fetch_assoc()): ?>
            <option value="<?= $e['id'] ?>" <?= $e['id'] == $filtro_empresa_id ? 'selected' : '' ?>>
              <?= $e['nome'] ?>
            </option>
          <?php endwhile; ?>
        </select>
      </div>

      <div class="flex flex-col">
        <label class="text-sm font-medium text-gray-600 mb-1">Mês</label>
        <select name="mes" class="h-10 px-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
          <?php for ($m = 1; $m <= 12; $m++): ?>
            <option value="<?= $m ?>" <?= $m == $filtro_mes ? 'selected' : '' ?>>
              <?= str_pad($m, 2, '0', STR_PAD_LEFT) ?>
            </option>
          <?php endfor; ?>
        </select>
      </div>

      <div class="flex flex-col">
        <label class="text-sm font-medium text-gray-600 mb-1">Ano</label>
        <input type="number" name="ano" value="<?= $filtro_ano ?>" min="2000" max="<?= date('Y') ?>"
          class="h-10 px-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
      </div>

      <div class="flex items-end">
        <button type="submit"
          class="w-full h-10 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition duration-150">
          Filtrar
        </button>
      </div>
    </form>



    <!-- Tabela -->
    <div class="overflow-x-auto rounded-lg shadow-lg bg-white">
      <table class="min-w-full divide-y divide-gray-200">
        <thead>
          <tr>
            <!-- <th class="px-6 py-3 text-left text-sm ">Descrição</th> -->
            <th class="px-6 py-3 text-left text-sm ">Status</th>
            <th class="px-6 py-3 text-left text-sm ">Valor</th>
            <th class="px-6 py-3 text-left text-sm ">Categoria</th>
            <th class="px-6 py-3 text-left text-sm ">Data</th>

          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
          <?php while ($row = $result->fetch_assoc()) : ?>
            <tr class="hover:bg-gray-100 cursor-pointer" onclick="openModal(<?= htmlspecialchars(json_encode($row['id'])) ?>)">
              <td class="px-6 py-4">
                <?php
                $statusClasse = match ($row['status']) {
                  'pendente'   => 'bg-yellow-100 text-yellow-800',
                  'paga'       => 'bg-green-100 text-green-800',
                  'cancelada'  => 'bg-gray-100 text-gray-800',
                  'a vencer'   => 'bg-blue-100 text-blue-800',
                  'em atraso'  => 'bg-red-100 text-red-800',
                  default      => 'bg-gray-100 text-gray-800',
                };
                ?>
                <span class="inline-block px-3 py-1 rounded-full text-xs font-semibold <?= $statusClasse ?>">
                  <?= htmlspecialchars($row['status']) ?>
                </span>
              </td>
              <td class="px-6 py-4">R$ <?= number_format($row['valor'], 2, ',', '.') ?></td>
              <td class="px-6 py-4"><?= htmlspecialchars($row['categoria']) ?></td>
              <td class="px-6 py-4"><?= (new DateTime($row['dt_vencimento']))->format('d/m/Y') ?></td>
            </tr>
          <?php endwhile; ?>
        </tbody>


      </table>
    </div>

  </div>

  <!-- Mover o modal para fora da tabela -->
  <div id="modalTransacao" class="fixed inset-0 hidden bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-lg shadow-xl max-w-3xl w-full max-h-[90vh] overflow-y-auto">
      <div class="sticky top-0 bg-white p-4 border-b flex justify-between items-center">
        <h2 class="text-xl font-bold text-gray-700">Detalhes da Transação</h2>
        <button onclick="closeModal()" class="text-gray-400 hover:text-red-500 text-3xl leading-none">&times;</button>
      </div>
      <div id="modalContent" class="p-6">
      </div>
    </div>
  </div>

</body>


<script>
// Funções auxiliares para formatação (assumindo que você as tem)
function formatarData(data) {
    if (!data) return 'N/A';
    // Exemplo: '2024-12-25 00:00:00' -> '25/12/2024'
    return new Date(data).toLocaleDateString('pt-BR');
}

function formatarMoeda(valor) {
    if (valor === null || valor === undefined) return 'N/A';
    return parseFloat(valor).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
}

function closeModal() {
    document.getElementById('modalTransacao').classList.add('hidden');
}

function openModal(transacaoId) {
    const modal = document.getElementById('modalTransacao');
    const content = document.getElementById('modalContent');

    // 1. Mostra o modal com uma mensagem de "carregando"
    content.innerHTML = '<p class="text-center text-gray-500">Carregando detalhes...</p>';
    modal.classList.remove('hidden');

    // 2. Busca todos os dados da API
    fetch(`./transacao_detalhes_api.php?transacao_id=${transacaoId}`)
        .then(res => res.json())
        .then(data => {
            if (!data.success) {
                content.innerHTML = `<p class="text-center text-red-500">${data.error}</p>`;
                return;
            }

            // 3. Extrai os dados da resposta
            const { transacao, documentos, cotacao } = data;

            // --- CONSTRUÇÃO DO HTML ---
            let html = '';

            // SEÇÃO 1: CABEÇALHO DA NOTA
            let statusClass = 'bg-gray-100 text-gray-800';
            if (transacao.status === 'paga') statusClass = 'bg-green-100 text-green-800';
            if (transacao.status === 'pendente') statusClass = 'bg-yellow-100 text-yellow-800';

            html += `
                <div class="flex justify-between items-start mb-6">
                    <div>
                        <h3 class="font-bold text-lg text-gray-800">${transacao.descricao}</h3>
                        <p class="text-sm text-gray-500">ID da Transação: ${transacao.id}</p>
                    </div>
                    <span class="text-sm font-bold py-1 px-3 rounded-full ${statusClass}">${transacao.status.toUpperCase()}</span>
                </div>
            `;

            // SEÇÃO 2: DETALHES PRINCIPAIS (GRID)
            html += `
                <div class="grid grid-cols-1 md:grid-cols-3 gap-x-6 gap-y-4 mb-6 border-t border-b py-4">
                    <div><strong>Fornecedor:</strong> <br> ${transacao.nome_fornecedor || 'N/A'}</div>
                    <div><strong>Categoria:</strong> <br> ${transacao.nome_categoria || 'N/A'}</div>
                    <div><strong>Origem:</strong> <br> ${transacao.origem || 'N/A'}</div>
                    <div><strong>Vencimento:</strong> <br> ${formatarData(transacao.dt_vencimento)}</div>
                    <div><strong>Valor Principal:</strong> <br> ${formatarMoeda(transacao.valor)}</div>
                </div>
            `;

            // --- SEÇÃO 3: REEMBOLSO (SÓ APARECE SE FOR REEMBOLSO) ---
            // Esta é a lógica que você pediu. Ela verifica se 'is_reembolso' é 1.
            if (transacao.chave_pix_reembolso !== null && transacao.chave_pix_reembolso.length > 0) {
                html += `
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
                        <h4 class="font-bold text-md text-blue-800 mb-2">Detalhes do Reembolso</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                           <p><strong>Motivo:</strong> ${transacao.motivo_reembolso || 'Não especificado'}</p>
                           <p><strong>Chave PIX:</strong> ${transacao.chave_pix_reembolso || 'Não especificada'}</p>
                        </div>
                    </div>
                `;
            }

            // SEÇÃO 4: DOCUMENTOS ANEXADOS
            if (documentos && documentos.length > 0) {
                let docListHTML = documentos.map(doc => `
                    <li class="flex items-center justify-between py-1.5">
                        <a href="${doc.caminho_arquivo.replace('../', '')}" target="_blank" class="flex items-center text-blue-600 hover:underline">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"></path></svg>
                            ${doc.nome}
                        </a>
                    </li>
                `).join('');

                html += `
                    <div class="mb-6">
                        <h4 class="font-bold text-md text-gray-700 mb-2">Documentos Anexados</h4>
                        <ul class="divide-y divide-gray-200">${docListHTML}</ul>
                    </div>
                `;
            }

            // SEÇÃO 5: COTAÇÃO RELACIONADA
            if (cotacao && cotacao.detalhes) {
                let itemsListHTML = cotacao.itens.map(item => `
                    <li>${item.quantidade}x ${item.descricao_tecnica} - ${formatarMoeda(item.valor_final)}</li>
                `).join('');

                html += `
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <h4 class="font-bold text-md text-gray-800 mb-3">Cotação Relacionada (ID: ${cotacao.detalhes.id})</h4>
                        <p><strong>Cotante:</strong> ${cotacao.detalhes.cotante}</p>
                        <p><strong>Descrição:</strong> ${cotacao.detalhes.descricao}</p>
                        <h5 class="font-semibold mt-3 mb-1">Itens da Cotação:</h5>
                        <ul class="list-disc pl-5 text-sm">${itemsListHTML}</ul>
                        <div class="text-right mt-2">
                             <a href="../cotacoes/detalhes.php?sc_id=${cotacao.detalhes.sc_id}" target="_blank" class="text-sm text-blue-600 hover:underline">Ver detalhes completos &rarr;</a>
                        </div>
                    </div>
                `;
            }

            // Finalmente, insere todo o HTML construído no modal de uma só vez
            content.innerHTML = html;

        })
        .catch(error => {
            console.error('Erro ao buscar detalhes da transação:', error);
            content.innerHTML = `<p class="text-center text-red-500">Não foi possível carregar os detalhes. Tente novamente.</p>`;
        });
}
</script>



</html>