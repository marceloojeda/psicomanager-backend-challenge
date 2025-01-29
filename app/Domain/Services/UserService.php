<?php

namespace App\Domain\Services;

use App\Domain\Interfaces\UserRepositoryInterface;
use Illuminate\Http\Response;
use App\Application\Resources\UserResource;
use Exception;

use App\Infrastructure\Services\ServiceResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Application\Requests\UserRequest;
use App\Infrastructure\Persistence\Models\User;

class UserService extends ServiceResponse {
    public function __construct(private UserRepositoryInterface $userRepository) {}

    /**
     * Método para filtrar usuários com base nos filtros fornecidos.
     *
     * @param array $filters Um array contendo os filtros (id e nome).
     * @return bool
     */
    public function getFilteredUsersService(array $filters): bool
    {
        try {
            $data = $this->userRepository->getFilteredUsersRepository($filters);

            $this->setStatus(Response::HTTP_OK);
            $this->setMessage('Usuários listados com sucesso!');
            $this->setCollection($data);
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
     * @param int $id ID do usuario passado.
     * @return bool
     */
    public function getUserService(int $id): bool
    {
        try {
            $data = $this->userRepository->getUserRepository($id);

            $this->setStatus(Response::HTTP_OK);
            $this->setMessage('Usuário encontrado com sucesso!');
            $this->setCollectionItem($data);
            $this->setResource(UserResource::class);

            return true;

        } catch (ModelNotFoundException $e) {
            throw new Exception("Tarefa não encontrada!");
            $this->setStatus(Response::HTTP_NOT_FOUND);
            $this->setMessage('Usuário não encontrado.');
            $this->setError($e->getMessage());
        } catch (Exception $e) {
            $this->setStatus(Response::HTTP_INTERNAL_SERVER_ERROR);
            $this->setMessage('Erro ao buscar usuário. Tente novamente mais tarde.');
            $this->setError($e->getMessage());
        }

        return false;
    }

    /**
     * Método para cadastrar usuario.
     *
     * @param array $data Dados para salvar usuario.
     * @return bool
     */
    public function createUserService(array $data): bool
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

    /**
     * Método para excluir usuario.
     *
     * @param int $id ID usuario para excluir.
     * @return bool
     */
    public function deleteUserService(int $id): bool
    {
        try {
            DB::beginTransaction();

            $user = User::where('id', $id)->firstOrFail();
            $user->delete();

            $this->setStatus(Response::HTTP_OK);
            $this->setMessage('Usuário deletado com sucesso!');

            DB::commit();
            return true;

        } catch (ModelNotFoundException $e) {
            DB::rollback();

            $this->setStatus(Response::HTTP_NOT_FOUND);
            $this->setMessage('Usuário não encontrado.');
            $this->setError($e->getMessage());
        } catch (Exception $e) {
            DB::rollback();

            $this->setStatus(Response::HTTP_INTERNAL_SERVER_ERROR);
            $this->setMessage('Erro ao excluir usuário. Tente novamente mais tarde.');
            $this->setError($e->getMessage());
        }

        return false;
    }
}
