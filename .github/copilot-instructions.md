# GitHub Copilot Instructions

## Priority Guidelines

When generating code for this repository:

1. **Version Compatibility**: This is a Cacti plugin (`npc`, version 3.1) targeting Cacti 1.2.1+. **The plugin README explicitly warns this is a fresh port of a 10-year-old Cacti plugin and should be treated as non-production quality until further migration testing is done** — be conservative and flag risky legacy patterns rather than assuming they are correct.
2. **Context Files**: Prioritize patterns and standards defined in this file (`.github/copilot-instructions.md`)
3. **Codebase Patterns**: When context files don't provide specific guidance, scan the codebase for established patterns
4. **Architectural Consistency**: Maintain plugin-based architecture extending Cacti core
5. **Code Quality**: Prioritize security, maintainability, and compatibility in all generated code

## Technology Stack

### Core Technologies
- **PHP**: Compatible with Cacti 1.2.x supported versions
- **Platform**: Cacti Plugin Architecture ("Nagios Plugin for Cacti" - full Nagios UI replacement)
- **Database**: MySQL/MariaDB (large Nagios-derived schema)
- **Frontend**: ExtJS-based UI components

### Key Dependencies
- Cacti core framework (`api_plugin_*`, `db_*`)
- `lib/` includes a `Command` class used to build/execute Nagios external commands

## Project Structure

```
npc/                       # Repository root (install to plugins/npc/ in Cacti)
├── controllers/             # Request/action controllers
├── css/ js/                   # Frontend assets (ExtJS-based UI)
├── lib/                          # Command class and supporting library code
├── locales/                        # Internationalization files
├── models/                           # Data model helpers
├── queries/                            # Data query definitions
├── cli.php                               # CLI entry point
├── config.php                              # Plugin-specific configuration
├── nagioscmd.php                             # Nagios external command interface
├── npc.php                                     # Main NPC UI entry point
├── perfdata.php                                  # Performance data ingestion
├── top_graph_header.php                            # Graph header integration
├── upgrade_schema.sql                                # Schema upgrade reference
├── INFO                                                # Plugin metadata (name, version, compat)
├── README.md
└── setup.php                                            # Plugin install/uninstall/upgrade hooks
```

## Naming Conventions

### Function Names
- **Plugin lifecycle/hook-registration functions** MUST be prefixed `plugin_npc_`: `plugin_npc_install()`, `plugin_npc_uninstall()`, `plugin_npc_version()`.
- **All other functions** MUST be prefixed `npc_`: `npc_config_arrays()`, `npc_show_tab()`, `npc_setup_tables()`.
- The `Command` class in `lib/` uses standard method naming (`getCommand()`, `setCommand()`, `execute()`) — follow existing class conventions rather than the procedural `npc_` prefix inside that class.
- Match the existing prefix used by the function you are editing; do not introduce a fourth naming scheme.

### Database Tables
All tables use the **unprefixed `npc_` family** (not `plugin_npc_`), mirroring the original Nagios schema — see `plugin_npc_uninstall()` for the full list (`npc_hosts`, `npc_hoststatus`, `npc_commands`, `npc_contacts`, `npc_hostgroups`, etc.). Preserve this naming; do not rename to `plugin_npc_*`.

## Code Style

### Indentation and Formatting
- **Tabs**: Use tabs (not spaces) for indentation throughout all PHP files.
- **Braces**: Opening brace on the same line for functions and control structures.
- **Spacing**: Space after control structure keywords (`if`, `foreach`, `while`).

### File Headers
PHP files in this plugin carry a Nagios-Plugin-for-Cacti specific header crediting the original author (Billy Gunn) in addition to the standard Cacti GPL v2 header block — preserve both attributions when editing existing files; use the standard Cacti GPL v2 header for new files.

## Security Standards

### External Command Execution
`nagioscmd.php` / the `Command` class build and submit external commands to Nagios. Any value that ends up in a command string MUST be validated/escaped; never interpolate raw request input into `setCommand()`/`execute()` arguments.

### SQL Query Security
Use prepared statements for anything involving variable input:

```php
// CORRECT
db_fetch_row_prepared('SELECT * FROM npc_hosts WHERE host_object_id = ?', array($id));

// WRONG - never do this with request-derived values
db_fetch_row("SELECT * FROM npc_hosts WHERE host_object_id = $id");
```

### Input Validation
Use `get_filter_request_var()` / `get_nfilter_request_var()` for request input; never read `$_GET`/`$_POST` directly.

`get_filter_request_var()` (and its `gfrv()` shorthand, where available) called with only the
`$name` argument (no regex/filter as the 2nd/3rd argument) already validates the value as numeric
and returns it as a **string** -- it does not return an int, and it halts execution if the request
value is not numeric. Because of this, do NOT cast its output to `(int)` when the result is only
used for string output (e.g. `print`/`echo`, string concatenation, embedding in HTML/JS); the cast
is redundant. Only cast when the value is genuinely used in an integer/numeric context (e.g.
arithmetic, strict `===` comparisons).

## Database Operations

### Upgrade Handling
Schema upgrades reference `upgrade_schema.sql` and are applied via `npc_upgrade_tables()`; keep new schema changes consistent with this file rather than only changing `npc_setup_tables()` (which defines the fresh-install schema).

## Internationalization

