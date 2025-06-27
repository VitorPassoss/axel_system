<?php
include '../backend/auth.php'; // Autenticação e dados do usuário
include '../layout/imports.php'; // Imports de CSS/JS
include '../backend/dbconn.php'; // Conexão com o banco

// Verifica se a conexão foi bem-sucedida
if ($conn->connect_error) {
  die("Conexão falhou: " . $conn->connect_error);
}

// --- LÓGICA DE FILTROS E CONSULTA ---

// Pega o ID da empresa da sessão do usuário como padrão
$empresa_id_sessao = $_SESSION['empresa_id'];

// Verifica se um filtro de empresa foi enviado via GET. O valor padrão é uma string vazia.
$filtro_empresa = $_GET['empresa_id'] ?? '';

// Consulta para popular o dropdown de filtro de empresas
$empresas = $conn->query("SELECT id, nome FROM empresas ORDER BY nome ASC");

// --- CONSTRUÇÃO DA QUERY DINÂMICA ---

// SQL base (já inclui o JOIN com empresas para pegar o nome do polo)
$sql = "
    SELECT 
        c.id, 
        c.numero_contrato, 
        c.numero_empenho, 
        c.nome_cliente, 
        e.nome AS nome_empresa, 
        c.situacao,
        c.dt_inicio,
        c.dt_fim
    FROM contratos c
    JOIN empresas e ON c.empresa_id = e.id
";

// Array para os parâmetros da query preparada
$params = [];
$types = ""; // String de tipos para bind_param

// WHERE base para não incluir aditivos na listagem principal
$where_conditions = ["c.aditivo = FALSE"];

// Lógica de filtro atualizada
if ($filtro_empresa === 'all') {
  // Se a opção for 'all', não adiciona nenhuma condição de empresa_id, mostrando todos.
} else if (!empty($filtro_empresa)) {
  // Se um ID de empresa específico for selecionado, filtra por ele.
  $where_conditions[] = "c.empresa_id = ?";
  $params[] = intval($filtro_empresa);
  $types .= "i";
} else {
  // Se nenhum filtro for aplicado (carregamento inicial da página), filtra pela empresa da sessão.
  $where_conditions[] = "c.empresa_id = ?";
  $params[] = $empresa_id_sessao;
  $types .= "i";
}

// Junta as condições do WHERE
if (count($where_conditions) > 0) {
  $sql .= " WHERE " . implode(" AND ", $where_conditions);
}

// Ordenação
$sql .= " ORDER BY c.criado_em DESC";

