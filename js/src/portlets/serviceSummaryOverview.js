/* ex: set tabstop=4 expandtab: */

npc.serviceSummary = function(){

    // Portlet name
    var name = 'Service Status Summary';

    // Portlet ID
    var id = 'serviceSummary';

    // Portlet URL
    var url = 'npc.php?module=services&action=summary';

    // Refresh rate
    var refresh = npc.params.npc_portlet_refresh;

    var store = new Ext.data.JsonStore({
        url:url,
        totalProperty:'totalCount',
        root:'data',
        fields:['critical', 'warning', 'unknown', 'ok', 'pending'],
        autoload:true
    });

    var cm = new Ext.grid.ColumnModel([{
        id: 'serviceTotalsCRITICAL',
        header:"Critical",
        dataIndex:'critical',
        renderer: npc.renderStatusBg,
        width:80,
        align:'center'
    },{
        id: 'serviceTotalsWARNING',
        header:"Warning",
        dataIndex:'warning',
        renderer: npc.renderStatusBg,
        width:80,
        align:'center'
    }, {
        id: 'serviceTotalsUNKNOWN',
        header:"Unknown",
        dataIndex:'unknown',
        renderer: npc.renderStatusBg,
        width:80,
        align:'center'
    }, {
        id: 'serviceTotalsOK',
        header:"Ok",
        dataIndex:'ok',
        renderer: npc.renderStatusBg,
        width:80,
        align:'center'
    }, {
        id: 'serviceTotalsPENDING',
        header:"Pending",
        dataIndex:'pending',
        renderer: npc.renderStatusBg,
        width:80,
        align:'center'
    }]);

    var grid = new Ext.grid.GridPanel({
        title: name,
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

    // Add the grid to the north panel
    Ext.getCmp('serviceStatusCol').add(grid);

    // Refresh the dashboard
    Ext.getCmp('north-panel').doLayout();

    // Render the grid
    grid.render();

    // Load the data store
    grid.store.load();

    store.startAutoRefresh(refresh);
};
