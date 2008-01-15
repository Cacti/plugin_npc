Ext.onReady(function(){

    // Portlet name
    var name = 'Service Status Summary';

    // Portlet ID
    var id = 'service-status-summary';

    // Portlet URL
    var url = 'npc.php?module=services&action=getServiceSummary';

    // Default column
    var column = 'dashcol1';

    // Refresh rate
    var refresh = npc.app.params.npc_portlet_refresh;

    function renderStatus(val, meta){
        console.log(meta);
        if(val > 0){
            switch(meta.id) {
                case 'serviceTotalsOk':
                    bg = '33FF00';
                    break;    
                case 'serviceTotalsCritical':
                    bg = 'F83838';
                    break;    
                case 'serviceTotalsWarning':
                    bg = 'FFFF00';
                    break;
                case 'serviceTotalsUnknown':
                    bg = 'FF9900';
                    break;
                case 'serviceTotalsPending':
                    bg = '0099FF';
                    break;
            }
            meta.attr = 'style="background-color: #' + bg + ';"';
        }
        return val;
    }

    var store = new Ext.data.JsonStore({
        url:url,
        totalProperty:'totalCount',
        root:'data',
        fields:['critical', 'warning', 'unknown', 'ok', 'pending'],
        autoload:true
    });

    var cm = new Ext.grid.ColumnModel([{
        id: 'serviceTotalsCritical',
        header:"Critical",
        dataIndex:'critical',
        width:60,
        renderer: renderStatus,
        align:'center'
    },{
        id: 'serviceTotalsWarning',
        header:"Warning",
        dataIndex:'warning',
        width:60,
        renderer: renderStatus,
        align:'center'
    }, {
        id: 'serviceTotalsUnknown',
        header:"Unknown",
        dataIndex:'unknown',
        width:50,
        renderer: renderStatus,
        align:'center'
    }, {
        id: 'serviceTotalsOk',
        header:"Ok",
        dataIndex:'ok',
        width:40,
        renderer: renderStatus,
        align:'center'
    }, {
        id: 'serviceTotalsPending',
        header:"Pending",
        dataIndex:'pending',
        width:50,
        renderer: renderStatus,
        align:'center'
    }]);

    var grid = new Ext.grid.GridPanel({
        id: id + '-grid',
        autoHeight:true,
        autoWidth:true,
        store:store,
        cm:cm,
        view: new Ext.grid.GridView({
             forceFit:true,
             autoFill:true,
             scrollOffset:0
        })
    });

    // Create a portlet to hold the grid
    npc.app.addPortlet(id, name, column);

    // Add the grid to the portlet
    Ext.getCmp(id).items.add(grid);

    // Refresh the dashboard
    Ext.getCmp('centerTabPanel').doLayout();

    // Render the grid
    grid.render();

    // Load the data store
    grid.store.load();

    // Start auto refresh of the grid
    if (Ext.getCmp(id).isVisible()) {
        store.startAutoRefresh(refresh);
    }

    // Add listeners to the portlet to stop and start auto refresh
    // depending on wether or not the portlet is visible.
    var listeners = {
        hide: function() {
            store.stopAutoRefresh();
        },
        show: function() {
            store.startAutoRefresh(refresh);
        }
    };

    Ext.getCmp(id).addListener(listeners);
});
