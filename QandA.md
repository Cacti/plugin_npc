# Nagios Plugin for Cacti Questions and Answers

## General questions about NPC.

### What is Nagios Plugin for Cacti (NPC)?

NPC is a complete web based user interface replacement for ​Nagios written as a
plugin to ​Cacti.

### Who develops NPC?

NPC was written by Billy Gunn (Divagater on most forums), and now maintained by
The Cacti Group.

### Who can use NPC?
NPC is free software released under the GPL license and can be used by any
person or company.

### What can it do?

It can do anything the current Nagios UI can do but with a much improved
interface. Additionally, it has some additional features to integrate with Cacti
like creating data input methods from Nagios performance data. For a complete
list of features see the features page?.

### How does it work?

The Nagios folks provide a utility called ndo2db. ndo2db is made up of 2 pieces,
a module that is loaded by Nagios and a daemon that is started and stopped
independently. The module passes events from Nagios to the NDO2DB deamon which
handles updating the NPC database tables. The NPC web interface (written using
the ​Ext JavaScript framework) uses regular ajax calls to the server to update
the data displayed to the user. All the updates are done asynchronously so the
NPC web page never needs to be "reloaded" like traditional web apps. This gives
NPC the feel of a more traditional desktop application.

### What does it run on?

The server side components require a ​LAMP environment.  The client side is known
to work all modern browsers.

### Do Nagios and Cacti/NPC need to be installed on the same server?

Cacti and Nagios do not need to be on the same system. NPC gets all of its
display data from the database so when you configure NDO2DB you simply point it
to the host where your cacti database lives. For full functionality NPC needs to
be able to write commands (comments, acknowledgments, enabling and disabling
services etc.) to the Nagios command file. You will need to make the Nagios
command file available to the Cacti host via nfs (or cifs). This is a simple and
common way to do it.

### What does it look like?

There are various screenshots available on the GitHub site.

### Where can I find help?

For the current alpha release of NPC please post requests for help on this ​Cacti
forum thread.

### Why has the NPC JavaScript been obfuscated?

This was simply done for performance reasons. Client side applications written
in javascript can be quite large. To improve load times the NPC JavaScript is
compressed using the ​YUI Compressor.

### Why can't I see any hosts or services but I can see host and service groups?

This has to do with whether or not you are restoring retained information when
Nagios starts. Go to Console -> Settings -> NPC. Find the option 'Host/Service?
Config Type' and change it to the alternate value. 1 is the default option and
works for most environments.

### How can I alter the NPC JavaScript code for my own needs?

The full uncompressed JavaScript source code is provided. You can find it under
the js/src/ directory. To configure NPC to load the uncompressed source code
edit npc.php in the root of the npc plugin directory and make the following
change:

Change this line:

```js
`$module = 'layout';`
```

To this:

```js
`$module = 'layoutDev';`
```

If you want to compress the JavaScript with your changes, a phing build script
(build.xml) is provided in the js/src/ directory which will handle concatenating
all the source in the correct order and then calling the yui compressor. This
requires ​phing and the ​YUI compressor. Edit the build.xml file with the correct
path to the yuicompressor-x.x.x.jar file.

## NDO2DB

### Why does the Nagios process die immediately after startup?

The Nagios process will die immediately after startup if ndo2db is running with
incorrect database configuration. For instance a bad database password in
ndo2db.cfg will generate the following error in syslog:

```console
Mar 31 21:25:49 acid ndo2db: Error:
Could not connect to MySQL database: Access denied for user 'cactiuser'@'localhost' (using password: YES)
```

The ndo2db daemon will continue to run even after generating the above error
however Nagios will not start.

### Why aren't my NPC tables populating with data?

There could be many reasons for this related to your Nagios and ndo2db setup.
One common reason however is that you have enabled debugging in ndo2db.cfg and
then launched ndo2db as root. The logfile will be created as root prior to
ndo2db switching to a secure user like nagios as specified in the ndo2db.cfg.
ndo2db is now running with less privileges and connot write to the debug log
file which causes the whole thing to stop working.

To correct this look in ndo2db.cfg and see where you are writing the debug
logfile. Verify that file is owned by the user that ndo2db is running as. If
needed, change the owner of the file to the correct user. The default user is
nagios.

### ndo2db fails with a database not supported message

A message like the following:

```
Support for the specified database server is either not yet supported, or was
not found on your system.
```

This indicates that when you compiled ndo2db it was unable to find the MySQL
development libraries. Verify that you have the MySQL development libraries
installed (yum install mysql-devel on rpm based systems). If the libraries are
installed and the issue is still happening try specifying the lib path when
running configure. On an FC10 64 bit system I had to do the following:

```
`./configure --with-mysql-lib=/usr/lib64/mysql`
```

-----------------------------------------------
Copyright (c) 2004-2023 - The Cacti Group, Inc.
