npc.portlet.hostSummary = function(){

    // Portlet name
    var name = 'Host Status Summary';

    // Portlet ID
    var id = 'hostSummary';

    // Portlet URL
    var url = 'npc.php?module=hosts&action=summary';

    // Default column
    var column = 'dashcol1';

    // Refresh rate
    var refresh = npc.params.npc_portlet_refresh;

    var store = new Ext.data.JsonStore({
        url:url,
        totalProperty:'totalCount',
        root:'data',
        fields:['down', 'unreachable', 'up', 'pending'],
        autoload:true
    });

    var cm = new Ext.grid.ColumnModel([{
        id: 'hostTotalsDOWN',
        header:"Down",
        dataIndex:'down',
        renderer: npc.renderStatusBg,
        width:100,
        align:'center'
    },{
        id: 'hostTotalsUNREACHABLE',
        header:"Unreachable",
        dataIndex:'unreachable',
        renderer: npc.renderStatusBg,
        width:100,
        align:'center'
    }, {
        id: 'hostTotalsUP',
        header:"Up",
        dataIndex:'up',
        renderer: npc.renderStatusBg,
        width:100,
        align:'center'
    }, {
        id: 'hostTotalsPENDING',
        header:"Pending",
        dataIndex:'pending',
        renderer: npc.renderStatusBg,
        width:100,
        align:'center'
    }]);

    var grid = new Ext.grid.GridPanel({
        id:'host-status-summary-grid',
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
