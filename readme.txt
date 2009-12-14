=== Tabbed Widgets ===
Contributors: kasparsd
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=kaspars%40konstruktors%2ecom&item_name=Tabbed%20Widgets%20Plugin%20for%20WordPress&no_shipping=1&no_note=1&tax=0&currency_code=EUR&lc=LV&bn=PP%2dDonationsBF&charset=UTF%2d8
Tags: widget, widgets, tabs, tabbed widgets, accordion, sidebar, ui
Requires at least: 2.8
Tested up to: 2.9
Stable tag: 0.83

Create tab and accordion type widgets without writing a single line of code.

== Description ==

Tabbed interfaces are the most common on newspaper type website where they can save a 
lot of vertical space and make it look less cluttered. Accordion type tabs are 
particularly useful if you want to have longer tab titles or more tabs that 
wouldn't otherwise fit into the given horizontal width.

= Tabbed Widget features: =

*	Use other widgets for the tab content and specify a custom tab title.
*	Make tabs rotate in a set interval so that they become more noticeable and prominent.
*	Set a random start tab on each page load so that all tabbed content gets equal exposure.
*	Make unlimited number of tabbed widgets that can be then used as regular widgets under ‘Design’ › ‘Widgets’.

= Widget Design Customization =

Tabbed widgets created by this plugin have very little CSS applied by default, because every theme is very different.

Therefore, I offer [Tabbed Widget design customization service](http://konstruktors.com/blog/projects-services/wordpress-plugins/tabbed-accordion-widgets/#service).


== Installation ==

1.	Search for "Tabbed Widgets" in ‘Plugins’ › ‘Add New’. Install it.

2.	Under ‘Design’ › ‘Widgets’ drag a new "Tabbed Widget" (from the list of Available Widgets) into a sidebar where you want it to appear.

3.	Widgets that have configuration settings *must* be placed in the 'Invisible Sidebar Area' before they will appear in the drop-down menu.


== Changelog ==

*	**0.83** (Dec 14, 2009) -- Another fix of javascript variables.
*	**0.82**: Fixed empty javascript variables, updated readme.txt and faq.
*	**0.81**: Added support for 2.8+; Updated Javascript, simplified interface.
*	**0.76** and **0.77**: Bug fix: Selected start tab was not opened for accordion type widgets.
*	**0.74**: Fixed active widget list creation error for PHP 4.3.6 users. It turned out that $this->tabbed_widget_content doesn't get passed around from class init to child functions. Could be a WP issue as well.
*	**0.73**: Bug fix: removed the extra ob_end_clean which was clearing the widget titles from the settings page drop-down selection.
*	**0.72**: Bug fix: recursion error for PHP 5.2 users due to non-strict `==` comparison in `wp-includes/widgets.php` line 266. Stripped self from the array of active widgets.
*	**0.71**: Bug fix: content under 'Edit' section of dashboard was disappearing due to widget titles being ob_started too early.
*	**0.7**: New feature: choose any start tab. Improved widget drop-down selection with exact widget titles. Added an invisible sidebar (widgetized area) for placing and configuring widgets that are going to be used inside the tabbed widgets. Adding automatic rotation stop also for regulat type tabs.
*	**0.2**: New feature: if a user clicks on a link inside a tab, that tab will be automatically set to open on the next page load. This is a significant usability improvement.
*	**0.1x**: Various bug fixes.
*	**0.1**: Initial public release.


== Frequently Asked Questions ==

Post your questions in [WordPress support forum](http://wordpress.org/tags/tabbed-widgets?forum_id=10).

= Widget X doesn't appear in the drop-down selection =

It is most likely that the widget must be configured before it can be used -- 
place it in the 'Invisible Sidebar Area', refresh the Widget 
admin page and it should appear in the drop-down selection.

== Screenshots ==

1. Widget output using default theme
2. Widget settings
