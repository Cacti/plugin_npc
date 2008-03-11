npc.app.comments = function(){

    var title = 'Comments';

    // Default # of rows to display
    var pageSize = 20;

    var outerTabId = 'comments-tab';

    npc.app.addCenterNestedTab(outerTabId, 'Comments');

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
            deferredRender:false,
            closable: false, 
            items: [{}] 
        });
        innerTabPanel.add({ 
            id: 'service-comments-tab', 
            title: 'Service Comments', 
            deferredRender:false,
            closable: false, 
            items: [{}] 
        });
        innerTabPanel.show(); 
        innerTabPanel.setActiveTab(0); 
    }

    function renderAction(v, p, r) {
        return String.format('<img src="images/icons/comment_delete.png">');
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

    var hcGrid = new Ext.grid.GridPanel({
        autoHeight:true,
        autoWidth:true,
        store:hcStore,
        cm:hcCm,
        autoExpandColumn:'comment_data',
        stripeRows: true,
        view: new Ext.grid.GroupingView({
            forceFit:true,
            autoFill:true,
            hideGroupedColumn: true,
            enableGroupingMenu: false,
            enableNoGroups: true,
            emptyText:'No comments.',
            groupTextTpl: '{text} ({[values.rs.length]} {[values.rs.length > 1 ? "Comments" : "Comment"]})'
        }),
        tbar:[{
            text:'New Comment',
            msg: 'Add a new host comment.',
            iconCls:'commentAdd',
            handler : function(){
                npc.app.addHostComment();
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
                            npc.app.aPost({
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
            pageSize: pageSize,
            store: hcStore,
            displayInfo: true
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
            'entry_type',
            'author_name',
            'comment_data',
            'is_persistent',
            'internal_comment_id',
            {name: 'expiration_time', type: 'date', dateFormat: 'Y-m-d H:i:s'}
        ]),
        groupField:'service_description'
    });

    var scCm = new Ext.grid.ColumnModel([{
        header:"Host Name",
        dataIndex:'host_name',
        hidden:true,
        width:120
    },{
        header:"Service",
        dataIndex:'service_description',
        hidden:true,
        width:120
    },{
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
        view: new Ext.grid.GroupingView({
            forceFit:true,
            autoFill:true,
            hideGroupedColumn: true,
            enableGroupingMenu: false,
            enableNoGroups: true,
            emptyText:'No comments.',
            groupTextTpl: '{text} ({[values.rs.length]} {[values.rs.length > 1 ? "Comments" : "Comment"]})'
        }),
        tbar:[{
            text:'New Comment',
            iconCls:'commentAdd',
            handler : function(){
                npc.app.addServiceComment();
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
                            npc.app.aPost({
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
            pageSize: pageSize,
            store: hcStore,
            displayInfo: true
        })
    });

    // Add the grid to the panel
    Ext.getCmp('host-comments-tab').items.add(hcGrid);
    Ext.getCmp('service-comments-tab').items.add(scGrid);

    // Refresh the dashboard
    centerTabPanel.doLayout();

    // Render the grid
    hcGrid.render();
    scGrid.render();

    // Load the data store
    hcGrid.store.load({params:{start:0, limit:pageSize}});
    scGrid.store.load({params:{start:0, limit:pageSize}});

    // Start auto refresh of the grid
    hcStore.startAutoRefresh(npc.app.params.npc_portlet_refresh);
    scStore.startAutoRefresh(npc.app.params.npc_portlet_refresh);

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

    //hcGrid.on('rowclick', npc.app.serviceGridClick);
};
