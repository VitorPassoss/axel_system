CREATE TABLE fornecedores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    razao_social VARCHAR(255) NOT NULL,          -- Nome da empresa
    nome_fantasia VARCHAR(255),                  -- Pode ser NULL
    cnpj VARCHAR(18) UNIQUE NOT NULL,            -- CNPJ no formato '00.000.000/0000-00'
    inscricao_estadual VARCHAR(50),              -- Pode ser NULL
    inscricao_municipal VARCHAR(50),             -- Pode ser NULL
    email VARCHAR(255),                          -- Pode ser NULL
    telefone VARCHAR(20),                        -- Pode ser NULL
    celular VARCHAR(20),                         -- Pode ser NULL
    site VARCHAR(255),                           -- Pode ser NULL
    contato_responsavel VARCHAR(255),            -- Nome do contato da empresa, pode ser NULL

    endereco VARCHAR(255),                       -- Pode ser NULL
    numero VARCHAR(10),                          -- Pode ser NULL
    complemento VARCHAR(100),                    -- Pode ser NULL
    bairro VARCHAR(100),                         -- Pode ser NULL
    cidade VARCHAR(100),                         -- Pode ser NULL
    estado CHAR(2),                              -- Pode ser NULL (ex: 'SP')
    cep VARCHAR(9),                              -- Pode ser NULL (ex: '00000-000')

    data_cadastro DATETIME DEFAULT CURRENT_TIMESTAMP,
    observacoes TEXT,                            -- Pode ser NULL

    ativo BOOLEAN DEFAULT TRUE
);


CREATE TABLE cotacao (
    id INT AUTO_INCREMENT PRIMARY KEY,

    obra_id INT,
    sc_id INT,
    os_id INT,

    descricao TEXT,                          -- Descrição da cotação
    data_inicio DATE,
    data_final DATE,

    dt_criado DATETIME DEFAULT CURRENT_TIMESTAMP,
    dt_aprovado DATETIME,
    dt_rejeitado DATETIME,
    retorno_rej TEXT,                        -- Justificativa da rejeição

    status ENUM('pendente', 'aprovado', 'rejeitado', 'em_analise') DEFAULT 'pendente',
    valor_total DECIMAL(12, 2),

    -- Chaves estrangeiras
    CONSTRAINT fk_obra FOREIGN KEY (obra_id) REFERENCES obras(id) ON DELETE SET NULL,
    CONSTRAINT fk_solicitacao FOREIGN KEY (sc_id) REFERENCES solicitacao_compras(id) ON DELETE SET NULL,
    CONSTRAINT fk_ordem_servico FOREIGN KEY (os_id) REFERENCES ordem_de_servico(id) ON DELETE SET NULL
);

CREATE TABLE cotacao_item (
    id INT AUTO_INCREMENT PRIMARY KEY,

    cotacao_id INT NOT NULL,
    fornecedor_id INT NOT NULL,

    descricao_tecnica TEXT,                         -- Detalhes técnicos do item
    und_medida VARCHAR(10),                         -- Unidade de medida (ex: 'kg', 'un', 'm')
    quantidade DECIMAL(10,2) DEFAULT 1,             -- Quantidade solicitada
    valor_item DECIMAL(12,2) NOT NULL,              -- Valor unitário proposto pelo fornecedor
    desconto DECIMAL(12,2) DEFAULT 0,               -- Desconto absoluto (não %)
    valor_final DECIMAL(12,2) AS (quantidade * (valor_item - desconto)) STORED,  -- Valor total

    -- Relacionamentos
    CONSTRAINT fk_cotacao_item_cotacao FOREIGN KEY (cotacao_id)
        REFERENCES cotacao(id) ON DELETE CASCADE,

    CONSTRAINT fk_cotacao_item_fornecedor FOREIGN KEY (fornecedor_id)
        REFERENCES fornecedores(id) ON DELETE CASCADE
);


ALTER TABLE cotacao
ADD COLUMN cotante VARCHAR(255) NULL AFTER os_id;


ALTER TABLE fornecedores ADD COLUMN empresa_id INT;





CREATE TABLE profissionais (
    id INT AUTO_INCREMENT PRIMARY KEY,
    
    -- Dados pessoais
    nome VARCHAR(255) NOT NULL,
    data_nascimento DATE NOT NULL,
    genero ENUM('Masculino', 'Feminino', 'Outro') DEFAULT NULL,
    estado_civil ENUM('Solteiro', 'Casado', 'Divorciado', 'Viúvo', 'União Estável') DEFAULT NULL,
    nacionalidade VARCHAR(100),
    naturalidade VARCHAR(100),
    
    -- Documentos
    cpf VARCHAR(14) NOT NULL UNIQUE,
    rg VARCHAR(20),
    orgao_emissor VARCHAR(50),
    data_emissao DATE,
    titulo_eleitor VARCHAR(20),
    ctps_numero VARCHAR(20),
    ctps_serie VARCHAR(20),
    pis_pasep VARCHAR(20),
    reservista VARCHAR(20),
    cnh VARCHAR(20),
    cnh_categoria VARCHAR(5),
    validade_cnh DATE,

    -- Contato
    telefone VARCHAR(20),
    celular VARCHAR(20),
    email VARCHAR(255),

    -- Endereço
    cep VARCHAR(10),
    endereco VARCHAR(255),
    numero VARCHAR(20),
    complemento VARCHAR(100),
    bairro VARCHAR(100),
    cidade VARCHAR(100),
    estado VARCHAR(2),

    -- Dados bancários
    banco VARCHAR(100),
    tipo_conta ENUM('Corrente', 'Poupança'),
    agencia VARCHAR(20),
    conta VARCHAR(20),
    pix_chave VARCHAR(255),
    pix_tipo ENUM('CPF', 'CNPJ', 'E-mail', 'Telefone', 'Aleatória'),

    -- Dados contratuais
    cargo VARCHAR(100),
    departamento VARCHAR(100),
    tipo_contrato ENUM('CLT', 'PJ', 'Temporário', 'Estágio'),
    salario DECIMAL(10,2),
    data_admissao DATE,
    jornada_trabalho VARCHAR(50),
    data_termino_contrato DATE, -- se aplicável
    beneficios TEXT,
    
    -- Dados de emergência
    contato_emergencia_nome VARCHAR(255),
    contato_emergencia_parentesco VARCHAR(50),
    contato_emergencia_telefone VARCHAR(20),

    -- Informações adicionais
    possui_deficiencia BOOLEAN DEFAULT FALSE,
    tipo_deficiencia VARCHAR(100),
    observacoes TEXT,

    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
