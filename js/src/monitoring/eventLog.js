npc.eventLogGrid = Ext.extend(Ext.ux.grid.livegrid.GridPanel, {

    filter: 'any',

    initComponent : function()
    {
        var bufferedReader = new Ext.ux.grid.livegrid.JsonReader({
            root            : 'response.value.items',
            versionProperty : 'response.value.version',
            totalProperty   : 'response.value.total_count',
            id              : 'host_object_id'
        },[
             {name: 'instance_id', type: 'int', sortType: 'int'},
             {name: 'instance_name', type: 'string', sortType: 'string'},
             {name: 'logentry_id', type: 'int', sortType: 'int'},
             {name: 'entry_time', type: 'date', sortType: 'date', dateFormat: 'Y-m-d H:i:s'},
             {name: 'logentry_data', type: 'string', sortType: 'string'}
          ]
        );

        this.store = new Ext.ux.grid.livegrid.Store({
            autoLoad   : true,
            bufferSize : 200,
            reader     : bufferedReader,
            sortInfo   : {field: 'host_name', direction: 'ASC'},
            url        : 'npc.php?module=logentries&action=getLogs'
        });

        this.selModel = new Ext.ux.grid.livegrid.RowSelectionModel();

        this.view = new Ext.ux.grid.livegrid.GridView({
            nearLimit : 50
            ,forceFit:true
            ,autoFill:true
            ,emptyText:'No events.'
            ,loadMask: {
                msg: 'Please wait...'
            }
        });

        this.bbar = new Ext.ux.grid.livegrid.Toolbar({
            view        : this.view,
            displayInfo : true
        });

        npc.eventLogGrid.superclass.initComponent.call(this);
    }

});

npc.eventLog = function(){

    var title = 'Event Log';

    var id = 'eventlog-tab';
    var gridId = id + '-grid';

    // Set the number of rows to display and the refresh rate
    var state = Ext.state.Manager.get(gridId);
    var refresh = (state && state.refresh) ? state.refresh : 300;


    var tabPanel = Ext.getCmp('centerTabPanel');
    var tab = Ext.getCmp(id);

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

    var grid = new npc.eventLogGrid({
        id: gridId,
        height:800,
        cm:cm,
        autoExpandColumn:'logentry_data',
        stripeRows: true,
        listeners: {
            // Intercept the state save to add our custom attributes
            beforestatesave: function(o, s) {
                s.refresh = refresh
                Ext.state.Manager.set(gridId, s);
                return false;
            }
        },
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

    // Start auto refresh of the grid
    //grid.store.startAutoRefresh(refresh);

    // Stop auto refresh if the tab is closed
    var listeners = {
        destroy: function() {
            grid.store.stopAutoRefresh();
        }
    };

    // Add the listener to the tab
    tab.addListener(listeners);

};
