# Guia de Integração Frontend - PsicoManager API

## 📋 Visão Geral
Este guia fornece todas as informações necessárias para o time frontend integrar com a API PsicoManager de forma eficiente e segura.

## 🔗 Links Importantes
- **Documentação Interativa Swagger**: http://localhost:8888/api-documentation.html
- **Especificação OpenAPI (JSON)**: http://localhost:8888/api-docs-simple.json
- **Collection Postman**: `postman/PsicoManager-API.postman_collection.json`
- **Environment Postman**: `postman/PsicoManager-Environment.postman_environment.json`

## 🚀 Configuração Base

### URL Base
```
Desenvolvimento: http://localhost:8888
```

### Headers Obrigatórios
```javascript
{
  'Accept': 'application/json',
  'Content-Type': 'application/json' // Para requests POST/PUT/PATCH
}
```

## 📚 Endpoints Disponíveis

### 1. Usuários (Users)

#### 1.1 Listar Usuários
```javascript
// GET /users
fetch('http://localhost:8888/users')
  .then(response => response.json())
  .then(data => console.log(data));

// Com filtros e paginação
fetch('http://localhost:8888/users?name=João&page=1&per_page=10')
  .then(response => response.json())
  .then(data => console.log(data));
```

**Parâmetros de Query:**
- `id` (integer): Filtrar por ID específico
- `name` (string): Filtrar por nome (busca parcial)
- `page` (integer): Número da página (default: 1)
- `per_page` (integer): Itens por página (default: 15)

**Resposta de Sucesso (200):**
```json
{
  "data": [
    {
      "id": 77,
      "name": "João Silva",
      "email": "joao@example.com",
      "created_at": "2025-09-25T14:30:00.000000Z"
    }
  ],
  "current_page": 1,
  "per_page": 15,
  "total": 1,
  "last_page": 1
}
```

#### 1.2 Buscar Usuário por ID
```javascript
// GET /users/{id}
fetch('http://localhost:8888/users/77')
  .then(response => response.json())
  .then(data => console.log(data));
```

**Resposta de Sucesso (200):**
```json
{
  "data": {
    "id": 77,
    "name": "João Silva", 
    "email": "joao@example.com",
    "created_at": "2025-09-25T14:30:00.000000Z"
  }
}
```

#### 1.3 Criar Usuário
```javascript
// POST /users
const userData = {
  name: 'João Silva',
  email: 'joao@example.com',
  password: '123456'
};

fetch('http://localhost:8888/users', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json'
  },
  body: JSON.stringify(userData)
})
.then(response => response.json())
.then(data => console.log(data));
```

**Resposta de Sucesso (201):**
```json
{
  "data": {
    "id": 78,
    "name": "João Silva",
    "email": "joao@example.com", 
    "created_at": "2025-09-26T14:30:00.000000Z"
  },
  "message": "Usuário criado com sucesso"
}
```

#### 1.4 Excluir Usuário (Soft Delete)
```javascript
// DELETE /users/{id}
fetch('http://localhost:8888/users/77', {
  method: 'DELETE',
  headers: {
    'Accept': 'application/json'
  }
})
.then(response => response.json())
.then(data => console.log(data));
```

#### 1.5 Restaurar Usuário
```javascript
// PATCH /users/{id}/restore
fetch('http://localhost:8888/users/77/restore', {
  method: 'PATCH',
  headers: {
    'Accept': 'application/json'
  }
})
.then(response => response.json())
.then(data => console.log(data));
```

### 2. Tarefas (Tasks)

#### 2.1 Listar Tarefas
```javascript
// GET /tasks
fetch('http://localhost:8888/tasks')
  .then(response => response.json())
  .then(data => console.log(data));

// Filtrar por usuário
fetch('http://localhost:8888/tasks?user_id=77&per_page=50')
  .then(response => response.json())
  .then(data => console.log(data));
```

**Parâmetros de Query:**
- `user_id` (integer): Filtrar por ID do usuário
- `page` (integer): Número da página
- `per_page` (integer): Itens por página (máximo 50)

