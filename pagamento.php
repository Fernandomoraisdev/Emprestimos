<?php
/**
 * Sistema de Controle de Empréstimos - Página de Pagamento
 * 
 * Esta página permite registrar pagamentos para um devedor específico.
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

// Processa o formulário de pagamento se enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tipo_pagamento'])) {
    $tipoPagamento = $_POST['tipo_pagamento'];
    $dataAtual = date('Y-m-d');
    
    // Atualiza os dados do devedor com base no tipo de pagamento
    $devedorAtualizado = $devedor;
    $devedorAtualizado['data_ultimo_pagamento'] = $dataAtual;
    $devedorAtualizado['ultimo_pagamento'] = $tipoPagamento;
    
    switch ($tipoPagamento) {
        case 'total':
            // Pagamento total - quita a dívida
            $devedorAtualizado['valor_atual'] = 0;
            
            // Atualiza o devedor no banco de dados
            if (atualizarDevedor($db, $id, $devedorAtualizado)) {
                // Move o devedor para a lista de quitados
                if (moverParaQuitados($db, $id)) {
                    $mensagem = "Pagamento total registrado com sucesso! Dívida quitada e movida para a lista de quitados.";
                    $tipoAlerta = "success";
                } else {
                    $mensagem = "Pagamento registrado, mas houve um erro ao mover para quitados.";
                    $tipoAlerta = "warning";
                }
            } else {
                $mensagem = "Erro ao registrar o pagamento.";
                $tipoAlerta = "danger";
            }
            break;
            
        case 'juros':
            // Pagamento apenas dos juros
            // Calcula o valor dos juros (40% do valor inicial)
            $valorJuros = $devedor['valor_inicial'] * 0.4;
            
            // Atualiza o valor atual para o valor inicial
            $devedorAtualizado['valor_atual'] = $devedor['valor_inicial'];
            
            // Atualiza o devedor no banco de dados
            if (atualizarDevedor($db, $id, $devedorAtualizado)) {
                $mensagem = "Pagamento de juros registrado com sucesso! Valor pago: R$ " . number_format($valorJuros, 2, ',', '.');
                $tipoAlerta = "success";
            } else {
                $mensagem = "Erro ao registrar o pagamento.";
                $tipoAlerta = "danger";
            }
            break;
            
        case 'nenhum':
            // Nenhum pagamento - apenas atualiza a data e o status
            // Atualiza o devedor no banco de dados
            if (atualizarDevedor($db, $id, $devedorAtualizado)) {
                $mensagem = "Registrado que não houve pagamento. Juros de R$10 por dia serão aplicados.";
                $tipoAlerta = "warning";
            } else {
                $mensagem = "Erro ao registrar o status de pagamento.";
                $tipoAlerta = "danger";
            }
            break;
            
        default:
            $mensagem = "Tipo de pagamento inválido.";
            $tipoAlerta = "danger";
            break;
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrar Pagamento - Sistema de Controle de Empréstimos</title>
    <link rel="stylesheet" href="styles-dark.css">
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Registrar Pagamento</h1>
            <div class="navigation">
                <a href="index.php" class="btn btn-primary">Página Inicial</a>
                <a href="cadastro.php" class="btn btn-success">Cadastrar Novo</a>
                <a href="detalhes.php?id=<?php echo $id; ?>" class="btn btn-info">Detalhes</a>
            </div>
        </div>
        
        <?php if (isset($mensagem)): ?>
            <div class="alert alert-<?php echo $tipoAlerta; ?>">
                <?php echo $mensagem; ?>
            </div>
        <?php endif; ?>
        
        <div class="card">
            <h2>Informações do Devedor</h2>
            <div class="info-row">
                <div class="info-label">Nome:</div>
                <div class="info-value"><?php echo htmlspecialchars($devedor['nome']); ?></div>
            </div>
            <div class="info-row">
                <div class="info-label">Telefone:</div>
                <div class="info-value"><?php echo htmlspecialchars($devedor['telefone'] ?: 'Não informado'); ?></div>
            </div>
            <div class="info-row">
                <div class="info-label">Valor Inicial:</div>
                <div class="info-value">R$ <?php echo number_format($devedor['valor_inicial'], 2, ',', '.'); ?></div>
            </div>
            <div class="info-row">
                <div class="info-label">Valor com Juros (40%):</div>
                <div class="info-value">R$ <?php echo number_format($devedor['valor_inicial'] * 1.4, 2, ',', '.'); ?></div>
            </div>
            <div class="info-row">
                <div class="info-label">Data do Empréstimo:</div>
                <div class="info-value"><?php echo date('d/m/Y', strtotime($devedor['data_emprestimo'])); ?></div>
            </div>
            <div class="info-row">
                <div class="info-label">Último Pagamento:</div>
                <div class="info-value"><?php echo $devedor['ultimo_pagamento']; ?> (<?php echo date('d/m/Y', strtotime($devedor['data_ultimo_pagamento'])); ?>)</div>
            </div>
        </div>
        
        <div class="card">
            <h2>Registrar Pagamento</h2>
            <form method="post" action="">
                <div class="payment-options">
                    <div class="payment-option total" onclick="selecionarPagamento('total')">
                        <div class="payment-icon">💰</div>
                        <h3>Pagamento Total</h3>
                        <p>Quita a dívida completamente</p>
                    </div>
                    <div class="payment-option juros" onclick="selecionarPagamento('juros')">
                        <div class="payment-icon">💸</div>
                        <h3>Pagamento de Juros</h3>
                        <p>Paga apenas os juros mensais (40%)</p>
                    </div>
                    <div class="payment-option nenhum" onclick="selecionarPagamento('nenhum')">
                        <div class="payment-icon">❌</div>
                        <h3>Nenhum Pagamento</h3>
                        <p>Registra que não houve pagamento</p>
                    </div>
                </div>
                
                <input type="hidden" id="tipoPagamento" name="tipo_pagamento" value="">
                <button type="submit" id="btnConfirmar" class="btn btn-primary" disabled>Confirmar Pagamento</button>
                <a href="index.php" class="btn btn-secondary">Cancelar</a>
            </form>
        </div>
        
        <div class="alert alert-info">
            <h3>Informações sobre os tipos de pagamento:</h3>
            <ul>
                <li><strong>Pagamento Total:</strong> Quita a dívida completamente e move o registro para a lista de quitados.</li>
                <li><strong>Pagamento de Juros:</strong> Paga apenas os juros mensais (40% do valor inicial) e mantém o valor principal.</li>
                <li><strong>Nenhum Pagamento:</strong> Registra que não houve pagamento, aplicando juros de R$10 por dia de atraso.</li>
            </ul>
            <p>Se passar um mês sem pagamento, será aplicado novamente 40% de juros sobre o valor atual.</p>
        </div>
        
        <?php include 'footer.php'; ?>
    </div>
    
    <script>
        // Função para selecionar o tipo de pagamento
        function selecionarPagamento(tipo) {
            // Remove a classe 'selected' de todas as opções
            document.querySelectorAll('.payment-option').forEach(function(option) {
                option.classList.remove('selected');
            });
            
            // Adiciona a classe 'selected' à opção selecionada
            document.querySelector('.payment-option:nth-child(' + (tipo === 'total' ? '1' : tipo === 'juros' ? '2' : '3') + ')').classList.add('selected');
            
            // Define o valor do campo oculto
            document.getElementById('tipoPagamento').value = tipo;
            
            // Habilita o botão de confirmar
            document.getElementById('btnConfirmar').disabled = false;
        }
    </script>
</body>
</html>
