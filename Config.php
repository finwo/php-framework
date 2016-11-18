<?php

namespace Finwo\Framework;

class Config
{
    /**
     * @var string
     */
    protected static $configDirectory = null;

    /**
     * Holds the already-loaded configurations
     * Prevents double-fetching a file
     *
     * @var array
     */
    protected static $cache = array();

    /**
     * @param string $directory
     */
    public static function init( $directory = null )
    {
        if (is_null($directory)) {
            $directory = __DIR__ . '/../../config/autoload';
        }
        self::$configDirectory = $directory;
    }

    /**
     * Gets a configuration value
     * Attempts to autoload it if the key is unknown
     *
     * @param string $key dot.seperated.key
     *
     * @return mixed
     */
    public static function get( $key )
    {
        // Easy if we already have it
        if (isset(self::$cache[$key])) {
            return self::$cache[$key];
        }

        // Build which files to try
        // Specificity wins
        $parts        = explode('.',$key);
        $compositeKey = '';
        foreach ($parts as $part) {
            $compositeKey .= $part . '.';
            self::loadFile( implode(DIRECTORY_SEPARATOR, array(self::$configDirectory, $compositeKey . 'global.php')), $compositeKey );
            self::loadFile( implode(DIRECTORY_SEPARATOR, array(self::$configDirectory, $compositeKey . 'local.php')),  $compositeKey );
        }

        // Let's try again
        if (isset(self::$cache[$key])) {
            return self::$cache[$key];
        }

        // Nope, sorry
        return null;
    }

    /**
     * Flattens an array with dot-seperated-keys
     *
     * @param        $input
     * @param string $prepend
     *
     * @return array
     */
    protected static function flattenArray($input, $prepend = '')
    {
        $output = array();
        foreach ($input as $key => $value) {
            switch (gettype($value)) {
                case 'array':
                    $thisValue = self::flattenArray($value, sprintf("%s%s.", $prepend, $key));
                    $output = array_merge($output, $thisValue);
                    break;
                default:
                    $output[$prepend.$key] = $value;
                    break;
            }
        }
        return $output;
    }

    public static function loadFile( $filename, $keyPrepend = '' )
    {
        // It should exist, no?
        if (!file_exists($filename)) {
            return;
        }

        // Load it
        self::setArray(include($filename), $keyPrepend);
    }

    public static function setArray( array $input, $keyPrepend = '' )
    {
        foreach ($input as $key => $value) {
            switch (gettype($value)) {
                case 'array':
                    self::setArray($value, $keyPrepend.$key.'.');
                    break;
                default:
                    self::set($keyPrepend.$key, $value);
                    break;
            }
        }
    }

    /**
     * Sets a configuration key for the current request (non-persistent)
     *
     * @param string|array $key
     * @param mixed        $value
     */
    public static function set( $key, $value )
    {
        if (is_array($key)) {
            $key = implode('.', $key);
        }

        self::$cache[$key] = $value;
    }
}

Config::init();
