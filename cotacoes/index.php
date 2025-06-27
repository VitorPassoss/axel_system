<?php
include '../backend/auth.php';
include '../layout/imports.php';
include '../backend/dbconn.php';

if ($conn->connect_error) {
    die("Conexão falhou: " . $conn->connect_error);
}

// --- LÓGICA DOS FILTROS ---
$empresa_id_sessao = $_SESSION['empresa_id'];
$filtro_empresa_get = $_GET['empresa_id'] ?? null;
$filtro_obra = $_GET['obra_id'] ?? '';
$filtro_contrato = $_GET['contrato_id'] ?? '';
$filtro_periodo = $_GET['periodo'] ?? '';
$empresa_selecionada = ($filtro_empresa_get === null) ? $empresa_id_sessao : $filtro_empresa_get;

// --- DADOS PARA OS DROPDOWNS DE FILTRO ---
$empresas = $conn->query("SELECT id, nome FROM empresas ORDER BY nome");
$empresa_para_dropdowns = !empty($empresa_selecionada) ? intval($empresa_selecionada) : $empresa_id_sessao;
$obras = $conn->query("SELECT id, nome FROM obras WHERE empresa_id = $empresa_para_dropdowns ORDER BY nome");
$contratos_filtro = $conn->query("SELECT id, numero_contrato FROM contratos WHERE empresa_id = $empresa_para_dropdowns ORDER BY numero_contrato");

// --- LÓGICA DE CONSTRUÇÃO DOS FILTROS (WHERE) ---
$params_vinculadas = [];
$types_vinculadas = "";
$where_conditions_vinculadas = ["c.os_id IS NOT NULL"]; // Condição base para esta tabela

$params_avulsas = [];
$types_avulsas = "";
$where_conditions_avulsas = ["c.os_id IS NULL"]; // Condição base para a tabela de avulsas

// Filtro de Empresa (aplica-se a ambas as consultas, se possível)
if (!empty($empresa_selecionada)) {
    // Para vinculadas, filtramos pelo os.empresa_id
    $where_conditions_vinculadas[] = "os.empresa_id = ?";
    $params_vinculadas[] = intval($empresa_selecionada);
    $types_vinculadas .= "i";
    // Para avulsas, o filtro de empresa pode não se aplicar se a empresa está apenas na OS.
    // Se a tabela 'cotacao' tiver 'empresa_id', adicione o filtro aqui.
}

// Filtros de Obra e Contrato (apenas para vinculadas)
if (!empty($filtro_obra)) {
    $where_conditions_vinculadas[] = "os.obra_id = ?";
    $params_vinculadas[] = intval($filtro_obra);
    $types_vinculadas .= "i";
}
if (!empty($filtro_contrato)) {
    $where_conditions_vinculadas[] = "o.contrato_id = ?";
    $params_vinculadas[] = intval($filtro_contrato);
    $types_vinculadas .= "i";
}

// Filtro de Período (aplica-se a ambas)
if (!empty($filtro_periodo)) {
    $period_condition = '';
    if ($filtro_periodo == 'hoje') {
        $period_condition = "DATE(c.dt_criado) = CURDATE()";
    } elseif ($filtro_periodo == 'mes') {
        $period_condition = "YEAR(c.dt_criado) = YEAR(CURDATE()) AND MONTH(c.dt_criado) = MONTH(CURDATE())";
    }
    if ($period_condition) {
        $where_conditions_vinculadas[] = $period_condition;
        $where_conditions_avulsas[] = $period_condition;
    }
}

$where_sql_vinculadas = "WHERE " . implode(" AND ", $where_conditions_vinculadas);
$where_sql_avulsas = "WHERE " . implode(" AND ", $where_conditions_avulsas);

