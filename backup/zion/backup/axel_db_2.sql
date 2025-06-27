-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 07/05/2025 às 23:50
-- Versão do servidor: 10.4.32-MariaDB
-- Versão do PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `axel_db`
--

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
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `contratos`
--

INSERT INTO `contratos` (`id`, `numero_contrato`, `numero_empenho`, `cnpj_cliente`, `nome_cliente`, `endereco_cliente`, `telefone_cliente`, `email_cliente`, `valor_mensal`, `valor_anual`, `observacoes`, `empresa_id`, `criado_em`) VALUES
(2, '172374624912', '0001-423-4924', '62.173.620/0001-80', 'Serasa', 'Rua Cordoval Luiz - Brança - Portugal SP - 6832055', '4234-2345', 'serasaclientes@gmail.com', 1000000.00, 123000000.00, '', 1, '2025-05-07 12:42:36');

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
(17, 'ordem_de_servico', 10, 'f349d2cd56bb46c765ef76d3e2ad82e1.pdf', 'uploads/ordem_de_servico/10/681bc355148c8_f349d2cd56bb46c765ef76d3e2ad82e1.pdf', '2025-05-07 20:32:21');

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
(1, '24.970.772/0002-03', 'Filial Manaus', 'Axel Construção e Projetos', '213321231231', 'axel@axel.com.br', 'Manaus/AM', '2025-05-05 19:55:11'),
(2, '24.970.772/0001-14', 'Matriz Boa Vista', 'Axel Construção e Projetos	', NULL, NULL, 'Boa Vista/RO', '2025-05-07 12:20:52');

-- --------------------------------------------------------

--
-- Estrutura para tabela `insumos`
--

CREATE TABLE `insumos` (
  `id` int(11) NOT NULL,
  `nome` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
(10, 'Quadra poliesportiva	', '', 'Civil', '2025-05-01', '2025-05-15', NULL, NULL, '', 4, 'Manaus', 'Amazonas', '69093755', NULL, NULL, 'Prefeitura de Manaus', 'Dr Silva', 1, 2, 2, '2025-05-07 19:59:34');

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
(10, 1, 2, 10, '', 'Terreno', '01', 'Fulano de tal', '2025-05-01', '2025-05-17', 'fulano de tal', 'Aberta', '2025-05-07 20:01:07');

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
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `projetos`
--

INSERT INTO `projetos` (`id`, `nome`, `descricao`, `valor`, `data_inicio`, `data_fim`, `status_fk`, `responsavel`, `cliente_nome`, `empresa_id`, `criado_em`) VALUES
(2, 'Quadra poliesportiva', 'etapa de planejamento estrutural', 0.00, '2025-05-01', '2025-05-07', 3, 'Fulana de Tal', 'ciclano ', 1, '2025-05-07 18:32:50');

-- --------------------------------------------------------

--
-- Estrutura para tabela `sc_item`
--

CREATE TABLE `sc_item` (
  `id` int(11) NOT NULL,
  `solicitacao_id` int(11) NOT NULL,
  `insumo_id` int(11) NOT NULL,
  `quantidade` int(11) NOT NULL,
  `fornecedor` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
(2, 'Limpar Terreno');

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
(7, 10, 2, 'm', 150, 'corretiva', 'Fulano de tal', NULL, NULL);

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
(2, 'Projetos'),
(3, 'Recursos Humanos'),
(4, 'Compras');

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
  `fornecedor` varchar(255) DEFAULT NULL,
  `status` enum('pendente','aprovado','rejeitado') DEFAULT 'pendente',
  `grau` enum('prioritario','urgente','normal') DEFAULT 'normal',
  `descricao` text DEFAULT NULL,
  `aprovado_por` varchar(255) DEFAULT NULL,
  `aprovado_em` datetime DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

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
(1, 'Planejamento', '#E3F2FD	', '2025-05-05 20:01:41'),
(2, 'Viabilidade', '#FFF9C4	', '2025-05-07 18:34:05'),
(3, 'Preliminar', '#FFE0B2	', '2025-05-07 18:34:24'),
(4, 'Anteprojeto', '#D1C4E9	', '2025-05-07 18:34:42'),
(5, 'Projeto Legal', '#AED581	', '2025-05-07 18:35:07'),
(6, 'Projeto Executivo', '#B3E5FC	', '2025-05-07 18:35:25');

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
(1, 'Levantamento e Estudo Inicial', '#FFFFFF', '2025-05-07 18:50:08'),
(2, 'Projeto Arquitetônico', '#F9FAFB\n', '2025-05-07 18:50:08'),
(3, 'Planejamento e Orçamento', '#F3F4F6\n', '2025-05-07 18:50:08'),
(4, 'Serviços Preliminares', '#E5E7EB\n', '2025-05-07 18:50:08'),
(5, 'Estrutura e Fundações', '#D1D5DB\n', '2025-05-07 18:50:08'),
(6, 'Instalações', '#D1D5DB\n', '2025-05-07 18:50:08'),
(7, 'Paredes e Telhados', '#D1D5DB\n', '2025-05-07 18:50:08'),
(8, 'Acabamentos e Revestimentos', '#D1D5DB\n', '2025-05-07 18:50:08'),
(9, 'Manutenções', '#D1D5DB\n', '2025-05-07 18:51:07');

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
  `is_superuser` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `users`
--

INSERT INTO `users` (`id`, `email`, `senha`, `setor_id`, `empresa_id`, `criado_em`, `is_superuser`) VALUES
(1, 'admin@gmail.com', '$2y$10$JN0x08eies5Szf9SneYwFeVfAXTxBX3Gyrs6GbIBHW0a3yTQhfaUy', 1, 1, '2025-05-05 19:55:36', 0);

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
(1, 'admin@gmail.com', '2025-05-05 19:53:51');

--
-- Índices para tabelas despejadas
--

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
-- Índices de tabela `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

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
-- AUTO_INCREMENT de tabela `contratos`
--
ALTER TABLE `contratos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de tabela `documentos`
--
ALTER TABLE `documentos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT de tabela `empresas`
--
ALTER TABLE `empresas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de tabela `insumos`
--
ALTER TABLE `insumos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `obras`
--
ALTER TABLE `obras`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de tabela `ordem_de_servico`
--
ALTER TABLE `ordem_de_servico`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de tabela `projetos`
--
ALTER TABLE `projetos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de tabela `sc_item`
--
ALTER TABLE `sc_item`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `servicos`
--
ALTER TABLE `servicos`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de tabela `servicos_os`
--
ALTER TABLE `servicos_os`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de tabela `setores`
--
ALTER TABLE `setores`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de tabela `solicitacao_compras`
--
ALTER TABLE `solicitacao_compras`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `status`
--
ALTER TABLE `status`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de tabela `status_obras`
--
ALTER TABLE `status_obras`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT de tabela `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `users_approveds`
--
ALTER TABLE `users_approveds`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

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
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
