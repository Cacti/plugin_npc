# NPC Quick Start Guide

NPC is designed to be a complete web based UI replacement to Nagios while fully
integrating into Cacti using the Cacti Plugin Architecture. This integration
will provide a single point of access for trending and alert monitoring.

**IMPORTANT NOTE:** Since this is a fresh port of a 10 year old Cacti plugin,
you should be using it in a non-production setup until further migration testing
has been performed by the Cacti community.

## Status

**Known issue:** `npc.php` does not load the base controller class
(`controllers/controller.php`), so opening NPC from the console tab ends in a
fatal error (`Class "Controller" not found`). Cacti then disables the plugin
and it has to be enabled again under Console > Configuration > Plugins. The
settings tab, the device Nagios host mapping and the command line scripts do
not go through `npc.php` and are not affected.

The screens, settings and scripts described below are what the code provides.
Anything that was part of the original ExtJS plugin and is not listed here is
not implemented in this version.

## Features

1) An NPC tab in the console header with seven screens: Dashboard, Hosts,
   Services, Comments, Downtime, Event Log and Process Info.

2) The screens are built with jQuery on the Cacti theme. ExtJS is not used.

3) The data of a screen is requested as JSON. The Hosts, Services and Event Log
   screens refresh themselves every Portlet Refresh Rate seconds. Changing the
   tab loads a new page.

4) Host and service commands are written to the Nagios command file. The
   Acknowledge, Add Comment, Schedule Downtime and Schedule Check options of the
   command dialog do not send the values those commands need, and NPC rejects
   them with "Missing required parameter". Enable and Disable Notifications
   need nothing more than the host or service and are accepted.

5) A "Nagios Host Mapping" drop-down on the Cacti device page.

6) The command line scripts `perfdata.php` and `cli.php`, which print Nagios
   performance data in the form a Cacti data input method reads.

Importing Nagios hosts into Cacti (N2C) and per user screen layouts have server
side code but no screen, so neither can be used from the interface.

## Usage

With the exception of some installation notes documentation is very slim. Watch
the forums and the NPC GitHub for additional info. Here are a few tips to get
you started:

* Click a host or service name to open a dialog with its details.

* The gear icon at the end of a host or service row opens the command dialog.
  Commands need Remote Commands to be enabled in the settings and the NPC
  Global Commands realm for the user.

* The Hosts and Services screens have a state filter (any, up/ok, down/warning,
  unreachable/critical, unknown, problems).

* The Comments screen lists host and service comments. The cross at the end of
  a host comment deletes it after a confirmation.

* The Dashboard shows the host and service status counts and the host and
  service problems.

* Every list shows the first 25 rows. There are no paging controls.

**IMPORTANT NOTE:** Since this is a fresh port of a 10 year old Cacti plugin,
you should be using it in a non-production setup until further migration testing
has been performed by the Cacti community.

## Installation

The remainder of the document provides the steps that are required to install
and configure the NPC Plugin for Cacti.

## Requirements

Below are the minimum requirements:

- Cacti 1.2.1 or later in the 1.2 series (`compat` in the `INFO` file). The
  CI workflow tests against the Cacti 1.2.x branch only.
- PHP 8.2 or later. The CI workflow runs PHP 8.2, 8.3 and 8.4.
- MySQL or MariaDB, as required by Cacti.
- NDOUtils 1.4b7
- Nagios 3.x

The plugin needs no PHP extension beyond the ones Cacti itself needs. Importing
hosts from Nagios runs `cli/add_device.php` with `exec()`, which needs `php` in
the PATH of the web server user.

The NPC Plugin, first written for Cacti 0.8.7, has been updated to work with
Cacti 1.2.x.  However, testing has been very light.  We will need feedback from
users as to its usability with this Cacti version.

The default memory limit for PHP probably will not be enough to run NPC. Edit
/etc/php.ini and update the memory_limit option if you find times when NPC is
causing Cacti to run out of memory.

