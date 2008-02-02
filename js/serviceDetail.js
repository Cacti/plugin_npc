npc.app.serviceDetail = function(record) {

    // Set the id for the service detail tab
    var id = 'serviceDetail' + record.data.service_object_id + '-tab';

    // Set thetitle
    var title = record.data.host_name + ': ' + record.data.service_description;

    var outerTabId = 'services-tab';

    npc.app.addCenterNestedTab(outerTabId, 'Services');

    var centerTabPanel = Ext.getCmp('centerTabPanel');

    var innerTabPanelId = 'services-tab-inner-panel';

    var innerTabPanel = Ext.getCmp(innerTabPanelId);

    // Get the tab
    var tab = Ext.getCmp(id);

    // Default # of rows to display
    var pageSize = 20;

    // Build the tool bar for the graph mapping
    var sgTbar = new Ext.Toolbar();

    var store = new Ext.data.JsonStore({
        url: 'npc.php?module=services&action=getGraphs',
        totalProperty:'totalCount',
        root:'data',
        fields:[
           'local_graph_id',
           'height',
           'width',
           'title'
        ],
        autoload:true
    });
    store.load();

    var combo = new Ext.form.ComboBox({
        store: store,
        displayField:'title',
        valueField:'local_graph_id',
        typeAhead: true,
        mode: 'local',
        triggerAction: 'all',
        emptyText:'Select a graph...',
        selectOnFocus:true,
        listWidth:300,
        width:300,
        listeners: {
            select: function(c, r) {
                sgStore.url = 'npc.php?module=services&action=getServiceGraph&p_local_graph_id=' + r.data.local_graph_id;
                sgStore.proxy.conn.url = 'npc.php?module=services&action=getServiceGraph&p_local_graph_id=' + r.data.local_graph_id;
                sgStore.reload();
            }
        }
    });


    function renderCheckAttempt(val, p, r){
        return String.format('{0}/{1}', val, r.data.max_check_attempts);
    };

    function renderStateType(val){
        var state;
        switch(val) {
            case '0':
                state = 'Soft';
                break;
            case '1':
                state = 'Hard';
                break;
        }
        return String.format('{0}', state);
    };

    function renderGraph(val, p, r) {
        // '<img src="/graph_image.php?action=view&local_graph_id='.$this->local_graph_id.'&rra_id=1&graph_height='.$this->height.'&graph_width='.$this->width.'">';
        return(val);
    }

    function renderCommentType(val) {
        var s;
        switch(val) {
            case '1':
                s = 'User';
                break;
            case '2':
                s = 'Scheduled Downtime';
                break;
            case '3':
                s = 'Flap Detection';
                break;
            case '4':
                s = 'Acknowledgement';
                break;
        }
        return String.format('{0}', s);
    }

    function renderPersistent(val) {
        var s;
        switch(val) {
            case '0':
                s = 'No';
                break;
            case '1':
                s = 'Yes';
                break;
        }
        return String.format('{0}', s);
    }

    function renderAction(v, p, r) {
        var url = 'npc.php?module=comments&action=delete&p_id=' + r.data.comment_id;
        return String.format('<a href="#" onClick="npc.app.get(\''+url+'\')"><img src="images/icons/delete.png"></a>')
    }

    // If the tab exists set it active and return or else create it.
    if (tab)  { 
        innerTabPanel.setActiveTab(tab);
        return; 
    } else {
        innerTabPanel.add({
            id: id, 
            title: title,
            autoHeight:true,
            closable: true,
            autoScroll: true,
            containerScroll: true,
            items: [
                new Ext.TabPanel({
                    style:'padding:10px 0 10px 10px',
                    activeTab: 0,
                    autoHeight:true,
                    autoWidth:true,
                    plain:true,
                    deferredRender:false,
                    //layoutOnTabChange:true,
                    defaults:{autoScroll: true},
                    items:[{
                        title: 'Service State Information',
                        id: id + '-si'
                    },{
                        title: 'State History',
                        autoHeight:true,
                        id: id + '-sh'
                    },{
                        title: 'Notification History',
                        id: id + '-sn'
                    },{
                        title: 'Downtime History',
                        id: id + '-sd'
                    },{
                        title: 'Comments',
                        id: id + '-sc'
                    },{
                        title: 'Graph',
                        //autoLoad: 'graphProxy.php'
                        disabled:true,
                        id: id + '-sg',
                        tbar: sgTbar
                    },{
                        title: 'Commands',
                        //listeners: {activate: handleActivate},
                        disabled:true,
                        html: 'Execute commands if you have permission.'
                    }]
                })
            ]
        }).show();
        centerTabPanel.doLayout();
        innerTabPanel.setActiveTab(tab);
        tab = Ext.getCmp(id);
    }

    // Add the graph selector to the graph tab
    sgTbar.addField(combo);

    var siStore = new Ext.data.JsonStore({
        url: 'npc.php?module=services&action=getServiceStateInfo&p_id=' + record.data.service_object_id,
        totalProperty:'totalCount',
        root:'data',
        fields:[
           'name',
           'value'
        ],
        autoload:true
    });

    var siCm = new Ext.grid.ColumnModel([{
        header:"Parameter",
        dataIndex:'name',
        width:100
    },{
        header:"Value",
        dataIndex:'value',
        width:300,
        align:'left'
    }]);

    var siGrid = new Ext.grid.GridPanel({
        autoHeight:true,
        autoWidth:true,
        store:siStore,
        cm:siCm,
        autoExpandColumn:'Value',
        stripeRows: true,
        view: new Ext.grid.GridView({
             forceFit:true,
             autoFill:true,
             scrollOffset:0
        })
    });

    var snStore = new Ext.data.JsonStore({
        url: 'npc.php?module=notifications&action=getNotifications&p_id=' + record.data.service_object_id,
        totalProperty:'totalCount',
        root:'data',
        fields:[
            'state',
            {name: 'start_time', type: 'date', dateFormat: 'Y-m-d H:i:s'},
            'output'
        ],
        autoload:true
    });

    var snCm = new Ext.grid.ColumnModel([{
        header:"",
        dataIndex:'state',
        width:40,
        renderer:npc.app.renderStatusImage
    },{
        header:"Date",
        dataIndex:'start_time',
        width:120,
        renderer: npc.app.formatDate
    },{
        header:"Message",
        dataIndex:'output',
        width:600,
    }]);

    var snGrid = new Ext.grid.GridPanel({
        autoHeight:true,
        autoWidth:true,
        store:snStore,
        cm:snCm,
        autoExpandColumn:'output',
        stripeRows: true,
        view: new Ext.grid.GridView({
            forceFit:true,
            autoFill:true,
            emptyText:'No notifications.',
            scrollOffset:0
        }),
        bbar: new Ext.PagingToolbar({
            pageSize:pageSize,
            store:snStore,
            displayInfo:true
        })
    });

    var shStore = new Ext.data.JsonStore({
        url: 'npc.php?module=statehistory&action=getStateHistory&type=2&p_id=' + record.data.service_object_id,
        totalProperty:'totalCount',
        root:'data',
        fields:[
            'state',
            {name: 'state_time', type: 'date', dateFormat: 'Y-m-d H:i:s'},
            'state_type',
            'current_check_attempt',
            'max_check_attempts',
            'output'
        ],
        autoload:true
    });

    var shCm = new Ext.grid.ColumnModel([{
        header:"",
        dataIndex:'state',
        renderer:npc.app.renderStatusImage,
        width:40
    },{
        header:"Date",
        dataIndex:'state_time',
        renderer: npc.app.formatDate,
        width:120
    },{
        header:"State Type",
        dataIndex:'state_type',
        renderer: renderStateType,
        width:80
    },{
        header:"Check Attempt",
        dataIndex:'current_check_attempt',
        renderer: renderCheckAttempt,
        width:100
    },{
        header:"Plugin Output",
        dataIndex:'output',
        width:500
    }]);

    var shGrid = new Ext.grid.GridPanel({
        autoHeight:true,
        autoWidth:true,
        store:shStore,
        cm:shCm,
        autoExpandColumn:'output',
        stripeRows: true,
        view: new Ext.grid.GridView({
            forceFit:true,
            autoFill:true,
            emptyText:'No alerts.',
            scrollOffset:0
        }),
        bbar: new Ext.PagingToolbar({
            pageSize:pageSize,
            store:shStore,
            displayInfo:true
        })
    });

    var sdStore = new Ext.data.JsonStore({
        url: 'npc.php?module=downtime&action=getDowntime&p_id=' + record.data.service_object_id,
        totalProperty:'totalCount',
        root:'data',
        fields:[
            {name: 'entry_time', type: 'date', dateFormat: 'Y-m-d H:i:s'},
            {name: 'scheduled_start_time', type: 'date', dateFormat: 'Y-m-d H:i:s'},
            {name: 'scheduled_end_time', type: 'date', dateFormat: 'Y-m-d H:i:s'},
            'author_name',
            'comment_data'
        ],
        autoload:true
    });

    var sdCm = new Ext.grid.ColumnModel([{
        header:"Entry Time",
        dataIndex:'entry_time',
        renderer: npc.app.formatDate,
        width:120
    },{
        header:"Start Time",
        dataIndex:'scheduled_start_time',
        renderer: npc.app.formatDate,
        width:120
    },{
        header:"End Time",
        dataIndex:'scheduled_end_time',
        renderer: npc.app.formatDate,
        width:120
    },{
        header:"User",
        dataIndex:'author_name',
        width:100
    },{
        header:"Comment",
        dataIndex:'comment_data',
        width:400
    }]);

    var sdGrid = new Ext.grid.GridPanel({
        autoHeight:true,
        autoWidth:true,
        store:sdStore,
        cm:sdCm,
        autoExpandColumn:'comment_data',
        stripeRows: true,
        view: new Ext.grid.GridView({
            forceFit:true,
            autoFill:true,
            emptyText:'No downtime.',
            scrollOffset:0
        }),
        bbar: new Ext.PagingToolbar({
            pageSize:pageSize,
            store:sdStore,
            displayInfo:true
        })
    });

    var scStore = new Ext.data.JsonStore({
        url: 'npc.php?module=comments&action=getComments&p_type=2&p_id=' + record.data.service_object_id,
        totalProperty:'totalCount',
        root:'data',
        fields:[
            'comment_id',
            'instance_id',
            {name: 'entry_time', type: 'date', dateFormat: 'Y-m-d H:i:s'},
            'entry_type',
            'author_name',
            'comment_data',
            'is_persistent',
            {name: 'expiration_time', type: 'date', dateFormat: 'Y-m-d H:i:s'}
        ],
        autoload:true
    });

    var scCm = new Ext.grid.ColumnModel([{
        header:"Entry Time",
        dataIndex:'entry_time',
        renderer: npc.app.formatDate,
        width:120
    },{
        header:"Author",
        dataIndex:'author_name',
        width:100
    },{
        header:"Comment",
        dataIndex:'comment_data',
        width:400
    },{
        header:"Persistent",
        dataIndex:'is_persistent',
        renderer:renderPersistent,
        width:80
    },{
        header:"Type",
        dataIndex:'entry_type',
        renderer:renderCommentType,
        width:100
    },{
        header:"Expires",
        dataIndex:'expiration_time',
        renderer: npc.app.formatDate,
        width:120
    },{
        header:"Delete",
        renderer: renderAction,
        align:'center',
        width:50
    }]);

    var scGrid = new Ext.grid.GridPanel({
        autoHeight:true,
        autoWidth:true,
        store:scStore,
        cm:scCm,
        autoExpandColumn:'comment_data',
        stripeRows: true,
        view: new Ext.grid.GridView({
            forceFit:true,
            autoFill:true,
            emptyText:'No comments.',
            scrollOffset:0
        })
    });

    // Add the grids to the tabs
    Ext.getCmp(id+'-si').add(siGrid);
    Ext.getCmp(id+'-sn').add(snGrid);
    Ext.getCmp(id+'-sh').add(shGrid);
    Ext.getCmp(id+'-sd').add(sdGrid);
    Ext.getCmp(id+'-sc').add(scGrid);

    // Refresh the dashboard
    centerTabPanel.doLayout();

    // Render the default grid
    siGrid.render();
    snGrid.render();
    shGrid.render();
    sdGrid.render();
    scGrid.render();

    // Load the data stores
    siStore.load();
    snStore.load({params:{start:0, limit:pageSize}});
    shStore.load({params:{start:0, limit:pageSize}});
    sdStore.load({params:{start:0, limit:pageSize}});
    scStore.load({params:{start:0, limit:pageSize}});

    // Start auto refresh
    siStore.startAutoRefresh(npc.app.params.npc_portlet_refresh);
    snStore.startAutoRefresh(npc.app.params.npc_portlet_refresh);
    shStore.startAutoRefresh(npc.app.params.npc_portlet_refresh);
    sdStore.startAutoRefresh(npc.app.params.npc_portlet_refresh);
    scStore.startAutoRefresh(npc.app.params.npc_portlet_refresh);

    // Add listeners to stop auto refresh on the store if the tab is closed
    var listeners = {
        destroy: function() {
            siStore.stopAutoRefresh();
            snStore.stopAutoRefresh();
            shStore.stopAutoRefresh();
            sdStore.stopAutoRefresh();
            scStore.stopAutoRefresh();
            if (!innerTabPanel.items.length) {
                centerTabPanel.remove(outerTabId, true);
            }
        }
    };

    // Add the listeners
    tab.addListener(listeners);
};
