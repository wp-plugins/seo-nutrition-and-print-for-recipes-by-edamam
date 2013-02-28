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

var win=null;
function zlrPrint(id)
{
	var content = document.getElementById(id).innerHTML;
	win = window.open();
	self.focus();
	win.document.open();
	win.document.write('<html><head>');
	win.document.write('<link charset=\'utf-8\' href=\'http://www.edamam.com/widget/recipe/source/style.css\' rel=\'stylesheet\' type=\'text/css\' />');
	win.document.write('<style>.hide-print{display: none;}</style>');
	win.document.write('</head><body>');
	win.document.write('<div id=\'recipe-print-container\' >');
	win.document.write(content);
	win.document.write('</div>');
	win.document.write('</body></html>');
	win.document.close();
	win.print();
	// win.close();
}
