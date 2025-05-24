<?php
/**
 * Sistema de Controle de Empréstimos - Página Principal
 * 
 * Esta é a página principal do sistema, onde são listados os devedores,
 * é possível cadastrar novos empréstimos e pesquisar devedores.
 * 
 * Versão 2.5 - Adiciona pesquisa por nome ou ID
 * 
 * @author Manus AI
 * @version 2.5
 */

// Inclui o arquivo de funções do banco de dados
require_once 'db.php';

// Inicializa o banco de dados
$db = inicializarBancoDados();

// Tenta migrar dados do JSON para o SQLite (se existirem)
migrarDadosJsonParaSQLite($db);

// Atualiza os juros e penalidades antes de carregar
atualizarJuros($db);

// Carrega TODOS os devedores do banco de dados
$todosDevedores = carregarDevedores($db);

// --- Lógica de Pesquisa ---
$termoPesquisa = isset($_GET['q']) ? trim($_GET['q']) : '';
$devedoresFiltrados = [];

if (!empty($termoPesquisa)) {
    foreach ($todosDevedores as $devedor) {
        // Verifica se o termo é numérico (busca por ID)
        if (is_numeric($termoPesquisa)) {
            if ($devedor['id'] == (int)$termoPesquisa) {
                $devedoresFiltrados[] = $devedor;
                break; // ID é único, pode parar
            }
        } else {
            // Busca por nome (case-insensitive)
            if (stripos($devedor['nome'], $termoPesquisa) !== false) {
                $devedoresFiltrados[] = $devedor;
            }
        }
    }
} else {
    // Se não houver pesquisa, usa todos os devedores
    $devedoresFiltrados = $todosDevedores;
}
// --- Fim da Lógica de Pesquisa ---


// Separa os devedores FILTRADOS em três grupos: atrasados, pagamentos do dia e outros
$atrasados = [];
$pagamentosHoje = [];
$outrosDevedores = [];
$dataAtual = new DateTime(); // Data atual para comparações

foreach ($devedoresFiltrados as $devedor) {
    $dataUltimoPagamento = new DateTime($devedor['data_ultimo_pagamento']);
    $intervalo = $dataAtual->diff($dataUltimoPagamento);
    $diferencaDiasTotal = $intervalo->days;
    $estaAtrasado = false;
    $estaNoDiaDePagar = false;

    // Verifica se está atrasado
    if ($dataAtual > $dataUltimoPagamento && $devedor['ultimo_pagamento'] === 'nenhum') {
        // Considera atrasado apenas se a diferença for maior que 0 dias
        // (ou seja, no dia seguinte à data do último pagamento)
        if ($diferencaDiasTotal > 0) {
             $estaAtrasado = true;
        }
    }

    // Verifica se está no dia de pagamento
    if (!$estaAtrasado && $dataAtual->format('d') == $dataUltimoPagamento->format('d') && $dataAtual->format('Y-m-d') != $dataUltimoPagamento->format('Y-m-d') && $dataAtual > $dataUltimoPagamento) {
         if ($diferencaDiasTotal >= 28) { 
            $estaNoDiaDePagar = true;
         }
    }

    // Categoriza o devedor
    if ($estaAtrasado) {
        $atrasados[] = $devedor;
    } elseif ($estaNoDiaDePagar) {
        $pagamentosHoje[] = $devedor;
    } else {
        // Adiciona à lista geral apenas se não estiver atrasado ou no dia de pagar
        $outrosDevedores[] = $devedor;
    }
}

// Ordena os três grupos por ordem alfabética
usort($atrasados, function($a, $b) {
    return strcmp($a['nome'], $b['nome']);
});
usort($pagamentosHoje, function($a, $b) {
    return strcmp($a['nome'], $b['nome']);
});
usort($outrosDevedores, function($a, $b) {
    return strcmp($a['nome'], $b['nome']);
});

