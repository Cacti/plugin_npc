npc.portlet.eventLog = function(){

    // Portlet name
    var name = 'Event Log';

    // Portlet ID
    var id = 'eventLog';

    // Default column
    var column = 'dashcol2';

    // Get the portlet height
    var height = Ext.state.Manager.get(id).height;
    height = (height > 150) ? height : 150;

    var cm = new Ext.grid.ColumnModel([{
        dataIndex:'logentry_data',
        renderer: npc.renderEventIcon,
        width:25
    },{
        header:"Date",
        dataIndex:'entry_time',
        width:125,
        renderer:npc.formatDate,
        align:'left'
    }, {
        header:"Log Entry",
        dataIndex:'logentry_data',
        width:600,
        align:'left'
    }]);

    var grid = new npc.eventLogGrid({
        id: id + '-grid'
        ,height:height
        ,enableDragDrop : false
        ,cm : cm
        ,stripeRows: true
        ,loadMask       : {
            msg : 'Loading...'
        }
        ,plugins:[new Ext.ux.grid.Search({
            mode:'remote',
            iconCls:false
        })]
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
            grid.store.stopAutoRefresh();
        },
        expand: function() {
            doAutoRefresh();
        }
    };

    Ext.getCmp(id).addListener(listeners);

    function doAutoRefresh() {
       grid. store.startAutoRefresh(npc.params.npc_portlet_refresh);
    }
};
