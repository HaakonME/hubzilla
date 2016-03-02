/**
 * redbasic theme specific JavaScript
 */
$(document).ready(function() {

	//Simple cover-photo implementation
	if($('#cover-photo').length && $(window).width() > 767) {
		$('.navbar-fixed-top').css('position', 'relative');
		$('aside, section').css('padding-top', 0 + 'px');
		$('main').css('opacity', 0);
	}
	else {
		$('#cover-photo').remove();
	}

	// CSS3 calc() fallback (for unsupported browsers)
	$('body').append('<div id="css3-calc" style="width: 10px; width: calc(10px + 10px); display: none;"></div>');
	if( $('#css3-calc').width() == 10) {
		$(window).resize(function() {
			if($(window).width() < 767) {
				$('main').css('width', $(window).width() + 287 );
			} else {
				$('main').css('width', '100%' );
			}
		});
	}
	$('#css3-calc').remove(); // Remove the test element

	$('#expand-aside').click(function() {
		$('#expand-aside-icon').toggleClass('icon-circle-arrow-right').toggleClass('icon-circle-arrow-left');
		$('main').toggleClass('region_1-on');
	});

	if($('aside').length && $('aside').html().length === 0) {
		$('#expand-aside').hide();
	}

	$('#expand-tabs').click(function() {
		if(!$('#tabs-collapse-1').hasClass('in')){
			$('html, body').animate({ scrollTop: 0 }, 'slow');
		}
		$('#expand-tabs-icon').toggleClass('icon-circle-arrow-down').toggleClass('icon-circle-arrow-up');
	});

	if($('#tabs-collapse-1').length === 0) {
		$('#expand-tabs').hide();
	}

	$("input[data-role=cat-tagsinput]").tagsinput({
		tagClass: 'label label-primary'
	});

	var doctitle = document.title;
	function checkNotify() {
		var notifyUpdateElem = document.getElementById('notify-update');
		if(notifyUpdateElem !== null) { 
			if(notifyUpdateElem.innerHTML !== "")
				document.title = "(" + notifyUpdateElem.innerHTML + ") " + doctitle;
			else
				document.title = doctitle;
		}
	}
	setInterval(function () {checkNotify();}, 10 * 1000);
});

//Simple cover-photo implementation
$(window).scroll(function () {
	if($('#cover-photo').length && $(window).width() > 767 && $(window).scrollTop() >= $('#cover-photo').height()) {
		$('aside, section').css('padding-top', 71 + 'px');
		$(window).scrollTop($(window).scrollTop() - $('#cover-photo').height())
		$('.navbar-fixed-top').css('position', 'fixed');
		$('#cover-photo').remove();
	}

	if($('#cover-photo').length) {
		$('main').css('opacity', ($(window).scrollTop()/$('#cover-photo').height()).toFixed(1));
	}
});
