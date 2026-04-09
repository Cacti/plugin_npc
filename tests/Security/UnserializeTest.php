<?php

declare(strict_types=1);

require_once __DIR__ . '/../Helpers/CactiStubs.php';

/**
 * Unserialize / object injection surface tests for plugin_npc.
 *
 * Finding NPC-UNSER-01: controllers/settings.php:43
 *   unserialize() called on DB-fetched blob without allowed_classes guard.
 *
 * Finding NPC-SQL-04 (chained): controllers/layoutDev.php:92
 *   SQLi-tainted row selection + unserialize = object injection.
 */

describe('NPC unserialize object injection', function (): void {
    it('verifies settings.php uses allowed_classes => false (NPC-UNSER-01 fix)', function (): void {
        $source = file_get_contents(__DIR__ . '/../../controllers/settings.php');

        // Old pattern: unserialize($obj->settings) with no guard
        expect($source)->not->toMatch('/unserialize\(\$obj->settings\)\s*;/');

        // Must contain the allowed_classes guard
        expect($source)->toContain("'allowed_classes' => false");
    });

    it('verifies layoutDev.php uses allowed_classes => false (NPC-SQL-04 fix)', function (): void {
        $source = file_get_contents(__DIR__ . '/../../controllers/layoutDev.php');

        expect($source)->toContain("'allowed_classes' => false");
    });

    it('verifies layout.php uses allowed_classes => false', function (): void {
        $source = file_get_contents(__DIR__ . '/../../controllers/layout.php');

        expect($source)->toContain("'allowed_classes' => false");
    });

    it('demonstrates allowed_classes => false prevents object instantiation', function (): void {
        $payload = 'O:8:"stdClass":1:{s:1:"x";s:5:"value";}';

        $unsafe = @unserialize($payload);
        expect($unsafe)->toBeObject();
        expect($unsafe)->toBeInstanceOf(\stdClass::class);

        $safe = @unserialize($payload, ['allowed_classes' => false]);
        expect($safe)->toBeInstanceOf(\__PHP_Incomplete_Class::class);
    });

    it('allowed_classes => false still permits arrays and scalars', function (): void {
        $arrayPayload = 'a:2:{s:3:"foo";s:3:"bar";s:3:"baz";i:42;}';
        $result = unserialize($arrayPayload, ['allowed_classes' => false]);

        expect($result)->toBeArray();
        expect($result['foo'])->toBe('bar');
        expect($result['baz'])->toBe(42);
    });

    it('allowed_classes => false blocks nested object payloads', function (): void {
        // Simulates a gadget chain: object containing another object
        $inner = serialize(new \stdClass());
        $outer = 'a:1:{s:3:"obj";' . $inner . '}';

        $result = @unserialize($outer, ['allowed_classes' => false]);

        expect($result)->toBeArray();
        expect($result['obj'])->toBeInstanceOf(\__PHP_Incomplete_Class::class);
    });
});
