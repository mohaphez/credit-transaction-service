<?php

declare(strict_types=1);

namespace CreditTransactionService\Infrastructure\Config;

use Symfony\Component\Dotenv\Dotenv;
use RuntimeException;

class Environment
{
    private static bool $initialized = false;

    public static function load(): void
    {
        if (self::$initialized) {
            return;
        }

        $dotenv = new Dotenv();

        $envFile = __DIR__.'/../../../.env';
        if (file_exists($envFile)) {
            $dotenv->load($envFile);
        } else {

            $exampleEnvFile = __DIR__.'/../../../.env.example';
            if (file_exists($exampleEnvFile)) {
                $dotenv->load($exampleEnvFile);
            }
        }

        self::validateEnvironment();

        self::$initialized = true;
    }

    public static function get(string $key, $default = null)
    {
        if (!self::$initialized) {
            self::load();
        }

        return $_ENV[$key] ?? $_SERVER[$key] ?? $default;
    }

    public static function isProduction(): bool
    {
        return 'prod' === self::get('APP_ENV');
    }

    public static function isDevelopment(): bool
    {
        return 'dev' === self::get('APP_ENV');
    }

    public static function isDebug(): bool
    {
        return (bool) self::get('APP_DEBUG', false);
    }

    private static function validateEnvironment(): void
    {
        $required = [
            'APP_ENV',
            'DB_HOST',
            'DB_PORT',
            'DB_DATABASE',
            'DB_USERNAME',
            'DB_PASSWORD',
            'REDIS_HOST',
            'REDIS_PORT'
        ];

        $missing = [];
        foreach ($required as $var) {
            if (!isset($_ENV[$var]) && !isset($_SERVER[$var])) {
                $missing[] = $var;
            }
        }

        if (!empty($missing)) {
            throw new RuntimeException(sprintf(
                'Required environment variables are not set: %s',
                implode(', ', $missing)
            ));
        }
    }
}
