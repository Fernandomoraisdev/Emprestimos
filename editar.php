<?php
/**
 * Sistema de Controle de Empréstimos - Página de Edição
 * 
 * Esta página permite editar informações de um devedor existente.
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

// Processa o formulário de edição se enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao']) && $_POST['acao'] === 'editar') {
    // Valida os dados do formulário
    $nome = trim($_POST['nome']);
    $telefone = trim($_POST['telefone']);
    $endereco = trim($_POST['endereco']);
    $valorInicial = floatval($_POST['valor_inicial']);
    $dataEmprestimo = $_POST['data_emprestimo'];
    $dataUltimoPagamento = $_POST['data_ultimo_pagamento'];
    $ultimoPagamento = $_POST['ultimo_pagamento'];
    
    // Verifica se os campos obrigatórios foram preenchidos
    if (empty($nome) || $valorInicial <= 0 || empty($dataEmprestimo) || empty($dataUltimoPagamento)) {
        $mensagem = "Por favor, preencha todos os campos obrigatórios.";
        $tipoAlerta = "danger";
    } else {
        // Atualiza os dados do devedor
        $devedorAtualizado = [
            'nome' => $nome,
            'telefone' => $telefone,
            'endereco' => $endereco,
            'valor_inicial' => $valorInicial,
            'valor_atual' => $devedor['valor_atual'],
            'data_emprestimo' => $dataEmprestimo,
            'data_ultimo_pagamento' => $dataUltimoPagamento,
            'ultimo_pagamento' => $ultimoPagamento
        ];
        
        // Atualiza o valor atual com base no tipo de pagamento
        if ($ultimoPagamento === 'total') {
            $devedorAtualizado['valor_atual'] = 0;
        } else if ($ultimoPagamento === 'juros') {
            $devedorAtualizado['valor_atual'] = $valorInicial;
        }
        
        // Atualiza o devedor no banco de dados
        if (atualizarDevedor($db, $id, $devedorAtualizado)) {
            $mensagem = "Informações atualizadas com sucesso!";
            $tipoAlerta = "success";
            
            // Se o pagamento foi total, move para quitados
            if ($ultimoPagamento === 'total') {
                if (moverParaQuitados($db, $id)) {
                    header('Location: index.php');
                    exit;
                }
            }
        } else {
            $mensagem = "Erro ao atualizar as informações.";
            $tipoAlerta = "danger";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Empréstimo - Sistema de Controle de Empréstimos</title>
    <link rel="stylesheet" href="styles-dark.css">
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Editar Empréstimo</h1>
            <div class="navigation">
                <a href="index.php" class="btn btn-primary">Página Inicial</a>
                <a href="cadastro.php" class="btn btn-success">Cadastrar Novo</a>
                <a href="detalhes.php?id=<?php echo $id; ?>" class="btn btn-info">Detalhes</a>
                <a href="pagamento.php?id=<?php echo $id; ?>" class="btn btn-warning">Registrar Pagamento</a>
            </div>
        </div>
        
        <?php if (isset($mensagem)): ?>
            <div class="alert alert-<?php echo $tipoAlerta; ?>">
                <?php echo $mensagem; ?>
            </div>
        <?php endif; ?>
        
        <div class="card">
            <h2>Editar Informações</h2>
            <form method="post" action="">
                <input type="hidden" name="acao" value="editar">
                
                <div class="form-group">
                    <label for="nome">Nome:</label>
                    <input type="text" id="nome" name="nome" value="<?php echo htmlspecialchars($devedor['nome']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="telefone">Telefone:</label>
                    <input type="text" id="telefone" name="telefone" value="<?php echo htmlspecialchars($devedor['telefone']); ?>" placeholder="(00) 00000-0000">
                </div>
                
                <div class="form-group">
                    <label for="endereco">Endereço:</label>
                    <input type="text" id="endereco" name="endereco" value="<?php echo htmlspecialchars($devedor['endereco']); ?>" placeholder="Rua, número, bairro, cidade">
                </div>
                
                <div class="form-group">
                    <label for="valor_inicial">Valor Inicial (R$):</label>
                    <input type="number" id="valor_inicial" name="valor_inicial" step="0.01" min="0.01" value="<?php echo $devedor['valor_inicial']; ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="data_emprestimo">Data do Empréstimo:</label>
                    <input type="date" id="data_emprestimo" name="data_emprestimo" value="<?php echo $devedor['data_emprestimo']; ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="data_ultimo_pagamento">Data do Último Pagamento:</label>
                    <input type="date" id="data_ultimo_pagamento" name="data_ultimo_pagamento" value="<?php echo $devedor['data_ultimo_pagamento']; ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="ultimo_pagamento">Último Pagamento:</label>
                    <select id="ultimo_pagamento" name="ultimo_pagamento" required>
                        <option value="total" <?php echo $devedor['ultimo_pagamento'] === 'total' ? 'selected' : ''; ?>>Pagamento Total</option>
                        <option value="juros" <?php echo $devedor['ultimo_pagamento'] === 'juros' ? 'selected' : ''; ?>>Pagamento de Juros</option>
                        <option value="nenhum" <?php echo $devedor['ultimo_pagamento'] === 'nenhum' ? 'selected' : ''; ?>>Nenhum Pagamento</option>
                    </select>
                </div>
                
                <button type="submit" class="btn btn-primary">Salvar Alterações</button>
                <a href="index.php" class="btn btn-secondary">Cancelar</a>
            </form>
        </div>
        
        <div class="alert alert-info">
            <h3>Informações Importantes:</h3>
            <ul>
                <li>Se alterar para "Pagamento Total", o valor atual será zerado.</li>
                <li>Se alterar para "Pagamento de Juros", o valor atual será igual ao valor inicial.</li>
                <li>Se alterar para "Nenhum Pagamento", os juros diários serão aplicados a partir da data informada.</li>
            </ul>
        </div>
        
        <?php include 'footer.php'; ?>
    </div>
</body>
</html>
