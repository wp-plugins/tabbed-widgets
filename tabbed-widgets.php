<?php
/*
Plugin Name: Tabbed Widgets
Plugin URI: http://wordpress.org/extend/plugins/tabbed-widgets/
Description: Place widgets into tabbed and accordion type interface blocks. Configuration options are available under <em>Design &raquo; <a href="themes.php?page=tabbed-widgets.php">Tabbed Widgets</a></em>.
Version: 0.7
Author: Kaspars Dambis
Author URI: http://konstruktors.com/blog/

Thanks for the suggestions to Ronald Huereca.
*/

class tabbedWidgets {
	
	var $donot_list_without_config = array('categories-1', 'text', 'rss-1');
	var $tw_options_name = 'tabbed_widgets_options';
	var $tw_original_widgets = 'tabbed_widgets_originals';
	var $tabbed_widget_content = array();
	var $stored_widgets = array();
	var $tw_available = 8;
	var $tabs_per_tw = 6;
	var $defaultRotateInterval = 7;
	var $plugin_path = '';
	
	var $active_widgets = array();
	var $registered_widgets = array();
	var $tw_array = array();
	var $styles_called = array();
	
	
	function tabbedWidgets($printjsvars = false) {
		
		if (!defined('WP_CONTENT_URL')) define('WP_CONTENT_URL', get_option('siteurl') . '/wp-content'); // Pre-2.6 compatibility
		$this->plugin_path = WP_CONTENT_URL . '/plugins/'. plugin_basename(dirname(__FILE__)) . '/';
		
		if (!$printjsvars) {
			// Save original widget callbacks in case some other plugin takes control over
			add_action('widgets_init', array($this, 'initProcedures'));
			add_action('admin_menu', array($this, 'addOptionsPage'));
			add_action('wp_head', array($this, 'addHeader'), 1);
			add_action('plugins_loaded', array($this, 'registerWidgets'), 2);
		} else {
			$this->printJsVars();	
		}
	}
	
	
	function initProcedures() {
		global $wp_registered_widgets;
		
		// Add an invisible sidebar for placing widgets that would be only used inside tabbed interface.
		if (function_exists('register_sidebar')) {
			// Add widgetized area for placing and configuring widgets that are going to be used in tabbed widgets.
		    register_sidebar(array('name' => 'Invisible Widget Area'));
		}
		
		// Save original widgets
		$this->stored_widgets = $wp_registered_widgets;
		
		// Tabbed Widget settings will be altered only from the admin, save resources
		if (is_admin()) {
			// Save widgets that are currently active
			$this->active_widgets = $this->get_active_widgets();
			// Save it in our own row, as other plugins might take it over when we need it. Like widget context plugin, for example.
			update_option($this->tw_original_widgets, $wp_registered_widgets);
		}
	}


