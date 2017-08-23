<div id="help-content" class="generic-content-wrapper">
	<div class="section-title-wrapper">
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
