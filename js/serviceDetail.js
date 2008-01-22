npc.app.serviceDetail = function(record) {

    // Set the id for the service detail tab
    var id = 'serviceDetail' + record.data.service_id + '-tab';

    // Set thetitle
    var title = record.data.host_name + ': ' + record.data.service_description;

    // Get the tabPanel
    var tabPanel = Ext.getCmp('centerTabPanel');

    // Get the tab
    var tab = Ext.getCmp(id);

    // Set the URL
    var url = 'npc.php?module=services&action=getServiceDetail&p_id=' + record.data.service_id;

    function renderValue(val, meta, record) {
        return(val);
    }

    function renderEnabled(v) {
        var img;
        switch(v) {
            case '0':
                img = 'cross.png';;
                break;
            case '1':
                img = 'tick.png';;
                break;
        }
        return String.format('<p align="center"><img src="images/icons/{0}"></p>', img);
    }

    // If the tab exists set it active and return or else create it.
    if (tab)  { 
        tabPanel.setActiveTab(tab);
        return; 
    } else {
        tabPanel.add({
            id: id, 
            title: title,
            closable: true,
            autoScroll: true,
            containerScroll: true,
            tbar: [{
                id:'tab',
                text: 'View in New Tab',
                iconCls: 'new-tab',
                disabled:true,
                handler : this.openTab,
                scope: this
            },
            '-',
            {
                id:'win',
                text: 'Go to Post',
                iconCls: 'new-win',
                disabled:true,
                scope: this,
                handler : function(){
                    window.open(this.gsm.getSelected().data.link);
                }
            }],
            items: [
                new Ext.Panel({
                    id:'ssi-main-table',
                    layout:'column',
                    //autoWidth:true,
                    //border:false,
                    bodyBorder:false,
                    items: [{
                        id: 'ssi-left',
                        border:false,
                        columnWidth: .5
                    },{
                        id: 'ssi-right',
                        border:false,
                        columnWidth: .5
                    }]
                })
            ]
        }).show();
        tabPanel.doLayout();
        tabPanel.setActiveTab(tab);
        tab = Ext.getCmp(id);
    }

    var store = new Ext.data.JsonStore({
        url:url,
        totalProperty:'totalCount',
        root:'data',
        fields:[
           'name',
           'value'
        ],
        autoload:true
    });

    var cm = new Ext.grid.ColumnModel([{
        header:"Parameter",
        dataIndex:'name',
        width:75
    },{
        header:"Value",
        dataIndex:'value',
        width:125,
        renderer: renderValue,
        align:'left'
    }]);

    var grid = new Ext.grid.GridPanel({
        title:'Service State Information',
        id: 'ssi-grid',
        style:'padding:10px 0 10px 10px',
        autoHeight:true,
        width:500,
        store:store,
        cm:cm,
        autoExpandColumn:'value',
        stripeRows: true,
        view: new Ext.grid.GridView({
             forceFit:true,
             autoFill:true,
             scrollOffset:0
        })
    });

    // Add the grid to the panel
    Ext.getCmp('ssi-left').add(grid);

    // Refresh the dashboard
    tabPanel.doLayout();

    // Render the grid
    grid.render();

    // Load the data store
    store.load();

    // STart auto refresh
    store.startAutoRefresh(npc.app.params.npc_portlet_refresh);

    // Add listeners to stop auto refresh on the store if the tab is closed
    var listeners = {
        destroy: function() {
            store.stopAutoRefresh();
            console.log(store);
        }
    };

    // Add the listener to the tab
    tab.addListener(listeners);
};
