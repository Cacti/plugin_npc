npc.portlet.serviceProblems = function(){

    // Portlet name
    var name = 'Service Problems';

    // Portlet ID
    var id = 'serviceProblems';

    // Default column
    var column = 'dashcol2';

    // Get the number of rows to display
    var rows = Ext.state.Manager.get(id).rows;

    // Refresh rate
    var refresh = Ext.state.Manager.get(id).refresh;

    var cm = new Ext.grid.ColumnModel([{
        header:"Host",
        dataIndex:'host_name',
        hidden:false
    },{
        header:"Host Alias",
        dataIndex:'host_alias',
        hidden:true
    },{
        header:"Service",
        dataIndex:'service_description',
        renderer:npc.renderExtraIcons,
        width:100
    },{
        header:"Status",
        dataIndex:'current_state',
        renderer:npc.serviceStatusImage,
        width:45
    },{
        header:"Plugin Output",
        dataIndex:'output',
        width:500
    }]);

    var grid = new npc.servicesGrid({
        id: id + '-grid'
        ,height:150
        ,filter: 'not_ok'
        ,enableDragDrop : false
        ,cm: cm
        ,stripeRows: true
        ,loadMask: {
            msg: 'Loading...'
        }
    });

    // Create a portlet to hold the grid
    npc.addPortlet(id, name, column);

    // Add the grid to the portlet
    Ext.getCmp(id).items.add(grid);

    // Refresh the dashboard
    Ext.getCmp('centerTabPanel').doLayout();

    // Start auto refresh of the grid
    if (Ext.getCmp(id).isVisible()) {
        doAutoRefresh();
    }

    // Add listeners to the portlet to stop and start auto refresh
    // depending on wether or not the portlet is visible.
    var listeners = {
        hide: function() {
            grid.store.stopAutoRefresh();
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
        grid.store.startAutoRefresh(refresh);
    }

    // Double click action
    grid.on('rowdblclick', npc.serviceGridClick);
    
    // Right click action
    grid.on('rowcontextmenu', npc.serviceContextMenu);

};
