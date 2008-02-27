npc.app.n2c = function() {

    // Set a grid object
    var xg = Ext.grid;

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
        autoWidth:true,
        autoHeight:true,
        frame:false,
        clicksToEdit:1,
        tbar:[{
            text:'Import',
            tooltip:'Import the selected hostgroups applying the selected template.',
            iconCls:'add',
            handler : function(){
                getHosts(sm.getSelections());
            }
        }, '-', {
            text:'Cancel',
            iconCls:'cancel',
            handler : function(){
                win.close();
            }
        }]
    });

    // Setup a tab panel
    var panel = new Ext.TabPanel({
        id:'n2c-panel',
        style:'padding:10px 0 10px 10px',
        activeTab: 0,
        autoHeight:true,
        autoWidth:true,
        plain:true,
        deferredRender:false,
        defaults:{autoScroll: true},
        items:[{
            title: 'Import Hosts',
            id: 'n2c-import'
        },{
            title: 'Map Hosts',
            id: 'n2c-map'
        }]
    });

    // Create a modal window to hold our tab panel and grids
    var win = new Ext.Window({
        title:'Nagios to Cacti',
        layout:'fit',
        modal:true,
        closable: true,
        width:600,
        height:400,
        bodyStyle:'padding:5px;',
        items: panel
    });
    win.show();

    // Add the grid to the panel
    Ext.getCmp('n2c-import').add(grid);

    // Refresh the window
    win.doLayout();

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
        
        var upBar = Ext.MessageBox.progress('Import Progress');

        // A simple asynchronous pattern
        if(!!nodes.length){

            var totalNodes = nodes.length;
            var currentNode = 1;
            var progPerc;
            var textData = [];
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
                            textData[currentNode-1] = r.split("|");
                            currentNode++;
                            //upText.setValue(textData.join('\n'));
                            setTimeout(self,1);   //  <--- Adjust this to smooth out UI response time during loop
                        }
                    });
                } else {
                    upBar.hide();
                    panel.add({
                        id:'n2c-import-results',
                        title: 'Import Results',
                        closable: true
                    }).show();
                    panel.doLayout();
                    panel.setActiveTab(Ext.getCmp('n2c-import-results'));
                    //     var myData = [
                    //             ['3m Co',71.72,0.02,0.03,'9/1 12:00am'],
                    //             ['Alcoa Inc',29.01,0.42,1.47,'9/1 12:00am']
                    //     ];
                    console.log(textData);
                }
            })();
        }
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
