Ext.onReady(function(){

    // Portlet name
    var name = 'Host Status Summary';

    // Portlet ID
    var id = 'host-status-summary';

    // Portlet URL
    var url = 'npc.php?module=hosts&action=getHostSummary';

    // Default column
    var column = 'dashcol1';

    // Refresh rate
    var refresh = npc.app.params.npc_portlet_refresh;

    function renderStatus(value, meta, record){

        var count = record.data.down + record.data.unreachable + record.data.up + record.data.pending;
        var percentage = value / count ;
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
        fields:['down', 'unreachable', 'up', 'pending'],
        autoload:true
    });

    var cm = new Ext.grid.ColumnModel([{
        id: 'hostTotalsDown',
        header:"Down",
        dataIndex:'down',
        renderer: renderStatus,
        width:100,
        align:'center'
    },{
        id: 'hostTotalsUnreachable',
        header:"Unreachable",
        dataIndex:'unreachable',
        renderer: renderStatus,
        width:100,
        align:'center'
    }, {
        id: 'hostTotalsUp',
        header:"Up",
        dataIndex:'up',
        renderer: renderStatus,
        width:100,
        align:'center'
    }, {
        id: 'hostTotalsPending',
        header:"Pending",
        dataIndex:'pending',
        renderer: renderStatus,
        width:100,
        align:'center'
    }]);

    var grid = new Ext.grid.GridPanel({
        id:'host-status-summary-grid',
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
