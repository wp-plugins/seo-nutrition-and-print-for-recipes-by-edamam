<?php
/*
Plugin Name: Recipe SEO and Nutrition Plugin
Plugin URI: http://www.edamam.com/widget
Description: Include calorie and nutritional information in your recipes automatically and completely free.
Version: 3.1
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

// Make sure we don't expose any info if called directly
if ( !function_exists( 'add_action' ) ) {
	echo "Hey!  This is just a plugin, not much it can do when called directly.";
	exit;
}

if (!defined('EDAMAM_RECIPE_VERSION_KEY'))
    define('EDAMAM_RECIPE_VERSION_KEY', 'edamam_recipe_version');

if (!defined('EDAMAM_RECIPE_VERSION_NUM'))
    define('EDAMAM_RECIPE_VERSION_NUM', '3.1'); //!!mwp
    
if (!defined('EDAMAM_RECIPE_PLUGIN_DIRECTORY'))
    define('EDAMAM_RECIPE_PLUGIN_DIRECTORY', get_option('siteurl') . '/wp-content/plugins/' . dirname(plugin_basename(__FILE__)) . '/');

add_option(EDAMAM_RECIPE_VERSION_KEY, EDAMAM_RECIPE_VERSION_NUM);  // sort of useless as is never updated
add_option("edamam_recipe_db_version"); // used to store DB version

add_option('edamam_partner_key'); //!!mwp

add_option('recipe_logo', 'yes'); //!!dc
add_option('recipe_background', '#FFFFFF'); //!!dc
add_option('recipe_nutritional_info', 'yes'); //!!dc
add_option('recipe_title_hide', ''); //!!dc (oops, btw)
add_option('recipe_image_hide', ''); //!!dc
add_option('recipe_image_hide_print', 'Hide'); //!!dc
add_option('recipe_print_link_hide', ''); //!!dc
add_option('recipe_ingredient_label', 'Ingredients');
add_option('recipe_ingredient_label_hide', '');
add_option('recipe_ingredient_list_type', 'ul');
add_option('recipe_instruction_label', 'Instructions'); //!!mwp
add_option('recipe_instruction_label_hide', '');
add_option('recipe_instruction_list_type', 'ol');
add_option('recipe_notes_label', 'Notes'); //!!dc
add_option('recipe_notes_label_hide', ''); //!!dc
add_option('recipe_prep_time_label', 'Prep Time:');
add_option('recipe_prep_time_label_hide', '');
add_option('recipe_cook_time_label', 'Cook Time:');
add_option('recipe_cook_time_label_hide', '');
add_option('recipe_total_time_label', 'Total Time:');
add_option('recipe_total_time_label_hide', '');
add_option('recipe_serving_size_label', 'Number of servings:');
add_option('recipe_serving_size_label_hide', '');
add_option('recipe_rating_label', 'Rating:'); //!!dc
add_option('recipe_rating_label_hide', ''); //!!dc
add_option('recipe_image_width', ''); //!!dc
add_option('recipe_outer_border_style', ''); //!!dc

register_activation_hook(__FILE__, 'edamam_recipe_install');
add_action('plugins_loaded', 'edamam_recipe_install');

register_activation_hook(__FILE__, 'edamam_register');

add_action('admin_head', 'edamam_recipe_add_recipe_button');
add_action('admin_head','edamam_recipe_js_vars');

function edamam_recipe_js_vars() {

    global $current_screen;
    $type = $current_screen->post_type;

    if (is_admin() && $type == 'post' || $type == 'page') {
        ?>
        <script type="text/javascript">
        var post_id = '<?php global $post; echo $post->ID; ?>';
        </script>
        <?php
    }
}

if (strpos($_SERVER['REQUEST_URI'], 'media-upload.php') && strpos($_SERVER['REQUEST_URI'], '&type=edamam_recipe') && !strpos($_SERVER['REQUEST_URI'], '&wrt='))
{
	edamam_recipe_iframe_content($_POST, $_REQUEST);
	exit;
}

global $recipe_db_version;
$recipe_db_version = "3.2";	// This must be changed when the DB structure is modified

// Creates Edamam tables in the db if they don't exist already.
// Don't do any data initialization in this routine as it is called on both install as well as
//   every plugin load as an upgrade check.
//
// Updates the table if needed
// Plugin Ver  DB Ver
//   1.0        3.1
//   2.0        3.1
//   2.1        3.1
//   2.2        3.1
//   2.3        3.2
//   2.4        3.2
//   2.5        3.2
//   2.6        3.2
//   2.7        3.2
//   2.8        3.2
//   2.9        3.2
//   3.0        3.2
//   3.1        3.2

global $edamam_url_api;
$edamam_url_api = "http://www.edamam.com/api/nutrient-info";

global $edamam_register_api;
$edamam_register_api = "http://www.edamam.com/api/widget-key";

function edamam_register(){
  
  global $edamam_register_api;
  $partner_key = get_option('edamam_partner_key');
  
    $site_url = site_url();
    $title = get_bloginfo('name');
    $title = str_replace(" ", "+", $title);
    $title = htmlspecialchars($title);
    $email = get_bloginfo('admin_email');
    
    if($email == ""){
      $json_url = $edamam_register_api.'?blogUrl='.urlencode($site_url).'&blogName='.urlencode($title);
    } else {
      $json_url = $edamam_register_api.'?blogUrl='.urlencode($site_url).'&blogName='.urlencode($title).'&email='.urlencode($email);
    }   

    $params = array('http' => array(
                   'method' => 'GET'
              ));
    if ($optional_headers !== null) {
       $params['http']['header'] = $optional_headers;
    }
    $ctx = stream_context_create($params);
    $fp = @fopen($json_url, 'rb', false, $ctx);
    if (!$fp) {
       //throw new Exception("Problem with $url, $php_errormsg");
    }
    $response = @stream_get_contents($fp);
      
    $response = substr($response, 19, -3);
    update_option('edamam_partner_key', $response);  
    
}

function edamam_recipe_install() {
    global $wpdb;
    global $recipe_db_version;

    $recipes_table = $wpdb->prefix . "edamam_recipe_recipes";
    $installed_db_ver = get_option("edamam_recipe_db_version");
    
    if(strcmp($installed_db_ver, $recipe_db_version) != 0) {				// An older (or no) database table exists
        $sql = "CREATE TABLE " . $recipes_table . " (
            recipe_id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            post_id BIGINT(20) UNSIGNED NOT NULL,
            recipe_title TEXT,
            recipe_image TEXT,
            summary TEXT,
            rating TEXT,
            prep_time TEXT,
            cook_time TEXT,
            total_time TEXT,
            serving_size VARCHAR(50),
            ingredients TEXT,
            instructions TEXT,
            notes TEXT,
            created_at TIMESTAMP DEFAULT NOW(),
            servings VARCHAR(50),
            calories VARCHAR(50),
            fatlabel VARCHAR(50),
            fatquantity VARCHAR(50),
            fatunit VARCHAR(50),
            carbslabel VARCHAR(50),
            carbsquantity VARCHAR(50),
            carbsunit VARCHAR(50),
            proteinlabel VARCHAR(50),
            proteinquantity VARCHAR(50),
            proteinunit VARCHAR(50)
        	);";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);

        update_option("edamam_recipe_db_version", $recipe_db_version);

    }
}

add_action('admin_menu', 'edamam_recipe_menu_pages');

// Adds module to left sidebar in wp-admin for EDRecipe
function edamam_recipe_menu_pages() {
    // Add the top-level admin menu
    $page_title = 'Recipe SEO and Nutrition Settings';
    $menu_title = 'Recipe SEO and Nutrition';
    $capability = 'manage_options';
    $menu_slug = 'recipe-settings';
    $function = 'edamam_recipe_settings';
    add_menu_page($page_title, $menu_title, $capability, $menu_slug, $function);

    // Add submenu page with same slug as parent to ensure no duplicates
    $settings_title = 'Settings';
    add_submenu_page($menu_slug, $page_title, $settings_title, $capability, $menu_slug, $function);
}

// Adds 'Settings' page to the EDRecipe module
function edamam_recipe_settings() {
    if (!current_user_can('manage_options')) {
        wp_die('You do not have sufficient permissions to access this page.');
    }
    
    $recipe_icon = EDAMAM_RECIPE_PLUGIN_DIRECTORY . "edamam.gif";
    
    if ($_POST['ingredient-list-type']) {
    	$edamam_partner_key = $_POST['edamam-partner-key'];

        $recipe_nutritional_info = $_POST['recipe-nutritional-info'];
        $recipe_logo = $_POST['recipe-logo'];
        $recipe_background = $_POST['recipe-background'];
        $recipe_title_hide = $_POST['recipe-title-hide'];
        $image_hide = $_POST['image-hide'];
        $image_hide_print = $_POST['image-hide-print'];
        $print_link_hide = $_POST['print-link-hide'];
        $ingredient_label = $_POST['ingredient-label'];
        $ingredient_label_hide = $_POST['ingredient-label-hide'];
        $ingredient_list_type = $_POST['ingredient-list-type'];
        $instruction_label = $_POST['instruction-label'];
        $instruction_label_hide = $_POST['instruction-label-hide'];
        $instruction_list_type = $_POST['instruction-list-type'];
        $notes_label = $_POST['notes-label'];
        $notes_label_hide = $_POST['notes-label-hide'];
        $prep_time_label = $_POST['prep-time-label'];
        $prep_time_label_hide = $_POST['prep-time-label-hide'];
        $cook_time_label = $_POST['cook-time-label'];
        $cook_time_label_hide = $_POST['cook-time-label-hide'];
        $total_time_label = $_POST['total-time-label'];
        $total_time_label_hide = $_POST['total-time-label-hide'];
        $serving_size_label = $_POST['serving-size-label'];
        $serving_size_label_hide = $_POST['serving-size-label-hide'];
        $rating_label = $_POST['rating-label'];
        $rating_label_hide = $_POST['rating-label-hide'];
        $image_width = $_POST['image-width'];
        $outer_border_style = $_POST['outer-border-style'];
        
        update_option('edamam_partner_key', $edamam_partner_key);
        update_option('recipe_nutritional_info', $recipe_nutritional_info);
        update_option('recipe_logo', $recipe_logo);
        update_option('recipe_background', $recipe_background);
        update_option('recipe_title_hide', $recipe_title_hide);
        update_option('recipe_image_hide', $image_hide);
        update_option('recipe_image_hide_print', $image_hide_print);
        update_option('recipe_print_link_hide', $print_link_hide);
        update_option('recipe_ingredient_label', $ingredient_label);
        update_option('recipe_ingredient_label_hide', $ingredient_label_hide);
        update_option('recipe_ingredient_list_type', $ingredient_list_type);
        update_option('recipe_instruction_label', $instruction_label);
        update_option('recipe_instruction_label_hide', $instruction_label_hide);
        update_option('recipe_instruction_list_type', $instruction_list_type);
        update_option('recipe_notes_label', $notes_label);
        update_option('recipe_notes_label_hide', $notes_label_hide);
        update_option('recipe_prep_time_label', $prep_time_label);
        update_option('recipe_prep_time_label_hide', $prep_time_label_hide);
        update_option('recipe_cook_time_label', $cook_time_label);
        update_option('recipe_cook_time_label_hide', $cook_time_label_hide);
        update_option('recipe_total_time_label', $total_time_label);
        update_option('recipe_total_time_label_hide', $total_time_label_hide);
        update_option('recipe_serving_size_label', $serving_size_label);
        update_option('recipe_serving_size_label_hide', $serving_size_label_hide);
        update_option('recipe_rating_label', $rating_label);
        update_option('recipe_rating_label_hide', $rating_label_hide);
        update_option('recipe_image_width', $image_width);
        update_option('recipe_outer_border_style', $outer_border_style);
    } else {
        $edamam_partner_key = get_option('edamam_partner_key');
        $recipe_nutritional_info = get_option('recipe_nutritional_info');
        $recipe_logo = get_option('recipe_logo');
        $recipe_background = get_option('recipe_background');
        $recipe_title_hide = get_option('recipe_title_hide');
        $image_hide = get_option('recipe_image_hide');
        $image_hide_print = get_option('recipe_image_hide_print');
        $print_link_hide = get_option('recipe_print_link_hide');
        $ingredient_label = get_option('recipe_ingredient_label');
        $ingredient_label_hide = get_option('recipe_ingredient_label_hide');
        $ingredient_list_type = get_option('recipe_ingredient_list_type');
        $instruction_label = get_option('recipe_instruction_label');
        $instruction_label_hide = get_option('recipe_instruction_label_hide');
        $instruction_list_type = get_option('recipe_instruction_list_type');
        $notes_label = get_option('recipe_notes_label');
        $notes_label_hide = get_option('recipe_notes_label_hide');
        $prep_time_label = get_option('recipe_prep_time_label');
        $prep_time_label_hide = get_option('recipe_prep_time_label_hide');
        $cook_time_label = get_option('recipe_cook_time_label');
        $cook_time_label_hide = get_option('recipe_cook_time_label_hide');
        $total_time_label = get_option('recipe_total_time_label');
        $total_time_label_hide = get_option('recipe_total_time_label_hide');
        $serving_size_label = get_option('recipe_serving_size_label');
        $serving_size_label_hide = get_option('recipe_serving_size_label_hide');
        $rating_label = get_option('recipe_rating_label');
        $rating_label_hide = get_option('recipe_rating_label_hide');
        $image_width = get_option('recipe_image_width');
        $outer_border_style = get_option('recipe_outer_border_style');
    }
    
    $recipe_title_hide = (strcmp($recipe_title_hide, 'Hide') == 0 ? 'checked="checked"' : '');
    $image_hide = (strcmp($image_hide, 'Hide') == 0 ? 'checked="checked"' : '');
    $image_hide_print = (strcmp($image_hide_print, 'Hide') == 0 ? 'checked="checked"' : '');
    $print_link_hide = (strcmp($print_link_hide, 'Hide') == 0 ? 'checked="checked"' : '');
    $recipe_nutritional_info = (strcmp($recipe_nutritional_info, 'yes') == 0 ? 'checked="checked"' : '');
    $recipe_logo = (strcmp($recipe_logo, 'yes') == 0 ? 'checked="checked"' : '');

    // Outer (hrecipe) border style
  	$obs = '';
  	$borders = array('None' => '', 'Solid' => '1px solid', 'Dotted' => '1px dotted', 'Dashed' => '1px dashed', 'Thick Solid' => '2px solid', 'Double' => 'double');
  	foreach ($borders as $label => $code) {
  		$obs .= '<option value="' . $code . '" ' . (strcmp($outer_border_style, $code) == 0 ? 'selected="true"' : '') . '>' . $label . '</option>';
  	}

    $ingredient_label_hide = (strcmp($ingredient_label_hide, 'Hide') == 0 ? 'checked="checked"' : '');
    
    $ing_ul = (strcmp($ingredient_list_type, 'ul') == 0 ? 'checked="checked"' : '');
    $ing_ol = (strcmp($ingredient_list_type, 'ol') == 0 ? 'checked="checked"' : '');
    $ing_p = (strcmp($ingredient_list_type, 'p') == 0 ? 'checked="checked"' : '');
    $ing_div = (strcmp($ingredient_list_type, 'div') == 0 ? 'checked="checked"' : '');
    
    $instruction_label_hide = (strcmp($instruction_label_hide, 'Hide') == 0 ? 'checked="checked"' : '');
    
    $ins_ul = (strcmp($instruction_list_type, 'ul') == 0 ? 'checked="checked"' : '');
    $ins_ol = (strcmp($instruction_list_type, 'ol') == 0 ? 'checked="checked"' : '');
    $ins_p = (strcmp($instruction_list_type, 'p') == 0 ? 'checked="checked"' : '');
    $ins_div = (strcmp($instruction_list_type, 'div') == 0 ? 'checked="checked"' : '');

    $prep_time_label_hide = (strcmp($prep_time_label_hide, 'Hide') == 0 ? 'checked="checked"' : '');
    $cook_time_label_hide = (strcmp($cook_time_label_hide, 'Hide') == 0 ? 'checked="checked"' : '');
    $total_time_label_hide = (strcmp($total_time_label_hide, 'Hide') == 0 ? 'checked="checked"' : '');

    $serving_size_label_hide = (strcmp($serving_size_label_hide, 'Hide') == 0 ? 'checked="checked"' : '');

    $rating_label_hide = (strcmp($rating_label_hide, 'Hide') == 0 ? 'checked="checked"' : '');
    $notes_label_hide = (strcmp($notes_label_hide, 'Hide') == 0 ? 'checked="checked"' : '');
    
    $other_options = '';
    $other_options_array = array('Rating', 'Prep Time', 'Cook Time', 'Total Time', 'Serving Size', 'Notes');
    
    foreach ($other_options_array as $option) {
        $name = strtolower(str_replace(' ', '-', $option));
        $value = strtolower(str_replace(' ', '_', $option)) . '_label';
        $value_hide = strtolower(str_replace(' ', '_', $option)) . '_label_hide';
        $other_options .= '<tr valign="top">
            <th scope="row">\'' . $option . '\' Label</th>
            <td><input type="text" name="' . $name . '-label" value="' . ${$value} . '" class="regular-text" /><br />
            <label><input type="checkbox" name="' . $name . '-label-hide" value="Hide" ' . ${$value_hide} . ' /> Don\'t show ' . $option . ' label</label></td>
        </tr>';
    }

    echo '<style>
        .form-table label { line-height: 2.5; }
        hr { border: 1px solid #DDD; border-left: none; border-right: none; border-bottom: none; margin: 30px 0; }
    </style>
    <div class="wrap">
        <form enctype="multipart/form-data" method="post" action="" name="recipe_settings_form">
            <h2><img src="' . $recipe_icon . '" /> Edamam Recipe Plugin Settings</h2>
            
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">Partner Key</th>
                    <td>
                        <input type="text" name="edamam-partner-key" value="' . $edamam_partner_key . '" class="regular-text" />
                    </td>
                </tr>
            </table>
            
            <hr />
			<h3>General</h3>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">Nutritional information</th>
                    <td><label><input type="checkbox" name="recipe-nutritional-info" value="yes" ' . $recipe_nutritional_info . ' /> Use Edamam nutritional info</label></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Edamam logo</th>
                    <td><label><input type="checkbox" name="recipe-logo" value="yes" ' . $recipe_logo . ' /> Show Edamam logo</label></td>
                </tr>                
                <tr valign="top">
                    <th scope="row">Recipe Title</th>
                    <td><label><input type="checkbox" name="recipe-title-hide" value="Hide" ' . $recipe_title_hide . ' /> Don\'t show Recipe Title in post (still shows in print view)</label></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Recipe Background</th>
                    <td><label><input type="text" name="recipe-background" value="' . $recipe_background . '" class="regular-text" /></label></td>
                </tr>                
                <tr valign="top">
                    <th scope="row">Print Button</th>
                    <td><label><input type="checkbox" name="print-link-hide" value="Hide" ' . $print_link_hide . ' /> Don\'t show Print Button</label></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Image Width</th>
                    <td><label><input type="text" name="image-width" value="' . $image_width . '" class="regular-text" /> pixels</label></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Image Display</th>
                    <td>
                    	<label><input type="checkbox" name="image-hide" value="Hide" ' . $image_hide . ' /> Don\'t show Image in post</label>
                    	<br />
                    	<label><input type="checkbox" name="image-hide-print" value="Hide" ' . $image_hide_print . ' /> Don\'t show Image in print view</label>
                    </td>
                </tr>
                <tr valign="top">
                	<th scope="row">Border Style</th>
                	<td>
						<select name="outer-border-style">' . $obs . '</select>
					</td>
				</tr>
            </table>
            <hr />            
            <h3>Ingredients</h3>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">\'Ingredients\' Label</th>
                    <td><input type="text" name="ingredient-label" value="' . $ingredient_label . '" class="regular-text" /><br />
                    <label><input type="checkbox" name="ingredient-label-hide" value="Hide" ' . $ingredient_label_hide . ' /> Don\'t show Ingredients label</label></td>
                </tr>
                <tr valign="top">
                    <th scope="row">\'Ingredients\' List Type</th>
                    <td><input type="radio" name="ingredient-list-type" value="ul" ' . $ing_ul . ' /> <label>Bulleted List</label><br />
                    <input type="radio" name="ingredient-list-type" value="ol" ' . $ing_ol . ' /> <label>Numbered List</label><br />
                    <input type="radio" name="ingredient-list-type" value="p" ' . $ing_p . ' /> <label>Paragraphs</label><br />
                    <input type="radio" name="ingredient-list-type" value="div" ' . $ing_div . ' /> <label>Divs</label></td>
                </tr>
            </table>
            
            <hr />
            
            <h3>Instructions</h3>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">\'Instructions\' Label</th>
                    <td><input type="text" name="instruction-label" value="' . $instruction_label . '" class="regular-text" /><br />
                    <label><input type="checkbox" name="instruction-label-hide" value="Hide" ' . $instruction_label_hide . ' /> Don\'t show Instructions label</label></td>
                </tr>
                <tr valign="top">
                    <th scope="row">\'Instructions\' List Type</th>
                    <td><input type="radio" name="instruction-list-type" value="ol" ' . $ins_ol . ' /> <label>Numbered List</label><br />
                    <input type="radio" name="instruction-list-type" value="ul" ' . $ins_ul . ' /> <label>Bulleted List</label><br />
                    <input type="radio" name="instruction-list-type" value="p" ' . $ins_p . ' /> <label>Paragraphs</label><br />
                    <input type="radio" name="instruction-list-type" value="div" ' . $ins_div . ' /> <label>Divs</label></td>
                </tr>
            </table>
            
            <hr />
            
            <h3>Other Options</h3>
            <table class="form-table">
                ' . $other_options . '
            </table>
            
            <p><input type="submit" name="submit" id="submit" class="button-primary" value="Save Changes"></p>
        </form>
    </div>';
}




function edamam_recipe_tinymce_plugin($plugin_array) {
	$plugin_array['edamamEDrecipe'] = plugins_url( '/editor.js?sver=' . EDAMAM_RECIPE_VERSION_NUM, __FILE__ );
	return $plugin_array;
}

function edamam_recipe_register_tinymce_button($buttons) {
   array_push($buttons, "edamamEDrecipe");
   return $buttons;
}

function edamam_recipe_add_recipe_button() {
    global $typenow;
    // check user permissions
    if ( !current_user_can('edit_posts') && !current_user_can('edit_pages') ) {
   	return;
    }
    // verify the post type
    if( ! in_array( $typenow, array( 'post', 'page' ) ) )
        return;
	// check if WYSIWYG is enabled
	if ( get_user_option('rich_editing') == 'true') {
		add_filter('mce_external_plugins', 'edamam_recipe_tinymce_plugin');
		add_filter('mce_buttons', 'edamam_recipe_register_tinymce_button');
	}
}





function edamam_recipe_strip_chars( $val )
{
	return str_replace( '\\', '', $val );
}

// Content for the popup iframe when creating or editing a recipe
function edamam_recipe_iframe_content($post_info = null, $get_info = null) {
    $recipe_id = 0;
    if ($post_info || $get_info) {
    
    	if( $get_info["add-recipe-button"] || strpos($get_info["post_id"], '-') !== false ) {
        	$iframe_title = "Update Your Recipe"; 
        	$submit = "Update Recipe";
        } else {
    		$iframe_title = "Add a Recipe";
    		$submit = "Add Recipe";
        }
    
        if ($get_info["post_id"] && !$get_info["add-recipe-button"] && strpos($get_info["post_id"], '-') !== false) {
            $recipe_id = preg_replace('/[0-9]*?\-/i', '', $get_info["post_id"]);
            $recipe = edamam_recipe_select_recipe_db($recipe_id);           
            $recipe_title = $recipe->recipe_title; //!!xxx edamam_recipe_strip_chars( $recipe->recipe_title );
            $recipe_image = $recipe->recipe_image;
            $summary = $recipe->summary; //!!xxx edamam_recipe_strip_chars( $recipe->summary );
            $notes = $recipe->notes;
            $rating = $recipe->rating;
            $ss = array();
            $ss[(int)$rating] = 'selected="true"';
            
            $prep_time_input = '';
            $cook_time_input = '';
            $total_time_input = '';
            if (class_exists('DateInterval')) {
                try {
                    $prep_time = new DateInterval($recipe->prep_time);
                    $prep_time_seconds = $prep_time->s;
                    $prep_time_minutes = $prep_time->i;
                    $prep_time_hours = $prep_time->h;
                    $prep_time_days = $prep_time->d;
                    $prep_time_months = $prep_time->m;
                    $prep_time_years = $prep_time->y;
                } catch (Exception $e) {
                    if ($recipe->prep_time != null) {
                        $prep_time_input = '<input type="text" name="prep_time" value="' . $recipe->prep_time . '"/>';
                    }
                }

                try {
                    $cook_time = new DateInterval($recipe->cook_time);
                    $cook_time_seconds = $cook_time->s;
                    $cook_time_minutes = $cook_time->i;
                    $cook_time_hours = $cook_time->h;
                    $cook_time_days = $cook_time->d;
                    $cook_time_months = $cook_time->m;
                    $cook_time_years = $cook_time->y;
                } catch (Exception $e) {
                    if ($recipe->cook_time != null) {
                        $cook_time_input = '<input type="text" name="cook_time" value="' . $recipe->cook_time . '"/>';
                    }
                }
            
                try {
                    $total_time = new DateInterval($recipe->total_time);
                    $total_time_seconds = $total_time->s;
                    $total_time_minutes = $total_time->i;
                    $total_time_hours = $total_time->h;
                    $total_time_days = $total_time->d;
                    $total_time_months = $total_time->m;
                    $total_time_years = $total_time->y;
                } catch (Exception $e) {
                    if ($recipe->total_time != null) {
                        $total_time_input = '<input type="text" name="total_time" value="' . $recipe->total_time . '"/>';
                    }
                }
            } else {
                if (preg_match('(^[A-Z0-9]*$)', $recipe->prep_time) == 1) {
                    preg_match('(\d*S)', $recipe->prep_time, $pts);
                    $prep_time_seconds = str_replace('S', '', $pts[0]);
                    preg_match('(\d*M)', $recipe->prep_time, $ptm, PREG_OFFSET_CAPTURE, strpos($recipe->prep_time, 'T'));
                    $prep_time_minutes = str_replace('M', '', $ptm[0][0]);
                    preg_match('(\d*H)', $recipe->prep_time, $pth);
                    $prep_time_hours = str_replace('H', '', $pth[0]);
                    preg_match('(\d*D)', $recipe->prep_time, $ptd);
                    $prep_time_days = str_replace('D', '', $ptd[0]);
                    preg_match('(\d*M)', $recipe->prep_time, $ptmm);
                    $prep_time_months = str_replace('M', '', $ptmm[0]);
                    preg_match('(\d*Y)', $recipe->prep_time, $pty);
                    $prep_time_years = str_replace('Y', '', $pty[0]);
                } else {
                    if ($recipe->prep_time != null) {
                        $prep_time_input = '<input type="text" name="prep_time" value="' . $recipe->prep_time . '"/>';
                    }
                }
                
                if (preg_match('(^[A-Z0-9]*$)', $recipe->cook_time) == 1) {
                    preg_match('(\d*S)', $recipe->cook_time, $cts);
                    $cook_time_seconds = str_replace('S', '', $cts[0]);
                    preg_match('(\d*M)', $recipe->cook_time, $ctm, PREG_OFFSET_CAPTURE, strpos($recipe->cook_time, 'T'));
                    $cook_time_minutes = str_replace('M', '', $ctm[0][0]);
                    preg_match('(\d*H)', $recipe->cook_time, $cth);
                    $cook_time_hours = str_replace('H', '', $cth[0]);
                    preg_match('(\d*D)', $recipe->cook_time, $ctd);
                    $cook_time_days = str_replace('D', '', $ctd[0]);
                    preg_match('(\d*M)', $recipe->cook_time, $ctmm);
                    $cook_time_months = str_replace('M', '', $ctmm[0]);
                    preg_match('(\d*Y)', $recipe->cook_time, $cty);
                    $cook_time_years = str_replace('Y', '', $cty[0]);
                } else {
                    if ($recipe->cook_time != null) {
                        $cook_time_input = '<input type="text" name="cook_time" value="' . $recipe->cook_time . '"/>';
                    }
                }
                
                if (preg_match('(^[A-Z0-9]*$)', $recipe->total_time) == 1) {
                    preg_match('(\d*S)', $recipe->total_time, $tts);
                    $total_time_seconds = str_replace('S', '', $tts[0]);
                    preg_match('(\d*M)', $recipe->total_time, $ttm, PREG_OFFSET_CAPTURE, strpos($recipe->total_time, 'T'));
                    $total_time_minutes = str_replace('M', '', $ttm[0][0]);
                    preg_match('(\d*H)', $recipe->total_time, $tth);
                    $total_time_hours = str_replace('H', '', $tth[0]);
                    preg_match('(\d*D)', $recipe->total_time, $ttd);
                    $total_time_days = str_replace('D', '', $ttd[0]);
                    preg_match('(\d*M)', $recipe->total_time, $ttmm);
                    $total_time_months = str_replace('M', '', $ttmm[0]);
                    preg_match('(\d*Y)', $recipe->total_time, $tty);
                    $total_time_years = str_replace('Y', '', $tty[0]);
                } else {
                    if ($recipe->total_time != null) {
                        $total_time_input = '<input type="text" name="total_time" value="' . $recipe->total_time . '"/>';
                    }
                }
            }
            
            $serving_size = $recipe->serving_size;

            $ingredients = $recipe->ingredients; //!!xxx edamam_recipe_strip_chars( $recipe->ingredients ); //!!mwp
            $instructions = $recipe->instructions; //!!xxx edamam_recipe_strip_chars( $recipe->instructions );

        } else {
            $recipe_id = htmlentities($post_info["recipe_id"], ENT_QUOTES);
            if( !$get_info["add-recipe-button"] ) //!!mwp
                 $recipe_title = get_the_title( $get_info["post_id"] ); //!!mwp
            else
                 $recipe_title = edamam_recipe_strip_chars( htmlentities($post_info["recipe_title"], ENT_QUOTES) );
            $recipe_image = htmlentities($post_info["recipe_image"], ENT_QUOTES); //!!mwp
            $summary = edamam_recipe_strip_chars( htmlentities($post_info["summary"], ENT_QUOTES) );
            $notes = edamam_recipe_strip_chars( htmlentities($post_info["notes"], ENT_QUOTES) );
            $rating = htmlentities($post_info["rating"], ENT_QUOTES);
            $prep_time_seconds = htmlentities($post_info["prep_time_seconds"], ENT_QUOTES);
            $prep_time_minutes = htmlentities($post_info["prep_time_minutes"], ENT_QUOTES);
            $prep_time_hours = htmlentities($post_info["prep_time_hours"], ENT_QUOTES);
            $prep_time_days = htmlentities($post_info["prep_time_days"], ENT_QUOTES);
            $prep_time_weeks = htmlentities($post_info["prep_time_weeks"], ENT_QUOTES);
            $prep_time_months = htmlentities($post_info["prep_time_months"], ENT_QUOTES);
            $prep_time_years = htmlentities($post_info["prep_time_years"], ENT_QUOTES);
            $cook_time_seconds = htmlentities($post_info["cook_time_seconds"], ENT_QUOTES);
            $cook_time_minutes = htmlentities($post_info["cook_time_minutes"], ENT_QUOTES);
            $cook_time_hours = htmlentities($post_info["cook_time_hours"], ENT_QUOTES);
            $cook_time_days = htmlentities($post_info["cook_time_days"], ENT_QUOTES);
            $cook_time_weeks = htmlentities($post_info["cook_time_weeks"], ENT_QUOTES);
            $cook_time_months = htmlentities($post_info["cook_time_months"], ENT_QUOTES);
            $cook_time_years = htmlentities($post_info["cook_time_years"], ENT_QUOTES);
            $total_time_seconds = htmlentities($post_info["total_time_seconds"], ENT_QUOTES);
            $total_time_minutes = htmlentities($post_info["total_time_minutes"], ENT_QUOTES);
            $total_time_hours = htmlentities($post_info["total_time_hours"], ENT_QUOTES);
            $total_time_days = htmlentities($post_info["total_time_days"], ENT_QUOTES);
            $total_time_weeks = htmlentities($post_info["total_time_weeks"], ENT_QUOTES);
            $total_time_months = htmlentities($post_info["total_time_months"], ENT_QUOTES);
            $total_time_years = htmlentities($post_info["total_time_years"], ENT_QUOTES);

            $serving_size = htmlentities($post_info["serving_size"], ENT_QUOTES);

            $ingredients = edamam_recipe_strip_chars( htmlentities($post_info["ingredients"], ENT_QUOTES) ); //!!mwp
            $instructions = edamam_recipe_strip_chars( htmlentities($post_info["instructions"], ENT_QUOTES) );
            
            //!!mwp if ($recipe_title != null && $recipe_title != '' && $ingredients[0]['name'] != null && $ingredients[0]['name'] != '') {
            if ($recipe_title != null && $recipe_title != '' && $ingredients != null && $ingredients != '') { //!!mwp
                $recipe_id = edamam_recipe_insert_db($post_info);
            }
        }
    }
    
    $id = (int) $_REQUEST["post_id"];
    $url = get_option('siteurl');
    $dir_name = dirname(plugin_basename(__FILE__));
    $submitform = '';
    if ($post_info != null) {
        $submitform .= "<script>window.onload = edamamEDRecipeSubmitForm;</script>";
    }
 
    echo <<< HTML

<!DOCTYPE html>
<head>
    <link rel="stylesheet" href="$url/wp-content/plugins/$dir_name/edamam.css" type="text/css" media="all" />
    <script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.5.1/jquery.min.js"></script>
    <script type="text/javascript">//<!CDATA[
        
        function isNumberKey(evt){
            var charCode = (evt.which) ? evt.which : event.keyCode
            if (charCode > 31 && (charCode < 48 || charCode > 57))
                return false;
            return true;
        }                                            
        
        function edamamEDRecipeSubmitForm() {
            var title = document.forms['recipe_form']['recipe_title'].value;

            if (title==null || title=='') {
                $('#recipe-title input').addClass('input-error');
                $('#recipe-title').append('<p class="error-message">You must enter a title for your recipe.</p>');
                
                return false;
            }
            var ingredients = $('#edamam_recipe_ingredients textarea').val(); //!!mwp

            if (ingredients==null || ingredients=='' || ingredients==undefined) { //!!mwp

                $('#edamam_recipe_ingredients textarea').addClass('input-error'); //!!mwp
                $('#edamam_recipe_ingredients').append('<p class="error-message">You must enter at least one ingredient.</p>'); //!!mwp
                
                return false;
            }
            window.parent.edamamEDRecipeInsertIntoPostEditor('$recipe_id','$url','$dir_name');
            top.tinymce.activeEditor.windowManager.close(window); 
        }
        
        $(document).ready(function() {
            $('#more-options').hide();
            $('#more-options-toggle').click(function() {
                $('#more-options').toggle(400);
                
                return false;
            });

        });
        
    //]]>
    </script>
    $submitform
</head>
<body id="edamam-recipe-uploader">
    <form enctype='multipart/form-data' method='post' action='' name='recipe_form'>
        <h3 class='edamam-recipe-title'>$iframe_title</h3>
        <div id='edamam-recipe-form-items'>
            <input type='hidden' name='post_id' value='$id' />
            <input type='hidden' name='recipe_id' value='$recipe_id' />
            <p id='recipe-title'><label>Recipe Title <span class='required'>*</span></label> <input type='text' name='recipe_title' value='$recipe_title' /></p>
            <p id='recipe-image'><label>Recipe Image</label> <input type='text' name='recipe_image' value='$recipe_image' /></p>
            <p><label>Serving Size</label> <input type='number' onkeypress='return isNumberKey(event)' name='serving_size' value='$serving_size' /></p>
            <p class="cls"><label>Total Time</label>
              $total_time_input
              <span class="time">
                 <span><input type='number' min="0" max="24" name='total_time_hours' onkeypress='return isNumberKey(event)' value='$total_time_hours' /><label>hours</label></span>
                 <span><input type='number' min="0" max="60" name='total_time_minutes' onkeypress='return isNumberKey(event)' value='$total_time_minutes' /><label>minutes</label></span>
              </span>
            </p>
            <p id='edamam_recipe_ingredients' class='cls'><label>Ingredients <span class='required'>*</span> <small>Put each ingredient on a separate line.  There is no need to use bullets for your ingredients. To add sub-headings put them on a new line between [...]. Example will be - [for the dressing:]</small></label><textarea name='ingredients'>$ingredients</textarea></label></p>
            <p id='edamam-recipe-instructions' class='cls'><label>Instructions <small>Press return after each instruction. There is no need to number your instructions.</small></label><textarea name='instructions'>$instructions</textarea></label></p>
            <p><a href='#' id='more-options-toggle'>More options</a></p>
            <div id='more-options'>
                <p class='cls'><label>Summary</label> <textarea name='summary'>$summary</textarea></label></p>
                <p class='cls'><label>Rating</label>
                	<span class='rating'>
						<select name="rating">
							  <option value="0">None</option>
							  <option value="1" $ss[1]>1 Star</option>
							  <option value="2" $ss[2]>2 Stars</option>
							  <option value="3" $ss[3]>3 Stars</option>
							  <option value="4" $ss[4]>4 Stars</option>
							  <option value="5" $ss[5]>5 Stars</option>
						</select>
					</span>
				</p>
                <p class="cls"><label>Prep Time</label> 
                    $prep_time_input
                    <span class="time">
                        <span><input type='number' min="0" max="24" onkeypress='return isNumberKey(event)' name='prep_time_hours' value='$prep_time_hours' /><label>hours</label></span>
                        <span><input type='number' min="0" max="60" onkeypress='return isNumberKey(event)' name='prep_time_minutes' value='$prep_time_minutes' /><label>minutes</label></span>
                    </span>
                </p>
                <p class="cls"><label>Cook Time</label>
                    $cook_time_input
                    <span class="time">
                    	<span><input type='number' min="0" max="24" onkeypress='return isNumberKey(event)' name='cook_time_hours' value='$cook_time_hours' /><label>hours</label></span>
                        <span><input type='number' min="0" max="60" onkeypress='return isNumberKey(event)' name='cook_time_minutes' value='$cook_time_minutes' /><label>minutes</label></span>
                    </span>
                </p>
                <p class='cls'><label>Notes</label> <textarea name='notes'>$notes</textarea></label></p>
            </div>
            <input type='submit' value='$submit' name='add-recipe-button' />
        </div>
    </form>
</body>
HTML;
}

// Inserts the recipe into the database
function edamam_recipe_insert_db($post_info) {
    global $wpdb;
    
    $recipe_id = $post_info["recipe_id"];
    
    if ($post_info["prep_time_years"] || $post_info["prep_time_months"] || $post_info["prep_time_days"] || $post_info["prep_time_hours"] || $post_info["prep_time_minutes"] || $post_info["prep_time_seconds"]) {
        $prep_time = 'P';
        if ($post_info["prep_time_years"]) {
            $prep_time .= $post_info["prep_time_years"] . 'Y';
        }
        if ($post_info["prep_time_months"]) {
            $prep_time .= $post_info["prep_time_months"] . 'M';
        }
        if ($post_info["prep_time_days"]) {
            $prep_time .= $post_info["prep_time_days"] . 'D';
        }
        if ($post_info["prep_time_hours"] || $post_info["prep_time_minutes"] || $post_info["prep_time_seconds"]) {
            $prep_time .= 'T';
        }
        if ($post_info["prep_time_hours"]) {
            $prep_time .= $post_info["prep_time_hours"] . 'H';
        }
        if ($post_info["prep_time_minutes"]) {
            $prep_time .= $post_info["prep_time_minutes"] . 'M';
        }
        if ($post_info["prep_time_seconds"]) {
            $prep_time .= $post_info["prep_time_seconds"] . 'S';
        }
    } else {
        $prep_time = $post_info["prep_time"];
    }
    
    if ($post_info["cook_time_years"] || $post_info["cook_time_months"] || $post_info["cook_time_days"] || $post_info["cook_time_hours"] || $post_info["cook_time_minutes"] || $post_info["cook_time_seconds"]) {
        $cook_time = 'P';
        if ($post_info["cook_time_years"]) {
            $cook_time .= $post_info["cook_time_years"] . 'Y';
        }
        if ($post_info["cook_time_months"]) {
            $cook_time .= $post_info["cook_time_months"] . 'M';
        }
        if ($post_info["cook_time_days"]) {
            $cook_time .= $post_info["cook_time_days"] . 'D';
        }
        if ($post_info["cook_time_hours"] || $post_info["cook_time_minutes"] || $post_info["cook_time_seconds"]) {
            $cook_time .= 'T';
        }
        if ($post_info["cook_time_hours"]) {
            $cook_time .= $post_info["cook_time_hours"] . 'H';
        }
        if ($post_info["cook_time_minutes"]) {
            $cook_time .= $post_info["cook_time_minutes"] . 'M';
        }
        if ($post_info["cook_time_seconds"]) {
            $cook_time .= $post_info["cook_time_seconds"] . 'S';
        }
    } else {
        $cook_time = $post_info["cook_time"];
    }
    
    if ($post_info["total_time_years"] || $post_info["total_time_months"] || $post_info["total_time_days"] || $post_info["total_time_hours"] || $post_info["total_time_minutes"] || $post_info["total_time_seconds"]) {
        $total_time = 'P';
        if ($post_info["total_time_years"]) {
            $total_time .= $post_info["total_time_years"] . 'Y';
        }
        if ($post_info["total_time_months"]) {
            $total_time .= $post_info["total_time_months"] . 'M';
        }
        if ($post_info["total_time_days"]) {
            $total_time .= $post_info["total_time_days"] . 'D';
        }
        if ($post_info["total_time_hours"] || $post_info["total_time_minutes"] || $post_info["total_time_seconds"]) {
            $total_time .= 'T';
        }
        if ($post_info["total_time_hours"]) {
            $total_time .= $post_info["total_time_hours"] . 'H';
        }
        if ($post_info["total_time_minutes"]) {
            $total_time .= $post_info["total_time_minutes"] . 'M';
        }
        if ($post_info["total_time_seconds"]) {
            $total_time .= $post_info["total_time_seconds"] . 'S';
        }
    } else {
        $total_time = $post_info["total_time"];
    }
        
    $recipe = array (
        "recipe_title" => edamam_recipe_strip_chars( $post_info["recipe_title"] ),
        "recipe_image" => $post_info["recipe_image"],
        "summary" => edamam_recipe_strip_chars( $post_info["summary"] ),
        "rating" => $post_info["rating"],
        "prep_time" => $prep_time,
        "cook_time" => $cook_time,
        "total_time" => $total_time,
        "serving_size" => $post_info["serving_size"],
        "ingredients" => edamam_recipe_strip_chars( $post_info["ingredients"] ),
        "instructions" => edamam_recipe_strip_chars( $post_info["instructions"] ),
        "notes" => edamam_recipe_strip_chars( $post_info["notes"] ),
    );
    
    if (edamam_recipe_select_recipe_db($recipe_id) == null) {
    	$recipe["post_id"] = $post_info["post_id"];	// set only during record creation
        $wpdb->insert( $wpdb->prefix . "edamam_recipe_recipes", $recipe );
        $recipe_id = $wpdb->insert_id;
    } else {
        $wpdb->update( $wpdb->prefix . "edamam_recipe_recipes", $recipe, array( 'recipe_id' => $recipe_id ));       
    }

    return $recipe_id;
}

// Inserts the recipe into the post editor
function edamam_recipe_plugin_footer() {
    $url = get_option('siteurl');
    $dir_name = dirname(plugin_basename(__FILE__));
    
    echo <<< HTML
    <style type="text/css" media="screen">
        #wp_edit_recipebtns { position:absolute;display:block;z-index:999998; }
        #wp_edit_recipebtn { margin-right:20px; }
        #wp_edit_recipebtn,#wp_del_recipebtn { cursor:pointer; padding:12px;background:#010101; -moz-border-radius:8px;-khtml-border-radius:8px;-webkit-border-radius:8px;border-radius:8px; filter:alpha(opacity=80); -moz-opacity:0.8; -khtml-opacity: 0.8; opacity: 0.8; }
        #wp_edit_recipebtn:hover,#wp_del_recipebtn:hover { background:#000; filter:alpha(opacity=100); -moz-opacity:1; -khtml-opacity: 1; opacity: 1; }
    </style>
    <script>//<![CDATA[ 
    var baseurl = '$url';
    var dir_name = '$dir_name';
        function edamamEDRecipeInsertIntoPostEditor(rid,getoption,dir_name) {
            tb_remove();
            
            var ed;
            
            var output = '<img id="edamam-recipe-recipe-';
            output += rid;
            output += '" class="edamam-recipe-recipe" src="' + getoption + '/wp-content/plugins/' + dir_name + '/image.png" alt="" />';
            
        	if ( typeof tinyMCE != 'undefined' && ( ed = tinyMCE.activeEditor ) && !ed.isHidden() && ed.id=='content') {  //path followed when in Visual editor mode
        		ed.focus();
        		if ( tinymce.isIE )
        			ed.selection.moveToBookmark(tinymce.EditorManager.activeEditor.windowManager.bookmark);

        		ed.execCommand('mceInsertContent', false, output);

        	} else if ( typeof edInsertContent == 'function' ) {  //!!mwp path followed when in HTML editor mode
                output = '[edamam-recipe-recipe:'; //!!mwp
                output += rid;
                output += ']';
                edInsertContent(edCanvas, output);
        	} else {
                output = '[edamam-recipe-recipe:'; //!!mwp
                output += rid;
                output += ']';
        		jQuery( edCanvas ).val( jQuery( edCanvas ).val() + output );
        	}
        }
    //]]></script>
HTML;
}

add_action('admin_footer', 'edamam_recipe_plugin_footer');

// Converts the image to a recipe for output
function edamam_recipe_convert_to_recipe($post_text) {
    $output = $post_text;
    $needle_old = 'id="edamam-recipe-recipe-';
    $preg_needle_old = '/(id)=("(edamam-recipe-recipe-)[0-9^"]*")/i';
    $needle = '[edamam-recipe-recipe:';
    $preg_needle = '/\[edamam-recipe-recipe:([0-9]+)\]/i';
    
    if (strpos($post_text, $needle_old) !== false) {
        // This is for backwards compatability. Please do not delete or alter.
        preg_match_all($preg_needle_old, $post_text, $matches);
        foreach ($matches[0] as $match) {
            $recipe_id = str_replace('id="edamam-recipe-recipe-', '', $match);
            $recipe_id = str_replace('"', '', $recipe_id);           
            $recipe = edamam_recipe_select_recipe_db($recipe_id);
            $formatted_recipe = edamam_recipe_format_recipe($recipe);
            $output = str_replace('<img id="edamam-recipe-recipe-' . $recipe_id . '" class="edamam-recipe-recipe" src="' . get_option('siteurl') . '/wp-content/plugins/' . dirname(plugin_basename(__FILE__)) . '/image.png?ver=1.0" alt="" />', $formatted_recipe, $output);
        }
    }
    
    if (strpos($post_text, $needle) !== false) {
        preg_match_all($preg_needle, $post_text, $matches);
        foreach ($matches[0] as $match) {
            $recipe_id = str_replace('[edamam-recipe-recipe:', '', $match);
            $recipe_id = str_replace(']', '', $recipe_id);
            $recipe = edamam_recipe_select_recipe_db($recipe_id);
            $formatted_recipe = edamam_recipe_format_recipe($recipe); //!!mwp
            $output = str_replace('[edamam-recipe-recipe:' . $recipe_id . ']', $formatted_recipe, $output);
        }
    }
    
    return $output;
}

add_filter('the_content', 'edamam_recipe_convert_to_recipe');

// Pulls a recipe from the db
function edamam_recipe_select_recipe_db($recipe_id) {
    global $wpdb;
    
    $recipe = $wpdb->get_row("SELECT * FROM " . $wpdb->prefix . "edamam_recipe_recipes WHERE recipe_id=" . $recipe_id);

    return $recipe;
}

//get current URL
function cur_Page_URL() {
   $pageURL = 'http';
   if ($_SERVER["HTTPS"] == "on") {$pageURL .= "s";}
   $pageURL .= "://";
   if ($_SERVER["SERVER_PORT"] != "80") {
    $pageURL .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"].$_SERVER["REQUEST_URI"];
   } else {
    $pageURL .= $_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];
   }
   return $pageURL;
}

// Format an ISO8601 duration for human readibility
function edamam_recipe_format_duration($duration) {
	$date_abbr = array('y' => 'year', 'm' => 'month', 'd' => 'day', 'h' => 'hour', 'i' => 'minute', 's' => 'second');
	$result = '';

	if (class_exists('DateInterval')) {
		try {
			$result_object = new DateInterval($duration);

			foreach ($date_abbr as $abbr => $name) {
				if ($result_object->$abbr > 0) {
					$result .= $result_object->$abbr . ' ' . $name;
					if ($result_object->$abbr > 1) {
						$result .= 's';
					}
					$result .= ', ';
				}
			}

			$result = trim($result, ' \t,');
		} catch (Exception $e) {
			$result = $duration;
		}
	} else { // else we have to do the work ourselves so the output is pretty
		$arr = explode('T', $duration);
		$arr[1] = str_replace('M', 'I', $arr[1]); // This mimics the DateInterval property name
		$duration = implode('T', $arr);

		foreach ($date_abbr as $abbr => $name) {
		if (preg_match('/(\d+)' . $abbr . '/i', $duration, $val)) {
				$result .= $val[1] . ' ' . $name;
				if ($val[1] > 1) {
					$result .= 's';
				}
				$result .= ', ';
			}
		}

		$result = trim($result, ' \t,');
	}
	return $result;
}

// function to include the javascript for the Add Recipe button
function edamam_recipe_process_head() {
  global $wpdb;
      
  $header_html = '';
  $preview = $_GET["preview"];
  $partner_key = get_option('edamam_partner_key');
  //$display = get_option('edamam_ingredient_display');
  $url = cur_Page_URL();
  $rtitle = get_the_title(); 
  $recipe_id = get_the_ID();
  $logo = get_option('recipe_logo');
  $background = get_option('recipe_background');
  
  $recipe = $wpdb->get_row("SELECT * FROM " . $wpdb->prefix . "edamam_recipe_recipes WHERE post_id=" . $recipe_id);
  $yield = $recipe->serving_size;

  $header_html = '
  <meta property="og:title" content="'.$rtitle.'"/>
  <meta property="og:type" content="food"/>
  <meta property="og:url" content="'.$url.'"/>
  <meta property="og:image" content="'.$recipe->recipe_image.'"/>
  <meta property="og:site_name" content="Edamam.com"/>  
  ';

  $header_html .= '<link type="text/css" rel="stylesheet" href="'.EDAMAM_RECIPE_PLUGIN_DIRECTORY.'style.css"/>';
  $header_html .= '<style type="text/css">#recipe-container{background: '.$background.';}</style>';
  $header_html .= '<script type="text/javascript" src="'.EDAMAM_RECIPE_PLUGIN_DIRECTORY.'print.js"></script>'; 
  if (is_single() == true){
 	  echo $header_html; 
  }

}
add_filter('wp_head', 'edamam_recipe_process_head');

add_filter( 'post_class', 'fc_remove_hentry', 20 );
function fc_remove_hentry( $classes ) {
  if( ( $key = array_search( 'hentry', $classes ) ) !== false )
  unset( $classes[$key] );
  return $classes;
}

function force_Recipe() {
  global $edamam_url_api; 
  global $edamam_url_source;
  global $wpdb;

  $url = get_permalink();
  $recipe_id = get_the_ID();
  $partner_key = get_option('edamam_partner_key');
  
  $recipe = $wpdb->get_row("SELECT * FROM " . $wpdb->prefix . "edamam_recipe_recipes WHERE post_id=" . $recipe_id);
  
  $ingredients = $recipe->ingredients;
  $ingredients = htmlspecialchars($ingredients);
  $ingredients = preg_split("/\r\n|\n|\r/", $ingredients);    
  $ingredients = '"'.implode('","', $ingredients).'"';
  
  $recipe_title = $recipe->recipe_title;
  $recipe_title = htmlspecialchars($recipe_title);
  $serving_size = $recipe->serving_size;
  $serving_size = htmlspecialchars($serving_size);
  
  $data_string = '{
      "title":"'.$recipe_title.'", 
      "yield":"'.$serving_size.'", 
      "img":"'.$recipe->recipe_image.'" ,
      "ingr":['.$ingredients.']
  }';  
  
  $ch = curl_init($edamam_url_api.'?widgetKey='.$partner_key.'&url='.urlencode($url));                                                                      
  curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");                                                                     
  curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);                                                                  
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);                                                                      
  curl_setopt($ch, CURLOPT_HTTPHEADER, array(                                                                          
      'Content-Type: application/json',                                                                                
      'Content-Length: ' . strlen($data_string))                                                                       
  );                                                                                                                    

  $result = curl_exec($ch);
  $info = curl_getinfo($ch);

  if($info['http_code'] == 401){
    
    edamam_register();
    
    $partner_key = get_option('edamam_partner_key');
    
    $ch = curl_init($edamam_url_api.'?widgetKey='.$partner_key.'&url='.urlencode($url));                                                                      
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");                                                                     
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);                                                                  
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);                                                                      
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(                                                                          
        'Content-Type: application/json',                                                                                
        'Content-Length: ' . strlen($data_string))                                                                       
    );                                                                                                                    
  
    $result = curl_exec($ch);      
  }
  
  $parsed_json = json_decode($result, true); 
   
  $error = $parsed_json["message"];

  if(($error != null)||($error == "")){ 
  
    if($error != "Bad Request"){
  
      $Servings = $parsed_json["yield"];     
      $Calories = round($parsed_json["calories"]);
      
      if($Calories != 0){

        $Calories = round($Calories/$Servings);
      
        $FatLabel = $parsed_json["totalNutrients"]["FAT"]["label"];  
        $FatQuantity = round($parsed_json["totalNutrients"]["FAT"]["quantity"]);
        $FatQuantity = round($FatQuantity/$Servings);
        $FatUnit = $parsed_json["totalNutrients"]["FAT"]["unit"];   
        
        $CarbsLabel = $parsed_json["totalNutrients"]["CHOCDF"]["label"];  
        $CarbsQuantity = round($parsed_json["totalNutrients"]["CHOCDF"]["quantity"]);
        $CarbsQuantity = round($CarbsQuantity/$Servings);
        $CarbsUnit = $parsed_json["totalNutrients"]["CHOCDF"]["unit"];  
        
        $ProteinLabel = $parsed_json["totalNutrients"]["PROCNT"]["label"];  
        $ProteinQuantity = round($parsed_json["totalNutrients"]["PROCNT"]["quantity"]);
        $ProteinQuantity = round($ProteinQuantity/$Servings);
        $ProteinUnit = $parsed_json["totalNutrients"]["PROCNT"]["unit"];    
      
        $RecipeData = array (
              "servings" => $Servings,
              "calories" => $Calories,
              "fatlabel" => $FatLabel,
              "fatquantity" => $FatQuantity,
              "fatunit" => $FatUnit,
              "carbslabel" => $CarbsLabel,
              "carbsquantity" => $CarbsQuantity,
              "carbsunit" => $CarbsUnit,
              "proteinlabel" => $ProteinLabel,
              "proteinquantity" => $ProteinQuantity,
              "proteinunit" => $ProteinUnit,
        );
       
        $wpdb->update( $wpdb->prefix . "edamam_recipe_recipes", $RecipeData, array( 'post_id' => $recipe_id ));
      
      }
      
    }
    
  }
  
}

add_action('publish_post', 'force_Recipe');

// Replaces the [a|b] pattern with text a that links to b
// Replaces _words_ with an italic span and *words* with a bold span
function edamam_recipe_richify_item($item, $class) {
	$output = preg_replace('/\[([^\]\|\[]*)\|([^\]\|\[]*)\]/', '<a href="\\2" class="' . $class . '-link" target="_blank">\\1</a>', $item);
	$output = preg_replace('/(^|\s)\*([^\s\*][^\*]*[^\s\*]|[^\s\*])\*(\W|$)/', '\\1<span class="bold">\\2</span>\\3', $output);
	return preg_replace('/(^|\s)_([^\s_][^_]*[^\s_]|[^\s_])_(\W|$)/', '\\1<span class="italic">\\2</span>\\3', $output);
}

function edamam_recipe_break( $otag, $text, $ctag) {
	$output = "";
	$split_string = explode( "\r\n\r\n", $text, 10 );
	foreach ( $split_string as $str )
	{
		$output .= $otag . $str . $ctag;
	}
	return $output;
}

// Processes markup for attributes like labels, images and links
// !Label
// %image
function edamam_recipe_format_item($item, $elem, $class, $itemprop, $id, $i) {

	if (preg_match("/^%(\S*)/", $item, $matches)) {	// IMAGE
		$output = '<img class = "' . $class . '-image" src="' . $matches[1] . '" />';
		return $output; // Images don't also have labels or links so return the line immediately.
	}

	if (preg_match("/^!(.*)/", $item, $matches)) {	// LABEL
		$class .= '-label';
		$elem = 'div';
		$item = $matches[1];
		$output = '<' . $elem . ' id="' . $id . $i . '" class="' . $class . '" >';	// No itemprop for labels
	} else {
		$output = '<' . $elem . ' id="' . $id . $i . '" class="' . $class . '" itemprop="' . $itemprop . '">';
	}

	$output .= edamam_recipe_richify_item($item, $class);
	$output .= '</' . $elem . '>';

	return $output;
}

// Formats the recipe for output
function edamam_recipe_format_recipe($recipe) { //!!mwp
  global $wpdb;
    $output = "";
    $permalink = get_permalink();

	// Output main recipe div with border style
	$style_tag = '';
	$border_style = get_option('recipe_outer_border_style');
	if ($border_style != null)
		$style_tag = 'style="border: ' . $border_style . ';"';
    $output .= '
    <div id="recipe-container-' . $recipe->recipe_id . '" class="recipe-container-border" ' . $style_tag . '>
    <div id="recipe-container" itemscope itemtype="http://schema.org/Recipe">
     
      <div id="recipe-inner">
      
        <div class="title-print">';

          // Add the print button
          if (strcmp(get_option('recipe_print_link_hide'), 'Hide') != 0) {
      		  $output .= '<div id="recipe-print"><a class="print-link hide-print" title="Print this recipe" href="javascript:void(0);" onclick="zlrPrint(\'recipe-container-' . $recipe->recipe_id . '\'); return false">Print</a></div>';
      	  }
      
        	// Add the title
        	$hide_tag = '';
        	if (strcmp(get_option('recipe_title_hide'), 'Hide') == 0){
            $hide_tag = 'display:none;';
          }
      	  $output .= '<div id="recipe-title" style="' . $hide_tag . '" itemprop="name"><h2>' . $recipe->recipe_title . '</h2></div>
      
        </div>';
	
	
	//!!dc open the zlmeta and fl-l container divs
	$output .= '
        <div class="recipe-clear">
          <div id="recipe-info">';
          
            if ($recipe->rating != 0) {
                $output .= '<p id="recipe-rating" itemprop="aggregateRating" itemscope itemtype="http://schema.org/AggregateRating">';
                if (strcmp(get_option('recipe_rating_label_hide'), 'Hide') != 0) {
                	$output .= get_option('recipe_rating_label') . ' ';
                }
                $output .= '<span class="rating rating-' . $recipe->rating . '"><span itemprop="ratingValue">' . $recipe->rating . '</span><span itemprop="reviewCount" style="display: none;">1</span></span>
               </p>';
            }
      
            // prep time
            if ($recipe->prep_time != null) {
            	$prep_time = edamam_recipe_format_duration($recipe->prep_time);
                
                $output .= '<p id="recipe-prep-time">';
                if (strcmp(get_option('recipe_prep_time_label_hide'), 'Hide') != 0) {
                    $output .= get_option('recipe_prep_time_label') . ' ';
                }
                $output .= '<span itemprop="prepTime" content="' . $recipe->prep_time . '">' . $prep_time . '</span></p>';
            }
            // cook time 
            if ($recipe->cook_time != null) {
                $cook_time = edamam_recipe_format_duration($recipe->cook_time);
                
                $output .= '<p id="recipe-cook-time">';
                if (strcmp(get_option('recipe_cook_time_label_hide'), 'Hide') != 0) {
                    $output .= get_option('recipe_cook_time_label') . ' ';
                }
                $output .= '<span itemprop="cookTime" content="' . $recipe->cook_time . '">' . $cook_time . '</span></p>';
            }
            // total time
            if ($recipe->total_time != null) {
                $total_time = edamam_recipe_format_duration($recipe->total_time);
                
                $output .= '<p id="recipe-total-time">';
                if (strcmp(get_option('recipe_total_time_label_hide'), 'Hide') != 0) {
                    $output .= get_option('recipe_total_time_label') . ' ';
                }
                $output .= '<span itemprop="totalTime" content="' . $recipe->total_time . '">' . $total_time . '</span></p>';
            }
          
            if ($recipe->serving_size != null) {
                $output .= '<div id="recipe-nutrition">';
            // serving 
                if ($recipe->serving_size != null) {
                    $output .= '<p id="recipe-serving-size">';
                    if (strcmp(get_option('recipe_serving_size_label_hide'), 'Hide') != 0) {
                        $output .= get_option('recipe_serving_size_label') . ' ';
                    }
                    $output .= '<span>' . $recipe->serving_size . '</span></p>';
                }

                $output .= '</div>';
            }
  
            //!! close the second container
          $output .= '
          </div>';
        
        $url = get_permalink();
        $recipe_id = get_the_ID();
        $edamam_partner_key = get_option('edamam_partner_key');
        
        $record = $wpdb->get_row("SELECT * FROM " . $wpdb->prefix . "edamam_recipe_recipes WHERE post_id=" . $recipe_id);

        if(($record->servings == "")||($record->calories == null)||($record->calories == 0)){
          force_Recipe();
        }
         
        $recipe = $wpdb->get_row("SELECT * FROM " . $wpdb->prefix . "edamam_recipe_recipes WHERE post_id=" . $recipe_id);

        $calories = $recipe->calories;
      
        $fatlabel = $recipe->fatlabel;  
        $fatquantity = $recipe->fatquantity;
        $fatunit = $recipe->fatunit;   
        
        $carbslabel = $recipe->carbslabel;  
        $carbsquantity = $recipe->carbsquantity;
        $carbsunit = $recipe->carbsunit;  
        
        $proteinlabel = $recipe->proteinlabel;  
        $proteinquantity = $recipe->proteinquantity;
        $proteinunit = $recipe->proteinunit;  
        
        $logo = get_option('recipe_logo');
          
        if($logo == "yes"){
          $logostr = '<span style="float:right;"><a target="_blank" href="https://www.edamam.com/website/wizard.jsp"><img src="' . EDAMAM_RECIPE_PLUGIN_DIRECTORY . 'edamam-logo-plugin.png" class="edamam-img"></a></span>';
        } 
        
        $urlAddr = 'http://www.edamam.com/widget/nutrition.jsp?widgetKey='.$edamam_partner_key.'&url='.urlencode($url).'&rtitle='.urlencode($recipe->recipe_title).'&y='.urlencode($recipe->serving_size);
         
        $edamamAPI = get_option('recipe_nutritional_info');
        if (strcmp($edamamAPI, '') != 0) {
        
          if($record->servings == null){

            $output .= '
            <div id="edamam-not-nutritional">
              See Detailed Nutrition Info on<br/><a target="_blank" href="'.$urlAddr.'"><img src="' . EDAMAM_RECIPE_PLUGIN_DIRECTORY . 'logo-plugin-big.png" class="edamam-img"></a>
            </div>';

          } else {

            $output .= '  
            <div id="edamam-nutritional-block">
            
              <div id="edamam-nutritional" itemprop="nutrition" itemscope itemtype="http://schema.org/NutritionInformation">
                <p id="edamam-line">
                  <span class="name">Per Serving</span>
                  <span class="gr big"><span itemprop="calories">'.$calories.'</span> calories</span>
                </p>
                <p id="edamam-line">
                  <span class="name">'.$fatlabel.'</span>
                  <span class="gr"><span itemprop="fatContent">'.$fatquantity.'</span> '.$fatunit.'</span>
                </p>
                <p id="edamam-line">
                  <span class="name">'.$carbslabel.'</span>
                  <span class="gr"><span itemprop="carbohydrateContent">'.$carbsquantity.'</span> '.$carbsunit.'</span>
                </p>
                <p id="edamam-line">
                  <span class="name">'.$proteinlabel.'</span>
                  <span class="gr"><span itemprop="proteinContent">'.$proteinquantity.'</span> '.$proteinunit.'</span>
                </p>
                <span itemprop="servingSize" style="display:none;">' . $recipe->servings . '</span>
              </div>
              <div id="edamam-nutritional">
                <p id="edamam-line">
                  <span style="float:left;"><a class="edamam-full" target="_blank" href="'.$urlAddr.'">See full nutrition!</a></span>'.$logostr.'<br/>
                </p>
              </div>
              
            </div>'; 
          
          }        
       		
        } else {
          $output .= '
          <div id="edamam-not-nutritional">
            See Detailed Nutrition Info on<br/><a target="_blank" href="'.$urlAddr.'"><img src="' . EDAMAM_RECIPE_PLUGIN_DIRECTORY . 'logo-plugin-big.png" class="edamam-img"></a>
          </div>';
        }
     
        $output .= '
        <img src="http://www.edamam.com/images/media/mentions/1x1.png" style="display:none;">            
        </div>';

        // create image and summary container
        if ($recipe->recipe_image != null || $recipe->summary != null) {
        $output .= '
        <div id="image-desc">';
        		if ($recipe->recipe_image != null) {
        			$style_tag = '';
        			$class_tag = '';
        			$image_width = get_option('recipe_image_width');
        			if ($image_width != null) {
        				$style_tag = 'style="width: '.$image_width.'px;"';
        			}
        			if (strcmp(get_option('recipe_image_hide'), 'Hide') == 0)
        				$class_tag .= 'hide-card';
        			if (strcmp(get_option('recipe_image_hide_print'), 'Hide') == 0)
        				$class_tag .= ' hide-print';
        			$output .= '<p class="'.$class_tag.'">
        			  <img class="recipe-image" itemprop="image" src="' . $recipe->recipe_image . '" title="' . $recipe->recipe_title . '" alt="' . $recipe->recipe_title . '" ' . $style_tag . ' />
        			</p>';
        		}
        		if ($recipe->summary != null) {
        			$output .= '<div id="recipe-summary" itemprop="description">';
        			$output .= edamam_recipe_break( '<p>', edamam_recipe_richify_item($recipe->summary, 'summary'), '</p>' );
        			$output .= '</div>';
        		}
        $output .= '
        </div>';
    	  }

    $ingredient_type= '';
    $ingredient_tag = '';
    $ingredient_class = '';
    $ingredient_list_type_option = get_option('recipe_ingredient_list_type');
    if (strcmp($ingredient_list_type_option, 'ul') == 0 || strcmp($ingredient_list_type_option, 'ol') == 0) {
        $ingredient_type = $ingredient_list_type_option;
        $ingredient_tag = 'li';
    } else if (strcmp($ingredient_list_type_option, 'p') == 0 || strcmp($ingredient_list_type_option, 'div') == 0) {
        $ingredient_type = 'span';
        $ingredient_tag = $ingredient_list_type_option;
    }
    
    if (strcmp(get_option('recipe_ingredient_label_hide'), 'Hide') != 0) {
        $output .= '<h3>' . get_option('recipe_ingredient_label') . '</h3>';
    }
    
    $output .= '
    <div id="edamam-widget-start"></div>
      <' . $ingredient_type . ' id="recipe-ingredients-list">';
    $i = 0;
    $ingredients = explode("\n", $recipe->ingredients); //!!mwp
    foreach ($ingredients as $ingredient) {
    
      $pos = strpos($ingredient, '[');
    
      if ($pos === false) {   
    		$output .= edamam_recipe_format_item($ingredient, $ingredient_tag, 'ingredient', 'ingredients', 'recipe-ingredient-', $i);            
      } else {    
        $ingredient = str_replace("[", "<h4>", $ingredient);
        $ingredient = str_replace("]", "</h4>", $ingredient);
        $output .= $ingredient; 
      }    
    
      $i++;
    
    }
    $output .= '
      </' . $ingredient_type . '>
    <div id="edamam-widget-end"></div>';

	// add the instructions
    if ($recipe->instructions != null) {
        
        $instruction_type= '';
        $instruction_tag = '';
        $instruction_list_type_option = get_option('recipe_instruction_list_type');
        if (strcmp($instruction_list_type_option, 'ul') == 0 || strcmp($instruction_list_type_option, 'ol') == 0) {
            $instruction_type = $instruction_list_type_option;
            $instruction_tag = 'li';
        } else if (strcmp($instruction_list_type_option, 'p') == 0 || strcmp($instruction_list_type_option, 'div') == 0) {
            $instruction_type = 'span';
            $instruction_tag = $instruction_list_type_option;
        }
        
        $instructions = explode("\n", $recipe->instructions);
        if (strcmp(get_option('recipe_instruction_label_hide'), 'Hide') != 0) {
            $output .= '<h3>'.get_option('recipe_instruction_label').'</h3>';
        }
        $output .= '<' . $instruction_type . ' id="recipe-instructions-list" class="instructions">';
        $j = 0;
        foreach ($instructions as $instruction) {
            if (strlen($instruction) > 1) {
            	$output .= edamam_recipe_format_item($instruction, $instruction_tag, 'instruction', 'recipeInstructions', 'recipe-instruction-', $j);
                $j++;
            }
        }
        $output .= '</' . $instruction_type . '>';
    }

    //!! add notes section
    if ($recipe->notes != null) {
        if (strcmp(get_option('recipe_notes_label_hide'), 'Hide') != 0) {
            $output .= '<h3>' . get_option('recipe_notes_label') . '</h3>';
        }

		$output .= '<div id="recipe-notes-list">';
		$output .= edamam_recipe_break( '', edamam_recipe_richify_item($recipe->notes, 'notes'), '' );
		$output .= '</div>';

	}

	//!!mwp add Edamam attribution and version



    $output .= '</div>'; //!!dc

    $output .= '</div>
		</div>';
    
    return $output;
}