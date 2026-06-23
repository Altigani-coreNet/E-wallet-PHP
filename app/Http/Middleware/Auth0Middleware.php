<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class Auth0Middleware
{
    private $auth0Domain;
    private $auth0Audience;
    private $jwksUri;

    public function __construct()
    {
        $this->auth0Domain = config('auth0.domain', 'dev-xfvxo1j6cfqr7hn2.us.auth0.com');
        $this->auth0Audience = config('auth0.audience', 'your-api-identifier');
        $this->jwksUri = "https://{$this->auth0Domain}/.well-known/jwks.json";
    }

    public function handle(Request $request, Closure $next)
    {
        try {
            // Get token from Authorization header
            $token = $this->getBearerToken($request);
            
            if (!$token) {
                return response()->json([
                    'status' => false,
                    'message' => 'No token provided',
                    'Error_Code' => 'NO_TOKEN'
                ], 401);
            }

            // Verify and decode the token
            $user = $this->verifyToken($token);
            
            // Add user info to request
            $request->attributes->set('auth0_user', $user);

            return $next($request);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid or expired token',
                'Error_Code' => 'INVALID_TOKEN'
            ], 401);
        }
    }

    private function getBearerToken(Request $request): ?string
    {
        $header = $request->header('Authorization');
        
        if (!$header || !str_starts_with($header, 'Bearer ')) {
            return null;
        }

        return substr($header, 7);
    }

    private function verifyToken(string $token): array
    {
        // Get JWKS keys
        $jwks = $this->getJWKS();
        
        // Decode header to get kid
        $header = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], explode('.', $token)[0])), true);
        
        if (!isset($header['kid'])) {
            throw new \Exception('Token header missing kid');
        }

        // Find the correct key
        $key = null;
        foreach ($jwks['keys'] as $jwk) {
            if ($jwk['kid'] === $header['kid']) {
                $key = $this->jwkToPem($jwk);
                break;
            }
        }

        if (!$key) {
            throw new \Exception('Unable to find key');
        }

        // Verify and decode token
        $decoded = JWT::decode($token, new Key($key, 'RS256'));
        
        // Validate audience and issuer
        if ($decoded->aud !== $this->auth0Audience) {
            throw new \Exception('Invalid audience');
        }

        if ($decoded->iss !== "https://{$this->auth0Domain}/") {
            throw new \Exception('Invalid issuer');
        }

        return (array) $decoded;
    }

    private function getJWKS(): array
    {
        $client = new Client();
        
        try {
            $response = $client->get($this->jwksUri);
            return json_decode($response->getBody()->getContents(), true);
        } catch (RequestException $e) {
            throw new \Exception('Failed to fetch JWKS');
        }
    }

    private function jwkToPem(array $jwk): string
    {
        $n = $this->base64urlDecode($jwk['n']);
        $e = $this->base64urlDecode($jwk['e']);

        $modulus = $this->convertToBigInteger($n);
        $exponent = $this->convertToBigInteger($e);

        $keyData = pack('H*', '30820122300d06092a864886f70d01010105000382010f003082010a0282010100') .
                   $modulus .
                   pack('H*', '0203') .
                   $exponent;

        return "-----BEGIN PUBLIC KEY-----\n" . chunk_split(base64_encode($keyData), 64) . "-----END PUBLIC KEY-----";
    }

    private function base64urlDecode(string $data): string
    {
        return base64_decode(str_pad(strtr($data, '-_', '+/'), strlen($data) % 4, '=', STR_PAD_RIGHT));
    }

    private function convertToBigInteger(string $data): string
    {
        $hex = bin2hex($data);
        return hex2bin('00' . $hex);
    }
}