**IMPORTANT NOTE:** Since this is a fresh port of a 10 year old Cacti plugin,
you should be using it in a non-production setup until further migration testing
has been performed by the Cacti community.

## Installing NPC

Grab the latest version of NPC from GitHub using the following steps:

1. `cd <path_to_cacti>/plugins`

2. `git clone https://github.com/Cacti/plugin_npc.git npc`

   The directory has to be named `npc`.

3. Ensure permissions are set for the apache user to read

4. login to Cacti as an admin.

5. Install the NPC Plugin

   1. Go to Console > Configuration > Plugins

   2. Click the Install Plugin icon to the left of the 'Npc' plugin line. The
      plugin is listed as "Nagios Plugin for Cacti" with the status Installed.

   3. Next, click the Enable Plugin icon on the same line

   The same can be done from the command line:

   ```shell
   php cli/plugin_manage.php --plugin=npc --install --enable
   ```

6. Grant the realms under Console > Configuration > Users, on the Permissions
   tab of each user:

   * **NPC** - the NPC tab and all of its screens.

   * **NPC Global Commands** - sending commands to Nagios. The NPC realm alone
     is not enough.

7. You can find the NPC plugin options under Console > Configuration > Settings
   in the NPC tab.

Installing creates 62 tables with the prefix `npc_` in the Cacti database, one
column (`npc_host_object_id`) on the Cacti `host` table, the two realms, and the
settings `npc_date_format`, `npc_time_format` and `npc_log_level` with their
defaults.

Without NDO2DB feeding Nagios data into NPC the dashboard (and other screens)
will be empty.

Next, there are a few NPC settings to add/update. Goto Console > Configuration >
Settings > NPC.

* **Remote Commands** - Check the checkbox to enable remote commands (off by
  default). Users also need the NPC Global Commands realm to send commands.

* **Nagios Command File Path** - Add the path to the Nagios command file. It
  will be something like /usr/local/nagios/var/rw/nagios.cmd. The web server
  user has to be able to read and write the file.

* **Nagios URL** - The URL of the Nagios web interface. The value is stored and
  handed to the page, but no screen uses it in this version.

* **Date Format** and **Time Format** - Default Y-m-d and H:i. The choices are
  the nine date and six time formats in the drop-downs. They format the dates
  on the Process Info screen. The other screens show the dates as Nagios stored
  them.

* **Portlet Refresh Rate** - Seconds between refreshes of the Hosts, Services
  and Event Log screens, default 60. The text next to the field gives a minimum
  of 30 seconds, which is not enforced.

* **Host/Service Config Type** - 0 or 1, default 1. It selects the rows with
  that `config_type` in the host and service tables. If you can see host and
  service groups but not hosts or services try the other value.

* **Host Icons** and **Service Icons** - Stored, but no screen uses them in this
  version.

* **Logging Level** - None (default), Error, Warn, Info or Debug. NPC writes to
  the Cacti log (`log/cacti.log`) with the component NPC. Debug fills the log
  quickly.

## Upgrading and uninstalling

Replace the files (for example `git pull` in the plugin directory). When the
version in `INFO` differs from the version Cacti has stored, the next load of
Console > Configuration > Settings runs the schema upgrade, removes the files
that `manifest.json` lists as tombstones together with the `tests` directory
and `phpunit.xml`, resets the stored per user NPC settings and records the new
version.

Disable and Uninstall are the same Plugins page icons, or `--disable` and
`--uninstall` of `plugin_manage.php`. Uninstalling drops all `npc_` tables,
including the Nagios data collected in them, drops the `npc_host_object_id`
column, and removes the realms and the NPC settings.

## Command line scripts

