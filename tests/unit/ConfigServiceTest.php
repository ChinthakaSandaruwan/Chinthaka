<?php

/**
 * Configuration Service Test
 * Unit tests for ConfigService class
 */

require_once __DIR__ . '/../../src/services/ConfigService.php';

class ConfigServiceTest
{
    public function testGetConfigValue()
    {
        // Test getting a configuration value
        $appName = ConfigService::get('app_name');
        assert($appName === 'RentFinder SL', 'App name should be RentFinder SL');

        echo "✓ testGetConfigValue passed\n";
    }

    public function testGetNestedConfigValue()
    {
        // Test getting nested configuration value
        $dbHost = ConfigService::get('database.host');
        assert($dbHost === 'localhost', 'Database host should be localhost');

        echo "✓ testGetNestedConfigValue passed\n";
    }

    public function testSetConfigValue()
    {
        // Test setting a configuration value
        ConfigService::set('test.value', 'test123');
        $value = ConfigService::get('test.value');
        assert($value === 'test123', 'Set value should be test123');

        echo "✓ testSetConfigValue passed\n";
    }

    public function testHasConfigKey()
    {
        // Test checking if configuration key exists
        $hasAppName = ConfigService::has('app_name');
        assert($hasAppName === true, 'app_name should exist');

        $hasNonExistent = ConfigService::has('non.existent.key');
        assert($hasNonExistent === false, 'non-existent key should not exist');

        echo "✓ testHasConfigKey passed\n";
    }

    public function testGetDefaultValue()
    {
        // Test getting default value for non-existent key
        $defaultValue = ConfigService::get('non.existent.key', 'default');
        assert($defaultValue === 'default', 'Should return default value');

        echo "✓ testGetDefaultValue passed\n";
    }

    public function runAllTests()
    {
        echo "Running ConfigService Tests...\n";
        echo "================================\n";

        try {
            $this->testGetConfigValue();
            $this->testGetNestedConfigValue();
            $this->testSetConfigValue();
            $this->testHasConfigKey();
            $this->testGetDefaultValue();

            echo "================================\n";
            echo "All tests passed! ✓\n";
        } catch (Exception $e) {
            echo "Test failed: " . $e->getMessage() . "\n";
        }
    }
}

// Run tests if called directly
if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    $test = new ConfigServiceTest();
    $test->runAllTests();
}