// --- CONSULTA 1: COTAÇÕES VINCULADAS A OBRAS E CONTRATOS ---
$sql_vinculadas = "
WITH RankedCotacoes AS (
    SELECT 
        c.id, c.sc_id, c.os_id, c.cotante, c.descricao, c.status, c.dt_criado,
        COALESCE(o.nome, 'S/N') AS nome_obra,
        COALESCE(con.numero_contrato, 'S/N') AS numero_contrato,
        (SELECT SUM(ci.valor_final) FROM cotacao_item ci WHERE ci.cotacao_id = c.id) AS valor_total,
        (SELECT COUNT(*) FROM transacoes t WHERE t.cotacao_id = c.id) > 0 AS tem_transacao,
        ROW_NUMBER() OVER(PARTITION BY c.sc_id ORDER BY (SELECT SUM(ci.valor_final) FROM cotacao_item ci WHERE ci.cotacao_id = c.id) ASC, c.id ASC) as rn
    FROM 
        cotacao c
    LEFT JOIN ordem_de_servico os ON os.id = c.os_id
    LEFT JOIN obras o ON o.id = os.obra_id
    LEFT JOIN contratos con ON con.id = o.contrato_id
    LEFT JOIN empresas e ON e.id = os.empresa_id
    $where_sql_vinculadas
)
SELECT id, sc_id, os_id, cotante, descricao, status, dt_criado, nome_obra, numero_contrato, valor_total, tem_transacao
FROM RankedCotacoes
WHERE rn = 1 AND sc_id IS NOT NULL
ORDER BY id DESC;
";
$stmt_vinculadas = $conn->prepare($sql_vinculadas);
if (!empty($params_vinculadas)) {
    $stmt_vinculadas->bind_param($types_vinculadas, ...$params_vinculadas);
}
$stmt_vinculadas->execute();
$cotacoes_vinculadas = $stmt_vinculadas->get_result()->fetch_all(MYSQLI_ASSOC);

// --- CONSULTA 2: COTAÇÕES AVULSAS (SEM VÍNCULO) ---
$sql_avulsas = "
WITH RankedCotacoes AS (
    SELECT 
        c.id, c.sc_id, c.cotante, c.descricao, c.status, c.dt_criado,
        (SELECT SUM(ci.valor_final) FROM cotacao_item ci WHERE ci.cotacao_id = c.id) AS valor_total,
        (SELECT COUNT(*) FROM transacoes t WHERE t.cotacao_id = c.id) > 0 AS tem_transacao,
        ROW_NUMBER() OVER(PARTITION BY c.sc_id ORDER BY (SELECT SUM(ci.valor_final) FROM cotacao_item ci WHERE ci.cotacao_id = c.id) ASC, c.id ASC) as rn
    FROM 
        cotacao c
    $where_sql_avulsas
)
SELECT id, sc_id, cotante, descricao, status, dt_criado, valor_total, tem_transacao
FROM RankedCotacoes
WHERE rn = 1 AND sc_id IS NOT NULL
ORDER BY id DESC;
";
$stmt_avulsas = $conn->prepare($sql_avulsas);
// Note: A consulta de avulsas não usa parâmetros no nosso caso, mas o código está aqui por segurança.
if (!empty($params_avulsas)) {
    $stmt_avulsas->bind_param($types_avulsas, ...$params_avulsas);
}
$stmt_avulsas->execute();
$cotacoes_avulsas = $stmt_avulsas->get_result()->fetch_all(MYSQLI_ASSOC);

?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Cotações - Fluxo de Compras</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        * {
            font-family: "Poppins", sans-serif;
            font-style: normal;
        }
    </style>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#171717'
                    }
                }
            }
        }
    </script>
</head>

