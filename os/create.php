<?php
session_start();

// Verifica se a variável de sessão 'empresa_id' existe
if (!isset($_SESSION['empresa_id'])) {
    die("Erro: Empresa não identificada.");
}

$empresa_id = $_SESSION['empresa_id']; // Obtém o empresa_id da sessão

// Conexão com o banco de dados


include '../backend/dbconn.php';

if ($conn->connect_error) {
    die("Conexão falhou: " . $conn->connect_error);
}

// Função de inserção na tabela ordem_de_servico
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $descricao       = $conn->real_escape_string(string: $_POST['descricao'] ?? '');
    $local           = $conn->real_escape_string($_POST['local'] ?? '');
    $numero_os       = $conn->real_escape_string($_POST['numero_os'] ?? '');
    $responsavel_os  = $conn->real_escape_string($_POST['responsavel_os'] ?? '');
    $data_inicio     = isset($_POST['data_inicio']) ? date('Y-m-d', strtotime($_POST['data_inicio'])) : null;
    $data_final      = isset($_POST['data_final']) ? date('Y-m-d', strtotime($_POST['data_final'])) : null;
    $equipe          = $conn->real_escape_string($_POST['equipe'] ?? '');
    $status          = $conn->real_escape_string($_POST['status'] ?? '');
    $obra_id         = $_POST['obra_id'] ?? null;

    // Verificar se obra_id foi fornecido e buscar o contrato_id
    if ($obra_id) {
        $sql_contrato = "SELECT contrato_id FROM obras WHERE id = '$obra_id' LIMIT 1";
        $result = $conn->query($sql_contrato);

        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $contrato_id = $row['contrato_id'];
        } else {
            // Se não encontrar o contrato_id, interromper o processo
            echo json_encode(['success' => false, 'error' => 'Contrato não encontrado para a obra especificada.']);
            exit;
        }
    } else {
        // Se não fornecer obra_id, é necessário tratar o erro
        echo json_encode(['success' => false, 'error' => 'Obra não especificada.']);
        exit;
    }

    // Inserir na tabela ordem_de_servico
    $sql = "INSERT INTO ordem_de_servico (
        empresa_id, contrato_id, obra_id, descricao, local, numero_os,
        responsavel_os, data_inicio, data_final, equipe, status
    ) VALUES (
        '$empresa_id',
        '$contrato_id',
        '$obra_id',
        '$descricao',
        '$local',
        '$numero_os',
        '$responsavel_os',
        " . ($data_inicio ? "'$data_inicio'" : "NULL") . ",
        " . ($data_final ? "'$data_final'" : "NULL") . ",
        '$equipe',
        '$status'
    )";

    if ($conn->query($sql) === TRUE) {
        $os_id = $conn->insert_id; // Pega o ID da OS recém-criada



        // Criar conexão PDO
        include '../backend/db.php';


        // Salvar serviços (espera um array JSON no campo oculto 'servicos')
        if (!empty($_POST['servicos'])) {
            $servicos = json_decode($_POST['servicos'], true);

            if (is_array($servicos)) {
                $stmt = $pdo->prepare("
                    INSERT INTO servicos_os (
                        os_id, servico_id, und_do_servico, quantidade, tipo_servico, executor, dt_inicio, dt_final
                    ) VALUES (
                        :os_id, :servico_id, :und_do_servico, :quantidade, :tipo_servico, :executor, :dt_inicio, :dt_final
                    )
                ");

                foreach ($servicos as $servico) {
                    // Verifica se o serviço já existe
                    $servico_nome = $servico['nome'];
                    $sql_check_servico = "SELECT id FROM servicos WHERE nome = '$servico_nome' LIMIT 1";
                    $result_servico = $conn->query($sql_check_servico);

                    if ($result_servico && $result_servico->num_rows > 0) {
                        // Serviço já existe, obtém o id
                        $row_servico = $result_servico->fetch_assoc();
                        $servico_id = $row_servico['id'];
                    } else {
                        // Serviço não existe, cria um novo
                        $sql_insert_servico = "INSERT INTO servicos (nome) VALUES ('" . $conn->real_escape_string($servico_nome) . "')";
                        if ($conn->query($sql_insert_servico) === TRUE) {
                            $servico_id = $conn->insert_id;
                        } else {
                            // Erro ao inserir o serviço
                            echo json_encode(['success' => false, 'error' => 'Erro ao inserir serviço: ' . $conn->error]);
                            exit;
                        }
                    }

                    // Inserção na tabela servicos_os para cada serviço
                    $stmt->execute([
                        ':os_id' => $os_id,
                        ':servico_id' => $servico_id,
                        ':und_do_servico' => $servico['unidade'] ?? null,
                        ':quantidade' => $servico['quantidade'],
                        ':tipo_servico' => $servico['tipo'] ?? null,
                        ':executor' => $servico['executor'] ?? null,
                        ':dt_inicio' => $servico['data_inicio'] ?? null,
                        ':dt_final' => $servico['data_final'] ?? null,
                    ]);
                }
            }
        }

        // Salvar anexos se existirem
        if (!empty($_FILES['anexos']['name'][0])) {
            salvarAnexos($pdo, 'ordem_de_servico', $os_id, $_FILES['anexos']);
        }

        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => $conn->error]);
    }
    exit;
}

function salvarAnexos(PDO $conn, $tabela_ref, $ref_id, $arquivos)
{
    $pasta_base = "uploads/$tabela_ref/$ref_id/";
    if (!is_dir($pasta_base)) {
        mkdir($pasta_base, 0777, true);
    }

    $total = count($arquivos['name']);
    for ($i = 0; $i < $total; $i++) {
        $nome_original = basename($arquivos['name'][$i]);
        $tmp_name = $arquivos['tmp_name'][$i];
        $erro = $arquivos['error'][$i];

        if ($erro !== UPLOAD_ERR_OK || !is_uploaded_file($tmp_name)) {
            continue;
        }

        $nome_seguro = uniqid() . "_" . preg_replace("/[^a-zA-Z0-9\.\-_]/", "_", $nome_original);
        $caminho_final = $pasta_base . $nome_seguro;

        if (move_uploaded_file($tmp_name, $caminho_final)) {
            $stmt = $conn->prepare("INSERT INTO documentos (tabela_ref, ref_id, nome, caminho_arquivo) VALUES (:tabela_ref, :ref_id, :nome, :caminho)");
            $stmt->execute([
                ':tabela_ref' => $tabela_ref,
                ':ref_id' => $ref_id,
                ':nome' => $nome_original,
                ':caminho' => $caminho_final
            ]);
        }
    }
}
