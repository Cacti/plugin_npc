npc.app.serviceDetail = function(record) {

    // Set the id for the service detail tab
    var id = 'serviceDetail' + record.data.service_id + '-tab';

    // Set thetitle
    var title = record.data.host_name + ': ' + record.data.service_description;

    // Get the tabPanel
    var tabPanel = Ext.getCmp('centerTabPanel');

    // Get the tab
    var tab = Ext.getCmp(id);

    // Set the URL
    var url = 'npc.php?module=services&action=getServices&p_id=' + record.data.service_id;

    var ssi = {
        "current_state":{header:"Current State",renderer:renderState},
        "last_state_change":{header:"State Duration",renderer:npc.app.getDuration},
        "check_command":{header:"Check Command",renderer:renderCheckCommand}
    };

    function renderState(v) {
        return String.format('<b>&nbsp;{0}</b>', v);
    }

    function renderCheckCommand(v) {
        var command = v.split("!");
        console.log(command);
        return String.format('<a href="#" onclick="npc.app.showCommand(\'Services\', \'any\');return false;">{0}</a>', v);
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
           'instance_id',
           'instance_name',
           'host_object_id',
           'host_name',
           'service_id',
           'service_description',
           'servicestatus_id',
           'service_object_id',
           {name: 'status_update_time', type: 'date', dateFormat: 'Y-m-d H:i:s'},
           'output',
           'perfdata',
           'current_state',
           'has_been_checked',
           'should_be_scheduled',
           'current_check_attempt',
           'max_check_attempts',
           {name: 'last_check', type: 'date', dateFormat: 'Y-m-d H:i:s'},
           {name: 'next_check', type: 'date', dateFormat: 'Y-m-d H:i:s'},
           'check_type',
           {name: 'last_state_change', type: 'date', dateFormat: 'Y-m-d H:i:s'},
           {name: 'last_hard_state_change', type: 'date', dateFormat: 'Y-m-d H:i:s'},
           'last_hard_state',
           {name: 'last_time_ok', type: 'date', dateFormat: 'Y-m-d H:i:s'},
           {name: 'last_time_warning', type: 'date', dateFormat: 'Y-m-d H:i:s'},
           {name: 'last_time_unknown', type: 'date', dateFormat: 'Y-m-d H:i:s'},
           {name: 'last_time_critical', type: 'date', dateFormat: 'Y-m-d H:i:s'},
           'state_type',
           {name: 'last_notification', type: 'date', dateFormat: 'Y-m-d H:i:s'},
           {name: 'next_notification', type: 'date', dateFormat: 'Y-m-d H:i:s'},
           'no_more_notifications',
           'notifications_enabled',
           'problem_has_been_acknowledged',
           'acknowledgement_type',
           'current_notification_number',
           'passive_checks_enabled',
           'active_checks_enabled',
           'event_handler_enabled',
           'flap_detection_enabled',
           'is_flapping',
           'percent_state_change',
           'latency',
           'execution_time',
           'scheduled_downtime_depth',
           'failure_prediction_enabled',
           'process_performance_data',
           'obsess_over_service',
           'modified_service_attributes',
           'event_handler',
           'check_command',
           {name: 'check_command', type: 'string'},
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
            if (ssi[k]) {

                //if (typeof(v) == 'object' && v.getDate) {
                //    v = v.dateFormat(npc.app.params.npc_date_format + ' ' + npc.app.params.npc_time_format);
                //}

                c = ssi[k];

                if(typeof c.renderer == "string"){
                    c.renderer = Ext.util.Format[c.renderer];
                }

                if (c.renderer) {
                    v = c.renderer(o[k]);
                } else {
                    v = String.format('<p>&nbsp;{0}</p>', v);
                }

                if (c.header) {
                    k = c.header;
                }

                items.push({width:200, html: String.format('{0}', k)});
                items.push({wifth:300, html: v});
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
