npc.serviceCommandMenu = function(service, menu) {

    var text;
    var a;

    menu.removeAll();

    var post = {
        module: 'nagios',
        action: 'command',
        p_host_name: service.host_name,
        p_service_description: service.service_description
    };

    var font = '<b style="font-size: xx-small">';

    if (service.current_state == 2) {
        if (!service.problem_has_been_acknowledged) {
            menu.add({
                text: font + 'Acknowledge Problem</b>',
                handler: function(o) {
                    npc.ackProblem('svc', service.host_name, service.service_description);
                }
            });
        } else {
            menu.add({
                text: font + 'Remove problem acknowledgement</b>',
                handler: function(o) {
                    post.p_command = 'REMOVE_SVC_ACKNOWLEDGEMENT';
                    post.p_host_name = service.host_name;
                    post.p_service_description = service.service_description;
                    npc.doCommand(o.text+'?',post);
                }
            });
        }
    }

    menu.add({
        text: font + 'Re-schedule Next Check</b>',
        handler: function() {
            npc.scheduleNextCheck('svc', service.host_name, service.service_description);
        }
    });

    a = service.active_checks_enabled ? 'Disable' : 'Enable';
    text = font + a + ' Active Checks</b>';
    menu.add({
        action: a,
        text: text,
        handler: function(o) {
            post.p_command = o.action.toUpperCase() + '_SVC_CHECK';
            npc.doCommand(o.text+'?',post);
        }
    });

    a = service.notifications_enabled ? 'Disable' : 'Enable';
    text = font + a + ' Notifications</b>';
    menu.add({
        action: a,
        text: text,
        handler: function(o) {
            post.p_command = o.action.toUpperCase() + '_SVC_NOTIFICATIONS';
            npc.doCommand(o.text+'?',post);
        }
    });

    menu.add({
        text: font + 'Send Custom Notification</b>',
        handler: function() {
            npc.sendCustomNotification('svc', service.host_name, service.service_description);
        }
    });

    if (service.passive_checks_enabled) {
        menu.add({
            text: font + 'Submit Passive Check Result</b>',
            handler: function() {
                npc.submitPassiveCheckResult('service', service.host_name, service.service_description);
            }
        });
    }

    menu.add({
        text: font + 'Schedule Downtime</b>',
        handler: function() {
            npc.scheduleDowntime('svc', service.host_name, service.service_description);
        }
    });

    a = service.passive_checks_enabled ? 'Stop' : 'Start';
    text = font + a + ' Accepting Passive Checks</b>';
    menu.add({
        action: a,
        text: text,
        handler: function(o) {
            post.p_command = o.action.toUpperCase() + '_PASSIVE_SVC_CHECKS</b>';
            npc.doCommand(o.text+'?',post);
        }
    });

    a = service.obsess_over_service ? 'Stop' : 'Start';
    text = font + a + ' Obsessing</b>';
    menu.add({
        action: a,
        text: text,
        handler: function(o) {
            post.p_command = o.action.toUpperCase() + '_OBSESSING_OVER_SVC';
            npc.doCommand(o.text+'?',post);
        }
    });

    a = service.event_handler_enabled ? 'Disable' : 'Enable';
    text = font + a + ' Event Handler</b>';
    menu.add({
        action: a,
        text: text,
        handler: function(o) {
            post.p_command = o.action.toUpperCase() + '_SVC_EVENT_HANDLER';
            npc.doCommand(o.text+'?',post);
        }
    });

    a = service.flap_detection_enabled ? 'Disable' : 'Enable';
    text = font + a + ' Flap Detection</b>';
    menu.add({
        action: a,
        text: text,
        handler: function(o) {
            post.p_command = o.action.toUpperCase() + '_SVC_FLAP_DETECTION';
            npc.doCommand(o.text+'?',post);
        }
    });

    if (service.current_state == 2) {
        menu.add({
            text: font + 'Delay next notification</b>',
            handler: function() {
                npc.delayNextNotification('svc', service.host_name,service.service_description);
            }
        });
    }

    return(menu);
};

