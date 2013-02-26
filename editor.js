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
        
        init : function(ed, url) {
            var t = this;

            t.url = url;
            t._create_Buttons();

            // Register the command so that it can be invoked by using tinyMCE.activeEditor.execCommand('...');
            ed.addCommand('WP_Edit_Recipe', function() {
                var el = ed.selection.getNode(), vp = tinymce.DOM.getViewPort(), H = vp.h, W = ( 720 < vp.w ) ? 720 : vp.w, cls = ed.dom.getAttrib(el, 'class'), id = ed.dom.getAttrib(el, 'id').replace('edamam-recipe-recipe-', '');

                if ( cls.indexOf('mceItem') != -1 || cls.indexOf('wpGallery') != -1 || el.nodeName != 'IMG' )
                    return;

                tb_show('', baseurl + '/wp-admin/media-upload.php?post_id=1-' + id + '&type=edamam_recipe&tab=edamam_recipe&TB_iframe=true&width=640&height=523');
                // tb_show('', url + '/editimage.html?ver=321&TB_iframe=true');
                tinymce.DOM.setStyles('TB_window', {
                    'width':( W - 50 )+'px',
                    'height':( H - 45 )+'px',
                    'margin-left':'-'+parseInt((( W - 50 ) / 2),10) + 'px'
                });

                if ( ! tinymce.isIE6 ) {
                    tinymce.DOM.setStyles('TB_window', {
                        'top':'20px',
                        'marginTop':'0'
                    });
                }

                tinymce.DOM.setStyles('TB_iframeContent', {
                    'width':( W - 50 )+'px',
                    'height':( H - 75 )+'px'
                });
                tinymce.DOM.setStyle( ['TB_overlay','TB_window','TB_load'], 'z-index', '999999' );
            });


            ed.onInit.add(function(ed) {
                tinymce.dom.Event.add(ed.getBody(), 'dragstart', function(e) {
                    if ( !tinymce.isGecko && e.target.nodeName == 'IMG' && ed.dom.getParent(e.target, 'dl.wp-caption') )
                    return tinymce.dom.Event.cancel(e);
                });
            });

            // show editimage buttons
            ed.onMouseDown.add(function(ed, e) {
                var p;

                if ( e.target.nodeName == 'IMG' && ed.dom.getAttrib(e.target, 'class').indexOf('mceItem') == -1 && ed.dom.getAttrib(e.target, 'class').indexOf('edamam-recipe-recipe') === 0) {

                    ed.plugins.wordpress._hideButtons();
                    t._show_Buttons(e.target, 'wp_edit_recipebtns');
                    if ( tinymce.isGecko && (p = ed.dom.getParent(e.target, 'dl.wp-caption')) && ed.dom.hasClass(p.parentNode, 'mceTemp') ) {
                        ed.selection.select(p.parentNode);
                    }
                } else {
                    t._hideButtons();
                }
            });

            // when pressing Return inside a caption move the cursor to a new parapraph under it
            ed.onKeyPress.add(function(ed, e) {
                var n, DL, DIV, P;

                if ( e.keyCode == 13 ) {
                    n = ed.selection.getNode();
                    DL = ed.dom.getParent(n, 'dl.wp-caption');
                    DIV = ed.dom.getParent(DL, 'div.mceTemp');

                    if ( DL && DIV ) {
                        P = ed.dom.create('p', {}, '&nbsp;');
                        ed.dom.insertAfter( P, DIV );

                        if ( P.firstChild )
                            ed.selection.select(P.firstChild);
                        else
                            ed.selection.select(P);

                        tinymce.dom.Event.cancel(e);
                        
                        return false;
                    }
                }
            });

            ed.onInit.add(function(ed) {
                tinymce.dom.Event.add(ed.getWin(), 'scroll', function(e) {
                    t._hideButtons();
                });
                tinymce.dom.Event.add(ed.getBody(), 'dragstart', function(e) {
                    t._hideButtons();
                });
            });

            ed.onBeforeExecCommand.add(function(ed, cmd, ui, val) {
                t._hideButtons();
            });

            ed.onSaveContent.add(function(ed, o) {
                t._hideButtons();
            });
            
            ed.onMouseDown.add(function(ed, e) {
                if ( e.target.nodeName != 'IMG' )
                    t._hideButtons();
            });

            ed.onBeforeSetContent.add(function(ed, o) {
                o.content = t._do_sh_code(o.content);
            });

            ed.onPostProcess.add(function(ed, o) {
                if (o.get) {
                    o.content = t._get_sh_code(o.content);
                }
            });
        },

        _show_Buttons : function(n, id) {
            var ed = tinyMCE.activeEditor, p1, p2, vp, DOM = tinymce.DOM, X, Y;

            vp = ed.dom.getViewPort(ed.getWin());
            p1 = DOM.getPos(ed.getContentAreaContainer());
            p2 = ed.dom.getPos(n);

            X = Math.max(p2.x - vp.x, 0) + p1.x;
            Y = Math.max(p2.y - vp.y, 0) + p1.y;

            DOM.setStyles(id, {
                'top' : Y+59+'px',
                'left' : X+87+'px',
                'display' : 'block'
            });
        },

        _hideButtons : function() {
            if (document.getElementById('wp_edit_recipebtns'))
                tinymce.DOM.hide('wp_edit_recipebtns');
        },

        _do_sh_code : function(co) {
            return co.replace(/\[edamam-recipe-recipe:([0-9]+)\]/g, function(a, b) {
                return '<img id="edamam-recipe-recipe-'+b+'" class="edamam-recipe-recipe" src="' + baseurl + '/wp-content/plugins/' + dir_name + '/image.png" alt="" />';
            });
        },

        _get_sh_code : function(co) {
            return co.replace(/\<img[^>]*?\sid="edamam-recipe-recipe-([0-9]+)[^>]*?\>/g, function(a, b){
                
                return '[edamam-recipe-recipe:'+b+']';
            });
        },

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