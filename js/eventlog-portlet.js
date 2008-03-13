npc.app.portlet.eventLog = function(){

    // Portlet name
    var name = 'Event Log';

    // Portlet ID
    var id = 'eventLog';

    // Portlet URL
    var url = 'npc.php?module=logentries&action=getLogs';

    // Default column
    var column = 'dashcol2';

    // Default # of events to display
    var pageSize = 5;

    var store = new Ext.data.JsonStore({
        url:url,
        totalProperty:'totalCount',
        root:'data',
        fields:[
            'logentry_id',
            {name: 'entry_time', type: 'date', dateFormat: 'Y-m-d H:i:s'},
            'logentry_data'
        ],
        autoload:true
    });

    var cm = new Ext.grid.ColumnModel([{
        dataIndex:'logentry_data',
        renderer: npc.app.renderEventIcon,
        width:25
    },{
        header:"Date",
        dataIndex:'entry_time',
        width:125,
        renderer:npc.app.formatDate,
        align:'left'
    }, {
        header:"Log Entry",
        dataIndex:'logentry_data',
        width:600,
        align:'left'
    }]);

    var grid = new Ext.grid.GridPanel({
        id: 'event-log-grid',
        autoHeight:true,
        autoWidth:true,
        store:store,
        cm:cm,
        autoExpandColumn:'logentry_data',
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
};
