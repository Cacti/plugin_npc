npc.hostDetail = function(record) {

    var host_object_id = (typeof record.data.host_object_id != 'undefined') ? record.data.host_object_id : record.data.object_id;

    // Set the id for the service detail tab
    var id = 'hostDetail' + host_object_id + '-tab';

    // Set thetitle
    var title = record.data.host_name;

    var outerTabId = 'hosts-tab';

    npc.addCenterNestedTab(outerTabId, 'Hosts');

    var centerTabPanel = Ext.getCmp('centerTabPanel');

    var innerTabPanelId = 'hosts-tab-inner-panel';

    var innerTabPanel = Ext.getCmp(innerTabPanelId);
    var detailsPanelId = innerTabPanelId + '-details';

    // Get the tab
    var tab = Ext.getCmp(id);

    // Default # of rows to display
    var pageSize = 20;

    // build the command menu
    var menu = new Ext.menu.Menu();
    menu = npc.hostCommandMenu(record.data, menu);

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
            height:600,
            layout: 'fit',
            closable: true,
            autoScroll: true,
            containerScroll: true,
            items: [
                new Ext.TabPanel({
                    id: detailsPanelId,
                    style:'padding:10px 0 10px 10px',
                    height:600,
                    border:false,
                    plain:true,
                    deferredRender:false,
                    defaults:{autoScroll: true}
                })
            ]
        }).show();
        centerTabPanel.doLayout();
        innerTabPanel.setActiveTab(tab);
        tab = Ext.getCmp(id);
    }

    function toggleEnabled(v, toggle) {

        var cmd;
        var msg;
        var m;

        switch(v) {
            case 'Active Checks Enabled':
                m = ' active checks for this host?';
                cmd = '_HOST_CHECK';
                break;
            case 'Passive Checks Enabled':
                m = ' passive checks for this host?';
                cmd = '_PASSIVE_HOST_CHECKS';
                break;
            case 'Event Handler Enabled':
                m = ' event handler for this host?';
                cmd = '_HOST_EVENT_HANDLER';
                break;
            case 'Flap Detection Enabled':
                m = ' flap detection for this host?';
                cmd = '_HOST_FLAP_DETECTION';
                break;
            case 'Notifications Enabled':
                m = ' notifications for this host?';
                cmd = '_HOST_NOTIFICATIONS';
                break;
            case 'Obsess Over Host':
                var c = 'Stop';
                if (toggle == 'Enable') {
                    c = 'Start';
                }
                msg = c + ' obsessing over this host?';
                cmd = c.toUpperCase() + '_OBSESSING_OVER_HOST';
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
            p_host_name: record.data.host_name
        };

        npc.doCommand(msg, post);
    }

    var hostStore = new Ext.data.JsonStore({
        url:'npc.php?module=hosts&action=getHosts&p_id=' + host_object_id,
        root            : 'response.value.items',
        versionProperty : 'response.value.version',
        totalProperty   : 'response.value.total_count',
        id              : 'host_object_id',
        fields: [
            'host_name',
            'notes_url',
            'perfdata',
            {name: 'host_object_id', type: 'int'},
            {name: 'local_graph_id', type: 'int'},
            {name: 'current_state', type: 'int'},
            {name: 'problem_has_been_acknowledged', type: 'int'},
            {name: 'notifications_enabled', type: 'int'},
            {name: 'active_checks_enabled', type: 'int'},
            {name: 'passive_checks_enabled', type: 'int'},
            {name: 'obsess_over_host', type: 'int'},
            {name: 'event_handler_enabled', type: 'int'},
            {name: 'flap_detection_enabled', type: 'int'}
        ]
    });

    var hiStore = new Ext.data.JsonStore({
        url: 'npc.php?module=hosts&action=getStateInfo&p_id=' + host_object_id,
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
        title: 'Host State Information',
        autoHeight:true,
        autoWidth:true,
        store:hiStore,
        cm:hiCm,
        autoExpandColumn:'Value',
        stripeRows: true,
        tbar: [{
            text:'Commands',
            iconCls:'cogAdd',
            menu: menu
        },
            '-',
        {
            text:'View Graph',
            iconCls:'chartBar',
            handler: function() {
                var gid = hostStore.data.items[0].data.local_graph_id;
                if (gid) {
                    if (!Ext.getCmp('hostGraph'+gid)) {
                        var win = new Ext.Window({
                            title:record.data.host_name,
                            id:'hostGraph'+gid,
                            layout:'fit',
                            modal:false,
                            closable: true,
                            html: '<img src="/graph_image.php?action=view&local_graph_id='+gid+'&rra_id=1">',
                            width:640
                        }).show();
                    }
                } else {
                    npc.mapGraph('hosts', host_object_id);
                }
            }
        },
            '-',
        {
            text: 'Map Graph',
            iconCls:'chartBarAdd',
            handler: function() {
                npc.mapGraph('hosts', host_object_id);
            }
        }],
        view: new Ext.grid.GridView({
             forceFit:true,
             autoFill:true,
             scrollOffset:0
        })
    });

    // Add some action handlers to the SSI grid
    hiGrid.on('rowdblclick', function(grid, row) {
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

    var hnStore = new Ext.data.JsonStore({
        url: 'npc.php?module=notifications&action=getNotifications&p_id=' + host_object_id,
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
        renderer:npc.hostStatusImage
    },{
        header:"Date",
        dataIndex:'start_time',
        width:120,
        renderer: npc.formatDate
    },{
        header:"Message",
        dataIndex:'output',
        width:600
    }]);

    /* Notification History Grid */
    var hnGridId = id + '-hnGrid';
    var hnGridState = Ext.state.Manager.get(hnGridId);
    var hnGridRows = (hnGridState && hnGridState.rows) ? hnGridState.rows : 15;
    var hnGridRefresh = (hnGridState && hnGridState.refresh) ? hnGridState.refresh : 60;

    var hnGrid = new Ext.grid.GridPanel({
        id: hnGridId,
        title: 'Notification History',
        height:800,
        layout: 'fit',
        autoScroll:true,
        store:hnStore,
        cm:hnCm,
        autoExpandColumn:'output',
        stripeRows: true,
        listeners: {
            // Intercept the state save to add our custom state attributes
            beforestatesave: function(o, s) {
                s.rows = hnGridRows;
                s.refresh = hnGridRefresh;
                Ext.state.Manager.set(hnGridId, s);
                return false;
            }
        },
        view: new Ext.grid.GridView({
            forceFit:true,
            autoFill:true,
            emptyText:'No notifications.'
        }),
        bbar: new Ext.PagingToolbar({
            pageSize:hnGridRows,
            store:hnStore,
            displayInfo:true,
            items: npc.setRefreshCombo(hnGridId, hnStore, hnGridState),
            plugins: new Ext.ux.Andrie.pPageSize({ gridId: hnGridId })
        })
    });

    var hhStore = new Ext.data.JsonStore({
        url: 'npc.php?module=statehistory&action=getStateHistory&type=2&p_id=' + host_object_id,
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
        renderer:npc.hostStatusImage,
        width:40
    },{
        header:"Date",
        dataIndex:'state_time',
        renderer: npc.formatDate,
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


    /* State History Grid */
    var hhGridId = id + '-hhGrid';
    var hhGridState = Ext.state.Manager.get(hhGridId);
    var hhGridRows = (hhGridState && hhGridState.rows) ? hhGridState.rows : 15;
    var hhGridRefresh = (hhGridState && hhGridState.refresh) ? hhGridState.refresh : 60;

    var hhGrid = new Ext.grid.GridPanel({
        id: hhGridId,
        title: 'State History',
        height:800,
        layout: 'fit',
        autoScroll: true,
        store:hhStore,
        cm:hhCm,
        autoExpandColumn:'output',
        stripeRows: true,
        listeners: {
            // Intercept the state save to add our custom state attributes
            beforestatesave: function(o, s) {
                s.rows = hhGridRows;
                s.refresh = hhGridRefresh;
                Ext.state.Manager.set(hhGridId, s);
                return false;
            }
        },
        view: new Ext.grid.GridView({
            forceFit:true,
            autoFill:true,
            emptyText:'No alerts.'
        }),
        bbar: new Ext.PagingToolbar({
            pageSize:hhGridRows,
            store:hhStore,
            displayInfo:true,
            items: npc.setRefreshCombo(hhGridId, hhStore, hhGridState),
            plugins: new Ext.ux.Andrie.pPageSize({ gridId: hhGridId })
        })
    });

    var hdStore = new Ext.data.JsonStore({
        url: 'npc.php?module=downtime&action=getDowntime&p_id=' + host_object_id,
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
        renderer: npc.formatDate,
        width:120
    },{
        header:"Start Time",
        dataIndex:'scheduled_start_time',
        renderer: npc.formatDate,
        width:120
    },{
        header:"End Time",
        dataIndex:'scheduled_end_time',
        renderer: npc.formatDate,
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


    /* Scheduled Downtime History Grid */
    var hdGridId = id + '-hdGrid';
    var hdGridState = Ext.state.Manager.get(hdGridId);
    var hdGridRows = (hdGridState && hdGridState.rows) ? hdGridState.rows : 15;
    var hdGridRefresh = (hdGridState && hdGridState.refresh) ? hdGridState.refresh : 60;

    var hdGrid = new Ext.grid.GridPanel({
        id: hdGridId,
        title: 'Scheduled Downtime History',
        height:800,
        layout: 'fit',
        autoScroll:true,
        store:hdStore,
        cm:hdCm,
        autoExpandColumn:'comment_data',
        stripeRows: true,
        listeners: {
            // Intercept the state save to add our custom state attributes
            beforestatesave: function(o, s) {
                s.rows = hdGridRows;
                s.refresh = hdGridRefresh;
                Ext.state.Manager.set(hdGridId, s);
                return false;
            }
        },
        view: new Ext.grid.GridView({
            forceFit:true,
            autoFill:true,
            emptyText:'No downtime.'
        }),
        bbar: new Ext.PagingToolbar({
            pageSize:hdGridRows,
            store:hdStore,
            displayInfo:true,
            items: npc.setRefreshCombo(hdGridId, hdStore, hdGridState),
            plugins: new Ext.ux.Andrie.pPageSize({ gridId: hdGridId })
        })
    });

    var hcStore = new Ext.data.JsonStore({
        url: 'npc.php?module=comments&action=getComments&p_id=' + host_object_id,
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

    var hcCm = new Ext.grid.ColumnModel([{
        header:"Entry Time",
        dataIndex:'comment_time',
        renderer: npc.formatDate,
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
        renderer:npc.renderPersistent,
        width:80
    },{
        header:"Type",
        dataIndex:'entry_type',
        renderer:npc.renderCommentType,
        width:100
    },{
        header:"Expires",
        dataIndex:'expiration_time',
        renderer: npc.renderCommentExpires,
        width:120
    },{
        header:"Delete",
        dataIndex:'internal_comment_id',
        renderer: renderAction,
        align:'center',
        width:50
    }]);


    /* Comments Grid */
    var hcGridId = id + '-hcGrid';
    var hcGridState = Ext.state.Manager.get(hcGridId);
    var hcGridRows = (hcGridState && hcGridState.rows) ? hcGridState.rows : 15;
    var hcGridRefresh = (hcGridState && hcGridState.refresh) ? hcGridState.refresh : 60;

    var hcGrid = new Ext.grid.GridPanel({
        id: hcGridId,
        title: 'Comments',
        height:800,
        layout: 'fit',
        autoScroll: true,
        store:hcStore,
        cm:hcCm,
        autoExpandColumn:'comment_data',
        stripeRows: true,
        listeners: {
            // Intercept the state save to add our custom state attributes
            beforestatesave: function(o, s) {
                s.rows = hcGridRows;
                s.refresh = hcGridRefresh;
                Ext.state.Manager.set(hcGridId, s);
                return false;
            }
        },
        view: new Ext.grid.GridView({
            forceFit:true,
            autoFill:true,
            emptyText:'No comments.'
        }),
        tbar:[{
            text:'New Comment',
            iconCls:'commentAdd',
            handler : function(){
                npc.addComment('host', record.data.host_name);
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
                            npc.aPost({
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
            pageSize:hcGridRows,
            store: hcStore,
            displayInfo: true,
            items: npc.setRefreshCombo(hdGridId, hcStore, hcGridState),
            plugins: new Ext.ux.Andrie.pPageSize({ gridId: hcGridId })
        })
    });

    // Add the grids to the tabs
    var detailsPanel = Ext.getCmp(detailsPanelId);
    detailsPanel.add(hiGrid);
    detailsPanel.add(hnGrid);
    detailsPanel.add(hhGrid);
    detailsPanel.add(hdGrid);
    detailsPanel.add(hcGrid);

    detailsPanel.add({
        id: 'hdNotes'+host_object_id,
        title: 'Additional Information',
        disabled: true,
        tabTip: 'Enabled by setting the notes_url parameter in your Nagios host definition.'
    });

    // Refresh the dashboard
    centerTabPanel.doLayout();

    // Render the default grid
    hiGrid.render();
    hnGrid.render();
    hhGrid.render();
    hdGrid.render();
    hcGrid.render();

    detailsPanel.setActiveTab(hiGrid);

    // Load the data stores
    hostStore.load();
    hiStore.load();
    hnStore.load({params:{start:0, limit:hnGridRows}});
    hhStore.load({params:{start:0, limit:hhGridRows}});
    hdStore.load({params:{start:0, limit:hdGridRows}});
    hcStore.load({params:{start:0, limit:hcGridRows}});

    // Start auto refresh
    hostStore.startAutoRefresh(60);
    hiStore.startAutoRefresh(60);
    hnStore.startAutoRefresh(hnGridRefresh);
    hhStore.startAutoRefresh(hhGridRefresh);
    hdStore.startAutoRefresh(hdGridRefresh);
    hcStore.startAutoRefresh(hcGridRefresh);

    // Add listeners to stop auto refresh on the store if the tab is closed
    var listeners = {
        destroy: function() {
            hostStore.stopAutoRefresh();
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
                        npc.aPost(args);
                    }
                },
                animEl: 'elId',
                icon: Ext.MessageBox.QUESTION
            });
        }
    });

    hostStore.on('load', function() {
        npc.hostCommandMenu(hostStore.data.items[0].data, menu);

        var notesUrl = hostStore.data.items[0].data.notes_url;
        var tab = Ext.getCmp('hdNotes'+host_object_id);
        if (notesUrl && tab.disabled) {
            detailsPanel.remove(tab);
            detailsPanel.add({
                id: 'hdNotes'+host_object_id,
                title: 'Additional Information',
                disabled: false,
                layout:'fit',
                deferredRender:false,
                layoutOnTabChange:true,
                scripts: true,
                items: [ new Ext.ux.IFrameComponent({
                    url: notesUrl
                })]
            });
            detailsPanel.doLayout();
        }

        if (!Ext.getCmp('dimb'+host_object_id)) {
            var perfdata = hostStore.data.items[0].data.perfdata;
            if (perfdata != '') {
                hiGrid.getTopToolbar().add('-', {
                    text: 'Data Input Method',
                    id:'dimb'+host_object_id,
                    iconCls:'scriptAdd',
                    handler: function() {
                        Ext.Msg.show({
                            title:'Create Data Input Method',
                            msg: 'Are you sure you want to create a data input method for this host?',
                            buttons: Ext.Msg.YESNO,
                            fn: function(btn) {
                                if (btn == 'yes') {
                                    var args = {
                                        module: 'cacti',
                                        action: 'addDataInputMethod',
                                        p_host: record.data.host_name,
                                        p_object_id: host_object_id
                                    };
                                    npc.aPost(args);
                                }
                            },
                            animEl: 'elId',
                            icon: Ext.MessageBox.QUESTION
                        });
                    }
                });
            }
        }
    });
};
