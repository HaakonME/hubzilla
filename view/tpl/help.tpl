<div id="help-content" class="generic-content-wrapper">
	<div class="section-title-wrapper" style="display: none;">
	<h2>{{$title}}</h2>
	</div>
	<div class="section-content-wrapper" id="doco-content">
	<h1>Contents</h1>
	<ul id="doco-top-toc"></ul>
	<hr>
	{{$content}}
	</div>
</div>

<script>

	// Generate the table of contents in the side nav menu (see view/tpl/help.tpl)
	$(document).ready(function () {

		$('#doco-top-toc').toc({content: "#doco-content", headings: "h1,h2,h3,h4"});

	});

</script>