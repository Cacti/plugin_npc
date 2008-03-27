npc.processInfo = function(){

    var title = 'Process Info';

    var outerTabId = 'processinfo-tab';

    npc.addCenterNestedTab(outerTabId, 'Process Information');

    var centerTabPanel = Ext.getCmp('centerTabPanel');

    var innerTabId = outerTabId + '-inner-panel';

    var innerTabPanel = Ext.getCmp(innerTabId);

    var tab = Ext.getCmp(innerTabPanel);

    if (tab) {
        innerTabPanel.setActiveTab(tab);
        return;
    } else {
        innerTabPanel.add({
            id: 'process-instance-tab',
            title: 'Instance',
            deferredRender:false,
            closable: false,
            items: [{}]
        });
        innerTabPanel.show();
        innerTabPanel.setActiveTab(0);
    }

    var piStore = new Ext.data.JsonStore({
        url: 'npc.php?module=nagios&action=getProcessInfoGrid',
        totalProperty:'totalCount',
        root:'data',
        fields:[
           'name',
           'value'
        ],
        autoload:true
    });

    var piCm = new Ext.grid.ColumnModel([{
        header:"Parameter",
        dataIndex:'name',
        width:100
    },{
        header:"Value",
        dataIndex:'value',
        width:300,
        align:'left'
    }]);

    var piGrid = new Ext.grid.GridPanel({
        autoHeight:true,
        autoWidth:true,
        store:piStore,
        cm:piCm,
        autoExpandColumn:'Value',
        sm: new Ext.grid.RowSelectionModel(),
        stripeRows: true,
        view: new Ext.grid.GridView({
             forceFit:true,
             autoFill:true,
             scrollOffset:0
        })
    });

    // Add the grids to the tabs
    Ext.getCmp('process-instance-tab').add(piGrid);

    // Refresh the dashboard
    centerTabPanel.doLayout();

    // Render the default grid
    piGrid.render();

    // Load the data stores
    piStore.load();

    // Start auto refresh
    piStore.startAutoRefresh(60);

    // Add listeners to stop auto refresh on the store if the tab is closed
    var listeners = {
        destroy: function() {
            piStore.stopAutoRefresh();
        }
    };

    // Add some action handlers to the SSI grid
    piGrid.on('rowdblclick', function(grid, row) {
        var n = grid.getStore().getAt(row).data.name;
        var v = grid.getStore().getAt(row).data.value;
        var toggle = false;

        if (v.match('tick.png')) {
            toggle = 'Stop';
        } else if (v.match('cross.png')) {
            toggle = 'Start';
        }

        if (toggle) {
            toggleEnabled(n, toggle);
        }
    });

    function toggleEnabled(v, toggle) {

        var cmd;
        var msg;
        var m;

        switch(v) {
            case 'Notifications Enabled':
                toggle = (toggle == 'Start') ? 'Enable' : 'Disable';
                m = ' notifications?';
                cmd = '_NOTIFICATIONS';
                break;
            case 'Active Service Checks Enabled':
                m = ' executing active service checks?';
                cmd = '_EXECUTING_SVC_CHECKS';
                break;
            case 'Passive Service Checks Enabled':
                m = ' accepting passive service checks?';
                cmd = '_ACCEPTING_PASSIVE_SVC_CHECKS';
                break;
            case 'Active Host Checks Enabled':
                m = ' executing active host checks?';
                cmd = '_EXECUTING_HOST_CHECKS';
                break;
            case 'Passive Host Checks Enabled':
                m = ' accepting passive host checks?';
                cmd = '_ACCEPTING_PASSIVE_HOST_CHECKS';
                break;
            case 'Event Handlers Enabled':
                toggle = (toggle == 'Start') ? 'Enable' : 'Disable';
                m = ' accepting passive host checks?';
                cmd = '_EVENT_HANDLERS';
                break;
            case 'Flap Detection Enabled':
                toggle = (toggle == 'Start') ? 'Enable' : 'Disable';
                m = ' flap detection?';
                cmd = '_FLAP_DETECTION';
                break;
            case 'Processing Performance Data':
                toggle = (toggle == 'Start') ? 'Enable' : 'Disable';
                m = ' processing performance data?';
                cmd = '_PERFORMANCE_DATA';
                break;
            case 'Obsess Over Services':
                m = ' obsessing over services?';
                cmd = '_OBSESSING_OVER_SVC_CHECKS';
                break;
            case 'Obsess Over Hosts':
                m = ' obsessing over hosts?';
                cmd = '_OBSESSING_OVER_HOST_CHECKS';
                break;
        }

        cmd = toggle.toUpperCase() + cmd;
        msg = toggle + m;

        Ext.Msg.show({
            title:'Confirm',
            msg:msg,
            buttons: Ext.Msg.YESNO,
            fn: function(btn) {
                if (btn == 'yes') {
                    npc.aPost({
                        module : 'nagios',
                        action : 'command',
                        p_command :cmd
                    });
                }
            },
            animEl: 'elId',
            icon: Ext.MessageBox.QUESTION
        });
    }


};
