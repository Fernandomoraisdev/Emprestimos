<?php
/**
 * Sistema de Controle de Empréstimos - Configuração do Banco de Dados
 * 
 * Este arquivo contém funções para inicialização e conexão com o banco de dados SQLite,
 * substituindo o antigo sistema de armazenamento em JSON.
 * 
 * @author Manus AI
 * @version 2.0
 */

/**
 * Inicializa o banco de dados SQLite
 * 
 * Cria as tabelas necessárias se não existirem
 * 
 * @return PDO Objeto de conexão com o banco de dados
 */
function inicializarBancoDados() {
    $dbPath = __DIR__ . '/database.sqlite';
    $dbExists = file_exists($dbPath);
    
    // Cria conexão com o banco de dados
    $db = new PDO('sqlite:' . $dbPath);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Se o banco não existir, cria as tabelas
    if (!$dbExists) {
        // Tabela de devedores
        $db->exec('CREATE TABLE IF NOT EXISTS devedores (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            nome TEXT NOT NULL,
            telefone TEXT,
            endereco TEXT,
            valor_inicial REAL NOT NULL,
            valor_atual REAL NOT NULL,
            data_emprestimo TEXT NOT NULL,
            data_ultimo_pagamento TEXT NOT NULL,
            ultimo_pagamento TEXT NOT NULL
        )');
        
        // Tabela de empréstimos quitados
        $db->exec('CREATE TABLE IF NOT EXISTS quitados (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            nome TEXT NOT NULL,
            telefone TEXT,
            endereco TEXT,
            valor_inicial REAL NOT NULL,
            data_emprestimo TEXT NOT NULL,
            data_quitacao TEXT NOT NULL
        )');
    }
    
    return $db;
}

/**
 * Migra dados do JSON para o SQLite
 * 
 * Verifica se existem dados no formato antigo (JSON) e os migra para o novo formato (SQLite)
 * 
 * @param PDO $db Conexão com o banco de dados
 * @return bool True se a migração foi realizada, False caso contrário
 */
