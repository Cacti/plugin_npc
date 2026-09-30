## Changelog

--- develop ---

* dev: Relocate the schema provisioning function `npc_setup_tables()` from
  `setup.php` into `includes/database.php` (loaded via `require_once`) to align
  with the fleet schema/includes convention; behavior is unchanged
* dev: Add a root `manifest.json` file manifest with upgrade-time pruning
  (`plugin_npc_prune_files()`) that removes tombstoned and dev-only paths on
  version change, plus a CI `validate-manifest.php` drift check
* dev: Measure CI coverage with xdebug instead of pcov so the plugin's own sources are instrumented (pcov auto-scopes to the Composer root and skipped cacti/plugins/, leaving the patch-coverage gate with nothing to measure)
* dev: Enforce patch coverage of changed lines in CI and remove the inert COMPOSER_ROOT_VERSION env from the Pest step
* security: Add a version-safe CSP nonce (`plugin_npc_csp_nonce()`) to every inline `<script>` tag so pages stay compatible with Cacti's Content-Security-Policy nonce enforcement, while falling back cleanly on older Cacti releases that lack the `CactiSecureHeaders` class
* issue: Fix outdated Security Pest tests that failed CI after the Cacti-native
  controller refactor (front-controller auth model, graph passthrough,
  prepared-statement allowlist, redirect stubs)
* issue: Harmonize the PHP-compatibility tests with the plugin fleet - remove
  `tests/Security/Php74CompatibilityTest.php` (PHP 7.4-floor forward-compat
  check) and align `tests/Security/PhpCompatibilityTest.php` with the fleet
  canonical (drop the vestigial `lib/Doctrine/` exclusion), so npc uses the
  shared PHP 8.2 floor and the single standard compat test
* security: Convert the remaining static raw `db_fetch_assoc()` calls in the
  hostgroups/hosts/services CLI helpers to `db_fetch_assoc_prepared()`, and add
  an explicit `exit` after the `Location` redirect in the directory-index stubs
* issue: Add Security & Quality Conventions section to .github/copilot-instructions.md
* issue#13: Nagios sync partially to NPC plugin - only can see update of
  hostgroup

--- 3.1 ---

* issue: Adding some missing columns to a few tables
* issue: Undefined variables in controllers settings.php


--- 3.0 ---

* feature: compatibility improvements for cacti 1.2.x

-----------------------------------------------
Copyright (c) 2004-2026 - The Cacti Group, Inc.
