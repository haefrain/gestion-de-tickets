<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Http;

use App\Shared\Application\Health\HealthCheck;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * GET /api/v1/health/ready — readiness. Pinguea cada dependencia vía su adaptador.
 * 200 si todas están listas; 503 (application/problem+json, RFC 7807) si alguna falla.
 */
final readonly class ReadinessController
{
    /**
     * @param iterable<HealthCheck> $checks
     */
    public function __construct(private iterable $checks)
    {
    }

    public function __invoke(): JsonResponse
    {
        $dependencies = [];
        $allHealthy = true;

        foreach ($this->checks as $check) {
            $healthy = $check->isHealthy();
            $dependencies[$check->name()] = $healthy ? 'ok' : 'down';
            $allHealthy = $allHealthy && $healthy;
        }

        if ($allHealthy) {
            return new JsonResponse([
                'status' => 'ready',
                'dependencies' => $dependencies,
            ]);
        }

        return new JsonResponse(
            [
                'type' => 'https://docs.iatsae.dev/errors/service-unavailable',
                'title' => 'Servicio no disponible',
                'status' => Response::HTTP_SERVICE_UNAVAILABLE,
                'detail' => 'Una o más dependencias no están listas.',
                'dependencies' => $dependencies,
            ],
            Response::HTTP_SERVICE_UNAVAILABLE,
            ['Content-Type' => 'application/problem+json'],
        );
    }
}