function migrarDadosJsonParaSQLite($db) {
    $arquivoJson = __DIR__ . '/dados.json';
    
    // Verifica se o arquivo JSON existe
    if (!file_exists($arquivoJson)) {
        return false;
    }
    
    // Carrega os dados do JSON
    $json = file_get_contents($arquivoJson);
    $dados = json_decode($json, true);
    
    if (!$dados) {
        return false;
    }
    
    // Inicia uma transação para garantir a integridade dos dados
    $db->beginTransaction();
    
    try {
        // Migra os devedores
        if (isset($dados['devedores']) && is_array($dados['devedores'])) {
            $stmt = $db->prepare('INSERT INTO devedores (nome, telefone, endereco, valor_inicial, valor_atual, data_emprestimo, data_ultimo_pagamento, ultimo_pagamento) 
                                 VALUES (:nome, :telefone, :endereco, :valor_inicial, :valor_atual, :data_emprestimo, :data_ultimo_pagamento, :ultimo_pagamento)');
            
            foreach ($dados['devedores'] as $devedor) {
                $stmt->bindValue(':nome', $devedor['nome']);
                $stmt->bindValue(':telefone', isset($devedor['telefone']) ? $devedor['telefone'] : '');
                $stmt->bindValue(':endereco', isset($devedor['endereco']) ? $devedor['endereco'] : '');
                $stmt->bindValue(':valor_inicial', $devedor['valor_inicial']);
                $stmt->bindValue(':valor_atual', $devedor['valor_atual']);
                $stmt->bindValue(':data_emprestimo', $devedor['data_emprestimo']);
                $stmt->bindValue(':data_ultimo_pagamento', $devedor['data_ultimo_pagamento']);
                $stmt->bindValue(':ultimo_pagamento', $devedor['ultimo_pagamento']);
                $stmt->execute();
            }
        }
        
        // Migra os quitados
        if (isset($dados['quitados']) && is_array($dados['quitados'])) {
            $stmt = $db->prepare('INSERT INTO quitados (nome, telefone, endereco, valor_inicial, data_emprestimo, data_quitacao) 
                                 VALUES (:nome, :telefone, :endereco, :valor_inicial, :data_emprestimo, :data_quitacao)');
            
            foreach ($dados['quitados'] as $quitado) {
                $stmt->bindValue(':nome', $quitado['nome']);
                $stmt->bindValue(':telefone', isset($quitado['telefone']) ? $quitado['telefone'] : '');
                $stmt->bindValue(':endereco', isset($quitado['endereco']) ? $quitado['endereco'] : '');
                $stmt->bindValue(':valor_inicial', $quitado['valor_inicial']);
                $stmt->bindValue(':data_emprestimo', $quitado['data_emprestimo']);
                $stmt->bindValue(':data_quitacao', $quitado['data_quitacao']);
                $stmt->execute();
            }
        }
        
        // Confirma a transação
        $db->commit();
        
        // Renomeia o arquivo JSON original para backup
        rename($arquivoJson, $arquivoJson . '.bak');
        
        return true;
    } catch (Exception $e) {
        // Em caso de erro, desfaz a transação
        $db->rollBack();
        error_log('Erro ao migrar dados: ' . $e->getMessage());
        return false;
    }
}

/**
 * Carrega todos os devedores do banco de dados
 * 
 * @param PDO $db Conexão com o banco de dados
 * @return array Lista de devedores
 */
function carregarDevedores($db) {
    $stmt = $db->query('SELECT * FROM devedores ORDER BY nome');
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Carrega todos os empréstimos quitados do banco de dados
 * 
 * @param PDO $db Conexão com o banco de dados
 * @return array Lista de empréstimos quitados
 */
function carregarQuitados($db) {
    $stmt = $db->query('SELECT * FROM quitados ORDER BY data_quitacao DESC');
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Busca um devedor pelo ID
 * 
 * @param PDO $db Conexão com o banco de dados
 * @param int $id ID do devedor
 * @return array|null Dados do devedor ou null se não encontrado
 */
function buscarDevedor($db, $id) {
    $stmt = $db->prepare('SELECT * FROM devedores WHERE id = :id');
    $stmt->bindValue(':id', $id);
    $stmt->execute();
    
    $devedor = $stmt->fetch(PDO::FETCH_ASSOC);
    return $devedor ?: null;
}

/**
 * Adiciona um novo devedor
 * 
 * @param PDO $db Conexão com o banco de dados
 * @param array $devedor Dados do devedor
 * @return int ID do devedor inserido
 */
function adicionarDevedor($db, $devedor) {
    $stmt = $db->prepare('INSERT INTO devedores (nome, telefone, endereco, valor_inicial, valor_atual, data_emprestimo, data_ultimo_pagamento, ultimo_pagamento) 
                         VALUES (:nome, :telefone, :endereco, :valor_inicial, :valor_atual, :data_emprestimo, :data_ultimo_pagamento, :ultimo_pagamento)');
    
    $stmt->bindValue(':nome', $devedor['nome']);
    $stmt->bindValue(':telefone', isset($devedor['telefone']) ? $devedor['telefone'] : '');
    $stmt->bindValue(':endereco', isset($devedor['endereco']) ? $devedor['endereco'] : '');
    $stmt->bindValue(':valor_inicial', $devedor['valor_inicial']);
    $stmt->bindValue(':valor_atual', $devedor['valor_atual']);
    $stmt->bindValue(':data_emprestimo', $devedor['data_emprestimo']);
    $stmt->bindValue(':data_ultimo_pagamento', $devedor['data_ultimo_pagamento']);
    $stmt->bindValue(':ultimo_pagamento', $devedor['ultimo_pagamento']);
    
    $stmt->execute();
    return $db->lastInsertId();
}

/**
 * Atualiza um devedor existente
 * 
 * @param PDO $db Conexão com o banco de dados
 * @param int $id ID do devedor
 * @param array $devedor Novos dados do devedor
 * @return bool True se atualizado com sucesso
 */
function atualizarDevedor($db, $id, $devedor) {
    $stmt = $db->prepare('UPDATE devedores SET 
                         nome = :nome, 
                         telefone = :telefone, 
                         endereco = :endereco, 
                         valor_inicial = :valor_inicial, 
                         valor_atual = :valor_atual, 
                         data_emprestimo = :data_emprestimo, 
                         data_ultimo_pagamento = :data_ultimo_pagamento, 
                         ultimo_pagamento = :ultimo_pagamento 
                         WHERE id = :id');
    
    $stmt->bindValue(':id', $id);
    $stmt->bindValue(':nome', $devedor['nome']);
    $stmt->bindValue(':telefone', isset($devedor['telefone']) ? $devedor['telefone'] : '');
    $stmt->bindValue(':endereco', isset($devedor['endereco']) ? $devedor['endereco'] : '');
    $stmt->bindValue(':valor_inicial', $devedor['valor_inicial']);
    $stmt->bindValue(':valor_atual', $devedor['valor_atual']);
    $stmt->bindValue(':data_emprestimo', $devedor['data_emprestimo']);
    $stmt->bindValue(':data_ultimo_pagamento', $devedor['data_ultimo_pagamento']);
    $stmt->bindValue(':ultimo_pagamento', $devedor['ultimo_pagamento']);
    
    return $stmt->execute();
}

/**
 * Move um devedor para a lista de quitados
 * 
 * @param PDO $db Conexão com o banco de dados
 * @param int $id ID do devedor
 * @return bool True se movido com sucesso
 */
function moverParaQuitados($db, $id) {
    // Busca o devedor
    $devedor = buscarDevedor($db, $id);
    if (!$devedor) {
        return false;
    }
    
    // Inicia uma transação
    $db->beginTransaction();
    
    try {
        // Insere na tabela de quitados
        $stmt = $db->prepare('INSERT INTO quitados (nome, telefone, endereco, valor_inicial, data_emprestimo, data_quitacao) 
                             VALUES (:nome, :telefone, :endereco, :valor_inicial, :data_emprestimo, :data_quitacao)');
        
        $stmt->bindValue(':nome', $devedor['nome']);
        $stmt->bindValue(':telefone', $devedor['telefone']);
        $stmt->bindValue(':endereco', $devedor['endereco']);
        $stmt->bindValue(':valor_inicial', $devedor['valor_inicial']);
        $stmt->bindValue(':data_emprestimo', $devedor['data_emprestimo']);
        $stmt->bindValue(':data_quitacao', date('Y-m-d'));
        $stmt->execute();
        
        // Remove da tabela de devedores
        $stmt = $db->prepare('DELETE FROM devedores WHERE id = :id');
        $stmt->bindValue(':id', $id);
        $stmt->execute();
        
        // Confirma a transação
        $db->commit();
        return true;
    } catch (Exception $e) {
        // Em caso de erro, desfaz a transação
        $db->rollBack();
        error_log('Erro ao mover para quitados: ' . $e->getMessage());
        return false;
    }
}

/**
 * Atualiza os juros de todos os devedores
 * 
 * @param PDO $db Conexão com o banco de dados
 * @return bool True se atualizado com sucesso
 */
function atualizarJuros($db) {
    $devedores = carregarDevedores($db);
    $dataAtual = new DateTime();
    $atualizados = false;
    
    foreach ($devedores as $devedor) {
        $dataUltimoPagamento = new DateTime($devedor['data_ultimo_pagamento']);
        $diferencaDias = $dataUltimoPagamento->diff($dataAtual)->days;
        $valorAtual = $devedor['valor_atual'];
        $atualizar = false;
        
        // Verifica se passou um mês desde o último pagamento
        if ($diferencaDias >= 30) {
            // Se o último pagamento foi "nenhum", aplica juros diários de R$10
            if ($devedor['ultimo_pagamento'] === 'nenhum') {
                $valorAtual += 10 * $diferencaDias;
            }
            
            // Aplica 40% de juros mensais
            $valorAtual *= 1.4;
            $atualizar = true;
        }
        
        // Se houve alteração, atualiza o devedor
        if ($atualizar) {
            $devedor['valor_atual'] = $valorAtual;
            atualizarDevedor($db, $devedor['id'], $devedor);
            $atualizados = true;
        }
    }
    
    return $atualizados;
}
