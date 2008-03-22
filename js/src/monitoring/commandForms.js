npc.commandNote = {
    xtype: 'panel',
    html: '<br /><span style="font-size:10px;"><b>Note:</b> It may take a while before Nagios processes the command.</span>',
    width: 400
};


npc.ackProblem = function(type, host, service) {

    var cmd = 'ACKNOWLEDGE_' + type.toUpperCase() + '_PROBLEM';

    var title = 'Acknowledge Host Problem - ' + host;

    if (type == 'svc') {
        title = 'Acknowledge Service Problem - ' + host + ': ' + service;
    }

    var hostField = {
        name: 'p_host_name',
        value: host,
        xtype: 'hidden'
    };

    var serviceField = {
        name: 'p_service_description',
        value: service,
        xtype: 'hidden'
    };

    var form = new Ext.FormPanel({
        labelWidth: 110,
        url:'npc.php?module=nagios&action=command',
        frame:true,
        bodyStyle:'padding:5px 5px 0',
        width: 550,
        defaults: {width: 150},
        defaultType: 'textfield',
        items: [
        {
            name: 'p_command',
            value:cmd,
            xtype: 'hidden'
        },
            hostField,
            serviceField,
        {
            fieldLabel: 'Sticky',
            name: 'p_sticky',
            xtype: 'xcheckbox',
            labelStyle: 'cursor: help;',
            tooltipText: "Disables notifications until the host recovers.",
            listeners: {
                render: function(o) {
                    npc.setFormFieldTooltip(o);
                }
            },
            checked:true
        },{
            fieldLabel: 'Send Notification',
            name: 'p_notify',
            xtype: 'xcheckbox',
            labelStyle: 'cursor: help;',
            tooltipText: "Send a notification about the acknowledgement to contacts for this service.",
            listeners: {
                render: function(o) {
                    npc.setFormFieldTooltip(o);
                }
            },
            checked:true
        },{
            fieldLabel: 'Persistent',
            name: 'p_persistent',
            xtype: 'xcheckbox',
            labelStyle: 'cursor: help;',
            tooltipText: "Keep the service comment even after the acknowledgement is removed.",
            listeners: {
                render: function(o) {
                    npc.setFormFieldTooltip(o);
                }
            },
            checked:false
        },{
            name: 'p_author',
            value: npc.params.userName,
            xtype: 'hidden'
        },{
            fieldLabel: 'Comment',
            name: 'p_comment',
            xtype: 'htmleditor',
            height:150,
            width: 500
        },{
            xtype: 'panel',
            html: '<br /><span style="font-size:10px;"><b>Note:</b> It may take a while before Nagios processes this command.</span>',
            width: 400
        }],
        buttons: npc.cmdFormButtons
    });

    var win = new Ext.Window({
        //title:'Acknowledge Service Problem -  ' + host + ': ' + service,
        title:title,
        layout:'fit',
        modal:true,
        closable: true,
        width:680,
        height:375,
        bodyStyle:'padding:5px;',
        items: form
    });
    win.show();
};

