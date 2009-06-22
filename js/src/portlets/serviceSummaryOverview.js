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

    // click action
    grid.on('cellclick', cellClick);

    // Open a services grid filtered by the cell click selection
    function cellClick(grid, rowIndex, colIndex, e) {
        var record = grid.getStore().getAt(rowIndex);
        var fieldName = grid.getColumnModel().getDataIndex(colIndex);
        npc.services('Services: '+ fieldName.substr(0, 1).toUpperCase() + fieldName.substr(1), fieldName);
    }

};
