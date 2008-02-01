Ext.onReady(function(){

    // Portlet name
    var name = 'Monitoring Performance';

    // Portlet ID
    var id = 'mon-perf-portlet';

    // Portlet URL
    var url = 'npc.php?module=nagios&action=checkPerf&p_resolution=7';

    // Default column
    var column = 'dashcol2';

    var store = new Ext.data.JsonStore({
        url:url,
        totalProperty:'totalCount',
        root:'data',
        fields:[
            'name',
            'min',
            'max',
            'avg',
        ],
        autoload:true
    });

    var cm = new Ext.grid.ColumnModel([{
        dataIndex:'name',
        width:100
    },{
        header:"Min",
        dataIndex:'min',
        width:50
    },{
        header:"Max",
        dataIndex:'max',
        width:50
    },{
        header:"Avg",
        dataIndex:'avg',
        width:50
    }]);

    var grid = new Ext.grid.GridPanel({
        id: id + '-grid',
        autoExpandColumn: 'name',
        autoHeight: true,
        store:store,
        cm:cm,
        stripeRows: true,
        view: new Ext.grid.GridView({
            forceFit:true,
            autoFill:true,
            scrollOffset:0
        })
    });

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

    function doAutoRefresh() {
        store.startAutoRefresh(npc.app.params.npc_portlet_refresh);
    };

    // Create a portlet to hold the grid
    npc.app.addPortlet(id, name, column);

    // Add the grid to the portlet
    Ext.getCmp(id).items.add(grid);

    // Refresh the dashboard
    Ext.getCmp('centerTabPanel').doLayout();

    // Render the grid
    grid.render();

    // Load the data store
    //grid.store.load({params:{start:0, limit:pageSize}});
    store.load();

    // Start auto refresh of the grid
    if (Ext.getCmp(id).isVisible()) {
        doAutoRefresh();
    }

    Ext.getCmp(id).addListener(listeners);
});