npc.addComment = function(type, host, service) {

    var cmd = 'ADD_' + type.toUpperCase() + '_COMMENT';

    var hostField = {
        name: 'p_host_name',
        value: host,
        xtype: 'hidden'
    };

    if (typeof host == 'undefined') {
        hostField.fieldLabel = 'Host Name';
        hostField.xtype = 'textfield';
        hostField.allowBlank = false;
    }

    var serviceField = {
        name: 'p_service_description',
        value: service,
        xtype: 'hidden'
    };

    if (type == 'svc') {
        if (typeof service == 'undefined') {
            serviceField.fieldLabel = 'Service Description';
            serviceField.xtype = 'textfield';
            serviceField.allowBlank = false;
        }
    }

    var form = new Ext.FormPanel({
        labelWidth: 110,
        url:'npc.php?module=nagios&action=command',
        frame:true,
        bodyStyle:'padding:5px 5px 0',
        width: 550,
        defaults: {width: 175},
        defaultType: 'textfield',
            items: [
            {
                name: 'p_command',
                value:cmd,
                xtype: 'hidden'
            },
                hostField,
                serviceField,
            {
                fieldLabel: 'Persistent',
                name: 'p_persistent',
                xtype: 'xcheckbox',
                labelStyle: 'cursor: help;',
                tooltipText: "Persists the comment across Nagios restarts.",
                listeners: {
                    render: function(o) {
                        npc.setFormFieldTooltip(o);
                    }
                },
                checked:true
            },{
                name: 'p_author',
                value: npc.params.userName,
                xtype: 'hidden'
            },{
                fieldLabel: 'Comment',
                name: 'p_comment',
                xtype: 'htmleditor',
                height:200,
                width: 500
            },{
                xtype: 'panel',
                html: '<br /><span style="font-size:10px;"><b>Note:</b> It may take a while before Nagios processes the comment.</span>',
                width: 400
            }
        ],
        buttons: npc.cmdFormButtons
    });

    var win = new Ext.Window({
        title:'New Comment',
        layout:'fit',
        modal:true,
        closable: true,
        width:680,
        height:400,
        bodyStyle:'padding:5px;',
        items: form
    });
    win.show();

};

npc.sendCustomNotification = function(type, host, service) {

    var cmd = 'SEND_CUSTOM_' + type.toUpperCase() + '_NOTIFICATION';

    var hostField = {
        name: 'p_host_name',
        value: host,
        xtype: 'hidden'
    };

    if (typeof host == 'undefined') {
        hostField.fieldLabel = 'Host Name';
        hostField.xtype = 'textfield';
        hostField.allowBlank = false;
    }

    var serviceField = {
        name: 'p_service_description',
        value: service,
        xtype: 'hidden'
    };

    if (type == 'svc') {
        if (typeof service == 'undefined') {
            serviceField.fieldLabel = 'Service Description';
            serviceField.xtype = 'textfield';
            serviceField.allowBlank = false;
        }
    }

    var form = new Ext.FormPanel({
        labelWidth: 110,
        url:'npc.php?module=nagios&action=command',
        frame:true,
        bodyStyle:'padding:5px 5px 0',
        width: 550,
        defaults: {width: 175},
        defaultType: 'textfield',
            items: [
            {
                name: 'p_command',
                value:cmd,
                xtype: 'hidden'
            },
                hostField,
                serviceField,
            {
                name: 'p_options',
                value:0,
                xtype: 'hidden'
            },{
                fieldLabel: 'Forced',
                name: 'p_force_notification',
                xtype: 'xcheckbox',
                labelStyle: 'cursor: help;',
                tooltipText: "Selecting the Forced option will force the notification to be sent out, " +
                             "regardless of the time restrictions, whether or not notifications are enabled, etc.",
                listeners: {
                    check: function() {
                        var v = 2;
                        var options = parseInt(form.form.getValues().p_options);
                        if (this.checked) {
                            options = options + v;
                        } else {
                            options = options - v;
                        }
                        form.form.setValues({p_options: options});
                    },
                    render: function(o) {
                        npc.setFormFieldTooltip(o);
                    }
                },
                checked:false
            },{
                fieldLabel: 'Broadcast',
                name: 'p_broadcast_notification',
                xtype: 'xcheckbox',
                labelStyle: 'cursor: help;',
                tooltipText: "Selecting the Broadcast option causes the notification to be sent out to " +
                             "all normal (non-escalated) and escalated contacts.",
                listeners: {
                    check: function() {
                        var v = 1;
                        var options = parseInt(form.form.getValues().p_options);
                        if (this.checked) {
                            options = options + v;
                        } else {
                            options = options - v;
                        }
                        form.form.setValues({p_options: options});
                    },
                    render: function(o) {
                        npc.setFormFieldTooltip(o);
                    }
                },
                checked:false
            },{
                name: 'p_author',
                value: npc.params.userName,
                xtype: 'hidden'
            },{
                fieldLabel: 'Comment',
                name: 'p_comment',
                xtype: 'htmleditor',
                height:200,
                width: 500
            },{
                xtype: 'panel',
                html: '<br /><span style="font-size:10px;"><b>Note:</b> It may take a while before Nagios processes the command.</span>',
                width: 400
            }
        ],
        buttons: npc.cmdFormButtons
    });

    var win = new Ext.Window({
        title:'Send Custom Notification',
        layout:'fit',
        modal:true,
        closable: true,
        width:680,
        height:400,
        bodyStyle:'padding:5px;',
        items: form
    });
    win.show();
};

