npc.app.n2c = function() {

    // Set a grid object
    var xg = Ext.grid;

    var upBar;
    var results = [];

    var tabPanel = Ext.getCmp('centerTabPanel');
    var tab = Ext.getCmp('n2c-panel');

    // Set the selection model
    var sm = new xg.CheckboxSelectionModel();

    // Get the host groups and the number of hosts 
    // that will be imported from the server.
    var hostGroupStore = new Ext.data.JsonStore({
        url:'npc.php?module=sync&action=listHostgroups',
        totalProperty:'totalCount',
        root:'data',
        fields:[
            'alias',
            'members',
            'template'
        ],
        autoload:true
    });

    // Get the host template names and id's from the server.
    var templateStore = new Ext.data.JsonStore({
        url:'npc.php?module=cacti&action=getHostTemplates',
        totalProperty:'totalCount',
        root:'data',
        id: 'id',
        fields:[
            'id',
            'name'
        ],
        autoload:true
    });
    templateStore.load();

    // Setup our column model
    var cm = new Ext.grid.ColumnModel([
        sm,
        {
            header: "Hostgroup",
            dataIndex: 'alias',
            width: 200,
        },{
            header: "Hosts",
            dataIndex: 'members',
            width: 50,
        },{
            header: "Template",
            dataIndex: 'template',
            renderer:renderComboDisplay,
            width: 200,
            editor: new Ext.form.ComboBox({
                triggerAction: 'all',
                name:'template',
                valueField:'id',
                displayField:'name',
                editable:false,
                autoWidth:true,
                emptyText:'Select a template...',
                store: templateStore,
                lazyRender:true,
                listClass: 'x-combo-list-small'
            })
        }
    ]);

    // create the editor grid
    var grid = new Ext.grid.EditorGridPanel({
        store: hostGroupStore,
        border:false,
        cm:cm,
        sm:sm,
        width:600,
        autoHeight:true,
        frame:false,
        clicksToEdit:1,
        view: new Ext.grid.GridView({
            forceFit:true,
            autoFill:true
        }),
        tbar:[{
            text:'Import',
            tooltip:'Import the selected hostgroups with the selected template.',
            iconCls:'add',
            handler : function(){
                var tab = Ext.getCmp('n2c-results');
                if (tab) {
                    panel.remove(tab, true);
                }
                getHosts(sm.getSelections());
            }
        }]
    });

    if (tab)  {
        tabPanel.setActiveTab(tab);
    } else {
        tabPanel.add({
            title: 'N2C',
            closable:true,
            height:400,
            width:600,
            deferredRender:false,
            layoutOnTabChange: true,
            defaults:{autoScroll: true},
            items: [
                new Ext.TabPanel({
                    id:'n2c-panel',
                    style:'padding:5px 0 5px 5px',
                    activeTab: 0,
                    autoHeight:true,
                    autoWidth:true,
                    plain:true,
                    deferredRender:false,
                    defaults:{autoScroll: true},
                    items:[{
                        title: 'Import Hosts',
                        id: 'n2c-import',
                        layout:'fit',
                        items: [ grid ]
                    }]
                })
            ]
        }).show();
        tabPanel.doLayout();
        tabPanel.setActiveTab(tab);
    }

    // Render the grid
    grid.render();

    // Load the hostgroup data store
    hostGroupStore.load();

    function renderComboDisplay(v) {
        if (v) {
            return String.format('{0}', templateStore.getById(v).get('name'));
        }
    }

    function doImport(nodes) {
        
        if (!nodes.length){
            return(Ext.Msg.alert('Error', 'There are no hosts available for import in the selected hostgroup.'));
        }

        upBar = Ext.MessageBox.progress('Import Progress');
        results = [];

        // A simple asynchronous pattern
        if(!!nodes.length){

            var totalNodes = nodes.length;
            var currentNode = 1;
            var progPerc;
            var dt = new Date();
            var cacheId = dt.getTime();
   
            (function(){
                var node, self = arguments.callee ;
                if(node = nodes.shift()){
                    progPerc = (currentNode/totalNodes);
                    upBar.updateProgress(progPerc, 'Importing host '+currentNode+' of '+totalNodes);
                    Ext.Ajax.request({
                        method: 'GET',
                        timeout: 10000,
                        url : 'npc.php' , 
                        params : { 
                            module : 'sync',
                            action : 'import',
                            p_host_object_id : node.host_object_id,
                            p_description : node.display_name,
                            p_ip : node.address,
                            p_template_id : node.template,
                            p_cache_id : cacheId
                        },
                        success: function(response){
                            r = response.responseText;
                            results[currentNode-1] = r.split("|");
                            currentNode++;
                            setTimeout(self,1);   // Adjust this to smooth out UI response time during loop
                        }
                    });
                } else {
                    importResults();
                }
            })();
        }
    }

    function importResults() {

        // Hide the progress bar
        upBar.hide();

        function renderCheck(val) {
            var img;
            if (val == 0) {
                return '';
            } else {
                return String.format('<p align="center"><img src="images/icons/tick.png"></p>');
            }
        }

        // Create a store to hold the results
        var resultsStore = new Ext.data.SimpleStore({
            fields: [
                {name: 'host'},
                {name: 'imported', type: 'int'},
                {name: 'mapped', type: 'int'},
                {name: 'message'}
            ]
        });

        var cm = new Ext.grid.ColumnModel([{
            header:"Host",
            dataIndex:'host',
            sortable:true,
            width:100
        },{
            header:"Imported",
            dataIndex:'imported',
            renderer:renderCheck,
            width:45
        },{
            header:"Mapped",
            dataIndex:'mapped',
            renderer:renderCheck,
            width:45
        },{
            header:"Message",
            dataIndex:'message',
            width:300
        }]);

        // create the Grid
        var resultsGrid = new Ext.grid.GridPanel({
            id: 'n2cResultsGrid',
            store: resultsStore,
            border:false,
            cm:cm,
            sm: new Ext.grid.RowSelectionModel({singleSelect:true}),
            view: new Ext.grid.GridView({
                forceFit:true,
                autoFill:true
            }),
            stripeRows: true,
            autoHeight:true,
            width:600
        });

        Ext.getCmp('n2c-panel').add({
            id:'n2c-results',
            title: 'Import Results',
            deferredRender: false,
            layout:'fit',
            closable: true,
            items: [resultsGrid]
        }).show();
        tabPanel.doLayout();
        Ext.getCmp('n2c-panel').setActiveTab(Ext.getCmp('n2c-results'));

        // Render the grid
        resultsGrid.render();

        // Load the results data
        resultsStore.loadData(results);
    }

    function getHosts(s) {

        var hg = new Object();

        // Get the hosts for the selected hostgroups
        for(var i = 0; i < s.length; i++) {
            hg[i] = {'alias' : s[i].data.alias, 'template' : s[i].data.template};
        }

        // json encode our object
        var data = Ext.util.JSON.encode(hg);

        // Get all the hosts that will be imported
        Ext.Ajax.request({
            url : 'npc.php' , 
            params : { 
                module : 'sync',
                action : 'getHosts',
                p_data : data
            },
            success: function (response) {
                doImport(Ext.util.JSON.decode(response.responseText));
            },
            failure: function (response) { 
                alert('Import failed.'); 
            } 
        });
    }

    // Set the grid template field to display the template name
    grid.on('afteredit', function(o){
        if(o){
            var grid=o.grid;
            var record=o.record;
            var cm=grid.getColumnModel();
            var editor=cm.getCellEditor( o.column, o.row);
            var v=editor.getValue();
            
            if(editor.field.hiddenField){
                var v=editor.field.hiddenField.value;
                record.set('selected', v);
            }
        }
    });
}
