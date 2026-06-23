<?php

declare(strict_types=1);

namespace App\Identity\Infrastructure\Http;

use App\Identity\Application\Command\UpdateUserCommand;
use App\Shared\Application\Bus\CommandBus;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;

/**
 * PATCH /api/v1/users/{id} (HU-L1-E2-03). Solo Admin (access_control): cambia roles y/o estado.
 */
final readonly class UpdateUserController
{
    public function __construct(private CommandBus $commandBus)
    {
    }

    public function __invoke(string $id, #[MapRequestPayload] UpdateUserRequest $request): Response
    {
        $this->commandBus->dispatch(new UpdateUserCommand($id, $request->roles, $request->active));

        return new Response(null, Response::HTTP_NO_CONTENT);
    }
}
