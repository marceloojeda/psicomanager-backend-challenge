# Psicomanager BackEnd Challenge

![Psicomanager](/logo_psicomanager.png)

## Soluções e Melhorias

Abaixo segue as soluções dos produtos propostos assim como melhorias a mais para tornar a aplicação mais robusta.

### Os seguintes problemas propostos foram corrigidos

1. Endpoint GET /users não possui nenhum tipo de filtro. Permitir filtrar pelo nome e id.

    Foi implementado o filtro de busca por usuários atraves do nome e do id.
    A busca do campo id é exata somente compara para verificar se no banco existe algum usuario com o id fornecido.
    Para o campo de nome ele busca na coluna name do banco de dados algum nome que seja parecido e ou proximo do nome fornecido na busca.

    Exemplo de busca por id:

    ```
    users?id=9
    ```

    Exemplo de busca por nome:

    ```
    users?name=raimundo
    ```

2. Senha dos usuários estão sendo retornados no endpoint GET /users, causando uma falha de
segurança.

    Foi implementado para que na busca de qualquer usuário ele retorne somente os campos de:
        id, name, email e created_at
    A solução implementada foram 2:

    - Na model User setar para que os campos de senhas sejam camuflados na busca

    ```
    protected $hidden = [
        'password',
        'remember_token',
    ];
    ```

    - Foi implementado uma classe para padronizar as retorno de usuários (e tambem para tarefas).
      A classe se chama UserResource, especificando os campos do usuário que iremos retornar:

    ```
        public function toArray($request)
            {
                return [
                    'id' => $this->id,
                    'name' => $this->name,
                    'email' => $this->email,
                    'createdAt' => $this->createdAt,
                ];
            }
    ```

3. Não há um endpoint para recuperar dados de um único usuário.

    - Na classe UserRepository foi implementado uma busca no banco de dados para buscar o usuário pelo id.
      Veja que no retorno ele nao retorna o objeto direto da classe User. Ele usa uma classe diferente chamada UserEntity.
      Foi feito tambem desta forma para as tarefas. As tarefas usam a classe TaskEntity.

    ```
    public function getUserRepository(int $userId): ?UserEntity {
        $user = User::findOrFail($userId);

        return new UserEntity(
            id: $user->id,
            name: $user->name,
            email: $user->email,
            createdAt: $user->createdAt
        );
    }
    ```

4. Endpoint POST /users não está criptografando as senhas.

    - No momento de salvar o usuário no banco de dados. Foi usado a classe Hash para criptografar a senha do usuário
      e assim salvar de forma anonima.

    ```
    'password' => Hash::make($request->password),
    ```

5. Lentidão no endpoint GET /tasks?userId={id} quando um usuário possui mais de
1.000 tarefas.

    - Foi identificado que a coluna user_id da tabela tasks nao possuia indice de relacionamento com a tabela users.
      Este indice foi criado e tambem adicionado relacionamento de chave estrangeira para ligar as duas tabelas tasks e users
      através da coluna user_id da tabela tasks.
    - Foi tambem adicionado paginação no momento de buscar as tasks. Limitando a um total de 100 registros por pagina.
      Atraves de 2 parametros chamados page e perPage conseguimos passar qual pagina queremos buscar.
      Ex: /tasks?page=2&&perPage=200. Sendo que estes valores ja estão padronizados: page=1 e perPage=100
    - Foi adicionado uma camada de cache usando a ferramenta do redis.
      No momento que realiza uma primeira busca as tasks retornadas ficarão na memoria durante 10 minutos.
      Dentro destes 10 minutos se forem realizar uma busca nos mesmos parametros ira pegar do cache e não do banco de dados.

6. O endpoint POST /users permite criar usuários com emails duplicados.

    - Foi adicionado um trecho de validação no cadastro do usuario onde os usuários terão que ter emails unicos
    ```
    'email' => 'required|string|email|max:255|unique:users',
    ```

7. O endpoint DELETE /users/{id} falha ao excluir usuários que possuem tarefas
associadas.

    - Foi adicionado um relacionamento na coluna user_id da tabela tasks. Onde o user_id possui chave estrangeira
      de relacionamento com a tabela users na coluna id. Alem disso foi adicionado o comportamento de excluir em cascata:
      basicamente como existe um relacionamento entre as 2 tabelas. Quando um usuário for excluido as tasks pertencentes
      a ele também serão excluidas.

8. Logs importantes das operações (ex.: criação de tarefas, exclusão de usuários) não estão
sendo gravados corretamente.

    - Foi adicionado uma ferramenta de observabilidade chamada: Elasticsearch.
      O Elasticsearch tem o papel se salvar todos os logs de operações como (erros da aplicação e cadastro de usuários)
      na memória. Assim conseguimos monitorar tudo que acontece na aplicação.

      A rota para exibir os logs do ElasticSearch: http://localhost:8080/api/logs

9. Não há validação robusta nos endpoints, o que pode permitir dados inconsistentes no
sistema.

    - Foi adicionado classes para padronizar o retorno e o envio de requisições como o UserResource e o UserRequest.

## Melhorias implementadas

#### A arquetura foi modificada de MVC para DDD. Uma tipo mais adequado e organizado para microserviços.

#### Melhores práticas de clean code seguindo as regras do PSR-12 como identação e codeblock para legibilidade.

#### Testes unitarios atraves do phpunit (UserTest e TaskTest)

#### Autenticação JWT na api

#### Sistema robusto de logs com o ElasticSearch

#### Cache com Redis para aliviar as consultar de tarefas no banco de dados
