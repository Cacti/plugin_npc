
/**
  * Ext.ux.XCheckbox - nice checkbox with configurable submit values
  *
  * @author  Ing. Jozef Sakalos
  * @version $Id: Ext.ux.form.XCheckbox.js 704 2008-02-11 04:49:11Z jozo $
  * @date    10. February 2008
  *
  * Default css:
  * .xcheckbox-wrap {
  *     line-height: 18px;
  *     padding-top:2px;
  * }
  * .xcheckbox-wrap a {
  *     display:block;
  *     width:16px;
  *     height:16px;
  * }
  * .x-toolbar .xcheckbox-wrap {
  *     padding: 0 0 2px 0;
  * }
  * .xcheckbox-on {
  *     background:transparent url(./ext/resources/images/default/menu/checked.gif) no-repeat 0 0;
  * }
  * .xcheckbox-off {
  *     background:transparent url(./ext/resources/images/default/menu/unchecked.gif) no-repeat 0 0;
  * }
  *
  * @class Ext.ux.XCheckbox
  * @extends Ext.form.Checkbox
  */
Ext.ns('Ext.ux.form');
Ext.ux.form.XCheckbox = Ext.extend(Ext.form.Checkbox, {
     offCls:'xcheckbox-off'
    ,onCls:'xcheckbox-on'
    ,submitOffValue:'false'
    ,submitOnValue:'true'
    ,checked:false

    ,onRender:function(ct) {
        // call parent
        Ext.ux.form.XCheckbox.superclass.onRender.apply(this, arguments);

        // save tabIndex remove & re-create this.el
        var tabIndex = this.el.dom.tabIndex;
        var id = this.el.dom.id;
        this.el.remove();
        this.el = ct.createChild({tag:'input', type:'hidden', name:this.name, id:id});

        // update value of hidden field
        this.updateHidden();

        // adjust wrap class and create link with bg image to click on
        this.wrap.replaceClass('x-form-check-wrap', 'xcheckbox-wrap');
        this.cbEl = this.wrap.createChild({tag:'a', href:'#', cls:this.checked ? this.onCls : this.offCls});

        // support tooltip
        if(this.tooltip) {
            this.cbEl.set({qtip:this.tooltip});
        }

        // install event handlers
        this.wrap.on({click:{scope:this, fn:this.onClick, delegate:'a'}});
        this.wrap.on({keyup:{scope:this, fn:this.onClick, delegate:'a'}});

        // restore tabIndex
        this.cbEl.dom.tabIndex = tabIndex;
    } // eo function onRender

    ,onClick:function(e) {
        if(!e.isNavKeyPress()) {
            this.setValue(!this.checked);
        }
    } // eo function onClick

    ,setValue:function(val) {
        if('string' == typeof val) {
            this.checked = val === this.submitOnValue;
        }
        else {
            this.checked = !(!val);
        }

        if(this.rendered && this.cbEl) {
            this.updateHidden();
            this.cbEl.removeClass([this.offCls, this.onCls]);
            this.cbEl.addClass(this.checked ? this.onCls : this.offCls);
        }
        this.fireEvent('check', this, this.checked);

    } // eo function setValue

    ,updateHidden:function() {
        this.el.dom.value = this.checked ? this.submitOnValue : this.submitOffValue;
    } // eo function updateHidden

    ,getValue:function() {
        return this.checked;
    } // eo function getValue

}); // eo extend

// register xtype
Ext.reg('xcheckbox', Ext.ux.form.XCheckbox);

// eo file





/**
 * Ext.ux.grid.Search
 *
 * Search plugin
 *
 * @author    Ing. Jozef Sakalos
 * @copyright (c) 2008, by Ing. Jozef Sakalos
 * @date      17. January 2008
 * @version   $Id: Ext.ux.grid.Search.js 634 2008-01-19 17:00:20Z jozo $
 */

Ext.namespace('Ext.ux', 'Ext.ux.grid');

/**
 * @constructor
 */
Ext.ux.grid.Search = function(config) {
    Ext.apply(this, config, {
        // defaults
         position:'bottom'
        ,searchText:'Search'
        ,searchTipText:'Type a text to search and press Enter'
        ,iconCls:'ux-icon-search'
        ,checkIndexes:'all'
        ,disableIndexes:[]
        ,dateFormat:undefined
        ,mode:'remote'
        ,paramNames: {
             fields:'p_searchFields'
            ,query:'p_searchString'
        }
    });

    Ext.ux.grid.Search.superclass.constructor.call(this);
}; // end of constructor

