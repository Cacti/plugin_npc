Ext.onReady(function(){

    // Portlet name
    var name = 'Service Problems';

    // Portlet ID
    var id = 'service-problems-portlet';

    // Portlet URL
    var url = 'npc.php?module=services&action=getServices&p_state=not_ok&p_portlet=1';

    // Default column
    var column = 'dashcol2';

    // Default # of events to display
    var pageSize = parseInt(npc.app.params.npc_portlet_rows);

    function renderStatus(val){
        var img;
        if (val == 0) {
            img = 'recovery.png';
        } else if (val == 1) {
            img = 'warning.png';
        } else if (val == 2) {
            img = 'critical.png';
        } else if (val == 3) {
            img = 'unknown.png';
        } else if (val == -1) {
            img = 'info.png';
        }
        return String.format('<p align="center"><img src="images/nagios/{0}"></p>', img);
    }

    var store = new Ext.data.GroupingStore({
        url:url,
        autoload:true,
        reader: new Ext.data.JsonReader({
            totalProperty:'totalCount',
            root:'data'
        }, [
            'host_object_id',
            'host_name',
            'service_id',
            'service_description',
            'current_state',
            'output'
        ]),
        groupField:'host_name'
    });

    var cm = new Ext.grid.ColumnModel([{
        header:"Service",
        dataIndex:'service_description',
        width:100
    },{
        header:"Status",
        dataIndex:'current_state',
        renderer:renderStatus,
        width:45
    },{
        header:"Host",
        dataIndex:'host_name'
    },{
        header:"Plugin Output",
        dataIndex:'output',
        width:500
    }]);


    var grid = new Ext.grid.GridPanel({
        id: 'service-problems-grid',
        autoHeight:true,
        autoExpandColumn: 'service_description',
        store:store,
        cm:cm,
        sm: new Ext.grid.RowSelectionModel({singleSelect:true}),
        stripeRows: true,
        view: new Ext.grid.GroupingView({
            forceFit:true,
            autoFill:true,
            hideGroupedColumn: true,
            enableGroupingMenu: false,
            enableNoGroups: true,
            groupTextTpl: '{text}'
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

    grid.on('rowclick', npc.app.serviceGridClick);

});
