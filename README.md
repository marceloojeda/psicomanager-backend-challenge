# ☄ Proposta do projeto
> Você foi contratado para diagnosticar e solucionar problemas em uma API RESTful já existente. A
API é responsável por gerenciar usuários e suas respectivas tarefas. Alguns usuários têm relatado
falhas e lentidão ao utilizar os serviços.
### Objetivo
> Identificar e solucionar os problemas relatados, garantindo o correto funcionamento e bom
desempenho da API.

# ⚡️ Como Instalar


- Acesse o diretório do projeto pelo prompt de comandos de seu sistema e configure o seu arquivo .env com base no arquivo .env.example:
```
cp .env.example .env
```
- Seu arquivo .env deve estar conforme informado abaixo:
```
APP_NAME=Lumen
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost
APP_TIMEZONE=UTC

LOG_CHANNEL=stack
LOG_SLACK_WEBHOOK_URL=

DB_CONNECTION=mysql
DB_HOST=mysql_container
DB_PORT=3306
DB_DATABASE=psico-case
DB_USERNAME=root
DB_PASSWORD=root

QUEUE_CONNECTION=sync

CACHE_DRIVER=redis
REDIS_CLIENT=predis
REDIS_HOST=redis_container
REDIS_PASSWORD=null
REDIS_PORT=6379
```
- O projeto roda utilizando o docker portanto certifique-se de tê-lo instalado:

- Com o docker instalado rode o comando para criar o build
```
docker compose build
```
- Após a criação do build execute
```
docker compose up -d
```
- Com o container de pé rode o comando para gerar a pasta vendor e criar o autoload
```
docker exec -it php_container composer install
```
- Dentro do container precisamos realizar algumas configurações, a primeira é gerar o jwt secret
```
docker exec -it php_container php artisan jwt:secret
```
- Ainda dentro do conteiner crie as tabelas usando as migrations:
```
docker exec -it php_container php artisan migrate
```
- Após isso iremos popular as tabelas com as seeders:
```
docker exec -it php_container php artisan db:seed
```
- Para executar os testes automatizados basta executar o comando:
```
docker exec -it php_container php vendor/bin/phpunit --colors
```
✅ Pronto! Agora você está pronto para usar o projeto na sua máquina com essas etapas simples.