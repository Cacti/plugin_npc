npc.portlet.hostProblems = function(){

    // Portlet name
    var name = 'Host Problems';

    // Portlet ID
    var id = 'hostProblems';

    // Portlet URL
    var url = 'npc.php?module=hosts&action=getHosts&p_state=not_ok';

    // Default column
    var column = 'dashcol2';

    // Default # of events to display
    var pageSize = 5;


    var store = new Ext.data.JsonStore({
        url:url,
        autoload:true,
        sortInfo:{field: 'host_name', direction: "ASC"},
        totalProperty:'totalCount',
        root:'data',
        fields: [
            {name: 'host_object_id', type: 'int'},
            'host_name',
            'alias',
            'comment',
            {name: 'current_state', type: 'int'},
            'output',
            'acknowledgement',
            {name: 'problem_has_been_acknowledged', type: 'int'},
            {name: 'notifications_enabled', type: 'int'},
            {name: 'active_checks_enabled', type: 'int'},
            {name: 'passive_checks_enabled', type: 'int'},
            {name: 'is_flapping', type: 'int'}
        ]
    });

    var cm = new Ext.grid.ColumnModel([{
        header:"Host",
        dataIndex:'host_name',
        sortable:true,
        renderer:npc.renderExtraIcons,
        width:100
    },{
        header:"Alias",
        dataIndex:'alias',
        hidden:true,
        width:100
    },{
        header:"Status",
        dataIndex:'current_state',
        renderer:npc.hostStatusImage,
        align:'center',
        width:50
    },{
        header:"Plugin Output",
        dataIndex:'output',
        width:400
    }]);

    var grid = new Ext.grid.GridPanel({
        id: id + '-grid',
        autoHeight:true,
        autoExpandColumn: 'host_name',
        store:store,
        autoScroll: true,
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
            displayInfo: true
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
    //grid.store.load({params:{start:0, limit:pageSize}});
    store.load({params:{start:0, limit:10}});

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

    grid.on('rowdblclick', npc.hostGridClick);

};