	function addOptionsPage() {
		$options_page = add_theme_page('Tabbed Widgets', 'Tabbed Widgets', 10, basename(__FILE__), array($this, 'printAdminOptions'));
		add_action("admin_print_scripts-$options_page", array($this, 'addAdminCSS'), 1);
	}
	
	
	function addHeader() {
		
		if (function_exists('wp_enqueue_script')) {

			$tw_options = get_option($this->tw_options_name);

			$libtitle = 'jquery';			
			$add_tabs_js = false;

			if (function_exists('get_avatar') && !class_exists('WP_Dependencies')) {
 				// 2.5
				$add_tabs_js = true;
 				wp_enqueue_script('jquery');
			} elseif (class_exists('WP_Dependencies')) { 
				// 2.6 or up
				wp_enqueue_script('jquery-ui-core');
				wp_enqueue_script('jquery-ui-tabs');
			} else {
 				// 2.3
				$libtitle = 'tw-jquery';
				$add_tabs_js = true;
 				wp_enqueue_script('tw-jquery',  $this->plugin_path . 'js/jquery-1.2.3.min.js', false, '1.2.3'); 
			}
			
			// if 2.5 or below then add jQuery UI tabs scripts
			if ($add_tabs_js) {
				wp_enqueue_script('tw-tabs',  $this->plugin_path . 'js/jquery-ui-tabs.min.js', array($libtitle));
			}

			
			// for accordion
			wp_enqueue_script('tw-dimensions',  $this->plugin_path . 'js/jquery.dimensions.pack.js', array($libtitle));
			wp_enqueue_script('tw-accordion',  $this->plugin_path . 'js/jquery.accordion.js', array($libtitle));
			wp_enqueue_script('tw-easing',  $this->plugin_path . 'js/jquery.easing.js', array($libtitle));
			
			// check if rounded corners are enabled
			if (!empty($tw_options['enable-rounded-corners'])) {
				wp_enqueue_script('tw-cornerz',  $this->plugin_path . 'js/cornerz.js', array($libtitle));
			}
			
			// init all
			wp_enqueue_script('tw-init',  $this->plugin_path . basename(__FILE__) . '?returnjs=true', array($libtitle));
		}
		
		// if (function_exists('wp_enqueue_style')) wp_enqueue_style('tabbed-widgets', $this->plugin_path . 'js/uitabs.css'); else
		echo '<link type="text/css" rel="stylesheet" href="' . $this->plugin_path . 'js/uitabs.css" />' . "\n";
	}


	function addAdminCSS() {
		echo '<link type="text/css" rel="stylesheet" href="' . $this->plugin_path . 'admin-style.css" />' . "\n";
	}	
	

