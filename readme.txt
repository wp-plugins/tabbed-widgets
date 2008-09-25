=== Tabbed Widgets ===
Contributors: kasparsd
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=kaspars%40konstruktors%2ecom&item_name=Tabbed%20Widgets%20Plugin%20for%20WordPress&no_shipping=1&no_note=1&tax=0&currency_code=EUR&lc=LV&bn=PP%2dDonationsBF&charset=UTF%2d8
Tags: widget, widgets, tabs, tabbed widgets, accordion, sidebar, ui
Requires at least: 2.5
Tested up to: 2.6.1
Stable tag: 0.77

Put widgets into tabbed or accordion interface without writing a single line of code. 

== Description ==

See a live demo at [Konstruktors Notes](http://konstruktors.com/blog/ "Writings about the Web, design and development").

Tabbed interfaces are the most common on newspaper type website where they can save a 
lot of vertical space and make it look less cluttered.

Accordion type tabs were made popular by Apple, who use them for grouping different 
types of content in a very narrow sidebar. They are particularly useful if you want 
to have longer tab titles or more tabs that would not otherwise fit into the given 
horizontal size.

= Tabbed Widget features: =

*	Use other widgets for the tab content and specify a custom tab title.
*	Make tabs rotate in a set interval so that they become more noticeable and prominent.
*	Set a random start tab on each page load so that all tabbed content gets equal exposure.
*	Make unlimited number of tabbed widgets that can be then used as regular widgets under ‘Design’ > ‘Widgets’.

== Installation ==

1.    Download the plugin and unzip its content.

2.    Upload the `tabbed-widgets` directory to `/wp-content/plugins/` directory. The
final directory tree should look like `/wp-content/plugins/tabbed-widgets/tabbed-widgets.php`

3.    Activate the plugin at the Plugin Management section under ‘Plugins’ menu.

4.    To create a tabbed widget, go to ‘Design’ > ‘Tabbed Widgets’.


== Other Notes ==

= Changelog =

*	**0.76** and **0.77**: Bug fix: Selected start tab was not opened for accordion type widgets.
*	**0.74**: Fixed active widget list creation error for PHP 4.3.6 users. It turned out that $this->tabbed_widget_content doesn't get passed around from class init to child functions. Could be a WP issue as well.
*	**0.73**: Bug fix: removed the extra ob_end_clean which was clearing the widget titles from the settings page drop-down selection.
*	**0.72**: Bug fix: recursion error for PHP 5.2 users due to non-strict `==` comparison in `wp-includes/widgets.php` line 266. Stripped self from the array of active widgets.
*	**0.71**: Bug fix: content under 'Edit' section of dashboard was disappearing due to widget titles being ob_started too early.
*	**0.7**: New feature: choose any start tab. Improved widget drop-down selection with exact widget titles. Added an invisible sidebar (widgetized area) for placing and configuring widgets that are going to be used inside the tabbed widgets. Adding automatic rotation stop also for regulat type tabs.
*	**0.2**: New feature: if a user clicks on a link inside a tab, that tab will be automatically set to open on the next page load. This is a significant usability improvement.
*	**0.1x**: Various bug fixes
*	**0.1**: Initial public release.


== Frequently Asked Questions ==

= How to change the default font/color/size of tabs or tab content? =

Overwrite the default CSS rules (see `js/uitabs.css`) at the end of your theme's `style.css`.

== Screenshots ==

1. Tabbed Widget Settings
2. Tab style widget
3. Accordion style widget