// Calcula os totais para o resumo financeiro (BASEADO EM TODOS OS DEVEDORES, NÃO APENAS OS FILTRADOS)
$totalEmprestado = 0;
$totalAtualComJurosPenalidades = 0; 
$totalJurosMensalAtual = 0; 

foreach ($todosDevedores as $devedor) { // Usa $todosDevedores para totais gerais
    $totalEmprestado += $devedor['valor_inicial'];
    $totalAtualComJurosPenalidades += $devedor['valor_atual']; 
    $totalJurosMensalAtual += $devedor['valor_inicial'] * 0.4;
}

$totalComJurosBase = $totalEmprestado + $totalJurosMensalAtual; 

// Calcula o total de juros a receber hoje (BASEADO NOS PAGAMENTOS DO DIA FILTRADOS)
$totalJurosHoje = 0;
foreach ($pagamentosHoje as $devedor) { // Usa $pagamentosHoje (já filtrado)
    $jurosDevedor = $devedor['valor_inicial'] * 0.4;
    $totalJurosHoje += $jurosDevedor;
}

?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Controle de Empréstimos</title>
    <link rel="stylesheet" href="styles-dark.css">
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Sistema de Controle de Empréstimos</h1>
            <div class="navigation">
                <a href="index.php" class="btn btn-primary">Página Inicial</a>
                <a href="cadastro.php" class="btn btn-success">Cadastrar Novo</a>
                <a href="quitados.php" class="btn btn-info">Ver Quitados</a>
            </div>
        </div>
        
        <?php 
        // Passa as variáveis calculadas para o resumo financeiro
        $totalJuros = $totalJurosHoje; 
        $totalComJuros = $totalComJurosBase; 
        // Passa a contagem de pagamentos hoje (dos filtrados)
        $countPagamentosHoje = count($pagamentosHoje); 
        include 'resumo_financeiro.php'; 
        ?>

        <!-- Formulário de Pesquisa -->
        <div class="card search-form">
             <h2>Pesquisar Devedor</h2>
             <form action="index.php" method="GET">
                 <input type="text" name="q" placeholder="Digite o nome ou ID" value="<?php echo htmlspecialchars($termoPesquisa); ?>">
                 <button type="submit" class="btn btn-primary">Pesquisar</button>
                 <?php if (!empty($termoPesquisa)): ?>
                     <a href="index.php" class="btn btn-secondary">Limpar Pesquisa</a>
                 <?php endif; ?>
             </form>
        </div>
        
        <?php if (empty($atrasados) && empty($pagamentosHoje) && empty($outrosDevedores)): ?>
            <div class="card">
                <h2>Lista de Devedores</h2>
                <?php if (!empty($termoPesquisa)): ?>
                     <div class="alert alert-info">Nenhum devedor encontrado para "<?php echo htmlspecialchars($termoPesquisa); ?>".</div>
                <?php else: ?>
                     <div class="alert alert-info">Nenhum devedor cadastrado.</div>
                <?php endif; ?>
            </div>
        <?php else: ?>

            <?php if (!empty($atrasados)): ?>
            <div class="card card-atrasado">
                <h2>Pagamentos em Atraso <?php echo !empty($termoPesquisa) ? '(Resultados da Pesquisa)' : ''; ?></h2>
                <div class="alert alert-danger">
                    <strong>Atenção!</strong> Estes devedores estão com pagamentos atrasados. Penalidade de R$10 por dia aplicada.
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nome</th>
                            <th>Telefone</th>
                            <th>Valor Atual (com Juros/Multa)</th>
                            <th>Data Último Pag.</th>
                            <th>Dias Atraso</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($atrasados as $devedor): 
                            $dataUltPag = new DateTime($devedor['data_ultimo_pagamento']);
                            $diasAtraso = $dataAtual->diff($dataUltPag)->days;
                        ?>
                            <tr class="pagamento-atrasado">
                                <td><?php echo $devedor['id']; ?></td>
                                <td><?php echo htmlspecialchars($devedor['nome']); ?></td>
                                <td><?php echo htmlspecialchars($devedor['telefone']); ?></td>
                                <td>R$ <?php echo number_format($devedor['valor_atual'], 2, ',', '.'); ?></td>
                                <td><?php echo $dataUltPag->format('d/m/Y'); ?></td>
                                <td><?php echo $diasAtraso; ?></td>
                                <td>
                                    <a href="pagamento.php?id=<?php echo $devedor['id']; ?>" class="btn btn-info">Registrar Pagamento</a>
                                    <a href="detalhes.php?id=<?php echo $devedor['id']; ?>" class="btn">Detalhes</a>
                                    <a href="editar.php?id=<?php echo $devedor['id']; ?>" class="btn btn-warning">Editar</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($pagamentosHoje)): ?>
            <div class="card card-hoje">
                <h2>Pagamentos do Dia <?php echo !empty($termoPesquisa) ? '(Resultados da Pesquisa)' : ''; ?></h2>
                <div class="alert alert-warning">
                    <strong>Atenção!</strong> Estes devedores estão na data de pagamento hoje.
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nome</th>
                            <th>Telefone</th>
                            <th>Valor com Juros (40%)</th>
                            <th>Data Último Pag.</th>
                            <th>Último Pagamento</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pagamentosHoje as $devedor): 
                            $valorComJuros = $devedor['valor_inicial'] * 1.4;
                        ?>
                            <tr class="pagamento-hoje">
                                <td><?php echo $devedor['id']; ?></td>
                                <td><?php echo htmlspecialchars($devedor['nome']); ?></td>
                                <td><?php echo htmlspecialchars($devedor['telefone']); ?></td>
                                <td>R$ <?php echo number_format($valorComJuros, 2, ',', '.'); ?></td>
                                <td><?php echo date('d/m/Y', strtotime($devedor['data_ultimo_pagamento'])); ?></td>
                                <td><?php echo $devedor['ultimo_pagamento']; ?></td>
                                <td>
                                    <a href="pagamento.php?id=<?php echo $devedor['id']; ?>" class="btn btn-info">Registrar Pagamento</a>
                                    <a href="detalhes.php?id=<?php echo $devedor['id']; ?>" class="btn">Detalhes</a>
                                    <a href="editar.php?id=<?php echo $devedor['id']; ?>" class="btn btn-warning">Editar</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
            
            <div class="card">
                <h2>Lista Geral de Devedores <?php echo !empty($termoPesquisa) ? '(Resultados da Pesquisa)' : ''; ?></h2>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nome</th>
                            <th>Telefone</th>
                            <th>Valor Atual</th>
                            <th>Data Último Pag.</th>
                            <th>Último Pagamento</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($outrosDevedores as $devedor): ?>
                            <tr>
                                <td><?php echo $devedor['id']; ?></td>
                                <td><?php echo htmlspecialchars($devedor['nome']); ?></td>
                                <td><?php echo htmlspecialchars($devedor['telefone']); ?></td>
                                <td>R$ <?php echo number_format($devedor['valor_atual'], 2, ',', '.'); ?></td> 
                                <td><?php echo date('d/m/Y', strtotime($devedor['data_ultimo_pagamento'])); ?></td>
                                <td><?php echo $devedor['ultimo_pagamento']; ?></td>
                                <td>
                                    <a href="pagamento.php?id=<?php echo $devedor['id']; ?>" class="btn btn-info">Registrar Pagamento</a>
                                    <a href="detalhes.php?id=<?php echo $devedor['id']; ?>" class="btn">Detalhes</a>
                                    <a href="editar.php?id=<?php echo $devedor['id']; ?>" class="btn btn-warning">Editar</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
        </div>
        
        <div class="alert alert-warning">
            <strong>Lembrete:</strong> Juros de 40% ao mês. Atraso (sem pagamento) gera multa de R$10 por dia.
        </div>
        <?php include 'footer.php'; ?>
    </div>
</body>
</html>

