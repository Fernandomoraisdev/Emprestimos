<?php
/**
 * Sistema de Controle de Empréstimos - Resumo Financeiro
 * 
 * Este componente exibe um resumo financeiro com os totais de valores
 * emprestados, valores atuais e juros a receber.
 * 
 * @author Manus AI
 * @version 1.3
 */
?>

<div class="card financial-summary">
    <h2>Resumo Financeiro</h2>
    <div class="summary-grid">
        <div class="summary-item">
            <div class="summary-label">Total Emprestado (sem juros):</div>
            <div class="summary-value">R$ <?php echo number_format($totalEmprestado, 2, ',', '.'); ?></div>
        </div>
        <div class="summary-item">
            <div class="summary-label">Total com Juros:</div>
            <div class="summary-value">R$ <?php echo number_format($totalComJuros, 2, ',', '.'); ?></div>
        </div>
        <div class="summary-item highlight">
            <div class="summary-label">Total de Juros a Receber:</div>
            <div class="summary-value">R$ <?php echo number_format($totalJuros, 2, ',', '.'); ?></div>
        </div>
        <div class="summary-item special">
            <div class="summary-label">Juros Mensais Possíveis:</div>
            <div class="summary-value">R$ <?php echo number_format($totalJurosMensais, 2, ',', '.'); ?></div>
            <div class="summary-note">Se todos pagarem apenas os juros este mês</div>
        </div>
    </div>
</div>
