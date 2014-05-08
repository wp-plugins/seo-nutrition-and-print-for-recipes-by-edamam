/*
Plugin Name: Recipe SEO and Nutrition Plugin
Plugin URI: http://www.edamam.com/widget
Description: Include calorie and nutritional information in your recipes automatically and completely free.
Version: 3.0
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
		init: function( editor, url ) {
			var t = this;
			t.url = url;

			//replace shortcode before editor content set
			editor.onBeforeSetContent.add(function(ed, o) {
				o.content = t._convert_codes_to_imgs(o.content);
			});

			//replace shortcode as its inserted into editor (which uses the exec command)
			editor.onExecCommand.add(function(ed, cmd) {
				if (cmd ==='mceInsertContent'){
					tinyMCE.activeEditor.setContent( t._convert_codes_to_imgs(tinyMCE.activeEditor.getContent()) );
				}
			});

			//replace the image back to shortcode on save
			editor.onPostProcess.add(function(ed, o) {
				if (o.get)
					o.content = t._convert_imgs_to_codes(o.content);
			});

			editor.addButton( 'edamamEDrecipe', {
				title: 'Edamam Recipe Plugin',
				image: url + '/edamam.png',
				onclick: function() {
					var recipe_id = null;
					if (recipe = editor.dom.select('img.edamam-recipe-recipe')[0]) {
						editor.selection.select(recipe);
						recipe_id = /edamam-recipe-recipe-([0-9]+)/i.exec(editor.selection.getNode().id);
					}
					var iframe_url = baseurl + '/wp-admin/media-upload.php?post_id=' + ((recipe_id) ? '1-' + recipe_id[1] : post_id) + '&type=edamam_recipe&tab=edamam_recipe&TB_iframe=true&width=640&height=523';
					editor.windowManager.open( {
						title: 'Recipe SEO and Nutrition Plugin',
						url: iframe_url,
						width: 700,
						height: 600,
						scrollbars : "yes",
						inline : 1,
						onsubmit: function( e ) {
							editor.insertContent( '<h3>' + e.data.title + '</h3>');
						}
					});
				}
			});
    	},

		_convert_codes_to_imgs : function(co) {
            return co.replace(/\[edamam-recipe-recipe:([0-9]+)\]/g, function(a, b) {
                return '<img id="edamam-recipe-recipe-'+b+'" class="edamam-recipe-recipe" src="' + baseurl + '/wp-content/plugins/' + dir_name + '/image.png" alt="" />';
            });
		},

		_convert_imgs_to_codes : function(co) {
			return co.replace(/\<img[^>]*?\sid="edamam-recipe-recipe-([0-9]+)[^>]*?\>/g, function(a, b){
                return '[edamam-recipe-recipe:'+b+']';
            });
		},

		getInfo : function() {
            return {
                longname : "Recipe SEO and Nutrition Plugin",
                author : 'Edamam LLC',
                authorurl : 'http://www.edamam.com/',
                infourl : 'http://wordpress.org/extend/plugins/seo-nutrition-and-print-for-recipes-by-edamam',
                version : "3.0"
            };
        }
	});

	tinymce.PluginManager.add('edamamEDrecipe', tinymce.plugins.edamamEditEDRecipe);

})();
