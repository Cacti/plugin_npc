npc.hostCommandMenu = function(host, menu) {

    var text;
    var a;

    menu.removeAll();

    var post = {
        module: 'nagios',
        action: 'command',
        p_host_name: host.host_name
    };

    var font = '<b style="font-size: xx-small">';

    if (host.current_state == 1) {
        if (host.problem_has_been_acknowledged) {
            menu.add({
                text: font + 'Remove Problem Acknowledgement</b>',
                handler: function(o) {
                    post.p_command = 'REMOVE_HOST_ACKNOWLEDGEMENT';
                    post.p_host_name = host.host_name;
                    npc.doCommand(o.text+'?',post);
                }
            });
        } else {
            menu.add({
                text: font + 'Acknowledge Problem</b>',
                handler: function(o) {
                    npc.ackProblem('host', host.host_name);
                }
            });
        }
    }

    menu.add({
        text: font + 'Re-schedule Next Check</b>',
        handler: function() {
            npc.scheduleNextCheck('host', host.host_name);
        }
    });

    a = host.active_checks_enabled ? 'Disable' : 'Enable';
    text = font + a + ' Active Checks</b>';
    menu.add({
        action: a,
        text: text,
        handler: function(o) {
            post.p_command = o.action.toUpperCase() + '_HOST_CHECK';
            npc.doCommand(o.text+'?',post);
        }
    });

    a = host.notifications_enabled ? 'Disable' : 'Enable';
    text = font + a + ' Notifications</b>';
    menu.add({
        action: a,
        text: text,
        handler: function(o) {
            post.p_command = o.action.toUpperCase() + '_HOST_NOTIFICATIONS';
            npc.doCommand(o.text+'?',post);
        }
    });

    menu.add({
        text: font + 'Send Custom Notification</b>',
        handler: function() {
            npc.sendCustomNotification('host', host.host_name);
        }
    });

    if (host.passive_checks_enabled) {
        menu.add({
            text: font + 'Submit Passive Check Result</b>',
            handler: function() {
                npc.submitPassiveCheckResult('host', host.host_name);
            }
        });
    }

    menu.add({
        text: font + 'Schedule Downtime</b>',
        handler: function() {
            npc.scheduleDowntime('host', host.host_name);
        }
    });

    a = host.passive_checks_enabled ? 'Stop' : 'Start';
    text = font + a + ' Accepting Passive Checks</b>';
    menu.add({
        action: a,
        text: text,
        handler: function(o) {
            post.p_command = o.action.toUpperCase() + '_PASSIVE_HOST_CHECKS</b>';
            npc.doCommand(o.text+'?',post);
        }
    });

    a = host.event_handler_enabled ? 'Disable' : 'Enable';
    text = font + a + ' Event Handler</b>';
    menu.add({
        action: a,
        text: text,
        handler: function(o) {
            post.p_command = o.action.toUpperCase() + '_HOST_EVENT_HANDLER';
            npc.doCommand(o.text+'?',post);
        }
    });

    a = host.flap_detection_enabled ? 'Disable' : 'Enable';
    text = font + a + ' Flap Detection</b>';
    menu.add({
        action: a,
        text: text,
        handler: function(o) {
            post.p_command = o.action.toUpperCase() + '_HOST_FLAP_DETECTION';
            npc.doCommand(o.text+'?',post);
        }
    });

    if (host.current_state == 1) {
        menu.add({
            text: font + 'Delay next notification</b>',
            handler: function() {
                npc.delayNextNotification('host', host.host_name);
            }
        });
    }

    a = host.obsess_over_host ? 'Stop' : 'Start';
    text = font + a + ' Obsessing</b>';
    menu.add({
        action: a,
        text: text,
        handler: function(o) {
            post.p_command = o.action.toUpperCase() + '_OBSESSING_OVER_HOST';
            npc.doCommand(o.text+'?',post);
        }
    });

    menu.add({
        text: font + 'Schedule Check of all Services</b>',
        handler: function() {
            npc.scheduleNextCheck('host_svc', host.host_name);
        }
    });

    menu.add({
        text: font + 'Disable notifications for all services</b>',
        handler: function(o) {
            post.p_command = 'DISABLE_HOST_SVC_NOTIFICATIONS';
            npc.doCommand(o.text+'?',post);
        }
    });

    menu.add({
        text: font + 'Enable notifications for all services</b>',
        handler: function(o) {
            post.p_command = 'ENABLE_HOST_SVC_NOTIFICATIONS';
            npc.doCommand(o.text+'?',post);
        }
    });

    menu.add({
        text: font + 'Disable checks of all services</b>',
        handler: function(o) {
            post.p_command = 'DISABLE_HOST_SVC_CHECKS';
            npc.doCommand(o.text+'?',post);
        }
    });

    menu.add({
        text: font + 'Enable checks of all services</b>',
        handler: function(o) {
            post.p_command = 'ENABLE_HOST_SVC_CHECKS';
            npc.doCommand(o.text+'?',post);
        }
    });

    return(menu);
};


npc.hostgroupCommandMenu = function(hostgroup, menu) {

    menu.removeAll();

    var post = {
        module: 'nagios',
        action: 'command',
        p_hostgroup_name: hostgroup.hostgroup_name
    };

    var font = '<b style="font-size: xx-small">';

    menu.add({
        text: font + 'Schedule Downtime for all Services</b>',
        handler: function() {
            npc.scheduleHostgroupDowntime(hostgroup.hostgroup_name, 'SVC');
        }
    });

    menu.add({
        text: font + 'Schedule Downtime for all Hosts</b>',
        handler: function() {
            npc.scheduleHostgroupDowntime(hostgroup.hostgroup_name, 'HOST');
        }
    });

    menu.add({
        text: font + 'Enable Notifications for all Hosts</b>',
        handler: function(o) {
            post.p_command = 'ENABLE_HOSTGROUP_HOST_NOTIFICATIONS';
            npc.doCommand(o.text+'?',post);
        }
    });

    menu.add({
        text: font + 'Disable Notifications for all Hosts</b>',
        handler: function(o) {
            post.p_command = 'DISABLE_HOSTGROUP_HOST_NOTIFICATIONS';
            npc.doCommand(o.text+'?',post);
        }
    });

    menu.add({
        text: font + 'Enable Notifications for all Services</b>',
        handler: function(o) {
            post.p_command = 'ENABLE_HOSTGROUP_SVC_NOTIFICATIONS';
            npc.doCommand(o.text+'?',post);
        }
    });

    menu.add({
        text: font + 'Disable Notifications for all Services</b>',
        handler: function(o) {
            post.p_command = 'DISABLE_HOSTGROUP_SVC_NOTIFICATIONS';
            npc.doCommand(o.text+'?',post);
        }
    });

    menu.add({
        text: font + 'Enable Active Checks of all Services</b>',
        handler: function(o) {
            post.p_command = 'ENABLE_HOSTGROUP_SVC_CHECKS';
            npc.doCommand(o.text+'?',post);
        }
    });

    menu.add({
        text: font + 'Disable Active Checks of all Services</b>',
        handler: function(o) {
            post.p_command = 'DISABLE_HOSTGROUP_SVC_CHECKS';
            npc.doCommand(o.text+'?',post);
        }
    });


    return(menu);
};
