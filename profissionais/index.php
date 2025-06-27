<?php
// arquivo: profissionais.php
include '../backend/auth.php';
include '../layout/imports.php';
include '../backend/dbconn.php';

if ($conn->connect_error) {
    die("Conexão falhou: " . $conn->connect_error);
}

// --- 1. CAPTURAR VALORES DE FILTRO E BUSCA DA URL ---
$searchTerm = $_GET['search'] ?? '';
$filtro_empresa = $_GET['empresa_id'] ?? '';
$filtro_setor = $_GET['setor_id'] ?? '';
$filtro_contrato = $_GET['tipo_contrato'] ?? '';


// --- 2. BUSCAR DADOS PARA POPULAR OS DROPDOWNS DE FILTRO ---
// Empresas
$empresas = [];
$sql_empresas = "SELECT id, nome FROM empresas ORDER BY nome ASC"; // Recomendo usar nome se existir
$result_empresas = $conn->query($sql_empresas);
if ($result_empresas) {
    while ($empresa = $result_empresas->fetch_assoc()) {
        $empresas[] = $empresa;
    }
}

// Setores
$setores = [];
$sql_setores = "SELECT id, nome FROM setores ORDER BY nome ASC";
$result_setores = $conn->query($sql_setores);
if ($result_setores) {
    while ($setor = $result_setores->fetch_assoc()) {
        $setores[] = $setor;
    }
}

// Tipos de contrato (pode ser um array fixo)
$tipos_contrato = ['CLT',  'Temporário', 'Estágio'];


// --- 3. CONSTRUÇÃO DINÂMICA DA CONSULTA SQL PRINCIPAL ---
$sql_base = "SELECT p.*, e.nome AS nome_empresa, s.nome AS nome_setor 
             FROM profissionais p
             LEFT JOIN empresas e ON p.empresa_id = e.id
             LEFT JOIN setores s ON p.setor_id = s.id";

$conditions = [];
$params = [];
$types = "";

if (!empty($searchTerm)) {
    $sanitizedSearchTerm = preg_replace('/[.\-\/]/', '', $searchTerm);
    $conditions[] = "(p.nome LIKE ? OR REPLACE(REPLACE(p.cpf, '.', ''), '-', '') LIKE ?)";
    $params[] = "%" . $searchTerm . "%";
    $params[] = "%" . $sanitizedSearchTerm . "%";
    $types .= "ss";
}
if (!empty($filtro_empresa)) {
    $conditions[] = "p.empresa_id = ?";
    $params[] = $filtro_empresa;
    $types .= "i";
}
if (!empty($filtro_setor)) {
    $conditions[] = "p.setor_id = ?";
    $params[] = $filtro_setor;
    $types .= "i";
}
if (!empty($filtro_contrato)) {
    $conditions[] = "p.tipo_contrato = ?";
    $params[] = $filtro_contrato;
    $types .= "s";
}

$sql = $sql_base;
if (!empty($conditions)) {
    $sql .= " WHERE " . implode(" AND ", $conditions);
}

