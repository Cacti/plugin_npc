npc.app.reporting = function(report, url){

    var title = 'Reporting';

    var id = report.replace(/[-' ']/g,'') + '-tab';

    var outerTabId = 'reporting-tab';

    npc.app.addCenterNestedTab(outerTabId, title);

    var centerTabPanel = Ext.getCmp('centerTabPanel');

    var innerTabId = outerTabId + '-inner-panel';

    var innerTabPanel = Ext.getCmp(innerTabId);

 
    var tabPanel = Ext.getCmp(innerTabId);
    var tab = (Ext.getCmp(id));
    if(!tab) {
        tabPanel.add({
            title: report,
            id:id,
            layout:'fit',
            height:600,
            closable: true,
            scripts: true,
            items: [ new Ext.ux.IFrameComponent({
                url: url
            })]
        }).show();
        tabPanel.doLayout();
        tab = (Ext.getCmp(id));
    }
    tabPanel.setActiveTab(tab);


    var listeners = {
        destroy: function() {
            if (!tabPanel.items.length) {
                centerTabPanel.remove(outerTabId, true);
            }
        }
    };

    tab.addListener(listeners);

};
