npc.app.downtime = function(){

    var title = 'Downtime';

    // Default # of rows to display
    var pageSize = 20;

    var outerTabId = 'downtime-tab';

    npc.app.addCenterNestedTab(outerTabId, title);

    var centerTabPanel = Ext.getCmp('centerTabPanel');

    var innerTabId = outerTabId + '-inner-panel';

    var innerTabPanel = Ext.getCmp(innerTabId);

    var tab = Ext.getCmp(innerTabPanel);

    if (tab) {
        innerTabPanel.setActiveTab(tab);
        return;
    } else {
        innerTabPanel.add({ 
            id: 'host-downtime-tab', 
            title: 'Host Downtime', 
            deferredRender:false,
            closable: false, 
            items: [{}] 
        });
        innerTabPanel.add({ 
            id: 'service-comments-tab', 
            title: 'Service Downtime', 
            deferredRender:false,
            closable: false, 
            items: [{}] 
        });
        innerTabPanel.show(); 
        innerTabPanel.setActiveTab(0); 
    }


    var hostStore = new Ext.data.JsonStore({
        url: 'npc.php?module=downtime&action=getHostDowntime',
        totalProperty:'totalCount',
        root:'data',
        fields:[
            {name: 'entry_time', type: 'date', dateFormat: 'Y-m-d H:i:s'},
            {name: 'scheduled_start_time', type: 'date', dateFormat: 'Y-m-d H:i:s'},
            {name: 'scheduled_end_time', type: 'date', dateFormat: 'Y-m-d H:i:s'},
            {name: 'object_id', type: 'int'},
            'host_name',
            'author_name',
            'comment_data'
        ],
        autoload:true
    });

    var hostCm = new Ext.grid.ColumnModel([{
        header:"Host Name",
        dataIndex:'host_name',
        width:120
    },{
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

    var hostGrid = new Ext.grid.GridPanel({
        autoHeight:true,
        autoWidth:true,
        store:hostStore,
        cm:hostCm,
        autoExpandColumn:'comment_data',
        stripeRows: true,
        view: new Ext.grid.GridView({
            forceFit:true,
            autoFill:true,
            emptyText:'No downtime.',
            scrollOffset:0
        }),
        bbar: new Ext.PagingToolbar({
            pageSize:pageSize,
            store:hostStore,
            displayInfo:true
        })
    });



    // Add the grid to the panel
    Ext.getCmp('host-downtime-tab').items.add(hostGrid);
    //Ext.getCmp('service-downtime-tab').items.add(serviceGrid);

    // Refresh the dashboard
    centerTabPanel.doLayout();

    // Render the grid
    hostGrid.render();
    //serviceGrid.render();

    // Load the data store
    hostGrid.store.load({params:{start:0, limit:pageSize}});
    //serviceGrid.store.load({params:{start:0, limit:pageSize}});

    // Start auto refresh of the grid
    hostStore.startAutoRefresh(npc.app.params.npc_portlet_refresh);
    //serviceStore.startAutoRefresh(npc.app.params.npc_portlet_refresh);

    // Stop auto refresh if the tab is closed
    var listeners = {
        destroy: function() {
            hostStore.stopAutoRefresh();
            //serviceStore.stopAutoRefresh();
        }
    };

    // Add the listener to the tab
    Ext.getCmp('host-downtime-tab').addListener(listeners);
    //Ext.getCmp('service-downtime-tab').addListener(listeners);

    hostGrid.on('rowdblclick', npc.app.hostGridClick);
};