Ext.extend(Ext.ux.grid.Search, Ext.util.Observable, {
    // {{{
    init:function(grid) {
        this.grid = grid;

        grid.onRender = grid.onRender.createSequence(this.onRender, this);
        grid.reconfigure = grid.reconfigure.createSequence(this.reconfigure, this);
    } // end of function init
    // }}}
    // {{{
    ,onRender:function() {
        var grid = this.grid;
        var tb = 'bottom' == this.position ? grid.bottomToolbar : grid.topToolbar;

        // add menu
        this.menu = new Ext.menu.Menu();
        tb.addSeparator();
        tb.add({
             text:this.searchText
            ,menu:this.menu
            ,iconCls:this.iconCls
        });

        // add filter field
        this.field = new Ext.form.TwinTriggerField({
             width:this.width
            ,selectOnFocus:undefined === this.selectOnFocus ? true : this.selectOnFocus
            ,trigger1Class:'x-form-clear-trigger'
            ,trigger2Class:'x-form-search-trigger'
            ,onTrigger1Click:this.onTriggerClear.createDelegate(this)
            ,onTrigger2Click:this.onTriggerSearch.createDelegate(this)
        });
        this.field.on('render', function() {
            this.field.el.dom.qtip = this.searchTipText;
            var map = new Ext.KeyMap(this.field.el, [{
                 key:Ext.EventObject.ENTER
                ,scope:this
                ,fn:this.onTriggerSearch
            },{
                 key:Ext.EventObject.ESC
                ,scope:this
                ,fn:this.onTriggerClear
            }]);
            map.stopEvent = true;
        }, this, {single:true});

        tb.add(this.field);

        // reconfigure
        this.reconfigure();
    } // end of function onRender
    // }}}
    // {{{
    ,onTriggerClear:function() {
        this.field.setValue('');
        this.field.focus();
        this.onTriggerSearch();
    } // end of function onTriggerClear
    // }}}
    // {{{
    ,onTriggerSearch:function() {
        var val = this.field.getValue();
        var store = this.grid.store;

        if('local' === this.mode) {
            store.clearFilter();
            if(val) {
                store.filterBy(function(r) {
                    var retval = false;
                    this.menu.items.each(function(item) {
                        if(!item.checked || retval) {
                            return;
                        }
                        var rv = r.get(item.dataIndex);
                        rv = rv instanceof Date ? rv.format(this.dateFormat || r.fields.get(item.dataIndex).dateFormat) : rv;
                        var re = new RegExp(val, 'gi');
                        retval = re.test(rv);
                    }, this);
                    if(retval) {
                        return true;
                    }
                    return retval;
                }, this);
            }
            else {
            }
        }
        else {
            // clear start (necessary if we have paging)
            if(store.lastOptions) {
                store.lastOptions.params[store.paramNames.start] = 0;
            }

            // get fields to search array
            var fields = [];
            this.menu.items.each(function(item) {
                if(item.checked) {
                    fields.push(item.dataIndex);
                }
            });

            // add fields and query to baseParams of store
            delete(store.baseParams[this.paramNames.fields]);
            delete(store.baseParams[this.paramNames.query]);
            delete(store.lastOptions.params[this.paramNames.fields]);
            delete(store.lastOptions.params[this.paramNames.query]);
            if(fields.length) {
                store.baseParams[this.paramNames.fields] = Ext.encode(fields);
                store.baseParams[this.paramNames.query] = val;
            }

            // reload store
            store.reload();
        }

    } // end of function onTriggerSearch
    // }}}
    // {{{
    // private 
    ,reconfigure:function() {

        // {{{
        // remove old items
        var menu = this.menu;
        menu.removeAll();

        // }}}
        // {{{
        // add new items
        var cm = this.grid.colModel;
        Ext.each(cm.config, function(config) {
            var disable = false;
            if(config.header) {
                Ext.each(this.disableIndexes, function(item) {
                    disable = disable ? disable : item === config.dataIndex;
                });
                if(!disable) {
                    menu.add(new Ext.menu.CheckItem({
                         text:config.header
                        ,hideOnClick:false
                        ,checked:'all' === this.checkIndexes
                        ,dataIndex:config.dataIndex
                    }));
                }
            }
        }, this);
        // }}}
        // {{{
        // check items
        if(this.checkIndexes instanceof Array) {
            Ext.each(this.checkIndexes, function(di) {
                var item = menu.items.find(function(itm) {
                    return itm.dataIndex === di;
                });
                if(item) {
                    item.setChecked(true, true);
                }
            }, this);
        }
        // }}}

    } // end of function reconfigure
    // }}}

}); // end of extend

