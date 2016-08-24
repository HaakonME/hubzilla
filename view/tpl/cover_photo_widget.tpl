<script>
	var aside_padding_top;
	var section_padding_top;
	var coverSlid = false;

	$(document).ready(function() {

		$('body').on('click',slideUpCover);

		aside_padding_top = parseInt($('aside').css('padding-top'));
		section_padding_top = parseInt($('section').css('padding-top'));

		if($('#cover-photo').length && $(window).width() > 755) {
			$('.navbar-fixed-top').css('position', 'relative');
			$('main').css('margin-top', - $('nav').outerHeight(true) + 'px');
			$('aside').css('padding-top', aside_padding_top - $('nav').outerHeight() + 'px');
			$('section').css('padding-top', section_padding_top  - $('nav').outerHeight() + 'px');
			$('main').css('opacity', 0.5);
			$('header').hide();
		}
		else {
			$('#cover-photo').remove();
		}
	});

	$(window).scroll(function () {
		if((! coverSlid) && $('#cover-photo').length && $(window).width() > 755 && $(window).scrollTop() >= $('#cover-photo').height()) {
			$('header').fadeIn();
			$('main').css('opacity', 1);
			$('aside').css('padding-top', aside_padding_top + 'px');
			$('section').css('padding-top', section_padding_top + 'px');
			$(window).scrollTop($(window).scrollTop() - $('#cover-photo').height())
			$('.navbar-fixed-top').css({ 'position' : 'fixed', 'top' : 0});
			$('main').css('margin-top', '');
			coverSlid = true;
		}
		if($('#cover-photo').length) {
			$('main').css('opacity', ($(window).scrollTop()/$('#cover-photo').height()).toFixed(1));
		}
	});

	$(window).resize(function () {
		if($('#cover-photo').length && $(window).width() < 755) {
			$('main').css('opacity', 1);
			$('aside').css('padding-top', aside_padding_top + $('nav').outerHeight() + 20 + 'px');
			$('section').css('padding-top', section_padding_top  + $('nav').outerHeight() + 20 + 'px');
			$('.navbar-fixed-top').css({ 'position' : 'fixed', 'top' : 0 });
			$('#cover-photo').remove();
		}

	});

	function slideUpCover() {
		if(coverSlid)
			return;
		$('html, body').animate({scrollTop: Math.ceil($('#cover-photo').height()) + 'px' });
		$('#cover-photo').css({ 'position' : 'relative' , 'top' : 0 });
		$('.navbar-fixed-top').css({ 'position' : 'fixed', 'top' : 0});
		$('aside').css('padding-top', aside_padding_top + 'px');
		$('section').css('padding-top', section_padding_top + 'px');
		$('main').css('margin-top', '');
		coverSlid = true;
	}
</script>

<div id="cover-photo" title="{{$hovertitle}}">
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
