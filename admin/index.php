<?php
// ===================================================================
// ARQUIVO DE DADOS (ALTERE AQUI PARA ATUALIZAR AS DEMANDAS)
// ===================================================================
// Status disponíveis: 'Concluída', 'A Fazer'

$demandas = [
    // Módulos Concluídos
    [
        'id' => 1,
        'titulo' => 'Módulo Operacional',
        'responsavel' => 'Equipe Core',
        'status' => 'Concluída',
        'subitens' => [] // Sem subitens para mostrar
    ],
    [
        'id' => 2,
        'titulo' => 'Módulo Licitação',
        'responsavel' => 'Equipe Jurídico/Compras',
        'status' => 'Concluída',
        'subitens' => []
    ],
    [
        'id' => 3,
        'titulo' => 'Módulo Contratos',
        'responsavel' => 'Equipe Jurídico',
        'status' => 'Concluída',
        'subitens' => []
    ],
    [
        'id' => 4,
        'titulo' => 'Módulo de Profissionais (RH)',
        'responsavel' => 'Equipe RH',
        'status' => 'Concluída',
        'subitens' => []
    ],
    [
        'id' => 5,
        'titulo' => 'Módulo de Ordens de Serviço',
        'responsavel' => 'Equipe Operacional',
        'status' => 'Concluída',
        'subitens' => []
    ],
    [
        'id' => 6,
        'titulo' => 'Módulo de Compras',
        'responsavel' => 'Equipe de Suprimentos',
        'status' => 'Concluída',
        'subitens' => []
    ],

    // Módulos a Fazer
    [
        'id' => 7,
        'titulo' => 'Módulo Financeiro e Contábil',
        'responsavel' => 'Equipe Financeira',
        'status' => 'A Fazer',
        'subitens' => [
            'Integração com órgãos do governo',
            'Geração de Balancete',
            'Controle de Receita Total e Notas',
            'Detalhamento nas compras (Valor Único, Desconto, etc.)'
        ]
    ],
    [
        'id' => 8,
        'titulo' => 'Módulo Fornecedores',
        'responsavel' => 'Equipe de Suprimentos',
        'status' => 'A Fazer',
        'subitens' => [
            'Listagem e qualificação de fornecedores'
        ]
    ],
    [
        'id' => 9,
        'titulo' => 'Módulo RH (Expansão)',
        'responsavel' => 'Equipe RH',
        'status' => 'A Fazer',
        'subitens' => [
            'Processamento da Folha Salarial'
        ]
    ],
    [
        'id' => 10,
        'titulo' => 'Módulo Aplicativo de Campo',
        'responsavel' => 'Equipe de Desenvolvimento Mobile',
        'status' => 'A Fazer',
        'subitens' => [
            'Função Início/Fim de serviço',
            'Função Bater Ponto remoto',
            'Solicitação de combustível',
            'Registro fotográfico de obras'
        ]
    ],
        [
        'id' => 11,
        'titulo' => 'Módulo Relatorios e Dashboards',
        'responsavel' => 'Equipe RH',
        'status' => 'A Fazer',
        'subitens' => [
            'Visualização de Graficos',
            'KPIS',
            'Analise de Dados',
            'Geração de PDF'
        ]
    ],
];

// ===================================================================
// CÁLCULO DO PROGRESSO (NÃO PRECISA ALTERAR)
// ===================================================================
$totalDemandas = count($demandas);
$demandasConcluidas = 0;

if ($totalDemandas > 0) {
    $concluidasArray = array_filter($demandas, function($demanda) {
        return $demanda['status'] === 'Concluída';
    });
    $demandasConcluidas = count($concluidasArray);
    $porcentagem = round(($demandasConcluidas / $totalDemandas) * 100);
} else {
    $porcentagem = 0;
}

