/* ex: set tabstop=4 expandtab: */

// reference local blank image
Ext.BLANK_IMAGE_URL = 'js/ext/resources/images/default/s.gif';
 
// create namespace
Ext.namespace('npc');
 
// create application module
npc = function() {

    /* Private Variables */
	
    var viewport;
    var msgCt;

    // create some portlet tools using built in Ext tool ids
    var tools = [{
        id:'gear',
        handler: function(btn, e, o){
            configurePortlet(o);
        }
    },{
        id:'close',
        handler: function(e, target, panel){
            panel.hide();
        }
    }];

    function configurePortlet(portlet) {
        var state = Ext.state.Manager.get(portlet.id);

        var currentRefresh = state.refresh; 
        var currentHeight = state.height; 
        var height = 125;

        var rowField = {
            name: 'rows',
            xtype: 'hidden'
        };

        if (currentHeight) {
            height = 150;
            rowField = {
                fieldLabel: 'Display Height',
                name: 'height',
                value: currentHeight,
                labelStyle: 'cursor: help;',
                tooltipText: "The height of the portlet.",
                allowBlank: false,
                xtype: 'textfield',
                listeners: {
                    render: function(o) {
                        npc.setFormFieldTooltip(o);
                    }
                }
            }
        }

        var form = new Ext.FormPanel({
            labelWidth: 75,
            frame:true,
            bodyStyle:'padding:5px 5px 0',
            width: 200,
            defaults: {width: 50},
            defaultType: 'textfield',
            items: [
                {
                    fieldLabel: 'Refresh Rate',
                    name: 'refresh',
                    value: currentRefresh,
                    labelStyle: 'cursor: help;',
                    tooltipText: "The refresh rate in seconds.",
                    allowBlank: false,
                    listeners: {
                        render: function(o) {
                            npc.setFormFieldTooltip(o);
                        }
                    }
                },
                rowField
            ],
            buttons: [
            {
                text: 'Save',
                handler: function(o){
                    var r = parseInt(form.form.getValues().refresh);
                    var grid = portlet.items.items[0];

                    if (r != currentRefresh) {
                        state.refresh = (r >= 10) ? r : 10;
                        grid.store.startAutoRefresh(state.refresh);
                    }

                    if (currentHeight) {
                        state.height = parseInt(form.form.getValues().height);
                        grid.setHeight(state.height);
                        portlet.setHeight(state.height);
                    }

                    Ext.state.Manager.set(portlet.id, state);
                    o.ownerCt.ownerCt.close();
                }
            },{
                text: 'Cancel',
                handler: function(o){
                    o.ownerCt.ownerCt.close();
                }
            }]
        });

        var win = new Ext.Window({
            title:'Portlet Configuration',
            layout:'fit',
            modal:true,
            closable: true,
            height: height,
            width:250,
            items: form
        }).show();

    };

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
            npc.serviceDetail(grid.getStore().getAt(rowIndex));
        },

        hostGridClick: function(grid, rowIndex, e) {
            npc.hostDetail(grid.getStore().getAt(rowIndex));
        },

        hostContextMenu: function(grid, rowIndex, e) {
            e.stopEvent();
            var cmdMenu = new Ext.menu.Menu();
            cmdMenu = npc.hostCommandMenu(grid.getStore().getAt(rowIndex).data, cmdMenu);

            var menu = new Ext.menu.Menu({
                items: [
                    {
                        text: 'Commands',
                        iconCls: 'cogAdd',
                        menu: cmdMenu
                    },{
                        text: 'Host Detail',
                        iconCls: 'appViewDetail',
                        handler: function() {
                            npc.hostGridClick(grid, rowIndex, e)
                        }
                    }
                ] 
            });
            menu.showAt(e.getXY());
        },

        serviceContextMenu: function(grid, rowIndex, e) {
            e.stopEvent();
            var cmdMenu = new Ext.menu.Menu();
            cmdMenu = npc.serviceCommandMenu(grid.getStore().getAt(rowIndex).data, cmdMenu);

            var menu = new Ext.menu.Menu({
                items: [
                    {
                        text: 'Commands',
                        iconCls: 'cogAdd',
                        menu: cmdMenu
                    },{
                        text: 'Service Detail',
                        iconCls: 'appViewDetail',
                        handler: function() {
                            npc.serviceGridClick(grid, rowIndex, e)
                        }
                    }
                ] 
            });
            menu.showAt(e.getXY());
        },

        setRefreshCombo: function(gridId, store, state) {

            return([
                ' ', ' ', ' ', ' ', ' ',' ', ' ', ' ', ' ', ' ',' ', ' ', ' ', ' ', ' ',
                ' ', ' ', ' ', ' ', ' ',' ', ' ', ' ', ' ', ' ',' ', ' ', ' ', ' ', ' ',
                ' ', ' ', ' ', ' ', ' ',' ', ' ', ' ', ' ', ' ',' ', ' ', ' ', '-', 'Refresh',
                new Ext.form.ComboBox({
                    store: new Ext.data.SimpleStore({
                        fields: ['name', 'value'],
                        data: [
                            ['10', 10],
                            ['30', 30],
                            ['60', 60],
                            ['120', 120],
                            ['180', 180],
                            ['240', 240],
                            ['300', 300]
                        ]
                    }),
                    name: 'refresh',
                    value: (state && state.refresh) ? state.refresh : 60,
                    displayField:'name',
                    valueField:'value',
                    forceSelection:true,
                    listeners: {
                        select: function(comboBox) {
                            if (!state) {
                                state = Ext.state.Manager.get(gridId)
                                state = state ? state : {};
                            }

                            state.refresh = comboBox.getValue();
                            Ext.state.Manager.set(gridId, state);
                            store.stopAutoRefresh();
                            store.startAutoRefresh(state.refresh);
                        }
                    },
                    mode: 'local',
                    editable:false,
                    width:50,
                    allowBlank:false,
                    triggerAction: 'all',
                    selectOnFocus:true
                })
            ])
        },

        doCommand: function(msg, post) {

            Ext.Msg.show({
                title:'Confirm',
                msg:msg,
                buttons: Ext.Msg.YESNO,
                fn: function(btn) {
                    if (btn == 'yes') {
                        npc.aPost(post);
                    }
                },
                animEl: 'elId',
                icon: Ext.MessageBox.QUESTION
            });
        },

        cmdFormButtons: [{
            text: 'Submit',
            handler: function(o){
                o.ownerCt.getForm().submit({
                    success: function(f, r) {
                        o.ownerCt.ownerCt.close();
                    },
                    failure: function(f, r) {
                        o.ownerCt.ownerCt.close();
                        response = Ext.decode(r.response.responseText);
                        Ext.Msg.alert('Failed', response.msg);
                    }
                });
            }
        },{
            text: 'Cancel',
            handler: function(o){
                o.ownerCt.ownerCt.close();
            }
        }],

        setFormFieldTooltip: function(component) {
            var label = Ext.get('x-form-el-' + component.id).prev('label');
            Ext.QuickTips.register({
                target: label,
                text: component.tooltipText,
                title: ''
            });
        },

        // A simple ajax post
        aPost: function(args) {
            Ext.Ajax.request({
                url : 'npc.php' ,
                params : args,
                callback: function (o, s, r) {
                    var o = Ext.util.JSON.decode(r.responseText)
                    if(!o.success) {
                        Ext.Msg.alert('Error', o.msg);
                    }
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

        renderEventIcon: function(val){
            if (val.match(/SERVICE ALERT:/) && val.match(/WARNING/)) {
                return String.format('<img src="images/icons/error.png">');
            } else if (val.match(/SERVICE ALERT:/) && val.match(/OK/)) {
                return String.format('<img src="images/icons/accept.png">');
            } else if (val.match(/SERVICE ALERT:/) && val.match(/CRITICAL/)) {
                return String.format('<img src="images/icons/exclamation.png">');
            } else if (val.match(/LOG ROTATION:/)) {
                return String.format('<img src="images/icons/arrow_rotate_clockwise.png">');
            } else if (val.match(/ NOTIFICATION:/)) {
                return String.format('<img src="images/icons/transmit.png">');
            } else if (val.match(/HOST ALERT:/) && (val.match(/;RECOVERY;/) || val.match(/;UP;/))) {
                return String.format('<img src="images/icons/accept.png">');
            } else if (val.match(/Finished daemonizing.../)) {
                return String.format('<img src="images/icons/arrow_up.png">');
            } else if (val.match(/ shutting down.../)) {
                return String.format('<img src="images/icons/cancel.png">');
            } else if (val.match(/Successfully shutdown/)) {
                return String.format('<img src="images/icons/stop.png">');
            } else if (val.match(/ restarting.../)) {
                return String.format('<img src="images/icons/arrow_refresh.png">');
            } else if (val.match(/EXTERNAL COMMAND/)) {
                return String.format('<img src="images/icons/resultset_next.png">');
            }

            return String.format('<img src="images/icons/information.png">');
        },

        hostStatusImage: function(val){
            var img;
            var state;
            if (val == 0) {
                img = 'greendot.gif';
                state = "UP";
            } else if (val == 1) {
                img = 'reddot.gif';
                state = "DOWN";
            } else if (val == 2) {
                img = 'reddot.gif';
                state = "UNREACHABLE";
            } else if (val == -1) {
                img = 'bluedot.gif';
                state = "PENDING";
            }
            return String.format('<p align="center"><img ext:qtip="{0}" src="images/icons/{1}"></p>', state, img);
        },

        serviceStatusImage: function(val){
            var img;
            var state;
            if (val == 0) {
                img = 'greendot.gif';
                state = "OK";
            } else if (val == 1) {
                img = 'yellowdot.gif';
                state = "WARNING";
            } else if (val == 2) {
                img = 'reddot.gif';
                state = "CRITICAL";
            } else if (val == 3) {
                img = 'orangedot.gif';
                state = "UNKNOWN";
            } else if (val == -1) {
                img = 'bluedot.gif';
                state = "PENDING";
            }
            return String.format('<p align="center"><img ext:qtip="{0}" src="images/icons/{1}"></p>', state, img);
        },

        renderExtraIcons: function(val, p, record) {
            var img = '';
            if (record.data.problem_has_been_acknowledged == 1) {
                var ack = record.data.acknowledgement.split("*|*");
                var by = '';
                if (ack[0]) {
                    by = 'by ' + ack[0];
                }
                img = String.format('&nbsp;<img ext:qtip="This problem has been acknowledged {0}" src="images/icons/wrench.png">', by);
            }
            if (record.data.notifications_enabled == 0) {
                img = String.format('&nbsp;<img ext:qtip="Notifications have been disabled." src="images/icons/sound_mute.png">') + img;
            }
            if (record.data.comment) {
                //var c = record.data.comment.split("*|*");
                img = String.format('&nbsp;<img ext:qtip="There are comments for this item" src="images/icons/comment.png">') + img;
            }
            if (record.data.is_flapping) {
                img = String.format('&nbsp;<img ext:qtip="Flapping between states" src="images/icons/text_align_justify.png">') + img;
            }
            if (!record.data.active_checks_enabled && !record.data.passive_checks_enabled) {
                img = String.format('&nbsp;<img ext:qtip="Active and passive checks have been disabled" src="images/icons/cross.png">') + img;
            } else if (!record.data.active_checks_enabled) {
                img = String.format('&nbsp;<img qtitle="Active checks disabled" ext:qtip="Passive checks are being accepted" src="images/nagios/passiveonly.gif">') + img;
            }
            return String.format('<div><div style="float: left;">{0}</div><div style="float: right;">{1}</div></div>', val, img);
        },

        renderCommentType: function(val) {
            var s;
            switch(val) {
                case '1':
                    s = 'User';
                    break;
                case '2':
                    s = 'Scheduled Downtime';
                    break;
                case '3':
                    s = 'Flap Detection';
                    break;
                case '4':
                    s = 'Acknowledgement';
                    break;
            }
            return String.format('{0}', s);
        },

        renderPersistent: function(val) {
            var s;
            switch(val) {
                case '0':
                    s = 'No';
                    break;
                case '1':
                    s = 'Yes';
                    break;
            }
            return String.format('{0}', s);
        },

        renderCommentExpires: function(val, p, record) {
            if (record.data.is_persistent == 1) {
                return String.format('NA');
            }
            if(typeof val == "object") {
                if(val.getYear() == '69') {
                    return String.format('NA');
                } else {
                    return String.format(val.dateFormat(npc.params.npc_date_format + ' ' + npc.params.npc_time_format));
                }
            }
            return val;
        },

        renderGraph: function(val, p, r) {
            return String.format('<img src="/graph_image.php?action=view&local_graph_id={0}&rra_id=1&graph_height=120&graph_width=500">', r.data.local_graph_id);
        },

        mapGraph: function(module, object_id) {
    
            var graphGrid = new Ext.grid.GridPanel({
                id: 'graphGrid',
                autoHeight:true,
                autoWidth:true,
                store: new Ext.data.JsonStore({
                    url: 'npc.php?module='+module+'&action=getMappedGraph&p_id=' + object_id,
                    totalProperty:'totalCount',
                    root:'data',
                    fields:[
                        'local_graph_id'
                    ],
                    autoload:true
                }),
                cm: new Ext.grid.ColumnModel([{
                    header:"", 
                    menuDisabled: true,
                    dataIndex:'graph',
                    renderer: npc.renderGraph,
                    width:600
                }]),
                autoExpandColumn:'Value',
                stripeRows: true,
                tbar: [
                    new Ext.form.ComboBox({
                        store: new Ext.data.JsonStore({
                            url: 'npc.php?module=cacti&action=getGraphList',
                            totalProperty:'totalCount',
                            root:'data',
                            fields:[
                                'local_graph_id',
                                'height',
                                'width',
                                'title'
                            ],
                            autoload:true
                        }),
                        fieldLabel: 'Graph',
                        displayField:'title',
                        valueField:'local_graph_id',
                        typeAhead: false,
                        forceSelection:true,
                        editable:false,
                        mode: 'remote',
                        listeners: {
                            select: function(o, n, rowIndex) {
                               var local_graph_id = o.store.getAt(rowIndex).data.local_graph_id;
                               var args = {
                                    module: module,
                                    action: 'setMappedGraph',
                                    p_object_id: object_id,
                                    p_local_graph_id: local_graph_id
                                };
                                npc.aPost(args);
                                graphGrid.store.reload();
                            }
                        },
                        triggerAction: 'all',
                        emptyText:'Select a graph...',
                        selectOnFocus:true,
                        listWidth:400,
                        width:400
                    })
                ],
                view: new Ext.grid.GridView({
                    forceFit:true,
                    autoFill:true,
                    scrollOffset:0
                })
            });

            var win = new Ext.Window({ 
                title:'Map Graph',
                layout:'fit',
                modal:true,
                closable: true,
                width:640, 
                items: graphGrid
            });
    
            win.show();
            graphGrid.render()
            graphGrid.store.load();
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
                    return String.format(val.dateFormat(npc.params.npc_date_format + ' ' + npc.params.npc_time_format));
                }
            }
            return val;
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
                tabPanel.doLayout();
            } else {
                tabPanel.add({
                    id: id, 
                    title: title,
                    closable: true,
                    deferredRender:false,
                    layoutOnTabChange:true,
                    autoScroll: true,
                    layout:'fit',
                    containerScroll: true,
                    listeners: {
                        activate: function(tab) {
                            tab.doLayout();
                        }
                    },
                    items: [
                        new Ext.TabPanel({
                            id: id + '-inner-panel',
                            style:'padding:5px 0 5px 5px',
                            deferredRender:false,
                            layoutOnTabChange:true,
                            height:600,
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
                        var refresh = Ext.state.Manager.get(id).refresh;
                        var rows = Ext.state.Manager.get(id).rows;

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
                        return {collapsed:this.collapsed, hidden:this.hidden, column:column, index:index, dt:d, refresh:refresh, rows:rows};
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
                items:[{region:'north',
                        id:'north-panel',
                        border:false,
                        layout:'column',
                        collapseMode: 'mini',
                        height:76,
                        margins:'0 0 0 0',
                        defaults: {
                            layout:'fit',
                            bodyStyle: 'padding: 0px 0px 0px 20px;',
                            border:false,
                            columnWidth: .33
                        },
                        items: [{
                            id: 'nagiosStatusCol'
                        },{
                            id: 'hostStatusCol'
                        },{
                            id: 'serviceStatusCol'
                        }]
                },{
                        region:'west',
                        id:'west-panel',
                        split:true,
                        title: 'Navigation',
                        collapsible:true,
                        width: 210,
                        minSize: 100,
                        maxSize: 400,
                        margins:'0 0 0 0',
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
                                                leaf:true,
                                                listeners: {
                                                    click: function() {
                                                        npc.hosts('Hosts', 'any');
                                                    }
                                                }
                                            },{
                                                text:'Host Problems',
                                                iconCls:'tleaf',
                                                leaf:true,
                                                listeners: {
                                                    click: function() {
                                                        npc.hosts('Host Problems', 'not_ok');
                                                    }
                                                }
                                            },{
                                                text:'Hostgroup Overview',
                                                iconCls:'tleaf',
                                                leaf:true,
                                                listeners: {
                                                    click: function() {
                                                        npc.hostgroupOverview();
                                                    }
                                                }
                                            },{
                                                text:'Hostgroup Grid',
                                                iconCls:'tleaf',
                                                leaf:true,
                                                listeners: {
                                                    click: function() {
                                                        npc.hostgroupGrid();
                                                    }
                                                }
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
                                                        npc.services('Services', 'any');
                                                    }
                                                }
                                            },{
                                                text:'Service Problems',
                                                iconCls:'tleaf',
                                                leaf:true,
                                                listeners: {
                                                    click: function() {
                                                        npc.services('Service Problems', 'not_ok');
                                                    }
                                                }
                                            },{
                                                text:'Servicegroup Overview',
                                                iconCls:'tleaf',
                                                leaf:true,
                                                listeners: {
                                                    click: function() {
                                                        npc.servicegroupOverview();
                                                    }
                                                }
                                            },{
                                                text:'Servicegroup Grid',
                                                iconCls:'tleaf',
                                                leaf:true,
                                                listeners: {
                                                    click: function() {
                                                        npc.servicegroupGrid();
                                                    }
                                                }
                                            }]
                                        },{
                                            text:'Status Map',
                                            iconCls:'tleaf',
                                            leaf:true,
                                            listeners: {
                                                click: function() {
                                                    npc.addTabExt('StatusMap','Status Map',npc.params.npc_nagios_url+'/cgi-bin/statusmap.cgi?host=all');
                                                }
                                            }
                                        },{
                                            text:'Comments',
                                            iconCls:'tleaf',
                                            leaf:true,
                                            listeners: {
                                                click: function() {
                                                    npc.comments();
                                                }
                                            }
                                        },{
                                            text:'Scheduled Downtime',
                                            iconCls:'tleaf',
                                            leaf:true,
                                            listeners: {
                                                click: function() {
                                                    npc.downtime();
                                                }
                                            }
                                        },{
                                            text:'Process Information',
                                            iconCls:'tleaf',
                                            leaf:true,
                                            listeners: {
                                                click: function() {
                                                    npc.processInfo();
                                                }
                                            }
                                        },{
                                            text:'Event Log',
                                            iconCls:'tleaf',
                                            leaf:true,
                                            listeners: {
                                                click: function() {
                                                    npc.eventLog();
                                                }
                                            }
                                        }]
                                    },{
                                        text:'Reporting',
                                        iconCls:'tnode',
                                        expanded:false,
                                        children:[{
                                            text:'Trends',
                                            iconCls:'tleaf',
                                            leaf:true,
                                            listeners: {
                                                click: function() {
                                                    npc.reporting('Trends',npc.params.npc_nagios_url+'/cgi-bin/trends.cgi');
                                                }
                                            }
                                        },{
                                            text:'Availability',
                                            iconCls:'tleaf',
                                            leaf:true,
                                            listeners: {
                                                click: function() {
                                                    npc.reporting('Availability',npc.params.npc_nagios_url+'/cgi-bin/avail.cgi');
                                                }
                                            }
                                        },{
                                            text:'Alert Histogram',
                                            iconCls:'tleaf',
                                            leaf:true,
                                            listeners: {
                                                click: function() {
                                                    npc.reporting('Alert Histogram',npc.params.npc_nagios_url+'/cgi-bin/histogram.cgi');
                                                }
                                            }
                                        },{
                                            text:'Alert History',
                                            iconCls:'tleaf',
                                            leaf:true,
                                            listeners: {
                                                click: function() {
                                                    npc.reporting('Alert History',npc.params.npc_nagios_url+'/cgi-bin/history.cgi?host=all');
                                                }
                                            }
                                        },{
                                            text:'Alert Summary',
                                            iconCls:'tleaf',
                                            leaf:true,
                                            listeners: {
                                                click: function() {
                                                    npc.reporting('Alert Summary',npc.params.npc_nagios_url+'/cgi-bin/summary.cgi');
                                                }
                                            }
                                        },{
                                            text:'Notifications',
                                            iconCls:'tleaf',
                                            leaf:true,
                                            listeners: {
                                                click: function() {
                                                    npc.reporting('Notifications',npc.params.npc_nagios_url+'/cgi-bin/notifications.cgi?contact=all');
                                                }
                                            }
                                        }]
                                    },{
                                        text:'N2C',
                                        iconCls:'tleaf',
                                        leaf:true,
                                        listeners: {
                                            click: function() {
                                                npc.n2c();
                                            }
                                        }
                                    },{
                                        text:'Nagios',
                                        iconCls:'tleaf',
                                        leaf:true,
                                        listeners: {
                                            click: function() {
                                                npc.addTabExt('Nagios','Nagios',npc.params.npc_nagios_url);
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

            // Add button to show/hide the north region
            var tb = Ext.getCmp('dashboard').getTopToolbar();

            tb.add('->', {
                id: 'northButton',
                text: 'Hide Overview',
                handler: function() {
                    var panel = Ext.getCmp('north-panel');
                    panel.toggleCollapse();

                    panel.on("collapse", function() {
                            Ext.getCmp('northButton').setText('Show Overview');
                    });

                    panel.on("expand", function() {
                            Ext.getCmp('northButton').setText('Hide Overview');
                    });
                }
            });

            // Add the portlets button to the dashboard toolbar:
            Ext.getCmp('dashboard').getTopToolbar().add('->', '-', {
                text: 'Portlets',
                handler: function() {

                    var hP = Ext.getCmp('hostProblems');
                    var hPC = hP.isVisible();

                    var eL = Ext.getCmp('eventLog');
                    var eLC = eL.isVisible();

                    var sP = Ext.getCmp('serviceProblems');
                    var sPC = sP.isVisible();

                    var mP = Ext.getCmp('monitoringPerf');
                    var mPC = mP.isVisible();

                    var sgSS = Ext.getCmp('servicegroupServiceStatus');
                    var sgSSC = sgSS.isVisible();

                    var sgHS = Ext.getCmp('servicegroupHostStatus');
                    var sgHSC = sgHS.isVisible();

                    var hgSS = Ext.getCmp('hostgroupServiceStatus');
                    var hgSSC = hgSS.isVisible();

                    var hgHS = Ext.getCmp('hostgroupHostStatus');
                    var hgHSC = hgHS.isVisible();

                    var form = new Ext.form.FormPanel({
                        bodyStyle:'padding:5px 5px 0',
                        layout: 'form',
                        border:false,
                        frame:false,
                        items: [{
                            xtype:'fieldset',
                            title: 'Show/hide portlets',
                            checkboxToggle:false,
                            autoHeight:true,
                            defaults: {width: 210},
                            defaultType: 'checkbox',
                                items: [{
                                boxLabel: 'Event Log',
                                hideLabel: true,
                                xtype:'checkbox',
                                checked: eLC,
                                listeners: {
                                    check: function(cb, checked) {
                                        if (checked) {
                                            eL.show();
                                        } else {
                                            eL.hide();
                                        }
                                    }
                                }
                            },{
                                boxLabel: 'Host Problems',
                                hideLabel: true,
                                xtype:'checkbox',
                                checked: hPC,
                                listeners: {
                                    check: function(cb, checked) {
                                        if (checked) {
                                            hP.show();
                                        } else {
                                            hP.hide();
                                        }
                                    }
                                }
                            },{
                                boxLabel: 'Service Problems',
                                hideLabel: true,
                                xtype:'checkbox',
                                checked: sPC,
                                listeners: {
                                    check: function(cb, checked) {
                                        if (checked) {
                                            sP.show();
                                        } else {
                                            sP.hide();
                                        }
                                    }
                                }
                            },{
                                boxLabel: 'Monitoring Performance',
                                hideLabel: true,
                                xtype:'checkbox',
                                checked: mPC,
                                listeners: {
                                    check: function(cb, checked) {
                                        if (checked) {
                                            mP.show();
                                        } else {
                                            mP.hide();
                                        }
                                    }
                                }
                            },{
                                boxLabel: 'Hostgroup: Service Status',
                                hideLabel: true,
                                xtype:'checkbox',
                                checked: sgSSC,
                                listeners: {
                                    check: function(cb, checked) {
                                        if (checked) {
                                            hgSS.show();
                                        } else {
                                            hgSS.hide();
                                        }
                                    }
                                }
                            },{
                                boxLabel: 'Hostgroup: Host Status',
                                hideLabel: true,
                                xtype:'checkbox',
                                checked: sgHSC,
                                listeners: {
                                    check: function(cb, checked) {
                                        if (checked) {
                                            hgHS.show();
                                        } else {
                                            hgHS.hide();
                                        }
                                    }
                                }
                            },{
                                boxLabel: 'Servicegroup: Service Status',
                                hideLabel: true,
                                xtype:'checkbox',
                                checked: sgSSC,
                                listeners: {
                                    check: function(cb, checked) {
                                        if (checked) {
                                            sgSS.show();
                                        } else {
                                            sgSS.hide();
                                        }
                                    }
                                }
                            },{
                                boxLabel: 'Servicegroup: Host Status',
                                hideLabel: true,
                                xtype:'checkbox',
                                checked: sgHSC,
                                listeners: {
                                    check: function(cb, checked) {
                                        if (checked) {
                                            sgHS.show();
                                        } else {
                                            sgHS.hide();
                                        }
                                    }
                                }
                            }]
                        }]
                    });

                    var window = new Ext.Window({
                        title:'Portlets',
                        modal:true,
                        closable: true,
                        width:300,
                        height:350,
                        layout:'fit',
                        //plain:true,
                        //bodyStyle:'padding:5px;',
                        items:form
                    });
                    window.show();
                }
            });

        // Add some portlets to the north region
        npc.nagiosStatus();
	    npc.hostSummary();
	    npc.serviceSummary();

        } // End init
    };
}();
