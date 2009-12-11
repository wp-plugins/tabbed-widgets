<?php
/*
Plugin Name: Widget Context
Plugin URI: http://konstruktors.com/blog/
Description: Display widgets in context.
Version: 0.33
Author: Kaspars Dambis
Author URI: http://konstruktors.com/blog/

    Copyright 2008  Kaspars Dambis  (email: kaspars@konstruktors.com)
    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.
    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.
    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

class widget_context {
	
	var $options_name = 'widget_logic_options'; // Widget logic settings (visibility, etc)
	var $options_callbacks = 'widget_logic_callbacks'; // Store original widget control callbacks before taking them over
	var $widget_options = 'widget_widget_clone'; // Store widget clone options
	
	var $active_widgets = array();
	var $original_widget_settings = array();
	var $clone_options = array();
	
	// Clone any of the existing widgets that are currently being used.
	var $enablecloning = false;
	
	function widget_context() {
		
		if (!defined('WP_CONTENT_URL')) define('WP_CONTENT_URL', get_option('siteurl') . '/wp-content'); // Pre-2.6 compatibility
		$this->plugin_path = WP_CONTENT_URL . '/plugins/'. plugin_basename(dirname(__FILE__)) . '/';
		
		add_action('admin_menu', array($this, 'addAdminOptions'));	
		
		if (is_admin()) {
			// Append widget controls only when viewing admin area
			add_action('sidebar_admin_setup', array($this, 'save_and_restore_widget_controls'), 50);
			// Save original widget callbacks so that we can clone them after widget context has taken over them.
			
		}
		
		// Enable widget context check only when viewed publicly,
		if (!is_admin()) {
			add_action('widgets_init', array($this, 'replace_only_widget_output'), 760);
		}
		
		if ($this->enablecloning) {
			if (is_admin()) 
				add_action('widgets_init', array($this, 'save_original_widgets'), 2);
			
			$this->clone_options = get_option($this->widget_options);
			add_action('plugins_loaded', array($this, 'register_widget_clone'));
		}
	}
	
	function save_original_widgets() {
		global $wp_registered_widgets;
		
		$this->active_widgets = $this->get_active_widgets();
		if (empty($this->original_widget_settings)) $this->original_widget_settings = $wp_registered_widgets;

	}
	
	
	function addAdminOptions() {
		add_options_page('Widget Logic', 'Widget Logic', 10, basename(__FILE__), array($this, 'printAdminOptions'));
		add_action("admin_print_scripts", array($this, 'addAdminScripts'));
	}
	
	function addAdminScripts() {
		// Add stylesheet and javascript only at widget's admin
		if (strstr($_SERVER['SCRIPT_FILENAME'], '/widgets.php')) {
			wp_enqueue_script('widget-context', $this->plugin_path . 'widget-context.js', array('jquery')); 
			echo '<link type="text/css" rel="stylesheet" href="' . $this->plugin_path . 'admin-style.css" />' . "\n";
		}
	}	
	function save_and_restore_widget_controls() {
	/* 	1. Check if updated widget data is being sent
		2. Store widget logic data (get id from the hidden field)
		3. Restore widget control to original for content saving	*/
		// add_action('widgets_init', array($this, 'save_original_widgets'), 2);
		if (strstr($_POST['_wp_http_referer'], '/wp-admin/widgets.php')) {
			// Save widget context settings
			$this->save_widget_logic();
		} else {
			// Append widget controls
			$this->replace_widget_control();
		}
	}
	function save_widget_logic() {
		$widget_logic_new = array();
		
		if (!isset($_POST)) return;
		if (empty($_POST['widget-id'])) $_POST['widget-id'] = array();
		/*
		    $_POST Example:
		    [widget-id] => Array
        		    [0] => pages
		            [1] => wl-clone-173514241
		    [wl] => Array
			    [text-172710881] => Array
				    [homepage] => on
			    [wl-clone-1] => Array
				    [singlepage] => on
				    [categoryarchives] => on
				    [tagarchives] => on
		    [wl-clone] => Array
			    [172714191] => Array
				    [submit] => 1
		*/
	
			$options = get_option($this->options_name);
			$widget_logic_new = $_POST['wl'];
			
			if (isset($_POST['sidebar']) && !empty($_POST['sidebar'])) 
				$sidebar_id = strip_tags((string)$_POST['sidebar']);
			if (!empty($sidebar_id)) {
				$all_sidebars = wp_get_sidebars_widgets(false);
				$old_sidebar_content = $all_sidebars[$sidebar_id];
				
				if (count($old_sidebar_content) > 0) {
					// Update old widgets that were already in this sidebar
					foreach ($old_sidebar_content as $widget_id) {
						if (empty($widget_logic_new[$widget_id]) && !in_array($widget_id, $_POST['widget-id'])) {
							// No visibility for $widget_id was selected, clear them
							$widget_logic_new[$widget_id] = array();
						}
					}
				}
			}
			// Add new widget options
			foreach($widget_logic_new as $widget_id => $widget_logic) {
				// If neither type of widget logic behaviour is selected, set to default
				if (empty($widget_logic['incexc']) || !isset($widget_logic['incexc']) || $widget_logic['incexc'] == '') 
					$widget_logic['incexc'] = 'notselected';
						
				if (empty($widget_logic)) { 
					$widget_logic = array();
				}
				
				$options[$widget_id] = $widget_logic;
			}
			// Finally update both cloned widgets and widget logic options
			update_option($this->options_name, (array)$options); // widget logic
			
			return;
	}
	function replace_only_widget_output() {
		$this->replace_widget_control(true);
		return;
	}
	function replace_widget_control($replace_widget_output = false) {
		global $wp_registered_widget_controls, $wp_registered_widgets;
		
		if (!function_exists('wp_register_sidebar_widget')) return; // If WP < 2.3
		
		$registered_widget_controls = $wp_registered_widget_controls;		
		$registered_widgets = $wp_registered_widgets;
		
		foreach ($registered_widgets as $widget_id => $widget_data) {
			$widget_controls = $registered_widget_controls[$widget_data['id']];
			$widget_ops = array('description' => $widget_data['description'] . '+++');
			if (empty($widget_controls['width'])) $widget_controls['width'] = 300;
			
			$control_ops = array(
				'width' => $widget_controls['width'], 
				'height' => $widget_controls['height'], 
				'id_base' => $widget_controls['id_base']
			);
				
			if (is_array($widget_controls['params'][0])) {
				// If multiple item widget, move one step down
				$widget_controls['params'] = $widget_controls['params'][0];
			}
			
			$saved_callbacks[$widget_id] = array(
				'control_callback' => $widget_controls['callback'],
				'control_params' => $widget_controls['params'],
				'widget_callback' => $widget_data['callback'],
				'widget_params' => $widget_data['params'],
				'control_ops' => $control_ops,
				'widget_id' => $widget_id,
				'id_base' => $widget_controls['id_base']
			);
			
			
			if (empty($widget_controls['params'])) $widget_controls['params'] = array();
			$new_params = array_merge($widget_controls['params'], array('original' => $saved_callbacks[$widget_data['id']]));
					
			// Replace only widget output
			if ($replace_widget_output == true) {
				//wp_register_sidebar_widget($id, $name, 'wp_widget_text', $widget_ops, array( 'number' => $o ));
				wp_register_sidebar_widget(
					$widget_id, 
					$widget_data['name'], 
					array($this, 'replace_widget_output'),
					array('classname' => (string)$widget_data['classname']),
					$new_params
				);
			}
				
			// Replace also widget controls
			if ($replace_widget_output == false) {
				wp_register_widget_control(
					$widget_id,
					$widget_data['name'], 
					array($this, 'replace_callback'),
					$control_ops,
					$new_params
				);
			}
		}
		
		update_option($this->options_callbacks, $saved_callbacks);
		
	}
	function replace_widget_output($args = array()) {
		global $wp_registered_widgets, $wp_registered_sidebars;
		$all_params = func_get_args();
		$widget_callback = $all_params[1]['original']['widget_callback'];
if (is_numeric($all_params[1][0])) { $all_params[1] = $all_params[1][0]; }
		// Get widget logic options and check visibility settings
		$logic_options = get_option($this->options_name);
		$do_show = $this->check_widget_visibility($logic_options[$args['widget_id']]);
		if (is_callable($widget_callback) && $do_show) {
			call_user_func_array($widget_callback, $all_params);
			return true;
		} else {
			return false;
		}
		
	}
	
	function replace_callback($args = array()) {
		$all_params = func_get_args();
if (is_numeric($all_params[0][0])) $all_params = $all_params[0];
		// Display the original callback
		if (isset($args['original']['control_callback']) && is_callable($args['original']['control_callback'])) {
			call_user_func_array($args['original']['control_callback'], $all_params);
		}
		
		print $this->display_widget_logic($args['original']);
	}
	
	function get_current_url() {
		
		if ($_SERVER['REQUEST_URI'] == '') 
			$uri = $_SERVER['REDIRECT_URL'];
		else 
			$uri = $_SERVER['REQUEST_URI'];
		
		$url = (!empty($_SERVER['HTTPS'])) 
			? "https://".$_SERVER['SERVER_NAME'].$uri 
			: "http://".$_SERVER['SERVER_NAME'].$uri;
		// Remove trailing slash
		if (substr($url, -1) == '/') $url = substr($url, 0, -1);
		return $url;
	}
	// Thanks to Drupal: http://api.drupal.org/api/function/drupal_match_path/6
	function match_path($path, $patterns) {
		static $regexps;
		
		// get home url;
		$home_url = get_bloginfo('url');
		// add trailing slash if missing
		if (substr($home_url, -1) !== '/') $home_url = $home_url . '/';
		// if not at home, remove home path from current path
		if ($path !== $home_url) $path = str_replace($home_url, '', $path);
		
		if (!isset($regexps[$patterns])) {
			$regexps[$patterns] = '/^('. preg_replace(array('/(\r\n?|\n)/', '/\\\\\*/', '/(^|\|)\\\\<home\\\\>($|\|)/'), array('|', '.*', '\1'. preg_quote($home_url, '/') .'\2'), preg_quote($patterns, '/')) .')$/';
		}
		return preg_match($regexps[$patterns], $path);
	}
	
	function check_widget_visibility($vis_settings = array()) {
		global $paged;
		$do_show = false;
		$do_show_by_select = null;
		$do_show_by_url = null;
		if (empty($vis_settings)) return;
		// Check by current URL
		if (!empty($vis_settings['url']['urls'])) {
			// Split on line breaks
			$split_urls = split("[\n ]+", (string)$vis_settings['url']['urls']);
			$current_url = $this->get_current_url();
			foreach ($split_urls as $id => $check_url) {
				$check_url = trim($check_url);
				if ($check_url !== '') {
					if ($this->match_path($current_url, $check_url)) 
					$do_show_by_url = true;
				} else {
					$do_show_by_url = false;
				}
			}
		}
		// Check by tag settings
		if (!empty($vis_settings['location'])) {
			$currently = array();
				
			if ((is_front_page() || is_home()) && $paged < 1) $currently['is_front_page'] = true;
			if (is_page()) $currently['is_page'] = true;
			if (is_single()) $currently['is_single'] = true;
			if (is_archive()) $currently['is_archive'] = true;
			if (is_category()) $currently['is_category'] = true;
			if (is_tag()) $currently['is_tag'] = true;
			if (is_author()) $currently['is_author'] = true;
			if (is_search()) $currently['is_search'] = true;
			if (is_404()) $currently['is_404'] = true;
			if (is_attachment()) $currently['is_attachment'] = true;
			$current_location = array_keys($currently); 
			if (count($current_location) > 0) {
				$visibility_options = array_keys($vis_settings['location']);
				foreach($current_location as $location_id) {					
					if (in_array($location_id, $visibility_options)) 
						$do_show_by_select = true;
				}
			}
		}
		
		// Group select and url checks
		if ($do_show_by_url == true || $do_show_by_select == true) {
			$do_show = true;
		} else {
			$do_show = false;
		}
		// Include or exclude?
		if ($vis_settings['incexc'] == '' || empty($vis_settings['incexc']) || !isset($vis_settings['incexc'])) {
			$do_show = true;
		} elseif ($do_show == true && ($vis_settings['incexc'] == 'selected')) {
			$do_show = true;
		} elseif ($do_show == false  && ($vis_settings['incexc'] == 'notselected')) {
			$do_show = true;
		} else {
			$do_show = false;
		}
		// if ($do_show) print "show - "; else print "no - ";
		// print $vis_settings['incexc']  . '<br />';		
		return $do_show;
	}
	function display_widget_logic($args = array()) {
		
		if ($args['control_params'][0]['number'] == -1) $wid = $args['id_base'] . '-%i%'; 
			else $wid = $args['widget_id'];
		
		$options = get_option($this->options_name);		
		$group = 'location'; // Produces: wl[$wid][$group][homepage/singlepost/...]
		$out = '<div class="widget-context"><div class="widget-context-inside">';
		$out .=   '<div class="wl-header"><h5>Widget context:</h5>'
			. '<p class="wl-visibility">'		
				. $this->make_simple_radio($options, $wid, 'incexc', 'selected', 'Display only on selected') 
				. $this->make_simple_radio($options, $wid, 'incexc', 'notselected', 'Display on every page except selected') 
			. '</p></div>'
			. '<div class="wl-wrap-columns"><div class="wl-columns">' 
			. '<div class="wl-column-2-1"><p>' 
			. $this->make_simple_checkbox($options, $wid, $group, 'is_front_page', __('Homepage'))
			. $this->make_simple_checkbox($options, $wid, $group, 'is_single', __('Single Post'))
			. $this->make_simple_checkbox($options, $wid, $group, 'is_page', __('Single Page'))
			. $this->make_simple_checkbox($options, $wid, $group, 'is_attachment', __('Attachment'))
			. $this->make_simple_checkbox($options, $wid, $group, 'is_search', __('Search'))
			. '</p></div>'
			. '<div class="wl-column-2-2"><p>' 
			. $this->make_simple_checkbox($options, $wid, $group, 'is_archive', __('All Archives'))
			. $this->make_simple_checkbox($options, $wid, $group, 'is_category', __('Category Archive'))
			. $this->make_simple_checkbox($options, $wid, $group, 'is_tag', __('Tag Archive'))
			. $this->make_simple_checkbox($options, $wid, $group, 'is_author', __('Author Archive'))
			. $this->make_simple_checkbox($options, $wid, $group, 'is_404', __('404 Error'))
			. '</p></div></div>'
		
			. '<div class="wl-options">'
			. $this->make_simple_textarea($options, $wid, 'url', 'urls', __('Target by URL'), __('Enter one location fragment per line. Use <strong>*</strong> character as a wildcard. Use <strong><code>&lt;home&gt;</code></strong> to select front page. Examples: <strong><code>category/peace/*</code></strong> to target all <em>peace</em> category posts; <strong><code>2012/*</code></strong> to target articles written in year 2012.'))
			. '</div></div>'
			. $this->make_simple_textarea($options, $wid, 'general', 'notes', __('Notes (invisible to public)'))
		. '</div></div>';
		return $out;
	}
	
	function printAdminOptions() {
		global $wp_registered_widget_controls, $wp_registered_widgets;
		
		$out = 	'<div class="wrap"><h2>'.__('Widget Logic &amp; Cloning Settings') . '</h2>'
		    	. '<p>'. __('<strong>Important</strong>: To configure widget visibility and clone widgets, go to <a href="widgets.php">Design &raquo; Widgets</a>.') . '</p>'
		    	. '<p>'. __('Todo: Add widget cloning option: enable/disable.') . '</p>';
		$out .= '</div>';
		
		print $out;
		print '<pre>'; print_r($wp_registered_widgets); print '</pre>';
		
	}
	
	
	
	
	/* 
		Interface constructors 
		
	*/
	
	function makeSubmitButton() {
		return '<p class="submit"><input type="submit" name="Submit" value="' . __('Update Options') . '" /></p>';
	}
	
	function makeDonate() {
		return '<p class="tw-donate"><a href="https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=kaspars%40konstruktors%2ecom&item_name=Tabbed%20Widgets%20Plugin%20for%20WordPress&no_shipping=1&no_note=1&tax=0&currency_code=EUR&lc=LV&bn=PP%2dDonationsBF&charset=UTF%2d8"><img alt="Donate" src="https://www.paypal.com/en_US/i/btn/btn_donate_LG.gif" /></a> '. __('Plugin developed by') . ' <a href="http://konstruktors.com/blog/">Kaspars Dambis</a>. '. __('If you find it useful, please consider donating.') .'</p>';
	}	
	function make_simple_checkbox($options, $prefix, $id, $fieldname = null, $label) {
		
		if ($fieldname !== null) {
			$value = strip_tags($options[$prefix][$id][$fieldname]);
			$fieldname = '[' . $fieldname . ']';
		} else {
			$value = strip_tags($options[$prefix][$id]);
			$fieldname = '';
		}
		
		$prefix = '[' . $prefix . ']';
		$id = '[' . $id . ']';
		
		if (!empty($value)) {
			$value = 1; $checked = 'checked="checked"'; $classname = 'wl-active';
		} else {
			$value = 0; $checked = ''; $classname = 'wl-inactive'; 
		}
		
		$out = '<label class="' . $classname . '"><input type="checkbox" name="wl'. $prefix . $id . $fieldname . '" '. $checked .' />&nbsp;' 
			. $label . '</label> ';
			
		return $out;
	}
	function make_simple_textarea($options, $prefix, $id, $fieldname = null, $label, $tip = null) {
		$classname = $fieldname;
		
		if ($fieldname !== null) {
			$value = $options[$prefix][$id][$fieldname];
			$fieldname = '[' . $fieldname . ']';
		} else {
			$value = $options[$prefix][$id];
			$fieldname = '';
		}
		$prefix = '[' . $prefix . ']';
		$id = '[' . $id . ']';
		
		if ($tip !== null) $tip = '<p class="wl-tip">' . $tip . '</p>';
		
		$out = '<div class="wl-'. $classname .'">'
			. '<label for="wl'. $prefix . $id . $fieldname . '"><strong>' . $label . '</strong></label>'
			. '<textarea name="wl'. $prefix . $id . $fieldname . '" id="wl'. $prefix . $id . $fieldname . '">'. stripslashes($value) .'</textarea>'
			. $tip . '</div>';
		return $out;
	}
	function make_simple_radio($options, $id, $fieldname, $value, $label = null) {
		if ($options[$id][$fieldname] == $value) {
			$checked = 'checked="checked"'; $classname = 'wl-active';
		} else {
			$checked = ''; $classname = 'wl-inactive'; 
		}
		
		$id = '[' . $id . ']';
		$fieldname = '[' . $fieldname . ']';
		
		$out = '<label class="' . $classname . ' label-'. $value .'"><input type="radio" name="wl'. $id . $fieldname . '" value="'. $value .'" '. $checked .' /> ' 
			. $label . '</label>';
			
		return $out;
	}
	/*
		Create widgets that allow making clones of any other widget.		
		
	*/
	function register_widget_clone() {
		
		$clone_options = $this->clone_options;
		$widget_ops = array('description' => __('Clone any of the widgets that are currently being used inside a sidebar'));
		$control_ops = array('id_base' => 'wl-clone');
		$name = __('Widget Clone');
		
		$id = false;
		foreach (array_keys($clone_options) as $o) {
			$id = 'wl-clone-' . $o;
			$params = array('number' => $o);
			$params['id_is'] = $id;
			
			wp_register_sidebar_widget($id, $name, array($this, 'wl_wiget_copy'), $widget_ops, $params);
			wp_register_widget_control($id, $name, array($this, 'wl_wiget_copy_control'), $control_ops, $params);
		}
		
		if (!$id) {
			$params = array('number' => -1);
			wp_register_sidebar_widget($control_ops['id_base']. '-1', $name, array($this, 'wl_wiget_copy'), $widget_ops, $params);
			wp_register_widget_control($control_ops['id_base']. '-1', $name, array($this, 'wl_wiget_copy_control'), $control_ops, $params);
		}
	}
	
	
	function wl_wiget_copy($args) {
		$params = func_get_args();
		extract($params);
		// Get cloned widget options
		$clone_options = $this->clone_options;
		
		// Get callback of the original widget (this is the clone)
		foreach ($clone_options as $clone_id => $clone_data) {
			$is_this_original = strstr($clone_id, $args['widget_id']);
			
			if ($is_this_original === false) {
				$original_id = $clone_data['widget'];
			} 
		}
		if (empty($original_id)) return;
		
		$widgets_data = $this->original_widget_settings[$original_id];
		
		if (is_callable($widgets_data['callback'])) {
			call_user_func_array($widgets_data['callback'], array_merge($widgets_data['params'], $params));
		} else {
			return false;
		}	
		
		// print "<pre>"; print_r($widgets_data); print "</pre>";
		return true;
	}
	function wl_wiget_copy_control($widget_args) {
		
		$clone_options = $this->clone_options;
		
		if (isset($_POST['wl-clone'])) {
			$wl_clones = $_POST['wl-clone']; 
			$wl_clone_keys = array_keys($wl_clones);
			
			foreach ($wl_clones as $clone_id => $clone_data) {
				$real_clone_id = 'wl-clone-' . $clone_id;
				if (empty($clone_data['widget']) && in_array($real_clone_id, $_POST['widget-id'])) {
					// Widget was only moved not deleted. Preserve selected widget.
					$clone_data['widget'] = $clone_options[$clone_id]['widget'];
				} else {
					$clone_data['widget'] = $clone_data['widget'];
				}
				$clone_data['real-id'] = $real_clone_id;
				$clone_options[$clone_id] = $clone_data;
			}
			foreach ($clone_options as $clone_id => $clone_data) {
				if (!in_array($clone_data['real-id'], $_POST['widget-id']) || empty($_POST['widget-id'])) {
					unset($clone_options[$clone_data['real-id']]);
				}
			}
			
			update_option($this->widget_options, $clone_options);
		}
		
		$this->wl_wiget_copy_control_form($widget_args, $clone_options);
	}
	function wl_wiget_copy_control_form($widget_args, $options = array()) {
		$number = $widget_args['number'];
		if ($number == -1) $number = '%i%';
		
		$out = '<p>'. $this->make_widget_dropdown($number, $options[$number]['widget']) . '</p>';
		$out .= '<input type="hidden" name="wl-clone[' . $number . '][submit]" value="1" />';
		if (!empty($widget_args['id_is'])) {
			$out .= '<input type="hidden" name="wl-clone[' . $number . '][real-id]" value="' . $widget_args['id_is'] . '" />';
		}
		
		print $out;
	}
	
	function get_active_widgets() {
		global $sidebars_widgets, $wp_registered_sidebars, $wp_registered_widgets;
		
		if (empty($this->registered_widgets)) $this->registered_widgets = $wp_registered_widgets;
		$this->visible_widgets = wp_get_sidebars_widgets(false);
		
		ob_start();
		foreach ($this->visible_widgets as $sidebar => $widgets) {
			foreach ($widgets as $widget_id => $widget_data) {
				$widget_name = $this->registered_widgets[$widget_data]['name'];
				
					$widget_params = $this->registered_widgets[$widget_data]['params'];
					$widget_callback = $this->registered_widgets[$widget_data]['callback'];
					
					// if parameter is a string
					if (isset($widget_params[0]) && !is_array($widget_params[0])) {
						$widget_params = $widget_params[0];
					}
					
					$sidebar_params['before_title'] = '[[';
					$sidebar_params['after_title'] = ']]';
					
					$all_params = array_merge(array($sidebar_params), (array)$widget_params);
					
					if (is_callable($widget_callback) && !strpos($widget_data, 'wl-clone') && !empty($widget_name)) {
						
						call_user_func_array($widget_callback, $all_params);
						$widget_title = ob_get_contents();
						ob_clean();
						$find_fn_pattern = '/\[\[(.*?)\]\]/';
						preg_match_all($find_fn_pattern, $widget_title, $result);
						
						if (!empty($result[1][0])) {
							$widget_title = $widget_name . ': ' . $result[1][0];
						} else {
							$widget_title = $widget_name;
						}
					} else {
						$widget_title = $widget_name;
					}
					$out[$widget_data] = $widget_title;
			}
		}
		
		return $out;
	}	
	
	
	function getArrayIndex($source, $searching) {
		$index = '';
		$source_count = count($source);
		$_v_source = array_values($source);
		$_k_source = array_keys($source);
		
		for ($count = 0; $count < $source_count; $count++) {
			if (in_array($searching, $_v_source[$count])) {
				$index = $_k_source[$count];
			}
		}
		return $index;
	}
	function make_widget_dropdown($id, $selected_id) {
		$active_widgets = $this->active_widgets;
		if (empty($active_widgets)) return __('No active widgets were found.');
		$list = '<label class="tw-in-widget-list">' . __('Select on of the active widgets') . ': <select name="wl-clone[' . $id . '][widget]">';	
		
		if (empty($selected_id)) 
			$list .= '<option value="empty" selected="selected">'. __('None') .'</option>'; 
		else 
			$list .= '<option value="empty"> </option>';
		
		foreach ($active_widgets as $widget => $name) {
			
			if (!empty($selected_id)) {
				if ($selected_id == $widget) {
					$selected = 'selected="selected"';
				} else {
					$selected = '';
				}
			}
			
			if (strpos($widget, 'wl-clone') === false) {
				$list .= '<option value="' . $widget . '" ' . $selected . '>' .  $name . '</option>';
			}
		}
		
		$list .= '</select></label>';
		
		return $list;
	}
}
$root = dirname(dirname(dirname(dirname(__FILE__))));
if (file_exists($root . '/wp-load.php')) {
	require_once($root . '/wp-load.php'); // WP 2.6
} else {
	require_once($root . '/wp-config.php'); // Before 2.6
}
// Start your engine and go!
new widget_context();
?>