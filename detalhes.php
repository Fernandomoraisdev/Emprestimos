<?php
/**
 * Sistema de Controle de Empréstimos - Página de Detalhes
 * 
 * Esta página exibe informações detalhadas sobre um devedor específico.
 * 
 * Versão 2.0 - Migração para SQLite com campos de telefone e endereço
 * 
 * @author Manus AI
 * @version 2.0
 */

// Inclui o arquivo de funções do banco de dados
require_once 'db.php';

// Inicializa o banco de dados
$db = inicializarBancoDados();

// Verifica se o ID foi fornecido
if (!isset($_GET['id'])) {
    header('Location: index.php');
    exit;
}

$id = $_GET['id'];

// Busca o devedor pelo ID
$devedor = buscarDevedor($db, $id);

// Se o devedor não for encontrado, redireciona para a página inicial
if (!$devedor) {
    header('Location: index.php');
    exit;
}

// Calcula o valor com juros (valor inicial + 40%)
$valorComJuros = $devedor['valor_inicial'] * 1.4;

// Calcula o tempo desde o empréstimo
$dataEmprestimo = new DateTime($devedor['data_emprestimo']);
$dataAtual = new DateTime();
$intervalo = $dataEmprestimo->diff($dataAtual);

// Formata o tempo decorrido
if ($intervalo->y > 0) {
    $tempoDecorrido = $intervalo->y . ' ano(s) e ' . $intervalo->m . ' mês(es)';
} else if ($intervalo->m > 0) {
    $tempoDecorrido = $intervalo->m . ' mês(es) e ' . $intervalo->d . ' dia(s)';
} else {
    $tempoDecorrido = $intervalo->d . ' dia(s)';
}

// Calcula o tempo desde o último pagamento
$dataUltimoPagamento = new DateTime($devedor['data_ultimo_pagamento']);
$intervaloUltimoPagamento = $dataUltimoPagamento->diff($dataAtual);

// Formata o tempo desde o último pagamento
if ($intervaloUltimoPagamento->y > 0) {
    $tempoUltimoPagamento = $intervaloUltimoPagamento->y . ' ano(s) e ' . $intervaloUltimoPagamento->m . ' mês(es)';
} else if ($intervaloUltimoPagamento->m > 0) {
    $tempoUltimoPagamento = $intervaloUltimoPagamento->m . ' mês(es) e ' . $intervaloUltimoPagamento->d . ' dia(s)';
} else {
    $tempoUltimoPagamento = $intervaloUltimoPagamento->d . ' dia(s)';
}

// Calcula o valor dos juros
$valorJuros = $valorComJuros - $devedor['valor_inicial'];

// Calcula o percentual de juros em relação ao valor inicial
$percentualJuros = ($valorJuros / $devedor['valor_inicial']) * 100;
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalhes do Devedor - Sistema de Controle de Empréstimos</title>
    <link rel="stylesheet" href="styles-dark.css">
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Detalhes do Devedor</h1>
            <div class="navigation">
                <a href="index.php" class="btn btn-primary">Página Inicial</a>
                <a href="cadastro.php" class="btn btn-success">Cadastrar Novo</a>
                <a href="pagamento.php?id=<?php echo $id; ?>" class="btn btn-info">Registrar Pagamento</a>
                <a href="editar.php?id=<?php echo $id; ?>" class="btn btn-warning">Editar</a>
            </div>
        </div>
        
        <div class="info-box">
            <h2>Informações Gerais</h2>
            <div class="info-row">
                <div class="info-label">Nome:</div>
                <div class="info-value"><?php echo htmlspecialchars($devedor['nome']); ?></div>
            </div>
            <div class="info-row">
                <div class="info-label">Telefone:</div>
                <div class="info-value"><?php echo htmlspecialchars($devedor['telefone'] ?: 'Não informado'); ?></div>
            </div>
            <div class="info-row">
                <div class="info-label">Endereço:</div>
                <div class="info-value"><?php echo htmlspecialchars($devedor['endereco'] ?: 'Não informado'); ?></div>
            </div>
        </div>
        
        <div class="info-box">
            <h2>Informações do Empréstimo</h2>
            <div class="info-row">
                <div class="info-label">Valor Inicial:</div>
                <div class="info-value">R$ <?php echo number_format($devedor['valor_inicial'], 2, ',', '.'); ?></div>
            </div>
            <div class="info-row">
                <div class="info-label">Valor com Juros (40%):</div>
                <div class="info-value">R$ <?php echo number_format($valorComJuros, 2, ',', '.'); ?></div>
            </div>
            <div class="info-row">
                <div class="info-label">Valor dos Juros:</div>
                <div class="info-value">R$ <?php echo number_format($valorJuros, 2, ',', '.'); ?> (<?php echo number_format($percentualJuros, 2); ?>%)</div>
            </div>
            <div class="info-row">
                <div class="info-label">Data do Empréstimo:</div>
                <div class="info-value"><?php echo date('d/m/Y', strtotime($devedor['data_emprestimo'])); ?></div>
            </div>
            <div class="info-row">
                <div class="info-label">Tempo Decorrido:</div>
                <div class="info-value"><?php echo $tempoDecorrido; ?></div>
            </div>
        </div>
        
        <div class="info-box">
            <h2>Informações de Pagamento</h2>
            <div class="info-row">
                <div class="info-label">Último Pagamento:</div>
                <div class="info-value"><?php echo $devedor['ultimo_pagamento']; ?></div>
            </div>
            <div class="info-row">
                <div class="info-label">Data do Último Pagamento:</div>
                <div class="info-value"><?php echo date('d/m/Y', strtotime($devedor['data_ultimo_pagamento'])); ?></div>
            </div>
            <div class="info-row">
                <div class="info-label">Tempo Desde o Último Pagamento:</div>
                <div class="info-value"><?php echo $tempoUltimoPagamento; ?></div>
            </div>
        </div>
        
        <div class="alert alert-warning">
            <strong>Lembrete:</strong> Os juros são calculados automaticamente a 40% ao mês. Em caso de atraso, são cobrados R$10 por dia.
        </div>
        
        <div class="navigation-footer">
            <a href="index.php" class="btn btn-secondary">Voltar para Lista</a>
        </div>
        
        <?php include 'footer.php'; ?>
    </div>
</body>
</html>
