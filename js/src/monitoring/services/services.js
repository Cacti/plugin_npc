npc.services = function(title, filter){

    // Panel ID
    var id = title.replace(/[-' ']/g,'') + '-tab';

    // Grid URL
    var url = 'npc.php?module=services&action=getServices&p_state=' + filter;

    // Default # of rows to display
    var pageSize = 20;

    var outerTabId = 'services-tab';

    npc.addCenterNestedTab(outerTabId, 'Services');

    var centerTabPanel = Ext.getCmp('centerTabPanel');

    var innerTabId = 'services-tab-inner-panel';

    var innerTabPanel = Ext.getCmp(innerTabId);

    var tab = Ext.getCmp(id);

    if (tab) {
        innerTabPanel.setActiveTab(tab);
        innerTabPanel.doLayout();
        return;
    } else {
        innerTabPanel.add({ 
            id: id, 
            title: title, 
            deferredRender:false,
            layoutOnTabChange:true,
            closable: true, 
            items: [{}] 
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

    var store = new Ext.data.GroupingStore({
        url:url,
        autoload:true,
        sortInfo:{field: 'service_description', direction: "ASC"},
        reader: new Ext.data.JsonReader({
            totalProperty:'totalCount',
            root:'data'
        }, [
            'instance_name',
            {name: 'host_object_id', type: 'int'},
            'host_name',
            {name: 'service_object_id', type: 'int'},
            {name: 'local_graph_id', type: 'int'},
            'service_description',
            'acknowledgement',
            'comment',
            'output',
            'current_state',
            'current_check_attempt',
            'max_check_attempts',
            {name: 'last_check', type: 'date', dateFormat: 'Y-m-d H:i:s'},
            {name: 'next_check', type: 'date', dateFormat: 'Y-m-d H:i:s'},
            {name: 'last_state_change', type: 'date', dateFormat: 'Y-m-d H:i:s'},
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
        renderer:npc.renderExtraIcons,
        sortable:true,
        width:100
    },{
        header:"Status",
        dataIndex:'current_state',
        renderer:npc.serviceStatusImage,
        width:30
    },{
        header:"Graph",
        dataIndex:'local_graph_id',
        renderer:renderGraphIcon,
        width:30
    },{
        header:"Instance",
        dataIndex:'instance_name',
        hidden:true,
        width:45
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
        renderer: npc.getDuration,
        hidden:true,
        width:110
    },{
        header:"Attempt",
        dataIndex:'current_check_attempt',
        renderer: renderAttempt,
        hidden:true,
        width:50
    },{
        header:"Host",
        dataIndex:'host_name',
        hidden:true,
        width:75
    },{
        header:"Plugin Output",
        dataIndex:'output',
        width:400
    }]);

    var grid = new Ext.grid.GridPanel({
        id: id + '-grid',
        autoHeight:true,
        autoExpandColumn: 'plugin_output',
        store:store,
        autoScroll: true,
        cm:cm,
        sm: new Ext.grid.RowSelectionModel({singleSelect:true}),
        stripeRows: true,
        view: new Ext.grid.GroupingView({
            forceFit:true,
            autoFill:true,
            hideGroupedColumn: true,
            enableGroupingMenu: false,
            enableNoGroups: true,
            scrollOffset:0,
            emptyText:'No services.',
            groupTextTpl: '{text} ({[values.rs.length]} {[values.rs.length > 1 ? "Services" : "Service"]})' 
        }),
        bbar: new Ext.PagingToolbar({
            pageSize: pageSize,
            store: store,
            displayInfo: true
        }),
        plugins:[new Ext.ux.grid.Search({
            mode:'remote',
            iconCls:false,
            disableIndexes:[
                'last_check', 
                'next_check', 
                'last_state_change', 
                'current_check_attempt', 
                'current_state'
            ]
        })]
    });

    // Add the grid to the panel
    tab.items.add(grid);

    // Refresh the dashboard
    centerTabPanel.doLayout();

    // Render the grid
    grid.render();

    // Load the data store
    grid.store.load({params:{start:0, limit:pageSize}});

    // Start auto refresh of the grid
    store.startAutoRefresh(npc.params.npc_portlet_refresh);

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

    // Double click action
    grid.on('rowdblclick', npc.serviceGridClick);

    // Right click action
    grid.on('rowcontextmenu', npc.serviceContextMenu);

    // If the graph icon is clicked popup the associated graph
    grid.on('cellclick', function(grid, rowIndex, columnIndex) {
        var record = grid.getStore().getAt(rowIndex);
        var fieldName = grid.getColumnModel().getDataIndex(columnIndex);
        var data = record.get(fieldName);

        if (fieldName == 'local_graph_id' && data && !Ext.getCmp('serviceGraph'+data)) {
            var win = new Ext.Window({
                title:record.data.host_name + ': ' + record.data.service_description,
                id:'serviceGraph'+data,
                layout:'fit',
                modal:false,
                closable: true,
                html: '<img src="/graph_image.php?action=view&local_graph_id='+data+'&rra_id=1">',
                width:600
            }).show();
        }
    });
};
