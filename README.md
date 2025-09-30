# Psicomanager BackEnd Challenge

![Psicomanager](/logo_psicomanager.png)

## História

Um produto do HUB de soluções da Psico Gestor deseja disponibilizar um gerenciador de tarefas para seus usuários. Para evitar ajustes e adequações complexas nas APIs já existentes, foi decidido que esse recurso será suportado por uma API exclusiva executando como um micro-serviço.
Os problemas e soluções desejáveis estão no arquivo *case_analitico_back.pdf*.

## 📚 Documentação da API

### 🌐 Links Rápidos
- **Documentação Interativa (Swagger)**: [http://localhost:8888/docs](http://localhost:8888/docs)
- **Especificação OpenAPI (JSON)**: [http://localhost:8888/api-docs-simple.json](http://localhost:8888/api-docs-simple.json)
- **Guia de Integração Frontend**: [docs/FRONTEND_INTEGRATION_GUIDE.md](docs/FRONTEND_INTEGRATION_GUIDE.md)
- **Collection Postman JWT**: [postman/PsicoManager-API-JWT.postman_collection.json](postman/PsicoManager-API-JWT.postman_collection.json)
- **Environment Postman**: [postman/PsicoManager-Environment.postman_environment.json](postman/PsicoManager-Environment.postman_environment.json)

### 🚀 Para Desenvolvedores Frontend
1. **Acesse a documentação interativa** em http://localhost:8888/docs
2. **Baixe e importe a collection do Postman** para testar os endpoints
3. **Consulte o guia completo** com exemplos React/JavaScript em [docs/FRONTEND_INTEGRATION_GUIDE.md](docs/FRONTEND_INTEGRATION_GUIDE.md)

### 📋 Endpoints Disponíveis

**Endpoints Públicos (sem autenticação):**
- `POST /users` - Registrar novo usuário
- `POST /auth/login` - Login com JWT authentication
- `POST /auth/refresh` - Renovar tokens JWT

**Endpoints Protegidos (requerem JWT):**
- `GET /auth/me` - Dados do usuário autenticado
- `POST /auth/logout` - Logout do sistema
- `GET /users` - Lista usuários com filtros e paginação
- `GET /users/{id}` - Busca usuário por ID
- `DELETE /users/{id}` - Exclui usuário (soft delete)
- `PATCH /users/{id}/restore` - Restaura usuário excluído
- `GET /tasks` - Lista tarefas com filtros
- `GET /tasks/{id}` - Busca tarefa por ID

### 🔐 Autenticação JWT
Esta API utiliza autenticação JWT (JSON Web Tokens) para proteger rotas sensíveis. 

**Fluxo recomendado:**
1. **Registre-se**: `POST /users` (público) - Crie sua conta
2. **Faça login**: `POST /auth/login` (público) - Obtenha seus tokens  
3. **Use o token**: Inclua o header `Authorization: Bearer {seu_token_jwt}` em rotas protegidas
4. **Renove quando necessário**: Use `POST /auth/refresh` antes do token expirar

**Exemplo completo:**
```bash
# 1. Registrar usuário (público)
curl -X POST http://localhost:8888/users \
  -H "Content-Type: application/json" \
  -d '{"name": "João Silva", "email": "joao@example.com", "password": "minhasenha123"}'

# 2. Login (público)  
curl -X POST http://localhost:8888/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email": "joao@example.com", "password": "minhasenha123"}'

# 3. Usar token para acessar rotas protegidas
curl -H "Authorization: Bearer eyJ0eXAiOiJKV1Q..." \
  http://localhost:8888/tasks
```

### 📖 Documentação Interativa
A **documentação interativa Swagger UI** está disponível em [http://localhost:8888/docs](http://localhost:8888/docs) e inclui:

- ✅ **Teste direto de endpoints** - Execute chamadas da API diretamente na interface
- ✅ **Autenticação JWT integrada** - Faça login e teste rotas protegidas
- ✅ **Schemas detalhados** - Veja estruturas de request/response
- ✅ **Exemplos práticos** - Dados de exemplo para cada endpoint
- ✅ **Códigos de erro documentados** - Entenda todos os códigos de status HTTP

## 🛠️ Configuração do Ambiente

### Pré-requisitos
- Docker e Docker Compose
- Acesso às portas 8888 (nginx), 3306 (mysql)

### Instalação
```bash
# 1. Clone o repositório
git clone <repository-url>
cd psicomanager-backend-challenge

# 2. Suba o ambiente
docker-compose up -d

# 3. Execute as migrações
docker-compose exec php php artisan migrate

# 4. Execute os seeders
docker-compose exec php php artisan db:seed
```

### Verificação
```bash
# Testar se a API está funcionando
curl http://localhost:8888/

# Listar usuários
curl -H "Accept: application/json" http://localhost:8888/users
```

## Envio

-  Faça o clone desse repositorio
-  Suba o ambiente local usando o *docker-compose*
-  Dentro do container php_container, execute os *migrate e db:seed* do artisan para implantar a estrutura de banco de dados e massa de dados.
-  Resolva os problemas/tarefas descritos no arquivo *case_analitico_back.pdf*
-  Crie sua branch e mande um *pull request* pra nós

