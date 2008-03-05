npc.app.portlet.hostSummary = function(){

    // Portlet name
    var name = 'Host Status Summary';

    // Portlet ID
    var id = 'hostSummary';

    // Portlet URL
    var url = 'npc.php?module=hosts&action=summary';

    // Default column
    var column = 'dashcol1';

    // Refresh rate
    var refresh = npc.app.params.npc_portlet_refresh;

    function renderStatus(val, meta){  
        if(val > 0){  
            switch(meta.id) {  
                case 'hostTotalsUp':  
                    bg = '33FF00';  
                    break;  
                case 'hostTotalsDown':  
                    bg = 'F83838';  
                    break;  
                case 'hostTotalsUnreachable':  
                    bg = 'F83838';  
                    break;  
                case 'hostTotalsPending':  
                    bg = '0099FF';  
                    break;  
            }  
            meta.attr = 'style="background-color: #' + bg + ';"';  
        }  
        return String.format('<b>{0}</b>', val);
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
};
