<?php
/**
 * Sistema de Controle de Empréstimos - Funções de Persistência
 * 
 * Este arquivo contém funções para manipulação de dados em JSON,
 * centralizando as operações de leitura e escrita para garantir consistência.
 * 
 * Versão 1.5 - Adicionado destaque para pagamentos no dia e ajuste no cálculo do valor atual
 * 
 * @author Manus AI
 * @version 1.5
 */

/**
 * Carrega os dados do arquivo JSON
 * 
 * @return array Array associativo com os dados carregados
 */
function carregarDados() {
    $arquivo = __DIR__ . '/dados.json';
    if (file_exists($arquivo)) {
        $json = file_get_contents($arquivo);
        $dados = json_decode($json, true);
        
        // Verifica se o JSON foi decodificado corretamente
        if ($dados === null && json_last_error() !== JSON_ERROR_NONE) {
            // Log do erro
            error_log('Erro ao decodificar JSON: ' . json_last_error_msg());
            // Retorna estrutura vazia em caso de erro
            return ['devedores' => [], 'quitados' => []];
        }
        
        // Garante que a estrutura tenha a lista de quitados
        if (!isset($dados['quitados'])) {
            $dados['quitados'] = [];
        }
        
        return $dados;
    } else {
        // Retorna um array vazio se o arquivo não existir
        return ['devedores' => [], 'quitados' => []];
    }
}

/**
 * Salva os dados no arquivo JSON
 * 
 * @param array $dados Array associativo com os dados a serem salvos
 * @return bool True se os dados foram salvos com sucesso, False caso contrário
 */
function salvarDados($dados) {
    $arquivo = __DIR__ . '/dados.json';
    
    // Garante que a estrutura de dados está correta
    if (!isset($dados['devedores'])) {
        $dados['devedores'] = [];
    }
    
    // Codifica os dados em JSON com formatação para facilitar leitura
    $json = json_encode($dados, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    
    // Verifica se a codificação foi bem-sucedida
    if ($json === false) {
        error_log('Erro ao codificar JSON: ' . json_last_error_msg());
        return false;
    }
    
    // Salva os dados no arquivo
    $resultado = file_put_contents($arquivo, $json);
    
    // Verifica se a escrita foi bem-sucedida
    if ($resultado === false) {
        error_log('Erro ao escrever no arquivo: ' . $arquivo);
        return false;
    }
    
    return true;
}

/**
 * Atualiza os juros de todos os devedores
 * 
 * Esta função aplica as regras de negócio para cálculo de juros:
 * - 40% de juros mensais
 * - R$10 por dia de atraso se não houver pagamento
 * - Reaplicação de 40% se passar um mês sem pagamento
 * 
 * @param array &$devedores Array de devedores a ser atualizado (passado por referência)
 * @return array Array de devedores atualizado
 */
function atualizarJuros(&$devedores) {
    $dataAtual = new DateTime();
    
    foreach ($devedores as &$devedor) {
        // Pula devedores com dívida zerada
        if ($devedor['valor_atual'] <= 0) {
            continue;
        }
        
        $dataUltimoPagamento = new DateTime($devedor['data_ultimo_pagamento']);
        $dataUltimaAtualizacao = new DateTime($devedor['data_ultima_atualizacao']);
        
        // Calcula o intervalo desde o último pagamento
        $intervalo = $dataUltimoPagamento->diff($dataAtual);
        
        // Verifica se passou um mês desde o último pagamento
        if ($intervalo->m >= 1 || $intervalo->y > 0) {
            // Reaplicação de 40% se passou um mês sem pagamento
            $devedor['valor_atual'] = $devedor['valor_atual'] * 1.4;
            $devedor['data_ultima_atualizacao'] = $dataAtual->format('Y-m-d');
            
            // Registra log da atualização
            error_log(sprintf(
                'Juros mensais aplicados para %s: Valor anterior: %.2f, Novo valor: %.2f',
                $devedor['nome'],
                $devedor['valor_atual'] / 1.4,
                $devedor['valor_atual']
            ));
        } else {
            // Verifica se há dias de atraso (se não houve pagamento)
            $diasAtraso = $dataUltimaAtualizacao->diff($dataAtual)->days;
            
            if ($diasAtraso > 0 && $devedor['ultimo_pagamento'] == 'nenhum') {
                // Adiciona R$10 por dia de atraso
                $valorAnterior = $devedor['valor_atual'];
                $devedor['valor_atual'] += (20 * $diasAtraso);
                $devedor['data_ultima_atualizacao'] = $dataAtual->format('Y-m-d');
                
                // Registra log da atualização
                error_log(sprintf(
                    'Juros por atraso aplicados para %s: %d dias, R$%d. Valor anterior: %.2f, Novo valor: %.2f',
                    $devedor['nome'],
                    $diasAtraso,
                    20 * $diasAtraso,
                    $valorAnterior,
                    $devedor['valor_atual']
                ));
            }
        }
    }
    
    return $devedores;
}

/**
 * Busca um devedor pelo ID
 * 
 * @param array $devedores Array de devedores
 * @param string $id ID do devedor a ser buscado
 * @return array|null Devedor encontrado ou null se não encontrado
 */
function buscarDevedorPorId($devedores, $id) {
    foreach ($devedores as $devedor) {
        if ($devedor['id'] === $id) {
            return $devedor;
        }
    }
    return null;
}

/**
 * Atualiza um devedor na lista
 * 
 * @param array &$devedores Array de devedores (passado por referência)
 * @param array $devedorAtualizado Dados atualizados do devedor
 * @return bool True se o devedor foi atualizado, False caso contrário
 */
function atualizarDevedor(&$devedores, $devedorAtualizado) {
    $id = $devedorAtualizado['id'];
    
    foreach ($devedores as $i => $devedor) {
        if ($devedor['id'] === $id) {
            $devedores[$i] = $devedorAtualizado;
            return true;
        }
    }
    
    return false;
}

/**
 * Move um devedor para a lista de quitados
 * 
 * @param array &$dados Array com os dados completos (devedores e quitados)
 * @param string $id ID do devedor a ser movido para quitados
 * @return bool True se o devedor foi movido, False caso contrário
 */
function moverParaQuitados(&$dados, $id) {
    // Procura o devedor na lista de devedores
    foreach ($dados['devedores'] as $i => $devedor) {
        if ($devedor['id'] === $id) {
            // Adiciona a data de quitação
            $devedor['data_quitacao'] = date('Y-m-d');
            
            // Adiciona à lista de quitados
            $dados['quitados'][] = $devedor;
            
            // Remove da lista de devedores
            array_splice($dados['devedores'], $i, 1);
            
            return true;
        }
    }
    
    return false;
}

/**
 * Calcula o total de juros mensais possíveis
 * 
 * Calcula quanto seria recebido em juros se todos os devedores 
 * pagassem apenas os juros no mês atual.
 * 
 * @param array $devedores Lista de devedores ativos
 * @return float Total de juros mensais possíveis
 */
function calcularJurosMensaisPossiveis($devedores) {
    $totalJurosMensais = 0;
    
    foreach ($devedores as $devedor) {
        // Juros mensais são 40% do valor inicial
        $jurosMensais = $devedor['valor_inicial'] * 0.4;
        $totalJurosMensais += $jurosMensais;
    }
    
    return $totalJurosMensais;
}
