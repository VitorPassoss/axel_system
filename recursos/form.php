<?php
include '../layout/imports.php';

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
include '../backend/dbconn.php';

if ($conn->connect_error) {
    die("Erro de conexão: " . $conn->connect_error);
}

$user_id = $_SESSION['user_id'];

// Busca os dados completos do usuário logado
$stmt = $conn->prepare("
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
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Usuário não encontrado.");
}

$usuario = $result->fetch_assoc();
$_SESSION['empresa_id'] = $usuario['empresa_id'];
$stmt->close();

$empresa_id = $_SESSION['empresa_id'];

?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Solicitar Compra</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:ital,opsz,wght@0,14..32,100..900;1,14..32,100..900&family=Lato:ital,wght@0,100;0,300;0,400;0,700;0,900;1,100;1,300;1,400;1,700;1,900&family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
    <link rel="icon" type="image/png" href="../assets/logo/il_fullxfull.2974258879_pxm3.webp">

    <style>
        * {
            font-family: "Poppins", sans-serif;
        }
    </style>

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#171717',
                    }
                }
            }
        }
    </script>
</head>

<body class="bg-[#F2F4F7] min-h-screen flex">
    <?php include '../layout/sidemenu.php'; ?>
    <div class="w-full ">
        <div class="relative p-4 md:p-8 animate-fadeIn">

            <header class="bg-white rounded-2xl shadow-lg p-6 mb-10 flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <button onclick="window.location.href='./'" class="text-gray-600 hover:text-primary transition">
                        <i class="fas fa-arrow-left text-xl"></i>
                    </button>
                    <h1 class="text-2xl font-semibold text-gray-800">Solicitar Compra Avulsa</h1>
                </div>
            </header>

            <div class="bg-white p-6 md:p-8 rounded-2xl shadow-lg w-full">
                
                <form id="form-solicitacao" class="w-full space-y-8">
                    
                    <div>
                        <h2 class="text-xl font-bold text-gray-800 mb-4">Detalhes da Solicitação</h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="flex flex-col md:col-span-2">
                                <label for="solicitante" class="text-gray-700 mb-1 text-sm font-medium">Solicitante</label>
                                <input type="text" id="solicitante" name="solicitante" required class="w-full rounded-lg border border-gray-300 p-3 bg-gray-50" />
                            </div>
                            <div class="flex flex-col md:col-span-2">
                                <label for="descricao" class="text-gray-700 mb-1 text-sm font-medium">Motivo / Justificativa</label>
                                <textarea id="descricao" name="descricao" required class="w-full rounded-lg border border-gray-300 p-3 resize-y min-h-[100px]" placeholder="Descreva o motivo da necessidade desta compra..."></textarea>
                            </div>
                        </div>
                    </div>

                    <div>
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
                                <tbody id="itens-table-body">
                                    </tbody>
                            </table>
                        </div>

                        <div class="mt-4">
                            <button type="button" id="btn-adicionar-linha" class="bg-blue-100 text-blue-700 font-semibold py-2 px-4 rounded-lg hover:bg-blue-200 transition-colors">
                                <i class="fas fa-plus mr-2"></i> Adicionar Item
                            </button>
                        </div>
                    </div>
                    
                    <div class="border-t mt-8 pt-6 flex justify-end">
                         <button type="submit" class="bg-primary hover:bg-black text-white font-bold py-3 px-8 rounded-lg shadow-md hover:shadow-lg transition duration-300">
                             Salvar e Enviar Solicitação
                         </button>
                    </div>

                </form>
            </div>
        </div>
    </div>

    <datalist id="insumos-datalist">
        <?php
        $sql = "SELECT nome FROM insumos ORDER BY nome";
        $result = $conn->query($sql);
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                echo "<option value='" . htmlspecialchars($row['nome'], ENT_QUOTES) . "'>";
            }
        }
        ?>
    </datalist>

    <template id="template-tabela-item">
        <tr class="border-b hover:bg-gray-50">
            <td class="p-2 align-top">
                <input name="insumo_nome" list="insumos-datalist" class="w-full rounded-md border-gray-300 p-2 text-sm" placeholder="Digite ou selecione" required>
            </td>
            <td class="p-2 align-top">
                <input type="text" name="unidade" class="w-24 rounded-md border-gray-300 p-2 text-sm" placeholder="Ex: kg, un" required>
            </td>
            <td class="p-2 align-top">
                <input type="number" name="quantidade" class="w-28 rounded-md border-gray-300 p-2 text-sm" placeholder="Qtd" required min="0.01" step="0.01">
            </td>
            <td class="p-2 align-top">
                <select name="grau" required class="w-full rounded-md border-gray-300 p-2 text-sm bg-white">
                    <option value="" disabled selected>Selecione...</option>
                    <option value="Sinistro">🚨 Sinistro</option>
                    <option value="Urgencia">🚨 Urgência</option>
                    <option value="Alta">🔴 Alta</option>
                    <option value="Media">🟠 Média</option>
                    <option value="Baixa">🟡 Baixa</option>
                    <option value="Pouca">🟢 Pouca</option>
                </select>
            </td>
            <td class="p-2 text-center align-middle">
                <button type="button" onclick="removerLinha(this)" class="text-red-500 hover:text-red-700 text-2xl font-bold" title="Remover Item">&times;</button>
            </td>
        </tr>
    </template>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('form-solicitacao');
        const tbody = document.getElementById('itens-table-body');
        const template = document.getElementById('template-tabela-item');
        const btnAdicionarLinha = document.getElementById('btn-adicionar-linha');

        const adicionarNovaLinha = () => {
            const clone = template.content.cloneNode(true);
            tbody.appendChild(clone);
        };
        
        // Adiciona a primeira linha ao carregar a página
        adicionarNovaLinha(); 
        
        // Adiciona nova linha ao clicar no botão
        btnAdicionarLinha.addEventListener('click', adicionarNovaLinha);
        
        // Manipula o envio do formulário
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const itensParaSalvar = [];
            const linhas = tbody.querySelectorAll('tr');

            if (linhas.length === 0) {
                Toastify({ text: "Adicione pelo menos um item à solicitação.", duration: 3000, backgroundColor: "#ef4444" }).showToast();
                return;
            }

            let formularioValido = true;
            linhas.forEach(linha => {
                const insumoNomeInput = linha.querySelector('[name="insumo_nome"]');
                const unidadeInput = linha.querySelector('[name="unidade"]');
                const quantidadeInput = linha.querySelector('[name="quantidade"]');
                const grauInput = linha.querySelector('[name="grau"]');

                // Validação simples
                if (!insumoNomeInput.value.trim() || !unidadeInput.value.trim() || !quantidadeInput.value || !grauInput.value) {
                    formularioValido = false;
                }

                itensParaSalvar.push({
                    insumo_nome: insumoNomeInput.value.trim(),
                    insumo_unidade: unidadeInput.value.trim(),
                    insumo_quantidade: quantidadeInput.value,
                    insumo_grau: grauInput.value
                });
            });
            
            if (!formularioValido) {
                Toastify({ text: "Preencha todos os campos de todos os itens.", duration: 3000, backgroundColor: "#ef4444" }).showToast();
                return;
            }
            
            const body = {
                solicitante: document.getElementById('solicitante').value,
                descricao: document.getElementById('descricao').value,
                insumos: itensParaSalvar, // Mantive o nome 'insumos' para compatibilidade com seu backend
                grau: '', // Grau geral pode ser removido ou calculado se necessário
            };

            console.log("Enviando dados:", body);

            // Envia os dados para o backend via Fetch API
            fetch('./create.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(body)
            })
            .then(res => {
                // Supondo que a resposta sempre será OK, mesmo que haja erro no backend (a ser tratado depois)
                if (res.ok) {
                    Toastify({
                        text: "Solicitação enviada com sucesso!",
                        duration: 3000,
                        gravity: "top", position: "right",
                        backgroundColor: "#10b981",
                        close: true
                    }).showToast();

                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);

                } else {
                   throw new Error('Falha na resposta do servidor.');
                }
            })
            .catch(err => {
                console.error("Erro no envio:", err);
                Toastify({
                    text: "Erro ao enviar a solicitação. Tente novamente.",
                    duration: 3000,
                    gravity: "top", position: "right",
                    backgroundColor: "#ef4444",
                    close: true
                }).showToast();
            });
        });
    });

    // Função global para ser acessada pelo 'onclick' no template
    function removerLinha(button) {
        // Encontra o elemento 'tr' (linha da tabela) mais próximo e o remove
        button.closest('tr').remove();
    }
    </script>

</body>
</html>