/* Add custom overrides here */

// Loadable menu - http://extjs.com/forum/showthread.php?t=5894&highlight=menu+json
//    var menu = new Ext.menu.Menu();
//    menu.load({ url: 'npc.php?module=services&action=getCommandsMenu&p_id=' + service_object_id });
//    $menu = '{menu: [{text:"Menu Item 1",href:"http://harmonicnewmedia.com"}]}';  
Ext.menu.Menu.prototype.load = function( options ){
    var loader = new Ext.menu.Item({text: 'Loading...'});
    var conn = new Ext.data.Connection();
    
    this.addItem(loader);
    
    conn.on('requestcomplete', function( conn, response ){
        this.remove(loader);
        response = Ext.decode(response.responseText);
        Ext.each( response.menu, function( item ){ this.add( item ) }, this);
    }, this);
    
    conn.on('requestexception', function(){
        this.remove(loader);
        this.add({text: 'Failed to load menu items'});
    }, this);
    
    conn.request( options );
}

// Property grid enhancement
Ext.grid.PropertyStore.prototype.setSource = function(o){
    this.source = o;
    this.store.removeAll();
    var data = [];
    for(var k in o){
        var avoid = false;

        if (this.grid.onlyFields && this.grid.onlyFields.length > 0) {
            avoid = true;
            if (this.grid.onlyFields.indexOf(k) > -1) {
                avoid = false;
            }
        }

        if (this.grid.avoidFields && this.grid.avoidFields.length > 0) {
            if (this.grid.avoidFields.indexOf(k) > -1) {
                avoid = true;
            }
        }


        if(!avoid && this.isEditableValue(o[k])){
            data.push(new Ext.grid.PropertyRecord({name: k, value: o[k]}, k));
        }
    }
    this.store.loadRecords({records: data}, {}, true);
}

Ext.grid.PropertyColumnModel.prototype.renderCell = function(val, p, record, rowIndex, colIndex, ds){ 
    var rv = val; 
    if (this.grid.customRenderers && this.grid.customRenderers[record.get("name")]) { 
        rv = this.grid.customRenderers[record.get("name")](this.grid.customEditors[record.get("name")],val,p, record, rowIndex, colIndex, ds); 
    } 
    else { 
        if(val instanceof Date) { 
            rv = this.renderDate(val); 
        } 
        else if(typeof val == "boolean") { 
            rv = this.renderBool(val); 
        } 
    } 
    return Ext.util.Format.htmlEncode(rv); 
}; 

