<?php
/**
 * Sistema de Controle de Empréstimos - Resumo Financeiro
 * 
 * Este componente exibe um resumo financeiro com os valores totais
 * emprestados, valores atuais e juros a receber.
 * 
 * @author Manus AI
 * @version 2.5
 */

// Garante que as variáveis necessárias existam, mesmo que vazias
$totalEmprestado = $totalEmprestado ?? 0;
$totalComJuros = $totalComJuros ?? 0;
$totalJuros = $totalJuros ?? 0;
$pagamentosHoje = $pagamentosHoje ?? []; // Usado para a contagem
$totalJurosMensalAtual = $totalJurosMensalAtual ?? 0;
$countPagamentosHoje = $countPagamentosHoje ?? count($pagamentosHoje); // Garante que a contagem exista

?>
<div class="card financial-summary">
    <h2>Resumo Financeiro</h2>
    <div class="summary-container">
        <div class="summary-item">
            <div class="summary-label">Total Emprestado (sem juros):</div>
            <div class="summary-value">R$ <?php echo number_format($totalEmprestado, 2, ',', '.'); ?></div>
        </div>
        <div class="summary-item">
            <div class="summary-label">Total com Juros (Base 40%):</div>
            <div class="summary-value">R$ <?php echo number_format($totalComJuros, 2, ',', '.'); ?></div>
        </div>
        <div class="summary-item highlight">
            <div class="summary-label">Total de Juros a Receber Hoje:</div>
            <div class="summary-value">R$ <?php echo number_format($totalJuros, 2, ',', '.'); ?></div>
            <div class="summary-note"><?php echo $countPagamentosHoje > 0 ? '(' . $countPagamentosHoje . ' pagamento' . ($countPagamentosHoje > 1 ? 's' : '') . ' hoje)' : '(Nenhum pagamento hoje)'; ?></div>
        </div>
        <div class="summary-item special">
            <div class="summary-label">Juros Mensais Possíveis (Base 40%):</div>
            <div class="summary-value">R$ <?php echo number_format($totalJurosMensalAtual, 2, ',', '.'); ?></div>
            <div class="summary-note">Se todos pagarem apenas os juros este mês</div>
        </div>
    </div>
</div>

