<div id="help-content" class="generic-content-wrapper">
	<div class="clearfix section-title-wrapper">
		<div class="pull-right">
			<div class="btn-group">
				<button type="button" class="btn btn-outline-secondary btn-sm dropdown-toggle" data-toggle="dropdown">
					<i class="fa fa-language" style="font-size: 1.4em;"></i>
				</button>
				<div class="dropdown-menu dropdown-menu-right flex-column lang-selector">
					<a class="dropdown-item lang-choice" href="/help">de</a>
					<a class="dropdown-item lang-choice" href="/help">en</a>
					<a class="dropdown-item lang-choice" href="/help">es</a>
				</div>
			</div>
		</div>
		<h2>{{$title}}: {{$heading}}</h2>
	</div>
	<div class="section-content-wrapper" id="doco-content">
		<h3 id="doco-top-toc-heading">
			<span class="fakelink" onclick="docoTocToggle(); return false;">
				<i class="fa fa-fw fa-caret-right fakelink" id="doco-toc-toggle"></i>
				{{$tocHeading}}
			</span>
		</h3>
		<ul id="doco-top-toc" style="margin-bottom: 1.5em; display: none;"></ul>
		{{$content}}
	</div>
</div>
<script>
	var help_language = '{{$language}}'
</script>
