npc.servicegroupOverview = function(){

    // Panel title
    var title = 'Servicegroup Overview';

    // Panel ID
    var id = 'servicegroupOverview-tab';

    // Portlet URL
    var url = 'npc.php?module=servicegroups&action=getOverview';

    // Default # of rows to display
    var pageSize = 25;

    var outerTabId = 'services-tab';

    npc.addCenterNestedTab(outerTabId, 'Services');

    var centerTabPanel = Ext.getCmp('centerTabPanel');

    var innerTabId = 'services-tab-inner-panel';

    var innerTabPanel = Ext.getCmp(innerTabId);

    var tab = Ext.getCmp(id);

    if (tab) {
        innerTabPanel.setActiveTab(tab);
        return;
    } else {
        innerTabPanel.add({
            id: id,
            title: title,
            closable: true,
            items: [{}]
        }).show();
        innerTabPanel.setActiveTab(tab);
        tab = Ext.getCmp(id);
    }

    var store = new Ext.data.GroupingStore({
        url:url,
        autoload:true,
        sortInfo:{field: 'host_name', direction: "ASC"},
        reader: new Ext.data.JsonReader({
            totalProperty:'totalCount',
            root:'data'
        }, [
            {name: 'instance_id', type: 'int'},
            {name: 'servicegroup_object_id', type: 'int'},
            'alias',
            'host_name',
            {name: 'host_state', type: 'int'},
            {name: 'critical', type: 'int'},
            {name: 'warning', type: 'int'},
            {name: 'unknown', type: 'int'},
            {name: 'ok', type: 'int'},
            {name: 'pending', type: 'int'}
        ]),
        groupField:'alias'
    });

    var cm = new Ext.grid.ColumnModel([{
        header:"Servicegroup",
        dataIndex:'alias',
        hidden:true
    },{
        header:"Host",
        dataIndex:'host_name',
        sortable:true,
        width:100
    },{
        header:"Status",
        dataIndex:'host_state',
        align:'center',
        width:40,
        renderer:npc.hostStatusImage
    },{
        id: 'sgHostTotalsCRITICAL',
        header:"Critical",
        dataIndex:'critical',
        align:'center',
        width:40,
        renderer: npc.renderStatusBg
    },{
        id: 'sgHostTotalsWARNING',
        header:"Warning",
        dataIndex:'warning',
        align:'center',
        width:45,
        renderer: npc.renderStatusBg
    },{
        id: 'sgHostTotalsUNKNOWN',
        header:"Unknown",
        dataIndex:'unknown',
        align:'center',
        width:45,
        renderer: npc.renderStatusBg
    },{
        id: 'sgHostTotalsOK',
        header:"Ok",
        dataIndex:'ok',
        align:'center',
        width:25,
        renderer: npc.renderStatusBg
    },{
        id: 'sgHostTotalsPENDING',
        header:"Pending",
        dataIndex:'pending',
        align:'center',
        width:45,
        renderer: npc.renderStatusBg
    }]);


    var grid = new Ext.grid.GridPanel({
        id: 'servicegroup-overview-portlet-grid',
        autoHeight:true,
        autoExpandColumn: 'host_name',
        store:store,
        cm:cm,
        sm: new Ext.grid.RowSelectionModel({singleSelect:true}),
        stripeRows: true,
        view: new Ext.grid.GroupingView({
            forceFit:true,
            autoFill:true,
            hideGroupedColumn: true,
            enableGroupingMenu: false,
            enableNoGroups: true,
            emptyText:'No servicegroups.',
            groupTextTpl: '{text} ({[values.rs.length]} {[values.rs.length > 1 ? "Hosts" : "Host"]})',
            scrollOffset:0
        }),
        bbar: new Ext.PagingToolbar({
            pageSize: pageSize,
            store: store,
            displayInfo: true,
            displayMsg: ''
        })
    });

    // Add the grid to the panel
    tab.items.add(grid);

    // Refresh the dashboard
    centerTabPanel.doLayout();

    // Render the grid
    grid.render();

    // Load the data store
    grid.store.load({params:{start:0, limit:pageSize}});

    // Start auto refresh of the grid
    store.startAutoRefresh(npc.params.npc_portlet_refresh);

    // Stop auto refresh if the tab is closed
    var listeners = {
        destroy: function() {
            store.stopAutoRefresh();
            if (!innerTabPanel.items.length) {
                centerTabPanel.remove(outerTabId, true);
            }
        }
    };

    // Add the listener to the tab
    tab.addListener(listeners);

    grid.on('rowdblclick', sgOverviewClick);

    // Right click action
    grid.on('rowcontextmenu', npc.serviceContextMenu);

    function sgOverviewClick(grid, rowIndex, e) {
        //console.log(grid.getStore().getAt(rowIndex).json.servicegroup_object_id);
        var soi = grid.getStore().getAt(rowIndex).json.servicegroup_object_id;
        var name = grid.getStore().getAt(rowIndex).json.alias;
        npc.servicegroupGrid('servicegroupGrid-'+soi, 'Servicegroup: '+name, soi);
    }
};
