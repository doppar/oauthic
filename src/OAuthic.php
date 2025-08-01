<?php

namespace Doppar\OAuthic;

use Doppar\OAuthic\Contracts\ProviderInterface;
use Doppar\OAuthic\Drivers\AbstractProvider;
use Doppar\OAuthic\Exceptions\AuthException;

/**
 * Class OAuthic
 *
 * Main entry point for interacting with the OAuthic authentication system.
 * This class provides a singleton interface to configure OAuth providers,
 * manage custom drivers, and instantiate provider-specific authentication flows.
 *
 * @package Doppar\OAuthic
 */
class OAuthic
{
    /**
     * The singleton instance of OAuthic.
     *
     * @var OAuthic|null
     */
    private static ?OAuthic $instance = null;

    /**
     * Global configuration array for all drivers.
     *
     * @var array
     */
    private static array $config = [];

    /**
     * Array of custom driver class mappings.
     *
     * @var array
     */
    private static array $customDrivers = [];

    /**
     * The active driver instance.
     *
     * @var AbstractProvider|null
     */
    private ?AbstractProvider $driverInstance = null;

    /**
     * The currently selected driver name.
     *
     * @var string
     */
    private string $currentDriver;

    /**
     * Loads default configuration using a `config('oauthic')` helper.
     */
    private function __construct()
    {
        OAuthic::configure(config("oauthic"));
    }

    /**
     * Get the singleton instance of OAuthic.
     *
     * @return self
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Set the global configuration for all providers.
     *
     * @param array $config
     */
    public static function configure(array $config): void
    {
        self::$config = $config;
    }

    /**
     * Register a custom driver.
     *
     * @param string $driver The name of the driver.
     * @param string $class Fully-qualified class name of the driver implementation.
     */
    public static function extend(string $driver, string $class): void
    {
        self::$customDrivers[$driver] = $class;
    }

    /**
     * Get an instance of a provider driver.
     *
     * @param string $driver The name of the provider (e.g., "google", "github").
     * @return ProviderInterface
     *
     * @throws AuthException
     */
    public static function driver(string $driver): ProviderInterface
    {
        $instance = self::getInstance();
        $instance->currentDriver = $driver;
        $instance->driverInstance = $instance->createDriver($driver);

        return $instance->driverInstance;
    }

    /**
     * Create a driver instance (either custom or built-in).
     *
     * @param string $driver
     * @return AbstractProvider
     * @throws AuthException
     */
    private function createDriver(string $driver): AbstractProvider
    {
        $config = $this->getDriverConfig($driver);

        if (isset(self::$customDrivers[$driver])) {
            $class = self::$customDrivers[$driver];
            return new $class($config);
        }

        $class = "Doppar\\OAuthic\\Drivers\\" . ucfirst($driver) . "Provider";

        if (!class_exists($class)) {
            throw new AuthException("Driver [{$driver}] not supported.");
        }

        return new $class($config);
    }

    /**
     * Retrieve configuration for a specific driver.
     *
     * @param string $driver
     * @return array
     * @throws AuthException
     */
    private function getDriverConfig(string $driver): array
    {
        if (!isset(self::$config[$driver])) {
            throw new AuthException(
                "Configuration for driver [{$driver}] not found.",
            );
        }

        return self::$config[$driver];
    }

    /**
     * Dynamically handle static method calls.
     *
     * @param string $method
     * @param array $arguments
     * @return mixed
     */
    public static function __callStatic(string $method, array $arguments)
    {
        return self::getInstance()->$method(...$arguments);
    }
}
