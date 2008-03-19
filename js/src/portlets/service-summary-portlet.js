npc.portlet.serviceSummary = function(){

    // Portlet name
    var name = 'Service Status Summary';

    // Portlet ID
    var id = 'serviceSummary';

    // Portlet URL
    var url = 'npc.php?module=services&action=summary';

    // Default column
    var column = 'dashcol1';

    // Refresh rate
    var refresh = npc.params.npc_portlet_refresh;

    var store = new Ext.data.JsonStore({
        url:url,
        totalProperty:'totalCount',
        root:'data',
        fields:['critical', 'warning', 'unknown', 'ok', 'pending'],
        autoload:true
    });

    var cm = new Ext.grid.ColumnModel([{
        id: 'serviceTotalsCRITICAL',
        header:"Critical",
        dataIndex:'critical',
        renderer: npc.renderStatusBg,
        width:80,
        align:'center'
    },{
        id: 'serviceTotalsWARNING',
        header:"Warning",
        dataIndex:'warning',
        renderer: npc.renderStatusBg,
        width:80,
        align:'center'
    }, {
        id: 'serviceTotalsUNKNOWN',
        header:"Unknown",
        dataIndex:'unknown',
        renderer: npc.renderStatusBg,
        width:80,
        align:'center'
    }, {
        id: 'serviceTotalsOK',
        header:"Ok",
        dataIndex:'ok',
        renderer: npc.renderStatusBg,
        width:80,
        align:'center'
    }, {
        id: 'serviceTotalsPENDING',
        header:"Pending",
        dataIndex:'pending',
        renderer: npc.renderStatusBg,
        width:80,
        align:'center'
    }]);

    var grid = new Ext.grid.GridPanel({
        id: id + '-grid',
        autoHeight:true,
        width:400,
        store:store,
        cm:cm,
        view: new Ext.grid.GridView({
             forceFit:true,
             autoFill:true,
             scrollOffset:0
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
    grid.store.load();

    // Start auto refresh of the grid
    if (Ext.getCmp(id).isVisible()) {
        store.startAutoRefresh(refresh);
    }

    // Add listeners to the portlet to stop and start auto refresh
    // depending on wether or not the portlet is visible.
    var listeners = {
        hide: function() {
            store.stopAutoRefresh();
        },
        show: function() {
            store.startAutoRefresh(refresh);
        }
    };

    Ext.getCmp(id).addListener(listeners);
};
