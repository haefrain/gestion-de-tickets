<?php

declare(strict_types=1);

namespace App\Identity\Infrastructure\Http;

use App\Identity\Application\Command\RegisterUserCommand;
use App\Identity\Domain\Role;
use App\Identity\Domain\UserId;
use App\Shared\Application\Bus\CommandBus;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;

/**
 * POST /api/v1/register (HU-L1-E1-01). Controller delgado: valida el DTO y despacha el comando.
 */
final readonly class RegisterController
{
    public function __construct(private CommandBus $commandBus)
    {
    }

    public function __invoke(#[MapRequestPayload] RegisterRequest $request): JsonResponse
    {
        $userId = UserId::generate();

        $this->commandBus->dispatch(
            new RegisterUserCommand($userId->value(), $request->email, $request->password, $request->name),
        );

        return new JsonResponse(
            [
                'id' => $userId->value(),
                'email' => strtolower(trim($request->email)),
                'roles' => [Role::CLIENT],
            ],
            Response::HTTP_CREATED,
        );
    }
}
