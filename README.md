# Psicomanager BackEnd Challenge

![Psicomanager](/logo_psicomanager.png)

## História

Um produto do HUB de soluções da Psico Gestor deseja disponibilizar um gerenciador de tarefas para seus usuários. Para evitar ajustes e adequações complexas nas APIs já existentes, foi decidido que esse recurso será suportado por uma API exclusiva executando como um micro-serviço.
A problemática e instruções de como resolve-los estão no arquivo *case_analitico_back.pdf*.

## Envio

-  Faça o clone desse repositorio
-  Instale as dependencias do projeto usando o *composer*
-  Suba o ambiente local usando o *docker-compose*
-  Execute o *artisan migrate* para implantar a estrutura de banco de dados
-  Execute o *artisan seed* para popular registros nas tabelas users e tasks
-  Resolva os problemas/tarefas descritos no arquivo *case_analitico_back.pdf*
-  Crie sua branch e mande um *pull request* pra nós

## Instalação

#### instale a aplicação

```
docker-compose up -d
```

#### entre na aplicação laravel

```
docker exec -it php_container bash
```

#### instale as dependencias necessarias

```
composer install
```

#### por segurança, atualize as dependencias

```
composer update
```

#### crie o arquivo de configuração

```
cp .env.example .env
```

#### crie as tabelas do banco de dados

```
php artisan migrate
```

#### gere os dados para o banco de dados

```
php artisan db:seed
```

#### saia do container

```
exit
```

#### reinicie a aplicação

```
docker-compose restart
```

#### adicione permissão

```
sudo chmod 777 -R storage bootstrap
```

### Exemplo de Uso

#### crie um usuário pela api

metodo: POST
url:
```
http://localhost:8080/api/register
```
body:
```
{
    "name": "John Doe",
    "email": "john@example.com",
    "password": "password",
    "password_confirmation": "password"
}
```

a conta do usuário sera criada
e um token de acesso sera gerado

#### utilize o token para as proximas etapas

#### listar usuários

metodo: GET
authorization: BearerToken (use o token gerado)
url:
```
http://localhost:8080/api/users
```

#### pegar um usuário

metodo: GET
authorization: BearerToken (use o token gerado)
url:
```
http://localhost:8080/api/users/ID
```

#### listar as tarefas

metodo: GET
authorization: BearerToken (use o token gerado)
url:
```
http://localhost:8080/api/tasks?user_id=9
```

#### pegar uma tarefa

metodo: GET
authorization: BearerToken (use o token gerado)
url:
```
http://localhost:8080/api/tasks/ID
```
