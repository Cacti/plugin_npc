npc.hostgroupGrid = function(id, title, hoi){

    // Panel title
    title = (typeof title == 'undefined') ? 'Hostgroup Grid' : title;

    // Panel ID
    id = (typeof id == 'undefined') ? 'hostgroupGrid-tab' : id;

    hoi = (typeof hoi == 'undefined') ? '' : '&p_id='+hoi;

    // Grid URL
    var url = 'npc.php?module=hostgroups&action=getHosts'+hoi;

    var gridId = id + '-grid';

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

    var store = new Ext.data.GroupingStore({
        url:url,
        autoload:true,
        sortInfo:{field: 'host_name', direction: "ASC"},
        reader: new Ext.data.JsonReader({
            totalProperty:'totalCount',
            root:'data'
        }, [
            {name: 'hostgroup_id', type: 'int'},
            {name: 'instance_id', type: 'int'},
            {name: 'config_type', type: 'int'},
            {name: 'hostgroup_object_id', type: 'int'},
            'alias',
            'instance_name',
            'hostgroup_name',
            {name: 'hoststatus_id', type: 'int'},
            {name: 'host_object_id', type: 'int'},
            {name: 'current_state', type: 'int'},
            'output',
            'host_name'
        ]),
        groupField:'alias'
    });

    var cm = new Ext.grid.ColumnModel([{
        header:"Host",
        dataIndex:'host_name',
        sortable:true,
        width:75
    },{
        header:"Status",
        dataIndex:'current_state',
        align:'center',
        renderer: npc.hostStatusImage,
        width:40
    },{
        header:"Plugin Output",
        dataIndex:'output',
        width:400
    },{
        header:"Host Group",
        dataIndex:'alias',
        hidden:true,
        width:75
    }]);

    var grid = new Ext.grid.GridPanel({
        id: gridId,
        height:800,
        layout: 'fit',
        autoExpandColumn:'output',
        autoScroll:true,
        store:store,
        cm:cm,
        sm: new Ext.grid.RowSelectionModel({singleSelect:true}),
        stripeRows: true,
        listeners: {
            // Intercept the state save to add our custom attributes
            beforestatesave: function(o, s) {
                s.rows = pageSize;
                s.refresh = refresh
                Ext.state.Manager.set(gridId, s);
                return false;
            }
        },
        view: new Ext.grid.GroupingView({
            forceFit:true,
            autoFill:true,
            hideGroupedColumn: true,
            enableGroupingMenu: false,
            enableNoGroups: true,
            emptyText:'No hostgroups.',
            groupTextTpl: '{text} ({[values.rs.length]} {[values.rs.length > 1 ? "Hosts" : "Host"]})' 
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
            disableIndexes:['current_state']
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

    // Double click action
    grid.on('rowdblclick', npc.hostGridClick);

    // Right click action
    grid.on('rowcontextmenu', npc.hostContextMenu);
};
