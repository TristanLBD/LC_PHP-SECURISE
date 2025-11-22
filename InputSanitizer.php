<?php
// InputSanitizer.php

class InputSanitizer {

    public static function e($val) {
        return htmlspecialchars($val);
    }

    public static function PostIssetEscape($PostValueToSearch) {
        return isset($_POST[$PostValueToSearch]) ? self::e($_POST[$PostValueToSearch]) : '';
    }

    public static function get(string $key, int $filter, bool $canBeNull = false) {
        $raw = $_POST[$key] ?? null;
        if (is_string($raw)) {
            $raw = str_replace(',', '.', $raw);
        }
        $val = filter_var($raw, $filter);

        if (!$canBeNull && ($val === false || $val === null)) {
            return null;
        }

        return $val;
    }
}
