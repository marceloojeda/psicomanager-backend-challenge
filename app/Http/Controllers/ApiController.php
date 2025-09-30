<?php

namespace App\Http\Controllers;

/**
 * @OA\Info(
 *     title="PsicoManager API",
 *     version="1.0.0",
 *     description="API para gerenciamento de usuários e tarefas do PsicoManager",
 *     @OA\Contact(
 *         email="dev@psicomanager.com",
 *         name="Equipe de Desenvolvimento PsicoManager"
 *     ),
 *     @OA\License(
 *         name="MIT",
 *         url="https://opensource.org/licenses/MIT"
 *     )
 * )
 * 
 * @OA\Server(
 *     url="http://localhost:8888",
 *     description="Servidor de Desenvolvimento"
 * )
 * 
 * @OA\SecurityScheme(
 *     securityScheme="bearerAuth",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="JWT",
 *     description="Token JWT para autenticação. Use o formato: Bearer {token}"
 * )
 * 
 * @OA\Tag(
 *     name="Authentication",
 *     description="Operações de autenticação e autorização"
 * )
 * 
 * @OA\Tag(
 *     name="Users",
 *     description="Operações relacionadas aos usuários"
 * )
 * 
 * @OA\Tag(
 *     name="Tasks", 
 *     description="Operações relacionadas às tarefas"
 * )
 * 
 * @OA\Schema(
 *     schema="User",
 *     type="object",
 *     title="User",
 *     description="Modelo de usuário",
 *     @OA\Property(
 *         property="id",
 *         type="integer",
 *         format="int64",
 *         description="ID único do usuário",
 *         example=77
 *     ),
 *     @OA\Property(
 *         property="name",
 *         type="string",
 *         description="Nome completo do usuário",
 *         example="João Silva"
 *     ),
 *     @OA\Property(
 *         property="email",
 *         type="string",
 *         format="email",
 *         description="E-mail do usuário",
 *         example="joao@example.com"
 *     ),
 *     @OA\Property(
 *         property="created_at",
 *         type="string",
 *         format="date-time",
 *         description="Data de criação",
 *         example="2025-09-25T14:30:00.000000Z"
 *     ),
 *     @OA\Property(
 *         property="updated_at",
 *         type="string",
 *         format="date-time",
 *         description="Data de atualização",
 *         example="2025-09-25T14:30:00.000000Z"
 *     )
 * )
 * 
 * @OA\Schema(
 *     schema="Task",
 *     type="object",
 *     title="Task",
 *     description="Modelo de tarefa",
 *     @OA\Property(
 *         property="id",
 *         type="integer",
 *         format="int64",
 *         description="ID único da tarefa",
 *         example=1
 *     ),
 *     @OA\Property(
 *         property="title",
 *         type="string",
 *         description="Título da tarefa",
 *         example="Implementar API"
 *     ),
 *     @OA\Property(
 *         property="description",
 *         type="string",
 *         description="Descrição detalhada da tarefa",
 *         example="Desenvolver endpoints REST para o sistema"
 *     ),
 *     @OA\Property(
 *         property="user_id",
 *         type="integer",
 *         format="int64",
 *         description="ID do usuário responsável",
 *         example=77
 *     ),
 *     @OA\Property(
 *         property="created_at",
 *         type="string",
 *         format="date-time",
 *         description="Data de criação",
 *         example="2025-09-25T14:30:00.000000Z"
 *     ),
 *     @OA\Property(
 *         property="updated_at",
 *         type="string",
 *         format="date-time",
 *         description="Data de atualização",
 *         example="2025-09-25T14:30:00.000000Z"
 *     ),
 *     @OA\Property(
 *         property="user",
 *         ref="#/components/schemas/User",
 *         description="Dados do usuário responsável (quando incluído)"
 *     )
 * )
 * 
 * @OA\Schema(
 *     schema="PaginatedResponse",
 *     type="object",
 *     title="Paginated Response",
 *     description="Resposta paginada padrão",
 *     @OA\Property(
 *         property="current_page",
 *         type="integer",
 *         description="Página atual",
 *         example=1
 *     ),
 *     @OA\Property(
 *         property="per_page",
 *         type="integer",
 *         description="Itens por página",
 *         example=15
 *     ),
 *     @OA\Property(
 *         property="total",
 *         type="integer",
 *         description="Total de itens",
 *         example=1
 *     ),
 *     @OA\Property(
 *         property="last_page",
 *         type="integer",
 *         description="Última página",
 *         example=1
 *     )
 * )
 * 
 * @OA\Schema(
 *     schema="Error",
 *     type="object",
 *     title="Error Response",
 *     description="Resposta de erro padrão",
 *     @OA\Property(
 *         property="error",
 *         type="string",
 *         description="Mensagem de erro",
 *         example="Recurso não encontrado"
 *     )
 * )
 * 
 * @OA\Schema(
 *     schema="ValidationError",
 *     type="object",
 *     title="Validation Error Response",
 *     description="Resposta de erro de validação",
 *     @OA\Property(
 *         property="errors",
 *         type="object",
 *         description="Lista de erros de validação por campo",
 *         example={
 *             "name": {"O campo nome é obrigatório."},
 *             "email": {"O campo email deve ser um endereço de e-mail válido."}
 *         }
 *     )
 * )
 */
class ApiController extends Controller
{
    //
}