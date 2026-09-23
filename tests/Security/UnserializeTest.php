<?php

declare(strict_types=1);

/**
 * Unserialize / object injection surface tests for plugin_npc.
 *
 * Finding NPC-UNSER-01: controllers/settings.php:43
 *   unserialize() called on DB-fetched blob without allowed_classes guard.
 *
 * NPC-SQL-04's original sink, controllers/layoutDev.php, was removed along
 * with the rest of the legacy ExtJS/Doctrine code; layout.php no longer
 * unserializes state itself (settings.php's save() is the only remaining
 * unserialize() call, covered by NPC-UNSER-01 above).
 */

describe('NPC unserialize object injection', function (): void {
    it('verifies settings.php uses allowed_classes => false (NPC-UNSER-01 fix)', function (): void {
        $source = file_get_contents(__DIR__ . '/../../controllers/settings.php');

        // Old pattern: unserialize($obj->settings) with no guard
        expect($source)->not->toMatch('/unserialize\(\$obj->settings\)\s*;/');

        // Must contain the allowed_classes guard
        expect($source)->toMatch('/[\'"]allowed_classes[\'"]\s*=>\s*false/');
    });

    it('layoutDev.php no longer exists (removed with ExtJS/Doctrine)', function (): void {
        expect(realpath(__DIR__ . '/../../controllers/layoutDev.php'))->toBeFalse();
    });

    it('verifies layout.php does not unserialize state directly', function (): void {
        $source = file_get_contents(__DIR__ . '/../../controllers/layout.php');

        expect($source)->not->toContain('unserialize(');
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
