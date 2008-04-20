npc.downtime = function(){

    var title = 'Scheduled Downtime';

    var outerTabId = 'downtime-tab';

    npc.addCenterNestedTab(outerTabId, title);

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
            title: 'Hosts', 
            height:600,
            layout: 'fit',
            deferredRender:false,
            closable: false
        });
        innerTabPanel.add({ 
            id: 'service-downtime-tab', 
            title: 'Services', 
            height:600,
            layout: 'fit',
            deferredRender:false,
            closable: false
        });
        innerTabPanel.show(); 
        innerTabPanel.setActiveTab(0); 
    }

    function renderServicegroupHeading(v, p, r) {
        return String.format('[{0}] - {1}', r.data.host_name, r.data.service_description);
    }

    var hostStore = new Ext.data.GroupingStore({
        url: 'npc.php?module=downtime&action=getHostDowntime',
        autoload:true,
        sortInfo:{field: 'host_name', direction: "ASC"},
        reader: new Ext.data.JsonReader({
            totalProperty:'totalCount',
            root:'data'
        }, [
            {name: 'entry_time', type: 'date', dateFormat: 'Y-m-d H:i:s'},
            {name: 'scheduled_start_time', type: 'date', dateFormat: 'Y-m-d H:i:s'},
            {name: 'scheduled_end_time', type: 'date', dateFormat: 'Y-m-d H:i:s'},
            {name: 'object_id', type: 'int'},
            'host_name',
            'author_name',
            'comment_data'
        ]),
        groupField:'host_name'
    });


    var hostCm = new Ext.grid.ColumnModel([{
        header:"Host Name",
        dataIndex:'host_name',
        hidden:true,
        width:120
    },{
        header:"Entry Time",
        dataIndex:'entry_time',
        renderer: npc.formatDate,
        width:120
    },{
        header:"Start Time",
        dataIndex:'scheduled_start_time',
        renderer: npc.formatDate,
        width:120
    },{
        header:"End Time",
        dataIndex:'scheduled_end_time',
        renderer: npc.formatDate,
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

    /* Host Downtime Grid */
    var hdGridId ='downtime-hdGrid';
    var hdGridState = Ext.state.Manager.get(hdGridId);
    var hdGridRows = (hdGridState && hdGridState.rows) ? hdGridState.rows : 15;
    var hdGridRefresh = (hdGridState && hdGridState.refresh) ? hdGridState.refresh : 60;

    var hostGrid = new Ext.grid.GridPanel({
        id: hdGridId,
        height:800,
        layout: 'fit',
        autoScroll:true,
        store:hostStore,
        cm:hostCm,
        autoExpandColumn:'comment_data',
        stripeRows: true,
        listeners: {
            // Intercept the state save to add our custom attributes
            beforestatesave: function(o, s) {
                s.rows = hdGridRows;
                s.refresh = hdGridRefresh;
                Ext.state.Manager.set(hdGridId, s);
                return false;
            }
        },
        view: new Ext.grid.GroupingView({
            forceFit:true,
            autoFill:true,
            hideGroupedColumn: true,
            enableGroupingMenu: false,
            showGroupName:false,
            enableNoGroups: true,
            emptyText:'No scheduled downtime.',
            groupTextTpl: '{text} ({[values.rs.length]} {[values.rs.length > 1 ? "Comments" : "Comment"]})'
        }),
        bbar: new Ext.PagingToolbar({
            pageSize: hdGridRows,
            store: hostStore,
            displayInfo: true,
            items: npc.setRefreshCombo(hdGridId, hostStore, hdGridState),
            plugins: new Ext.ux.Andrie.pPageSize({ gridId: hdGridId })
        })
    });

    var serviceStore = new Ext.data.GroupingStore({
        url: 'npc.php?module=downtime&action=getServiceDowntime&p_id=',
        autoload:true,
        sortInfo:{field: 'host_name', direction: "ASC"},
        reader: new Ext.data.JsonReader({
            totalProperty:'totalCount',
            root:'data'
        }, [
            {name: 'entry_time', type: 'date', dateFormat: 'Y-m-d H:i:s'},
            {name: 'scheduled_start_time', type: 'date', dateFormat: 'Y-m-d H:i:s'},
            {name: 'scheduled_end_time', type: 'date', dateFormat: 'Y-m-d H:i:s'},
            {name: 'object_id', type: 'int'},
            'host_name',
            'service_description',
            'author_name',
            'comment_data'
        ]),
        groupField:'object_id'
    });

    var serviceCm = new Ext.grid.ColumnModel([{
        header:"Host Name",
        dataIndex:'host_name',
        hidden:true
    },{
        header:"Service",
        dataIndex:'object_id',
        renderer:renderServicegroupHeading,
        hidden:true
    },{
        header:"Entry Time",
        dataIndex:'entry_time',
        renderer: npc.formatDate,
        width:120
    },{
        header:"Start Time",
        dataIndex:'scheduled_start_time',
        renderer: npc.formatDate,
        width:120
    },{
        header:"End Time",
        dataIndex:'scheduled_end_time',
        renderer: npc.formatDate,
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


    /* Service Downtime Grid */
    var sdGridId = 'downtime-sdGrid';
    var sdGridState = Ext.state.Manager.get(sdGridId);
    var sdGridRows = (sdGridState && sdGridState.rows) ? sdGridState.rows : 15;
    var sdGridRefresh = (sdGridState && sdGridState.refresh) ? sdGridState.refresh : 60;

    var serviceGrid = new Ext.grid.GridPanel({
        id: sdGridId,
        height:800,
        layout: 'fit',
        autoScroll:true,
        store:serviceStore,
        cm:serviceCm,
        autoExpandColumn:'comment_data',
        stripeRows: true,
        listeners: {
            // Intercept the state save to add our custom attributes
            beforestatesave: function(o, s) {
                s.rows = sdGridRows;
                s.refresh = sdGridRefresh;
                Ext.state.Manager.set(sdGridId, s);
                return false;
            }
        },
        view: new Ext.grid.GroupingView({
            forceFit:true,
            autoFill:true,
            hideGroupedColumn: true,
            enableGroupingMenu: false,
            showGroupName:false,
            enableNoGroups: true,
            emptyText:'No scheduled downtime.',
            groupTextTpl: '{text} ({[values.rs.length]} {[values.rs.length > 1 ? "Comments" : "Comment"]})'
        }),
        bbar: new Ext.PagingToolbar({
            pageSize: sdGridRows,
            store: serviceStore,
            displayInfo: true,
            items: npc.setRefreshCombo(sdGridId, serviceStore, sdGridState),
            plugins: new Ext.ux.Andrie.pPageSize({ gridId: sdGridId })
        })
    });

    // Add the grid to the panel
    Ext.getCmp('host-downtime-tab').add(hostGrid);
    Ext.getCmp('service-downtime-tab').add(serviceGrid);

    // Refresh the dashboard
    centerTabPanel.doLayout();

    // Render the grid
    hostGrid.render();
    serviceGrid.render();

    // Load the data store
    hostGrid.store.load({params:{start:0, limit:hdGridRows}});
    serviceGrid.store.load({params:{start:0, limit:sdGridRows}});

    // Start auto refresh of the grid
    hostStore.startAutoRefresh(hdGridRefresh);
    serviceStore.startAutoRefresh(sdGridRefresh);

    // Stop auto refresh if the tab is closed
    var listeners = {
        destroy: function() {
            hostStore.stopAutoRefresh();
            serviceStore.stopAutoRefresh();
        }
    };

    // Add the listener to the tab
    Ext.getCmp('host-downtime-tab').addListener(listeners);
    Ext.getCmp('service-downtime-tab').addListener(listeners);

    hostGrid.on('rowdblclick', npc.hostGridClick);
    serviceGrid.on('rowdblclick', npc.serviceGridClick);
};
