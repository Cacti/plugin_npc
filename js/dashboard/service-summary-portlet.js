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

    function renderStatus(value, meta, record){

        var services = record.data.ok+record.data.critical+record.data.warning+record.data.unknown+record.data.pending;
        var percentage = value / services ;
        var w = Math.floor(percentage*100);

        var html = '<div class="x-progress-wrap">'+
                       '<div style="text-align: center;" class="x-progress-inner">'+
                           '<div class="status-bar ' + meta.id + '" style="width:' + w + '%">'+
                           '</div>'+
                           '<div class="status-bar-text status-bar-text-back">'+
                               '<div>' + value + '</div>'+
                           '</div>'+
                       '</div>'+
                   '</div>';

        return html;
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
        renderer: renderStatus,
        width:80,
        align:'center'
    },{
        id: 'serviceTotalsWarning',
        header:"Warning",
        dataIndex:'warning',
        renderer: renderStatus,
        width:80,
        align:'center'
    }, {
        id: 'serviceTotalsUnknown',
        header:"Unknown",
        dataIndex:'unknown',
        renderer: renderStatus,
        width:80,
        align:'center'
    }, {
        id: 'serviceTotalsOk',
        header:"Ok",
        dataIndex:'ok',
        renderer: renderStatus,
        width:80,
        align:'center'
    }, {
        id: 'serviceTotalsPending',
        header:"Pending",
        dataIndex:'pending',
        renderer: renderStatus,
        width:80,
        align:'center'
    }]);

    var grid = new Ext.grid.GridPanel({
        id: id + '-grid',
        autoHeight:true,
        width:400,
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
