npc.app.portlet.servicegroupHostStatus = function(){

    // Portlet name
    var name = 'Servicegroup: Host Status';

    // Portlet ID
    var id = 'servicegroupHostStatus';

    // Portlet URL
    var url = 'npc.php?module=servicegroups&action=getHostStatusPortlet';

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
            'alias',
            {name: 'instance_id', type: 'int'},
            {name: 'servicegroup_object_id', type: 'int'},
            {name: 'down', type: 'int'},
            {name: 'unreachable', type: 'int'},
            {name: 'up', type: 'int'},
            {name: 'pending', type: 'int'}
        ]
    });

    // Setup the column model
    var cm = new Ext.grid.ColumnModel([{
        header:"Servicegroup",
        dataIndex:'alias',
        sortable:true
    },{
        id: 'sgHSDOWN',
        header:"Down",
        dataIndex:'down',
        align:'center',
        width:40,
        renderer: npc.app.renderStatusBg
    },{
        id: 'sgHSUNREACHABLE',
        header:"Unreachable",
        dataIndex:'unreachable',
        align:'center',
        width:40,
        renderer: npc.app.renderStatusBg
    },{
        id: 'sgHSUP',
        header:"Up",
        dataIndex:'up',
        align:'center',
        width:20,
        renderer: npc.app.renderStatusBg
    },{
        id: 'sgHSPENDING',
        header:"Pending",
        dataIndex:'pending',
        align:'center',
        width:40,
        renderer: npc.app.renderStatusBg
    }]);

    // Setup the grid
    var grid = new Ext.grid.GridPanel({
        id: 'servicegroup-host-status-grid',
        autoHeight:true,
        autoExpandColumn: 'alias',
        store:store,
        cm:cm,
        sm: new Ext.grid.RowSelectionModel({singleSelect:true}),
        stripeRows: true,
        view: new Ext.grid.GridView({
             forceFit:true,
             autoFill:true,
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
    npc.app.addPortlet(id, name, column);

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
        store.startAutoRefresh(npc.app.params.npc_portlet_refresh);
    }

    grid.on('rowclick', sgClick);

    function sgClick(grid, rowIndex, e) {
        var soi = grid.getStore().getAt(rowIndex).json.servicegroup_object_id;
        var name = grid.getStore().getAt(rowIndex).json.alias;
        npc.app.servicegroupGrid('servicegroupGrid-'+soi, 'Servicegroup: '+name, soi);
    }
};
