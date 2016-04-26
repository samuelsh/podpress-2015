
(function( $ ) {
	'use strict';
	$(window).load( function() {
		$('span[id*="_PlayLink"]').parent().click(function(){ 
			  // getting id of first childern of parent node
			  var name = jQuery(this).children().first().attr('id');
			  // this is the way to match only digits in any text string
			  var div_id = name.match(/\d+/g);
			  // getting href attribute from <a> tag
			  var lesson_link = jQuery(this).attr('href');
			  // popups html5 player 
			  podPressShowHidePlayer(div_id,lesson_link,320,267,'true'); 
			  return false; 
			  });
	});
}) (jQuery);