Both scripts are run with `php` from any directory and read the Cacti
configuration. `perfdata.php` is the one a Cacti data input method runs, for
example `php <path_cacti>/plugins/npc/perfdata.php --type=service --id=<service
object id>`. It prints the numeric performance values of the newest stored
service check as `label:value` pairs separated by spaces, which is the form a
Cacti script data input method reads. A service can also be selected with
`--host=<host name> --service=<service description>`. For a host
(`--type=host`) the output comes from the first stored check row, not the
newest. An entry such as `load15=U` adds a stray ` : ` to the output.

Options of `perfdata.php`:

* `--type=host|service` and `--id=<object id>`, `--host=<name>`,
  `--service=<description>` select the object.

* `--list-hosts [--hostgroup=<alias>]`, `--list-hostgroups` and
  `--list-services` print the objects with their IDs.

* `--hostgroup` and `--ds` are accepted. `--ds` is not used.

`cli.php` takes the same options and adds `--perfdata` (the default),
`--perf-history` with `--host`, `--service`, `--begin=<date>` and `--end=<date>`,
which prints the end time and performance data of the checks in that range.
`--list-services` accepts `--host=<display name>`. The usage text of both
scripts names `--datasource` and does not list all options; the options above
are the ones the code reads.

## Configuring Nagios

Refer to the Nagios documentation for installation. Some parts of NPC like
reporting and the status map use the Nagios interface by wrapping the Nagios
CGI's in an Iframe. Be sure to have the Nagios web UI working if you want to use
the reporting and status map features.

The following parameters are found in the Nagios configuration file nagios.cfg

The power of NPC is greatly enhanced by the ability to issue commands to the
Nagios process. To enable external commands in Nagios set:

```console
check_external_commands=1
```

Check external commands as often as possible.

```console
command_check_interval=-1
```

Broker all events.

```console
event_broker_options=-1
```

The path to the event broker module and config file which you will install next.
This example assumes Nagios is installed in /usr/local/nagios. Adjust the path
to suit your installation.

```console
broker_module=/usr/local/nagios/bin/ndomod.o
config_file=/usr/local/nagios/etc/ndomod.cfg
```

If you want to use performance data from Nagios plugins to create graphs in
Cacti then set the following parameter.

```console
process_performance_data=1
```

Setting `host_perfdata_command` and `service_perfdata_command` is not necessary.
The performance data will be written to the NPC database where it can be polled
by cacti using the `perfdata.php` script included with NPC.

## Installing/Configuring NDO2DB

NOD2DB is part of the NDOUTILS package. Nagios hands events off to NDO2DB via
the event broker. NDO2DB handles the actual inserts of Nagios data into the NPC
tables.

Instructions for compiling, installing, and configuring NDO2DB are included in
the README file of the ndoutils package. The README has four sections. Skip the
section on initializing the database.

1. Follow the 'COMPILING INSTRUCTIONS' section of the README.

2. Skip the 'INITIALIZING THE SQL DATABASE' section of the README

3. Follow the 'INSTALLING THE NDOMOD BROKER MODULE' section of the README

4. Follow the 'INSTALLING THE NDO2DB DAEMON' section of the README

Edit `/usr/local/nagios/etc/ndo2db.cfg` and add/update the following parameters:

```console
db_servertype=mysql
db_host=localhost (the host/ip where cacti database is running)
db_port=3306
db_name=cacti (Your cacti database name)
db_prefix=npc_
db_user=<user> (Your cacti database user)
db_pass=<pass> (Your cacti user password)
```

The user/pass you assign needs select, insert, update, delete on all the npc_
tables.

ndo2db can communicates with the Nagios ndo2db.o module via unix socket or TCP.
Use whatever works for you (I use the TCP mode) but you need to set it the same
in both the ndo2db.cfg and ndomod.cfg.

**NOTE:** The config_output_options parameter in ndomod.cfg must be set to 2
(config_output_options=2).

Here are working ndo2db.cfg and ndomod.cfg configs. As noted above the database
parameters need to be changed for your database.

### ndo2db.cfg

