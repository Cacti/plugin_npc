npc.hosts = function(title, filter){

    // Panel ID
    var id = title.replace(/[-' ']/g,'') + '-tab';

    // Grid URL
    var url = 'npc.php?module=hosts&action=getHosts&p_state=' + filter;

    // Default # of rows to display
    var pageSize = 20;

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
            closable: true, 
            items: [{}] 
        }).show(); 
        innerTabPanel.setActiveTab(tab); 
        tab = Ext.getCmp(id); 
    }

    function renderAttempt(val, p, record){
        return String.format('{0}/{1}', val, record.data.max_check_attempts);
    };

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
        width:50
    },{
        header:"Last Check",
        dataIndex:'last_check',
        renderer: npc.formatDate,
        width:110
    },{
        header:"Next Check",
        dataIndex:'next_check',
        renderer: npc.formatDate,
        width:110
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
        id: id + '-grid',
        autoHeight:true,
        autoExpandColumn: 'host_name',
        store:store,
        autoScroll: true,
        cm:cm,
        sm: new Ext.grid.RowSelectionModel({singleSelect:true}),
        stripeRows: true,
        view: new Ext.grid.GridView({
             forceFit:true,
             autoFill:true,
             scrollOffset:0
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
                'service_count', 
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

    grid.on('rowdblclick', npc.hostGridClick);

    // Right click action
    grid.on('rowcontextmenu', npc.hostContextMenu);
};