npc.scheduleNextCheck = function(type, host, service) {

    var cmd = 'SCHEDULE_FORCED_' + type.toUpperCase() + '_CHECK';

    var title = 'Schedule Check';
    if (type == 'host_svc') {
        title = 'Schedule check of all services on ' + host;
        cmd = cmd + 'S';
    } 

    var dt = new Date();

    var nowDate = dt.format('Y-m-d H:i:s');
    var nowEpoch = dt.format('U');

    var hostField = {
        name: 'p_host_name',
        value: host,
        xtype: 'hidden'
    };

    if (typeof host == 'undefined') {
        hostField.fieldLabel = 'Host Name';
        hostField.xtype = 'textfield';
        hostField.allowBlank = false;
    }

    var serviceField = {
        name: 'p_service_description',
        value: service,
        xtype: 'hidden'
    };

    if (type == 'svc' && typeof service == 'undefined') {
        serviceField.fieldLabel = 'Service Description';
        serviceField.xtype = 'textfield';
        serviceField.allowBlank = false;
    }

    var form = new Ext.FormPanel({
        labelWidth: 100,
        url:'npc.php?module=nagios&action=command',
        frame:true,
        bodyStyle:'padding:5px 5px 0',
        width: 350,
        defaults: {width: 175},
        defaultType: 'textfield',
            items: [
            {
                name: 'p_command',
                value:cmd,
                xtype: 'hidden'
            },
                hostField,
                serviceField,
            {
                fieldLabel: 'Force',
                name: 'p_force_notification',
                xtype: 'xcheckbox',
                checked:true,
                labelStyle: 'cursor: help;',
                tooltipText: "Force a check of the service regardless of what time the scheduled check occurs " +
                             "and whether or not checks are enabled for the service.",
                listeners: {
                    change: function() {
                        var cmd = parseInt(form.form.getValues().p_cmd);
                        if (this.checked) {
                            cmd = cmd.replace(/SCHEDULE_/, 'SCHEDULE_FORCED_');
                        } else {
                            cmd = cmd.replace(/FORCED_/, '');
                        }
                        form.form.setValues({p_cmd: cmd});
                    },
                    render: function(o) {
                        npc.setFormFieldTooltip(o);
                    }
                }
            },{
                fieldLabel: 'Check Time',
                name: 'p_date',
                value:nowDate,
                labelStyle: 'cursor: help;',
                tooltipText: "The date/time must be in the format YYYY-MM-DD HH:MM:SS.",
                listeners: {
                    change: function() {
                        var v = form.form.getValues().p_date;
                        var d = new Date(v.replace(/-/g,' '));
                        form.form.setValues({p_check_time: d.format('U')});
                    },
                    render: function(o) {
                        npc.setFormFieldTooltip(o);
                    }
                },
                xtype: 'textfield'
            },{
                name: 'p_check_time',
                value:nowEpoch,
                xtype:'hidden'
            },{
                xtype: 'panel',
                html: '<br /><span style="font-size:10px;"><b>Note:</b> It may take a while before Nagios processes the command.</span>',
                width: 400
            }
        ],
        buttons: npc.cmdFormButtons
    });

    var win = new Ext.Window({
        title:title,
        layout:'fit',
        modal:true,
        closable: true,
        width:400,
        height:200,
        bodyStyle:'padding:5px;',
        items: form
    });
    win.show();
};

