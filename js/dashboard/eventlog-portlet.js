Ext.onReady(function(){

    // Portlet name
    var name = 'Event Log';

    // Portlet ID
    var id = 'eventlog-portlet';

    // Portlet URL
    var url = 'npc.php?module=eventlog&action=getLogEntries';

    // Default column
    var column = 'dashcol2';

    // Default # of events to display
    var pageSize = parseInt(npc.app.params.npc_portlet_rows);

    function renderIcon(val){
        if (val.match(/SERVICE ALERT:/) && val.match(/WARNING/)) {
            return String.format('<img src="images/icons/error.png">');
        } else if (val.match(/SERVICE ALERT:/) && val.match(/OK/)) {
            return String.format('<img src="images/icons/accept.png">');
        } else if (val.match(/SERVICE ALERT:/) && val.match(/CRITICAL/)) {
            return String.format('<img src="images/icons/exclamation.png">');
        } else if (val.match(/LOG ROTATION:/)) {
            return String.format('<img src="images/icons/arrow_rotate_clockwise.png">');
        } else if (val.match(/ NOTIFICATION:/)) {
            return String.format('<img src="images/icons/transmit.png">');
        } else if (val.match(/HOST ALERT:/) && (val.match(/;RECOVERY;/) || val.match(/;UP;/))) {
            return String.format('<img src="images/icons/accept.png">');
        } else if (val.match(/Finished daemonizing.../)) {
            return String.format('<img src="images/icons/arrow_up.png">');
        } else if (val.match(/ shutting down.../)) {
            return String.format('<img src="images/icons/cancel.png">');
        } else if (val.match(/Successfully shutdown/)) {
            return String.format('<img src="images/icons/stop.png">');
        } else if (val.match(/ restarting.../)) {
            return String.format('<img src="images/icons/arrow_refresh.png">');
        }
        return String.format('<img src="images/icons/information.png">');
    }

    function renderDate(val) {
        return String.format(val.dateFormat(npc.app.params.npc_date_format + ' ' + npc.app.params.npc_time_format));
    }

    var store = new Ext.data.JsonStore({
        url:url,
        totalProperty:'totalCount',
        root:'data',
        fields:[
            'logentry_id',
            {name: 'entry_time', type: 'date', dateFormat: 'timestamp'},
            'logentry_data'
        ],
        autoload:true
    });

    var cm = new Ext.grid.ColumnModel([{
        dataIndex:'logentry_data',
        renderer: renderIcon,
        width:25
    },{
        header:"Date",
        dataIndex:'entry_time',
        width:125,
        renderer: renderDate,
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
            pageSize: 10,
            store: store,
            displayInfo: true,
            displayMsg: 'Displaying events {0} - {1} of {2}',
            emptyMsg: "No events to display"
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
        store.startAutoRefresh(npc.app.params.npc_portlet_refresh);
    }
});
