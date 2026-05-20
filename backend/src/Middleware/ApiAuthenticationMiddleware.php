<?php
declare(strict_types=1);

namespace App\Middleware;

use App\Service\TokenService;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class ApiAuthenticationMiddleware implements MiddlewareInterface
{
    /**
     * @param array<string> $publicPaths
     */
    public function __construct(
        private readonly string $secret,
        private readonly array $publicPaths = ['/api/v1/auth/login', '/api/v1/auth/demo']
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $path = $request->getUri()->getPath();
        if (!str_starts_with($path, '/api/v1/') || in_array($path, $this->publicPaths, true)) {
            return $handler->handle($request);
        }

        $authorization = $request->getHeaderLine('Authorization');
        if (!str_starts_with($authorization, 'Bearer ')) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Missing bearer token.',
            ], 401);
        }

        $token = substr($authorization, 7);
        $identity = (new TokenService($this->secret))->verify($token);
        if ($identity === null) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Invalid or expired token.',
            ], 401);
        }

        return $handler->handle($request->withAttribute('identity', $identity));
    }
}
