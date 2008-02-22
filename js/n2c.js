npc.app.n2c = function() {

    var panel = new Ext.TabPanel({
        style:'padding:10px 0 10px 10px',
        activeTab: 0,
        autoHeight:true,
        autoWidth:true,
        plain:true,
        deferredRender:false,
        bodyStyle:'padding:5px;',
        //layoutOnTabChange:true,
        defaults:{autoScroll: true},
        items:[{
            title: 'Import Hosts',
            id: 'n2c-import'
        },{
            title: 'Map Hosts',
            id: 'n2c-map'
        }]
    });

    var store = new Ext.data.JsonStore({
        url:'npc.php?module=sync&action=getServices&p_state=';
        totalProperty:'totalCount',
        root:'data',
        fields:[
            'group',
            'hosts'
        ],
        autoload:true
    });


    var win = new Ext.Window({
        title:'Nagios to Cacti',
        layout:'fit',
        modal:true,
        closable: true,
        width:800,
        height:600,
        bodyStyle:'padding:5px;',
        items: panel
    });
    win.show();

    // Refresh the window
    win.doLayout();

}
