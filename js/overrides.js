/* Add custom overrides here */

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

