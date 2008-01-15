npc.app.showServices = function(title, filter){

    // Panel ID
    var id = title.replace(/[-' ']/g,'') + '-tab';

    // Grid URL
    var url = 'npc.php?module=services&action=getServices&p_state=' + filter;

    // Default # of rows to display
    var pageSize = 30;

    var tab = Ext.getCmp(id);
    var tabPanel = Ext.getCmp('centerTabPanel');

    // Add or switch to the tab
    if (tab)  {
        tabPanel.setActiveTab(tab);
        return;
    } else {
        tabPanel.add({
            id: id,
            title: title,
            closable: true,
            items: [{}]
        }).show();
        tabPanel.doLayout();
        tabPanel.setActiveTab(tab);
        tab = Ext.getCmp(id);
    }

    function renderAttempt(val, p, record){
        return String.format('{0}/{1}', val, record.data.max_check_attempts);
    }

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
        sortInfo:{field: 'service_description', direction: "ASC"},
        reader: new Ext.data.JsonReader({
            totalProperty:'totalCount',
            root:'data',
        }, [
            'host_object_id',
            'host_name',
            'service_object_id',
            'service_description',
            'output',
            'current_state',
            'current_check_attempt',
            'max_check_attempts',
            {name: 'last_check', type: 'date', dateFormat: 'Y-m-d H:i:s'},
            {name: 'next_check', type: 'date', dateFormat: 'Y-m-d H:i:s'},
            {name: 'last_state_change', type: 'date', dateFormat: 'Y-m-d H:i:s'}
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
        renderer:renderStatus,
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
        width:75
    },{
        header:"Plugin Output",
        dataIndex:'output',
        width:400
    }]);

    var grid = new Ext.grid.GridPanel({
        id: 'services-grid',
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
        })
    });

    // Add the grid to the panel
    tab.items.add(grid);

    // Refresh the dashboard
    tabPanel.doLayout();

    // Render the grid
    grid.render();

    // Load the data store
    grid.store.load({params:{start:0, limit:pageSize}});

    // Start auto refresh of the grid
    if (Ext.getCmp(id).isVisible()) {
        doAutoRefresh();
    }

    // Add listeners to the portlet to stop and start auto refresh
    // depending on wether or not the portlet is visible.
    var listeners = {
        close: function() {
            store.stopAutoRefresh();
        }
    };

    tab.addListener(listeners);

    function doAutoRefresh() {
        store.startAutoRefresh(npc.app.params.npc_portlet_refresh);
    }

    grid.on('rowclick', function(grid, rowIndex, e) {
        var record = grid.getStore().getAt(rowIndex);
        var id = 'soi' + record.data.service_object_id + '-tab';
        var tab = Ext.getCmp(id);
        var tabPanel = Ext.getCmp('centerTabPanel');
        if (!tab) {
            tabPanel.add({
                id: id,
                title: record.data.host_name + ': ' + record.data.service_description,
                closable: true,
                autoLoad: {
                    url: 'npc.php?module=services&action=showService&p_id=' + record.data.service_object_id,
                    scripts: true
                },
                items: [{}]
            }).show();
            tabPanel.doLayout();
        }
        tabPanel.setActiveTab(tab);
    });
};
