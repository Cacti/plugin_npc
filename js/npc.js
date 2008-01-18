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
            return String.format(val.dateFormat(npc.app.params.npc_date_format + ' ' + npc.app.params.npc_time_format));
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

        addTabExt: function(title, url) {
            if(!Ext.getCmp(title + '-tab')) {
                var tabPanel = Ext.getCmp('centerTabPanel');
                tabPanel.add({
                    title: title + '-tab',
                    closable: true,
                    scripts: true,
                    items: [ new Ext.ux.IFrameComponent({ url: url }) ]
                }).show();
                tabPanel.doLayout();
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
                        width: 160,
                        minSize: 160,
                        maxSize: 300,
                        collapsible: true,
                        margins:'0 0 0 5',
                        layout:'accordion',
                        layoutConfig:{
                            animate:true
                        },
                        items: [{
                            title:'Monitoring',
                            contentEl: 'west-monitoring',
                            border:false,
                            iconCls:'monitoring'
                        },{
                            title:'Reporting',
                            html:'<p>Some settings in here.</p>',
                            border:false,
                            iconCls:'reporting'
                        },{
                            title:'N2Cacti',
                            contentEl: 'west-config',
                            border:false,
                            iconCls:'configuration'
                        }]
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
