# Psicomanager BackEnd Test

![Psicomanager](/logo_psicomanager.png)

## Problemas encontrados na construção do ambiente

### Conflito de versão entre os pacotes travados no composer.lock e a versão do php do contêiner. 

- Optei por atualizar a versão de 8.1 para 8.2 já que seria feita uma varredura no sistema para refatoração do mesmo e é sempre melhor atualizar quando podemos para obter a maior performance e compatibilidade de pacotes mais recentes.


- Correção do path na configuração do nginx default.conf, na diretriz fastcgi_param SCRIPT_FILENAME. O path deve ser correspondente à como o volume está montado no contêiner php, por isso foi adicionado "/public" em root e alterado o fastcgi_param SCRIPT_FILENAME de 
```
- /var/www/html$fastcgi_script_name 
```
para 
```
$document_root$fastcgi_script_name
```

- Além disso foram implementadas todas as permissões necessárias para execução.

## Problemas informados

### 1. Endpoint GET /users não possui nenhum tipo de filtro. Permitir filtrar pelo nome e id.

- Foi implementado o filtro de busca por usuários atraves do nome e do id.
Sendo a busca pelo id retorna um único usuário como deve ser e na busca pelo nome é utilizado um like e retorna todos os usuários que encontrar com o nome informado.

### 2. Senha dos usuários estão sendo retornados no endpoint GET /users, causando uma falha de segurança.

- Foi adicionado o exemplo abaixo na model User.

```
protected $hidden = [
    'password',
    'remember_token',
];
```
### 3. Não há um endpoint para recuperar dados de um único usuário.

- Criado método/lógica no UserController referente à rota GET /users/{userId}.

### 4. Endpoint POST /users não está criptografando as senhas.

- No momento de salvar o usuário no banco de dados foi usado a classe Hash para criptografar a senha do usuário.

```
'password' => Hash::make($request->password),
```

### 5. Lentidão no endpoint GET /tasks?userId={id} quando um usuário possui mais de 1.000 tarefas.
- Foi adicionada indexação no banco de dados para o campo 'user_id', que é a melhor forma de melhorar a performance em SELECTs com where.
- Foi tambem adicionado paginação no momento de buscar as tasks. Limitando a um total de 100 registros por pagina.
Atraves de 2 parametros chamados page e perPage conseguimos passar qual pagina queremos buscar.
Sendo que estes valores ja estão padronizados: page=1 e perPage=100.
- Foi adicionado uma camada de cache usando a ferramenta do redis.
No momento que realiza uma primeira busca as tasks retornadas ficarão na memoria durante 10 minutos.
Dentro destes 10 minutos se forem realizar uma busca nos mesmos parametros ira pegar do cache e não do banco de dados.

### 6. O endpoint POST /users permite criar usuários com emails duplicados.
- Foi adicionado um trecho de validação no cadastro do usuario onde os usuários terão que ter emails unicos.
```
'email' => 'required|email|unique:users',
```

### 7. O endpoint DELETE /users/{id} falha ao excluir usuários que possuem tarefas associadas.
- Optei por movê-las para um usuário admin por não saber qual seria o impacto no trabalho do pessoal para saber quais tarefas foram excluídas sem que tenham que consultar algum log do sistema, nesse caso o usuário admin pode verificar as tarefas e delegá-las posteriormente.

### 8. Logs importantes das operações (ex.: criação de tarefas, exclusão de usuários) não estão sendo gravados corretamente.
- Foi criada uma classe que centraliza as chamadas de log, podendo ser chamada em qualquer parte do sistema bastando apenas injetá-la na classe que pretende utilizar.

### 9. Não há validação robusta nos endpoints, o que pode permitir dados inconsistentes no sistema.
- Foram adicionadas classes para padronizar o retorno e o envio de requisições como o UserResource e o CreateUserValidator.

## Melhorias implementadas
- A arquetura foi alteradas para uma arquitetura em camadas, onde cada camada tem sua responsabilidade única.
- Testes unitarios atraves do phpunit.
- Autenticação JWT na api.
- Sistema centralizado de logs.
- Cache com Redis para reduzir consultas ao banco.
- Boas práticas implementadas como clean code, alguns conceitos do SOLID como inversão de dependências e responsabilidade única e praticas de object calisthenics evitando ao máximo que o código tenha sua legibilidade afetada.