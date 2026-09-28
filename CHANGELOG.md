## Changelog

--- develop ---

* security: Add a version-safe CSP nonce (`plugin_npc_csp_nonce()`) to every inline `<script>` tag so pages stay compatible with Cacti's Content-Security-Policy nonce enforcement, while falling back cleanly on older Cacti releases that lack the `CactiSecureHeaders` class
* issue: Fix outdated Security Pest tests that failed CI after the Cacti-native
  controller refactor (front-controller auth model, graph passthrough, PHP 7.4
  named-argument tokenizer check, prepared-statement allowlist, redirect stubs)
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
