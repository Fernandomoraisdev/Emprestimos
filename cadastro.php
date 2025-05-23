<?php
/**
 * Sistema de Controle de Empréstimos - Página de Cadastro
 * 
 * Esta página é exclusiva para cadastro de novos devedores,
 * separada da listagem principal para melhor organização.
 * 
 * Versão 2.1 - Separação do cadastro em página exclusiva
 * 
 * @author Manus AI
 * @version 2.1
 */

// Inclui o arquivo de funções do banco de dados
require_once 'db.php';

// Inicializa o banco de dados
$db = inicializarBancoDados();

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
            
            // Define mensagem de sucesso
            $mensagemSucesso = "Devedor cadastrado com sucesso!";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastrar Novo Empréstimo - Sistema de Controle de Empréstimos</title>
    <link rel="stylesheet" href="styles-dark.css">
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Cadastrar Novo Empréstimo</h1>
            <div class="navigation">
                <a href="index.php" class="btn btn-primary">Página Inicial</a>
                <a href="quitados.php" class="btn btn-info">Ver Quitados</a>
            </div>
        </div>
        
        <?php if (isset($mensagemErro)): ?>
            <div class="alert alert-danger">
                <?php echo $mensagemErro; ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($mensagemSucesso)): ?>
            <div class="alert alert-success">
                <?php echo $mensagemSucesso; ?>
            </div>
        <?php endif; ?>
        
        <div class="card">
            <h2>Informações do Novo Empréstimo</h2>
            <form method="post" action="">
                <input type="hidden" name="acao" value="cadastrar">
                
                <div class="form-group">
                    <label for="nome">Nome:</label>
                    <input type="text" id="nome" name="nome" required>
                </div>
                
                <div class="form-group">
                    <label for="telefone">Telefone:</label>
                    <input type="text" id="telefone" name="telefone" placeholder="(00) 00000-0000">
                </div>
                
                <div class="form-group">
                    <label for="endereco">Endereço:</label>
                    <input type="text" id="endereco" name="endereco" placeholder="Rua, número, bairro, cidade">
                </div>
                
                <div class="form-group">
                    <label for="valor">Valor Emprestado (R$):</label>
                    <input type="number" id="valor" name="valor" step="0.01" min="0.01" required>
                </div>
                
                <div class="form-group">
                    <label for="data_emprestimo">Data do Empréstimo:</label>
                    <input type="date" id="data_emprestimo" name="data_emprestimo" value="<?php echo date('Y-m-d'); ?>" required>
                </div>
                
                <button type="submit" class="btn btn-primary">Cadastrar</button>
                <a href="index.php" class="btn btn-secondary">Cancelar</a>
            </form>
        </div>
        
        <div class="alert alert-info">
            <strong>Lembrete:</strong> Os juros são calculados automaticamente a 40% ao mês. Em caso de atraso, são cobrados R$10 por dia.
        </div>
        
        <div class="navigation-footer">
            <a href="index.php" class="btn btn-secondary">Voltar para Lista de Devedores</a>
        </div>
        
        <?php include 'footer.php'; ?>
    </div>
</body>
</html>
