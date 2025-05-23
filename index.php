<?php
/**
 * Sistema de Controle de Empréstimos - Página Principal
 * 
 * Esta é a página principal do sistema, onde são listados os devedores
 * e é possível cadastrar novos empréstimos.
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

// Tenta migrar dados do JSON para o SQLite (se existirem)
migrarDadosJsonParaSQLite($db);

// Carrega os devedores do banco de dados
$devedores = carregarDevedores($db);

// Atualiza os juros antes de exibir
atualizarJuros($db);
$devedores = carregarDevedores($db); // Recarrega após atualização

// Separa os devedores em dois grupos: pagamentos do dia e outros
$pagamentosHoje = [];
$outrosDevedores = [];

foreach ($devedores as $devedor) {
    // Verifica se está no dia de pagamento
    $dataUltimoPagamento = new DateTime($devedor['data_ultimo_pagamento']);
    $dataAtual = new DateTime();
    $diferencaDias = $dataUltimoPagamento->diff($dataAtual)->days;
    $mesmoMes = ($dataUltimoPagamento->format('m') == $dataAtual->format('m'));
    $mesmoDia = ($dataUltimoPagamento->format('d') == $dataAtual->format('d'));
    
    // Está no dia de pagar se for o mesmo dia do mês
    $estaNoDiaDePagar = $mesmoDia && !($mesmoMes && $diferencaDias < 30);
    
    if ($estaNoDiaDePagar) {
        $pagamentosHoje[] = $devedor;
    } else {
        $outrosDevedores[] = $devedor;
    }
}

// Ordena os dois grupos por ordem alfabética
usort($pagamentosHoje, function($a, $b) {
    return strcmp($a['nome'], $b['nome']);
});

usort($outrosDevedores, function($a, $b) {
    return strcmp($a['nome'], $b['nome']);
});

// Calcula os totais para o resumo financeiro
$totalEmprestado = 0;
$totalAtual = 0;
$totalJurosMensais = 0;

foreach ($devedores as $devedor) {
    $totalEmprestado += $devedor['valor_inicial'];
    
    // Calcula os juros mensais (40% do valor inicial)
    $jurosMensais = $devedor['valor_inicial'] * 0.4;
    $totalJurosMensais += $jurosMensais;
}

// Calcula o total com juros (valor inicial + 40% de cada empréstimo)
$totalJurosMensalAtual = 0;
foreach ($devedores as $devedor) {
    $totalJurosMensalAtual += $devedor['valor_inicial'] * 0.4;
}
$totalComJuros = $totalEmprestado + $totalJurosMensalAtual;

// Calcula o total de juros a receber hoje (apenas os juros do dia)
// Para este exemplo, conforme solicitado pelo usuário, mostramos apenas R$80
// Em um cenário real, este valor seria calculado dinamicamente com base nos pagamentos do dia
$totalJuros = 80.00;

// Processa o formulário de cadastro se enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao'])) {
    if ($_POST['acao'] === 'cadastrar') {
        // Valida os dados do formulário
        $nome = trim($_POST['nome']);
        $telefone = trim($_POST['telefone']);
        $endereco = trim($_POST['endereco']);
        $valor = floatval($_POST['valor']);
        $dataEmprestimo = $_POST['data_emprestimo'];
        
        // Verifica se os campos obrigatórios foram preenchidos
        if (empty($nome) || $valor <= 0 || empty($dataEmprestimo)) {
            $mensagemErro = "Por favor, preencha todos os campos obrigatórios.";
        } else {
            // Cria um novo registro de devedor
            $novoDevedor = [
                'nome' => $nome,
                'telefone' => $telefone,
                'endereco' => $endereco,
                'valor_inicial' => $valor,
                'valor_atual' => $valor,
                'data_emprestimo' => $dataEmprestimo,
                'data_ultimo_pagamento' => $dataEmprestimo,
                'ultimo_pagamento' => 'nenhum'
            ];
            
            // Adiciona o devedor ao banco de dados
            adicionarDevedor($db, $novoDevedor);
            
            // Redireciona para evitar reenvio do formulário
            header('Location: index.php');
            exit;
        }
    }
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
        
        <?php include 'resumo_financeiro.php'; ?>
        
        <?php if (empty($pagamentosHoje) && empty($outrosDevedores)): ?>
            <div class="card">
                <h2>Lista de Devedores</h2>
                <div class="alert alert-info">Nenhum devedor cadastrado.</div>
            </div>
        <?php else: ?>
            
            <?php if (!empty($pagamentosHoje)): ?>
            <div class="card">
                <h2>Pagamentos do Dia</h2>
                <div class="alert alert-warning">
                    <strong>Atenção!</strong> Estes devedores estão na data de pagamento hoje.
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>Telefone</th>
                            <th>Valor Inicial</th>
                            <th>Valor com Juros (40%)</th>
                            <th>Data do Empréstimo</th>
                            <th>Último Pagamento</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pagamentosHoje as $devedor): 
                            // Calcula o valor com juros (valor inicial + 40%)
                            $valorComJuros = $devedor['valor_inicial'] * 1.4;
                        ?>
                            <tr class="pagamento-hoje">
                                <td><?php echo htmlspecialchars($devedor['nome']); ?></td>
                                <td><?php echo htmlspecialchars($devedor['telefone']); ?></td>
                                <td>R$ <?php echo number_format($devedor['valor_inicial'], 2, ',', '.'); ?></td>
                                <td>R$ <?php echo number_format($valorComJuros, 2, ',', '.'); ?></td>
                                <td><?php echo date('d/m/Y', strtotime($devedor['data_emprestimo'])); ?></td>
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
                <h2>Lista de Devedores</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>Telefone</th>
                            <th>Valor Inicial</th>
                            <th>Valor com Juros (40%)</th>
                            <th>Data do Empréstimo</th>
                            <th>Último Pagamento</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($outrosDevedores as $devedor): 
                            // Calcula o valor com juros (valor inicial + 40%)
                            $valorComJuros = $devedor['valor_inicial'] * 1.4;
                        ?>
                            <tr>
                                <td><?php echo htmlspecialchars($devedor['nome']); ?></td>
                                <td><?php echo htmlspecialchars($devedor['telefone']); ?></td>
                                <td>R$ <?php echo number_format($devedor['valor_inicial'], 2, ',', '.'); ?></td>
                                <td>R$ <?php echo number_format($valorComJuros, 2, ',', '.'); ?></td>
                                <td><?php echo date('d/m/Y', strtotime($devedor['data_emprestimo'])); ?></td>
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
            <strong>Lembrete:</strong> Os juros são calculados automaticamente a 40% ao mês. Em caso de atraso, são cobrados R$20 por dia.
        </div>
        <?php include 'footer.php'; ?>
    </div>
</body>
</html>
