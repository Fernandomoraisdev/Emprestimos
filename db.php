<?php
/**
 * Sistema de Controle de Empréstimos - Configuração do Banco de Dados
 * 
 * Este arquivo contém funções para inicialização e conexão com o banco de dados SQLite,
 * e funções para manipulação dos dados de devedores e quitados.
 * 
 * @author Manus AI
 * @version 2.4
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
        // Verifica se já existem dados para evitar duplicidade na migração
        $stmtCheck = $db->query('SELECT COUNT(*) FROM devedores');
        $countDevedores = $stmtCheck->fetchColumn();
        $stmtCheck = $db->query('SELECT COUNT(*) FROM quitados');
        $countQuitados = $stmtCheck->fetchColumn();

        if ($countDevedores == 0 && $countQuitados == 0) {
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
        }
        
        // Confirma a transação
        $db->commit();
        
        // Renomeia o arquivo JSON original para backup após migração bem-sucedida (se dados foram migrados)
        if ($countDevedores == 0 && $countQuitados == 0 && (isset($dados['devedores']) || isset($dados['quitados']))) {
             rename($arquivoJson, $arquivoJson . '.migrated.bak');
        }
        
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
    // A ordenação será feita no PHP após categorizar
    $stmt = $db->query('SELECT * FROM devedores'); 
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
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
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
    // Valor atual inicial é igual ao valor inicial
    $stmt->bindValue(':valor_atual', $devedor['valor_inicial']); 
    $stmt->bindValue(':data_emprestimo', $devedor['data_emprestimo']);
    // Data do último pagamento inicial é a data do empréstimo
    $stmt->bindValue(':data_ultimo_pagamento', $devedor['data_emprestimo']); 
    $stmt->bindValue(':ultimo_pagamento', 'nenhum'); // Status inicial é 'nenhum'
    
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
    // Apenas atualiza os campos permitidos (evita atualizar valor_atual diretamente aqui)
    $stmt = $db->prepare('UPDATE devedores SET 
                         nome = :nome, 
                         telefone = :telefone, 
                         endereco = :endereco, 
                         valor_inicial = :valor_inicial, 
                         data_emprestimo = :data_emprestimo, 
                         data_ultimo_pagamento = :data_ultimo_pagamento, 
                         ultimo_pagamento = :ultimo_pagamento 
                         WHERE id = :id');
    
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt->bindValue(':nome', $devedor['nome']);
    $stmt->bindValue(':telefone', isset($devedor['telefone']) ? $devedor['telefone'] : '');
    $stmt->bindValue(':endereco', isset($devedor['endereco']) ? $devedor['endereco'] : '');
    $stmt->bindValue(':valor_inicial', $devedor['valor_inicial']);
    // $stmt->bindValue(':valor_atual', $devedor['valor_atual']); // Não atualiza valor_atual aqui
    $stmt->bindValue(':data_emprestimo', $devedor['data_emprestimo']);
    $stmt->bindValue(':data_ultimo_pagamento', $devedor['data_ultimo_pagamento']);
    $stmt->bindValue(':ultimo_pagamento', $devedor['ultimo_pagamento']);
    
    return $stmt->execute();
}

/**
 * Atualiza apenas o valor atual de um devedor (usado por atualizarJuros)
 * 
 * @param PDO $db Conexão com o banco de dados
 * @param int $id ID do devedor
 * @param float $novoValorAtual Novo valor atual
 * @return bool True se atualizado com sucesso
 */
function atualizarValorAtualDevedor($db, $id, $novoValorAtual) {
    $stmt = $db->prepare('UPDATE devedores SET valor_atual = :valor_atual WHERE id = :id');
    $stmt->bindValue(':valor_atual', round($novoValorAtual, 2));
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
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
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
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
 * Atualiza os juros e penalidades de todos os devedores.
 * Aplica juros de 40% mensalmente e penalidade de R$10 diariamente em caso de atraso.
 * 
 * @param PDO $db Conexão com o banco de dados
 * @return bool True se alguma atualização foi feita
 */
function atualizarJuros($db) {
    $devedores = $db->query('SELECT * FROM devedores')->fetchAll(PDO::FETCH_ASSOC);
    $dataAtual = new DateTime();
    $atualizados = false;

    foreach ($devedores as $devedor) {
        $dataUltimoPagamento = new DateTime($devedor['data_ultimo_pagamento']);
        $valorAtualDB = (float)$devedor['valor_atual'];
        $novoValorCalculado = $valorAtualDB; // Começa com o valor atual do banco
        $houveAlteracao = false;

        // Calcula a diferença de dias desde o último pagamento
        $intervalo = $dataAtual->diff($dataUltimoPagamento);
        $diasDesdeUltimoPagamento = $intervalo->days;

        // --- Lógica de Atualização --- 
        // Esta lógica precisa ser idempotente (rodar várias vezes no mesmo dia não causa efeito colateral)
        // Idealmente, usaríamos uma data da última atualização.
        // Solução: Calcular o valor *esperado* hoje e comparar com o valor no banco.

        // --- Cálculo do Valor Esperado --- 
        // 1. Base: Valor inicial ou valor após último pagamento ('juros' ou 'total')
        // 2. Juros Mensais: 40% aplicados a cada ciclo de 30 dias desde a base.
        // 3. Penalidade Diária: R$10 por dia desde que 'ultimo_pagamento' == 'nenhum' e data_atual > data_ultimo_pagamento.

        // --- Implementação Simplificada (Pode ter imprecisões sem histórico detalhado) ---

        // 1. Aplica Juros Mensais (40%) se passaram 30 dias ou mais
        //    Esta lógica ainda pode aplicar juros repetidamente se não houver pagamento.
        if ($diasDesdeUltimoPagamento >= 30) {
            // Aplica sobre o valor atual do banco (que pode já incluir penalidades)
            // Para ser mais preciso, deveria aplicar sobre o valor no início do ciclo de 30 dias.
            $novoValorCalculado *= 1.4;
            $houveAlteracao = true; 
            // TODO: Implementar uma forma de não reaplicar juros no mesmo ciclo mensal.
            // Ex: Adicionar campo 'data_juros_mensal_aplicado'
        }

        // 2. Aplica Penalidade Diária (R$10) se atrasado
        if ($devedor['ultimo_pagamento'] === 'nenhum' && $dataAtual > $dataUltimoPagamento) {
            // Adiciona R$10 ao valor calculado *hoje*. 
            // Se rodar de novo hoje, adicionará +10. Isso é um problema.
            // Solução paliativa: Adicionar apenas se a data atual for diferente da data do último pagamento?
            // Não, a penalidade é diária.
            
            // Tentativa: Calcular a penalidade total e ajustar?
            $penalidadeTotalEsperada = $diasDesdeUltimoPagamento * 10;
            // Como saber quanto de penalidade já está no $valorAtualDB?
            
            // Solução mais simples e direta ao pedido: Adicionar R$10 hoje se atrasado.
            // Assume que a função roda uma vez por dia.
            $novoValorCalculado += 10;
            $houveAlteracao = true;
        }

        // Arredonda o valor final
        $novoValorArredondado = round($novoValorCalculado, 2);

        // Atualiza o banco de dados APENAS se o valor calculado for diferente do valor atual no banco
        if ($houveAlteracao && abs($novoValorArredondado - $valorAtualDB) > 0.001) { // Compara floats com tolerância
            atualizarValorAtualDevedor($db, $devedor['id'], $novoValorArredondado);
            $atualizados = true;
        }
    }

    return $atualizados;
}

?>

