<?php

namespace App\Services;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\SignatureInvalidException;
use Carbon\Carbon;
use App\Models\User;

class JWTService
{
    private string $secretKey;
    private string $algorithm;
    private int $expirationTime;
    private int $refreshExpirationTime;
    private string $issuer;
    private string $audience;

    public function __construct()
    {
        $this->secretKey = env('JWT_SECRET', 'psicomanager_secret_key_2025');
        $this->algorithm = 'HS256';
        $this->expirationTime = (int) env('JWT_EXPIRATION', 3600); // 1 hora por padrão
        $this->refreshExpirationTime = (int) env('JWT_REFRESH_EXPIRATION', 2592000); // 30 dias
        $this->issuer = env('JWT_ISSUER', env('APP_URL', 'http://localhost'));
        $this->audience = env('JWT_AUDIENCE', 'psicomanager-api');
    }

    /**
     * Gera um token JWT para o usuário
     */
    public function generateToken(User $user): string
    {
        $payload = [
            'iss' => $this->issuer, // Issuer
            'sub' => $user->id, // Subject (user ID)
            'aud' => $this->audience, // Audience
            'iat' => Carbon::now()->timestamp, // Issued at
            'exp' => Carbon::now()->addSeconds($this->expirationTime)->timestamp, // Expiration
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email
            ]
        ];

        return JWT::encode($payload, $this->secretKey, $this->algorithm);
    }

    /**
     * Valida e decodifica um token JWT
     */
    public function validateToken(string $token): ?array
    {
        try {
            $decoded = JWT::decode($token, new Key($this->secretKey, $this->algorithm));
            return (array) $decoded;
        } catch (ExpiredException $e) {
            throw new \Exception('Token expirado', 401);
        } catch (SignatureInvalidException $e) {
            throw new \Exception('Token inválido', 401);
        } catch (\Exception $e) {
            throw new \Exception('Token malformado', 401);
        }
    }

    /**
     * Extrai o usuário do token
     */
    public function getUserFromToken(string $token): ?User
    {
        try {
            $payload = $this->validateToken($token);
            $userId = $payload['sub'] ?? null;
            
            if ($userId) {
                return User::find($userId);
            }
            
            return null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Gera um refresh token (mais longo)
     */
    public function generateRefreshToken(User $user): string
    {
        $payload = [
            'iss' => $this->issuer,
            'sub' => $user->id,
            'aud' => $this->audience . '-refresh',
            'iat' => Carbon::now()->timestamp,
            'exp' => Carbon::now()->addSeconds($this->refreshExpirationTime)->timestamp,
            'type' => 'refresh'
        ];

        return JWT::encode($payload, $this->secretKey, $this->algorithm);
    }

    /**
     * Valida se é um refresh token válido
     */
    public function validateRefreshToken(string $token): bool
    {
        try {
            $payload = $this->validateToken($token);
            return ($payload['type'] ?? null) === 'refresh';
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Extrai informações do token sem validar expiração (para debug)
     */
    public function getTokenInfo(string $token): ?array
    {
        try {
            $parts = explode('.', $token);
            if (count($parts) !== 3) {
                return null;
            }
            
            $payload = json_decode(base64_decode($parts[1]), true);
            return $payload;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Verifica se o token está próximo do vencimento
     */
    public function isTokenExpiringSoon(string $token, int $thresholdMinutes = 15): bool
    {
        try {
            $payload = $this->validateToken($token);
            $exp = $payload['exp'] ?? 0;
            $threshold = Carbon::now()->addMinutes($thresholdMinutes)->timestamp;
            
            return $exp <= $threshold;
        } catch (\Exception $e) {
            return true; // Se não conseguir validar, considere que está expirando
        }
    }

    /**
     * Check if token is expiring soon (within 15 minutes) - alias for isTokenExpiringSoon
     *
     * @param string $token
     * @return bool
     */
    public function isTokenExpiring(string $token): bool
    {
        return $this->isTokenExpiringSoon($token, 15);
    }

    /**
     * Get remaining time to token expiration in seconds
     *
     * @param string $token
     * @return int
     */
    public function getTokenTimeToExpire(string $token): int
    {
        try {
            $payload = $this->validateToken($token);
            $exp = $payload['exp'] ?? 0;
            $timeToExpire = $exp - time();
            
            return max(0, $timeToExpire);
            
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Get token expiration time in seconds
     *
     * @return int
     */
    public function getExpirationTime(): int
    {
        return $this->expirationTime;
    }

    /**
     * Get refresh token expiration time in seconds  
     *
     * @return int
     */
    public function getRefreshExpirationTime(): int
    {
        return $this->refreshExpirationTime;
    }
}