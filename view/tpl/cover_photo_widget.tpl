<script>
	var aside_padding_top;
	var section_padding_top;
	var coverSlid = false;

	$(document).ready(function() {

		aside_padding_top = parseInt($('aside').css('padding-top'));
		section_padding_top = parseInt($('section').css('padding-top'));

		$('#cover-photo').on('click', slideUpCover);
		$('#cover-photo').on('keyup', slideUpCover);

		if($('#cover-photo').length && $(window).width() > 755) {
			if($(window).scrollTop() < $('#cover-photo').height()) {
				$('.navbar').removeClass('fixed-top');
				$('main').css('margin-top', - $('nav').outerHeight(true) + 'px');
				$('main').css('opacity', 0);
			}
		}
		else {
			$('#cover-photo').remove();
			coverSlid = true;
		}
	});

	$(window).scroll(function () {
		if($('#cover-photo').length && $(window).width() > 755 && $(window).scrollTop() >= $('#cover-photo').height()) {
			$('.navbar').addClass('fixed-top');
			$('main').css('margin-top', '');
			$('main').css('opacity', 1);
			coverSlid = true;
		}
		else if ($('#cover-photo').length && $(window).width() > 755 && $(window).scrollTop() < $('#cover-photo').height()){
			if(coverSlid) {
				$(window).scrollTop(Math.ceil($('#cover-photo').height()));
				setTimeout(function(){ coverSlid = false; }, 1000);
			}
			else {
				if($(window).scrollTop() < $('#cover-photo').height()) {
					$('.navbar').removeClass('fixed-top');
					$('main').css('margin-top', - $('nav').outerHeight(true) + 'px');
					$('main').css('opacity', 0);
				}
			}
		}
		if($('#cover-photo').length && $('main').css('opacity') < 1) {
			$('main').css('opacity', ($(window).scrollTop()/$('#cover-photo').height()).toFixed(1));
		}
	});

	$(window).resize(function () {
		if($('#cover-photo').length && $(window).width() < 755) {
			$('#cover-photo').remove();
			$('.navbar').addClass('fixed-top');
			$('main').css('opacity', 1);
			coverSlid = true;
		}

	});

	function slideUpCover() {
		if(coverSlid) {
			return;
		}
		$('html, body').animate({scrollTop: Math.ceil($('#cover-photo').height()) + 'px' });
		return;
	}
</script>

<div id="cover-photo" title="{{$hovertitle}}">
	{{$photo_html}}
	<div id="cover-photo-caption">
		<h1>{{$title}}</h1>
		<h3>{{$subtitle}}</h3>
	</div>
</div>