// Mapeia os status para classes CSS para dar cores diferentes
$statusClasses = [
    'Concluída' => 'status-concluida',
    'A Fazer' => 'status-afazer',
];

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard de Projetos</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            /* Paleta de Cores Refinada para Alto Contraste */
            --cor-fundo: #f9fafb; /* Cinza muito claro, quase branco */
            --cor-branca: #ffffff;
            --cor-primaria-texto: #111827; /* Preto suave para títulos */
            --cor-secundaria-texto: #6b7280; /* Cinza para textos de apoio */
            --cor-sucesso: #059669; /* Verde escuro para contraste */
            --cor-afazer: #2563eb; /* Azul escuro para contraste */
            --cor-borda: #e5e7eb; /* Borda sutil */
            --cor-tag-fundo: #f3f4f6; /* Fundo neutro para as tags de status */
            --sombra-suave: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --sombra-card: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px -1px rgba(0, 0, 0, 0.1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--cor-fundo);
            color: var(--cor-primaria-texto);
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 24px;
        }

        header {
            text-align: center;
            margin-bottom: 32px;
        }

        header h1 {
            font-weight: 700;
            font-size: 1.875rem; /* 30px */
            letter-spacing: -0.025em;
        }
        
        header p {
            color: var(--cor-secundaria-texto);
            font-size: 1rem; /* 16px */
            margin-top: 4px;
        }

        .progresso-card {
            background-color: var(--cor-branca);
            border-radius: 0.75rem; /* 12px */
            padding: 24px;
            margin-bottom: 32px;
            box-shadow: var(--sombra-card);
            border: 1px solid var(--cor-borda);
        }

        .progresso-card h2 {
            font-size: 1.125rem; /* 18px */
            margin-bottom: 16px;
            font-weight: 600;
        }
        
        .progresso-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-weight: 500;
            font-size: 0.875rem; /* 14px */
            color: var(--cor-secundaria-texto);
        }

        .progresso-info .percentual {
            color: var(--cor-primaria-texto);
            font-weight: 600;
        }

        .progresso-barra {
            height: 0.75rem; /* 12px */
            background-color: var(--cor-fundo);
            border-radius: 9999px;
            overflow: hidden;
            margin-top: 8px;
            margin-bottom: 8px;
        }

        .progresso-preenchimento {
            height: 100%;
            width: <?php echo $porcentagem; ?>%;
            background-color: var(--cor-sucesso);
            border-radius: 9999px;
            transition: width 0.8s cubic-bezier(0.25, 0.1, 0.25, 1);
        }

        .lista-demandas h2 {
            font-size: 1.5rem; /* 24px */
            margin-bottom: 24px;
            padding-bottom: 12px;
            border-bottom: 1px solid var(--cor-borda);
            font-weight: 600;
        }

        .demanda-card {
            background-color: var(--cor-branca);
            border-radius: 0.75rem; /* 12px */
            padding: 24px;
            margin-bottom: 16px;
            box-shadow: var(--sombra-card);
            border: 1px solid var(--cor-borda);
            transition: box-shadow 0.2s ease-in-out;
        }
        
        .demanda-card:hover {
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -2px rgba(0, 0, 0, 0.1);
        }

        .demanda-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 16px;
            margin-bottom: 12px;
        }

        .demanda-header h3 {
            font-size: 1.125rem; /* 18px */
            font-weight: 600;
        }
        
        .demanda-body p {
            font-size: 0.875rem; /* 14px */
            color: var(--cor-secundaria-texto);
            line-height: 1.5;
        }
        
        .status {
            background-color: var(--cor-tag-fundo);
            padding: 4px 10px;
            border-radius: 9999px;
            font-size: 0.75rem; /* 12px */
            font-weight: 500;
            white-space: nowrap;
            text-transform: uppercase;
        }

        /* Cor do TEXTO do status, não do fundo */
        .status-concluida { color: var(--cor-sucesso); }
        .status-afazer { color: var(--cor-afazer); }

        .subitens-container {
            margin-top: 16px;
            border-top: 1px solid var(--cor-borda);
            padding-top: 16px;
        }
        .subitens-container strong {
            font-weight: 600;
            font-size: 0.875rem;
            color: var(--cor-primaria-texto);
        }
        .subitens-lista {
            list-style-type: none; /* Remove a bolinha padrão */
            padding-left: 0;
            margin-top: 8px;
        }
        .subitens-lista li {
            font-size: 0.875rem;
            color: var(--cor-secundaria-texto);
            margin-bottom: 8px;
            display: flex;
            align-items: center;
        }
        /* Adiciona um "check" ou "traço" customizado para melhor visualização */
        .status-concluida .subitens-lista li::before {
             content: '✓';
             color: var(--cor-sucesso);
             margin-right: 8px;
             font-weight: 600;
        }
        .status-afazer .subitens-lista li::before {
             content: '–';
             color: var(--cor-secundaria-texto);
             margin-right: 8px;
             font-weight: 600;
        }

    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>Dashboard de Projetos</h1>
            <p>Visão geral do progresso dos módulos do sistema</p>
        </header>

        <section class="progresso-card">
            <h2>Progresso Geral dos Módulos</h2>
            <div class="progresso-info">
                <span>Status de Implementação</span>
                <span class="percentual"><?php echo $porcentagem; ?>%</span>
            </div>
            <div class="progresso-barra">
                <div class="progresso-preenchimento"></div>
            </div>
            <div class="progresso-info" style="margin-top: 4px;">
                <span><?php echo "$demandasConcluidas de $totalDemandas módulos concluídos"; ?></span>
            </div>
        </section>

        <section class="lista-demandas">
            <h2>Status dos Módulos</h2>
            
            <?php if (empty($demandas)): ?>
                <div class="demanda-card"><p>Nenhum módulo cadastrado no momento.</p></div>
            <?php else: ?>
                <?php foreach ($demandas as $demanda): ?>
                    <?php
                        $status_class = $statusClasses[$demanda['status']] ?? '';
                    ?>
                    <div class="demanda-card <?php echo $status_class; ?>">
                        <div class="demanda-header">
                            <h3><?php echo htmlspecialchars($demanda['titulo']); ?></h3>
                            <span class="status <?php echo $status_class; ?>">
                                <?php echo htmlspecialchars($demanda['status']); ?>
                            </span>
                        </div>
                        <div class="demanda-body">

                            <?php if (!empty($demanda['subitens']) && is_array($demanda['subitens'])): ?>
                                <div class="subitens-container">
                                    <strong>Tarefas Pendentes:</strong>
                                    <ul class="subitens-lista">
                                        <?php foreach ($demanda['subitens'] as $subitem): ?>
                                            <li><?php echo htmlspecialchars($subitem); ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </section>
    </div>
</body>
</html>