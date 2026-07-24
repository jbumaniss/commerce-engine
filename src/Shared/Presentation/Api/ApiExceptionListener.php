<?php

declare(strict_types=1);

namespace App\Shared\Presentation\Api;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Validator\Exception\ValidationFailedException;

/**
 * Renders a consistent RFC 7807 (application/problem+json) error response for every
 * /api/ endpoint.
 *
 * Priority -64 places this after Symfony's ErrorListener::logKernelException (priority 0),
 * which both logs the exception and applies any #[WithHttpStatus] mapping, and before the
 * default error renderer (priority -128), which this replaces by setting a response.
 */
#[AsEventListener(event: KernelEvents::EXCEPTION, priority: -64)]
final class ApiExceptionListener
{
    public function __invoke(ExceptionEvent $event): void
    {
        if (!str_starts_with($event->getRequest()->getPathInfo(), '/api/')) {
            return;
        }

        $throwable = $event->getThrowable();

        $status = $throwable instanceof HttpExceptionInterface
            ? $throwable->getStatusCode()
            : Response::HTTP_INTERNAL_SERVER_ERROR;

        $problem = [
            'type' => 'about:blank',
            'title' => Response::$statusTexts[$status] ?? 'Error',
            'status' => $status,
            'detail' => $this->detail($throwable, $status),
        ];

        $violations = $this->violations($throwable);

        if ([] !== $violations) {
            $problem['violations'] = $violations;
        }

        $response = new JsonResponse($problem, $status);

        if ($throwable instanceof HttpExceptionInterface) {
            $response->headers->add($throwable->getHeaders());
        }

        $response->headers->set('Content-Type', 'application/problem+json');

        $event->setResponse($response);
    }

    private function detail(\Throwable $throwable, int $status): string
    {
        if ($status >= Response::HTTP_INTERNAL_SERVER_ERROR) {
            // Never expose internal exception details on server errors.
            return 'An unexpected error occurred.';
        }

        if ($throwable->getPrevious() instanceof ValidationFailedException) {
            return 'Validation failed.';
        }

        $message = $throwable->getMessage();

        return '' !== $message ? $message : (Response::$statusTexts[$status] ?? 'Error');
    }

    /**
     * @return list<array{property: string, message: string}>
     */
    private function violations(\Throwable $throwable): array
    {
        $previous = $throwable->getPrevious();

        if (!$previous instanceof ValidationFailedException) {
            return [];
        }

        $violations = [];

        foreach ($previous->getViolations() as $violation) {
            $violations[] = [
                'property' => $violation->getPropertyPath(),
                'message' => (string) $violation->getMessage(),
            ];
        }

        return $violations;
    }
}
