// reference local blank image
Ext.BLANK_IMAGE_URL = 'js/ext/resources/images/default/s.gif';
 
// create namespace
Ext.namespace('npc');
 
// create application module
npc.app = function() {

    /* Private Variables */
	
    var viewport;
    var msgCt;

    // create some portlet tools using built in Ext tool ids
    var tools = [{
        id:'gear',
        handler: function(){
            Ext.Msg.alert('Message', 'The Settings tool was clicked.');
        }
    },{
        id:'close',
        handler: function(e, target, panel){
            panel.hide();
        }
    }];

    /* Private Functions */
    // Override Ext.data.Store to add an auto refresh option
    Ext.override(Ext.data.Store, {
        startAutoRefresh : function(interval, params, callback, refreshNow){
            if(refreshNow){
                this.reload({callback:callback});
            }
            if(this.autoRefreshProcId){
                clearInterval(this.autoRefreshProcId);
            }
            this.autoRefreshProcId = setInterval(this.reload.createDelegate(this, [{callback:callback}]), interval*1000);
        },
        stopAutoRefresh : function(){
            if(this.autoRefreshProcId){
                clearInterval(this.autoRefreshProcId);
            }
        }        
    });

    Ext.ux.IFrameComponent = Ext.extend(Ext.BoxComponent, {
        onRender : function(ct, position){
            this.el = ct.createChild({tag: 'iframe', id: 'iframe-'+ this.id, frameBorder: 0, src: this.url});
        }
    });


    /** 
     * createBox returns a formatted box for displaying
     * messages to the end user.
     * 
     * @private
     * @param (String)  t   The title 
     * @param (String)  s   The message 
     * @return 
     */
    function createBox(t, s) {
        return ['<div class="msg">',
                '<div class="x-box-tl"><div class="x-box-tr"><div class="x-box-tc"></div></div></div>',
                '<div class="x-box-ml"><div class="x-box-mr"><div class="x-box-mc"><h3>', t, '</h3>', s, '</div></div></div>',
                '<div class="x-box-bl"><div class="x-box-br"><div class="x-box-bc"></div></div></div>',
                '</div>'].join('');
    };

    function services() {
        alert('SERVICES!!!');
    };


    /* Public Space */
    return {

        // public properties, e.g. strings to translate

        // public methods

        // Unused at the moment
        getTip: function(el) {
            new Ext.ToolTip({
                target: el.id,
                width: 200,
                autoLoad: {url: 'npc.php?module=help&action=getTip&p_id='+el.id+'&format=html'},
                dismissDelay: 15000 // auto hide after 15 seconds
            });
        },

        serviceGridClick: function(grid, rowIndex, e) {
            npc.app.serviceDetail(grid.getStore().getAt(rowIndex));
        },

        getDuration: function(val) {
            var d = new Date();
            var t = d.dateFormat('U') - val.dateFormat('U');

            var one_day = 86400;
            var one_hour = 3600;
            var one_min = 60;
    
            var days = Math.floor(t / one_day);
            var hours = Math.floor((t % one_day) / one_hour);
            var minutes = Math.floor(((t % one_day) % one_hour) / one_min);
            var seconds = Math.floor((((t % one_day) % one_hour) % one_min));

            return String.format('{0}d {1}h {2}m {3}s', days, hours, minutes, seconds);
        },

        formatDate: function(val) {
            if(typeof val == "object") {
                if(val.getYear() == '69') {
                    return String.format('NA');
                } else {
                    return String.format(val.dateFormat(npc.app.params.npc_date_format + ' ' + npc.app.params.npc_time_format));
                }
            }
            return val;
        },

        renderStatusImage: function(val){
            var img;
            if (val == 0) {
                img = 'recovery.png';
            } else if (val == 1) {
                img = 'warning.png';
            } else if (val == 2) {
                img = 'critical.png';
            } else if (val == 3) {
                img = 'unknown.png';
            } else if (val == -1) {
                img = 'info.png';
            }
            return String.format('<p align="center"><img src="images/nagios/{0}"></p>', img);
        },

        toggleRegion: function(region, link){
            var r = Ext.getCmp(region);
            if (r.isVisible()) {
                r.collapse();
            } else {
                r.expand();
            }
        },

        msg: function(title, format){
            if(!msgCt){
                msgCt = Ext.DomHelper.insertFirst(document.body, {id:'msg-div'}, true);
            }
            msgCt.alignTo(document, 't-t');
            var s = String.format.apply(String, Array.prototype.slice.call(arguments, 1));
            var m = Ext.DomHelper.append(msgCt, {html:createBox(title, s)}, true);
            m.slideIn('t').pause(5).ghost("t", {remove:true});
        },

        addTabExt: function(id, title, url) {
            var tabPanel = Ext.getCmp('centerTabPanel');
            var tab = (Ext.getCmp(id));
            if(!tab) {
                tabPanel.add({
                    title: title,
                    id:id,
                    layout:'fit',
                    closable: true,
                    scripts: true,
                    items: [ new Ext.ux.IFrameComponent({ 
                        url: url 
                    })]
                }).show();
                tabPanel.doLayout();
            }
            tabPanel.setActiveTab(tab);
        },
    
        addCenterNestedTab: function(id, title) {
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
                    containerScroll: true,
                    items: [
                        new Ext.TabPanel({
                            id: id + '-inner-panel',
                            style:'padding:10px 0 10px 10px',
                            autoHeight:true,
                            autoWidth:true,
                            plain:true,
                            defaults:{autoScroll: true}
                        })
                    ] 
                }).show();
                tabPanel.doLayout();
                tabPanel.setActiveTab(id);
            }
        },

        addPortlet: function(id, title, column) {
            if(!Ext.getCmp(id)) {
                panel = new Ext.ux.Portlet({
                    id: id,
                    title: title,
                    column: column,
                    layout:'fit',
                    stateEvents: ["move","position","drop","hide","show","collapse","expand","columnmove","columnresize","sortchange"],
                    stateful:true,
                    getState: function(){
                        return {collapsed:this.collapsed, hidden:this.hidden, column:this.ownerCt.id};
                    },
                    tools: tools
                });
                Ext.getCmp(panel.column).items.add(panel);

                //var p = Ext.getCmp('dashboard').items.itemAt(0).items.itemAt(1);
                Ext.getCmp('centerTabPanel').doLayout();
            }
        },

        init: function() {
    
            var viewport = new Ext.Viewport({
                layout:'border',
                items:[{
                        region:'west',
                        id:'west-panel',
                        split:true,
                        title: 'Navigation',
                        collapsible:true,
                        width: 220,
                        minSize: 220,
                        maxSize: 400,
                        margins:'0 0 0 5',
                        items: [
                            new Ext.tree.TreePanel({
                                id:'nav-tree',
                                loader: new Ext.tree.TreeLoader(),
                                rootVisible:false,
                                border:false,
                                lines:true,
                                autoScroll:false,
                                root: new Ext.tree.AsyncTreeNode({
                                    text:'root',
                                    children:[{
                                        text:'Monitoring',
                                        iconCls:'tnode',
                                        expanded:true,
                                        children:[{
                                            text:'Hosts',
                                            iconCls:'tnode',
                                            expanded:true,
                                            children:[{
                                                text:'Hosts',
                                                iconCls:'tleaf',
                                                leaf:true
                                            },{
                                                text:'Host Problems',
                                                iconCls:'tleaf',
                                                leaf:true 
                                            },{
                                                text:'Hostgroup Overview',
                                                iconCls:'tleaf',
                                                leaf:true 
                                            },{
                                                text:'Hostgroup Summary',
                                                iconCls:'tleaf',
                                                leaf:true 
                                            },{
                                                text:'Hostgroup Grid',
                                                iconCls:'tleaf',
                                                leaf:true 
                                            }]
                                        },{
                                            text:'Services',
                                            iconCls:'tnode',
                                            expanded:true,
                                            children:[{
                                                text:'Service List',
                                                iconCls:'tleaf',
                                                leaf:true,
                                                listeners: {
                                                    click: function() {
                                                        npc.app.serviceList('Service List', 'any');
                                                    }
                                                }
                                            },{
                                                text:'Service Problems',
                                                iconCls:'tleaf',
                                                leaf:true,
                                                listeners: {
                                                    click: function() {
                                                        npc.app.serviceList('Service Problems', 'not_ok');
                                                    }
                                                }
                                            },{
                                                text:'Servicegroup Overview',
                                                iconCls:'tleaf',
                                                leaf:true 
                                            },{
                                                text:'Servicegroup Summary',
                                                iconCls:'tleaf',
                                                leaf:true 
                                            },{
                                                text:'Servicegroup Grid',
                                                iconCls:'tleaf',
                                                leaf:true 
                                            }]
                                        },{
                                            text:'Status Map',
                                            iconCls:'tleaf',
                                            leaf:true,
                                            listeners: {
                                                click: function() {
                                                    npc.app.addTabExt('StatusMap','Status Map',npc.app.params.npc_nagios_url+'/cgi-bin/statusmap.cgi?host=all');
                                                }
                                            }
                                        },{
                                            text:'Comments',
                                            iconCls:'tleaf',
                                            leaf:true 
                                        },{
                                            text:'Downtime',
                                            iconCls:'tleaf',
                                            leaf:true 
                                        },{
                                            text:'Process Info',
                                            iconCls:'tleaf',
                                            leaf:true 
                                        },{
                                            text:'Performance Info',
                                            iconCls:'tleaf',
                                            leaf:true 
                                        },{
                                            text:'Scheduling Queue',
                                            iconCls:'tleaf',
                                            leaf:true 
                                        }]
                                    },{
                                        text:'Reporting',
                                        iconCls:'tnode',
                                        expanded:false,
                                        children:[{
                                            text:'Kelly',
                                            leaf:true
                                        },{
                                            text:'Sara',
                                            leaf:true
                                        },{
                                            text:'John',
                                            leaf:true
                                        }]
                                    },{
                                        text:'Nagios',
                                        iconCls:'tleaf',
                                        leaf:true,
                                        listeners: {
                                            click: function() {
                                                npc.app.addTabExt('Nagios','Nagios',npc.app.params.npc_nagios_url);
                                            }
                                        }
                                    }]
                                })
                            })
                        ]
                    },
                    new Ext.TabPanel({
                        region:'center',
                        id: 'centerTabPanel',
                        enableTabScroll:true,
                        autowidth:true,
                        deferredRender: false,
                        activeTab: 0,
                        items:[{
                            id:'dashboard',
                            title:'Dashboard',
                            iconCls:'layout',
                            autoScroll: true,
                            xtype:'portal',
                            margins:'35 5 5 0',
                            tbar: [],
                            items:[{
                                id:'dashcol1',
                                columnWidth:.50,
                                style:'padding:10px 0 10px 10px'
                            },{
                                id:'dashcol2',
                                columnWidth:.50,
                                style:'padding:10px 0 10px 10px'
                            }]
                        }]

                    })
                ]
            }); // End viewport

            // Add the portlets button to the dashboard toolbar:
            Ext.getCmp('dashboard').getTopToolbar().add('->', {
                text: 'Portlets',
                handler: function() {

                    var eventLog = Ext.getCmp('eventlog-portlet');
                    var evetLogChecked = eventLog.isVisible();

                    var hostSummary = Ext.getCmp('host-status-summary')
                    var hostSummaryChecked = hostSummary.isVisible();

                    var serviceSummary = Ext.getCmp('service-status-summary');
                    var serviceSummaryChecked = serviceSummary.isVisible();

                    var serviceProblems = Ext.getCmp('service-problems-portlet');
                    var serviceProblemsChecked = serviceProblems.isVisible();

                    var form = new Ext.form.FormPanel({
                        title: 'Show/hide portlets',
                        bodyStyle:'padding:5px 5px 0',
                        layout: 'form',
                        frame:true,
                        xtype:'fieldset',
                        items: [{
                            boxLabel: 'Event Log',
                            hideLabel: true,
                            xtype:'checkbox',
                            checked: evetLogChecked,
                            listeners: {
                                check: function(cb, checked) {
                                    if (checked) {
                                        eventLog.show();
                                    } else {
                                        eventLog.hide();
                                    }
                                }
                            }
                        },{
                            boxLabel: 'Host Status Summary',
                            hideLabel: true,
                            xtype:'checkbox',
                            checked: hostSummaryChecked,
                            listeners: {
                                check: function(cb, checked) {
                                    if (checked) {
                                        hostSummary.show();
                                    } else {
                                        hostSummary.hide();
                                    }
                                }
                            }
                        },{
                            boxLabel: 'Service Status Summary',
                            hideLabel: true,
                            xtype:'checkbox',
                            checked: serviceSummaryChecked,
                            listeners: {
                                check: function(cb, checked) {
                                    if (checked) {
                                        serviceSummary.show();
                                    } else {
                                        serviceSummary.hide();
                                    }
                                }
                            }
                        },{
                            boxLabel: 'Service Problems',
                            hideLabel: true,
                            xtype:'checkbox',
                            checked: serviceProblemsChecked,
                            listeners: {
                                check: function(cb, checked) {
                                    if (checked) {
                                        serviceProblems.show();
                                    } else {
                                        serviceProblems.hide();
                                    }
                                }
                            }
                        }]
                    });

                    var window = new Ext.Window({
                        title:'Portlets',
                        modal:true,
                        closable: true,
                        width:300,
                        height:200,
                        layout:'fit',
                        plain:true,
                        bodyStyle:'padding:5px;',
                        items:form
                    });
                    window.show();
                }
            });

        } // End init
    };
}();
