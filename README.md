# Sistema de Controle de Empréstimos v2.5

Este sistema permite gerenciar empréstimos pessoais, calculando juros automaticamente, controlando pagamentos e facilitando a busca por devedores.

## Novidades da Versão 2.5

- **Pesquisa Rápida**: Adicionada barra de pesquisa na página inicial para encontrar devedores por nome (busca parcial) ou ID (busca exata).
- **Resultados Filtrados**: As listas de devedores (Atrasados, Pagamentos do Dia, Geral) são atualizadas para mostrar apenas os resultados da pesquisa.
- **Correções Gerais**: Melhorias na estabilidade e correção de pequenos erros.

## Novidades da Versão 2.4

- **Lista de Atrasados**: Nova seção na página inicial destacando devedores com pagamentos em atraso.
- **Destaque Visual**: Devedores atrasados são exibidos em vermelho para fácil identificação.
- **Penalidade Diária Automática**: Aplicação automática de R$10 ao valor da dívida para cada dia de atraso (quando o último pagamento foi 'nenhum').
- **Contador de Dias Atrasados**: Exibição do número de dias em atraso para cada devedor na lista de atrasados.
- **IDs Visíveis**: Exibição do ID de cada devedor nas listas para facilitar a referência.

## Novidades da Versão 2.3

- **Cálculo Dinâmico de Juros**: Total de juros a receber hoje calculado automaticamente
- **Indicador de Pagamentos**: Exibição da quantidade de pagamentos do dia
- **Resumo Financeiro Aprimorado**: Valores precisos para melhor controle

## Novidades da Versão 2.2

- **Listagem Organizada**: Devedores ordenados alfabeticamente para fácil localização
- **Pagamentos Prioritários**: Seção separada para devedores com pagamento no dia
- **Visualização Otimizada**: Identificação rápida de quem precisa pagar hoje

## Novidades da Versão 2.1

- **Interface Reorganizada**: Cadastro em página separada para melhor organização
- **Navegação Aprimorada**: Acesso rápido a todas as funcionalidades em qualquer página
- **Melhor Escalabilidade**: Página principal mais limpa, focada apenas na listagem

## Novidades da Versão 2.0

- **Banco de Dados SQLite**: Armazenamento robusto e portátil para seus dados
- **Campos de Contato**: Adição de telefone e endereço para cada devedor
- **Instalação como Programa**: Instruções para executar como aplicativo local
- **Interface Aprimorada**: Visual dark mode com melhor organização

## Funcionalidades

- **Pesquisa**: Encontre devedores rapidamente por nome ou ID na página inicial.
- Cadastro de pessoas com valor emprestado, telefone e endereço
- Cálculo automático de 40% de juros mensais
- Controle de pagamentos (total, apenas juros, nenhum)
- **Lista de Atrasados**: Seção separada destacada em vermelho para devedores com pagamentos atrasados.
- **Penalidade Diária**: Juros de R$10 por dia de atraso quando não há pagamento (aplicado automaticamente).
- Reaplicação de 40% de juros mensais (aplicado após 30 dias do último pagamento).
- Resumo financeiro com valores totais
- Lista separada de empréstimos quitados
- Edição de datas e status de pagamento

## Instalação

Para instruções detalhadas de instalação no Windows ou Ubuntu, consulte o arquivo `guia_instalacao.md` incluído no pacote.

### Resumo da Instalação

#### Windows
1. Instale XAMPP ou use PHP com servidor embutido
2. Extraia os arquivos para a pasta apropriada
3. Acesse via navegador em localhost

#### Ubuntu
1. Instale Apache e PHP ou use o servidor embutido do PHP
2. Configure os arquivos no diretório web
3. Acesse via navegador em localhost

## Uso do Sistema

### Primeira Execução
- Na primeira execução, o sistema criará automaticamente o banco de dados SQLite
- Se você tinha dados no formato antigo (JSON), eles serão migrados automaticamente

### Funcionalidades Principais
1. **Página Inicial**: Resumo financeiro, barra de pesquisa e listas de devedores (Atrasados, Pagamentos do Dia, Lista Geral).
2. **Pesquisa**: Digite o nome (completo ou parcial) ou o ID exato do devedor na barra de pesquisa e clique em "Pesquisar". Para limpar a pesquisa, clique em "Limpar Pesquisa".
3. **Cadastro**: Página separada para adicionar novos empréstimos com nome, telefone, endereço e valor
4. **Pagamentos**: Registre pagamentos totais, de juros ou nenhum pagamento
5. **Detalhes**: Visualize informações completas de cada empréstimo
6. **Edição**: Modifique dados, datas e status de pagamento
7. **Quitados**: Acesse o histórico de empréstimos já quitados

### Backup
- O arquivo do banco de dados fica em `database.sqlite` na pasta do sistema
- Faça backup regular deste arquivo para evitar perda de dados

## Suporte

Para suporte ou dúvidas, entre em contato com o desenvolvedor.

---

© Fernando Morais - Sistema de Controle de Empréstimos v2.5

