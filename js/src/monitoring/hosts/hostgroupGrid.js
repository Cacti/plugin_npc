npc.hostgroupGrid = function(id, title, hoi){

    // Panel title
    title = (typeof title == 'undefined') ? 'Hostgroup Grid' : title;

    // Panel ID
    id = (typeof id == 'undefined') ? 'hostgroupGrid-tab' : id;

    hoi = (typeof hoi == 'undefined') ? '' : '&p_id='+hoi;

    // Grid URL
    var url = 'npc.php?module=hostgroups&action=getHosts'+hoi;

    // Default # of rows to display
    var pageSize = 25;

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
            closable: true, 
            items: [{}] 
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
        id: id + '-grid',
        autoHeight:true,
        autoExpandColumn:'output',
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
            scrollOffset:0,
            groupTextTpl: '{text} ({[values.rs.length]} {[values.rs.length > 1 ? "Hosts" : "Host"]})' 
        }),
        bbar: new Ext.PagingToolbar({
            pageSize: pageSize,
            store: store,
            displayInfo: true
        }),
        plugins:[new Ext.ux.grid.Search({
            mode:'remote',
            iconCls:false,
            disableIndexes:['current_state']
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
    grid.on('rowdblclick', npc.hostGridClick);

    // Right click action
    grid.on('rowcontextmenu', npc.hostContextMenu);
};
