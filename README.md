# NPC Quick Start Guide

NPC is designed to be a complete web based UI replacement to Nagios while fully
integrating into Cacti using the Cacti Plugin Architecture. This integration
will provide a single point of access for trending and alert monitoring.

**IMPORTANT NOTE:** Since this is a fresh port of a 10 year old Cacti plugin,
you should be using it in a non-production setup until further migration testing
has been performed by the Cacti community.

## Features

1) A complete Nagios UI replacement integrated into Cacti.

2) A feature rich user interface developed on ExtJS.

3) A central location to monitor trending and alerting.

4) All NPC data is updated asynchronously via JSON (no reloading web pages).

5) Automated importing/syncing of hosts from Nagios to Cacti (N2C).

6) The UI can be customized on a per user basis.

## Usage

With the exception of some installation notes documentation is very slim. Watch
the forums and the NPC GitHub for additional info. Here are a few tips to get
you started:

* Most lists including services or hosts can be double clicked to open
  additional detail screens.

* Most lists including services or hosts can be right clicked to popup a context
  menu used to issue service or host commands.

* If a graph is mapped to a service or host, a graph icon will be displayed in
  the hosts and services screens.  A single left click of this icon will popup
  the graph.

* Most green check marks (started/enabled) or most red X (stopped/disabled) can
  be double clicked to toggle the option (requires that remote commands are
  enabled.)

* Most grid columns can be re-ordered via drag and drop.

* Most grid columns can be hidden and currently hidden columns can be viewed by
  clicking a down arrow to the right of the column heading.

* Dashboard portlets can be closed or minimized. Closed portlets can be made
  visible again by clicking the Portlets button on the far right of the toolbar
  on the Dashboard tab and then select which portlets you want displayed.

* All changes to the interface (portlets, column order, etc.) are saved server
  side on a per user basis.  Customize the UI your way and it is available
  anywhere you login.

* Importing Nagios hosts is done from the N2C link on the left side tree menu.
  Before starting an import you need to click in the template field to the right
  of the hostgroup you are importing. Doing so will expose a select box for
  assigning a host template.  Presently, this may be broken in Cacti 1.x due to
  it's redesigned tree architecture.

**IMPORTANT NOTE:** Since this is a fresh port of a 10 year old Cacti plugin,
you should be using it in a non-production setup until further migration testing
has been performed by the Cacti community.

## Installation

The remainder of the document provides the steps that are required to install
and configure the NPC Plugin for Cacti.

## Requirements

Below are the minimum requirements:

- Cacti 1.2.1
- NDOUtils 1.4b7
- Nagios 3.x

The NPC Plugin, first written for Cacti 0.8.7, has been updated to work with
Cacti 1.2.x.  However, testing has been very light.  We will need feedback from
users as to its usability with this Cacti version.

It is anticipated that there will be many changes forthcoming as we review the
architecture and the ExtJS framework to see how it co-exists with the Cacti
Framework.  So far so good.

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

3. Ensure permissions are set for the apache user to read

4. login to Cacti as an admin.

5. Install the NPC Plugin

   1. Go to Console > Configuration > Plugins

   2. Click the Install link to the left of the 'Npc' plugin line

   3. Next, click 'Enable' link to the left of the 'Npc' plugin line

6. You can find the NPC plugin options under Console > Configuration > Settings
   section

Without NDO2DB feeding Nagios data into NPC the dashboard (and other screens)
will be empty.

Next, there are a few NPC settings to add/update. Goto Console > Configuration >
Settings > NPC.

* **Remote Commands** - Check the checkbox to enable remote commands. At the
  moment this is system wide so all users that can access NPC will be able to
  execute commands.

* **Nagios Command File Path** - Add the path to the Nagios command file. It
  will be something like /usr/local/nagios/var/rw/nagios.cmd

* **Nagios URL** - The URL to the Nagios web interface is used to get access to
  the status map and reporting CGI's. You can also access the Nagios UI by
  clicking the "Nagios" link in the left hand "Navigation" pane in NPC.

* Set the date and time format to your liking.

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

## Bugs and Feature Enhancements

Bug and feature enhancements for the npc plugin are handled in GitHub. If you
find a first search the Cacti forums for a solution before creating an issue in
GitHub.

## Changelog

--- develop ---

* issue#13: Nagios sync partially to NPC plugin - only can see update of
  hostgroup


--- 3.1 ---

* issue: Adding some missing columns to a few tables

* issue: Undefined variables in controllers settings.php


--- 3.0 ---

* feature: compatibility improvements for cacti 1.2.x

-----------------------------------------------
Copyright (c) 2004-2024 - The Cacti Group, Inc.
