<?php

declare(strict_types=1);


/**
 * Cacti function stubs for testing plugin_npc in isolation.
 *
 * Each stub uses if(!function_exists()) guards so Cacti's real
 * functions take precedence when the full bootstrap is available.
 */

if (!function_exists('db_execute')) {
    function db_execute(string $sql, bool $log = true): bool
    {
        return true;
    }
}

if (!function_exists('db_execute_prepared')) {
    function db_execute_prepared(string $sql, array $params = [], bool $log = true): bool
    {
        return true;
    }
}

if (!function_exists('db_fetch_assoc')) {
    function db_fetch_assoc(string $sql, bool $log = true): array
    {
        return [];
    }
}

if (!function_exists('db_fetch_assoc_prepared')) {
    function db_fetch_assoc_prepared(string $sql, array $params = [], bool $log = true): array
    {
        return [];
    }
}

if (!function_exists('db_fetch_cell')) {
    function db_fetch_cell(string $sql, string $col_name = '', bool $log = true): string|false
    {
        return false;
    }
}

if (!function_exists('db_fetch_cell_prepared')) {
    function db_fetch_cell_prepared(string $sql, array $params = [], string $col_name = '', bool $log = true): string|false
    {
        return false;
    }
}

if (!function_exists('db_fetch_row')) {
    function db_fetch_row(string $sql, bool $log = true): array|false
    {
        return false;
    }
}

if (!function_exists('db_fetch_row_prepared')) {
    function db_fetch_row_prepared(string $sql, array $params = [], bool $log = true): array|false
    {
        return false;
    }
}

if (!function_exists('html_escape')) {
    function html_escape(mixed $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('get_filter_request_var')) {
    function get_filter_request_var(string $name, int $filter = FILTER_DEFAULT, mixed $options = null): mixed
    {
        return filter_input(INPUT_GET, $name, $filter, $options)
            ?? filter_input(INPUT_POST, $name, $filter, $options);
    }
}

if (!function_exists('get_nfilter_request_var')) {
    function get_nfilter_request_var(string $name): string
    {
        return $_REQUEST[$name] ?? '';
    }
}

if (!function_exists('sanitize_search_string')) {
    function sanitize_search_string(string $string): string
    {
        return htmlspecialchars(strip_tags($string), ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('db_qstr')) {
    function db_qstr(string $string): string
    {
        return "'" . addslashes($string) . "'";
    }
}

if (!function_exists('cacti_log')) {
    function cacti_log(string $message, bool $output = false, string $environ = 'CACTI', int $level = 0): void
    {
        // no-op in test context
    }
}

if (!function_exists('cacti_sizeof')) {
    function cacti_sizeof(mixed $value): int
    {
        return is_countable($value) ? count($value) : 0;
    }
}

if (!function_exists('cacti_count')) {
    function cacti_count(mixed $value): int
    {
        return is_countable($value) ? count($value) : 0;
    }
}

if (!function_exists('read_config_option')) {
    function read_config_option(string $key, bool $force = false): string
    {
        return '';
    }
}

if (!function_exists('api_plugin_user_realm_auth')) {
    function api_plugin_user_realm_auth(string $realm): bool
    {
        return false;
    }
}

if (!function_exists('cacti_escapeshellarg')) {
    function cacti_escapeshellarg(string $arg): string
    {
        return escapeshellarg($arg);
    }
}

if (!function_exists('raise_message')) {
    function raise_message(string $id, string $message = '', int $level = 0): void
    {
        // no-op in test context
    }
}

if (!function_exists('__')) {
    function __(string $text, ...$args): string
    {
        return $text;
    }
}