$stmt = $conn->prepare($sql);
if (!empty($params)) {
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
    <title>Profissionais - Gestão Pro</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body, input, button, th, td, select { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-gray-50 min-h-screen flex">
    <?php include '../layout/sidemenu.php'; ?>
    <main class="flex-1 p-6 lg:p-10 space-y-6">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <h1 class="text-3xl font-bold text-gray-800">Profissionais</h1>
                <p class="text-gray-500 mt-1">Gerencie, busque e adicione novos profissionais.</p>
            </div>
            <a href="./form.php" class="flex items-center gap-2 bg-blue-600 text-white font-semibold py-2.5 px-5 rounded-lg shadow-sm hover:bg-blue-700 transition-all duration-300">
                <i class="fas fa-plus-circle"></i>
                Novo Profissional
            </a>
        </div>

        <div class="bg-white p-4 rounded-lg border border-gray-200">
            <form action="" method="GET" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
                    <div class="lg:col-span-2">
                        <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Buscar por Nome ou CPF</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-3"><i class="fas fa-search text-gray-400"></i></span>
                            <input type="text" name="search" id="search" class="pl-10 pr-4 py-2 border border-gray-300 rounded-lg w-full focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all" value="<?php echo htmlspecialchars($searchTerm); ?>">
                        </div>
                    </div>
                    <div>
                        <label for="empresa_id" class="block text-sm font-medium text-gray-700 mb-1">Empresa</label>
                        <select name="empresa_id" id="empresa_id" class="py-2 pr-8 border border-gray-300 rounded-lg w-full focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all">
                            <option value="">Todas</option>
                            <?php foreach ($empresas as $empresa): ?>
                                <option value="<?php echo $empresa['id']; ?>" <?php echo ($filtro_empresa == $empresa['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($empresa['nome']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label for="setor_id" class="block text-sm font-medium text-gray-700 mb-1">Setor</label>
                        <select name="setor_id" id="setor_id" class="py-2 pr-8 border border-gray-300 rounded-lg w-full focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all">
                            <option value="">Todos</option>
                            <?php foreach ($setores as $setor): ?>
                                <option value="<?php echo $setor['id']; ?>" <?php echo ($filtro_setor == $setor['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($setor['nome']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label for="tipo_contrato" class="block text-sm font-medium text-gray-700 mb-1">Contrato</label>
                        <select name="tipo_contrato" id="tipo_contrato" class="py-2 pr-8 border border-gray-300 rounded-lg w-full focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all">
                            <option value="">Todos</option>
                            <?php foreach ($tipos_contrato as $contrato): ?>
                                <option value="<?php echo $contrato; ?>" <?php echo ($filtro_contrato == $contrato) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($contrato); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="flex justify-end gap-3">
                    <a href="" class="bg-gray-200 text-gray-700 font-medium py-2 px-6 rounded-lg hover:bg-gray-300 transition-colors">Limpar</a>
                    <button type="submit" class="bg-gray-800 text-white font-medium py-2 px-6 rounded-lg hover:bg-gray-900 transition-colors">Filtrar</button>
                </div>
            </form>
        </div>

        <div class="overflow-hidden rounded-lg border border-gray-200 bg-white">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="w-4/12 px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Profissional</th>
                            <th class="w-3/12 px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Empresa</th>
                            <th class="w-2/12 px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Setor</th>
                            <th class="w-1/12 px-6 py-3 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">Contrato</th>
                            <th class="w-2/12 px-6 py-3 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">Salário</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php if ($result->num_rows > 0) : ?>
                            <?php while ($row = $result->fetch_assoc()) : ?>
                                <tr class="hover:bg-blue-50/50 cursor-pointer transition-colors duration-200" onclick="window.location='detalhes.php?id=<?php echo $row['id']; ?>';">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex flex-col">
                                            <span class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($row['nome']); ?></span>
                                            <span class="text-xs text-gray-500"><?php echo htmlspecialchars($row['cpf']); ?></span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600"><?php echo htmlspecialchars($row['nome_empresa'] ?? 'N/A'); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600"><?php echo htmlspecialchars($row['nome_setor'] ?? 'N/A'); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 text-center">
                                        <span class="px-2.5 py-0.5 rounded-full text-xs font-medium 
                                            <?php 
                                                switch($row['tipo_contrato']) {
                                                    case 'CLT': echo 'bg-blue-100 text-blue-800'; break;
                                                    case 'Temporário': echo 'bg-yellow-100 text-yellow-800'; break;
                                                    case 'Estágio': echo 'bg-purple-100 text-purple-800'; break;
                                                    default: echo 'bg-gray-100 text-gray-800';
                                                }
                                            ?>">
                                            <?php echo htmlspecialchars($row['tipo_contrato']); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 font-medium text-right">
                                        <?php echo 'R$ ' . number_format($row['salario'], 2, ',', '.'); ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else : ?>
                            <tr>
                                <td colspan="5" class="text-center py-16 px-6">
                                    <div class="flex flex-col items-center justify-center text-gray-500">
                                        <i class="fas fa-user-slash fa-3x mb-4 text-gray-400"></i>
                                        <h3 class="text-lg font-semibold">Nenhum profissional encontrado</h3>
                                        <p class="text-sm">Tente refinar sua busca ou alterar os filtros.</p>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</body>
</html>
<?php
// Fecha o statement e a conexão
$stmt->close();
$conn->close();
?>