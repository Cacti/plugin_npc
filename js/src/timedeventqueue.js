npc.app.timedEventQueue = function(){

    var title = 'Scheduling Queue';

    var id = 'timedeventqueue-tab';

    // Default # of rows to display
    var pageSize = 25;

    var centerTabPanel = Ext.getCmp('centerTabPanel');

    var tabPanel = Ext.getCmp('centerTabPanel');
    var tab = Ext.getCmp(id);

    if (tab)  {
        tabPanel.setActiveTab(tab);
    } else {
        tabPanel.add({
            id: id,
            title: title,
            closable: true,
            autoScroll: true,
            layout:'fit',
            containerScroll: true,
            items: [{}]
        }).show();
        tabPanel.doLayout();
        tabPanel.setActiveTab(id);
    }

    var store = new Ext.data.JsonStore({
        url: 'npc.php?module=timedeventqueue&action=getQueue';
        totalProperty:'totalCount',
        root:'data',
        fields:[
            'host_name',
            'service_description',
            {name: 'last_check', type: 'date', dateFormat: 'Y-m-d H:i:s'},
            {name: 'next_check', type: 'date', dateFormat: 'Y-m-d H:i:s'},
            'state_type',
            'current_check_attempt',
            'max_check_attempts',
            'output'
        ],
        autoload:true
    });



};
