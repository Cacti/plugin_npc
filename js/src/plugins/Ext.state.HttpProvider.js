/**
 * @copyright Copyright Intermesh 2007
 * @author Merijn Schering <mschering@intermesh.nl>
 *
 * This file is part of Group-Office.
 *
 * Group-Office is free software; you can redistribute it and/or modify it
 * under the terms of the GNU General Public License as published by the
 * Free Software Foundation; either version 2 of the License, or (at your
 * option) any later version.
 *
 * See file /LICENSE.GPL
 */

/**
 * @class Ext.state.HttpProvider
 * @extends Ext.state.Provider
 * The default Provider implementation which saves state via XmlHttpRequest calls to save it in
 * a database.
 * <br />Usage:
 <pre><code>
   var cp = new Ext.state.HttpProvider({
      url: state.php
   });
   Ext.state.Manager.setProvider(cp);

   A global variable ExtState must be created!
   this variable holds all the all the values like this:

   var ExtState = { name: value };

   For example if you have a PHP array of settings you can do this in the document head:

   var ExtState = Ext.decode('<?php echo json_encode($state); ?>');


   The $state PHP variable holds all the values that you can pull from a database


 </code></pre>
 * @cfg {String} url The server page that will handle the request to save the state
 * when a value changes it will send 'name' and 'value' to that page.
 *
 * @constructor
 * Create a new HttpProvider
 * @param {Object} config The configuration object
 */
Ext.state.HttpProvider = function(config){
    Ext.state.HttpProvider.superclass.constructor.call(this);
    this.url = "";

    Ext.apply(this, config);
    this.state = this.readValues();
};

Ext.extend(Ext.state.HttpProvider, Ext.state.Provider, {
    // private
    set : function(name, value){
        if(typeof value == "undefined" || value === null){
            this.clear(name);
            return;
        }
        this.setValue(name, value);
        Ext.state.HttpProvider.superclass.set.call(this, name, value);
    },

    // private
    clear : function(name){
        this.clearValue(name);
        Ext.state.HttpProvider.superclass.clear.call(this, name);
    },

    // private
    readValues : function(){
        var state = {};

        for (var name in ExtState)
        {
            if(name!='remove')
            {
                state[name] = this.decodeValue(ExtState[name]);
            }
        }
        return state;
    },

    // private
    setValue : function(name, value){

        var conn = new Ext.data.Connection();
        conn.request({
            url: this.url,
            params: {task: 'set', 'p_name': name, 'p_value': this.encodeValue(value) }
        });

    },

    // private
    clearValue : function(name){
        var conn = new Ext.data.Connection();
        conn.request({
            url: this.url,
            params: {task: 'set', 'name': name, 'value': 'null' }
        });
    }
});