#### 2.2 Buscar Tarefa por ID
```javascript
// GET /tasks/{id}
fetch('http://localhost:8888/tasks/1')
  .then(response => response.json())
  .then(data => console.log(data));
```

## ❌ Tratamento de Erros

### Códigos de Status HTTP
- **200 OK**: Sucesso
- **201 Created**: Recurso criado com sucesso  
- **400 Bad Request**: Dados de entrada inválidos
- **404 Not Found**: Recurso não encontrado
- **409 Conflict**: Conflito (ex: email duplicado na restauração)
- **422 Unprocessable Entity**: Erro de validação
- **500 Internal Server Error**: Erro interno do servidor

### Exemplos de Erros

**Erro 400 (Bad Request):**
```json
{
  "error": "ID inválido. O ID deve ser um número positivo."
}
```

**Erro 404 (Not Found):**
```json
{
  "error": "Usuário não encontrado"
}
```

**Erro 422 (Validation Error):**
```json
{
  "errors": {
    "name": ["O campo nome é obrigatório."],
    "email": ["O campo email deve ser um endereço de e-mail válido."],
    "password": ["O campo password deve ter pelo menos 6 caracteres."]
  }
}
```

### Tratamento com Async/Await
```javascript
async function createUser(userData) {
  try {
    const response = await fetch('http://localhost:8888/users', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      },
      body: JSON.stringify(userData)
    });

    if (!response.ok) {
      const errorData = await response.json();
      
      if (response.status === 422) {
        // Erro de validação
        console.error('Dados inválidos:', errorData.errors);
        return { success: false, errors: errorData.errors };
      }
      
      if (response.status === 400) {
        // Email já existe ou outros erros de negócio
        console.error('Erro:', errorData.error);
        return { success: false, message: errorData.error };
      }
      
      throw new Error(`Erro ${response.status}: ${response.statusText}`);
    }

    const data = await response.json();
    return { success: true, data: data.data };

  } catch (error) {
    console.error('Erro na requisição:', error);
    return { success: false, error: error.message };
  }
}
```

## 🔧 Estruturas de Dados TypeScript

```typescript
// Interfaces para tipagem
interface User {
  id: number;
  name: string;
  email: string;
  created_at: string;
  updated_at?: string;
}

interface Task {
  id: number;
  title: string;
  description: string;
  user_id: number;
  created_at: string;
  updated_at?: string;
  user?: User;
}

interface PaginatedResponse<T> {
  data: T[];
  current_page: number;
  per_page: number;
  total: number;
  last_page?: number;
}

interface ApiResponse<T> {
  data: T;
  message?: string;
}

interface ErrorResponse {
  error: string;
}

interface ValidationErrorResponse {
  errors: {
    [field: string]: string[];
  };
}
```

## ⚛️ Exemplos com React

### Hook para Gerenciamento de Usuários
```javascript
import { useState, useEffect } from 'react';

export function useUsers() {
  const [users, setUsers] = useState([]);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);

  const fetchUsers = async (filters = {}) => {
    setLoading(true);
    setError(null);

    try {
      const params = new URLSearchParams(filters);
      const response = await fetch(`http://localhost:8888/users?${params}`);
      
      if (!response.ok) {
        throw new Error('Erro ao buscar usuários');
      }

      const data = await response.json();
      setUsers(data.data);
    } catch (err) {
      setError(err.message);
    } finally {
      setLoading(false);
    }
  };

  const createUser = async (userData) => {
    try {
      const response = await fetch('http://localhost:8888/users', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json'
        },
        body: JSON.stringify(userData)
      });

      if (!response.ok) {
        const errorData = await response.json();
        throw new Error(errorData.error || 'Erro ao criar usuário');
      }

      const newUser = await response.json();
      setUsers(prev => [...prev, newUser.data]);
      
      return { success: true, data: newUser.data };
    } catch (err) {
      setError(err.message);
      return { success: false, error: err.message };
    }
  };

  const deleteUser = async (userId) => {
    try {
      const response = await fetch(`http://localhost:8888/users/${userId}`, {
        method: 'DELETE',
        headers: {
          'Accept': 'application/json'
        }
      });

      if (!response.ok) {
        throw new Error('Erro ao excluir usuário');
      }

      setUsers(prev => prev.filter(user => user.id !== userId));
      return { success: true };
    } catch (err) {
      setError(err.message);
      return { success: false, error: err.message };
    }
  };

  return {
    users,
    loading,
    error,
    fetchUsers,
    createUser,
    deleteUser
  };
}
```

### Componente de Lista de Usuários
```javascript
import React, { useEffect } from 'react';
import { useUsers } from './useUsers';

