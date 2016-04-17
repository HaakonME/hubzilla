/**
 * redbasic theme specific JavaScript
 */
$(document).ready(function() {

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

function makeFullScreen(full) {
	if(typeof full=='undefined' || full == true) {
		$('main').css({'transition': 'none'}).addClass('fullscreen');
		$('#fullscreen-btn, header, nav, aside').css({'display': 'none'});
		$('#inline-btn').show();

	}
	else {
		$('main').removeClass('fullscreen');
		$('#fullscreen-btn, header, nav, aside').css({'display': ''});
		$('#inline-btn').hide();
		$('main').css({'transition': ''});
	}
}

/* contextual help */
$('.help-content').css('top', '-' + $('#help-content').height() + 'px')
$(document).mouseup(function (e)
{
  e.preventDefault;

  var container = $("#help-content");

  if ((!container.is(e.target) // if the target of the click isn't the container...
          && container.has(e.target).length === 0 // ... nor a descendant of the container
          && container.hasClass('help-content-open'))
          ||
          (
                  ($('#help_nav_btn').is(e.target) || $('#help_nav_btn').has(e.target).length !== 0)
                  && container.hasClass('help-content-open')
                  )) {
    container.removeClass('help-content-open');
    $('main').removeClass('help-content-open');
    $('main').css('top', 'auto')
  }
  else if (($('#help_nav_btn').is(e.target) || $('#help_nav_btn').has(e.target).length !== 0)
          && !container.hasClass('help-content-open')) {
    $('#help-content').addClass('help-content-open');
    $('main').removeClass('help-content-open');
    var mainTop = $('#navbar-collapse-1').height();
    if ($('#navbar-collapse-1').height() < $('#help-content').height()) {
      mainTop = $('#help-content').height();
    }

    $('main').css('top', +mainTop + +50 + 'px');
  }

});

var contextualHelpFocus = function (target, openSidePanel) {
  if (openSidePanel) {
    $("main").addClass('region_1-on');  // Open the side panel to highlight element 
  } else {
    $("main").removeClass('region_1-on');
  }
  // Animate the page scroll to the element and then pulse the element to direct attention
  $('html,body').animate({scrollTop: $(target).offset().top - $('#navbar-collapse-1').height() - $('#help-content').height() - 50}, 'slow');
  for (i = 0; i < 3; i++) {
    $(target).fadeTo('slow', 0.1).fadeTo('slow', 1.0);
  }
}