Wrap new user-facing strings in `__('text', 'npc')` where the surrounding code already does so; much of this legacy port predates consistent i18n coverage — do not assume every string is already wrapped.

## Plugin Architecture

### Plugin Hooks
Register all plugin hooks in `plugin_npc_install()` (`setup.php`):

```php
api_plugin_register_realm('npc', 'npc.php', 'NPC', 1);
api_plugin_register_realm('npc', 'npc1.php', 'NPC Global Commands', 1);

api_plugin_register_hook('npc', 'config_arrays', 'npc_config_arrays', 'setup.php');
api_plugin_register_hook('npc', 'top_header_tabs', 'npc_show_tab', 'setup.php');
api_plugin_register_hook('npc', 'top_graph_header_tabs', 'npc_show_tab', 'setup.php');
api_plugin_register_hook('npc', 'draw_navigation_text', 'npc_draw_navigation_text', 'setup.php');
api_plugin_register_hook('npc', 'config_form', 'npc_config_form', 'setup.php');
api_plugin_register_hook('npc', 'api_device_save', 'npc_api_device_save', 'setup.php');
api_plugin_register_hook('npc', 'config_settings', 'npc_config_settings', 'setup.php');
api_plugin_register_hook('npc', 'page_head', 'npc_page_head', 'setup.php');
```

## Best Practices

1. Treat existing code as legacy-quality; flag risky patterns (unescaped commands, missing validation) rather than propagating them into new code.
2. Never build Nagios external commands from unescaped/unvalidated input.
3. Preserve the unprefixed `npc_*` table naming.
4. Wrap new user-facing strings with `__('text', 'npc')`.

## Common Pitfalls to Avoid

```php
// WRONG - building an external command from raw request input
$cmd->setCommand($_GET['command'], $_GET['args']);

// CORRECT - validate against known command types first, then use filtered vars
$command = get_filter_request_var('command', FILTER_VALIDATE_REGEXP, array('options' => array('regexp' => '/^[A-Z_]+$/')));
$cmd->setCommand($command, $validated_args);
```

## Version Control

Document all changes in `CHANGELOG.md`; use descriptive commit messages referencing issue/PR numbers when applicable.

## CI & Dependency Baselines

- Do not commit a `composer.json` or `composer.lock` in this plugin's own repo root — the shared CI workflow installs Pest/dev dependencies into Cacti's own Composer-managed vendor tree (checked out alongside the plugin). Use Cacti's `composer.json`, not a plugin-local one.
- Do not add a plugin-local `.phpstan.neon`/`phpstan.neon` or `.php-cs-fixer.php`/`.php-cs-fixer.dist.php` — lint/static-analysis steps run against Cacti's own config from the Cacti core checkout, targeting this plugin's directory. Use the Cacti version, not a plugin-local config.
- Prefer Cacti's `cacti_count()`/`cacti_sizeof()` wrappers over the raw `count()`/`sizeof()` builtins in new or edited code.

## Internationalization (i18n)

- Translatable strings are managed with GNU gettext via `locales/build_gettext.sh`. `locales/po/cacti.pot` is the source template; Weblate owns syncing the per-language `.po`/`.mo` files from it.
- When a pull request adds or changes a string wrapped in `__()`/`__n()`/`__esc()`/`__x()`/`__xn()`/`__gettext()`, run `locales/build_gettext.sh` before pushing and add the resulting change to `locales/po/cacti.pot` only. Do not commit the regenerated per-language `.po`/`.mo` files in the same PR — Weblate takes care of the rest.

## References

- [Cacti main repo](https://github.com/Cacti/cacti/tree/1.2.x)
- [Cacti Documentation](https://www.github.com/Cacti/documentation)
- `README.md` for feature descriptions
- `QandA.md` for known issues / migration notes

## Security & Quality Conventions

These conventions apply across the Cacti plugin fleet and should be followed whenever touching
existing code or adding new code, not just in dedicated cleanup passes:

- **No hardcoded third-party hosts.** Never hardcode a third-party IP address, hostname, or URL
  in plugin code (even for tooling/download helpers). Expose it as a plugin setting instead, with
  secure-by-default values (e.g. an SSL-verification setting that defaults to verify-on).
- **Prepared statements over `db_qstr()`.** Build dynamic `WHERE` clauses using the
  `$sql_where`/`$sql_params` prepared-statement pattern, not string concatenation via `db_qstr()`.
- **Use `html_escape_request_var()`.** Prefer it over the `html_escape(get_request_var(...))` call
  chain.
- **Harden `unserialize()`.** Always pass `['allowed_classes' => false]` as the second argument.
- **i18n text domain.** Every `__()`/`__esc()` call must include this plugin's text domain as the
  final argument, except when deliberately comparing against a literal, untranslated Cacti-core
  label.
- **Plugin table-creation API.** Use `api_plugin_db_table_create()`/`api_plugin_db_add_column()`
  (from Cacti core's `lib/plugins.php`) instead of raw `CREATE TABLE`/`ALTER TABLE ... ADD COLUMN`.
  Both are idempotent (safe no-ops when already applied), so the same call can run unconditionally
  from both the install AND upgrade paths.
- **PHPDoc shape.** Every function gets a PHPDoc block: a one-line description, a blank comment
  line, `@param` lines, a blank comment line, then `@return`. Infer parameter/return types from
  actual usage; don't change the function's real type-hints in the same pass (let static analysis
  flag mismatches separately). Skip vendored third-party library files.
