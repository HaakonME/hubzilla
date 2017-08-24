function docoTocToggle() {
	if ($('#doco-top-toc').is(':visible')) {
		$('#doco-toc-toggle').removeClass('fa-cog').addClass('fa-caret-right');
	} else {
		$('#doco-toc-toggle').removeClass('fa-caret-right').addClass('fa-caret-down');
	}
	$('#doco-top-toc').toggle();

	return false;
}

toc = {};
// Generate the table of contents in the side nav menu (see view/tpl/help.tpl)
$(document).ready(function () {
	// Generate the table of contents in the side nav menu (see view/tpl/help.tpl)
	$('#doco-top-toc').toc({content: "#doco-content", headings: "h3,h4,h5,h6"});
	
	$(".doco-section").find('a').each(function () {
		var url = document.createElement('a');
		url.href = window.location;
		var pageName = url.href.split('/').pop().split('#').shift().split('?').shift();
		var linkName = $(this).attr('href').split('/').pop();
		if (pageName === linkName) {
			var tocUl = $(this).closest('a').append('<ul>').find('ul');
			tocUl.removeClass();  // Classes are automatically added to <ul> elements by something else
			tocUl.toc({content: "#doco-content", headings: "h3"});
			tocUl.addClass('toc-content');
			tocUl.addClass('list-unstyled');
			tocUl.attr('id', 'doco-side-toc');

		}
	});

	$(document.body).trigger("sticky_kit:recalc");

	toc.contentTop = [];
	toc.edgeMargin = 20;   // margin above the top or margin from the end of the page
	toc.topRange = 200;  // measure from the top of the viewport to X pixels down
	// Set up content an array of locations
	$('#doco-side-toc').find('a').each(function () {
		toc.contentTop.push($('#' + $(this).attr('href').split('#').pop()).offset().top);
	});


	// adjust side menu
	$(window).scroll(function () {
		var winTop = $(window).scrollTop(),
				bodyHt = $(document).height(),
				vpHt = $(window).height() + toc.edgeMargin;  // viewport height + margin
		$.each(toc.contentTop, function (i, loc) {
			if ((loc > winTop - toc.edgeMargin && (loc < winTop + toc.topRange || (winTop + vpHt) >= bodyHt))) {
				$('#doco-side-toc li')
						.removeClass('selected-doco-nav')
						.eq(i).addClass('selected-doco-nav');
				if (typeof ($('#doco-side-toc li').eq(i).find('a').attr('href').split('#')[1]) !== 'undefined') {
					window.history.pushState({}, '', location.href.split('#')[0] + '#' + $('#doco-side-toc li').eq(i).find('a').attr('href').split('#')[1]);
				}
			}
		});
	});

	// When the page loads, it does not scroll to the section specified in the URL because it
	// has not been constructed yet by the script. This will reload the URL
	if (typeof (location.href.split('#')[1]) !== 'undefined') {
		var p = document.createElement('a');
		p.href = location.href;
		var portstr = '';
		if (p.port !== '') {
			portstr = ':' + p.port;
		}
		var newref = p.protocol + '//' + p.hostname + portstr + p.pathname + p.hash.split('?').shift();
		location.replace(newref)
	}
});
