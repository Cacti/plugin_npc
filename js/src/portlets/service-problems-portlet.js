npc.portlet.serviceProblems = function(){

    // Portlet name
    var name = 'Service Problems';

    // Portlet ID
    var id = 'serviceProblems';

    // Portlet URL
    var url = 'npc.php?module=services&action=getServices&p_state=not_ok';

    // Default column
    var column = 'dashcol2';

    // Default # of events to display
    var pageSize = 10;

    var store = new Ext.data.GroupingStore({
        url:url,
        autoload:true,
        sortInfo:{field: 'service_description', direction: "ASC"},
        reader: new Ext.data.JsonReader({
            totalProperty:'totalCount',
            root:'data'
        }, [
            {name: 'host_object_id', type: 'int'},
            {name: 'service_object_id', type: 'int'},
            {name: 'service_id', type: 'int'},
            'host_name',
            'service_description',
            'acknowledgement',
            'comment',
            'output',
            {name: 'current_state', type: 'int'},
            {name: 'problem_has_been_acknowledged', type: 'int'},
            {name: 'notifications_enabled', type: 'int'},
            {name: 'active_checks_enabled', type: 'int'},
            {name: 'passive_checks_enabled', type: 'int'},
            {name: 'obsess_over_service', type: 'int'},
            {name: 'event_handler_enabled', type: 'int'},
            {name: 'flap_detection_enabled', type: 'int'},
            {name: 'is_flapping', type: 'int'}
        ]),
        groupField:'host_name'
    });

    var cm = new Ext.grid.ColumnModel([{
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
            emptyText:'No problems.',
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
    npc.addPortlet(id, name, column);

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
        store.startAutoRefresh(60);
    }

    // Double click action
    grid.on('rowdblclick', npc.serviceGridClick);
    
    // Right click action
    grid.on('rowcontextmenu', npc.serviceContextMenu);

};
