npc.servicegroupGrid = function(id, title, soi){

    // Panel title
    title = (typeof title == 'undefined') ? 'Servicegroup Grid' : title;

    // Panel ID
    id = (typeof id == 'undefined') ? 'servicegroupGrid-tab' : id;

    soi = (typeof soi == 'undefined') ? '' : '&p_id='+soi;

    // Grid URL
    var url = 'npc.php?module=servicegroups&action=getServices'+soi;

    // Default # of rows to display
    var pageSize = 25;

    var outerTabId = 'services-tab';

    npc.addCenterNestedTab(outerTabId, 'Services');

    var centerTabPanel = Ext.getCmp('centerTabPanel');

    var innerTabId = 'services-tab-inner-panel';

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
        sortInfo:{field: 'service_description', direction: "ASC"},
        reader: new Ext.data.JsonReader({
            totalProperty:'totalCount',
            root:'data'
        }, [
            'servicegroup_id',
            'instance_id',
            'config_type',
            'servicegroup_object_id',
            'alias',
            'instance_name',
            'servicegroup_name',
            'servicestatus_id',
            'current_state',
            'output',
            'service_object_id',
            'host_name',
            'service_description',
            'comment',
            'acknowledgement',
            {name: 'problem_has_been_acknowledged', type: 'int'},
            {name: 'notifications_enabled', type: 'int'},
            {name: 'active_checks_enabled', type: 'int'},
            {name: 'passive_checks_enabled', type: 'int'},
            {name: 'is_flapping', type: 'int'}

        ]),
        groupField:'alias'
    });

    var cm = new Ext.grid.ColumnModel([{
        header:"Host",
        dataIndex:'host_name',
        sortable:true,
        width:75
    },{
        header:"Service",
        dataIndex:'service_description',
        renderer:npc.renderExtraIcons,
        width:75
    },{
        header:"Status",
        dataIndex:'current_state',
        renderer: npc.serviceStatusImage,
        width:40
    },{
        header:"Plugin Output",
        dataIndex:'output',
        width:400
    },{
        header:"Serivce Group",
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
            emptyText:'No servicegroups.',
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
    grid.on('rowdblclick', npc.serviceGridClick);

    // Right click action
    grid.on('rowcontextmenu', npc.serviceContextMenu);
};
