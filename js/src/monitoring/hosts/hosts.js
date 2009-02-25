npc.hostsGrid = Ext.extend(Ext.ux.grid.livegrid.GridPanel, {

    filter: 'any',

    initComponent : function()
    {
        var bufferedReader = new Ext.ux.grid.livegrid.JsonReader({
            root            : 'response.value.items',
            versionProperty : 'response.value.version',
            totalProperty   : 'response.value.total_count',
            id              : 'host_object_id'
        },[
             {name: 'host_object_id', type: 'int', sortType : 'int'},
             {name: 'host_name', sortType : 'string'},
             {name: 'alias',   sortType : 'string'},
             {name: 'address',   sortType : 'string'},
             {name: 'comment',   sortType : 'string'},
             {name: 'service_count', type: 'int', sortType : 'int'},
             {name: 'current_state', type: 'int', sortType: 'int'},
             {name: 'current_check_attempt', sortType : 'string'},
             {name: 'max_check_attempts', sortType : 'string'},
             {name: 'output', sortType : 'string'},
             {name: 'acknowledgement', sortType : 'string'},
             {name: 'local_graph_id', type: 'int', sortType: 'int'},
             {name: 'last_check', type: 'date', sortType: 'date', dateFormat: 'Y-m-d H:i:s'},
             {name: 'next_check', type: 'date', sortType: 'date', dateFormat: 'Y-m-d H:i:s'},
             {name: 'last_state_change', type: 'date', sortType: 'date', dateFormat: 'Y-m-d H:i:s'},
             {name: 'problem_has_been_acknowledged', type: 'int', sortType: 'int'},
             {name: 'notifications_enabled', type: 'int', sortType: 'int'},
             {name: 'active_checks_enabled', type: 'int', sortType: 'int'},
             {name: 'passive_checks_enabled', type: 'int', sortType: 'int'},
             {name: 'is_flapping', type: 'int', sortType: 'int'}
          ]
        );

        this.store = new Ext.ux.grid.livegrid.Store({
            autoLoad   : true,
            bufferSize : 100,
            reader     : bufferedReader,
            sortInfo   : {field: 'host_name', direction: 'ASC'},
            url        : 'npc.php?module=hosts&action=getHosts&p_state=' + this.filter
        });

        this.selModel = new Ext.ux.grid.livegrid.RowSelectionModel();

        this.view = new Ext.ux.grid.livegrid.GridView({
            nearLimit : 30
            ,forceFit:true
            ,autoFill:true
            ,emptyText:'No hosts.'
            ,loadMask: {
                msg: 'Please wait...'
            }
        });

        this.bbar = new Ext.ux.grid.livegrid.Toolbar({
            view        : this.view,
            displayInfo : true
        });

        npc.hostsGrid.superclass.initComponent.call(this);
    }

});

npc.hosts = function(title, filter){

    // Panel ID
    var id = title.replace(/[-' ']/g,'') + '-tab';

    var gridId = id + '-grid';

    var outerTabId = 'hosts-tab';

    npc.addCenterNestedTab(outerTabId, 'Hosts');

    var centerTabPanel = Ext.getCmp('centerTabPanel');

    var innerTabId = 'hosts-tab-inner-panel';

    var innerTabPanel = Ext.getCmp(innerTabId);

    var tab = Ext.getCmp(id);

    if (tab) {
        innerTabPanel.setActiveTab(tab);
        return;
    } else {
        innerTabPanel.add({ 
            id: id, 
            title: title, 
            deferredRender:false,
            height:600,
            layout: 'fit',
            closable: true
        }).show(); 
        innerTabPanel.setActiveTab(tab); 
        tab = Ext.getCmp(id); 
    }

    function renderAttempt(val, p, record){
        return String.format('{0}/{1}', val, record.data.max_check_attempts);
    }

    function renderGraphIcon(val, p, record){
        var icon = '';
        if (val) {
            icon = '<img src="images/icons/chart_bar.png">';
        }
        return String.format('{0}', icon);
    }

    var cm = new Ext.grid.ColumnModel([{
        header:"Host",
        dataIndex:'host_name',
        sortable:true,
        renderer:npc.renderExtraIcons,
        width:100
    },{
        header:"Alias",
        dataIndex:'alias',
        hidden:true,
        width:100
    },{
        header:"Address",
        dataIndex:'address',
        hidden:true,
        width:100
    },{
        header:"Status",
        dataIndex:'current_state',
        renderer:npc.hostStatusImage,
        align:'center',
        width:30
    },{
        header:"Graph",
        dataIndex:'local_graph_id',
        renderer:renderGraphIcon,
        width:30
    },{
        header:"Last Check",
        dataIndex:'last_check',
        renderer: npc.formatDate,
        width:100
    },{
        header:"Next Check",
        dataIndex:'next_check',
        renderer: npc.formatDate,
        width:100
    },{
        header:"Duration",
        dataIndex:'last_state_change',
        hidden:true,
        renderer: npc.getDuration,
        width:110
    },{
        header:"Attempt",
        dataIndex:'current_check_attempt',
        renderer: renderAttempt,
        hidden:true,
        align:'center',
        width:50
    },{
        header:"Services",
        dataIndex:'service_count',
        hidden:true,
        align:'center',
        width:50
    },{
        header:"Plugin Output",
        dataIndex:'output',
        width:400
    }]);


    var grid = new npc.hostsGrid({
        id: gridId
        ,height:800
        ,filter: filter
        ,enableDragDrop : false
        ,cm : cm
        ,stripeRows: true
        ,loadMask: {
            msg: 'Loading...'
        }
        ,plugins:[new Ext.ux.grid.Search({
            mode:'remote',
            iconCls:false,
            disableIndexes:[
                'last_check',
                'next_check',
                'local_graph_id',
                'service_count',
                'last_state_change',
                'current_check_attempt',
                'current_state'
            ]
        })]
    });

    // Add the grid to the panel
    tab.add(grid);

    // Refresh the dashboard
    centerTabPanel.doLayout();

    // Start auto refresh for grids with filter not equal to ok
    if (filter != 'any') {
        grid.store.startAutoRefresh(npc.params.npc_portlet_refresh);
    }

    var listeners = {
        destroy: function() {
            grid.store.stopAutoRefresh();
            if (!innerTabPanel.items.length) {
                centerTabPanel.remove(outerTabId, true);
            }
        }
    };

    // Add the listener to the tab
    tab.addListener(listeners);

    grid.on('rowdblclick', npc.hostGridClick);

    // Right click action
    grid.on('rowcontextmenu', npc.hostContextMenu);

    // If the graph icon is clicked popup the associated graph
    grid.on('cellclick', function(grid, rowIndex, columnIndex) {
        var record = grid.getStore().getAt(rowIndex);
        var fieldName = grid.getColumnModel().getDataIndex(columnIndex);
        var data = record.get(fieldName);

        if (fieldName == 'local_graph_id' && data && !Ext.getCmp('hostGraph'+data)) {
            var win = new Ext.Window({
                title:record.data.host_name,
                id:'hostGraph'+data,
                layout:'fit',
                modal:false,
                closable: true,
                html: '<img src="/graph_image.php?action=view&local_graph_id='+data+'&rra_id=1">',
                width:600
            }).show();
        }
    });
};