// Prepara e executa a consulta
$stmt = $conn->prepare($sql);
if ($types) { // Garante que bind_param só seja chamado se houver parâmetros
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
  <title>Contratos</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    * {
      font-family: "Poppins", sans-serif;
    }
  </style>
</head>

<body class="bg-[#F2F4F7] min-h-screen flex">
  <?php include '../layout/sidemenu.php'; ?>

  <div class="flex-1 p-8 space-y-8">
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 shadow bg-white py-4 px-6 rounded-2xl">
      <div>
        <h1 class="text-3xl font-bold text-primary">Contratos</h1>
      </div>
        <button onclick="window.location.href='./form.php'" class="mt-4  bg-black  py-2 px-8 rounded-lg font-semibold transition text-white">
            + Adicionar
          </button>
    </div>

    <div class="overflow-x-auto rounded-lg shadow-lg bg-white">

      <form method="GET" class="p-4 flex items-end gap-4 border-b">
        <div class="flex-1 min-w-[200px]">
          <label for="empresa_id" class="text-sm font-medium text-gray-700 flex items-center mb-1">
            <i class="fas fa-building mr-2 text-gray-500"></i>Polo (Empresa)
          </label>
          <select name="empresa_id" id="empresa_id" class="w-full p-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
            <option value="" <?= $filtro_empresa === '' ? 'selected' : '' ?>>Escolha um Polo</option>
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

        <button type="submit" class="flex items-center bg-black text-white px-4 py-2 rounded-md hover:bg-gray-800 focus:ring-2 focus:ring-gray-500 focus:ring-opacity-50">
          <i class="fas fa-filter mr-2"></i>Filtrar
        </button>
        <a href="?" class="flex items-center bg-gray-300 text-black px-4 py-2 rounded-md hover:bg-gray-400">
          Limpar
        </a>
      </form>

      <table class="min-w-full divide-y divide-gray-200">
        <thead>
          <tr>
            <th class="px-6 py-3 text-left text-sm border">N° Contrato</th>
            <th class="px-6 py-3 text-left text-sm border">Contratante</th>
            <th class="px-6 py-3 text-left text-sm border">Polo</th>
            <th class="px-6 py-3 text-left text-sm border">Situação</th>
            <th class="px-6 py-3 text-left text-sm border">Data Fim</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
          <?php if ($result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
              <tr class="hover:bg-gray-50">
                <td onclick="visualizarContrato(<?= $row['id']; ?>)" class="px-6 py-4 border text-sm"><?= htmlspecialchars($row['numero_contrato']); ?></td>
                <td onclick="visualizarContrato(<?= $row['id']; ?>)" class="px-6 py-4 border text-sm" title="<?= htmlspecialchars($row['nome_cliente']); ?>">
                  <?php
                  $nome_cliente = $row['nome_cliente'];
                  $palavras = explode(' ', $nome_cliente); // Divide o nome em um array de palavras

                  if (count($palavras) > 4) {
                    // Se tiver mais de 4 palavras, pega as 4 primeiras, junta com espaço e adiciona "..."
                    $nome_exibido = implode(' ', array_slice($palavras, 0, 4)) . '...';
                  } else {
                    // Caso contrário, usa o nome completo
                    $nome_exibido = $nome_cliente;
                  }

                  echo htmlspecialchars($nome_exibido);
                  ?>
                </td>
                <td onclick="visualizarContrato(<?= $row['id']; ?>)" class="px-6 py-4 border text-sm font-semibold"><?= htmlspecialchars($row['nome_empresa']); ?></td>
                <td onclick="visualizarContrato(<?= $row['id']; ?>)" class="px-6 py-4 border text-sm">
                  <?php
                  $status = htmlspecialchars($row['situacao']);
                  $class = match (strtolower($status)) {
                    'ativo'     => 'bg-green-100 text-green-800',
                    'inativo'   => 'bg-red-100 text-red-800',
                    'concluido' => 'bg-blue-100 text-blue-800',
                    'suspenso'  => 'bg-yellow-100 text-yellow-800',
                    default     => 'bg-gray-100 text-gray-800',
                  };
                  echo "<span class='px-3 py-1 rounded-full font-medium text-xs {$class}'>{$status}</span>";
                  ?>
                </td>
                <td onclick="visualizarContrato(<?= $row['id']; ?>)" class="px-6 py-4 border text-sm"><?= htmlspecialchars(date('d/m/Y', strtotime($row['dt_fim']))); ?></td>
              </tr>
            <?php endwhile; ?>
          <?php else: ?>
            <tr>
              <td colspan="6" class="text-center py-10 text-gray-500">Nenhum contrato encontrado.</td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <script>
    function toggleModal() {
      window.location.href = './form.php';
    }

    function editarContrato(id) {
      window.location.href = 'form.php?id=' + id;
    }

    function visualizarContrato(id) {
      window.location.href = 'detalhes.php?contrato_id=' + id;
    }

    function deleteContrato(id) {
      if (confirm('Tem certeza que deseja excluir este contrato? A ação não pode ser desfeita.')) {
        const formData = new FormData(document.getElementById('delete-form-' + id));
        fetch('./delete.php', {
            method: 'POST',
            body: formData,
          })
          .then(response => response.json())
          .then(data => {
            if (data.success) {
              alert('Contrato excluído com sucesso!');
              window.location.reload();
            } else {
              alert('Erro ao excluir o contrato: ' + (data.error || 'Erro desconhecido'));
            }
          })
          .catch(error => {
            console.error('Erro na requisição:', error);
            alert('Erro na requisição. Verifique o console para mais detalhes.');
          });
      }
    }
  </script>
</body>

</html>

<?php
$stmt->close();
$conn->close();
?>