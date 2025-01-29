<?php

namespace App\Infrastructure\Cache;

use Illuminate\Support\Facades\Redis;

/**
 * Serviço para gerenciamento de cache no Redis.
 *
 * Esta classe oferece métodos para armazenar, recuperar e excluir dados do Redis de forma padronizada.
 */
class RedisCacheService
{
    /**
     * Armazena um valor no Redis com tempo de expiração.
     *
     * @param string $key Chave única para armazenar os dados.
     * @param mixed $value Dados a serem armazenados (serão serializados).
     * @param int $ttl Tempo de expiração em segundos (padrão: 600s = 10 minutos).
     *
     * @return void
     */
    public function set(string $key, mixed $value, int $ttl = 600): void
    {
        Redis::setex($key, $ttl, serialize($value));
    }

    /**
     * Recupera um valor armazenado no Redis.
     *
     * @param string $key Chave de identificação do cache.
     *
     * @return mixed Retorna o valor armazenado ou null se não existir.
     */
    public function get(string $key): mixed
    {
        $cachedValue = Redis::get($key);
        return $cachedValue ? unserialize($cachedValue) : null;
    }

    /**
     * Remove um valor armazenado no Redis.
     *
     * @param string $key Chave única do cache a ser removido.
     *
     * @return bool Retorna true se o cache foi removido, false se não existir.
     */
    public function delete(string $key): bool
    {
        return Redis::del($key) > 0;
    }

    /**
     * Verifica se uma chave existe no Redis.
     *
     * @param string $key Chave única do cache.
     *
     * @return bool Retorna true se a chave existir, false caso contrário.
     */
    public function exists(string $key): bool
    {
        return Redis::exists($key) > 0;
    }

    /**
     * Obtém um valor do Redis com um identificador adicional opcional (exemplo: user_id).
     *
     * @param string $key Chave base do cache.
     * @param string|null $identifier Identificador adicional para diferenciar caches (exemplo: user_id).
     *
     * @return mixed Retorna os dados armazenados ou null se não existir.
     */
    public function getWithIdentifier(string $key, ?string $identifier = null): mixed
    {
        if ($identifier) {
            $key .= "_{$identifier}";
        }

        return $this->get($key);
    }

    /**
     * Armazena um valor no Redis com um identificador adicional (exemplo: user_id).
     *
     * @param string $key Chave base do cache.
     * @param mixed $value Valor a ser armazenado.
     * @param int $ttl Tempo de expiração em segundos (padrão: 600s = 10 minutos).
     * @param string|null $identifier Identificador adicional para diferenciar caches (exemplo: user_id).
     *
     * @return void
     */
    public function setWithIdentifier(string $key, mixed $value, int $ttl = 600, ?string $identifier = null): void
    {
        if ($identifier) {
            $key .= "_{$identifier}";
        }

        if ($ttl === 0) {
            $this->set($key, $value);
        } else {
            $this->set($key, $value, $ttl);
        }
    }
}
