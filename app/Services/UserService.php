<?php

namespace App\Services;

use App\Models\User;
use Exception;

use Illuminate\Http\Response;
use App\Services\ServiceResponse;
use App\Http\Resources\UserResource;
use Illuminate\Support\Facades\Hash;
use App\Http\Requests\UserRequest;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserService extends ServiceResponse
{
    /**
     * Método para filtrar usuários com base nos filtros fornecidos.
     *
     * @param array $filters Um array contendo os filtros (id e nome).
     * @return bool
     */
    public function getFilteredUsers(array $filters): bool
    {
        try {
            $object = User::when(!empty($filters['id']), function ($query) use ($filters) {
                return $query->where('id', $filters['id']);
            })
            ->when(!empty($filters['name']), function ($query) use ($filters) {
                return $query->where('name', 'like', '%' . $filters['name'] . '%');
            })
            ->get();

            $data = $object->all();

            $this->setStatus(Response::HTTP_OK);
            $this->setMessage('Usuários listados com sucesso!');
            $this->setData($data);
            $this->setResource(UserResource::class);

            return true;
        } catch (Exception $e) {
            $this->setStatus(Response::HTTP_INTERNAL_SERVER_ERROR);
            $this->setMessage('Erro ao listar usuários.Tente novamente mais tarde.');
            $this->setError($e->getMessage());

            return false;
        }
    }

    /**
     * Método para pegar unico usuário pelo id.
     *
     * @param User $user Model do usuario passado.
     * @return bool
     */
    public function getUser(User $user): bool
    {
        try {
            $this->setStatus(Response::HTTP_OK);
            $this->setMessage('Usuários encontrado com sucesso!');
            $this->setData($user->toArray());
            $this->setResource(UserResource::class);

            return true;
        } catch (Exception $e) {
            $this->setStatus(Response::HTTP_INTERNAL_SERVER_ERROR);
            $this->setMessage('Erro ao buscar o usuário. Tente novamente mais tarde.');
            $this->setError($e->getMessage());

            return false;
        }
    }

    /**
     * Método para cadastrar usuario.
     *
     * @param array $data Dados para salvar usuario.
     * @return bool
     */
    public function createUser(array $data): bool
    {
        try {
            if (
                empty($data['name']) === true ||
                empty($data['email']) === true ||
                empty($data['password']) === true
            ) {
                throw new Exception('Os campos nome, email e senha não estão preenchidos!');
            }

            DB::beginTransaction();

            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
            ]);

            $this->setStatus(Response::HTTP_OK);
            $this->setMessage('Usuário cadastrado com sucesso!');
            $this->setData($user->toArray());
            $this->setResource(UserResource::class);

            DB::commit();

            return true;
        } catch (Exception $e) {
            DB::rollback();

            $this->setStatus(Response::HTTP_INTERNAL_SERVER_ERROR);
            $this->setMessage('Erro ao cadastrar usuário. Tente novamente mais tarde.');
            $this->setError($e->getMessage());

            return false;
        }
    }

    /**
     * Validar novo usuário.
     *
     * @param Request $request Dados usuario para salvar.
     * @return bool
     */
    function validateUser(Request $request): bool
    {
        try {
            $userRequest = new UserRequest();
            $userRequest->validate($request);

            return true;
        } catch (ValidationException $e) {
            $this->setStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
            $this->setMessage('Erro na validação no cadastro de usuário.');
            $this->setError($e->getMessage());

            $errors = $e->errors();

            if (is_array($errors) === true) {
                $errors = $errors;
            } else {
                $errors = json_decode($errors, true);

                if (json_last_error() !== JSON_ERROR_NONE) {
                    $errors = [];
                }
            }

            $this->setData($errors);

            return false;
        }
    }
}
