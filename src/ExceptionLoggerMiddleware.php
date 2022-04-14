<?php

declare(strict_types=1);

namespace Whirlwind\Middleware\ExceptionLogger;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use Whirlwind\Infrastructure\Http\Exception\HttpException;

class ExceptionLoggerMiddleware implements MiddlewareInterface
{
    protected LoggerInterface $logger;

    protected bool $enabled;

    protected bool $enableHttpExceptionLogging;

    public function __construct(LoggerInterface $logger, bool $enabled = true, bool $enableHttpExceptionLogging = false)
    {
        $this->logger = $logger;
        $this->enabled = $enabled;
        $this->enableHttpExceptionLogging = $enableHttpExceptionLogging;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            return $handler->handle($request);
        } catch (\Throwable $e) {
            if (false === $this->enabled) {
                throw $e;
            }
            if ($e instanceof HttpException and false === $this->enableHttpExceptionLogging) {
                throw $e;
            }
            $context = [
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
                'class' => \get_class($e),
                'stackTrace' => $e->getTraceAsString()
            ];
            if ($e instanceof HttpException) {
                $context['status'] = $e->getStatusCode();
            }
            $this->logger->error($e->getMessage(), $context);
            throw $e;
        }
    }
}