npc.submitPassiveCheckResult = function(type, host, service) {

    var cmd = 'PROCESS_' + type.toUpperCase() + '_CHECK_RESULT';

    var hostField = {
        name: 'p_host_name',
        value: host,
        xtype: 'hidden'
    };

    var data = [['UP', 0], ['DOWN', 1], ['UNREACHABLE', 2], ['PENDING', -1]];


    if (typeof host == 'undefined') {
        hostField.fieldLabel = 'Host Name';
        hostField.xtype = 'textfield';
        hostField.allowBlank = false;
    }

    var serviceField = {
        name: 'p_service_description',
        value: service,
        xtype: 'hidden'
    };

    if (type == 'service') {
        data = [['OK', 0], ['WARNING', 1], ['CRITICAL', 2], ['UNKNOWN', 3], ['PENDING', -1]];
        if (typeof service == 'undefined') {
            serviceField.fieldLabel = 'Service Description';
            serviceField.xtype = 'textfield';
            serviceField.allowBlank = false;
        }
    }

    var form = new Ext.FormPanel({
        labelWidth: 110,
        url:'npc.php?module=nagios&action=command',
        frame:true,
        bodyStyle:'padding:5px 5px 0',
        width: 500,
        defaultType: 'textfield',
            items: [
            {
                name: 'p_command',
                value:cmd,
                xtype: 'hidden'
            },
                hostField,
                serviceField,
                new Ext.form.ComboBox({
                    store: new Ext.data.SimpleStore({
                        fields: ['name', 'value'],
                        data: data
                    }),
                    fieldLabel: 'Check Result',
                    hiddenName: 'p_return_code',
                    displayField:'name',
                    valueField:'value',
                    forceSelection:true,
                    mode: 'local',
                    editable:false,
                    width:100,
                    allowBlank:false,
                    emptyText:'',
                    triggerAction: 'all',
                    selectOnFocus:true
                }),
            {
                fieldLabel: 'Check Output',
                name: 'p_plugin_output',
                xtype: 'textfield',
                allowBlank: false,
                width:350,
                labelStyle: 'cursor: help;',
                tooltipText: "The check results for this passive check.",
                listeners: {
                    render: function(o) {
                        npc.setFormFieldTooltip(o);
                    }
                }
            },{
                xtype: 'panel',
                html: '<br /><span style="font-size:10px;"><b>Note:</b> It may take a while before Nagios processes the command.</span>',
                width: 400
            }
        ],
        buttons: npc.cmdFormButtons
    });

    var win = new Ext.Window({
        title:'Submit Passive Check Result',
        layout:'fit',
        modal:true,
        closable: true,
        width:550,
        height:200,
        bodyStyle:'padding:5px;',
        items: form
    });
    win.show();
};

