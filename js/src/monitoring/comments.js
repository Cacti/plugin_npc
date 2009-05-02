npc.comments = function(){

    var title = 'Comments';

    var outerTabId = 'comments-tab';

    npc.addCenterNestedTab(outerTabId, 'Comments');

    var centerTabPanel = Ext.getCmp('centerTabPanel');

    var innerTabId = 'comments-tab-inner-panel';

    var innerTabPanel = Ext.getCmp(innerTabId);

    var tab = Ext.getCmp(innerTabPanel);

    if (tab) {
        innerTabPanel.setActiveTab(tab);
        return;
    } else {
        innerTabPanel.add({ 
            id: 'host-comments-tab', 
            title: 'Host Comments', 
            height:600,
            layout: 'fit',
            deferredRender:false,
            closable: false
        });
        innerTabPanel.add({ 
            id: 'service-comments-tab', 
            title: 'Service Comments', 
            height:600,
            layout: 'fit',
            deferredRender:false,
            closable: false
        });
        innerTabPanel.show(); 
        innerTabPanel.setActiveTab(0); 
    }

    function renderAction(v, p, r) {
        return String.format('<img src="images/icons/comment_delete.png">');
    }

    function renderServicegroupHeading(v, p, r) {
        return String.format('[{0}] - {1}', r.data.host_name, r.data.service_description);
    }

    var hcStore = new Ext.data.GroupingStore({
        url: 'npc.php?module=comments&action=getHostComments',
        autoload:true,
        sortInfo:{field: 'host_name', direction: "ASC"},
        reader: new Ext.data.JsonReader({
            totalProperty:'totalCount',
            root:'data'
        }, [
            'comment_id',
            'instance_id',
            'host_name',
            {name: 'comment_time', type: 'date', dateFormat: 'Y-m-d H:i:s'},
            {name: 'object_id', type: 'int'},
            'entry_type',
            'author_name',
            'comment_data',
            'is_persistent',
            'internal_comment_id',
            {name: 'expiration_time', type: 'date', dateFormat: 'Y-m-d H:i:s'}
        ]),
        groupField:'host_name'
    });

    var hcCm = new Ext.grid.ColumnModel([{
        header:"Host Name",
        dataIndex:'host_name',
        hidden:true,
        width:120
    },{
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
	hidden:true,
        width:80
    },{
        header:"Type",
        dataIndex:'entry_type',
        renderer:npc.renderCommentType,
	hidden:true,
        width:100
    },{
        header:"Expires",
        dataIndex:'expiration_time',
        renderer: npc.renderCommentExpires,
	hidden:true,
        width:120
    },{
        header:"Delete",
        dataIndex:'internal_comment_id',
        renderer: renderAction,
        align:'center',
        width:50
    }]);

    /* Host Comments Grid */
    var hcGridId = title + '-hcGrid';
    var hcGridState = Ext.state.Manager.get(hcGridId);
    var hcGridRows = (hcGridState && hcGridState.rows) ? hcGridState.rows : 15;
    var hcGridRefresh = (hcGridState && hcGridState.refresh) ? hcGridState.refresh : 60;

    var hcGrid = new Ext.grid.GridPanel({
        id:hcGridId,
        height:800,
        layout: 'fit',
        autoScroll:true,
        store:hcStore,
        cm:hcCm,
        autoExpandColumn:'comment_data',
        stripeRows: true,
        listeners: {
            // Intercept the state save to add our custom attributes
            beforestatesave: function(o, s) {
                s.rows = hcGridRows;
                s.refresh = hcGridRefresh;
                Ext.state.Manager.set(hcGridId, s);
                return false;
            }
        },
        view: new Ext.grid.GroupingView({
            forceFit:true,
            autoFill:true,
            hideGroupedColumn: true,
            enableGroupingMenu: false,
            showGroupName:false,
            enableNoGroups: true,
            emptyText:'No comments.',
            groupTextTpl: '{text} ({[values.rs.length]} {[values.rs.length > 1 ? "Comments" : "Comment"]})'
        }),
        tbar:[{
            text:'New Comment',
            msg: 'Add a new host comment.',
            iconCls:'commentAdd',
            handler : function(){
                npc.addHostComment();
            }
        }, '-', {
            text:'Delete comments',
            tooltip:'Delete all host comments',
            iconCls:'commentsDelete',
            handler : function(){
                Ext.Msg.show({
                    title:'Confirm Delete',
                    msg: 'Are you sure you want to delete all host comments?',
                    buttons: Ext.Msg.YESNO,
                    fn: function(btn) {
                        if (btn == 'yes') {
                            npc.aPost({
                                module : 'comments',
                                action : 'deleteAllHostComments'
                            });
                        }
                    },
                    animEl: 'elId',
                    icon: Ext.MessageBox.QUESTION
                });
            }
        }],
        bbar: new Ext.PagingToolbar({
            pageSize: hcGridRows,
            store: hcStore,
            displayInfo: true,
            items: npc.setRefreshCombo(hcGridId, hcStore, hcGridState),
            plugins: new Ext.ux.Andrie.pPageSize({ gridId: hcGridId })
        })
    });

    var scStore = new Ext.data.GroupingStore({
        url: 'npc.php?module=comments&action=getServiceComments',
        autoload:true,
        sortInfo:{field: 'service_description', direction: "ASC"},
        reader: new Ext.data.JsonReader({
            totalProperty:'totalCount',
            root:'data'
        }, [
            'comment_id',
            'instance_id',
            'host_name',
            'service_description',
            {name: 'comment_time', type: 'date', dateFormat: 'Y-m-d H:i:s'},
            {name: 'object_id', type: 'int'},
            'entry_type',
            'author_name',
            'comment_data',
            'is_persistent',
            'internal_comment_id',
            {name: 'expiration_time', type: 'date', dateFormat: 'Y-m-d H:i:s'}
        ]),
        groupField:'object_id'
    });

    var scCm = new Ext.grid.ColumnModel([{
        header:"Host Name",
        dataIndex:'host_name',
        hidden:true,
        width:120
    },{
        header:"Service",
        dataIndex:'object_id',
        groupRenderer:renderServicegroupHeading,
        hidden:true,
        width:120
    },{
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
	hidden:true,
        width:80
    },{
        header:"Type",
        dataIndex:'entry_type',
        renderer:npc.renderCommentType,
	hidden:true,
        width:100
    },{
        header:"Expires",
        dataIndex:'expiration_time',
        renderer: npc.renderCommentExpires,
	hidden:true,
        width:120
    },{
        header:"Delete",
        dataIndex:'internal_comment_id',
        renderer: renderAction,
        align:'center',
        width:50
    }]);

    /* Service Comments Grid */
    var scGridId = title + '-scGrid';
    var scGridState = Ext.state.Manager.get(scGridId);
    var scGridRows = (scGridState && scGridState.rows) ? scGridState.rows : 15;
    var scGridRefresh = (scGridState && scGridState.refresh) ? scGridState.refresh : 60;

    var scGrid = new Ext.grid.GridPanel({
        id:scGridId,
        height:800,
        layout: 'fit',
        autoScroll:true,
        store:scStore,
        cm:scCm,
        autoExpandColumn:'comment_data',
        stripeRows: true,
        listeners: {
            // Intercept the state save to add our custom attributes
            beforestatesave: function(o, s) {
                s.rows = scGridRows;
                s.refresh = scGridRefresh;
                Ext.state.Manager.set(scGridId, s);
                return false;
            }
        },
        view: new Ext.grid.GroupingView({
            forceFit:true,
            autoFill:true,
            hideGroupedColumn: true,
            enableGroupingMenu: false,
            showGroupName: false,
            enableNoGroups: true,
            emptyText:'No comments.',
            groupTextTpl: '{text} ({[values.rs.length]} {[values.rs.length > 1 ? "Comments" : "Comment"]})'
        }),
        tbar:[{
            text:'New Comment',
            iconCls:'commentAdd',
            handler : function(){
                npc.addServiceComment();
            }
        }, '-', {
            text:'Delete comments',
            tooltip:'Delete all service comments',
            iconCls:'commentsDelete',
            handler : function(){
                Ext.Msg.show({
                    title:'Confirm Delete',
                    msg: 'Are you sure you want to delete all service comments?',
                    buttons: Ext.Msg.YESNO,
                    fn: function(btn) {
                        if (btn == 'yes') {
                            npc.aPost({
                                module : 'comments',
                                action : 'deleteAllServiceComments'
                            });
                        }
                    },
                    animEl: 'elId',
                    icon: Ext.MessageBox.QUESTION
                });
            }
        }],
        bbar: new Ext.PagingToolbar({
            pageSize: scGridRows,
            store: scStore,
            displayInfo: true,
            items: npc.setRefreshCombo(scGridId, scStore, scGridState),
            plugins: new Ext.ux.Andrie.pPageSize({ gridId: scGridId })
        })
    });

    // Add the grid to the panel
    Ext.getCmp('host-comments-tab').add(hcGrid);
    Ext.getCmp('service-comments-tab').add(scGrid);

    // Refresh the dashboard
    centerTabPanel.doLayout();

    // Render the grid
    hcGrid.render();
    scGrid.render();

    // Load the data store
    hcGrid.store.load({params:{start:0, limit:hcGridRows}});
    scGrid.store.load({params:{start:0, limit:scGridState}});

    // Start auto refresh of the grid
    hcStore.startAutoRefresh(hcGridRefresh);
    scStore.startAutoRefresh(scGridRefresh);

    // Stop auto refresh if the tab is closed
    var listeners = {
        destroy: function() {
            hcStore.stopAutoRefresh();
            scStore.stopAutoRefresh();
        }
    };

    // Add the listener to the tab
    Ext.getCmp('host-comments-tab').addListener(listeners);
    Ext.getCmp('service-comments-tab').addListener(listeners);

    //hcGrid.on('rowclick', npc.serviceGridClick);
};
