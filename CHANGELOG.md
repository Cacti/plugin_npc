## Changelog

--- develop ---

* issue: Treat a missing searchFields value as empty in the grid search; a search
  string without it raised the PHP 8.1 null deprecation
* issue: Return an empty definition for an unknown Nagios command instead of raising
  undefined index and foreach warnings before the not a valid command message
* issue: Log a fixed message when the host import selection is not a JSON object;
  the old message converted an array to a string
* issue: Cast the date format, time format and Nagios URL settings to strings in the
  NPC frame; unsaved settings raised the PHP 8.1 null deprecation on every page
* dev: Run the schema and the listing, perfdata and upgrade queries against MariaDB
  in the Pest suite, and run npc.php, perfdata.php and cli.php as subprocesses
  (tests/E2E); the JavaScript front end is tested with Node's test runner
* issue: Write the LIMIT values of the host, service, comment, downtime, event log,
  notification and state history listings into the statement as integers;
  Cacti binds values as strings and the databases reject a quoted LIMIT, so these
  listings failed with an SQL syntax error
* issue: Send the values Acknowledge, Add Comment, Schedule Downtime and Schedule
  Check need from the host and service command dialogs; they were rejected for
  missing parameters. Sticky, notify and persistent are options in the dialog and
  a downtime takes a duration in minutes
* security: Escape quotes in values the front end places inside HTML attributes
  (host and service names, user name)
* issue: Show the host, service, comment and event log grids and the summary and
  process tables; the front end read a response shape the controllers do not
  send, so every table rendered empty
* issue: Document the NPC Global Commands realm that gates remote commands
* issue: Add the minimum_importance column to npc_contacts on upgrade (the upgrade
  named a table that does not exist) and remove the settings of deleted users
  (the cleanup read a column the query does not return)
* issue: Skip perfdata entries without a numeric value (label=U) in perfdata.php and
  cli.php instead of printing PHP warnings into the data input output; both
  scripts now share one parser in the base controller
* issue: Declare the hostgroup and unhandled request properties on the base
  controller; assigning them raised the PHP 8.2 dynamic property deprecation
* issue: List each service once; the service grid joined the hostgroup members
  unconditionally, so a host in several hostgroups repeated its services and
  inflated the row count
* issue: Read only the newest host check when fetching host perfdata; the query
  returned every stored check and the oldest one was used
* issue: Name the failing class and method in the generic error returned by the
  controller logger; the message was built from an invalid property read
* issue: Remove dispatcher actions that cannot work with the single parameter array
  they are called with (getSetting, isMapped, mapHost, getSettings, checkHostExists)
* issue: Pass the searchFields and data request values to the controllers as
  JSON; the search string sanitizer removed their quotes, so grid searches and the
  host import selection never applied; the selection lookup also called a
  non-static method statically
* security: Copy only the known request parameters onto the NPC controllers and keep
  `start` and `limit` within bounds (at most 1000 rows per request)
* security: Accept the state-changing NPC actions (Nagios commands, comment
  deletion, data input creation, host import, graph mapping, settings save) only
  as POST requests so Cacti's CSRF token check applies to them
* issue: Load the base controller class in npc.php; every NPC page failed with
  a missing Controller class before
* security: Check the NPC command realm before the command file settings so users
  without the realm no longer see the configured command file path
* issue: Call the Nagios controller on an instance when deleting all host or service
  comments; the static call raised an Error on PHP 8
* security: Accept only known tab names for the `tab` request variable of the NPC
  frame; the raw value was written into an inline script block
* issue: Look up the stored perfdata with the plain object ID when creating a
  perfdata data input method; the shell-quoted value never matched, so no output
  fields were created
* security: Require the Devices realm for the Nagios host import and the Data Input
  Methods realm for creating a perfdata data input method; both were open to any
  user with NPC access
* security: Keep the host import address cache as JSON in the Cacti cache directory
  instead of a PHP file in the system temp directory that was included on each call
* security: Quote the description, address and template arguments of the Nagios host
  import command and reject non-numeric template IDs
* dev: Relocate the schema provisioning function `npc_setup_tables()` from
  `setup.php` into `includes/database.php` (loaded via `require_once`) to align
  with the fleet schema/includes convention; behavior is unchanged
* dev: Add a root `manifest.json` file manifest with upgrade-time pruning
  (`npc_prune_files()`) that removes tombstoned and dev-only paths on
  version change, plus a CI `validate-manifest.php` drift check
* dev: Measure CI coverage with xdebug instead of pcov so the plugin's own sources are instrumented (pcov auto-scopes to the Composer root and skipped cacti/plugins/, leaving the patch-coverage gate with nothing to measure)
* dev: Enforce patch coverage of changed lines in CI and remove the inert COMPOSER_ROOT_VERSION env from the Pest step
* dev: Pin the CI database image and shared test dependencies, disable persisted checkout credentials, and limit workflow permissions and runtime
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
