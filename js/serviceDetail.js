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

    // If the tab exists set it active and return or else create it.
    if (tab)  { 
        tabPanel.setActiveTab(tab);
        return; 
    } else {
        tabPanel.add({
            id: id, 
            title: title,
            closable: true,
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

    function renderCurrentState(editor, v){
        console.log(v);
        if(val > 0){
            return String.format('<p class="{0}">{1}</p>', p.id, val);
        }
        return v;
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
            'normal_check_interval',
            'retry_check_interval',
            'check_timeperiod_object_id'
        ],
        autoload:true
    });

    var serviceStateGrid = new Ext.grid.PropertyGrid({
        title: 'Service State Information',
        autoHeight: true,
        width: 400,
        style:'padding:10px 0 10px 10px',
        onlyFields: ['current_state','check_command'],
        customRenderers: { 
            'current_state': renderCurrentState
        },
        stripeRows: true,
        view: new Ext.grid.GridView({
            forceFit:true,
            autoFill:true
        })
    });

    // Add the grid to the panel
    tab.items.add(serviceStateGrid);

    // Refresh the dashboard
    tabPanel.doLayout();

    // Render the grid
    serviceStateGrid.render();

    // Load the data store
    store.load();

    // add an on load listener to update the propertygrid
    store.on('load', function(){
        serviceStateGrid.setSource(store.getAt(0).data);
    });

    /* Property grid values are editable by default.
     * Add a beforeedit event handler to cancel the edit
     * action. Eventualy this can be modified to allow 
     * editing of certain fields.
     */
    serviceStateGrid.on("beforeedit",function(e){
        //based on a specific field:
        //if(e.field == "Field Name")

        //based on a specific row
        //if(e.row == 1)

        //based on the value another field of the record
        //if(e.record.get("Field Name") == "value")

        //cancel the event
        e.cancel = true;
    });


};
