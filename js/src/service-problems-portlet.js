npc.app.portlet.serviceProblems = function(){

    // Portlet name
    var name = 'Service Problems';

    // Portlet ID
    var id = 'serviceProblems';

    // Portlet URL
    var url = 'npc.php?module=services&action=getServices&p_state=not_ok';

    // Default column
    var column = 'dashcol2';

    // Default # of events to display
    var pageSize = parseInt(npc.app.params.npc_portlet_rows);

    var store = new Ext.data.GroupingStore({
        url:url,
        autoload:true,
        sortInfo:{field: 'service_description', direction: "ASC"},
        reader: new Ext.data.JsonReader({
            totalProperty:'totalCount',
            root:'data'
        }, [
            'host_object_id',
            'host_name',
            'service_object_id',
            'service_id',
            'service_description',
            'acknowledgement',
            'comment',
            'current_state',
            'output',
            {name: 'problem_has_been_acknowledged', type: 'int'},
            {name: 'notifications_enabled', type: 'int'},
            {name: 'active_checks_enabled', type: 'int'},
            {name: 'passive_checks_enabled', type: 'int'},
            {name: 'is_flapping', type: 'int'}
        ]),
        groupField:'host_name'
    });

    var cm = new Ext.grid.ColumnModel([{
        header:"Service",
        dataIndex:'service_description',
        renderer:npc.app.renderExtraIcons,
        width:100
    },{
        header:"Status",
        dataIndex:'current_state',
        renderer:npc.app.serviceStatusImage,
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
            groupTextTpl: '{text}',
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

    grid.on('rowdblclick', npc.app.serviceGridClick);

};
