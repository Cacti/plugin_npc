npc.app.n2c = function() {

    // Set a grid object
    var xg = Ext.grid;

    // Set the selection model
    var sm = new xg.CheckboxSelectionModel();

    // Get the host groups and the number of hosts 
    // that will be imported from the server.
    var hostGroupStore = new Ext.data.JsonStore({
        url:'npc.php?module=hostgroups&action=listHostgroups',
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
                doImport(sm.getSelections());
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

    function doImport(s) {

        // Create an empty object to hold the json data sent to the server.
        var myJSON = new Object();

        // Add the hostgroup and assigned template to the object
        for(var i = 0; i < s.length; i++) {
            myJSON[i] = {'alias' : s[i].data.alias, 'template' : s[i].data.template};
        }

        // json encode our object
        var data = Ext.util.JSON.encode(myJSON);

        // Create a messagebox with progress bar.
        var dlgProgress = Ext.MessageBox.progress('', 'Importing...');

        // Set the progress bar to run forever
        dlgProgress.wait('Importing...', '', {interval:200});

        // Send the import data to the server
        Ext.Ajax.request({
            url : 'npc.php' , 
            params : { 
                module : 'sync',
                action : 'import',
                p_data : data
            },
            success: function (response) {
                console.log(response.responseText);

                // Hide the progress bar
                dlgProgress.hide();
            },
            failure: function (response) { 
                alert('Import failed.'); 

                // Hide the progress bar
                dlgProgress.hide();
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
