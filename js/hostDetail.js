npc.app.hostDetail = function(record) {

    // Set the id for the service detail tab
    var id = 'hostDetail' + record.data.host_object_id + '-tab';

    // Set thetitle
    var title = record.data.host_name;

    // Default # of rows to display
    var pageSize = 20;

    var outerTabId = 'hosts-tab';

    npc.app.addCenterNestedTab(outerTabId, 'Hosts');

    var centerTabPanel = Ext.getCmp('centerTabPanel');

    var innerTabPanelId = 'hosts-tab-inner-panel';

    var innerTabPanel = Ext.getCmp(innerTabPanelId);

    // Get the tab
    var tab = Ext.getCmp(id);

    // Default # of rows to display
    var pageSize = 20;

    // Build the tool bar for the graph mapping
    var sgTbar = new Ext.Toolbar();

    var store = new Ext.data.JsonStore({
        url: 'npc.php?module=hosts&action=getGraphs',
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
                hgStore.url = 'npc.php?module=hosts&action=getHostGraph&p_local_graph_id=' + r.data.local_graph_id;
                hgStore.proxy.conn.url = 'npc.php?module=hosts&action=getHostGraph&p_local_graph_id=' + r.data.local_graph_id;
                hgStore.reload();
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
        return String.format('<img src="images/icons/comment_delete.png">');
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
                    defaults:{autoScroll: true},
                    items:[{
                        title: 'Host State Information',
                        id: id + '-hi'
                    },{
                        title: 'State History',
                        autoHeight:true,
                        id: id + '-hh'
                    },{
                        title: 'Notification History',
                        id: id + '-hn'
                    },{
                        title: 'Downtime History',
                        id: id + '-hd'
                    },{
                        title: 'Comments',
                        id: id + '-hc'
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

    var hiStore = new Ext.data.JsonStore({
        url: 'npc.php?module=hosts&action=getStateInfo&p_id=' + record.data.host_object_id,
        totalProperty:'totalCount',
        root:'data',
        fields:[
           'name',
           'value'
        ],
        autoload:true
    });

    var hiCm = new Ext.grid.ColumnModel([{
        header:"Parameter",
        dataIndex:'name',
        width:100
    },{
        header:"Value",
        dataIndex:'value',
        width:300,
        align:'left'
    }]);

    var hiGrid = new Ext.grid.GridPanel({
        autoHeight:true,
        autoWidth:true,
        store:hiStore,
        cm:hiCm,
        autoExpandColumn:'Value',
        stripeRows: true,
        view: new Ext.grid.GridView({
             forceFit:true,
             autoFill:true,
             scrollOffset:0
        })
    });

    var hnStore = new Ext.data.JsonStore({
        url: 'npc.php?module=notifications&action=getNotifications&p_id=' + record.data.host_object_id,
        totalProperty:'totalCount',
        root:'data',
        fields:[
            'state',
            {name: 'start_time', type: 'date', dateFormat: 'Y-m-d H:i:s'},
            'output'
        ],
        autoload:true
    });

    var hnCm = new Ext.grid.ColumnModel([{
        header:"",
        dataIndex:'state',
        width:40,
        renderer:npc.app.hostStatusImage
    },{
        header:"Date",
        dataIndex:'start_time',
        width:120,
        renderer: npc.app.formatDate
    },{
        header:"Message",
        dataIndex:'output',
        width:600
    }]);

    var hnGrid = new Ext.grid.GridPanel({
        autoHeight:true,
        autoWidth:true,
        store:hnStore,
        cm:hnCm,
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
            store:hnStore,
            displayInfo:true
        })
    });

    var hhStore = new Ext.data.JsonStore({
        url: 'npc.php?module=statehistory&action=getStateHistory&type=2&p_id=' + record.data.host_object_id,
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

    var hhCm = new Ext.grid.ColumnModel([{
        header:"",
        dataIndex:'state',
        renderer:npc.app.hostStatusImage,
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

    var hhGrid = new Ext.grid.GridPanel({
        autoHeight:true,
        autoWidth:true,
        store:hhStore,
        cm:hhCm,
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
            store:hhStore,
            displayInfo:true
        })
    });

    var hdStore = new Ext.data.JsonStore({
        url: 'npc.php?module=downtime&action=getDowntime&p_id=' + record.data.host_object_id,
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

    var hdCm = new Ext.grid.ColumnModel([{
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

    var hdGrid = new Ext.grid.GridPanel({
        autoHeight:true,
        autoWidth:true,
        store:hdStore,
        cm:hdCm,
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
            store:hdStore,
            displayInfo:true
        })
    });

    var hcStore = new Ext.data.JsonStore({
        url: 'npc.php?module=comments&action=getComments&p_id=' + record.data.host_object_id,
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
            'internal_comment_id',
            {name: 'expiration_time', type: 'date', dateFormat: 'Y-m-d H:i:s'}
        ],
        autoload:true
    });

    var hcCm = new Ext.grid.ColumnModel([{
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
        dataIndex:'internal_comment_id',
        renderer: renderAction,
        align:'center',
        width:50
    }]);

    var hcGrid = new Ext.grid.GridPanel({
        autoHeight:true,
        autoWidth:true,
        store:hcStore,
        cm:hcCm,
        autoExpandColumn:'comment_data',
        stripeRows: true,
        view: new Ext.grid.GridView({
            forceFit:true,
            autoFill:true,
            emptyText:'No comments.',
            scrollOffset:0
        }),
        tbar:[{
            text:'New Comment',
            iconCls:'commentAdd',
            handler : function(){
                npc.app.addHostComment(record.data.host_name);
            }
        }, '-', {
            text:'Delete comments',
            tooltip:'Delete all comments',
            iconCls:'commentsDelete',
            handler : function(){
                Ext.Msg.show({
                    title:'Confirm Delete',
                    msg: 'Are you sure you want to delete all comments?',
                    buttons: Ext.Msg.YESNO,
                    fn: function(btn) {
                        if (btn == 'yes') {
                            npc.app.aPost({
                                module : 'nagios',
                                action : 'command',
                                p_command : 'DEL_ALL_HOST_COMMENTS',
                                p_host_name : record.data.host_name
                            });
                        }
                    },
                    animEl: 'elId',
                    icon: Ext.MessageBox.QUESTION
                });
            }
        }],
        bbar: new Ext.PagingToolbar({
            pageSize: pageSize,
            store: hcStore,
            displayInfo: true
        })
        // The search field won't render :(
        //plugins:[new Ext.ux.grid.Search({
        //    mode:'remote',
        //    iconCls:false
        //})]
    });

    // Add the grids to the tabs
    Ext.getCmp(id+'-hi').add(hiGrid);
    Ext.getCmp(id+'-hn').add(hnGrid);
    Ext.getCmp(id+'-hh').add(hhGrid);
    Ext.getCmp(id+'-hd').add(hdGrid);
    Ext.getCmp(id+'-hc').add(hcGrid);

    // Refresh the dashboard
    centerTabPanel.doLayout();

    // Render the default grid
    hiGrid.render();
    hnGrid.render();
    hhGrid.render();
    hdGrid.render();
    hcGrid.render();

    // Load the data stores
    hiStore.load();
    hnStore.load({params:{start:0, limit:pageSize}});
    hhStore.load({params:{start:0, limit:pageSize}});
    hdStore.load({params:{start:0, limit:pageSize}});
    hcStore.load({params:{start:0, limit:pageSize}});

    // Start auto refresh
    hiStore.startAutoRefresh(npc.app.params.npc_portlet_refresh);
    hnStore.startAutoRefresh(npc.app.params.npc_portlet_refresh);
    hhStore.startAutoRefresh(npc.app.params.npc_portlet_refresh);
    hdStore.startAutoRefresh(npc.app.params.npc_portlet_refresh);
    hcStore.startAutoRefresh(npc.app.params.npc_portlet_refresh);

    // Add listeners to stop auto refresh on the store if the tab is closed
    var listeners = {
        destroy: function() {
            hiStore.stopAutoRefresh();
            hnStore.stopAutoRefresh();
            hhStore.stopAutoRefresh();
            hdStore.stopAutoRefresh();
            hcStore.stopAutoRefresh();
            if (!innerTabPanel.items.length) {
                centerTabPanel.remove(outerTabId, true);
            }
        }
    };

    // Add the listeners
    tab.addListener(listeners);

    // Handle deleting individual comments
    hcGrid.addListener("cellclick", function(grid, row, column, e) {
        var rec = grid.getStore().getAt(row);
        var fieldName = grid.getColumnModel().getDataIndex(column);
        if (fieldName == 'internal_comment_id') {
            Ext.Msg.show({
                title:'Confirm Delete',
                msg: 'Are you sure you want to delete this comment?',
                buttons: Ext.Msg.YESNO,
                fn: function(btn) {
                    if (btn == 'yes') {
                        var args = {
                            module : 'nagios',
                            action : 'command',
                            p_command : 'DEL_HOST_COMMENT',
                            p_comment_id : rec.get(fieldName)
                        };
                        npc.app.aPost(args);
                    }
                },
                animEl: 'elId',
                icon: Ext.MessageBox.QUESTION
            });
        }
    });
};
