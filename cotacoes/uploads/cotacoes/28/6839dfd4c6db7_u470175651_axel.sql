-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Tempo de geração: 29/05/2025 às 15:42
-- Versão do servidor: 10.11.10-MariaDB-log
-- Versão do PHP: 7.2.34

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `u470175651_axel`
--

-- --------------------------------------------------------

--
-- Estrutura para tabela `bancos`
--

CREATE TABLE `bancos` (
  `id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `codigo_banco` varchar(10) NOT NULL,
  `agencia` varchar(10) NOT NULL,
  `digito_agencia` varchar(2) DEFAULT NULL,
  `conta` varchar(20) NOT NULL,
  `digito_conta` varchar(2) DEFAULT NULL,
  `tipo_conta` enum('corrente','poupanca','salario','pagamento') DEFAULT 'corrente',
  `titular_nome` varchar(255) DEFAULT NULL,
  `titular_documento` varchar(20) DEFAULT NULL,
  `pix_chave` varchar(255) DEFAULT NULL,
  `pix_tipo` enum('cpf','cnpj','email','telefone','aleatoria') DEFAULT NULL,
  `observacoes` text DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `bancos`
--

INSERT INTO `bancos` (`id`, `nome`, `codigo_banco`, `agencia`, `digito_agencia`, `conta`, `digito_conta`, `tipo_conta`, `titular_nome`, `titular_documento`, `pix_chave`, `pix_tipo`, `observacoes`, `criado_em`) VALUES
(1, 'Inter', '077', '000', '1', '23873232', '9', 'corrente', 'vitor passos', '70514093285', '70514093285', 'cpf', NULL, '2025-05-12 20:36:26');

-- --------------------------------------------------------

--
-- Estrutura para tabela `categorias`
--

CREATE TABLE `categorias` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `nome` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `categorias`
--

INSERT INTO `categorias` (`id`, `nome`) VALUES
(1, 'Folha');

-- --------------------------------------------------------

--
-- Estrutura para tabela `contratos`
--

CREATE TABLE `contratos` (
  `id` int(11) NOT NULL,
  `numero_contrato` varchar(100) DEFAULT NULL,
  `numero_empenho` varchar(100) DEFAULT NULL,
  `cnpj_cliente` varchar(20) DEFAULT NULL,
  `nome_cliente` varchar(255) DEFAULT NULL,
  `endereco_cliente` text DEFAULT NULL,
  `telefone_cliente` varchar(20) DEFAULT NULL,
  `email_cliente` varchar(255) DEFAULT NULL,
  `valor_mensal` decimal(15,2) DEFAULT NULL,
  `valor_anual` decimal(15,2) DEFAULT NULL,
  `observacoes` text DEFAULT NULL,
  `empresa_id` int(11) DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `situacao` varchar(255) DEFAULT 'Ativo',
  `dt_inicio` date DEFAULT NULL,
  `dt_fim` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `contratos`
--

INSERT INTO `contratos` (`id`, `numero_contrato`, `numero_empenho`, `cnpj_cliente`, `nome_cliente`, `endereco_cliente`, `telefone_cliente`, `email_cliente`, `valor_mensal`, `valor_anual`, `observacoes`, `empresa_id`, `criado_em`, `situacao`, `dt_inicio`, `dt_fim`) VALUES
(2, '172374624912', '0001-423-4924', '00.530.352/0001-59', 'GDA', 'Rua Creuza Lopes Leitão', '000000000', 'dep.amommandel@camara.leg.br', 0.00, 0.00, '', 1, '2025-05-07 12:42:36', 'Ativo', '2025-05-07', '2025-05-20');

-- --------------------------------------------------------

--
-- Estrutura para tabela `documentos`
--

CREATE TABLE `documentos` (
  `id` int(11) NOT NULL,
  `tabela_ref` varchar(50) NOT NULL,
  `ref_id` int(11) NOT NULL,
  `nome` varchar(255) NOT NULL,
  `caminho_arquivo` text NOT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `documentos`
--

INSERT INTO `documentos` (`id`, `tabela_ref`, `ref_id`, `nome`, `caminho_arquivo`, `criado_em`) VALUES
(1, 'contratos', 1, 'Documento.docx', 'uploads/contratos/1/68191826ea0ca_Documento.docx', '2025-05-05 19:57:26'),
(2, 'ordem_de_servico', 1, 'Cópia de RELATORIO EMBRIOLOGIA - Copiar.docx', 'uploads/ordem_de_servico/1/681918c3ea157_Co__pia_de_RELATORIO_EMBRIOLOGIA_-_Copiar.docx', '2025-05-05 20:00:03'),
(3, 'ordem_de_servico', 2, 'query relatorio geral.txt', 'uploads/ordem_de_servico/2/681919f4e8b80_query_relatorio_geral.txt', '2025-05-05 20:05:08'),
(4, 'ordem_de_servico', 2, '92b728d8291e62cd3001f120aeb5f1bd.pdf', 'uploads/ordem_de_servico/2/68191aadafc40_92b728d8291e62cd3001f120aeb5f1bd.pdf', '2025-05-05 20:08:13'),
(5, 'ordem_de_servico', 3, 'Novo(a) Text Document.txt', 'uploads/ordem_de_servico/3/6819279e9e67e_Novo_a__Text_Document.txt', '2025-05-05 21:03:26'),
(6, 'ordem_de_servico', 5, 'f19e3de4d99c62d0073cb180dedb9e17.pdf', 'uploads/ordem_de_servico/5/681930ac0e63f_f19e3de4d99c62d0073cb180dedb9e17.pdf', '2025-05-05 21:42:04'),
(7, 'ordem_de_servico', 5, '45db18dcaf335caad62c5124ca4c1032.pdf', 'uploads/ordem_de_servico/5/68193247578d2_45db18dcaf335caad62c5124ca4c1032.pdf', '2025-05-05 21:48:55'),
(8, 'ordem_de_servico', 5, '2c7670b412d7cdec438b4b9592d495e4.pdf', 'uploads/ordem_de_servico/5/68193271037c9_2c7670b412d7cdec438b4b9592d495e4.pdf', '2025-05-05 21:49:37'),
(9, 'ordem_de_servico', 5, '7b7bd3568854b9e56701d3520fe9575a.pdf', 'uploads/ordem_de_servico/5/6819327cca417_7b7bd3568854b9e56701d3520fe9575a.pdf', '2025-05-05 21:49:48'),
(10, 'ordem_de_servico', 5, 'f5e9559dcfd1b210d4f16d14251a25a6.pdf', 'uploads/ordem_de_servico/5/681932a26de8d_f5e9559dcfd1b210d4f16d14251a25a6.pdf', '2025-05-05 21:50:26'),
(11, 'ordem_de_servico', 5, '4ebad07b96ee75ae6689530f84f7f999.pdf', 'uploads/ordem_de_servico/5/681932e018b10_4ebad07b96ee75ae6689530f84f7f999.pdf', '2025-05-05 21:51:28'),
(12, 'ordem_de_servico', 5, '65e7651489fa5d1aa1b5dee1caf2a469.pdf', 'uploads/ordem_de_servico/5/681932e90d844_65e7651489fa5d1aa1b5dee1caf2a469.pdf', '2025-05-05 21:51:37'),
(13, 'ordem_de_servico', 5, 'dc014ecfb5d2330ccc1d12b13ac731af.pdf', 'uploads/ordem_de_servico/5/6819331c78ed9_dc014ecfb5d2330ccc1d12b13ac731af.pdf', '2025-05-05 21:52:28'),
(14, 'ordem_de_servico', 10, '6ae130f4685bd96d658c662ebd1def98.pdf', 'uploads/ordem_de_servico/10/681bc31f65e82_6ae130f4685bd96d658c662ebd1def98.pdf', '2025-05-07 20:31:27'),
(15, 'ordem_de_servico', 10, 'e4774952134f0c7a7d4d78602132ca3f.pdf', 'uploads/ordem_de_servico/10/681bc31fdd74e_e4774952134f0c7a7d4d78602132ca3f.pdf', '2025-05-07 20:31:27'),
(16, 'ordem_de_servico', 10, 'a3547db7ad0816c174e8eef74bbea3e9.pdf', 'uploads/ordem_de_servico/10/681bc35491f3b_a3547db7ad0816c174e8eef74bbea3e9.pdf', '2025-05-07 20:32:20'),
(17, 'ordem_de_servico', 10, 'f349d2cd56bb46c765ef76d3e2ad82e1.pdf', 'uploads/ordem_de_servico/10/681bc355148c8_f349d2cd56bb46c765ef76d3e2ad82e1.pdf', '2025-05-07 20:32:21'),
(18, 'ordem_de_servico', 19, '065a93fa7ef195b6800fabd12b69af4c.pdf', 'uploads/ordem_de_servico/19/68265249ef927_065a93fa7ef195b6800fabd12b69af4c.pdf', '2025-05-15 20:44:57'),
(19, 'ordem_de_servico', 25, '3feb9593-bd0d-4a1b-a0ab-c1ae1a640b0d.jpg', 'uploads/ordem_de_servico/25/682b9ac67dd8a_3feb9593-bd0d-4a1b-a0ab-c1ae1a640b0d.jpg', '2025-05-19 20:55:34');

-- --------------------------------------------------------

--
-- Estrutura para tabela `empresas`
--

CREATE TABLE `empresas` (
  `id` int(11) NOT NULL,
  `cnpj` varchar(20) NOT NULL,
  `nome` varchar(255) NOT NULL,
  `razao_social` varchar(255) DEFAULT NULL,
  `telefone` varchar(20) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `localizacao` varchar(255) DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `empresas`
--

INSERT INTO `empresas` (`id`, `cnpj`, `nome`, `razao_social`, `telefone`, `email`, `localizacao`, `criado_em`) VALUES
(1, '24.970.772/0002-03', 'Filial', 'Axel Construção e Projetos', '213321231231', 'axel@axel.com.br', 'Manaus/AM', '2025-05-05 19:55:11'),
(2, '24.970.772/0001-14', 'Matriz', 'Axel Construção e Projetos	', NULL, NULL, 'Boa Vista/RO', '2025-05-07 12:20:52');

-- --------------------------------------------------------

--
-- Estrutura para tabela `insumos`
--

CREATE TABLE `insumos` (
  `id` int(11) NOT NULL,
  `nome` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `insumos`
--

INSERT INTO `insumos` (`id`, `nome`) VALUES
(6, 'cimento'),
(7, 'tijolo'),
(8, 'dsasadsa'),
(9, 'Areia'),
(10, 'Fechadura Elétronica'),
(11, 'Sensor da Fechadura');

-- --------------------------------------------------------

--
-- Estrutura para tabela `obras`
--

CREATE TABLE `obras` (
  `id` int(11) NOT NULL,
  `nome` varchar(255) NOT NULL,
  `descricao` text DEFAULT NULL,
  `tipo_obra` varchar(100) DEFAULT NULL,
  `data_inicio` date DEFAULT NULL,
  `data_previsao_fim` date DEFAULT NULL,
  `data_fim` date DEFAULT NULL,
  `custo_real` decimal(15,2) DEFAULT NULL,
  `endereco` varchar(255) DEFAULT NULL,
  `status_id` int(11) DEFAULT NULL,
  `cidade` varchar(100) DEFAULT NULL,
  `estado` varchar(100) DEFAULT NULL,
  `cep` varchar(20) DEFAULT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `cliente` varchar(255) DEFAULT NULL,
  `responsavel_tecnico` varchar(255) DEFAULT NULL,
  `empresa_id` int(11) DEFAULT NULL,
  `contrato_id` int(11) DEFAULT NULL,
  `projeto_id` int(11) DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `obras`
--

INSERT INTO `obras` (`id`, `nome`, `descricao`, `tipo_obra`, `data_inicio`, `data_previsao_fim`, `data_fim`, `custo_real`, `endereco`, `status_id`, `cidade`, `estado`, `cep`, `latitude`, `longitude`, `cliente`, `responsavel_tecnico`, `empresa_id`, `contrato_id`, `projeto_id`, `criado_em`) VALUES
(13, 'Reforma Escritório Eldorado - GDA', 'O projeto visa uma reforma no escritório do Eldorado além de uma instalação de Rede (Switch) , câmeras de segurança, e fechaduras eletrônicas\r\n', 'Reforma/Infra/Rede', '2025-02-01', '2025-05-30', NULL, NULL, '', 11, 'Manaus', 'Am', '69050-340', NULL, NULL, 'GDA', 'Eng. Claudia', 1, 2, NULL, '2025-05-19 20:42:05');

-- --------------------------------------------------------

--
-- Estrutura para tabela `ordem_de_servico`
--

CREATE TABLE `ordem_de_servico` (
  `id` int(11) NOT NULL,
  `empresa_id` int(11) DEFAULT NULL,
  `contrato_id` int(11) DEFAULT NULL,
  `obra_id` int(11) DEFAULT NULL,
  `descricao` text DEFAULT NULL,
  `local` varchar(255) DEFAULT NULL,
  `numero_os` varchar(100) DEFAULT NULL,
  `responsavel_os` varchar(255) DEFAULT NULL,
  `data_inicio` date DEFAULT NULL,
  `data_final` date DEFAULT NULL,
  `equipe` varchar(255) DEFAULT NULL,
  `status` varchar(100) DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `ordem_de_servico`
--

INSERT INTO `ordem_de_servico` (`id`, `empresa_id`, `contrato_id`, `obra_id`, `descricao`, `local`, `numero_os`, `responsavel_os`, `data_inicio`, `data_final`, `equipe`, `status`, `criado_em`) VALUES
(23, 1, 2, 13, 'Cabeamento CFTV  e Redes', 'Escritorio Amom Parte Interna', '0', 'Vitor Passos ', '2025-05-16', '2025-05-20', '', 'Em andamento', '2025-05-19 20:50:20'),
(29, 1, 2, 13, 'Fechadura Porta da Garagem ', 'Garagem', '00', 'Vitor Passos', '2025-05-21', '2025-05-22', '', 'Concluída', '2025-05-21 19:38:59'),
(30, 1, 2, 13, 'Instalação Fechadura Porta Principal', 'Garagem', '0', 'Vitor Passos', '2025-05-21', '2025-05-23', '', 'Concluída', '2025-05-23 20:42:54');

-- --------------------------------------------------------

--
-- Estrutura para tabela `projetos`
--

CREATE TABLE `projetos` (
  `id` int(11) NOT NULL,
  `nome` varchar(255) NOT NULL,
  `descricao` text DEFAULT NULL,
  `valor` decimal(15,2) DEFAULT NULL,
  `data_inicio` date DEFAULT NULL,
  `data_fim` date DEFAULT NULL,
  `status_fk` int(11) DEFAULT NULL,
  `responsavel` varchar(255) DEFAULT NULL,
  `cliente_nome` varchar(255) DEFAULT NULL,
  `empresa_id` int(11) DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `contrato_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `recorrencias`
--

CREATE TABLE `recorrencias` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `tipo` varchar(20) DEFAULT NULL CHECK (`tipo` in ('unico','mensal','trimestral','anual','continuo'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `registro_os`
--

CREATE TABLE `registro_os` (
  `id` int(11) NOT NULL,
  `os_id` int(11) DEFAULT NULL,
  `servico_id` bigint(20) UNSIGNED DEFAULT NULL,
  `imagem` varchar(255) DEFAULT NULL,
  `data` datetime DEFAULT current_timestamp(),
  `observacao` text DEFAULT NULL,
  `momento` enum('antes','depois') NOT NULL DEFAULT 'antes'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `registro_os`
--

INSERT INTO `registro_os` (`id`, `os_id`, `servico_id`, `imagem`, `data`, `observacao`, `momento`) VALUES
(1, NULL, NULL, '6830d743b0c04_3feb9593-bd0d-4a1b-a0ab-c1ae1a640b0d.jpg', '2025-05-23 20:14:59', 'Fechadura danificada, necessaria á troca', 'antes'),
(3, NULL, NULL, '6830d7ba70d2e_2cdc8953-e48b-4578-a7d8-452f6ecb0d11.jpg', '2025-05-23 20:16:58', 'Fechadura trocada e em pleno funcionamento.', 'depois'),
(4, NULL, NULL, '6830d8307f5cd_2ee7a08f-c4fc-4c63-abd0-9effeaa16baa.jpg', '2025-05-23 20:18:56', 'Necessaria troca do cabeamento que esta mal posicionando na estrutura da porta/fechadura', 'antes'),
(5, NULL, NULL, '6830d85b286f2_a5e00325-e3fc-4058-9212-f79e0b547209.jpg', '2025-05-23 20:19:39', 'Troca do cabeamento realizada e posicionada da maneira correta evitando atrito e problemas futuros.', 'depois'),
(6, 29, 22, '6830d917588fd_0ff0bc58-6b5c-4d90-8a4d-9a32df8a1a86.jpg', '2025-05-23 20:22:47', 'Necessaria cobrir a fiação da fechadura', 'antes'),
(7, 29, 22, '6830d9304b2d2_feaf37f4-2533-4ef5-b0ec-c2d88dc278a7.jpg', '2025-05-23 20:23:12', 'Fiação foi coberta e foi necessaria uma capa protetora improvisada escolhida e aprovada pelo cliente.', 'depois'),
(8, 30, 24, '6830de262da72_2ee7a08f-c4fc-4c63-abd0-9effeaa16baa.jpg', '2025-05-23 20:44:22', 'Necessario á troca e o posicionamento do cabo', 'antes'),
(9, 30, 24, '6830de477fbdd_a5e00325-e3fc-4058-9212-f79e0b547209.jpg', '2025-05-23 20:44:55', 'Troca de cabeamento finalizada e reposicionada para evitar atrito', 'depois'),
(10, 30, 25, '6830de6954ab2_3feb9593-bd0d-4a1b-a0ab-c1ae1a640b0d.jpg', '2025-05-23 20:45:29', 'Fechadura danificada necessaria á troca', 'antes'),
(11, 30, 25, '6830de794a960_2cdc8953-e48b-4578-a7d8-452f6ecb0d11.jpg', '2025-05-23 20:45:45', 'Fechadura troca e devidamente configurada', 'depois');

-- --------------------------------------------------------

--
-- Estrutura para tabela `relatorio_os`
--

CREATE TABLE `relatorio_os` (
  `id` int(11) NOT NULL,
  `os_id` int(11) NOT NULL,
  `observacao` text NOT NULL,
  `criado_em` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `sc_item`
--

CREATE TABLE `sc_item` (
  `id` int(11) NOT NULL,
  `solicitacao_id` int(11) NOT NULL,
  `insumo_id` int(11) NOT NULL,
  `quantidade` int(11) NOT NULL,
  `fornecedor` varchar(255) DEFAULT NULL,
  `grau` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `sc_item`
--

INSERT INTO `sc_item` (`id`, `solicitacao_id`, `insumo_id`, `quantidade`, `fornecedor`, `grau`) VALUES
(28, 22, 10, 1, '', 'Media'),
(29, 22, 11, 1, '', 'Media'),
(30, 23, 6, 1000, '', 'Baixa'),
(31, 23, 7, 1000, '', 'Baixa');

-- --------------------------------------------------------

--
-- Estrutura para tabela `servicos`
--

CREATE TABLE `servicos` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `nome` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `servicos`
--

INSERT INTO `servicos` (`id`, `nome`) VALUES
(1, 'Consertar Pia'),
(2, 'Limpar Terreno'),
(3, 'Pintura do piso da Praça'),
(4, 'Recuperação do Piso da Praça'),
(5, 'Instalação de Adptadores em Cabos de Rede'),
(6, 'Instalação de Adptadores na Rede'),
(7, 'Configuração e Instalaçõ de Switch de Rede'),
(8, 'Troca de Fechadura e Sensor'),
(9, 'Troca de Cabeamento na porta Saida Interna'),
(10, 'Instalação da Porta'),
(11, 'Levantamento de Requisitos'),
(12, 'Troca da Proteçãon da Fechadura '),
(13, 'Instalação de capa protetora');

-- --------------------------------------------------------

--
-- Estrutura para tabela `servicos_os`
--

CREATE TABLE `servicos_os` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `os_id` int(11) NOT NULL,
  `servico_id` bigint(20) UNSIGNED NOT NULL,
  `und_do_servico` varchar(50) NOT NULL,
  `quantidade` int(11) NOT NULL,
  `tipo_servico` varchar(50) NOT NULL,
  `executor` varchar(100) NOT NULL,
  `dt_inicio` date DEFAULT NULL,
  `dt_final` date DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `servicos_os`
--

INSERT INTO `servicos_os` (`id`, `os_id`, `servico_id`, `und_do_servico`, `quantidade`, `tipo_servico`, `executor`, `dt_inicio`, `dt_final`) VALUES
(14, 23, 5, 'und', 17, 'corretiva', 'Bruno Rodrigues', NULL, NULL),
(15, 23, 6, 'und', 17, 'corretiva', 'Bruno Rodrigues e Vitor Passos', NULL, NULL),
(16, 23, 7, 'und ', 1, 'corretiva', 'Vitor Passos', NULL, NULL),
(22, 29, 13, 'und', 1, 'corretiva', 'Adolfo e Bruno', '2025-05-21', '2025-05-22'),
(24, 30, 9, 'und', 1, 'corretiva', 'Adolfo e Bruno', '2025-05-21', '2025-05-23'),
(25, 30, 8, 'und ', 2, 'corretiva', 'Adolfo e Bruno', '2025-05-21', '2025-05-22');

-- --------------------------------------------------------

--
-- Estrutura para tabela `setores`
--

CREATE TABLE `setores` (
  `id` int(11) NOT NULL,
  `nome` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `setores`
--

INSERT INTO `setores` (`id`, `nome`) VALUES
(1, 'Gestão'),
(4, 'Compras'),
(6, 'Operacional'),
(7, 'Contratante');

-- --------------------------------------------------------

--
-- Estrutura para tabela `solicitacao_compras`
--

CREATE TABLE `solicitacao_compras` (
  `id` int(11) NOT NULL,
  `os_id` int(11) NOT NULL,
  `solicitante` varchar(255) NOT NULL,
  `empresa_id` int(11) DEFAULT NULL,
  `valor` decimal(15,2) DEFAULT NULL,
  `status` enum('pendente','aprovado','rejeitado') DEFAULT 'pendente',
  `grau` enum('prioritario','urgente','normal') DEFAULT 'normal',
  `descricao` text DEFAULT NULL,
  `aprovado_por` varchar(255) DEFAULT NULL,
  `aprovado_em` datetime DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `solicitacao_compras`
--

INSERT INTO `solicitacao_compras` (`id`, `os_id`, `solicitante`, `empresa_id`, `valor`, `status`, `grau`, `descricao`, `aprovado_por`, `aprovado_em`, `criado_em`) VALUES
(22, 25, 'Bruno Rodrigues', 1, 0.00, 'pendente', '', 'Danificou a fechadura elétronica e o sensor', NULL, NULL, '2025-05-20 18:59:37'),
(23, 29, 'Vitor Passos', 1, 0.00, 'pendente', '', 'Acabou o materal do almoxarifado ', NULL, NULL, '2025-05-22 13:26:37');

-- --------------------------------------------------------

--
-- Estrutura para tabela `status`
--

CREATE TABLE `status` (
  `id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `cor` varchar(20) DEFAULT '#000000',
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `status`
--

INSERT INTO `status` (`id`, `nome`, `cor`, `criado_em`) VALUES
(1, 'Não Iniciado', '#E3F2FD	', '2025-05-05 20:01:41'),
(2, 'Em Andamento', '#a6f7d3', '2025-05-07 18:34:05'),
(3, 'Finalizado', '#FFE0B2	', '2025-05-07 18:34:24');

-- --------------------------------------------------------

--
-- Estrutura para tabela `status_obras`
--

CREATE TABLE `status_obras` (
  `id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `cor` varchar(20) DEFAULT '#FFFFFF',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `status_obras`
--

INSERT INTO `status_obras` (`id`, `nome`, `cor`, `created_at`) VALUES
(9, 'Manutenções', '#D1D5DB\n', '2025-05-07 18:51:07'),
(10, 'Não Iniciado', '#E3F2FD	', '2025-05-20 12:53:29'),
(11, 'Em Andamento', '#a6f7d3', '2025-05-20 12:53:45'),
(12, 'Finalizado', '#FFE0B2', '2025-05-20 12:53:58');

-- --------------------------------------------------------

--
-- Estrutura para tabela `transacoes`
--

CREATE TABLE `transacoes` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `descricao` text DEFAULT NULL,
  `tipo_transacao` varchar(10) DEFAULT NULL CHECK (`tipo_transacao` in ('entrada','saida')),
  `status` varchar(20) DEFAULT NULL CHECK (`status` in ('pendente','paga','cancelada')),
  `valor` decimal(10,2) NOT NULL,
  `banco_id` int(11) DEFAULT NULL,
  `dt_pagamento` date DEFAULT NULL,
  `dt_vencimento` date DEFAULT NULL,
  `juros` decimal(10,2) DEFAULT 0.00,
  `multa` decimal(10,2) DEFAULT 0.00,
  `recorrencia_id` int(11) DEFAULT NULL,
  `categoria_id` int(11) DEFAULT NULL,
  `contrato_id` int(11) DEFAULT NULL,
  `os_id` int(11) DEFAULT NULL,
  `empresa_id` int(11) DEFAULT NULL,
  `projeto_id` int(11) DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `transacoes`
--

INSERT INTO `transacoes` (`id`, `descricao`, `tipo_transacao`, `status`, `valor`, `banco_id`, `dt_pagamento`, `dt_vencimento`, `juros`, `multa`, `recorrencia_id`, `categoria_id`, `contrato_id`, `os_id`, `empresa_id`, `projeto_id`, `criado_em`) VALUES
(11, 'sada', 'entrada', 'pendente', 23.34, 1, NULL, NULL, 0.00, 0.00, NULL, 1, NULL, NULL, 1, NULL, '2025-05-12 21:07:45'),
(12, 'sdsadsa', 'entrada', 'pendente', 230.49, 1, NULL, NULL, 0.00, 0.00, NULL, 1, NULL, NULL, 1, NULL, '2025-05-12 21:08:01'),
(13, 'salario', 'entrada', 'pendente', 1340.60, 1, NULL, NULL, 0.00, 0.00, NULL, 1, NULL, NULL, 1, NULL, '2025-05-12 21:09:59'),
(14, 'contrato x', 'entrada', 'paga', 99999999.99, 1, NULL, NULL, 0.00, 0.00, NULL, 1, NULL, NULL, 1, NULL, '2025-05-12 21:20:42'),
(15, 'salario', 'entrada', 'paga', 1230.00, 1, NULL, NULL, 0.00, 0.00, NULL, 1, NULL, NULL, 1, NULL, '2025-05-13 15:35:45'),
(16, 'saidas', 'saida', 'paga', 2334.00, 1, NULL, NULL, 0.00, 0.00, NULL, 1, NULL, NULL, 1, NULL, '2025-05-13 15:36:30');

-- --------------------------------------------------------

--
-- Estrutura para tabela `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `senha` varchar(255) NOT NULL,
  `setor_id` int(11) DEFAULT NULL,
  `empresa_id` int(11) DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_superuser` tinyint(1) DEFAULT 0,
  `contrato_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `users`
--

INSERT INTO `users` (`id`, `email`, `senha`, `setor_id`, `empresa_id`, `criado_em`, `is_superuser`, `contrato_id`) VALUES
(1, 'admin@gmail.com', '$2y$10$JN0x08eies5Szf9SneYwFeVfAXTxBX3Gyrs6GbIBHW0a3yTQhfaUy', 1, 1, '2025-05-05 19:55:36', 0, NULL),
(2, 'financeiro.filial@axelconstrucoes.com.br', '$2y$10$laHj.jXyav.T.fhVWQXb0.q/EqHsJueFktwuQdUH.TkEmMVaOj1KC', 1, 1, '2025-05-12 20:15:02', 0, NULL),
(3, 'alfonsopessoa@hotmail.com', '$2y$10$UiagnNbqgZiA0R9o9wls9eKb11GbF2os6zzKc6asvKn4s4phn/daK', 1, 1, '2025-05-20 11:50:57', 0, NULL),
(4, 'claudiapimentelpereira@hotmail.com', '$2y$10$/oZZ9It.MJx7mZwuwWaV6.bu/Lxm4jjUy971NKxCpBYJTf.IaLvc2', 2, 1, '2025-05-20 12:55:57', 0, NULL),
(5, 'gda@email.com', '$2y$10$j.LLNyvKxyl4SbQi6AmvCuS4gd0Wsd6V/29TsTsQ80B1vN/GrZtGy', 7, 1, '2025-05-21 15:20:16', 0, NULL);

-- --------------------------------------------------------

--
-- Estrutura para tabela `users_approveds`
--

CREATE TABLE `users_approveds` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `users_approveds`
--

INSERT INTO `users_approveds` (`id`, `email`, `criado_em`) VALUES
(1, 'admin@gmail.com', '2025-05-05 19:53:51'),
(2, 'financeiro.filial@axelconstrucoes.com.br', '2025-05-12 20:14:31'),
(3, 'alfonsopessoa@hotmail.com', '2025-05-20 11:50:33'),
(4, 'claudiapimentelpereira@hotmail.com', '2025-05-20 12:55:18'),
(5, 'gda@email.com', '2025-05-21 15:18:35');

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `bancos`
--
ALTER TABLE `bancos`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `categorias`
--
ALTER TABLE `categorias`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `contratos`
--
ALTER TABLE `contratos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `empresa_id` (`empresa_id`);

--
-- Índices de tabela `documentos`
--
ALTER TABLE `documentos`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `empresas`
--
ALTER TABLE `empresas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `cnpj` (`cnpj`);

--
-- Índices de tabela `insumos`
--
ALTER TABLE `insumos`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `obras`
--
ALTER TABLE `obras`
  ADD PRIMARY KEY (`id`),
  ADD KEY `empresa_id` (`empresa_id`),
  ADD KEY `contrato_id` (`contrato_id`),
  ADD KEY `projeto_id` (`projeto_id`),
  ADD KEY `fk_status_id` (`status_id`);

--
-- Índices de tabela `ordem_de_servico`
--
ALTER TABLE `ordem_de_servico`
  ADD PRIMARY KEY (`id`),
  ADD KEY `empresa_id` (`empresa_id`),
  ADD KEY `contrato_id` (`contrato_id`),
  ADD KEY `obra_id` (`obra_id`);

--
-- Índices de tabela `projetos`
--
ALTER TABLE `projetos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `status_fk` (`status_fk`),
  ADD KEY `empresa_id` (`empresa_id`);

--
-- Índices de tabela `recorrencias`
--
ALTER TABLE `recorrencias`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `registro_os`
--
ALTER TABLE `registro_os`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_registro_os` (`os_id`),
  ADD KEY `fk_registro_servico_os` (`servico_id`);

--
-- Índices de tabela `relatorio_os`
--
ALTER TABLE `relatorio_os`
  ADD PRIMARY KEY (`id`),
  ADD KEY `os_id` (`os_id`);

--
-- Índices de tabela `sc_item`
--
ALTER TABLE `sc_item`
  ADD PRIMARY KEY (`id`),
  ADD KEY `solicitacao_id` (`solicitacao_id`),
  ADD KEY `insumo_id` (`insumo_id`);

--
-- Índices de tabela `servicos`
--
ALTER TABLE `servicos`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `servicos_os`
--
ALTER TABLE `servicos_os`
  ADD PRIMARY KEY (`id`),
  ADD KEY `os_id` (`os_id`),
  ADD KEY `servico_id` (`servico_id`);

--
-- Índices de tabela `setores`
--
ALTER TABLE `setores`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `solicitacao_compras`
--
ALTER TABLE `solicitacao_compras`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `status`
--
ALTER TABLE `status`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `status_obras`
--
ALTER TABLE `status_obras`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `transacoes`
--
ALTER TABLE `transacoes`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `fk_users_contrato` (`contrato_id`);

--
-- Índices de tabela `users_approveds`
--
ALTER TABLE `users_approveds`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `bancos`
--
ALTER TABLE `bancos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `categorias`
--
ALTER TABLE `categorias`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `contratos`
--
ALTER TABLE `contratos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de tabela `documentos`
--
ALTER TABLE `documentos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT de tabela `empresas`
--
ALTER TABLE `empresas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de tabela `insumos`
--
ALTER TABLE `insumos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT de tabela `obras`
--
ALTER TABLE `obras`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT de tabela `ordem_de_servico`
--
ALTER TABLE `ordem_de_servico`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT de tabela `projetos`
--
ALTER TABLE `projetos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de tabela `recorrencias`
--
ALTER TABLE `recorrencias`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `registro_os`
--
ALTER TABLE `registro_os`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT de tabela `relatorio_os`
--
ALTER TABLE `relatorio_os`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `sc_item`
--
ALTER TABLE `sc_item`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT de tabela `servicos`
--
ALTER TABLE `servicos`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT de tabela `servicos_os`
--
ALTER TABLE `servicos_os`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT de tabela `setores`
--
ALTER TABLE `setores`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de tabela `solicitacao_compras`
--
ALTER TABLE `solicitacao_compras`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT de tabela `status`
--
ALTER TABLE `status`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de tabela `status_obras`
--
ALTER TABLE `status_obras`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT de tabela `transacoes`
--
ALTER TABLE `transacoes`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT de tabela `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de tabela `users_approveds`
--
ALTER TABLE `users_approveds`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Restrições para tabelas despejadas
--

--
-- Restrições para tabelas `contratos`
--
ALTER TABLE `contratos`
  ADD CONSTRAINT `contratos_ibfk_1` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `obras`
--
ALTER TABLE `obras`
  ADD CONSTRAINT `fk_status_id` FOREIGN KEY (`status_id`) REFERENCES `status_obras` (`id`),
  ADD CONSTRAINT `obras_ibfk_1` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `obras_ibfk_2` FOREIGN KEY (`contrato_id`) REFERENCES `contratos` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `obras_ibfk_3` FOREIGN KEY (`projeto_id`) REFERENCES `projetos` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `ordem_de_servico`
--
ALTER TABLE `ordem_de_servico`
  ADD CONSTRAINT `ordem_de_servico_ibfk_1` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `ordem_de_servico_ibfk_2` FOREIGN KEY (`contrato_id`) REFERENCES `contratos` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `ordem_de_servico_ibfk_3` FOREIGN KEY (`obra_id`) REFERENCES `obras` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `projetos`
--
ALTER TABLE `projetos`
  ADD CONSTRAINT `projetos_ibfk_1` FOREIGN KEY (`status_fk`) REFERENCES `status` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `projetos_ibfk_2` FOREIGN KEY (`empresa_id`) REFERENCES `empresas` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `registro_os`
--
ALTER TABLE `registro_os`
  ADD CONSTRAINT `fk_registro_os` FOREIGN KEY (`os_id`) REFERENCES `ordem_de_servico` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_registro_servico_os` FOREIGN KEY (`servico_id`) REFERENCES `servicos_os` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `relatorio_os`
--
ALTER TABLE `relatorio_os`
  ADD CONSTRAINT `relatorio_os_ibfk_1` FOREIGN KEY (`os_id`) REFERENCES `ordem_de_servico` (`id`);

--
-- Restrições para tabelas `sc_item`
--
ALTER TABLE `sc_item`
  ADD CONSTRAINT `sc_item_ibfk_1` FOREIGN KEY (`solicitacao_id`) REFERENCES `solicitacao_compras` (`id`),
  ADD CONSTRAINT `sc_item_ibfk_2` FOREIGN KEY (`insumo_id`) REFERENCES `insumos` (`id`);

--
-- Restrições para tabelas `servicos_os`
--
ALTER TABLE `servicos_os`
  ADD CONSTRAINT `servicos_os_ibfk_1` FOREIGN KEY (`os_id`) REFERENCES `ordem_de_servico` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `servicos_os_ibfk_2` FOREIGN KEY (`servico_id`) REFERENCES `servicos` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `fk_users_contrato` FOREIGN KEY (`contrato_id`) REFERENCES `contratos` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
