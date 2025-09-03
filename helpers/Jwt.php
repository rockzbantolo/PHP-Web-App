<?php
namespace Mws\Helpers;

use Firebase\JWT\JWT as FirebaseJWT;
use Firebase\JWT\Key;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\SignatureInvalidException;

class JWT
{
    private static function getSecretKey(): string
    {
        return $_ENV['JWT_SECRET'] ?? 'your-default-secret-key-change-in-production';
    }
    
    private static function getAlgorithm(): string
    {
        return 'HS256';
    }
    
    /*
    public static function encode(array $payload): string
    {
        $payload['iat'] = time(); // Issued at
        $payload['exp'] = $payload['exp'] ?? (time() + (int)($_ENV['JWT_EXPIRE'] ?? 86400));
        
        return FirebaseJWT::encode($payload, self::getSecretKey(), self::getAlgorithm());
    }
    
    public static function decode(string $token): array
    {
        try {
            $decoded = FirebaseJWT::decode($token, new Key(self::getSecretKey(), self::getAlgorithm()));
            return (array) $decoded;
            
        } catch (ExpiredException $e) {
            throw new \Exception('Token has expired');
            
        } catch (SignatureInvalidException $e) {
            throw new \Exception('Token signature is invalid');
            
        } catch (\Exception $e) {
            throw new \Exception('Invalid token: ' . $e->getMessage());
        }
    }
    
    public static function verify(string $token): bool
    {
        try {
            self::decode($token);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
    
    public static function getUserFromToken(string $token): ?array
    {
        try {
            $decoded = self::decode($token);
            return [
                'UserID' => $decoded['UserID'] ?? null,
                'Email' => $decoded['Email'] ?? null
            ];
        } catch (\Exception $e) {
            return null;
        }
    }
    
    public static function isExpired(string $token): bool
    {
        try {
            $decoded = self::decode($token);
            return ($decoded['exp'] ?? 0) < time();
        } catch (\Exception $e) {
            return true;
        }
    }
    
    public static function refreshToken(string $token): ?string
    {
        try {
            $decoded = self::decode($token);
            
            // Only refresh if token is not expired or expires within next hour
            $expiresIn = ($decoded['exp'] ?? 0) - time();
            if ($expiresIn < 0 || $expiresIn > 3600) {
                return null;
            }
            
            // Create new token with same payload but new expiration
            $newPayload = $decoded;
            unset($newPayload['iat'], $newPayload['exp']);
            
            return self::encode($newPayload);
            
        } catch (\Exception $e) {
            return null;
        }
    }
    */
}