npc.app.serviceDetail = function(record) {

    var service_object_id = (typeof record.data.service_object_id != 'undefined') ? record.data.service_object_id : record.data.object_id;

    // Set the id for the service detail tab
    var id = 'serviceDetail' + service_object_id + '-tab';

    // Set thetitle
    var title = record.data.host_name + ': ' + record.data.service_description;

    // Default # of rows to display
    var pageSize = 20;

    var outerTabId = 'services-tab';

    npc.app.addCenterNestedTab(outerTabId, 'Services');

    var centerTabPanel = Ext.getCmp('centerTabPanel');

    var innerTabPanelId = 'services-tab-inner-panel';

    var innerTabPanel = Ext.getCmp(innerTabPanelId);

    // Get the tab
    var tab = Ext.getCmp(id);

    // Default # of rows to display
    var pageSize = 20;

    // build the command menu
    var menu = new Ext.menu.Menu();
    buildCommandMenu();

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

    function onItemClick() {
        console.log(arguments);
    }

    function renderCheckAttempt(val, p, r){
        return String.format('{0}/{1}', val, r.data.max_check_attempts);
    }

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
    }

    function renderGraph(val, p, r) {
        // '<img src="/graph_image.php?action=view&local_graph_id='.$this->local_graph_id.'&rra_id=1&graph_height='.$this->height.'&graph_width='.$this->width.'">';
        return(val);
    }

    function renderAction(v, p, r) {
        return String.format('<img src="images/icons/comment_delete.png">');
    }

    function toggleEnabled(v, toggle) {

        var cmd;
        var msg;
        var m;

        switch(v) {
            case 'Active Checks Enabled':
                m = ' active checks for this service?';
                cmd = '_SVC_CHECK';
                break;
            case 'Passive Checks Enabled':
                m = ' passive checks for this service?';
                cmd = '_PASSIVE_SVC_CHECKS';
                break;
            case 'Event Handler Enabled':
                m = ' event handler for this service?';
                cmd = '_SVC_EVENT_HANDLER';
                break;
            case 'Flap Detection Enabled':
                m = ' flap detection for this service?';
                cmd = '_SVC_FLAP_DETECTION';
                break;
            case 'Notifications Enabled':
                m = ' notifications for this service?';
                cmd = '_SVC_NOTIFICATIONS';
                break;
            case 'Obsess Over Service':
                var c = 'Stop';
                if (toggle == 'Enable') {
                    c = 'Start';
                }
                msg = c + ' obsessing over this service?';
                cmd = c.toUpperCase() + '_OBSESSING_OVER_SVC';
                break;
        }

        if (v.match('Enabled')) { 
            cmd = toggle.toUpperCase() + cmd;
            msg = toggle + m;
        }
        
        var post = {
            module: 'nagios',
            action: 'command',
            p_command: cmd,
            p_host_name: record.data.host_name,
            p_service_description: record.data.service_description
        };

        doCommand(msg, post);
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
                    style:'padding:5px 0 5px 5px',
                    activeTab: 0,
                    autoHeight:true,
                    autoWidth:true,
                    plain:true,
                    deferredRender:false,
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

    var serviceStore = new Ext.data.JsonStore({
        url:'npc.php?module=services&action=getServices&p_id=' + service_object_id,
        autoload:true,
        totalProperty:'totalCount',
        root:'data',
        fields: [
            'host_name',
            'service_description',
            {name: 'service_object_id', type: 'int'},
            {name: 'current_state', type: 'int'},
            {name: 'problem_has_been_acknowledged', type: 'int'},
            {name: 'notifications_enabled', type: 'int'},
            {name: 'active_checks_enabled', type: 'int'},
            {name: 'passive_checks_enabled', type: 'int'},
            {name: 'obsess_over_service', type: 'int'},
            {name: 'event_handler_enabled', type: 'int'},
            {name: 'flap_detection_enabled', type: 'int'}
        ]
    });


    var siStore = new Ext.data.JsonStore({
        url: 'npc.php?module=services&action=getStateInfo&p_id=' + service_object_id,
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
        sm: new Ext.grid.RowSelectionModel(),
        stripeRows: true,
        tbar: [{
            text:'Commands',
            iconCls:'appLightning',
            menu: menu
        }],
        view: new Ext.grid.GridView({
             forceFit:true,
             autoFill:true,
             scrollOffset:0
        })
    });

    // Add some action handlers to the SSI grid
    siGrid.on('rowdblclick', function(grid, row) {
        var n = grid.getStore().getAt(row).data.name;
        var v = grid.getStore().getAt(row).data.value;
        var toggle = false;

        if (v.match('tick.png')) {
            toggle = 'Disable';
        } else if (v.match('cross.png')) {
            toggle = 'Enable';
        }

        if (toggle) {
            toggleEnabled(n, toggle);
        }
    });

    var snStore = new Ext.data.JsonStore({
        url: 'npc.php?module=notifications&action=getNotifications&p_id=' + service_object_id,
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
        renderer:npc.app.serviceStatusImage
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
        url: 'npc.php?module=statehistory&action=getStateHistory&type=2&p_id=' + service_object_id,
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
        renderer:npc.app.serviceStatusImage,
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
        url: 'npc.php?module=downtime&action=getDowntime&p_id=' + service_object_id,
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
        url: 'npc.php?module=comments&action=getComments&p_id=' + service_object_id,
        totalProperty:'totalCount',
        root:'data',
        fields:[
            'comment_id',
            'instance_id',
            {name: 'comment_time', type: 'date', dateFormat: 'Y-m-d H:i:s'},
            'entry_type',
            'author_name',
            'comment_data',
            'is_persistent',
            'internal_comment_id',
            {name: 'expiration_time', type: 'date', dateFormat: 'Y-m-d H:i:s'}
        ],
        autoload:true
    });

    var scCm = new Ext.grid.ColumnModel([{
        header:"Entry Time",
        dataIndex:'comment_time',
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
        renderer:npc.app.renderPersistent,
        width:80
    },{
        header:"Type",
        dataIndex:'entry_type',
        renderer:npc.app.renderCommentType,
        width:100
    },{
        header:"Expires",
        dataIndex:'expiration_time',
        renderer: npc.app.renderCommentExpires,
        width:120
    },{
        header:"Delete",
        dataIndex:'internal_comment_id',
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
        }),
        tbar:[{
            text:'New Comment',
            iconCls:'commentAdd',
            handler : function(){
                npc.app.addServiceComment(record.data.host_name,record.data.service_description);
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
                                p_command : 'DEL_ALL_SVC_COMMENTS',
                                p_host_name : record.data.host_name,
                                p_service_description : record.data.service_description
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
            store: scStore,
            displayInfo: true
        })
        // The search field won't render :(
        //plugins:[new Ext.ux.grid.Search({
        //    mode:'remote',
        //    iconCls:false
        //})]
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
    serviceStore.load();
    snStore.load({params:{start:0, limit:pageSize}});
    shStore.load({params:{start:0, limit:pageSize}});
    sdStore.load({params:{start:0, limit:pageSize}});
    scStore.load({params:{start:0, limit:pageSize}});

    // Start auto refresh
    serviceStore.startAutoRefresh(60);
    siStore.startAutoRefresh(60);
    snStore.startAutoRefresh(60);
    shStore.startAutoRefresh(60);
    sdStore.startAutoRefresh(60);
    scStore.startAutoRefresh(60);

    // Add listeners to stop auto refresh on the store if the tab is closed
    var listeners = {
        destroy: function() {
            serviceStore.stopAutoRefresh();
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

    // Handle deleting individual comments
    scGrid.addListener("cellclick", function(grid, row, column, e) {
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
                            p_command : 'DEL_SVC_COMMENT',
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

    serviceStore.on('load', function() {
        buildCommandMenu(menu);
    });

    function buildCommandMenu() {

        var item;
        var text;
        var a;

        var service = serviceStore ? serviceStore.data.items[0].data : record.data;

        menu.removeAll();

        var post = {
            module: 'nagios',
            action: 'command',
            p_host_name: service.host_name,
            p_service_description: service.service_description
        };

        if (service.current_state == 2) {
            item = menu.add({
                text: 'Acknowledge this service problem'
                //handler: ackProblemk
            });
        }

        a = service.active_checks_enabled ? 'Disable' : 'Enable';
        text = a + ' active checks of this service';
        item = menu.add({
            text: text,
            handler: function(o) {
                post.p_command = a.toUpperCase() + '_SVC_CHECK';
                doCommand(o.text+'?',post);
            }
        });

        a = service.notifications_enabled ? 'Disable' : 'Enable';
        text = a + ' notifications for this service';
        item = menu.add({
            text: text,
            handler: function(o) {
                post.p_command = a.toUpperCase() + '_SVC_NOTIFICATIONS';
                doCommand(o.text+'?',post);
            }
        });

        item = menu.add({
            text: 'Send custom service notification'
            //handler: sendCustomNotification
        });

        item = menu.add({
            text: 'Re-schedule the next check of this service'
            //handler: scheduleNextCheck
        });

        if (service.passive_checks_enabled) {
            item = menu.add({
                text: 'Submit Passive Check Result For This Service'
                //handler: submitPassiveCheck
            });
        }

        item = menu.add({
            text: 'Schedule downtime for this service'
            //handler: scheduleNextCheck
        });

        a = service.passive_checks_enabled ? 'Stop' : 'Start';
        text = a + ' accepting passive checks for this service';
        item = menu.add({
            text: text,
            handler: function(o) {
                post.p_command = a.toUpperCase() + '_PASSIVE_SVC_CHECKS';
                doCommand(o.text+'?',post);
            }
        });

        a = service.obsess_over_service ? 'Stop' : 'Start';
        text = a + ' obsessing over this service';
        item = menu.add({
            text: text,
            handler: function(o) {
                post.p_command = a.toUpperCase() + '_OBSESSING_OVER_SVC';
                doCommand(o.text+'?',post);
            }
        });

        a = service.event_handler_enabled ? 'Disable' : 'Enable';
        text = a + ' event handler for this service';
        item = menu.add({
            text: text,
            handler: function(o) {
                post.p_command = a.toUpperCase() + '_SVC_EVENT_HANDLER';
                doCommand(o.text+'?',post);
            }
        });

        a = service.event_handler_enabled ? 'Disable' : 'Enable';
        text = a + ' flap detection for this service';
        item = menu.add({
            text: text,
            handler: function(o) {
                post.p_command = a.toUpperCase() + '_SVC_FLAP_DETECTION';
                doCommand(o.text+'?',post);
            }
        });

        return(menu);
    }

    function doCommand(msg, post) {

        Ext.Msg.show({
            title:'Confirm',
            msg:msg,
            buttons: Ext.Msg.YESNO,
            fn: function(btn) {
                if (btn == 'yes') {
                    npc.app.aPost(post);
                }
            },
            animEl: 'elId',
            icon: Ext.MessageBox.QUESTION
        });
    }

};
