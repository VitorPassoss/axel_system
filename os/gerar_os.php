<?php
session_start();
require_once __DIR__ . '/../vendor/autoload.php';

if (!isset($_SESSION['empresa_id'])) {
    die("Erro: Empresa não identificada.");
}

if (!isset($_GET['id'])) {
    die("Erro: OS não especificada.");
}

$empresa_id = $_SESSION['empresa_id'];
$os_id = intval($_GET['id']);

$host = 'localhost';
$dbname = 'axel_db';
$username = 'root';
$password = '';

// Conexão com banco
$conn = new mysqli($host, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Erro de conexão: " . $conn->connect_error);
}

$sql = "SELECT os.*, ob.nome AS nome_obra, c.numero_contrato
        FROM ordem_de_servico os
        JOIN obras ob ON ob.id = os.obra_id
        JOIN contratos c ON c.id = os.contrato_id
        WHERE os.id = $os_id AND os.empresa_id = $empresa_id
        LIMIT 1";

$result = $conn->query($sql);
if (!$result || $result->num_rows === 0) {
    die("OS não encontrada.");
}

$os = $result->fetch_assoc();

// Gera conteúdo
$html = '
<!DOCTYPE html>
<html lang="pt-BR">
  <head>
    <meta charset="UTF-8" />
    <title>Ordem de Serviço - AXEL</title>
    <style>
      body {
        font-family: Arial, sans-serif;
        margin: 40px;
      }

      * {
        margin: 0px;
        padding: 0px;
      }

      h2,
      h3 {
        text-align: center;
        margin-bottom: 10px;
      }

      .header,
      .assinaturas {
        margin-bottom: 20px;
      }

      .header div,
      .assinaturas div {
        margin-bottom: 5px;
      }

      .tabela-servicos {
        width: 100%;
        border-collapse: collapse;
      }

      .tabela-servicos th,
      .tabela-servicos td {
        border: 1px solid #000;
        padding: 8px;
        font-size: 14px;
      }

      .tabela-servicos th {
        background-color: #f2f2f2;
      }

      .assinaturas div {
        margin-top: 30px;
      }

      .assinaturas label {
        display: block;
        margin-bottom: 5px;
      }

      .assinatura-box {
        border-top: 1px solid #000;
        width: 300px;
        padding-top: 5px;
        margin-top: 10px;
      }

      .rodape {
        margin-top: 30px;
      }

      .rodape span {
        display: inline-block;
        margin-right: 40px;
      }

      .titlediv {
        background-color: rgb(148, 148, 148);
      }
    </style>
  </head>
  <body>
    <table class="tabela-servicos">
      <thead>
        <tr>
          <th style="width: 10%">
            <div>
              <p>Ordem de Serviço N-0000</p>
              <p>MANUTENÇÕES CORRETIVAS E PREVENTIVAS</p>
            </div>
          </th>
          <th style="width: 20%">ABRIL</th>

         
        </tr>
        <tr>
          <th style="width: 50%; background-color:rgb(217, 217, 217)">ABRIGO</th>
          <th style="width: 50%; ">LOGO</th>

        </tr>
        <tr>
          <th style="width: 70%; background-color:rgb(217, 217, 217)">ENDEREÇO</th>
          <th style="width: 50%; ">Periodo de Execução</th>

        </tr>
      </thead>
    </table>


    <table class="tabela-servicos">
      <thead>
        <tr>
          <th>Ordem de Serviço N</th>
          <th>Descrição dos Serviços </th>
          <th>Und. Medida</th>
          <th>Quantidade</th>
          <th>Tipo de Serviço</th>
          <th>Executor do Serviço</th>
          <th>Data Inicio</th>
          <th>Data Final</th>

        </tr>
      </thead>
      <tbody>
        <tr>
          <td>01</td>
          <td>Substituição semafórica: módulo de LED de 12V</td>
          <td>un</td>
          <td>1</td>
          <td>Direto</td>
          <td>AV. Princesa</td>
          <td></td>
          <td></td>

        </tr>
        <!-- Linhas extras podem ser adicionadas abaixo -->
      
      </tbody>
    </table>

    <div class="assinaturas">
      <div>
        <label>Responsável pela Execução Técnica (AXEL):</label>
        <div class="assinatura-box">Nome e Assinatura</div>
      </div>

      <div>
        <label>Responsável pelo Recebimento (Cliente):</label>
        <div class="assinatura-box">Nome e Assinatura</div>
      </div>
    </div>

    <div class="rodape">
      <span
        ><strong>Período de Execução:</strong> de ___/___/_____ até
        ___/___/_____</span
      >
      <span><strong>Data de Entrega da OS:</strong> ___/___/_____</span>
    </div>
  </body>
</html>

';

// Criar diretório para salvar cópia
$hash = md5(uniqid('os_', true));
$diretorio = __DIR__ . "/../uploads/ordem-servico/$os_id";
if (!is_dir($diretorio)) {
    mkdir($diretorio, 0777, true);
}
$pdfPath = "$diretorio/$hash.pdf";

// Gerar PDF com TCPDF
$pdf = new \TCPDF('L', PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
$pdf->AddPage();
$pdf->SetFont('helvetica', '', 12);
$pdf->writeHTML($html);
$pdf->Output($pdfPath, 'F'); // Salvar para anexo
$pdf->Output("OS_$os_id.pdf", 'I'); // Exibir no navegador

// Simular $_FILES para salvar no banco
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime = finfo_file($finfo, $pdfPath);
finfo_close($finfo);

$_FILES['anexos'] = [
    'name' => [basename($pdfPath)],
    'type' => [$mime],
    'tmp_name' => [$pdfPath],
    'error' => [UPLOAD_ERR_OK],
    'size' => [filesize($pdfPath)]
];

// Salvar no banco
$pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

salvarAnexos($pdo, 'ordem_de_servico', $os_id, $_FILES['anexos']);

// A cópia já foi salva, não removemos o arquivo

// Função para salvar anexo
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

        if ($erro !== UPLOAD_ERR_OK || !file_exists($tmp_name)) {
            continue;
        }

        $nome_seguro = uniqid() . "_" . preg_replace("/[^a-zA-Z0-9\.\-_]/", "_", $nome_original);
        $caminho_final = $pasta_base . $nome_seguro;

        if (copy($tmp_name, $caminho_final)) {
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
