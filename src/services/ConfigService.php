<?php

/**
 * Configuration Service
 * Manages application configuration and environment settings
 */

class ConfigService
{
    private static $config = null;
    private static $environment = null;

    /**
     * Get configuration value
     */
    public static function get($key, $default = null)
    {
        $config = self::loadConfig();
        return self::getNestedValue($config, $key, $default);
    }

    /**
     * Set configuration value
     */
    public static function set($key, $value)
    {
        $config = self::loadConfig();
        self::setNestedValue($config, $key, $value);
        self::$config = $config;
    }

    /**
     * Load configuration
     */
    private static function loadConfig()
    {
        if (self::$config === null) {
            $environment = self::getEnvironment();
            $baseConfig = require_once __DIR__ . '/../../config/app.php';

            if ($environment !== 'default') {
                $envConfigFile = __DIR__ . '/../../config/environments/' . $environment . '.php';
                if (file_exists($envConfigFile)) {
                    $envConfig = require_once $envConfigFile;
                    $baseConfig = array_merge($baseConfig, $envConfig);
                }
            }

            self::$config = $baseConfig;
        }

        return self::$config;
    }

    /**
     * Get current environment
     */
    private static function getEnvironment()
    {
        if (self::$environment === null) {
            self::$environment = $_ENV['APP_ENV'] ?? 'development';
        }
        return self::$environment;
    }

    /**
     * Get nested configuration value
     */
    private static function getNestedValue($array, $key, $default = null)
    {
        $keys = explode('.', $key);
        $value = $array;

        foreach ($keys as $k) {
            if (is_array($value) && array_key_exists($k, $value)) {
                $value = $value[$k];
            } else {
                return $default;
            }
        }

        return $value;
    }

    /**
     * Set nested configuration value
     */
    private static function setNestedValue(&$array, $key, $value)
    {
        $keys = explode('.', $key);
        $current = &$array;

        foreach ($keys as $k) {
            if (!isset($current[$k]) || !is_array($current[$k])) {
                $current[$k] = [];
            }
            $current = &$current[$k];
        }

        $current = $value;
    }

    /**
     * Get all configuration
     */
    public static function all()
    {
        return self::loadConfig();
    }

    /**
     * Check if configuration key exists
     */
    public static function has($key)
    {
        $config = self::loadConfig();
        return self::getNestedValue($config, $key) !== null;
    }
}
