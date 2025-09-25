# SOLUTION.md - Análise e Soluções dos Problemas da API

Este documento detalha a análise e correção dos problemas identificados na API de gerenciamento de usuários e tarefas.

## Problema 1: Falta de Filtros no Endpoint GET /users

### Diagnóstico
O endpoint `GET /users` retornava todos os usuários sem possibilidade de filtragem, causando problemas de performance e usabilidade.

**Código original:**
```php
function index()
{
    $users = User::all(); // Sempre retorna TODOS os usuários
    return response()->json($users);
}
```

### Solução Implementada
Adicionei filtros opcionais por ID e nome, mantendo a compatibilidade com o comportamento original:

```php
function index(Request $request)
{
    $query = User::query();

    if ($request->has('id')) {
        $query->where('id', $request->get('id'));
    }

    if ($request->has('name')) {
        $query->where('name', 'like', '%' . $request->get('name') . '%');
    }

    // Paginação opcional
    $perPage = $request->get('per_page', 15);
    $perPage = min($perPage, 100);

    if ($request->has('page') || $request->has('per_page')) {
        $users = $query->paginate($perPage);
    } else {
        $users = $query->get();
    }
    
    return response()->json($users);
}
```

**Funcionalidades adicionadas:**
- Filtro por ID: `GET /users?id=1`
- Filtro por nome (parcial e case-insensitive): `GET /users?name=João`
- Filtros combinados: `GET /users?id=1&name=João`

**🆕 Melhoria Adicional - Paginação:**
- Paginação opcional: `GET /users?page=1&per_page=10`
- Compatível com filtros: `GET /users?name=João&page=1&per_page=5`
- Limite de segurança: máximo 100 itens por página
- Resposta estruturada com metadados de navegação
- Retrocompatibilidade total: sem parâmetros de paginação retorna todos os resultados

**Resultados:**
- ✅ Performance otimizada com queries específicas
- ✅ Escalabilidade melhorada para grandes volumes de dados
- ✅ Compatibilidade total mantida

---

## Problema 2: Senha dos Usuários Expostas

### Diagnóstico
**Problema identificado:**
- O endpoint GET /users estava retornando todos os campos do modelo User, incluindo campos sensíveis como `password` e `remember_token`

**Evidência:**
```json
{
  "data": [
    {
      "id": 1,
      "name": "João Silva",
      "email": "joao@example.com",
      "password": "$2y$10$...", // ⚠️ VULNERABILIDADE
      "remember_token": "abc123", // ⚠️ VULNERABILIDADE
      "created_at": "2024-12-23T14:30:00Z"
    }
  ]
}
```

### Solução
**Implementação de API Resource:**

1. **Criação da UserResource.php:**
```php
<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
```

2. **Atualização do UserController:**
```php
use App\Http\Resources\UserResource;

public function index(Request $request)
{
    // ... lógica de filtros e paginação
    return UserResource::collection($users);
}

public function get($id)
{
    // ... validação
    return new UserResource($user);
}
```

**Resultados:**
- ✅ Senhas e tokens não são mais expostos
- ✅ API retorna apenas campos seguros e necessários  
- ✅ Padrão de segurança implementado para futuras expansões
- ✅ Compatibilidade com estrutura de resposta padronizada

---

## Problema 3: Falta de Endpoint para Usuário Específico

### Diagnóstico
Não havia endpoint dedicado para buscar um usuário específico por ID na URL.

### Solução Implementada
Adicionei o método `get` no UserController com validação robusta de ID:

```php
function get($userId)
{
    // Validação do ID - deve ser numérico e positivo
    if (!is_numeric($userId) || $userId <= 0) {
        return response()->json([
            'error' => 'ID inválido. O ID deve ser um número positivo.'
        ], 400);
    }

    try {
        $user = User::where('id', $userId)->firstOrFail();
        return response()->json($user);
    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
        return response()->json([
            'error' => 'Usuário não encontrado.',
            'message' => "Usuário com ID {$userId} não existe."
        ], 404);
    }
}
```

**Funcionalidades adicionadas:**
- Endpoint específico: `GET /users/{id}`
- ✅ **Validação de ID:** Rejeita IDs não numéricos, negativos ou zero
- ✅ **Tratamento de erro 400:** Para IDs inválidos com mensagem explicativa
- ✅ **Tratamento de erro 404:** Para usuários não encontrados com detalhes
- ✅ **Resposta estruturada:** JSON consistente para sucesso e erros

**Casos de validação:**
- `GET /users/1` → Sucesso: dados do usuário
- `GET /users/abc` → Erro 400: ID inválido
- `GET /users/-1` → Erro 400: ID inválido  
- `GET /users/999` → Erro 404: usuário não encontrado

---

## Problema 4: Senhas Não Criptografadas no POST /users (A ser implementado)

### Diagnóstico
[A ser documentado]

### Solução
[A ser implementada]

---

## Problema 5: Lentidão no GET /tasks?userId={id} (A ser implementado)

### Diagnóstico
[A ser documentado]

### Solução
[A ser implementada]

---

## Problema 6: Emails Duplicados (A ser implementado)

### Diagnóstico
[A ser documentado]

### Solução
[A ser implementada]

---

## Problema 7: Falha ao Excluir Usuários com Tarefas (A ser implementado)

### Diagnóstico
[A ser documentado]

### Solução
[A ser implementada]

---

## Problema 8: Implementar Soft Delete (A ser implementado)

### Diagnóstico
[A ser documentado]

### Solução
[A ser implementada]

---

## Problema 9: Logs das Operações (A ser implementado)

### Diagnóstico
[A ser documentado]

### Solução
[A ser implementada]

---

## Problema 10: Documentação da API (A ser implementado)

### Diagnóstico
[A ser documentado]

### Solução
[A ser implementada]

---

## Resumo do Progresso

| Problema | Status | Testes |
|----------|--------|--------|
| 1. Filtros GET /users + Paginação | ✅ Concluído 
| 2. Senha exposta | ⏳ Pendente | - |
| 3. Endpoint usuário específico + Validação | ✅ Concluído 
| 4. Criptografia de senha | ⏳ Pendente | - |
| 5. Performance tasks | ⏳ Pendente | - |
| 6. Emails duplicados | ⏳ Pendente | - |
| 7. Delete com tarefas | ⏳ Pendente | - |
| 8. Soft delete | ⏳ Pendente | - |
| 9. Logs centralizados | ⏳ Pendente | - |
| 10. Documentação API | ⏳ Pendente | - |