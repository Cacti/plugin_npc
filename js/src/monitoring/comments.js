npc.hostCommentsGrid = Ext.extend(Ext.ux.grid.livegrid.GridPanel, {

    filter: 'any',

    initComponent : function()
    {
        var bufferedReader = new Ext.ux.grid.livegrid.JsonReader({
            root            : 'response.value.items',
            versionProperty : 'response.value.version',
            totalProperty   : 'response.value.total_count',
            id              : 'comment_id'
        },[
            {name: 'comment_id', type: 'int', sortType: 'int'},
            {name: 'instance_id', type: 'int', sortType: 'int'},
            {name: 'host_name', sortType: 'string'},
            {name: 'host_icon_image', sortType: 'string'},
            {name: 'host_icon_image_alt', sortType: 'string'},
            {name: 'comment_time', type: 'date', dateFormat: 'Y-m-d H:i:s'},
            {name: 'object_id', type: 'int', sortType: 'int'},
            {name: 'entry_type', type: 'int', sortType: 'int'},
            {name: 'author_name', sortType: 'string'},
            {name: 'comment_data', sortType: 'string'},
            {name: 'is_persistent', sortType: 'int', sortType: 'int'},
            {name: 'internal_comment_id', sortType: 'int', sortType: 'int'},
            {name: 'expiration_time', type: 'date', dateFormat: 'Y-m-d H:i:s'}
          ]
        );

        this.store = new Ext.ux.grid.livegrid.Store({
            autoLoad   : true,
            bufferSize : 100,
            reader     : bufferedReader,
            sortInfo   : {field: 'host_name', direction: 'ASC'},
            url        : 'npc.php?module=comments&action=getHostComments'
        });

        this.selModel = new Ext.ux.grid.livegrid.RowSelectionModel();

        this.view = new Ext.ux.grid.livegrid.GridView({
            nearLimit : 30
            ,forceFit:true
            ,autoFill:true
            ,emptyText:'No Host comments.'
            ,loadMask: {
                msg: 'Please wait...'
            }
        });

        this.bbar = new Ext.ux.grid.livegrid.Toolbar({
            view        : this.view,
            displayInfo : true
        });

        npc.hostCommentsGrid.superclass.initComponent.call(this);
    }

});

npc.serviceCommentsGrid = Ext.extend(Ext.ux.grid.livegrid.GridPanel, {

    filter: 'any',

    initComponent : function()
    {
        var bufferedReader = new Ext.ux.grid.livegrid.JsonReader({
            root            : 'response.value.items',
            versionProperty : 'response.value.version',
            totalProperty   : 'response.value.total_count',
            id              : 'comment_id'
        },[
            {name: 'comment_id', type: 'int', sortType: 'int'},
            {name: 'instance_id', type: 'int', sortType: 'int'},
            {name: 'host_name', sortType: 'string'},
            {name: 'svc_icon_image', sortType: 'string'},
            {name: 'svc_icon_image_alt', sortType: 'string'},
            {name: 'host_icon_image', sortType: 'string'},
            {name: 'host_icon_image_alt', sortType: 'string'},
            {name: 'service_description', sortType: 'string'},
            {name: 'comment_time', type: 'date', dateFormat: 'Y-m-d H:i:s'},
            {name: 'object_id', type: 'int', sortType: 'int'},
            {name: 'entry_type', type: 'int', sortType: 'int'},
            {name: 'author_name', sortType: 'string'},
            {name: 'comment_data', sortType: 'string'},
            {name: 'is_persistent', sortType: 'int', sortType: 'int'},
            {name: 'internal_comment_id', sortType: 'int', sortType: 'int'},
            {name: 'expiration_time', type: 'date', dateFormat: 'Y-m-d H:i:s'}
          ]
        );

        this.store = new Ext.ux.grid.livegrid.Store({
            autoLoad   : true,
            bufferSize : 100,
            reader     : bufferedReader,
            sortInfo   : {field: 'service_description', direction: 'ASC'},
            url        : 'npc.php?module=comments&action=getServiceComments'
        });

        this.selModel = new Ext.ux.grid.livegrid.RowSelectionModel();

        this.view = new Ext.ux.grid.livegrid.GridView({
            nearLimit : 30
            ,forceFit:true
            ,autoFill:true
            ,emptyText:'No service comments.'
            ,loadMask: {
                msg: 'Please wait...'
            }
        });

        this.bbar = new Ext.ux.grid.livegrid.Toolbar({
            view        : this.view,
            displayInfo : true
        });

        npc.serviceCommentsGrid.superclass.initComponent.call(this);
    }

});


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
            closable: false
        });
        innerTabPanel.add({ 
            id: 'service-comments-tab', 
            title: 'Service Comments', 
            height:600,
            layout: 'fit',
            closable: false
        });
        innerTabPanel.show(); 
        innerTabPanel.setActiveTab(0); 
    }

    function renderAction(v, p, r) {
        return String.format('<img src="images/icons/comment_delete.png">');
    }

    var hcCm = new Ext.grid.ColumnModel([{
        header:"Host Name",
        dataIndex:'host_name',
        renderer: npc.renderHostIcons,
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

    var hcGrid = new npc.hostCommentsGrid({
        height:800,
        cm:hcCm,
        autoExpandColumn:'comment_data',
        stripeRows: true,
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
        plugins:[new Ext.ux.grid.Search({
            mode:'remote',
            iconCls:false,
            disableIndexes:[
                'comment_time'
		,'expiration_time'
		,'is_persistent'
                ,'entry_type'
                ,'internal_comment_id'
            ]
        })]
    });

    var scCm = new Ext.grid.ColumnModel([{
        header:"Host Name",
        dataIndex:'host_name',
        renderer: npc.renderHostIcons,
        width:120
    },{
        header:"Service",
        dataIndex:'service_description',
        renderer: npc.renderServiceIcons,
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

    var scGrid = new npc.serviceCommentsGrid({
        height:800,
        cm:scCm,
        autoExpandColumn:'comment_data',
        stripeRows: true,
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
        plugins:[new Ext.ux.grid.Search({
            mode:'remote',
            iconCls:false,
            disableIndexes:[
                'comment_time'
		,'expiration_time'
		,'is_persistent'
                ,'entry_type'
                ,'internal_comment_id'
            ]
        })]
    });

    // Add the grid to the panel
    Ext.getCmp('host-comments-tab').add(hcGrid);
    Ext.getCmp('service-comments-tab').add(scGrid);

    // Refresh the dashboard
    centerTabPanel.doLayout();

    // Render the grid
    hcGrid.render();
    scGrid.render();

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
                        npc.aPost(args);
                    }
                },
                animEl: 'elId',
                icon: Ext.MessageBox.QUESTION
            });
        }
    });

};
