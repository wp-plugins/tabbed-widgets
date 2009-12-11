$wicon = jQuery.noConflict();

$wicon(document).ready(function() {
	var $bg_color = $wicon('.widget-list-control-item').css('background-color');
	$wicon('.widget-context').css({backgroundColor:$bg_color});
});