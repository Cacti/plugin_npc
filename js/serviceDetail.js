npc.app.serviceDetail = function(record) {

    // Set the id for the service detail tab
    var id = 'serviceDetail' + record.data.service_object_id + '-tab';

    // Set thetitle
    var title = record.data.host_name + ': ' + record.data.service_description;

    // Get the tabPanel
    var tabPanel = Ext.getCmp('centerTabPanel');

    // Get the tab
    var tab = Ext.getCmp(id);

    // Default # of rows to display
    var pageSize = 30;

    function renderValue(val, meta, record) {
        return String.format(val);
    }

    function renderCheckAttempt(val, p, r){
        return String.format('{0}/{1}', val, r.data.max_check_attempts);
    }

    function renderStateType(val){
        var state;
        switch(val) {
            case '0':
                state = 'Soft';
                break;
            case '1':
                state = 'Hard';
                break;
        }
        return String.format('{0}', state);
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
            items: [
                new Ext.TabPanel({
                    style:'padding:10px 0 10px 10px',
                    activeTab: 0,
                    autoHeight:true,
                    autoWidth:true,
                    plain:true,
                    deferredRender:false,
                    //layoutOnTabChange:true,
                    defaults:{autoScroll: true},
                    items:[{
                        title: 'Service State Information',
                        id: id + '-si'
                    },{
                        title: 'Alert History',
                        id: id + '-sa'
                    },{
                        title: 'Notification History',
                        id: id + '-sn'
                    },{
                        title: 'Downtime History',
                        id: id + '-sd'
                    },{
                        title: 'Graph',
                        autoLoad: 'graphProxy.php'
                    },{
                        title: 'Commands',
                        //listeners: {activate: handleActivate},
                        disabled:true,
                        html: 'Execute commands if you have permission.'
                    }]
                })
            ]
        }).show();
        tabPanel.doLayout();
        tabPanel.setActiveTab(tab);
        tab = Ext.getCmp(id);
    }

    var siStore = new Ext.data.JsonStore({
        url: 'npc.php?module=services&action=getServiceStateInfo&p_id=' + record.data.service_object_id,
        totalProperty:'totalCount',
        root:'data',
        fields:[
           'name',
           'value'
        ],
        autoload:true
    });

    var siCm = new Ext.grid.ColumnModel([{
        header:"Parameter",
        dataIndex:'name',
        width:100
    },{
        header:"Value",
        dataIndex:'value',
        width:300,
        renderer: renderValue,
        align:'left'
    }]);

    var siGrid = new Ext.grid.GridPanel({
        autoHeight:true,
        autoWidth:true,
        store:siStore,
        cm:siCm,
        autoExpandColumn:'Value',
        stripeRows: true,
        view: new Ext.grid.GridView({
             forceFit:true,
             autoFill:true,
             scrollOffset:0
        })
    });

    var snStore = new Ext.data.JsonStore({
        url: 'npc.php?module=services&action=getServiceNotifications&p_id=' + record.data.service_object_id,
        totalProperty:'totalCount',
        root:'data',
        fields:[
            'state',
            {name: 'start_time', type: 'date', dateFormat: 'Y-m-d H:i:s'},
            'output'
        ],
        autoload:true
    });

    var snCm = new Ext.grid.ColumnModel([{
        header:"",
        dataIndex:'state',
        width:40,
        renderer:npc.app.renderStatusImage
    },{
        header:"Date",
        dataIndex:'start_time',
        width:120,
        renderer: npc.app.formatDate
    },{
        header:"Message",
        dataIndex:'output',
        width:400,
    }]);

    var snGrid = new Ext.grid.GridPanel({
        autoHeight:true,
        autoWidth:true,
        store:snStore,
        cm:snCm,
        autoExpandColumn:'Message',
        stripeRows: true,
        view: new Ext.grid.GridView({
            forceFit:true,
            autoFill:true,
            emptyText:'No data found.',
            scrollOffset:0
        })
    });

    var saStore = new Ext.data.JsonStore({
        url: 'npc.php?module=services&action=getServiceAlertHistory&p_id=' + record.data.service_object_id,
        totalProperty:'totalCount',
        root:'data',
        fields:[
            'state',
            {name: 'state_time', type: 'date', dateFormat: 'Y-m-d H:i:s'},
            'state_type',
            'current_check_attempt',
            'max_check_attempts',
            'output'
        ],
        autoload:true
    });

    var saCm = new Ext.grid.ColumnModel([{
        header:"",
        dataIndex:'state',
        renderer:npc.app.renderStatusImage,
        width:40
    },{
        header:"Date",
        dataIndex:'state_time',
        renderer: npc.app.formatDate,
        width:120
    },{
        header:"State Type",
        dataIndex:'state_type',
        renderer: renderStateType,
        width:80
    },{
        header:"Check Attempt",
        dataIndex:'current_check_attempt',
        renderer: renderCheckAttempt,
        width:100
    },{
        header:"Plugin Output",
        dataIndex:'output',
        width:350
    }]);

    var saGrid = new Ext.grid.GridPanel({
        autoHeight:true,
        autoWidth:true,
        store:saStore,
        cm:saCm,
        autoExpandColumn:'output',
        stripeRows: true,
        view: new Ext.grid.GridView({
            forceFit:true,
            autoFill:true,
            emptyText:'No data found.',
            scrollOffset:0
        }),
        bbar: new Ext.PagingToolbar({
            pageSize:pageSize,
            store:saStore,
            displayInfo:true
        })
    });

    var sdStore = new Ext.data.JsonStore({
        url: 'npc.php?module=services&action=getServiceDowntimeHistory&p_id=' + record.data.service_object_id,
        totalProperty:'totalCount',
        root:'data',
        fields:[
            {name: 'entry_time', type: 'date', dateFormat: 'Y-m-d H:i:s'},
            {name: 'scheduled_start_time', type: 'date', dateFormat: 'Y-m-d H:i:s'},
            {name: 'scheduled_end_time', type: 'date', dateFormat: 'Y-m-d H:i:s'},
            'author_name',
            'comment_data'
        ],
        autoload:true
    });

    var sdCm = new Ext.grid.ColumnModel([{
        header:"Entry Time",
        dataIndex:'entry_time',
        renderer: npc.app.formatDate,
        width:120
    },{
        header:"Start Time",
        dataIndex:'scheduled_start_time',
        renderer: npc.app.formatDate,
        width:120
    },{
        header:"End Time",
        dataIndex:'scheduled_end_time',
        renderer: npc.app.formatDate,
        width:120
    },{
        header:"User",
        dataIndex:'author_name',
        width:100
    },{
        header:"Comment",
        dataIndex:'comment_data',
        width:400
    }]);

    var sdGrid = new Ext.grid.GridPanel({
        autoHeight:true,
        autoWidth:true,
        store:sdStore,
        cm:sdCm,
        autoExpandColumn:'Comment',
        stripeRows: true,
        view: new Ext.grid.GridView({
            forceFit:true,
            autoFill:true,
            emptyText:'No data found.',
            scrollOffset:0
        })
    });

    // Add the grids to the tabs
    Ext.getCmp(id+'-si').add(siGrid);
    Ext.getCmp(id+'-sn').add(snGrid);
    Ext.getCmp(id+'-sa').add(saGrid);
    Ext.getCmp(id+'-sd').add(sdGrid);

    // Refresh the dashboard
    tabPanel.doLayout();

    // Render the default grid
    siGrid.render();
    snGrid.render();
    saGrid.render();
    sdGrid.render();

    // Load the data stores
    siStore.load();
    snStore.load({params:{start:0, limit:pageSize}});
    saStore.load({params:{start:0, limit:pageSize}});
    sdStore.load({params:{start:0, limit:pageSize}});

    // Start auto refresh
    siStore.startAutoRefresh(npc.app.params.npc_portlet_refresh);
    snStore.startAutoRefresh(npc.app.params.npc_portlet_refresh);
    saStore.startAutoRefresh(npc.app.params.npc_portlet_refresh);
    sdStore.startAutoRefresh(npc.app.params.npc_portlet_refresh);

    // Add listeners to stop auto refresh on the store if the tab is closed
    var listeners = {
        destroy: function() {
            siStore.stopAutoRefresh();
            snStore.stopAutoRefresh();
            saStore.stopAutoRefresh();
            sdStore.stopAutoRefresh();
        }
    };

    // Add the listeners
    tab.addListener(listeners);
};
