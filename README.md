# Sistema de Controle de Empréstimos v2.2

Este sistema permite gerenciar empréstimos pessoais, calculando juros automaticamente e controlando pagamentos.

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

- Cadastro de pessoas com valor emprestado, telefone e endereço
- Cálculo automático de 40% de juros mensais
- Controle de pagamentos (total, apenas juros, nenhum)
- Juros de R$10 por dia de atraso quando não há pagamento
- Reaplicação de 40% se passar um mês sem pagamento
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
1. **Página Inicial**: Lista de devedores e resumo financeiro
2. **Cadastro**: Página separada para adicionar novos empréstimos com nome, telefone, endereço e valor
3. **Pagamentos**: Registre pagamentos totais, de juros ou nenhum pagamento
4. **Detalhes**: Visualize informações completas de cada empréstimo
5. **Edição**: Modifique dados, datas e status de pagamento
6. **Quitados**: Acesse o histórico de empréstimos já quitados

### Backup
- O arquivo do banco de dados fica em `database.sqlite` na pasta do sistema
- Faça backup regular deste arquivo para evitar perda de dados

## Suporte

Para suporte ou dúvidas, entre em contato com o desenvolvedor.

---

© Fernando Morais - Sistema de Controle de Empréstimos v2.1