<body class="bg-[#F2F4F7] min-h-screen flex">
    <?php include '../layout/sidemenu.php'; ?>

    <div class="flex-1 p-8 space-y-8">
        <div class="flex flex-row justify-between items-center shadow bg-white py-6 px-6 rounded-2xl">
            <div>
                <h1 class="text-3xl font-bold text-primary">Cotações</h1>
            </div>
        </div>

        <div class="bg-white p-6 rounded-lg shadow-lg">
            <form method="GET" class="mb-2">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4 items-end">
                    <div>
                        <label for="empresa_id" class="text-sm font-medium text-gray-700 flex items-center gap-2"><i class="fas fa-building text-gray-500"></i>Empresa</label>
                        <select name="empresa_id" id="empresa_id" class="mt-1 block w-full p-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500" onchange="this.form.submit()">
                            <option value="">Todas</option>
                            <?php $empresas->data_seek(0);
                            while ($empresa = $empresas->fetch_assoc()) { ?>
                                <option value="<?= $empresa['id'] ?>" <?= $empresa_selecionada == $empresa['id'] ? 'selected' : '' ?>><?= htmlspecialchars($empresa['nome']) ?></option>
                            <?php } ?>
                        </select>
                    </div>
                    <div>
                        <label for="obra_id" class="text-sm font-medium text-gray-700 flex items-center gap-2"><i class="fas fa-hammer text-gray-500"></i>Obra</label>
                        <select name="obra_id" id="obra_id" class="mt-1 block w-full p-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            <option value="">Todas</option>
                            <?php while ($obra = $obras->fetch_assoc()) { ?>
                                <option value="<?= $obra['id'] ?>" <?= $filtro_obra == $obra['id'] ? 'selected' : '' ?>><?= htmlspecialchars($obra['nome']) ?></option>
                            <?php } ?>
                        </select>
                    </div>
                    <div>
                        <label for="contrato_id" class="text-sm font-medium text-gray-700 flex items-center gap-2"><i class="fas fa-file-contract text-gray-500"></i>Contrato</label>
                        <select name="contrato_id" id="contrato_id" class="mt-1 block w-full p-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            <option value="">Todos</option>
                            <?php while ($contrato = $contratos_filtro->fetch_assoc()) { ?>
                                <option value="<?= $contrato['id'] ?>" <?= $filtro_contrato == $contrato['id'] ? 'selected' : '' ?>><?= htmlspecialchars($contrato['numero_contrato']) ?></option>
                            <?php } ?>
                        </select>
                    </div>
                    <div>
                        <label for="periodo" class="text-sm font-medium text-gray-700 flex items-center gap-2"><i class="fas fa-calendar-alt text-gray-500"></i>Período</label>
                        <select name="periodo" id="periodo" class="mt-1 block w-full p-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                            <option value="" <?= empty($filtro_periodo) ? 'selected' : '' ?>>Todos</option>
                            <option value="hoje" <?= $filtro_periodo == 'hoje' ? 'selected' : '' ?>>Hoje</option>
                            <option value="mes" <?= $filtro_periodo == 'mes' ? 'selected' : '' ?>>Este Mês</option>
                        </select>
                    </div>
                    <div class="flex items-center gap-2">
                        <button type="submit" class="w-full flex justify-center items-center gap-2 bg-black text-white px-4 py-2 rounded-md hover:bg-gray-800"><i class="fas fa-filter"></i> Filtrar</button>
                        <a href="?" class="w-full flex justify-center items-center gap-2 bg-gray-200 text-gray-800 px-4 py-2 rounded-md hover:bg-gray-300" title="Limpar Filtros"><i class="fas fa-times"></i> Limpar</a>
                    </div>
                </div>
            </form>
        </div>

        <div class="overflow-x-auto rounded-lg shadow-lg bg-white p-6">
            <h2 class="text-xl font-bold text-primary mb-4">Cotações de Obras e Contratos</h2>
            <table class="min-w-full divide-y divide-gray-200">
                <thead>
                    <tr>
                        <th class="px-4 py-3 text-left text-sm border">ID</th>

                        <th class="px-4 py-3 text-left text-sm border">Cotante</th>
                        <th class="px-4 py-3 text-left text-sm border">Obra</th>
                        <th class="px-4 py-3 text-left text-sm border">Contrato</th>
                        <th class="px-4 py-3 text-left text-sm border">O.S</th>
                        <th class="px-4 py-3 text-left text-sm border">Status</th>
                        <th class="px-4 py-3 text-left text-sm border">Valor Total</th>
                        <th class="px-4 py-3 text-center text-sm w-[160px] border">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <?php if (count($cotacoes_vinculadas) > 0): ?>
                        <?php foreach ($cotacoes_vinculadas as $row) {
                            $id = $row['id'];

                            // Verifica se a cotação tem ao menos uma transação
                            $stmt_trans = $conn->prepare("SELECT COUNT(*) as total FROM transacoes WHERE cotacao_id = ?");
                            $stmt_trans->bind_param("i", $id);
                            $stmt_trans->execute();
                            $result_trans = $stmt_trans->get_result();
                            $temTransacao = false;
                            if ($transacao = $result_trans->fetch_assoc()) {
                                $temTransacao = $transacao['total'] > 0;
                            }
                            $stmt_trans->close();
                            $temTransacao = $row['tem_transacao'];

                            // Determina o status real
                            $status_real = $temTransacao ? 'PAGO' : strtoupper($row['status']);
                            $statusDisplay = $status_real; // Texto padrão a ser exibido

                            // Lógica para ajustar o texto e a cor do status
                            if ($status_real == 'APROVADO') {
                                $statusDisplay = 'AGUARDANDO COMPRA';
                                $bgColor = 'bg-indigo-100 text-indigo-800';
                            } else {
                                $bgColor = match ($status_real) {
                                    'PENDENTE'  => 'bg-blue-100 text-blue-800',
                                    'REJEITADO' => 'bg-red-100 text-red-800',
                                    'PAGO'      => 'bg-green-200 text-green-900',
                                    default     => 'bg-gray-100 text-gray-800',
                                };
                            }
                        ?>
                            <tr class="hover:bg-gray-50 cursor-pointer" onclick="toggleDropdown('v-<?= $row['id'] ?>')">
                                <td class="px-4 py-2 text-sm border"><?= htmlspecialchars($row['id'] ?? '-') ?></td>

                                <td class="px-4 py-2 text-sm border"><?= htmlspecialchars($row['cotante'] ?? '-') ?></td>
                                <td class="px-4 py-2 text-sm border"><?= htmlspecialchars($row['nome_obra'] ?? '-') ?></td>
                                <td class="px-4 py-2 text-sm border"><?= htmlspecialchars($row['numero_contrato'] ?? '-') ?></td>
                                <td class="px-4 py-2 text-sm border"><?= htmlspecialchars($row['os_id'] ?? '-') ?></td>
                                <td class="px-4 py-2 text-sm border"><span class="px-3 py-1 rounded-full text-[12px] font-semibold <?= $bgColor ?>"><?= $statusDisplay ?></span></td>
                                <td class="px-4 py-2 text-sm border">R$ <?= number_format($row['valor_total'] ?? 0, 2, ',', '.') ?></td>
                                <td class="px-4 py-2 text-center text-sm border">
                                    <button onclick="event.stopPropagation(); window.location.href='./detalhes.php?cotacao_id=<?= $row['id'] ?>&sc_id=<?= $row['sc_id'] ?>'" class="bg-green-600 text-[10px] p-2 rounded text-white">Ver Detalhes</button>
                                </td>
                            </tr>
                            <tr id="dropdown-v-<?= $row['id'] ?>" class="hidden bg-gray-50 border">
                                <td colspan="7" class="px-6 py-4"><strong>Descrição:</strong>
                                    <div class="mt-2 text-gray-700"><?= nl2br(htmlspecialchars($row['descricao'] ?? 'Sem descrição.')) ?></div>
                                </td>
                            </tr>
                        <?php } ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center py-10 text-gray-500">Nenhuma cotação vinculada a obras foi encontrada.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="overflow-x-auto rounded-lg shadow-lg bg-white p-6">
            <h2 class="text-xl font-bold text-primary mb-4">Cotações Avulsas</h2>
            <table class="min-w-full divide-y divide-gray-200">
                <thead>
                    <tr>
                        <th class="px-4 py-3 text-left text-sm border">ID</th>

                        <th class="px-4 py-3 text-left text-sm border">Cotante</th>
                        <th class="px-4 py-3 text-left text-sm border">descrição</th>
                        <th class="px-4 py-3 text-left text-sm border">Status</th>

                        <th class="px-4 py-3 text-left text-sm border">Valor Total</th>
                        <th class="px-4 py-3 text-center text-sm w-[160px] border">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <?php if (count($cotacoes_avulsas) > 0): ?>
                        <?php foreach ($cotacoes_avulsas as $row) {
                            // A lógica de status pode não ser mais necessária aqui se a coluna foi removida,
                            // mas mantemos caso seja usada para algo no futuro.
                            $id = $row['id'];

                            // Verifica se a cotação tem ao menos uma transação
                            $stmt_trans = $conn->prepare("SELECT COUNT(*) as total FROM transacoes WHERE cotacao_id = ?");
                            $stmt_trans->bind_param("i", $id);
                            $stmt_trans->execute();
                            $result_trans = $stmt_trans->get_result();
                            $temTransacao = false;
                            if ($transacao = $result_trans->fetch_assoc()) {
                                $temTransacao = $transacao['total'] > 0;
                            }
                            $stmt_trans->close();

                            $status_real = $temTransacao ? 'PAGO' : strtoupper($row['status']);
                            $statusDisplay = $status_real; // Texto padrão a ser exibido

                            // Lógica para ajustar o texto e a cor do status
                            if ($status_real == 'APROVADO') {
                                $statusDisplay = 'AGUARDANDO COMPRA';
                                $bgColor = 'bg-indigo-100 text-indigo-800';
                            } else {
                                $bgColor = match ($status_real) {
                                    'PENDENTE'  => 'bg-blue-100 text-blue-800',
                                    'REJEITADO' => 'bg-red-100 text-red-800',
                                    'PAGO'      => 'bg-green-200 text-green-900',
                                    default     => 'bg-gray-100 text-gray-800',
                                };
                            }
                        ?>
                            <tr class="hover:bg-gray-50 cursor-pointer" onclick="toggleDropdown('a-<?= $row['id'] ?>')">
                                <td class="px-4 py-2 text-sm border"><?= htmlspecialchars($row['id'] ?? '-') ?></td>

                                <td class="px-4 py-2 text-sm border"><?= htmlspecialchars($row['cotante'] ?? '-') ?></td>

                                <td class="px-4 py-2 text-sm border">
                                    <?php
                                    $descricao = $row['descricao'] ?? '';
                                    if (!empty($descricao)) {
                                        $palavras = explode(' ', $descricao); // Quebra a string em palavras
                                        if (count($palavras) > 5) {
                                            // Pega as 5 primeiras palavras, junta e adiciona "..."
                                            echo htmlspecialchars(implode(' ', array_slice($palavras, 0, 5)) . '...');
                                        } else {
                                            // Mostra a descrição completa se tiver 5 palavras ou menos
                                            echo htmlspecialchars($descricao);
                                        }
                                    } else {
                                        echo '-'; // Mostra um hífen se não houver descrição
                                    }
                                    ?>
                                </td>
                                <td class="px-4 py-2 text-sm border">
                                    <span class="px-3 py-1 rounded-full text-[12px] font-semibold <?= $bgColor ?>">
                                        <?= $statusDisplay ?>
                                    </span>
                                </td>
                                <td class="px-4 py-2 text-sm border">R$ <?= number_format($row['valor_total'] ?? 0, 2, ',', '.') ?></td>
                                <td class="px-4 py-2 text-center text-sm border">
                                    <button onclick="event.stopPropagation(); window.location.href='./detalhes.php?cotacao_id=<?= $row['id'] ?>&sc_id=<?= $row['sc_id'] ?>'" class="bg-green-600 text-[10px] p-2 rounded text-white">Ver Detalhes</button>
                                </td>
                            </tr>
                            <tr id="dropdown-a-<?= $row['id'] ?>" class="hidden bg-gray-50 border">
                                <td colspan="4" class="px-6 py-4"><strong>Descrição:</strong>
                                    <div class="mt-2 text-gray-700"><?= nl2br(htmlspecialchars($row['descricao'] ?? 'Sem descrição.')) ?></div>
                                </td>
                            </tr>
                        <?php } ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" class="text-center py-10 text-gray-500">Nenhuma cotação avulsa foi encontrada.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        function toggleDropdown(id) {
            document.getElementById('dropdown-' + id).classList.toggle('hidden');
        }
    </script>
</body>

</html>
<?php
$stmt_vinculadas->close();
$stmt_avulsas->close();
$conn->close();
?>