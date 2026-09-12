<?php

declare(strict_types=1);

require_once __DIR__ . '/../Helpers/CactiStubs.php';

/**
 * XSS surface tests for plugin_npc.
 *
 * Finding NPC-XSS-01: top_graph_header.php:95
 *   $_REQUEST["action"] is used without get_filter_request_var guard.
 *
 * Finding NPC-XSS-02: top_graph_header.php:115
 *   Username from DB is printed without htmlspecialchars escaping.
 */

describe('NPC XSS output escaping', function (): void {
    it('html_escape neutralises script injection payload', function (): void {
        $payload = '<script>alert(document.cookie)</script>';
        $escaped = htmlspecialchars($payload, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        expect($escaped)->not->toContain('<script>');
        expect($escaped)->toContain('&lt;script&gt;');
    });

    it('html_escape neutralises attribute-context injection', function (): void {
        $payload = '" onmouseover="alert(1)';
        $escaped = htmlspecialchars($payload, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        expect($escaped)->not->toContain('"');
        expect($escaped)->toContain('&quot;');
    });

    it('verifies top_graph_header.php escapes username output (NPC-XSS-02 fix)', function (): void {
        $source = file_get_contents(__DIR__ . '/../../top_graph_header.php');

        // The username print must use htmlspecialchars
        expect($source)->toContain('htmlspecialchars(db_fetch_cell_prepared("SELECT username FROM user_auth');
    });

    it('verifies layout.php uses json_encode for JS context hardening', function (): void {
        $source = file_get_contents(__DIR__ . '/../../controllers/layout.php');

        expect($source)->toContain('npc.params.userName            = <?php echo json_encode(db_fetch_cell_prepared');
        expect($source)->toContain('npc.params.cacti_path          = <?php echo json_encode(URL_PATH); ?>;');
    });

    it('allow-list comparison blocks XSS in action parameter', function (): void {
        $allowedActions = ['tree', 'list', 'preview'];
        $input          = 'tree<script>';

        $safe = in_array($input, $allowedActions, true) ? $input : '';
        expect($safe)->toBe('');
    });

    it('allow-list comparison passes valid action values', function (): void {
        $allowedActions = ['tree', 'list', 'preview'];

        foreach ($allowedActions as $action) {
            $result = in_array($action, $allowedActions, true) ? $action : '';
            expect($result)->toBe($action);
        }
    });
});
