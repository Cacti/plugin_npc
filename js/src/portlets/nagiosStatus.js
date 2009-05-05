/* ex: set tabstop=4 expandtab: */

npc.nagiosStatus = function(){

    // Portlet name
    var name = 'Nagios Status';

    // Portlet ID
    var id = 'nagiosStatus';

    // Portlet URL
    var url = 'npc.php?module=nagios&action=getProgramStatus';

    // Refresh rate
    var refresh = npc.params.npc_portlet_refresh;

    var store = new Ext.data.JsonStore({
        url:url,
        totalProperty:'totalCount',
        root:'data',
        fields:[
            {name: 'is_currently_running', type: 'int'},
            {name: 'process_id', type: 'int'},
            {name: 'notifications_enabled', type: 'int'},
            {name: 'active_service_checks_enabled', type: 'int'},
            {name: 'active_host_checks_enabled', type: 'int'}
        ],
        autoload:true
    });

    var cm = new Ext.grid.ColumnModel([{
        header:"Nagios",
        dataIndex:'is_currently_running',
        renderer: renderRunning,
        width:80,
        align:'center'
    },{
        header:"Notifications",
        dataIndex:'notifications_enabled',
        renderer: renderEnabled,
        width:80,
        align:'center'
    }, {
        header:"Host Checks",
        dataIndex:'active_host_checks_enabled',
        renderer: renderEnabled,
        width:80,
        align:'center'
    }, {
        header:"Service Checks",
        dataIndex:'active_service_checks_enabled',
        renderer: renderEnabled,
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
    Ext.getCmp('nagiosStatusCol').add(grid);

    // Refresh the dashboard
    Ext.getCmp('north-panel').doLayout();

    // Render the grid
    grid.render();

    // Load the data store
    grid.store.load();

    store.startAutoRefresh(refresh);

    function renderRunning(v, m) {
        s = v ? 'On' : 'Off';
        bg = s.match('On') ? '33FF00' : 'F83838';
        m.attr = 'style="background-color: #' + bg + ';"';
        return String.format('<b>{0}</b>', s);
    }

    function renderEnabled(v, m, r) {
console.log(npc.params.npc_portlet_refresh);
        if (!r.data.is_currently_running) {
            s = 'NA';
            bg = 'C0C0C0';
        } else {
            s = v ? 'Enabled' : 'Disabled';
            bg = s.match('Enabled') ? '33FF00' : 'F83838';
        }

        m.attr = 'style="background-color: #' + bg + ';"';
        return String.format('<b>{0}</b>', s);
    }

};
