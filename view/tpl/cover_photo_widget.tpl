<script>
	var aside_padding_top;
	var section_padding_top;

	$(document).ready(function() {

		aside_padding_top = parseInt($('aside').css('padding-top'));
		section_padding_top = parseInt($('section').css('padding-top'));

		if($('#cover-photo').length && $(window).width() > 755) {
			$('.navbar-fixed-top').css('position', 'relative');
			$('main').css('margin-top', - $('nav').outerHeight(true) + 'px');
			$('aside').css('padding-top', aside_padding_top - $('nav').outerHeight() + 'px');
			$('section').css('padding-top', section_padding_top  - $('nav').outerHeight() + 'px');
			$('main').css('opacity', 0);
			$('header').hide();
		}
		else {
			$('#cover-photo').remove();
		}
	});

	$(window).scroll(function () {
		if($('#cover-photo').length && $(window).width() > 755 && $(window).scrollTop() >= $('#cover-photo').height()) {
			$('header').fadeIn();
			$('main').css('opacity', 1);
			$('aside').css('padding-top', aside_padding_top + 'px');
			$('section').css('padding-top', section_padding_top + 'px');
			$(window).scrollTop($(window).scrollTop() - $('#cover-photo').height())
			$('.navbar-fixed-top').css('position', 'fixed');
			$('main').css('margin-top', '');
			$('#cover-photo').remove();
		}
		if($('#cover-photo').length) {
			$('main').css('opacity', ($(window).scrollTop()/$('#cover-photo').height()).toFixed(1));
		}
	});

	$(window).resize(function () {
		if($('#cover-photo').length && $(window).width() < 755) {
			$('main').css('opacity', 1);
			$('aside').css('padding-top', aside_padding_top + 'px');
			$('section').css('padding-top', section_padding_top + 'px');
			$('.navbar-fixed-top').css('position', 'fixed');
			$('#cover-photo').remove();
		}

	});

	function slideUpCover() {
		$('html, body').animate({scrollTop: Math.ceil($('#cover-photo').height()) + 'px' });
	}
</script>

<div id="cover-photo" onclick="slideUpCover();" title="{{$hovertitle}}">
	{{$photo_html}}
	<div id="cover-photo-caption">
		<div class="cover-photo-title">
			{{$title}}
		</div>
		<div class="cover-photo-subtitle">
			{{$subtitle}}
		</div>
	</div>
</div>