export function UsersList() {
  const { users, loading, error, fetchUsers, deleteUser } = useUsers();

  useEffect(() => {
    fetchUsers();
  }, []);

  const handleDelete = async (userId) => {
    if (window.confirm('Deseja excluir este usuário?')) {
      const result = await deleteUser(userId);
      if (result.success) {
        alert('Usuário excluído com sucesso!');
      } else {
        alert('Erro ao excluir usuário: ' + result.error);
      }
    }
  };

  if (loading) return <div>Carregando...</div>;
  if (error) return <div>Erro: {error}</div>;

  return (
    <div>
      <h2>Usuários</h2>
      {users.map(user => (
        <div key={user.id} className="user-card">
          <h3>{user.name}</h3>
          <p>{user.email}</p>
          <button onClick={() => handleDelete(user.id)}>
            Excluir
          </button>
        </div>
      ))}
    </div>
  );
}
```

## 📋 Boas Práticas

### 1. Sempre use try/catch
```javascript
// ✅ Bom
try {
  const response = await fetch('/users');
  const data = await response.json();
} catch (error) {
  console.error('Erro:', error);
}
```

### 2. Verifique o status da resposta
```javascript
// ✅ Bom
if (!response.ok) {
  throw new Error(`Erro ${response.status}`);
}
```

### 3. Use AbortController para cancelar requisições
```javascript
const controller = new AbortController();

fetch('/users', { signal: controller.signal })
  .catch(error => {
    if (error.name === 'AbortError') {
      console.log('Requisição cancelada');
    }
  });

// Cancelar se necessário
controller.abort();
```

### 4. Implemente loading states
```javascript
const [loading, setLoading] = useState(false);

const fetchData = async () => {
  setLoading(true);
  try {
    // fetch data
  } finally {
    setLoading(false);
  }
};
```

## 🧪 Testando com Postman

### Importando a Collection
1. Abra o Postman
2. Clique em "Import"
3. Selecione o arquivo `postman/PsicoManager-API.postman_collection.json`
4. Importe também o environment `postman/PsicoManager-Environment.postman_environment.json`

### Variáveis de Environment
- `base_url`: http://localhost:8888
- `user_id`: 77 (ID de usuário para testes)
- `task_id`: 1 (ID de tarefa para testes)

### Testes Inclusos
A collection inclui cenários de teste para:
- ✅ Validação de dados de entrada
- ✅ Tratamento de erros 404
- ✅ Validação de IDs inválidos
- ✅ Testes de email duplicado

## 🔍 Debugging

### Console do Browser
```javascript
// Log de requisições
fetch('/users')
  .then(response => {
    console.log('Status:', response.status);
    console.log('Headers:', response.headers);
    return response.json();
  })
  .then(data => console.log('Data:', data));
```

### Network Tab
Use a aba Network do DevTools para:
- Verificar requisições HTTP
- Analisar headers de request/response
- Verificar tempo de resposta
- Debugar problemas de CORS

## 🆘 Suporte

### Em caso de problemas:
1. Verifique a documentação Swagger: http://localhost:8888/api-documentation.html
2. Teste os endpoints com a collection Postman
3. Verifique os logs do console no DevTools
4. Consulte os exemplos neste guia

### Endpoints de Teste Rápido
```bash
# Testar se API está funcionando
curl http://localhost:8888/

# Listar usuários
curl -H "Accept: application/json" http://localhost:8888/users

# Buscar usuário específico  
curl -H "Accept: application/json" http://localhost:8888/users/77
```

---

*Documentação atualizada em: 26/09/2025*