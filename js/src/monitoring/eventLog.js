npc.eventLog = function(){

    var title = 'Event Log';

    var id = 'eventlog-tab';
    var gridId = id + '-grid';

    // Set the number of rows to display and the refresh rate
    var state = Ext.state.Manager.get(gridId);
    var pageSize = (state && state.rows) ? state.rows : 15;
    var refresh = (state && state.refresh) ? state.refresh : 60;


    var tabPanel = Ext.getCmp('centerTabPanel');
    var tab = Ext.getCmp(id);

    var store = new Ext.data.JsonStore({
        url:'npc.php?module=logentries&action=getLogs',
        totalProperty:'totalCount',
        root:'data',
        fields:[
            {name: 'instance_id', type: 'int'},
            'instance_name',
            {name: 'logentry_id', type: 'int'},
            {name: 'entry_time', type: 'date', dateFormat: 'Y-m-d H:i:s'},
            'logentry_data'
        ],
        autoload:true
    });

    var cm = new Ext.grid.ColumnModel([{
        dataIndex:'logentry_data',
        renderer: npc.renderEventIcon,
        width:25
    },{
        header:"Instance",
        dataIndex:'instance_name',
        width:125,
        hidden:true,
        align:'left'
    }, {
        header:"Date",
        dataIndex:'entry_time',
        width:100,
        renderer:npc.formatDate,
        align:'left'
    }, {
        header:"Log Entry",
        dataIndex:'logentry_data',
        width:600,
        align:'left'
    }]);

    var grid = new Ext.grid.GridPanel({
        id: gridId,
        //autoHeight:true,
        height:600,
        autoScroll:true,
        store:store,
        cm:cm,
        autoExpandColumn:'logentry_data',
        stripeRows: true,
        listeners: {
            // Intercept the state save to add our custom attributes
            beforestatesave: function(o, s) {
                s.rows = pageSize;
                s.refresh = refresh
                Ext.state.Manager.set(gridId, s);
                return false;
            }
        },
        view: new Ext.grid.GridView({
            forceFit:true,
            autoFill:true,
            emptyText:'No events.'
        }),
        bbar: new Ext.PagingToolbar({
            pageSize: pageSize,
            store: store,
            displayInfo: true,
            displayMsg: '',
            items: npc.setRefreshCombo(gridId, store, state),
            plugins: new Ext.ux.Andrie.pPageSize({ gridId: gridId })
        }),
        plugins:[new Ext.ux.grid.Search({
            mode:'remote',
            iconCls:false
        })]
    });

    if (tab)  {
        tabPanel.setActiveTab(tab);
    } else {
        tabPanel.add({
            id: id,
            title: title,
            closable: true,
            height:600,
            layout:'fit',
            deferredRender:false,
            containerScroll: true,
            items: grid
        }).show();
        tabPanel.doLayout();
    }

    tab = Ext.getCmp(id);
    tabPanel.setActiveTab(id);

    // Render the grid
    grid.render();

    // Load the data store
    store.load({params:{start:0, limit:pageSize}});

    // Start auto refresh of the grid
    store.startAutoRefresh(refresh);

    // Stop auto refresh if the tab is closed
    var listeners = {
        destroy: function() {
            store.stopAutoRefresh();
        }
    };

    // Add the listener to the tab
    tab.addListener(listeners);

};