npc.scheduleDowntime = function(type, host, service) {

    var cmd = 'SCHEDULE_' + type.toUpperCase() + '_DOWNTIME';

    var title = 'Schedule Host Downtime - ' + host;

    if (type == 'svc') {
        title = 'Schedule Service Downtime - ' + host + ': ' + service;
    }

    var sd = new Date();
    var ed = new Date();

    var startDate = sd.format('Y-m-d H:i:s');

    ed.setHours(ed.getHours() + 2);
    var endDate = ed.format('Y-m-d H:i:s');


    var startEpoch = sd.format('U');
    var endEpoch = ed.format('U');


    var hostField = {
        name: 'p_host_name',
        value: host,
        xtype: 'hidden'
    };

    if (typeof host == 'undefined') {
        hostField.fieldLabel = 'Host Name';
        hostField.xtype = 'textfield';
        hostField.allowBlank = false;
    }

    var serviceField = {
        name: 'p_service_description',
        value: service,
        xtype: 'hidden'
    };

    if (type == 'svc' && typeof service == 'undefined') {
        serviceField.fieldLabel = 'Service Description';
        serviceField.xtype = 'textfield';
        serviceField.allowBlank = false;
    }

    var form = new Ext.FormPanel({
        labelWidth: 110,
        url:'npc.php?module=nagios&action=command',
        frame:true,
        bodyStyle:'padding:5px 5px 0',
        width: 550,
        defaultType: 'textfield',
        items: [
            {
                name: 'p_command',
                value:cmd,
                xtype: 'hidden'
            },
                hostField,
                serviceField,
            {
                name: 'p_author',
                value: npc.params.userName,
                xtype: 'hidden'
            },{
                fieldLabel: 'Comment',
                name: 'p_comment',
                xtype: 'htmleditor',
                height:150,
                width: 500
            },
                new Ext.form.ComboBox({
                    store: new Ext.data.JsonStore({
                        url:'npc.php?module=downtime&action=getTriggeredByCombo',
                        totalProperty:'totalCount',
                        root:'data',
                        fields:[
                            'name',
                            {name: 'value', type: 'int'}
                        ],
                        autoload:true
                    }),
                    fieldLabel: 'Triggered By',
                    hiddenName: 'p_trigger_id',
                    displayField:'name',
                    valueField:'value',
                    forceSelection:true,
                    labelStyle: 'cursor: help;',
                    tooltipText: "Trigger the start of the downtime by the start of some other scheduled host or service downtime.",
                    listeners: {
                        expand: function(comboBox) {
                            comboBox.list.setWidth( 'auto' );
                            comboBox.innerList.setWidth( 'auto' );
                        },
                        render: function(o) {
                            npc.setFormFieldTooltip(o);
                        }
                    },
                    mode: 'remote',
                    editable:false,
                    width:400,
                    allowBlank:true,
                    emptyText:'',
                    triggerAction: 'all',
                    selectOnFocus:true
                }),
            {
                name: 'p_start_time',
                value: startEpoch,
                xtype: 'hidden'
            },{
                name: 'p_end_time',
                value: endEpoch,
                xtype: 'hidden',
            },{
                fieldLabel: 'Start Time',
                name: 'p_stime',
                value: startDate,
                labelStyle: 'cursor: help;',
                tooltipText: "Start time must be in the format 'YYYY-MM-DD HH:MM:SS'.",
                listeners: {
                    change: function(comboBox) {
                        var v = form.form.getValues().p_stime;
                        var d = new Date(v.replace(/-/g,' '));
                        form.form.setValues({p_start_time: d.format('U')});
                    },
                    render: function(o) {
                        npc.setFormFieldTooltip(o);
                    }
                },
                xtype:'textfield',
                allowBlank: false
            },{
                fieldLabel: 'End Time',
                name: 'p_etime',
                value: endDate,
                labelStyle: 'cursor: help;',
                tooltipText: "End time must be in the format 'YYYY-MM-DD HH:MM:SS'.",
                listeners: {
                    change: function(comboBox) {
                        var v = form.form.getValues().p_etime;
                        var d = new Date(v.replace(/-/g,' '));
                        form.form.setValues({p_end_time: d.format('U')});
                    },
                    render: function(o) {
                        npc.setFormFieldTooltip(o);
                    }
                },
                xtype:'textfield',
                allowBlank: false
            },
                new Ext.form.ComboBox({
                    store: new Ext.data.SimpleStore({
                        fields: ['name', 'value'],
                        data: [['Fixed', 1], ['Flexible', 0]]
                    }),
                    fieldLabel: 'Type',
                    hiddenName: 'p_fixed',
                    value: 1,
                    displayField:'name',
                    valueField:'value',
                    labelStyle: 'cursor: help;',
                    tooltipText: "Flexible downtime starts when the service enters a non-OK state (sometime between the start and end times you specified) and lasts as long as the duration of time you enter. Fixed downtime is starts and ands strictly based on start and end times provided. The duration field does not apply for fixed downtime.",
                    listeners: {
                        render: function(o) {
                            npc.setFormFieldTooltip(o);
                        }
                    },
                    forceSelection:true,
                    mode: 'local',
                    editable:false,
                    width:100,
                    allowBlank:false,
                    triggerAction: 'all',
                    selectOnFocus:true
                }),
            {
                name: 'p_duration',
                value: 0,
                xtype: 'hidden'
            },{
                fieldLabel: 'Duration (minutes)',
                name: 'p_minutes',
                value: 120,
                width: 50,
                listeners: {
                    change: function(comboBox) {
                        var min = parseInt(form.form.getValues().p_minutes);
                        var sec = min ? (min * 60) : 0
                        form.form.setValues({p_duration: sec});
                    }
                }
            }
        ],
        buttons: npc.cmdFormButtons
    });

    if (type == 'host') {
        var childrenField = new Ext.form.ComboBox({
            store: new Ext.data.SimpleStore({
                fields: ['name', 'value'],
                data: [
                    ['Do nothing with child hosts', 0], 
                    ['Schedule triggered downtime for all child hosts', 1],
                    ['Schedule non-triggered downtime for all child hosts', 2]
                ]
            }),
            fieldLabel: 'Child Hosts',
            hiddenName: 'p_child',
            value: 0,
            displayField:'name',
            valueField:'value',
            forceSelection:true,
            listeners: {
                change: function(comboBox) {
                    var v = parseInt(form.form.getValues().p_child);
                    if (v == 1) {
                        form.form.setValues({
                            p_cmd: 'SCHEDULE_AND_PROPAGATE_TRIGGERED_HOST_DOWNTIME'
                        });
                    } 
                    if (v == 2) {
                        form.form.setValues({
                            p_cmd: 'SCHEDULE_AND_PROPAGATE_HOST_DOWNTIME'
                        });
                    }
                }
            },
            mode: 'local',
            editable:false,
            width:300,
            allowBlank:false,
            triggerAction: 'all',
            selectOnFocus:true
        });
        
        form.items.add(childrenField);
    }

    var win = new Ext.Window({
        title:title,
        layout:'fit',
        modal:true,
        closable: true,
        width:700,
        height:450,
        bodyStyle:'padding:5px;',
        items: form
    });
    win.show();
};

