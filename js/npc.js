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

    /* Public Space */
    return {

        // public properties, e.g. strings to translate
        params: new Array(),
        portlet: {},

        // public methods

        serviceGridClick: function(grid, rowIndex, e) {
            npc.app.serviceDetail(grid.getStore().getAt(rowIndex));
        },

        addServiceComment: function(host, service) {
            var form = new Ext.FormPanel({
                labelWidth: 75,
                url:'npc.php?module=nagios&action=command',
                frame:true,
                bodyStyle:'padding:5px 5px 0',
                width: 525,
                defaults: {width: 175},
                defaultType: 'textfield',
                    items: [
                    {
                        name: 'p_command',
                        value: 'ADD_SVC_COMMENT',
                        xtype: 'hidden'
                    },{
                        fieldLabel: 'Host Name',
                        name: 'p_host_name',
                        value: host,
                        allowBlank:false
                    },{
                        fieldLabel: 'Service',
                        name: 'p_service_description',
                        value: service,
                        allowBlank:false
                    },{
                        fieldLabel: 'Persistent',
                        name: 'p_persistent',
                        xtype: 'xcheckbox',
                        checked:true
                    },{
                        name: 'p_author',
                        value: npc.app.params.userName,
                        xtype: 'hidden'
                    },{
                        fieldLabel: 'Comment',
                        name: 'p_comment',
                        xtype: 'htmleditor',
                        width: 525
                    },{
                        xtype: 'panel',
                        html: '<br /><span style="font-size:10px;"><b>Note:</b> It may take a while before Nagios processes the comment.</span>',
                        width: 400
                    }
                ],
                buttons: [{
                    text: 'Submit',
                    handler: function(){
                        form.getForm().submit({
                            success: function(f, a) {
                                win.close();
                            },
                            failure: function(f, a) {
                                Ext.Msg.alert('Error', a.result.msg);
                            } 
                        });
                    }
                },{
                    text: 'Cancel',
                    handler: function(){
                        win.close();
                    }
                }]
            });

            var win = new Ext.Window({ 
                title:'New Comment', 
                layout:'fit', 
                modal:true, 
                closable: true, 
                width:600, 
                height:400, 
                bodyStyle:'padding:5px;', 
                items: form 
            }); 
            win.show(); 

        },

        // A simple ajax post
        aPost: function(args) {
            Ext.Ajax.request({
                url : 'npc.php' ,
                params : args,
                failure: function (r) {
                    Ext.Msg.alert('Error', r.result.msg);
                }
            });
        },

        renderStatusBg: function(val, meta){
            if(val > 0){
                if (meta.id.match('UP')) {
                        bg = '33FF00';
                } else if  (meta.id.match('DOWN')) {
                        bg = 'F83838';
                } else if  (meta.id.match('UNREACHABLE')) {
                        bg = 'F83838';
                } else if  (meta.id.match('PENDING')) {
                        bg = '0099FF';
                } else if  (meta.id.match('OK')) {
                        bg = '33FF00';
                } else if  (meta.id.match('CRITICAL')) {
                        bg = 'F83838';
                } else if  (meta.id.match('WARNING')) {
                        bg = 'FFFF00';
                } else if  (meta.id.match('UNKNOWN')) {
                        bg = 'FF9900';
                }
                meta.attr = 'style="background-color: #' + bg + ';"';
                return String.format('<b>{0}</b>', val);
            }
            return('0');
        },

        renderServiceIcons: function(val, p, record) {
            var img = '';
            if (record.data.problem_has_been_acknowledged == 1) {
                var ack = record.data.acknowledgement.split("*|*");
                img = String.format('&nbsp;<img ext:qtitle="Acknowledged by {0}" ext:qtip="{1}" src="images/icons/wrench.png">', ack[0], ack[1]);
            }
            if (record.data.notifications_enabled == 0) {
                img = String.format('&nbsp;<img ext:qtip="Notifications for this service have been disabled." src="images/icons/sound_mute.png">') + img;
            }
            if (record.data.comment) {
                var c = record.data.comment.split("*|*");
                img = String.format('&nbsp;<img qtitle="{0}" ext:qtip="{1}" src="images/icons/comment.png">', c[0], c[1]) + img;
            }
            if (record.data.is_flapping) {
                img = String.format('&nbsp;<img ext:qtip="This service is flapping between states" src="images/icons/link_error.png">') + img;
            }
            if (!record.data.active_checks_enabled && !record.data.passive_checks_enabled) {
                img = String.format('&nbsp;<img ext:qtip="Active and passive checks have been disabled" src="images/icons/cross.png">') + img;
            } else if (!record.data.active_checks_enabled) {
                img = String.format('&nbsp;<img qtitle="Active checks disabled" ext:qtip="Passive checks are being accepted" src="images/nagios/passiveonly.gif">') + img;
            }
            return String.format('<div><div style="float: left;">{0}</div><div style="float: right;">{1}</div></div>', val, img);
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
                    layout:'fit',
                    containerScroll: true,
                    items: [
                        new Ext.TabPanel({
                            id: id + '-inner-panel',
                            style:'padding:10px 0 10px 10px',
                            deferredRender:false,
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

        initPortlets: function() {
            var o = this.portlet;
            var portlets = [];
            var c = 0;
            
            var a = 0;
            for (x in o) {
                if (!Ext.state.Manager.get(x).dt) {
                    portlets[a] = [x,0];
                } else {
                    portlets[a] = [x,Ext.state.Manager.get(x).dt];
                }
                a++;
            }

            // Sort portlets by position 1 which holds an integer value
            portlets.sort(function(a, b) { return b[1] - a[1] });

            // Launch the portlets in order
            for (var i = 0; i < portlets.length; i++) {
                for (x in portlets) {
                    if (typeof portlets[x][0] == 'string') {
                        if (Ext.state.Manager.get(portlets[x][0]).index == i) {
                            o[portlets[x][0]]();
                        }
                    }
                }
            }
        },

        addPortlet: function(id, title, column) {
            if(!Ext.getCmp(id)) {
                panel = new Ext.ux.Portlet({
                    id: id,
                    title: title,
                    index: 0,
                    layout:'fit',
                    stateEvents: ["hide","show","collapse","expand"],
                    stateful:true,
                    getState: function(){

                        var dt = new Date();
                        var d = 0;
                        var column;
                        var index;

                        // Find the new column and index
                        if (this.ownerCt) {
                            column = this.ownerCt.id;
                            var a = this.ownerCt.items.keys;
                            for (var i in a) {
                                if (a[i] == id) {
                                    index = i;
                                    break;
                                }
                            }
                        }

                        // Rare case to make sure column has a value
                        if (!column) { column = Ext.state.Manager.get(id).column; }

                        // Rare case to make sure index has a value
                        if (!index) { index = Ext.state.Manager.get(id).index; }

                        // d is used to track if portlets have moved up or down. This 
                        // is needed because the first time a portlet is moved it will 
                        // have the same index value as another portlet.
                        if (index < this.index) {
                            d = dt.format('U');    
                        } else if (index > this.index) {
                            d = -1
                        }

                        // A place holder for the current index value
                        this.index = index;
                        return {collapsed:this.collapsed, hidden:this.hidden, column:column, index:index, dt:d};
                    },
                    tools: tools
                });
                Ext.getCmp(panel.column).items.add(panel);

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
                                                text:'Services',
                                                iconCls:'tleaf',
                                                leaf:true,
                                                listeners: {
                                                    click: function() {
                                                        npc.app.serviceList('Services', 'any');
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
                                                leaf:true,
                                                listeners: {
                                                    click: function() {
                                                        npc.app.serviceGroupGrid();
                                                    }
                                                }
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
                                    },{
                                        text:'N2C',
                                        iconCls:'tleaf',
                                        leaf:true,
                                        listeners: {
                                            click: function() {
                                                npc.app.n2c();
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

                    var eventLog = Ext.getCmp('eventLog');
                    var evetLogChecked = eventLog.isVisible();

                    var hostSummary = Ext.getCmp('hostSummary')
                    var hostSummaryChecked = hostSummary.isVisible();

                    var serviceSummary = Ext.getCmp('serviceSummary');
                    var serviceSummaryChecked = serviceSummary.isVisible();

                    var serviceProblems = Ext.getCmp('serviceProblems');
                    var serviceProblemsChecked = serviceProblems.isVisible();

                    var monitoringPerf = Ext.getCmp('monitoringPerf');
                    var monitoringPerf = monitoringPerf.isVisible();

                    var form = new Ext.form.FormPanel({
                        //title: 'Show/hide portlets',
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
                        },{
                            boxLabel: 'Monitoring Performance',
                            hideLabel: true,
                            xtype:'checkbox',
                            checked: monitoringPerfChecked,
                            listeners: {
                                check: function(cb, checked) {
                                    if (checked) {
                                        monitoringPerf.show();
                                    } else {
                                        monitoringPerf.hide();
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