```console
ndo2db_user=nagios
ndo2db_group=nagios
socket_type=tcp
socket_name=/usr/local/nagios/var/ndo.sock
tcp_port=5668
db_servertype=mysql
db_host=localhost
db_port=3306
db_name=DATABASE_NAME
db_user=DATABASE_USER
db_pass=DATABASE_PASSWORD
db_prefix=npc_
max_timedevents_age=1440
max_systemcommands_age=10080
max_servicechecks_age=10080
max_hostchecks_age=10080
max_eventhandlers_age=44640
debug_level=1
debug_verbosity=1
debug_file=/usr/local/nagios/var/ndo2db.debug
max_debug_file_size=1000000
```

### ndomod.cfg

```console
instance_name=default
output_type=tcpsocket
output=127.0.0.1
tcp_port=5668
output_buffer_items=5000
buffer_file=/usr/local/nagios/var/ndomod.tmp
file_rotation_interval=14400
file_rotation_timeout=60
reconnect_interval=15
reconnect_warning_interval=15
data_processing_options=-1
config_output_options=2
```

**NOTE:** There have been reports of the ndo2db process dying (regularly) on old
versions of ndo2db. To get this, setup the process to respawn via init or
systemd. If you have trouble with the daemon dying you can add the following
line to /etc/inittab. Only do this if ndo2db frequently dies unexpectedly on
you.

```console
ndo:345:respawn:/usr/local/nagios/bin/ndo2db -c /usr/local/nagios/etc/ndo2db.cfg
```

After editing inittab issue the following command:

```shell
telinit Q
```

You may see messages like the following in your syslog when using init to
respawn ndo2db:

```console
Apr 19 11:11:55 acid init: Id "ndo" respawning too fast: disabled for 5 minutes
```

Besides muddying the logs there is no harm and so far this is the only way I
have been able to keep ndo2db running.

## Translations

Translatable strings are collected into `locales/po/cacti.pot` by
`locales/build_gettext.sh`, which needs GNU gettext (`xgettext`, `msgmerge`,
`msgfmt`), `realpath` and GNU `sed` (the script fails with the BSD `sed` of
macOS). The per language `.po` and `.mo` files are maintained on the Cacti
translation server (Weblate) and are not changed in pull requests. A pull
request that adds or changes a string wrapped in `__()` includes only the
regenerated `locales/po/cacti.pot`.

## Development and tests

The tests run inside a Cacti checkout of the 1.2.x branch that has its
development dependencies installed (Pest, in `include/vendor/bin`), with
the plugin at `plugins/npc`, PHP 8.2 or later with Xdebug for the coverage
report, and a database configured in `include/config.php`. The file
`tests/.cacti-version` has to contain `1.2.x`, `develop`, or the exact contents
of the Cacti `include/cacti_version`.

From the Cacti root:

```shell
echo -n "1.2.x" > plugins/npc/tests/.cacti-version
include/vendor/bin/pest --configuration=plugins/npc/phpunit.xml --coverage-clover=plugins/npc/coverage/clover.xml
```

From the plugin directory, with the branch the change is based on as the
reference:

```shell
php tests/bin/patch-coverage.php coverage/clover.xml origin/develop 100
php tests/bin/check-i18n-pot.php origin/develop
php tests/bin/validate-manifest.php
```

`patch-coverage.php` fails when fewer than 100 percent of the changed lines of
the measured files are executed. `check-i18n-pot.php` fails when translatable
strings changed without a regenerated `locales/po/cacti.pot`.
`validate-manifest.php` fails when the top level files do not match `expected`
in `manifest.json`.

## Bugs and Feature Enhancements

Bug and feature enhancements for the npc plugin are handled in GitHub. If you
find a bug, first search the Cacti forums for a solution before creating an
issue in GitHub.

-----------------------------------------------
Copyright (c) 2004-2026 - The Cacti Group, Inc.
