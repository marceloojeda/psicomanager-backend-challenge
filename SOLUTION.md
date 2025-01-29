Descrição do Problema
A API apresenta os seguintes sintomas:

1. Endpoint GET /users não possui nenhum tipo de filtro. Permitir filtrar pelo nome e id.
- Verificar se id e name foram passados por parametro e então realizar o filtro.

2. Senha dos usuários estão sendo retornados no endpoint GET /users, causando uma falha de segurança.
- Selecionar apenas os campos necessários ao realizar consulta. Nesse caso optei por: id, name e email.

3. Não há um endpoint para recuperar dados de um único usuário.
- Rota já existe no arquivo web.php. Criar metodo 'get' e tratar as requisições.

4. Endpoint POST /users não está criptografando as senhas.
- Adicionado criptografia para as senhas app('hash')->make('password').

5. Lentidão no endpoint GET /tasks?userId={id} quando um usuário possui mais de 1.000 tarefas.
- Adicionado cache para a consulta e select para os campos relevantes.

6. O endpoint POST /users permite criar usuários com emails duplicados.
- Adicionado validação no metodo store para emails duplicados.

7. O endpoint DELETE /users/{id} falha ao excluir usuários que possuem tarefas associadas.
- Criado migration com chave estrangeira para ligar tabelas users e task. Adicionado onDelete('cascade'), para deletar todos os registros associados ao usuário.

8. Logs importantes das operações (ex.: criação de tarefas, exclusão de usuários) não estão sendo gravados corretamente.
- Criar os observers para task e user e ativar os logs em AppServiceProvider, adicionando no metodo boot os observers criados.
- Criar arquivo de configuração para os logs em /config/logging.php
- Registrar o AppServiceProvider em app.php

9. Não há validação robusta nos endpoints, o que pode permitir dados inconsistentes no sistema.
- Adicionado validação no metodo store em userController.