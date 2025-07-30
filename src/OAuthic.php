<?php

namespace Doppar\OAuthic;

use Doppar\OAuthic\Contracts\ProviderInterface;
use Doppar\OAuthic\Drivers\AbstractProvider;
use Doppar\OAuthic\Exceptions\AuthException;

class OAuthic
{
    private static ?OAuthic $instance = null;
    private static array $config = [];
    private static array $customDrivers = [];

    private ?AbstractProvider $driverInstance = null;
    private string $currentDriver;

    /**
     * Singleton instance
     */
    private function __construct()
    {
        OAuthic::configure(config('oauthic'));
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Configure global options for OAuthic
     */
    public static function configure(array $config): void
    {
        self::$config = $config;
    }

    /**
     * Extend OAuthic with custom drivers
     */
    public static function extend(string $driver, string $class): void
    {
        self::$customDrivers[$driver] = $class;
    }

    /**
     * Get a driver instance
     */
    public static function driver(string $driver): ProviderInterface
    {
        $instance = self::getInstance();
        $instance->currentDriver = $driver;
        $instance->driverInstance = $instance->createDriver($driver);

        return $instance->driverInstance;
    }

    /**
     * Create driver instance
     */
    private function createDriver(string $driver): AbstractProvider
    {
        $config = $this->getDriverConfig($driver);

        if (isset(self::$customDrivers[$driver])) {
            $class = self::$customDrivers[$driver];
            return new $class($config);
        }

        $class = 'Doppar\\OAuthic\\Drivers\\' . ucfirst($driver) . 'Provider';

        if (!class_exists($class)) {
            throw new AuthException("Driver [{$driver}] not supported.");
        }

        return new $class($config);
    }

    /**
     * Get driver configuration
     */
    private function getDriverConfig(string $driver): array
    {
        if (!isset(self::$config[$driver])) {
            throw new AuthException("Configuration for driver [{$driver}] not found.");
        }

        return self::$config[$driver];
    }

    /**
     * Handle static method calls
     */
    public static function __callStatic(string $method, array $arguments)
    {
        return self::getInstance()->$method(...$arguments);
    }
}
