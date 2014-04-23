/*
Plugin Name: Recipe SEO and Nutrition Plugin
Plugin URI: http://www.edamam.com/widget
Description: Include calorie and nutritional information in your recipes automatically and completely free.
Version: 1.0
Author: Edamam LLC
Author URI: http://www.edamam.com/
License: GPLv3 or later

Copyright 2012 Edamam LLC.
This code is derived from the 2.0 build of ZipList Recipe Plugin released by: http://www.ziplist.com/recipe_plugin/ and licensed under GPLv3 or later
*/

/*
    This file is part of Edamam Plugin.

    Edamam Plugin is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    Edamam Plugin is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with Edamam Plugin. If not, see <http://www.gnu.org/licenses/>.
*/
(function() {

	tinymce.create('tinymce.plugins.edamamEditEDRecipe', {
        
        _create_Buttons : function() {
            var t = this, ed = tinyMCE.activeEditor, DOM = tinymce.DOM, editButton, deleteButton;

            DOM.remove('wp_edit_recipebtns');

            DOM.add(document.body, 'div', {
                id : 'wp_edit_recipebtns',
                style : 'display:none;'
            });

            editButton = DOM.add('wp_edit_recipebtns', 'img', {
                src : t.url+'/edit.png',
                id : 'wp_edit_recipebtn',
                width : '96',
                height : '96',
                title : 'Edit Recipe'
            });

            tinymce.dom.Event.add(editButton, 'mousedown', function(e) {
                var ed = tinyMCE.activeEditor;
                ed.windowManager.bookmark = ed.selection.getBookmark('simple');
                ed.execCommand("WP_Edit_Recipe");
            });
            
            deleteButton = DOM.add('wp_edit_recipebtns', 'img', {
                src : t.url+'/delete.png',
                id : 'wp_del_recipebtn',
                width : '96',
                height : '96',
                title : 'Delete Recipe'
            });

            tinymce.dom.Event.add(deleteButton, 'mousedown', function(e) {
                if (confirm("Are you sure you want to delete this recipe?")) {
                    var ed = tinyMCE.activeEditor, el = ed.selection.getNode(), p;

                    if ( el.nodeName == 'IMG' && ed.dom.getAttrib(el, 'class').indexOf('mceItem') == -1 ) {
                        if ( (p = ed.dom.getParent(el, 'div')) && ed.dom.hasClass(p, 'mceTemp') )
                            ed.dom.remove(p);
                        else if ( (p = ed.dom.getParent(el, 'A')) && p.childNodes.length == 1 )
                            ed.dom.remove(p);
                        else
                            ed.dom.remove(el);

                        ed.execCommand('mceRepaint');
                    }                    
                }
                
                return false;
            });
        },

        getInfo : function() {
            return {
                longname : "ZipList Recipe Plugin",
                author : 'ZipList, Inc.',
                authorurl : 'http://www.ziplist.com/',
                infourl : 'http://www.ziplist.com/recipe_plugin',
                version : "2.0"
            };
        }
    });

    tinymce.PluginManager.add('edamamrecipe', tinymce.plugins.edamamEditEDRecipe);
})();