{{if $not_refresh}}<div id="wiki_page_list_container" {{if $hide}} style="display: none;" {{/if}}>{{/if}}
<div id="wiki_page_list" class="widget" >
	<h3>{{$header}}</h3>
	<ul class="nav nav-pills nav-stacked">
		{{if $pages}}
		{{foreach $pages as $page}}
		<li><a href="/wiki/{{$channel}}/{{$wikiname}}/{{$page.url}}">{{$page.title}}</a></li>
		{{/foreach}}
		{{/if}}
		{{if $canadd}}
		<li><a href="#" onclick="wiki_show_new_page_form(); return false;"><i class="fa fa-plus-circle"></i>&nbsp;{{$addnew}}</a></li>
		{{/if}}
	</ul>
	{{if $canadd}}
	<div id="new-page-form-wrapper" class="sub-menu" style="display:none;">
		<form id="new-page-form" action="wiki/{{$channel}}/create/page" method="post" >
			{{include file="field_input.tpl" field=$pageName}}
			<button id="new-page-submit" class="btn btn-primary" type="submit" name="submit" >Submit</button>
		</form>
	</div>
	{{/if}}
</div>
{{if $not_refresh}}</div>{{/if}}

<script>
	$('#new-page-submit').click(function (ev) {
		if (window.wiki_resource_id === '') {
			window.console.log('You must have a wiki open in order to create pages.');
			ev.preventDefault();
			return false;
		}
		$.post("wiki/{{$channel}}/create/page", {name: $('#id_pageName').val(), resource_id: window.wiki_resource_id}, 
		function (data) {
			if (data.success) {
			window.location = data.url;
			} else {
			window.console.log('Error creating page.');
			}
		}, 'json');
		ev.preventDefault();
	});
</script>
