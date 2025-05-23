<?php
/**
 * Sistema de Controle de Empréstimos - Página de Empréstimos Quitados
 * 
 * Esta página exibe a lista de empréstimos que foram quitados.
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

// Carrega os empréstimos quitados
$quitados = carregarQuitados($db);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Empréstimos Quitados - Sistema de Controle de Empréstimos</title>
    <link rel="stylesheet" href="styles-dark.css">
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Empréstimos Quitados</h1>
            <div class="navigation">
                <a href="index.php" class="btn btn-primary">Página Inicial</a>
                <a href="cadastro.php" class="btn btn-success">Cadastrar Novo</a>
            </div>
        </div>
        
        <div class="card">
            <h2>Histórico de Empréstimos Quitados</h2>
            
            <?php if (empty($quitados)): ?>
                <div class="alert alert-info">Nenhum empréstimo quitado encontrado.</div>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>Telefone</th>
                            <th>Endereço</th>
                            <th>Valor</th>
                            <th>Data do Empréstimo</th>
                            <th>Data de Quitação</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($quitados as $quitado): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($quitado['nome']); ?></td>
                                <td><?php echo htmlspecialchars($quitado['telefone'] ?: 'Não informado'); ?></td>
                                <td><?php echo htmlspecialchars($quitado['endereco'] ?: 'Não informado'); ?></td>
                                <td>R$ <?php echo number_format($quitado['valor_inicial'], 2, ',', '.'); ?></td>
                                <td><?php echo date('d/m/Y', strtotime($quitado['data_emprestimo'])); ?></td>
                                <td><?php echo date('d/m/Y', strtotime($quitado['data_quitacao'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        
        <div class="navigation-footer">
            <a href="index.php" class="btn btn-secondary">Voltar para Lista de Devedores</a>
        </div>
        
        <?php include 'footer.php'; ?>
    </div>
</body>
</html>
