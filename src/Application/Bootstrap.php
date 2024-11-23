<?php

declare(strict_types=1);

namespace CreditTransactionService\Application;

use DI\ContainerBuilder;
use Psr\Container\ContainerInterface;
use CreditTransactionService\Infrastructure\Config\Environment;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\PsrCachedReader;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use ErrorException;
use Throwable;

class Bootstrap
{
    private static ?ContainerInterface $container = null;

    public static function init(): void
    {
        self::initializeErrorHandling();
        self::loadEnvironment();
    }

    public static function getContainer(): ContainerInterface
    {
        if (null === self::$container) {
            self::$container = self::createContainer();
        }

        return self::$container;
    }

    private static function initializeErrorHandling(): void
    {
        error_reporting(E_ALL);

        set_error_handler(function ($severity, $message, $file, $line): void {
            if (!(error_reporting() & $severity)) {
                return;
            }
            throw new ErrorException($message, 0, $severity, $file, $line);
        });

        set_exception_handler(function (Throwable $exception): void {
            error_log(sprintf(
                "Uncaught Exception: %s\nStack trace: %s",
                $exception->getMessage(),
                $exception->getTraceAsString()
            ));
            exit(1);
        });
    }

    private static function loadEnvironment(): void
    {
        $envFile = __DIR__.'/../../.env';
        if (file_exists($envFile)) {
            Environment::load($envFile);
        }
    }

    private static function createContainer(): ContainerInterface
    {
        try {
            $builder = new ContainerBuilder();

            // Enable compilation for better performance in production
            if ('prod' === $_ENV['APP_ENV']) {
                $builder->enableCompilation(__DIR__.'/../../var/cache');
                $builder->writeProxiesToFile(true, __DIR__.'/../../var/cache/proxies');
            }

            // Load service definitions
            $builder->addDefinitions(__DIR__.'/../../config/services.php');

            // Add annotation reader if needed
            $builder->addDefinitions([
                'annotation_reader' => function () {
                    $annotationReader = new AnnotationReader();
                    $cache = new ArrayAdapter();
                    return new PsrCachedReader($annotationReader, $cache);
                },
            ]);

            return $builder->build();

        } catch (Throwable $e) {
            error_log(sprintf(
                "Container initialization failed: %s\nStack trace: %s",
                $e->getMessage(),
                $e->getTraceAsString()
            ));
            throw $e;
        }
    }

    public static function resetContainer(): void
    {
        self::$container = null;
    }
}
