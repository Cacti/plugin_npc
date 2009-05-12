npc.portlet.hostgroupServiceStatus = function(){

    // Portlet name
    var name = 'Hostgroup: Service Status';

    // Portlet ID
    var id = 'hostgroupServiceStatus';

    // Portlet URL
    var url = 'npc.php?module=hostgroups&action=getHostgroupServiceStatus';

    // Default column
    var column = 'dashcol2';

    // Default # of events to display
    var pageSize = 10;

    // Setup the data store
    var store = new Ext.data.JsonStore({
        url:url,
        autoload:true,
        sortInfo:{field: 'alias', direction: "ASC"},
        totalProperty:'totalCount',
        root:'data',
        fields:[
            'hostgroup_name',
            'alias',
            {name: 'instance_id', type: 'int'},
            {name: 'hostgroup_object_id', type: 'int'},
            {name: 'critical', type: 'int'},
            {name: 'warning', type: 'int'},
            {name: 'unknown', type: 'int'},
            {name: 'ok', type: 'int'},
            {name: 'pending', type: 'int'}
        ]
    });

    // Setup the column model
    var cm = new Ext.grid.ColumnModel([{
        header:"Hostgroup",
        dataIndex:'alias',
        sortable:true
    },{
        id: 'hgSSCRITICAL',
        header:"Critical",
        dataIndex:'critical',
        align:'center',
        width:40,
        renderer: npc.renderStatusBg
    },{
        id: 'hgSSWARNING',
        header:"Warning",
        dataIndex:'warning',
        align:'center',
        width:40,
        renderer: npc.renderStatusBg
    },{
        id: 'hgSSUNKNOWN',
        header:"Unknown",
        dataIndex:'unknown',
        align:'center',
        width:40,
        renderer: npc.renderStatusBg
    },{
        id: 'hgSSOK',
        header:"Ok",
        dataIndex:'ok',
        align:'center',
        width:20,
        renderer: npc.renderStatusBg
    },{
        id: 'hgSSPENDING',
        header:"Pending",
        dataIndex:'pending',
        align:'center',
        width:40,
        renderer: npc.renderStatusBg
    }]);

    // Setup the grid
    var grid = new Ext.grid.GridPanel({
        id: 'hostgroup-service-status-grid',
        autoHeight:true,
        autoExpandColumn: 'alias',
        store:store,
        cm:cm,
        sm: new Ext.grid.RowSelectionModel({singleSelect:true}),
        stripeRows: true,
        view: new Ext.grid.GridView({
            forceFit:true,
            autoFill:true,
            emptyText:'No hostgroups.',
            scrollOffset:0
        }),
        bbar: new Ext.PagingToolbar({
            pageSize: pageSize,
            store: store,
            displayInfo: true,
            displayMsg: ''
        })
    });

    // Create a portlet to hold the grid
    npc.addPortlet(id, name, column);

    // Add the grid to the portlet
    Ext.getCmp(id).items.add(grid);

    // Refresh the dashboard
    Ext.getCmp('centerTabPanel').doLayout();

    // Render the grid
    grid.render();

    // Load the data store
    store.load({params:{start:0, limit:pageSize}});

    // Start auto refresh of the grid
    if (Ext.getCmp(id).isVisible()) {
        doAutoRefresh();
    }

    // Add listeners to the portlet to stop and start auto refresh
    // depending on wether or not the portlet is visible.
    var listeners = {
        hide: function() {
            store.stopAutoRefresh();
        },
        show: function() {
            doAutoRefresh();
        },
        collapse: function() {
            store.stopAutoRefresh();
        },
        expand: function() {
            doAutoRefresh();
        }
    };

    Ext.getCmp(id).addListener(listeners);

    function doAutoRefresh() {
        store.startAutoRefresh(npc.params.npc_portlet_refresh);
    }

    grid.on('rowdblclick', hgClick);

    // Right click action
    grid.on('rowcontextmenu', npc.hostgroupContextMenu);

    function hgClick(grid, rowIndex, e) {
        var hoi = grid.getStore().getAt(rowIndex).json.hostgroup_object_id;
        var name = grid.getStore().getAt(rowIndex).json.alias;
        npc.hostgroupGrid('hostgroupGrid-'+hoi, 'Hostgroup: '+name, hoi);
    }
};
