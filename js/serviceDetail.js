npc.app.serviceDetail = function(record) {

    // Set the id for the service detail tab
    var id = 'serviceDetail' + record.data.service_id + '-tab';

    // Set thetitle
    var title = record.data.host_name + ': ' + record.data.service_description;

    // Get the tabPanel
    var tabPanel = Ext.getCmp('centerTabPanel');

    // Get the tab
    var tab = Ext.getCmp(id);

    var dateFormat = 'Y-m-d H:i:s';

    // Set the URL
    var url = 'npc.php?module=services&action=getServices&p_id=' + record.data.service_id;

    var ssiRows = {
        'Current State': {
            renderer: renderState
        },
        'State Duration': {
            renderer: npc.app.getDuration
        },
        'Check Command': {
            renderer: renderCheckCommand
        },
        'Plugin Output': {},
        'Current Attempt': {
            renderer: renderCurrentAttempt
        },
        'Last Check': {
            renderer: npc.app.formatDate
        },
        'Next Check': {
            renderer: npc.app.formatDate
        },
        'Event Handler': {
            renderer: renderEventHandler
        },
        'Check Latency': {},
        'Check Duration': {},
        'Flapping': {
            renderer: renderFlapping
        }
    };

    function renderState(v, meta) {
        var img;
        switch(v) {
            case '0':
                img = 'recovery.png';
                break;
            case '1':
                img = 'warning.png';
                break;
            case '2':
                img = 'critical.png';
                break;
            case '3':
                img = 'unknown.png';
                break;
            case '-1':
                img = 'info.png';
                break;
        }
        return String.format('<img src="images/nagios/{0}">', img);
    }

    function renderCheckCommand(v) {
        //var command = v.split("!");
        //console.log(command);
        return String.format('<a href="#" onclick="npc.app.showCommand(\'Services\', \'any\');return false;">{0}</a>', v);
    }

    function renderCurrentAttempt(v) {
        var max = store.data.items[0].data['max_check_attempts'];
        return String.format('{0}/{1}', v, max);
    }

    function renderEventHandler(v) {
        var enabled = store.data.items[0].data['event_handler_enabled'];
        if (v && enabled) {
            return String.format('(Enabled) {0}', v);
        } else if (v) {
            return String.format('(Disabled) {0}', v);
        }

        return String.format('N/A');
    }

    function renderFlapping(v) {
        switch(v) {
            case '0':
                v = 'No';;
                break;
            case '1':
                v = 'Yes';;
                break;
        }

        return String.format('{0}', v);
    }

    // If the tab exists set it active and return or else create it.
    if (tab)  { 
        tabPanel.setActiveTab(tab);
        return; 
    } else {
        tabPanel.add({
            id: id, 
            title: title,
            closable: true,
            autoScroll: true,
            containerScroll: true,
            tbar: [{
                id:'tab',
                text: 'View in New Tab',
                iconCls: 'new-tab',
                disabled:true,
                handler : this.openTab,
                scope: this
            },
            '-',
            {
                id:'win',
                text: 'Go to Post',
                iconCls: 'new-win',
                disabled:true,
                scope: this,
                handler : function(){
                    window.open(this.gsm.getSelected().data.link);
                }
            }],
            items: [{}]
        }).show();

        tabPanel.doLayout();
        tabPanel.setActiveTab(tab);
        tab = Ext.getCmp(id);
    }

    var store = new Ext.data.JsonStore({
        url:url,
        totalProperty:'totalCount',
        root:'data',
        fields:[
           {name: 'Current State', mapping: 'current_state'},
           {name: 'Plugin Output', mapping: 'output'},
           {name: 'State Duration', mapping: 'last_state_change', type: 'date', dateFormat: dateFormat},
           {name: 'Check Command', mapping: 'check_command'},
           {name: 'Current Attempt', mapping: 'current_check_attempt'},
           {name: 'Last Check', mapping: 'last_check', type: 'date', dateFormat: dateFormat},
           {name: 'Next Check', mapping: 'next_check', type: 'date', dateFormat: dateFormat},
           {name: 'Event Handler', mapping: 'event_handler'},
           {name: 'Check Latency', mapping: 'latency'},
           {name: 'Check Duration', mapping: 'execution_time'},
           {name: 'Flapping', mapping: 'is_flapping'},
           'flap_detection_enabled',
           'max_check_attempts',
           'event_handler_enabled',
           'instance_id',
           'instance_name',
           'host_object_id',
           'host_name',
           'service_id',
           'service_description',
           'servicestatus_id',
           'service_object_id',
           {name: 'status_update_time', type: 'date', dateFormat: dateFormat},
           'perfdata',
           'has_been_checked',
           'should_be_scheduled',
           'check_type',
           {name: 'last_hard_state_change', type: 'date', dateFormat: dateFormat},
           'last_hard_state',
           {name: 'last_time_ok', type: 'date', dateFormat: dateFormat},
           {name: 'last_time_warning', type: 'date', dateFormat: dateFormat},
           {name: 'last_time_unknown', type: 'date', dateFormat: dateFormat},
           {name: 'last_time_critical', type: 'date', dateFormat: dateFormat},
           'state_type',
           {name: 'last_notification', type: 'date', dateFormat: dateFormat},
           {name: 'next_notification', type: 'date', dateFormat: dateFormat},
           'no_more_notifications',
           'notifications_enabled',
           'problem_has_been_acknowledged',
           'acknowledgement_type',
           'current_notification_number',
           'passive_checks_enabled',
           'active_checks_enabled',
           'percent_state_change',
           'scheduled_downtime_depth',
           'failure_prediction_enabled',
           'process_performance_data',
           'obsess_over_service',
           'modified_service_attributes',
           'normal_check_interval',
           'retry_check_interval',
           'check_timeperiod_object_id'
        ],
        autoload:true
    });

    // Load the data store
    store.load();

    // add an on load listener to update the propertygrid
    store.on('load', function(){
        drawTable(store.getAt(0).data);
    });

    function drawTable(o) {

        var items = [];

        for(var k in o){
            var v = o[k];
            if (ssiRows[k]) {

                c = ssiRows[k];

                if(typeof c.renderer == "string"){
                    c.renderer = Ext.util.Format[c.renderer];
                }

                if (c.renderer) {
                    v = c.renderer(o[k]);
                } else {
                    v = String.format('{0}', v);
                }

                items.push({width:200, html: k});
                items.push({width:300, html: v});
            }
        }

        var table = new Ext.Panel({
            title: 'Service State Information',
            style:'padding:10px 0 10px 10px',
            autoHeight: true,
            width: 500,
            //autoScroll: true,
            layout:'table',
            stripeRows:true,
            defaults: {
                bodyStyle:'padding:2px'
            },
            layoutConfig: {
                columns: 2
            },
            items: items
        });

        // Add the grid to the panel
        tab.items.add(table);

        // Refresh the dashboard
        tabPanel.doLayout();

        // Render the table
        table.render();
    }
};
