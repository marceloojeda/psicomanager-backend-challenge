<?php

namespace App\Application\Controllers;

use Illuminate\Http\Request;
use App\Domain\Services\LogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use App\Http\Controllers\Controller;

class LogController extends Controller
{
    protected $logService;

    /**
     * Injeção de dependência do LogService no construtor.
     *
     * @param LogService $logService
     */
    public function __construct(LogService $logService)
    {
        $this->logService = $logService;
        $this->logService->setDataFromCollection(false);
    }

    /**
     * Método responsável por retornar uma lista de logs filtrados.
     *
     * @param Request $request
     * @return JsonResponse
     */
    function index(Request $request): JsonResponse
    {
        $this->logService->getFilteredLogs($request->all());
        return $this->logService->getJsonResponse();
    }
}
