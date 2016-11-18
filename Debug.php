<?php

namespace Finwo\Framework;

class Debug
{
    protected static $output = null;

    /**
     * Overrules any output you may have given
     *
     * params: same as 'sprintf'
     */
    public static function printf()
    {
        if (is_null(self::$output)) {
            header('Content-Type: text/plain; charset=utf-8');
            ob_start(function($buffer) {
                return self::$output;
            });
            $started = true;
        }
        self::$output .= call_user_func_array("\\sprintf", func_get_args());
    }

    /**
     * Returns string lengths
     *
     * @param array $values
     *
     * @return array
     */
    public static function lengths(array $values = array())
    {
        return array_map(function($value) {
            return strlen($value);
        }, $values);
    }

    /**
     * Returns the highest number in the array
     *
     * @param array $values
     *
     * @return mixed
     */
    public static function max(array $values = array())
    {
        $output = array_shift($values);
        while ($value = array_shift($values)) {
            if ($value>$output) $output = $value;
        }
        return $output;
    }

    public static function dump($subject, $indent = '')
    {
        $type = gettype($subject);
        self::printf($type);
        switch ($type) {
            case 'object':
                self::printf(" " . get_class($subject));
                $subject = (array)$subject;
            case 'array':
                self::printf(" (%d)\n", count($subject));
                $length = self::max(self::lengths(array_keys($subject))) + 1;
                $last = @array_pop(array_keys($subject));
                foreach ($subject as $key => $value) {
                    self::printf("%s%s %s %s => ", $indent, $key == $last ? ' └─' : ' ├─', $key, str_repeat('_', $length - strlen($key)));
                    self::dump($value, $indent . ($key == $last ? '    ' : ' │  '));
                }
                break;
            case 'string':
                self::printf(" (%d)", strlen($subject));
                $subject = str_replace("\\", "\\\\", $subject);
                $subject = str_replace("\n", "\\n", $subject);
                $subject = str_replace("\r", "\\r", $subject);
                $subject = str_replace("\t", "\\t", $subject);
                $subject = str_replace("\t", "\\t", $subject);
                $subject = sprintf('"%s"', str_replace('"', '\\"', $subject));
            case 'double':
            case 'float':
            case 'integer':
                self::printf(" %s\n", $subject);
                break;
            case 'boolean':
                self::printf(" %s\n", $subject ? 'true' : 'false');
                break;
            case 'NULL':
                self::printf("\n");
                break;
        }
    }

    static function textTable($data)
    {
        $edges = array(
            "top-left"     => "┌─",
            "top"          => "─┬─",
            "top-right"    => "─┐",
            "right"        => "─┤",
            "bottom-right" => "─┘",
            "bottom"       => "─┴─",
            "bottom-left"  => "└─",
            "left"         => "├─",
            "horizontal"   => "─",
            "vertical"     => " │ ",
            "middle"       => "─┼─",
        );

        $column = array();

        // Fetch columns & their max length
        foreach ($data as &$row) {
            foreach ($row as $key => &$value) {
                if (!isset($column[$key])) $column[$key] = max(4,strlen($key));
                if (is_array($value)) $value = implode(',',array_values($value));
                $column[$key] = max($column[$key], strlen($value));
            }
        }

        // Print header
        self::printf($edges['top-left']);
        self::printf(implode($edges['top'], array_map(function($length) use ($edges) {
            return str_repeat($edges['horizontal'], $length);
        },$column)));
        self::printf($edges['top-right']);
        self::printf("\n");
        self::printf(ltrim($edges['vertical']) . implode($edges['vertical'],array_map(function($length, $key) use ($edges) {
                return $key . str_repeat(' ', $length-strlen($key));
            }, $column, array_keys($column))) . rtrim($edges['vertical']));
        self::printf("\n");

        // Print rows
        foreach ($data as $row) {
            // Seperator
            self::printf($edges['left']);
            self::printf(implode($edges['middle'], array_map(function($length) use ($edges) {
                return str_repeat($edges['horizontal'], $length);
            },$column)));
            self::printf($edges['right']);
            self::printf("\n");

            // Data
            self::printf(ltrim($edges['vertical']));
            self::printf(implode($edges['vertical'], array_map(function($length, $key) use ($row) {
                $data = isset($row[$key]) ? $row[$key] : 'NULL';
                return $data . str_repeat(' ', $length-strlen($data));
            },$column,array_keys($column))));
            self::printf(rtrim($edges['vertical']));
            self::printf("\n");
        }

        // Closing
        self::printf($edges['bottom-left']);
        self::printf(implode($edges['bottom'], array_map(function($length) use ($edges) {
            return str_repeat($edges['horizontal'], $length);
        },$column)));
        self::printf($edges['bottom-right']);
        self::printf("\n");
    }
}
