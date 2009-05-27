npc.portlet.hostProblems = function(){

    // Portlet name
    var name = 'Host Problems';

    // Portlet ID
    var id = 'hostProblems';

    // Portlet URL
    var url = 'npc.php?module=hosts&action=getHosts&p_state=not_ok';

    // Get the portlet height
    var height = Ext.state.Manager.get(id).height;
    height = (height > 150) ? height : 150;

    // Default column
    var column = 'dashcol2';

    var cm = new Ext.grid.ColumnModel([{
        header:"Host",
        dataIndex:'host_name',
        sortable:true,
        renderer:npc.renderHostIcons,
        width:100
    },{
        header:"Alias",
        dataIndex:'alias',
        hidden:true,
        width:100
    },{
        header:"Status",
        dataIndex:'current_state',
        renderer:npc.hostStatusImage,
        align:'center',
        width:50
    },{
        header:"Plugin Output",
        dataIndex:'output',
        width:400
    }]);

    var grid = new npc.hostsGrid({
        id: id + '-grid'
        ,height:height
        ,filter: 'not_ok'
        ,enableDragDrop : false
        ,cm : cm
        ,stripeRows: true
        ,loadMask       : {
            msg : 'Loading...'
        }
    });

    // Create a portlet to hold the grid
    npc.addPortlet(id, name, column);

    // Add the grid to the portlet
    Ext.getCmp(id).items.add(grid);

    // Refresh the dashboard
    Ext.getCmp('centerTabPanel').doLayout();

    // Start auto refresh of the grid
    if (Ext.getCmp(id).isVisible()) {
        doAutoRefresh();
    }

    // Add listeners to the portlet to stop and start auto refresh
    // depending on wether or not the portlet is visible.
    var listeners = {
        hide: function() {
            grid.store.stopAutoRefresh();
        },
        show: function() {
            doAutoRefresh();
        },
        collapse: function() {
            store.stopAutoRefresh();
        },
        expand: function() {
            doAutoRefresh();
        }
    };

    Ext.getCmp(id).addListener(listeners);

    function doAutoRefresh() {
        grid.store.startAutoRefresh(npc.params.npc_portlet_refresh);
    }

    // Double click action
    grid.on('rowdblclick', npc.hostGridClick);

    // Right click action
    grid.on('rowcontextmenu', npc.hostContextMenu);

};
