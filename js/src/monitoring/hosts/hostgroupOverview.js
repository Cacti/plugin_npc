npc.hostgroupOverview = function(){

    // Panel title
    var title = 'Hostgroup Overview';

    // Panel ID
    var id = 'hostgroupOverview-tab';

    var gridId = id + '-grid';

    // Portlet URL
    var url = 'npc.php?module=hostgroups&action=getOverview';

    // Set the number of rows to display and the refresh rate
    var state = Ext.state.Manager.get(gridId);
    var pageSize = (state && state.rows) ? state.rows : 15;
    var refresh = (state && state.refresh) ? state.refresh : 60;

    var outerTabId = 'hosts-tab';

    npc.addCenterNestedTab(outerTabId, 'Hosts');

    var centerTabPanel = Ext.getCmp('centerTabPanel');

    var innerTabId = 'hosts-tab-inner-panel';

    var innerTabPanel = Ext.getCmp(innerTabId);

    var tab = Ext.getCmp(id);

    if (tab) {
        innerTabPanel.setActiveTab(tab);
        return;
    } else {
        innerTabPanel.add({
            id: id,
            title: title,
            height:600,
            layout: 'fit',
            closable: true
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
            {name: 'hostgroup_object_id', type: 'int'},
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
        header:"Hostgroup",
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
        id: 'hgHostTotalsCRITICAL',
        header:"Critical",
        dataIndex:'critical',
        align:'center',
        width:40,
        renderer: npc.renderStatusBg
    },{
        id: 'hgHostTotalsWARNING',
        header:"Warning",
        dataIndex:'warning',
        align:'center',
        width:45,
        renderer: npc.renderStatusBg
    },{
        id: 'hgHostTotalsUNKNOWN',
        header:"Unknown",
        dataIndex:'unknown',
        align:'center',
        width:45,
        renderer: npc.renderStatusBg
    },{
        id: 'hgHostTotalsOK',
        header:"Ok",
        dataIndex:'ok',
        align:'center',
        width:25,
        renderer: npc.renderStatusBg
    },{
        id: 'hgHostTotalsPENDING',
        header:"Pending",
        dataIndex:'pending',
        align:'center',
        width:45,
        renderer: npc.renderStatusBg
    }]);


    var grid = new Ext.grid.GridPanel({
        id: 'hostgroup-overview-portlet-grid',
        height:800,
        layout: 'fit',
        autoExpandColumn: 'host_name',
        autoScroll: true,
        store:store,
        cm:cm,
        sm: new Ext.grid.RowSelectionModel({singleSelect:true}),
        stripeRows: true,
        listeners: {
            // Intercept the state save to add our custom attributes
            beforestatesave: function(o, s) {
                s.rows = pageSize;
                s.refresh = refresh
                Ext.state.Manager.set(gridId, s);
                return false;
            }
        },
        view: new Ext.grid.GroupingView({
            forceFit:true,
            autoFill:true,
            hideGroupedColumn: true,
            enableGroupingMenu: false,
            enableNoGroups: true,
            emptyText:'No hostgroups.',
            groupTextTpl: '{text} ({[values.rs.length]} {[values.rs.length > 1 ? "Hosts" : "Host"]})'
        }),
        bbar: new Ext.PagingToolbar({
            pageSize: pageSize,
            store: store,
            displayInfo: true,
            displayMsg: '',
            items: npc.setRefreshCombo(gridId, store, state),
            plugins: new Ext.ux.Andrie.pPageSize({ gridId: gridId })
        })
    });

    // Add the grid to the panel
    tab.add(grid);

    // Refresh the dashboard
    centerTabPanel.doLayout();

    // Render the grid
    grid.render();

    // Load the data store
    grid.store.load({params:{start:0, limit:pageSize}});

    // Start auto refresh of the grid
    store.startAutoRefresh(refresh);

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

    grid.on('rowdblclick', hgOverviewClick);

    // Right click action
    grid.on('rowcontextmenu', npc.hostContextMenu);

    function hgOverviewClick(grid, rowIndex, e) {
        var hoi = grid.getStore().getAt(rowIndex).json.hostgroup_object_id;
        var name = grid.getStore().getAt(rowIndex).json.alias;
        npc.hostgroupGrid('hostgroupGrid-'+hoi, 'Hostgroup: '+name, hoi);
    }
};
