npc.app.serviceGroupOverview = function(){

    // Panel title
    var title = 'Service Group Overview';

    // Panel ID
    var id = 'serviceGroupOverview-tab';

    // Grid URL
    var url = 'npc.php?module=services&action=getServices&p_state=' + filter;

    // Default # of rows to display
    var pageSize = 25;

    var outerTabId = 'services-tab';

    npc.app.addCenterNestedTab(outerTabId, 'Services');

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
            root:'data',
        }, [
            'host_object_id',
            'host_name',
            'service_object_id',
            'service_description',
            'current_state'
        ]),
        groupField:'host_name'
    });

    var cm = new Ext.grid.ColumnModel([{
        header:"Service",
        dataIndex:'service_description',
        sortable:true,
        width:100
    },{
        header:"Status",
        dataIndex:'current_state',
        renderer:npc.app.renderStatusImage,
        width:45
    },{
        header:"Last Check",
        dataIndex:'last_check',
        renderer: npc.app.formatDate,
        width:110
    },{
        header:"Next Check",
        dataIndex:'next_check',
        renderer: npc.app.formatDate,
        width:110
    },{
        header:"Duration",
        dataIndex:'last_state_change',
        renderer: npc.app.getDuration,
        width:110
    },{
        header:"Attempt",
        dataIndex:'current_check_attempt',
        renderer: renderAttempt,
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
            groupTextTpl: '{text} ({[values.rs.length]} {[values.rs.length > 1 ? "Services" : "Service"]})' 
        }),
        bbar: new Ext.PagingToolbar({
            pageSize: pageSize,
            store: store,
            displayInfo: true
        }),
        plugins:[new Ext.ux.grid.Search({
            mode:'remote',
            iconCls:false
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
    store.startAutoRefresh(npc.app.params.npc_portlet_refresh);

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

    grid.on('rowclick', npc.app.serviceGridClick);

};
