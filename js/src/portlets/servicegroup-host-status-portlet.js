npc.servicegroupHostStatusGrid = Ext.extend(Ext.ux.grid.livegrid.GridPanel, {

    filter: 'any',

    initComponent : function()
    {
        var bufferedReader = new Ext.ux.grid.livegrid.JsonReader({
            root            : 'response.value.items',
            versionProperty : 'response.value.version',
            totalProperty   : 'response.value.total_count',
            id              : 'service_object_id'
        },[
            {name: 'alias', sortType: 'string'},
            {name: 'instance_id', type: 'int', sortType: 'int'},
            {name: 'servicegroup_object_id', type: 'int', sortType: 'int'},
            {name: 'down', type: 'int', sortType: 'int'},
            {name: 'unreachable', type: 'int', sortType: 'int'},
            {name: 'up', type: 'int', sortType: 'int'},
            {name: 'pending', type: 'int', sortType: 'int'}
          ]
        );

        this.store = new Ext.ux.grid.livegrid.Store({
            autoLoad   : true,
            bufferSize : 100,
            reader     : bufferedReader,
            sortInfo   : {field: 'alias', direction: "ASC"},
            url        : 'npc.php?module=servicegroups&action=getHostStatusPortlet'
        });

        this.selModel = new Ext.ux.grid.livegrid.RowSelectionModel();

        this.view = new Ext.ux.grid.livegrid.GridView({
            nearLimit : 30
            ,forceFit:true
            ,autoFill:true
            ,emptyText:'No hostgroups.'
            ,loadMask: {
                msg: 'Please wait...'
            }
        });

        this.bbar = new Ext.ux.grid.livegrid.Toolbar({
            view        : this.view,
            displayInfo : true
        });

        npc.servicegroupHostStatusGrid.superclass.initComponent.call(this);
    }

});



npc.portlet.servicegroupHostStatus = function(){

    // Portlet name
    var name = 'Servicegroup: Host Status';

    // Portlet ID
    var id = 'servicegroupHostStatus';

    // Default column
    var column = 'dashcol2';

    // Get the portlet height
    var height = Ext.state.Manager.get(id).height;
    height = (height > 150) ? height : 150;

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
        renderer: npc.renderStatusBg
    },{
        id: 'sgHSUNREACHABLE',
        header:"Unreachable",
        dataIndex:'unreachable',
        align:'center',
        width:40,
        renderer: npc.renderStatusBg
    },{
        id: 'sgHSUP',
        header:"Up",
        dataIndex:'up',
        align:'center',
        width:20,
        renderer: npc.renderStatusBg
    },{
        id: 'sgHSPENDING',
        header:"Pending",
        dataIndex:'pending',
        align:'center',
        width:40,
        renderer: npc.renderStatusBg
    }]);

    // Setup the grid
    var grid = new npc.servicegroupHostStatusGrid({
        id: 'servicegroup-host-status-grid',
        height:height,
        autoExpandColumn: 'alias',
        cm:cm,
        sm: new Ext.grid.RowSelectionModel({singleSelect:true}),
        stripeRows: true
    });

    // Create a portlet to hold the grid
    npc.addPortlet(id, name, column);

    // Add the grid to the portlet
    Ext.getCmp(id).items.add(grid);

    // Refresh the dashboard
    Ext.getCmp('centerTabPanel').doLayout();

    // Render the grid
    grid.render();

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
        grid.store.startAutoRefresh(npc.params.npc_portlet_refresh);
    }

    grid.on('rowdblclick', sgClick);

    function sgClick(grid, rowIndex, e) {
        var soi = grid.getStore().getAt(rowIndex).json.servicegroup_object_id;
        var name = grid.getStore().getAt(rowIndex).json.alias;
        npc.servicegroupGrid('servicegroupGrid-'+soi, 'Servicegroup: '+name, soi);
    }
};
