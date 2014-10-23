var initResize = function(basicFontSize, ratioLogo, heightHeader) {
	// Font-size
	var actualWidth = parseInt($(this).width());
	actualWidth = actualWidth < 550 ? 550 : actualWidth;
	var newFontSize = actualWidth * basicFontSize / 1920;
	$('html').css('fontSize', newFontSize + 'px');

	// container height
	var bodyHeight = $('body').height();
	var headerHeight = $('body > header').height() + parseInt($('body > header').css('paddingTop')) + parseInt($('body > header').css('paddingBottom'));
	$('section.center').height(bodyHeight - headerHeight - 10);
}

$(document).ready(function() {
	var basicFontSize = parseInt($('html').css('fontSize'));
	var ratioLogo = $('#logo img').width() / $('#logo img').height();
	var heightHeader = $('body > header').height();
	initResize(basicFontSize, ratioLogo, heightHeader);
	$( window ).resize(function() { initResize(basicFontSize, ratioLogo, heightHeader); });

	$('body > section.center .container').perfectScrollbar({suppressScrollX: true});


	$('#lang > img').click(function() {
		$('#lang > div').toggle();
	});
});