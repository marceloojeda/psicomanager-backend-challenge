# Psicomanager BackEnd Challenge

![Psicomanager](/logo_psicomanager.png)

## Case prático: Sistema de Gerenciamento de Tarefas

Desenvolver uma API RestFull simples para o gerenciamento de tarefas. A aplicação deve permitir:
1) Criar, listar, atualizar e excluir tarefas.
2) Filtrar tarefas por status (pendente, em progresso, concluída).
3) Registrar logs de eventos (como criação, atualização e exclusão de tarefas).

## Requisitos técnicos

- Utilize a linguagem PHP 8.1+
- Utilize o framework Laravel Lumen 10+ para desenvolver a API
- Use um banco MySql
- Use containers docker para subir a aplicação e banco de dados

## Stack

- PHP 8.1+
- Laravel/Lumen
- Eloquent
- SOLID
- MVC

## História

Um produto do HUB de soluções da Psico Gestor deseja disponibilizar um gerenciador de tarefas para seus usuários. Para evitar ajustes e adequações complexas nas APIs já existentes, foi decidido que esse recurso será suportado por uma API exclusiva executando como um micro-serviço.

### Necessidade

Construir uma API que deve ser funcionar em cima de containers com as seguintes características:
- Alimentação de 100 registros de usuários com o artisan seed 
- Endpoints CRUD padrão para suportar o gerenciamento das tarefas
- Uma tarefa pertence à apenas um usuário, e um usuário pode ter várias tarefas
- Logs de inserção, alteração e exclusão de tarefas. Criar endpoint para fornecer esses logs

### Solução

Crie um docker-compose capaz de subir o(s) container(s) necessário(s) para publicar essa API RestFull.

### Critérios de Aceite

- A aplicação deve subir apenas usando os comandos docker-compose
- As tabelas e registros devem ser populadas através do artisan
- Os endpoints CRUD funcionando

