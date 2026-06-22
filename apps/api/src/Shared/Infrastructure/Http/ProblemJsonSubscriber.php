<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Http;

use App\Shared\Domain\ConflictException;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\Validator\Exception\ValidationFailedException;

/**
 * Traduce excepciones a respuestas RFC 7807 (application/problem+json). Transversal a todos los contextos.
 */
#[AsEventListener(event: 'kernel.exception')]
final class ProblemJsonSubscriber
{
    public function __invoke(ExceptionEvent $event): void
    {
        $throwable = $event->getThrowable();

        if ($throwable instanceof ConflictException) {
            $event->setResponse($this->problem(Response::HTTP_CONFLICT, 'Conflicto de estado', 'El recurso ya existe o entra en conflicto.'));

            return;
        }

        $violations = $this->violationsOf($throwable);
        if (null !== $violations) {
            $event->setResponse($this->problem(
                Response::HTTP_UNPROCESSABLE_ENTITY,
                'La validación ha fallado',
                'Uno o más campos son inválidos.',
                $violations,
            ));

            return;
        }

        if ($throwable instanceof HttpExceptionInterface) {
            $status = $throwable->getStatusCode();
            $event->setResponse($this->problem($status, Response::$statusTexts[$status] ?? 'Error', $throwable->getMessage()));
        }
    }

    /**
     * @return list<array{field: string, message: string}>|null
     */
    private function violationsOf(\Throwable $throwable): ?array
    {
        $previous = $throwable->getPrevious();
        $failed = match (true) {
            $throwable instanceof ValidationFailedException => $throwable,
            $previous instanceof ValidationFailedException => $previous,
            default => null,
        };
        if (!$failed instanceof ValidationFailedException) {
            return null;
        }

        $errors = [];
        foreach ($failed->getViolations() as $violation) {
            $errors[] = ['field' => $violation->getPropertyPath(), 'message' => (string) $violation->getMessage()];
        }

        return $errors;
    }

    /**
     * @param list<array{field: string, message: string}>|null $errors
     */
    private function problem(int $status, string $title, string $detail, ?array $errors = null): JsonResponse
    {
        $payload = [
            'type' => 'https://docs.iatsae.dev/errors/'.$status,
            'title' => $title,
            'status' => $status,
            'detail' => $detail,
        ];
        if (null !== $errors) {
            $payload['errors'] = $errors;
        }

        return new JsonResponse($payload, $status, ['Content-Type' => 'application/problem+json']);
    }
}
