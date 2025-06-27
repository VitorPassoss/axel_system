<?php
include '../backend/auth.php';
include '../layout/imports.php';
include '../backend/dbconn.php';

if ($conn->connect_error) {
    die("Conexão falhou: " . $conn->connect_error);
}

// --- LÓGICA DE FILTROS E CONSULTA ---

// Pega o ID da empresa da sessão do usuário como padrão
$empresa_id_sessao = $_SESSION['empresa_id'];

// Verifica se um filtro de empresa foi enviado via GET
$filtro_empresa = $_GET['empresa_id'] ?? '';

// Consulta para popular o dropdown de filtro de empresas
$empresas = $conn->query("SELECT id, nome FROM empresas ORDER BY nome ASC");

// --- CONSTRUÇÃO DA QUERY DINÂMICA ---

// SQL base com JOIN para buscar o nome da empresa (Polo)
$sql = "
    SELECT 
        o.*, 
        s.nome AS status_nome,
        s.cor as status_cor,
        e.nome as nome_empresa -- Campo que representa o Polo
    FROM obras o
    JOIN status_obras s ON o.status_id = s.id
    JOIN empresas e ON o.empresa_id = e.id
";

// Array para os parâmetros da query preparada
$params = [];
$types = "";

// Lógica de filtro para empresa
$where_conditions = [];
if ($filtro_empresa === 'all') {
    // Se 'all', não filtra por empresa
} else if (!empty($filtro_empresa)) {
    // Filtra pelo ID da empresa selecionada
    $where_conditions[] = "o.empresa_id = ?";
    $params[] = intval($filtro_empresa);
    $types .= "i";
} else {
    // Padrão: filtra pela empresa da sessão do usuário
    $where_conditions[] = "o.empresa_id = ?";
    $params[] = $empresa_id_sessao;
    $types .= "i";
}

if (count($where_conditions) > 0) {
    $sql .= " WHERE " . implode(" AND ", $where_conditions);
}

// Ordenação
$sql .= " ORDER BY o.id DESC";

// Prepara e executa a consulta
$stmt = $conn->prepare($sql);
if ($types) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Obras</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        * { font-family: "Poppins", sans-serif; }
    </style>
</head>

<body class="bg-[#F2F4F7] min-h-screen flex">
    <?php include '../layout/sidemenu.php'; ?>

    <div class="flex-1 p-8 space-y-8">
        <div class="flex flex-row justify-between items-center shadow bg-white py-4 px-6 rounded-2xl">
            <h1 class="text-3xl font-bold text-primary">Obras</h1>
            <button onclick="window.location.href='./form.php'" class="mt-4  bg-black  py-2 px-8 rounded-lg font-semibold transition text-white">
            + Adicionar
          </button>
        </div>

        <div class="flex justify-between items-center">
             <button onclick="window.location.href='../quadro/obras_quadro.php'" class="flex items-center gap-2 bg- py-2 px-6 rounded-lg font-semibold border border-gray-300 hover:bg-gray-100 text-gray-800 transition">
                <i class="fas fa-th-large text-gray-600"></i>
                Visualizar Quadro
            </button>
        </div>
        
        <div class="overflow-x-auto rounded-lg shadow-lg bg-white">
            
            <form method="GET" class="p-4 flex items-end gap-4 border-b">
                <div class="flex-1 min-w-[200px]">
                    <label for="empresa_id" class="text-sm font-medium text-gray-700 flex items-center mb-1">
                        <i class="fas fa-building mr-2 text-gray-500"></i>Polo (Empresa)
                    </label>
                    <select name="empresa_id" id="empresa_id" class="w-full p-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                        <option value="" <?= $filtro_empresa === '' ? 'selected' : '' ?>>Minha Empresa</option>
                        <option value="all" <?= $filtro_empresa === 'all' ? 'selected' : '' ?>>Todos os Polos</option>
                        <?php
                        $empresas->data_seek(0);
                        while ($empresa = $empresas->fetch_assoc()) { ?>
                            <option value="<?= $empresa['id'] ?>" <?= $filtro_empresa == $empresa['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($empresa['nome']) ?>
                            </option>
                        <?php } ?>
                    </select>
                </div>

                <button type="submit" class="flex items-center bg-black text-white px-4 py-2 rounded-md hover:bg-gray-800">
                    <i class="fas fa-filter mr-2"></i>Filtrar
                </button>
                <a href="?" class="flex items-center bg-gray-300 text-black px-4 py-2 rounded-md hover:bg-gray-400">
                    Limpar
                </a>
            </form>

            <table class="min-w-full divide-y divide-gray-200">
                <thead>
                    <tr>
                        <th class="px-6 py-3 text-left text-sm border">Nome</th>
                        <th class="px-6 py-3 text-left text-sm border">Polo</th> <th class="px-6 py-3 text-left text-sm border">Status</th>
                        <th class="px-6 py-3 text-left text-sm border">Início</th>
                        <th class="px-6 py-3 text-left text-sm border">Responsável</th>
                        <th class="px-6 py-3 text-left text-sm border">Cliente</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <?php if ($result->num_rows > 0): ?>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <?php $obra_id = $row['id']; ?>
                            <tr class="hover:bg-gray-100 cursor-pointer" onclick="editarObra(<?= $obra_id; ?>)">
                                <td class="px-6 py-4 border text-sm"><?= htmlspecialchars($row['nome']); ?></td>
                                <td class="px-6 py-4 border text-sm font-semibold"><?= htmlspecialchars($row['nome_empresa']); ?></td> <td class="px-6 py-4 border text-sm">
                                    <span style="background-color: <?= htmlspecialchars($row['status_cor']) ?>; color: black;" class="px-3 py-1 rounded-full text-xs font-semibold">
                                        <?= htmlspecialchars($row['status_nome']) ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 border text-sm">
                                    <?= date('d/m/Y', strtotime($row['data_inicio'])); ?>
                                </td>
                                <td class="px-6 py-4 border text-sm"><?= htmlspecialchars($row['responsavel_tecnico']); ?></td>
                                <td class="px-6 py-4 border text-sm"><?= htmlspecialchars($row['cliente']); ?></td>
                            </tr>
                            <tr id="dropdown-<?= $obra_id; ?>" class="hidden bg-gray-50">
                                <td colspan="6" class="px-6 py-4">
                                    <div class="text-sm">
                                        <strong>Solicitações de Compra:</strong>
                                        <div id="solicitacoes-<?= $obra_id; ?>" class="mt-2 text-gray-700">
                                            <em>Carregando...</em>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center py-10 text-gray-500">Nenhuma obra encontrada.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        </div>

    <script>
        function editarObra(id) {
            window.location.href = 'detalhes.php?obra_id=' + id;
        }

        // Suas outras funções JavaScript (toggleDropdown, deleteObra, etc.) continuam aqui
    </script>
</body>
</html>

<?php 
$stmt->close();
$conn->close(); 
?>