npc.delayNextNotification = function(type, host, service) {

    var cmd = 'DELAY_' + type.toUpperCase() + '_NOTIFICATION';

    var form = new Ext.FormPanel({
        labelWidth: 110,
        url:'npc.php?module=nagios&action=command',
        frame:true,
        bodyStyle:'padding:5px 5px 0',
        width: 250,
        defaults: {width: 50},
        defaultType: 'textfield',
            items: [
            {
                name: 'p_command',
                value:cmd,
                xtype: 'hidden'
            },{
                name: 'p_host_name',
                value:host,
                xtype: 'hidden'
            },{
                name: 'p_service_description',
                value:service,
                xtype: 'hidden'
            },{
                fieldLabel: 'Delay (minutes)',
                name: 'p_notification_time',
                xtype: 'textfield',
                labelStyle: 'cursor: help;',
                tooltipText: "The number of minutes from now that the notification should be delayed.",
                allowBlank: false,
                listeners: {
                    render: function(o) {
                        npc.setFormFieldTooltip(o);
                    }
                },
                checked:true
            },{
                xtype: 'panel',
                html: '<br /><span style="font-size:10px;"><b>Note:</b> It may take a while before Nagios processes this command.</span>',
                width: 400
            }
        ],
        buttons: npc.cmdFormButtons
    });

    var win = new Ext.Window({
        title:'Delay Next Notification',
        layout:'fit',
        modal:true,
        closable: true,
        width:400,
        height:200,
        bodyStyle:'padding:5px;',
        items: form
    });
    win.show();
};
