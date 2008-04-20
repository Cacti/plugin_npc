npc.hosts = function(title, filter){

    // Panel ID
    var id = title.replace(/[-' ']/g,'') + '-tab';

    var gridId = id + '-grid';

    // Grid URL
    var url = 'npc.php?module=hosts&action=getHosts&p_state=' + filter;

    // Set the number of rows to display and the refresh rate
    var state = Ext.state.Manager.get(gridId);
    var pageSize = (state && state.rows) ? state.rows : 15;
    var refresh = (state && state.refresh) ? state.refresh : 60;

    var outerTabId = 'hosts-tab';

    npc.addCenterNestedTab(outerTabId, 'Hosts');

    var centerTabPanel = Ext.getCmp('centerTabPanel');

    var innerTabId = 'hosts-tab-inner-panel';

    var innerTabPanel = Ext.getCmp(innerTabId);

    var tab = Ext.getCmp(id);

    if (tab) {
        innerTabPanel.setActiveTab(tab);
        return;
    } else {
        innerTabPanel.add({ 
            id: id, 
            title: title, 
            deferredRender:false,
            height:600,
            layout: 'fit',
            closable: true
        }).show(); 
        innerTabPanel.setActiveTab(tab); 
        tab = Ext.getCmp(id); 
    }

    function renderAttempt(val, p, record){
        return String.format('{0}/{1}', val, record.data.max_check_attempts);
    }

    function renderGraphIcon(val, p, record){
        var icon = '';
        if (val) {
            icon = '<img src="images/icons/chart_bar.png">';
        }
        return String.format('{0}', icon);
    }

    var store = new Ext.data.JsonStore({
        url:url,
        autoload:true,
        sortInfo:{field: 'host_name', direction: "ASC"},
        totalProperty:'totalCount',
        root:'data',
        fields: [
            {name: 'host_object_id', type: 'int'},
            'host_name',
            'alias',
            'comment',
            {name: 'service_count', type: 'int'},
            {name: 'current_state', type: 'int'},
            'current_check_attempt',
            'max_check_attempts',
            'output',
            'acknowledgement',
            {name: 'local_graph_id', type: 'int'},
            {name: 'last_check', type: 'date', dateFormat: 'Y-m-d H:i:s'},
            {name: 'next_check', type: 'date', dateFormat: 'Y-m-d H:i:s'},
            {name: 'last_state_change', type: 'date', dateFormat: 'Y-m-d H:i:s'},
            {name: 'problem_has_been_acknowledged', type: 'int'},
            {name: 'notifications_enabled', type: 'int'},
            {name: 'active_checks_enabled', type: 'int'},
            {name: 'passive_checks_enabled', type: 'int'},
            {name: 'is_flapping', type: 'int'}
        ]
    });

    var cm = new Ext.grid.ColumnModel([{
        header:"Host",
        dataIndex:'host_name',
        sortable:true,
        renderer:npc.renderExtraIcons,
        width:100
    },{
        header:"Alias",
        dataIndex:'alias',
        hidden:true,
        width:100
    },{
        header:"Status",
        dataIndex:'current_state',
        renderer:npc.hostStatusImage,
        align:'center',
        width:30
    },{
        header:"Graph",
        dataIndex:'local_graph_id',
        renderer:renderGraphIcon,
        width:30
    },{
        header:"Last Check",
        dataIndex:'last_check',
        renderer: npc.formatDate,
        width:100
    },{
        header:"Next Check",
        dataIndex:'next_check',
        renderer: npc.formatDate,
        width:100
    },{
        header:"Duration",
        dataIndex:'last_state_change',
        hidden:true,
        renderer: npc.getDuration,
        width:110
    },{
        header:"Attempt",
        dataIndex:'current_check_attempt',
        renderer: renderAttempt,
        hidden:true,
        align:'center',
        width:50
    },{
        header:"Services",
        dataIndex:'service_count',
        hidden:true,
        align:'center',
        width:50
    },{
        header:"Plugin Output",
        dataIndex:'output',
        width:400
    }]);

    var grid = new Ext.grid.GridPanel({
        id: gridId,
        height:800,
        layout: 'fit',
        autoExpandColumn: 'host_name',
        store:store,
        autoScroll: true,
        listeners: {
            // Intercept the state save to add our custom attributes
            beforestatesave: function(o, s) {
                s.rows = pageSize;
                s.refresh = refresh
                Ext.state.Manager.set(gridId, s);
                return false;
            }
        },
        cm:cm,
        sm: new Ext.grid.RowSelectionModel({singleSelect:true}),
        stripeRows: true,
        view: new Ext.grid.GridView({
            forceFit:true,
            autoFill:true,
            emptyText:'No hosts.',
            scrollOffset:0
        }),
        bbar: new Ext.PagingToolbar({
            pageSize: pageSize,
            store: store,
            displayInfo: true,
            items: npc.setRefreshCombo(gridId, store, state),
            plugins: new Ext.ux.Andrie.pPageSize({ gridId: gridId })
        }),
        plugins:[new Ext.ux.grid.Search({
            mode:'remote',
            iconCls:false,
            disableIndexes:[
                'last_check', 
                'next_check', 
                'local_graph_id', 
                'service_count', 
                'last_state_change', 
                'current_check_attempt', 
                'current_state'
            ]
        })]
    });

    // Add the grid to the panel
    tab.add(grid);

    // Refresh the dashboard
    centerTabPanel.doLayout();

    // Render the grid
    grid.render();

    // Load the data store
    grid.store.load({params:{start:0, limit:pageSize}});

    // Start auto refresh of the grid
    store.startAutoRefresh(refresh);

    // Stop auto refresh if the tab is closed
    var listeners = {
        destroy: function() {
            store.stopAutoRefresh();
            if (!innerTabPanel.items.length) {
                centerTabPanel.remove(outerTabId, true);
            }
        }
    };

    // Add the listener to the tab
    tab.addListener(listeners);

    grid.on('rowdblclick', npc.hostGridClick);

    // Right click action
    grid.on('rowcontextmenu', npc.hostContextMenu);

    // If the graph icon is clicked popup the associated graph
    grid.on('cellclick', function(grid, rowIndex, columnIndex) {
        var record = grid.getStore().getAt(rowIndex);
        var fieldName = grid.getColumnModel().getDataIndex(columnIndex);
        var data = record.get(fieldName);

        if (fieldName == 'local_graph_id' && data && !Ext.getCmp('hostGraph'+data)) {
            var win = new Ext.Window({
                title:record.data.host_name,
                id:'hostGraph'+data,
                layout:'fit',
                modal:false,
                closable: true,
                html: '<img src="/graph_image.php?action=view&local_graph_id='+data+'&rra_id=1">',
                width:600
            }).show();
        }
    });
};
