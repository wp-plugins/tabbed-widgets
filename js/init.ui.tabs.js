
$tw = jQuery.noConflict();
$tw(document).ready(function() {

	var $tab_clicked = tw_readCookie('tab_clicked');
	if ($tab_clicked !== null) {	
		var $tab_clicked_array = $tab_clicked.split("-");
		var $tab_clicked_id = $tab_clicked_array[2];
		var $tab_clicked_id_tab = $tab_clicked_array[3];
	}

	$tw('.tw-tabbed-widgets').each(function() {
		// tabbed-widget-1
		var $widgetid = $tw(this).attr("id");
		var $widgetidparts = $widgetid.split("-", 3);
		
		var $widgetid = $widgetidparts[2];
		var $widgetstyle = $rotateoptions[$widgetid]["style"];
		var $do_rotate = $rotateoptions[$widgetid]["rotate"];
		var $rotate_interval = $rotateoptions[$widgetid]["interval"];
		var $random_start = $rotateoptions[$widgetid]["randomstart"];
		var $start_tab = $rotateoptions[$widgetid]["start"];
			
		if ($widgetstyle == 'tabs') {
			
			$tw(this).addClass('acc');
			
			var $maxheight = 0;
			/* $tw(this).find('.tw-rotate div').each(function() {
				if ($tw(this).height() > $maxheight) { $maxheight = $tw(this).height(); }
			}); */
			
			var $panelcount = $tw('ul.tw-nav-list li', this).length;
			var $randno = Math.floor($panelcount * Math.random());
			
			if ($start_tab > 0) {
				var $set_start_tab = $start_tab - 1;
			} else {
				var $set_start_tab = 0;
			}
			
			if ($random_start) {
				var $set_start_tab = $randno;
			}
			
			// if the clicked on content inside tab then open the same tab now.
			if ($tab_clicked !== null && $widgetid == $tab_clicked_id) {
				$set_start_tab = parseInt($tab_clicked_id_tab);
			}
			
			if ($do_rotate) {
				if ($rotate_interval > 1) {
					var $set_interval = $rotate_interval * 1000;
				} else {
					var $set_interval = 7000;
				}				
			} else {
				var $set_interval = 0;
			}
			
			var $horizontal_tabs = $tw(this).find('.tw-rotate .tw-nav-list').tabs({ 
					selected: $set_start_tab, 
					cache:true, 
					fx: { opacity: 'toggle', duration: 'fast' }
			}).tabs('rotate', $set_interval);
			
			$tw(this).removeClass('acc');
			
			// If hovered, stop rotation
			$tw(this).find('.tw-rotate .tabbed-widget-item').bind("mouseenter", function(){
					$horizontal_tabs.tabs('rotate', null);
			});
			
			if ($tw_rounded_corners) {
				if ($tw.browser['safari']) $tw('ul.tw-nav-list a').cornerz({radius:3, corners:"tr tl br"});
					else if (($tw.browser['msie'])) $tw('ul.tw-nav-list a').cornerz({radius:6, corners:"tr tl br"});
						else $tw('ul.tw-nav-list a').cornerz({radius:4, corners:"tr tl br"});
			}
			
		} else if ($widgetstyle == 'accordion') {
			
			$tw('.tw-widgettitle:first', this).addClass('tw-first');
			$tw('.tw-widgettitle:last', this).addClass('tw-last');
			$tw('.tabbed-widget-item:first', this).addClass('tw-item-first');
			$tw('.tabbed-widget-item:last', this).addClass('tw-item-last');
			
			var $this_acco = $tw(this);
			var $acco = $this_acco.accordion({
			    header: '.tw-widgettitle',
				animated: 'easeslide',
				autoheight: false,
				active: false
			});
			
			// count the number of tabs
			var $tabs = $tw('.tw-widgettitle', $this_acco).length;
			
			// choose start tab, see if random is selected
			if ($random_start) {
				var $set_start_tab = Math.floor($tabs * Math.random());
			} else {
				var $set_start_tab = $tabs - 1;
			}

			// if the clicked on content inside tab then open the same tab now.
			if ($tab_clicked !== null && $widgetid == $tab_clicked_id) {
				var $set_start_tab = parseInt($tab_clicked_id_tab);
			}
			
			// activate the start tab
			$acco.activate($set_start_tab);

			if ($do_rotate) {
				
				if ($rotate_interval > 1) {
					var $set_interval = $rotate_interval * 1000;
				} else {
					var $set_interval = 7000;
				}
				
				var $cleared = false;
				var $wasstopped = false;
				
				(function() {
				    var t = $set_start_tab;
					var $step = 0;
					var $saverotation;
					
					function dorotate() {
						t = ++t;
						if (t == $tabs) { $step = -2; t = t + $step;  }
						else if (t == 1) { t = t; $step = 0; }
						else { t = t + $step; }
						$acco.accordion("activate", t);
				    }
					
				    if (!$cleared) var rotation = setInterval(function(){ dorotate(); }, $set_interval);
					
					$tw($this_acco).bind("mouseenter", function(){
						clearInterval(rotation);
						rotation = null;
						$cleared = true;
					}).bind("mouseleave",function(){
						if (!$wasstopped) rotation = setInterval(function(){ dorotate(); }, $set_interval);
					}).bind("click",function(){
						$wasstopped = true;
						clearInterval(rotation); rotation = null;
					});
					
				})();
			}
		}
		
		// Add css identifier to hover item, only because IE doesn't understand h4:hover.
		$tw('.tw-widgettitle').hover(function() {
			$tw(this).addClass('tw-hovered');
		}, function() {
			$tw(this).removeClass('tw-hovered');
		});
		
		// Save the id of the widget user clicked on and use it later to make it a start tab.
		$tw('.tabbed-widget-item').click(function() {
			tw_createCookie('tab_clicked', $tw(this).attr('id'), 1);
		});
		
		// Add rounded corners if option enabled.
		if ($tw_rounded_corners) {
			$tw('.tw-widgettitle').each(function() {
				$tw(this).cornerz({radius:6});
			});
		}
		
	});	
	
});

/* Create, edit, delete cookies. Thanks to http://www.quirksmode.org/js/cookies.html */

function tw_createCookie(name,value,days) {
	if (days) {
		var date = new Date();
		date.setTime(date.getTime()+(days*24*60*60*1000));
		var expires = "; expires="+date.toGMTString();
	}
	else var expires = "";
	document.cookie = name+"="+value+expires+"; path=/";
}

function tw_readCookie(name) {
	var nameEQ = name + "=";
	var ca = document.cookie.split(';');
	for(var i=0;i < ca.length;i++) {
		var c = ca[i];
		while (c.charAt(0)==' ') c = c.substring(1,c.length);
		if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length,c.length);
	}
	return null;
}

function tw_eraseCookie(name) {
	tw_createCookie(name,"",-1);
}