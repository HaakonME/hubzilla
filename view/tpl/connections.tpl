<div class="generic-content-wrapper">
	<div class="section-title-wrapper clearfix">
		<div class="dropdown pull-right">
			<button type="button" class="btn btn-primary btn-sm" onclick="openClose('contacts-search-form'); $('#contacts-search').focus()">
				<i class="fa fa-search"></i>&nbsp;{{$label}}
			</button>
			<button type="button" class="btn btn-outline-secondary btn-sm dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" title="{{$sort}}">
				<i class="fa fa-sort"></i>
			</button>
			<div class="dropdown-menu dropdown-menu-right">
				{{foreach $tabs as $menu}}
				<a class="dropdown-item" href="{{$menu.url}}">{{$menu.label}}</a>
				{{/foreach}}
			</div>
		</div>
		{{if $finding}}<h2>{{$finding}}</h2>{{else}}<h2>{{$header}}{{if $total}} ({{$total}}){{/if}}</h2>{{/if}}
	</div>
	<div id="contacts-search-form" class="section-content-tools-wrapper">
		<form action="{{$cmd}}" method="get" >
			<div class="input-group form-group">
				<input type="text" name="search" id="contacts-search" class="form-control" onfocus="this.select();" value="{{$search}}" placeholder="{{$desc}}" />
				<div class="input-group-btn">
					<button id="contacts-search-submit" class="btn btn-outline-secondary" type="submit" name="submit" value="{{$submit}}"><i class="fa fa-fw fa-search"></i></button>
				</div>
			</div>
		</form>
	</div>
	<div id="connections-wrapper clearfix">
		{{foreach $contacts as $contact}}
			{{include file="connection_template.tpl"}}
		{{/foreach}}
		<div id="page-end"></div>
	</div>
</div>
<script>$(document).ready(function() { loadingPage = false;});</script>
<div id="page-spinner" class="spinner-wrapper">
	<div class="spinner m"></div>
</div>
