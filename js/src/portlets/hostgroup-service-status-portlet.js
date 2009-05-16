npc.hostgroupServiceStatusGrid = Ext.extend(Ext.ux.grid.livegrid.GridPanel, {

    filter: 'any',

    initComponent : function()
    {
        var bufferedReader = new Ext.ux.grid.livegrid.JsonReader({
            root            : 'response.value.items',
            versionProperty : 'response.value.version',
            totalProperty   : 'response.value.total_count',
            id              : 'service_object_id'
        },[
            {name: 'hostgroup_name', sortType: 'string'},
            {name: 'alias', sortType: 'string'},
            {name: 'instance_id', type: 'int', sortType: 'int'},
            {name: 'hostgroup_object_id', type: 'int', sortType: 'int'},
            {name: 'critical', type: 'int', sortType: 'int'},
            {name: 'warning', type: 'int', sortType: 'int'},
            {name: 'unknown', type: 'int', sortType: 'int'},
            {name: 'ok', type: 'int', sortType: 'int'},
            {name: 'pending', type: 'int', sortType: 'int'}
          ]
        );

        this.store = new Ext.ux.grid.livegrid.Store({
            autoLoad   : true,
            bufferSize : 100,
            reader     : bufferedReader,
            sortInfo   : {field: 'alias', direction: "ASC"},
            url        : 'npc.php?module=hostgroups&action=getHostgroupServiceStatus'
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

        npc.hostgroupServiceStatusGrid.superclass.initComponent.call(this);
    }

});

npc.portlet.hostgroupServiceStatus = function(){

    // Portlet name
    var name = 'Hostgroup: Service Status';

    // Portlet ID
    var id = 'hostgroupServiceStatus';

    // Default column
    var column = 'dashcol2';

    // Get the portlet height
    var height = Ext.state.Manager.get(id).height;
    height = (height > 150) ? height : 150;

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
    var grid = new npc.hostgroupServiceStatusGrid({
        id: 'hostgroup-service-status-grid',
        height:height,
        autoExpandColumn: 'alias',
        cm:cm,
        sm: new Ext.grid.RowSelectionModel({singleSelect:true}),
        stripeRows: true,
        loadMask: {
            msg: 'Loading...'
        }
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

    grid.on('rowdblclick', hgClick);

    // Right click action
    grid.on('rowcontextmenu', npc.hostgroupContextMenu);

    function hgClick(grid, rowIndex, e) {
        var hoi = grid.getStore().getAt(rowIndex).json.hostgroup_object_id;
        var name = grid.getStore().getAt(rowIndex).json.alias;
        npc.hostgroupGrid('hostgroupGrid-'+hoi, 'Hostgroup: '+name, hoi);
    }
};
