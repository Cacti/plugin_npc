/* ex: set tabstop=4 expandtab: */

npc.hostSummary = function(){

    // Portlet name
    var name = 'Host Status Summary';

    // Portlet ID
    var id = 'hostSummary';

    // Portlet URL
    var url = 'npc.php?module=hosts&action=summary';

    // Refresh rate
    var refresh = npc.params.npc_portlet_refresh;

    var store = new Ext.data.JsonStore({
        url:url,
        totalProperty:'totalCount',
        root:'data',
        fields:['down', 'unreachable', 'up', 'pending'],
        autoload:true
    });

    var cm = new Ext.grid.ColumnModel([{
        id: 'hostTotalsDOWN',
        header:"Down",
        dataIndex:'down',
        renderer: npc.renderStatusBg,
        width:100,
        align:'center'
    },{
        id: 'hostTotalsUNREACHABLE',
        header:"Unreachable",
        dataIndex:'unreachable',
        renderer: npc.renderStatusBg,
        width:100,
        align:'center'
    }, {
        id: 'hostTotalsUP',
        header:"Up",
        dataIndex:'up',
        renderer: npc.renderStatusBg,
        width:100,
        align:'center'
    }, {
        id: 'hostTotalsPENDING',
        header:"Pending",
        dataIndex:'pending',
        renderer: npc.renderStatusBg,
        width:100,
        align:'center'
    }]);

    var grid = new Ext.grid.GridPanel({
        title:name,
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

    // Add the grid to the portlet
    Ext.getCmp('hostStatusCol').add(grid);

    // Refresh the dashboard
    Ext.getCmp('north-panel').doLayout();

    // Render the grid
    grid.render();

    // Load the data store
    grid.store.load();

    store.startAutoRefresh(refresh);
};
