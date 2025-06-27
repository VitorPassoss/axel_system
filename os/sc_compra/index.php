<?php
include '../../layout/imports.php';

session_start();

// Função para verificar se o usuário está autenticado
function verificarAutenticacao()
{
    if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
        header("Location: ../onboard/login.php");
        exit();
    }
}

// Chama a função automaticamente
verificarAutenticacao();

// Conexão com o banco
include '../../backend/dbconn.php';

if ($conn->connect_error) {
    die("Erro de conexão: " . $conn->connect_error);
}

$user_id = $_SESSION['user_id'];

// Busca os dados completos do usuário logado
$stmt_user = $conn->prepare("
    SELECT 
     u.id, u.email,  u.is_superuser,
     u.setor_id, u.empresa_id,
     COALESCE(s.nome, '') AS setor_nome,
     COALESCE(e.localizacao, '') AS empresa_nome
    FROM users u
    LEFT JOIN setores s ON u.setor_id = s.id
    LEFT JOIN empresas e ON u.empresa_id = e.id
    WHERE u.id = ?
");
$stmt_user->bind_param("i", $user_id);
$stmt_user->execute();
$result_user = $stmt_user->get_result();

if ($result_user->num_rows === 0) {
    die("Usuário não encontrado.");
}

$usuario = $result_user->fetch_assoc();
$_SESSION['empresa_id'] = $usuario['empresa_id'];
$stmt_user->close();

$empresa_id = $_SESSION['empresa_id'];
$os_id = 0;
$os = null;
$solicitacoes_existentes = [];

// Mantive sc_id para compatibilidade com seus links, mas ele representa o ID da OS aqui.
if (isset($_GET['sc_id'])) {
    $os_id = intval($_GET['sc_id']);

    // Recuperar dados da OS para o cabeçalho e contexto
    $stmt_os = $conn->prepare("SELECT * FROM ordem_de_servico WHERE id = ? AND empresa_id = ?");
    $stmt_os->bind_param("ii", $os_id, $empresa_id);
    $stmt_os->execute();
    $result_os = $stmt_os->get_result();
    $os = $result_os->fetch_assoc();
    $stmt_os->close();
    
    // Busca solicitações existentes para exibir no final da página
    if($os) {
        $stmt_sc = $conn->prepare("SELECT id, descricao, status FROM solicitacao_compras WHERE os_id = ? AND empresa_id = ? ORDER BY id DESC");
        $stmt_sc->bind_param("ii", $os_id, $empresa_id);
        $stmt_sc->execute();
        $result_sc = $stmt_sc->get_result();
        while ($row = $result_sc->fetch_assoc()) {
            $solicitacoes_existentes[] = $row;
        }
        $stmt_sc->close();
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Solicitar Compra</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
    
    <style> * { font-family: "Poppins", sans-serif; } </style>
</head>
<body class="bg-[#F2F4F7] min-h-screen flex">
    <?php include '../../layout/sidemenu_in.php'; ?>
    <div class="w-full">
        <div class="relative p-4 md:p-8 animate-fadeIn">

            <header class="bg-white rounded-2xl shadow-lg p-6 mb-10 flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <?php if ($os): ?>
                        <button onclick="window.location.href='../os/detalhes.php?os_id=<?= htmlspecialchars($os['id']); ?>'" class="text-gray-600 hover:text-primary transition">
                            <i class="fas fa-arrow-left text-xl"></i>
                        </button>
                        <h1 class="text-xl md:text-2xl font-semibold text-gray-800">Solicitar Compra - OS N°<?= htmlspecialchars($os['id']) ?></h1>
                    <?php else: ?>
                        <h1 class="text-xl md:text-2xl font-semibold text-gray-800">Solicitar Compra Avulsa</h1>
                    <?php endif; ?>
                </div>
            </header>

            <div class="bg-white p-6 md:p-8 rounded-2xl shadow-lg w-full">
                
                <h1 class="text-2xl font-bold mb-6 text-gray-800">Criar Nova Solicitação de Compra</h1>
                
                <form id="form-solicitacao-itens" class="w-full">
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                        <div class="flex flex-col md:col-span-2">
                            <label for="solicitante" class="text-gray-700 mb-1 text-sm font-medium">Solicitante</label>
                            <input type="text" id="solicitante" name="solicitante" required class="w-full rounded-lg border border-gray-300 p-3 bg-gray-100 " value=""  />
                        </div>
                        <div class="flex flex-col md:col-span-2">
                            <label for="descricao" class="text-gray-700 mb-1 text-sm font-medium">Descrição / Justificativa Geral da Solicitação</label>
                            <textarea id="descricao" name="descricao" required class="w-full rounded-lg border border-gray-300 p-3 resize-y min-h-[100px]" placeholder=""></textarea>
                        </div>
                        <?php if ($os): ?>
                            <input type="hidden" id="osId" name="osId" value="<?= htmlspecialchars($os['id']) ?>">
                        <?php endif; ?>
                    </div>
                    
                    <h3 class="text-xl font-bold text-primary mb-4">Itens da Solicitação</h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="bg-gray-100">
                                <tr>
                                    <th class="p-3 text-left font-semibold text-gray-700">Insumo</th>
                                    <th class="p-3 text-left font-semibold text-gray-700">Unidade</th>
                                    <th class="p-3 text-left font-semibold text-gray-700">Quantidade</th>
                                    <th class="p-3 text-left font-semibold text-gray-700">Grau de Prioridade</th>
                                    <th class="p-3 text-center font-semibold text-gray-700 w-16">Ação</th>
                                </tr>
                            </thead>
                            <tbody id="itens-table-body"></tbody>
                        </table>
                    </div>

                    <div class="mt-4">
                        <button type="button" id="btn-adicionar-linha" class="bg-blue-100 text-blue-700 font-semibold py-2 px-4 rounded-lg hover:bg-blue-200 transition-colors">
                            + Adicionar Item
                        </button>
                    </div>
                    
                    <div class="border-t mt-8 pt-6 flex justify-end">
                         <button type="submit" class="bg-green-600 hover:bg-green-700 text-white font-bold py-3 px-8 rounded-lg shadow-md hover:shadow-lg transition duration-300">
                            Salvar e Enviar Solicitação
                        </button>
                    </div>
                </form>
            </div>
            <?php if (!empty($solicitacoes_existentes)): ?>
            <div class="mt-10 p-6 bg-white rounded-lg shadow-md">
                <h3 class="text-xl font-semibold mb-6 text-gray-800">Solicitações Anteriores nesta OS</h3>
                <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                     <?php foreach ($solicitacoes_existentes as $sc): ?>
                         <div class="bg-gray-50 border border-gray-200 rounded-xl p-5 hover:shadow-md transition">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-sm text-gray-400">Solicitação #<?= htmlspecialchars($sc['id']) ?></span>
                                <?php
                                $status = strtolower($sc['status']);
                                $statusColor = match ($status) {
                                    'pendente' => 'bg-yellow-100 text-yellow-800', 'aprovado' => 'bg-green-100 text-green-800', 'rejeitado' => 'bg-red-100 text-red-800', default => 'bg-gray-200 text-gray-600',
                                };
                                ?>
                                <span class="text-xs font-medium px-3 py-1 rounded-full <?= $statusColor ?>"><?= ucfirst($sc['status']) ?></span>
                            </div>
                            <p class="text-gray-800 text-base font-medium mb-2 truncate" title="<?= htmlspecialchars($sc['descricao']) ?>"><?= htmlspecialchars($sc['descricao']) ?></p>
                            <a href="../solicitacao_compras/detalhes.php?sc_id=<?= $sc['id'] ?>" class="text-blue-600 hover:underline text-sm font-semibold">Ver Detalhes &rarr;</a>
                         </div>
                     <?php endforeach; ?>
                 </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <datalist id="insumos-datalist">
        <?php
        $sql = "SELECT id, nome FROM insumos ORDER BY nome";
        $result_datalist = $conn->query($sql);
        if ($result_datalist && $result_datalist->num_rows > 0) {
            while ($row = $result_datalist->fetch_assoc()) {
                echo "<option value='" . htmlspecialchars($row['nome'], ENT_QUOTES) . "' data-id='" . $row['id'] . "'>";
            }
        }
        ?>
    </datalist>

    <template id="template-tabela-item">
        <tr class="border-b">
            <td class="p-2 align-top"><input name="insumo_nome" list="insumos-datalist" class="w-full rounded-md border-gray-300 p-2" placeholder="Digite ou selecione" required></td>
            <td class="p-2 align-top"><input type="text" name="und_medida" class="w-24 rounded-md border-gray-300 p-2" placeholder="Ex: kg, un" required></td>
            <td class="p-2 align-top"><input type="number" name="quantidade" class="w-28 rounded-md border-gray-300 p-2" placeholder="Qtd" required min="0.01" step="0.01"></td>
            <td class="p-2 align-top">
                <select name="grau" required class="w-full rounded-md border-gray-300 p-2">
                    <option value="" disabled selected>Selecione...</option>
                    <option value="Sinistro">🚨 Sinistro</option>
                    <option value="Urgencia">🚨 Urgência</option>
                    <option value="Alta">🔴 Alta</option>
                    <option value="Media">🟠 Média</option>
                    <option value="Baixa">🟡 Baixa</option>
                    <option value="Pouca">🟢 Pouca</option>
                </select>
            </td>
            <td class="p-2 text-center align-top"><button type="button" onclick="removerLinha(this)" class="text-red-500 hover:text-red-700 text-2xl font-bold" title="Remover Item">&times;</button></td>
        </tr>
    </template>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('form-solicitacao-itens');
        const tbody = document.getElementById('itens-table-body');
        const template = document.getElementById('template-tabela-item');
        const btnAdicionarLinha = document.getElementById('btn-adicionar-linha');
        const insumosDatalist = document.getElementById('insumos-datalist');

        const adicionarLinha = () => {
            const clone = template.content.cloneNode(true);
            tbody.appendChild(clone);
        };
        
        adicionarLinha(); // Adiciona a primeira linha ao carregar
        
        btnAdicionarLinha.addEventListener('click', adicionarLinha);
        
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            const itensParaSalvar = [];
            const linhas = tbody.querySelectorAll('tr');

            if (linhas.length === 0) {
                alert('Você precisa adicionar pelo menos um item à solicitação.');
                return;
            }

            let hasError = false;
            linhas.forEach(linha => {
                const insumoNomeInput = linha.querySelector('[name="insumo_nome"]');
                const undMedidaInput = linha.querySelector('[name="und_medida"]');
                const quantidadeInput = linha.querySelector('[name="quantidade"]');
                const grauInput = linha.querySelector('[name="grau"]');

                if (!insumoNomeInput.value.trim() || !undMedidaInput.value.trim() || !quantidadeInput.value || !grauInput.value) {
                    hasError = true;
                }

                const option = Array.from(insumosDatalist.options).find(opt => opt.value.toLowerCase() === insumoNomeInput.value.trim().toLowerCase());
                
                const itemData = {
                    und_medida: undMedidaInput.value.trim(),
                    quantidade: quantidadeInput.value,
                    grau: grauInput.value
                };

                if (option) {
                    itemData.insumo_id = option.dataset.id;
                } else {
                    itemData.insumo_nome = insumoNomeInput.value.trim();
                }
                itensParaSalvar.push(itemData);
            });
            
            if (hasError) {
                alert('Por favor, preencha todos os campos de todos os itens antes de salvar.');
                return;
            }
            
            const formData = new FormData(form);
            const osIdInput = document.getElementById('osId');

            const body = {
                solicitante: formData.get('solicitante'),
                descricao: formData.get('descricao'),
                itens: itensParaSalvar
            };
            
            if (osIdInput) {
                body.osId = osIdInput.value;
            }

            // O caminho './create.php' assume que o script está na mesma pasta.
            // Ajuste se necessário. Ex: '../backend/create_solicitacao.php'
            fetch('./create.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(body)
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                     Toastify({ text: "Solicitação enviada com sucesso!", duration: 3000, gravity: "top", position: "right", backgroundColor: "#10b981" }).showToast();
                     setTimeout(() => {
                        const redirectUrl = `../../recursos`;
                        window.location.href = redirectUrl;
                     }, 2000);
                } else {
                     Toastify({ text: "Erro: " + (data.error || "Não foi possível salvar."), duration: 4000, gravity: "top", position: "right", backgroundColor: "#ef4444" }).showToast();
                }
            })
            .catch(err => {
                console.error("Erro no envio:", err);
                Toastify({ text: "Erro de comunicação com o servidor.", duration: 4000, gravity: "top", position: "right", backgroundColor: "#ef4444" }).showToast();
            });
        });
    });

    // Função global para ser acessada pelo 'onclick'
    function removerLinha(button) {
        button.closest('tr').remove();
    }
    </script>
</body>
</html>