	function printJsVars() {
		$optionsvar = '$rotateoptions';
		
		// read the tabs init file
		$filename = dirname(__FILE__) . '/js/init.ui.tabs.js';
		$handle = fopen($filename, "r");
		$contents = fread($handle, filesize($filename));
		fclose($handle);
		
		$tw_options = get_option($this->tw_options_name);
		$options_count = count($tw_options);
		if (empty($tw_options)) return;

		if (!empty($tw_options['enable-rounded-corners'])) $rounded_corners = 'true';
			else $rounded_corners = 'false';
		
		$jsvars = 'var ' . $optionsvar . ' = new Array();' . "\n";
		$jsvars .= 'var $tw_rounded_corners = ' . $rounded_corners . ";\n";

		for ($count = 1; $count <= $options_count; $count++) {
			
			$style = $tw_options[$count]['style'];
			$dorotate = $tw_options[$count]['rotate'];
			$default_rotatetime = $tw_options['default_rotate_time'];
			$rotatetime = $tw_options[$count]['rotatetime'];
			$randomstart = $tw_options[$count]['randomstart'];
			$start = $tw_options[$count]['start'];
			
			if (!empty($dorotate)) $dorotate = 'true';
				else $dorotate = 'false';
			
			if (!empty($randomstart)) $randomstart = 'true';
				else $randomstart = 'false';
			
			if ($dorotate && empty($rotatetime)) {
				$rotatetime = $default_rotatetime;
			}

			$jsvars .= $optionsvar . '[' . $count . '] = new Array();' . "\n";
			$jsvars .= $optionsvar . '[' . $count . ']["style"] = "' . $style . "\";\n";
			$jsvars .= $optionsvar . '[' . $count . ']["rotate"] = ' . $dorotate . ";\n";
			$jsvars .= $optionsvar . '[' . $count . ']["randomstart"] = ' . $randomstart . ";\n";

			if (is_numeric($start) && $start !== 'default') {
				$jsvars .= $optionsvar . '[' . $count . ']["start"] = ' . $start . ";\n";
			}
			
			if (is_numeric($rotatetime)) {
				$jsvars .= $optionsvar . '[' . $count . ']["interval"] = ' . $rotatetime . ";\n";
			} else {
				$jsvars .= $optionsvar . '[' . $count . ']["interval"] = false;' . "\n";     	
     			}
		}
		
		header('Content-type: application/x-javascript');
		header('Pragma: private');
		header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 31356000) . ' GMT');

		header('Cache-Control: max-age=31356000, must-revalidate');
		
		print $jsvars . "\n";
		print $contents;
		
	}

	
	function registerWidgets() {
		if (!function_exists('wp_register_sidebar_widget')) return;
		
		if (empty($this->tabbed_widget_content)) 
			$this->tabbed_widget_content = get_option($this->tw_options_name);
		
		$tw_options = $this->tabbed_widget_content;
		$options_count = count($tw_options);
		
		if (empty($tw_options)) return;

		for ($count = 1; $count <= $options_count; $count++) {
			if (is_array($tw_options[$count]['widgets']))
				$singlewidget = implode('', $tw_options[$count]['widgets']);
			
			if ($singlewidget !== '') {
				
				$dirtyname = 'Tabbed Widget ' . $count;
				
				if (empty($tw_options[$count]['title'])) 
					$name = $dirtyname;
				else 
					$name = 'TW: ' . $tw_options[$count]['title'];
				
				$register_widget_id = 'tabbed-widget-' . $count;

				$unregisterall = 0;			
				if ($unregisterall) {
					register_sidebar_widget($name, '');
					register_widget_control($name, '');
				} elseif (function_exists('wp_register_sidebar_widget')) {
					// wp_register_sidebar_widget($id, $name, $output_callback, $options = array())
			    	wp_register_sidebar_widget(
						$register_widget_id, 
						$name, 
						array($this, 'outputTabbedWidget'),
						array('classname' => 'tabbed-widget')
					);
				} else {
					return;
				}
			}
		}
			
	}
	
	
	function outputTabbedWidget($defargs) {
		global $wp_registered_sidebars, $wp_registered_widgets;
		
		// This should never happen, but lets make sure it exists
		if (empty($this->tabbed_widget_content)) 
			$this->tabbed_widget_content = get_option($this->tw_options_name);
		if (empty($this->stored_widgets)) 
			$this->stored_widgets = get_option($this->tw_original_widgets);
		
		// Get widget data for the widgets inside
		$tabbed_widgetdata = $wp_registered_widgets[$defargs['widget_id']];
		// Get widget data for the tabbed widget
		$no = str_replace('tabbed-widget-', '', $defargs['widget_id']);
		$widgetdata = $this->tabbed_widget_content[$no];
		
		if (empty($widgetdata['widgets'])) 
			$widgetdata['widgets'] = array();
		
		foreach ($widgetdata['widgets'] as $widget_id => $widget_inside) {
			// check if widget content is not empty
			$widget_inside = trim($widget_inside);
			if (empty($widget_inside) || $widget_inside == '') {
				unset($widgetdata['widgets'][$widget_id]);
			}
		}
		
		// Count how many normal widgets are in this tabbed widget
		$widgets_inside_count = count($widgetdata['widgets']);

		// get the id of the sidebar this widget is in
		$sidebar_id = $this->getArrayIndex(wp_get_sidebars_widgets(), $defargs['widget_id']);
		$sidebar_params = $wp_registered_sidebars[$sidebar_id];
		// Get the before_widget data for this sidebar
		$before_widget_raw = $sidebar_params['before_widget'];
		// Prepare tabbed widget wrapper
		$defargs['before_widget'] = sprintf($before_widget_raw, $defargs['widget_id'], 'tw-tabbed-widgets');
		
		
		for ($count = 0; $count < $widgets_inside_count; $count++) {
			$widget_id = $widgetdata['widgets'][$count];
			$widget_content = $wp_registered_widgets[$widget_id];
			
			// combine widget formating params with other widget params
			$params = array_merge(array($sidebar_params), (array)$this->stored_widgets[$widget_id]['params']);
			
			// Substitute HTML id and class attributes into before_widget
			$classname_ = '';
			foreach ((array)$this->stored_widgets[$widget_id]['classname'] as $cn) {
				if (is_string($cn)) $classname_ .= '_' . $cn;
				elseif (is_object($cn)) $classname_ .= '_' . get_class($cn);
			}
			$classname_ = strtolower(ltrim($classname_, '_'));
			$before_widget = sprintf($before_widget_raw, $widget_id, $classname_);
			
			$params[0]['name'] = $widget_id;
			$params[0]['before_widget'] = $before_widget;
			
			$wout[$count]['callback'] = $this->stored_widgets[$widget_id]['callback'];
			$wout[$count]['params'] = $params;
			$wout[$count]['title'] = $widgetdata['titles'][$count];
		}
		
		if ($widgetdata['style'] == 'accordion') {
			echo $this->style_accordion($defargs, $widgetdata, $wout);
		} elseif ($widgetdata['style'] == 'tabs') {
			echo $this->style_tabbed($defargs, $widgetdata, $wout);
		}
	}
	
	
	function style_tabbed($defargs, $widgetdata, $wout) {
		
		$this->hide_tabbed_titles = true;
		
		$widgets_inside_count = count($widgetdata['widgets']);
		$without_title_css = ' without_title';
		
		if (!empty($widgetdata['showtitle'])) {
			$widget_title = $defargs['before_title'] . $widgetdata['title'] . $defargs['after_title'];
			$without_title_css = '';
		}
		
		$out = '<div class="tw-rotate' . $without_title_css . '"><ul class="tw-nav-list">';
		for ($count = 0; $count < $widgets_inside_count; $count++) {
			$out .= '<li><a href="#'.$defargs['widget_id'].'-'.$count.'"><span>'. $widgetdata['titles'][$count] .'</span></a></li> ';
		}		
		$out .= '</ul>';
		
		$result = $defargs['before_widget'];
		$result .= $widget_title;
		$result .= $out;
		
		for ($count = 0; $count < $widgets_inside_count; $count++) {
			
			if (is_callable($wout[$count]['callback'])) {
				if (strstr($wout[$count]['params'][0]['before_widget'], '<li')) {
					$wrap_tag = 'ul';
				} else {
					$wrap_tag = 'div';
				}
				$result .= '<'. $wrap_tag .' id="'. $defargs['widget_id'] .'-'. $count .'" class="tabbed-widget-item">'; 
				$result .= $this->callMe($wout[$count]['callback'], $wout[$count]['params']); 
				$result .= '</'. $wrap_tag .'>';
			}
		}
		
		$result .= '</div>';
		$result .= $defargs['after_widget'];
		
		return $result;
	}
	
	
	function style_accordion($defargs, $widgetdata, $wout) {
	
		extract($defargs);
		$this->hide_tabbed_titles = true;
		
		$widgets_inside_count = count($widgetdata['widgets']);
		$without_title_css = ' without_title';
		
		if (!empty($widgetdata['showtitle'])) {
			$widget_title = $before_title . $widgetdata['title'] . $after_title;
			$without_title_css = '';
		}
		
		$result = $before_widget;
		$result .= $widget_title;
		$result .= '<div class="tw-accordion tw-accordion-'. $defargs['widget_id'] . $without_title_css .'">';
		
		for ($count = 0; $count < $widgets_inside_count; $count++) {
			if (is_callable($wout[$count]['callback'])) {
				if (strstr($wout[$count]['params'][0]['before_widget'], '<li')) {
					$wrap_tag = 'ul';
				} else {
					$wrap_tag = 'div';
				}
				
				$result .= '<h4 class="tw-widgettitle"><span>'. $widgetdata['titles'][$count] .'</span></h4> ';
				$result .= '<'. $wrap_tag .' id="'. $defargs['widget_id'] .'-'. $count .'" class="tabbed-widget-item">'; 
				$result .= $this->callMe($wout[$count]['callback'], $wout[$count]['params']); 
				$result .= '</'. $wrap_tag .'>';
			}
		}
		
		$result .= '</div>';
		$result .= $after_widget;
		
		return $result;
	}
	
	
	function callMe($callback, $params) {
		
		if ($this->hide_tabbed_titles) {
			$params[0]['before_title'] = '<div class="tw-hide">' . $params[0]['before_title'];
			$params[0]['after_title'] = $params[0]['after_title'] . '</div>';
		}
		
		ob_start();
			call_user_func_array($callback, $params);
			$output = ob_get_contents();
		ob_end_clean();
		
		return $output;
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

	
	function printAdminOptions() {
		
		if($_POST['tw_options_submitted'] == 'y') {
			update_option($this->tw_options_name, $_POST['tw']);
			$ifupdated = '<div id="message" class="updated fade"><p><strong>' . __('Options saved.') . '</strong></p></div>';
		}
		
		$tw_options = get_option($this->tw_options_name);
		
		$options = $ifupdated 
			. '<div class="wrap tw-settings"><form method="post" action="' . str_replace('%7E', '~', $_SERVER['REQUEST_URI']). '">' 
			. wp_nonce_field('update-options')
			. '<h2>Tabbed Widget Settings</h2>';
		
		$options .= $this->makeDonate();	
		$options .= '<fieldset>'
			 . '<div><p>' . $this->makeDefaultRotateOption($tw_options) . '</p></div>'
			 . $this->make_checkbox('Enable rounded corners for tabs', 'rounded-corners', 'using <a href="http://labs.parkerfox.co.uk/cornerz/">Cornerz</a> plugin for jQuery', $tw_options)
		 	 . '</fieldset>';
		
		$options .= $this->makeSubmitButton();
		
		$options .= '<div class="widget-wrapper">';
		for ($id = 1; $id <= $this->tw_available; $id++) {
		
			$options .= '<fieldset class="widget-fieldset"><legend><strong>' . __('Tabbed Widget No.') . ' ' . $id . '</strong></legend><div>';
			$options .= '<p class="tw-title"><label>' . __('Widget Title') . ': <input type="text" name="tw[' . $id . '][title]"  class="tw-widget-title" value="'. $tw_options[$id]['title'] .'" /></label> ';
			$options .= ' &mdash; ' . $this->makeTitleOption($id, $count, $tw_options) . '</p>';
			
			for ($count = 1; $count <= $this->tabs_per_tw; $count++) {
				$tab_title = __('Tab') . ' ' . $count . ':';
				$options .= '<div class="tw-each-tab">' . $this->makeSimpleRadio($tw_options, $id, 'start', $count, $tab_title) . $this->makeSingleWidgetsList($id, $count, $tw_options) . ' ' .  $this->makeSingleWidgetsTitleField($id, $count, $tw_options) . '</div>';
			}
			
			$options .= '<div class="tw-each-tab">' . $this->makeSimpleRadio($tw_options, $id, 'start', 'default', 'Default start tab') . '</div>';
			
			if (empty($tw_options[$id]['style'])) $tw_options[$id]['style'] = 'tabs';
			
			$options .= '<p class="tw-style-type"><strong>'. __('Style as') .'</strong>: ';
			$options .= '<span>' . $this->makeSimpleRadio($tw_options, $id, 'style', 'tabs', __('tabs')) . ' '. __('or') .'</span> ';
			$options .= '<span>' . $this->makeSimpleRadio($tw_options, $id, 'style', 'accordion', __('accordion')) . '</span></p>';
			$options .= '<div class="tw-rotateoptions">' . $this->makeRotateOption($id, $count, $tw_options) . '</div>';
			$options .= '<div class="tw-randomstart">' . $this->makeRandomStartOption($id, $count, $tw_options) . '</div>';
			$options .= $this->makeSubmitButton();
			$options .= '</div></fieldset>';
		}
		$options .= '</div>';
		
		$options .= $this->makeSubmitButton() . '<input type="hidden" name="tw_options_submitted" value="y"></form></div>';
				
		print $options;
		
	}
	
	
	function makeSubmitButton() {
		return '<p class="submit"><input type="submit" name="Submit" value="' . __('Update Options') . '" /></p>';
	}
	
	function makeDonate() {
		return '<p class="tw-donate"><a href="https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=kaspars%40konstruktors%2ecom&item_name=Tabbed%20Widgets%20Plugin%20for%20WordPress&no_shipping=1&no_note=1&tax=0&currency_code=EUR&lc=LV&bn=PP%2dDonationsBF&charset=UTF%2d8"><img alt="Donate" src="https://www.paypal.com/en_US/i/btn/btn_donate_LG.gif" /></a> '. __('Plugin developed by') . ' <a href="http://konstruktors.com/blog/">Kaspars Dambis</a>. '. __('If you find it useful, please consider donating.') .'</p>';
	}	

	function make_checkbox($label, $fieldname, $tip = false, $options = array()) {
		if ($tip) $tip = '<small>(' . __($tip) . ')</small>';
		$doenable = $options['enable-' . $fieldname];
	
		if (!empty($doenable)) {
			$value = 1; $checked = 'checked="checked"';
		} else {
			$value = 0; $checked = '';
		}			
		$out = '<div><label><input type="checkbox" id="option-' . $fieldname . '" name="tw[enable-' . $fieldname . ']" '. $checked .' /> '
			. __($label) . '</label> ' . $tip . '</div>';

		return $out;
	}

	function makeSingleWidgetsList($id = 0, $count = 0, $options = array()) {
		
		$list = '<label class="tw-in-widget-list"><select name="tw[' . $id . '][widgets][]">';	
		$list .= '<option></option>';
		$active_widgets = $this->active_widgets;
		
		foreach ($active_widgets as $widget => $value) {
			
			if (!empty($options)) {
				if ($options[$id]['widgets'][$count-1] == $widget) {
					$selected = 'selected="selected"';
				} else {
					$selected = '';
				}
			}
			
			if (strpos($widget, 'tabbed-widget') === false && strpos($widget, 'wl-clone') === false) {
				$list .= '<option value="' . $widget . '" ' . $selected . '>' . $value . '</option>';
			}
		}
		
		$list .= '</select></label>';
		
		return $list;
	}
	
	
	function makeSingleWidgetsTitleField($id = 0, $count = 0, $options = array()) {
		
		$title = '<label class="tw-in-widget-title">' . __('Title') . ': ' 
			. '<input type="text" name="tw[' . $id . '][titles][]" value="'. $options[$id]['titles'][$count - 1] .'" size="19" /></label>';

		return $title;
	}

	function makeSimpleRadio($options, $id, $fieldname, $value, $label = null) {
		
		if ($options[$id][$fieldname] == $value) {
			$checked = 'checked="checked"'; $classname = 'tw-active';
		} else {
			$checked = ''; $classname = 'tw-inactive'; 
		}
		
		$id = '[' . $id . ']';
		$fieldname_out = '[' . $fieldname . ']';
		
		$out = '<label class="' . $classname . ' label-'. $value .' label-'. $fieldname .'"><input type="radio" name="tw'. $id . $fieldname_out . '" value="'. $value .'" '. $checked .' /> ' 
			. $label . '</label>';
			
		return $out;
	}	

	function makeTitleOption($id = 0, $count = 0, $options = array()) {
		$showtitle = $options[$id]['showtitle'];
		
		if (!empty($showtitle)) {
			$value = 1; $checked = 'checked="checked"';
		} else {
			$value = 0; $checked = '';
		}
		
		$out = '<input type="checkbox" id="tw_showtitle_' . $id . '" name="tw[' . $id . '][showtitle]" '. $checked .' /> ' 
			. '<label for="tw_showtitle_' . $id . '">' . __('show title') . '</label> ';
			
		return $out;
	}


	function makeDefaultRotateOption($options = array()) {
		$rotatetime = $options['default_rotate_time'];
	
		if (is_numeric($rotatetime)) {
			if ($rotatetime < 1) $rotatetime = 1;
			if ($rotatetime > 30) $rotatetime = 30;
		} else {
			$rotatetime = $this->defaultRotateInterval;
		}
		
		$out = '<label>' . __('Default rotate interval (in seconds)') . ': ' 
			. '<input type="text" name="tw[default_rotate_time]" value="'. $rotatetime .'" size="3" /> <small>(' . __('used only when tab rotation is enabled') . ')</small></label>';
			
		return $out;
	}	

	function makeRotateOption($id = 0, $count = 0, $options = array()) {
		$rotate = $options[$id]['rotate'];
		$rotatetime = $options[$id]['rotatetime'];
		
		if (!empty($rotate)) {
			$value = 1; $checked = 'checked="checked"';
			// if (empty($rotatetime)) $rotatetime = $options['default_rotate_time'];
		} else {
			$value = 0; $checked = '';
			$rotatetime = '';
		}
		
		if (is_numeric($rotatetime)) {
			if ($rotatetime < 1) $rotatetime = 1;
			if ($rotatetime > 30) $rotatetime = 30;
		} else {
			$rotatetime = '';
		}
		
		$out = '<p class="inputfields"><input type="checkbox" id="tw_rotate_' . $id . '" name="tw[' . $id . '][rotate]" '. $checked .' /> ' 
			. '<label for="tw_rotate_' . $id . '">' . __('Rotate tabs') . '</label> '
			. '<label>' . __('with interval (in seconds)') . ': ' 
			. '<input type="text" name="tw[' . $id . '][rotatetime]" value="'. $rotatetime .'" size="3" /> </label></p> <span class="info">' . __('(default used, if empty)') . '</span>';
			
		return $out;
	}
	
	
	function makeRandomStartOption($id = 0, $count = 0, $options = array()) {
		$randomstart = $options[$id]['randomstart'];
		
		if (!empty($randomstart)) {
			$value = 1; $checked = 'checked="checked"';
		} else {
			$value = 0; $checked = '';
		}
		
		$out = '<input type="checkbox" id="tw_randomstart_' . $id . '" name="tw[' . $id . '][randomstart]" '. $checked .' /> ' 
			. '<label for="tw_randomstart_' . $id . '">' . __('Choose random start tab') . '</label> ';
			
		return $out;
	}
	
	
	function get_active_widgets() {
		global $wp_registered_sidebars;
		
		$visible_widgets = wp_get_sidebars_widgets();
		$sidebars = array_values($wp_registered_sidebars);
		
			foreach ($this->stored_widgets as $widget_id => $widget_data) {
			
				$widget_name = $widget_data['name'];
				$widget_params = $widget_data['params'];
				$widget_callback = $widget_data['callback'];
				
				if (in_array($widget_id, $visible_widgets) || !in_array($widget_id, $this->donot_list_without_config)) { 
				
					// if parameter is a string
					if (isset($widget_params[0]) && !is_array($widget_params[0])) {
						$widget_params = $widget_params[0];
					}
					
					$sidebar_params = $sidebars[0];
					$sidebar_params['before_title'] = '[[';
					$sidebar_params['after_title'] = ']]';
					
					$all_params = array_merge(array($sidebar_params), (array)$widget_params);					
					
					if (is_callable($widget_callback) && strpos($widget_id, 'wl-clone') === false && !empty($widget_name)) {
					
						ob_start();
							call_user_func_array($widget_callback, $all_params);
						$widget_title = ob_get_clean();
						
						$find_fn_pattern = '/\[\[(.*?)\]\]/';
						preg_match_all($find_fn_pattern, $widget_title, $result);
						
						$got_title = strip_tags(trim((string)$result[1][0]));
						
						if (!empty($got_title) && $got_title !== '') {
							$widget_title = $widget_name . ': ' . $got_title;
						} else {
							$widget_title = $widget_name;
						}
						
					} else {
						$widget_title = $widget_name;
					}
				
					$out[$widget_id] = $widget_title;
				}
			}

		return $out;
	}

}

$root = dirname(dirname(dirname(dirname(__FILE__))));

if (file_exists($root . '/wp-load.php')) {
	require_once($root . '/wp-load.php'); // WP 2.6
} else {
	require_once($root . '/wp-config.php'); // Before 2.6
}

if (isset($_GET['returnjs'])) {
	new tabbedWidgets($printjsvars = true);
} else {
	$twidgets = new tabbedWidgets();
}

?>