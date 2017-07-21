{{if ! ($navapps || $order)}}
<div class="app-container">
	<div class="app-detail{{if $deleted}} app-deleted{{/if}}">
		<a href="{{$app.url}}"{{if $app.target}} target="{{$app.target}}"{{/if}}{{if $app.desc}} title="{{$app.desc}}{{if $app.price}} ({{$app.price}}){{/if}}"{{else}}title="{{$app.name}}"{{/if}}>{{if $icon}}<i class="app-icon fa fa-fw fa-{{$icon}}"></i>{{else}}<img src="{{$app.photo}}" width="80" height="80" />{{/if}}
			<div class="app-name" style="text-align:center;">{{$app.name}}</div>
		</a>
	</div>
	{{if $app.type !== 'system'}}
	{{if $purchase}}
	<div class="app-purchase">
		<a href="{{$app.page}}" class="btn btn-outline-secondary" title="{{$purchase}}" ><i class="fa fa-external"></i></a>
	</div>
	{{/if}}
	{{if $install || $update || $delete || $feature}}
	<div class="app-tools">
		<form action="{{$hosturl}}appman" method="post">
		<input type="hidden" name="papp" value="{{$app.papp}}" />
		{{if $install}}<button type="submit" name="install" value="{{$install}}" class="btn btn-outline-secondary btn-sm" title="{{$install}}" ><i class="fa fa-arrow-circle-o-down" ></i></button>{{/if}}
		{{if $edit}}<input type="hidden" name="appid" value="{{$app.guid}}" /><button type="submit" name="edit" value="{{$edit}}" class="btn btn-outline-secondary btn-sm" title="{{$edit}}" ><i class="fa fa-pencil" ></i></button>{{/if}}
		{{if $delete}}<button type="submit" name="delete" value="{{if $deleted}}{{$undelete}}{{else}}{{$delete}}{{/if}}" class="btn btn-outline-secondary btn-sm" title="{{if $deleted}}{{$undelete}}{{else}}{{$delete}}{{/if}}" ><i class="fa fa-trash-o drop-icons"></i></button>{{/if}}
		{{if $feature}}<button type="submit" name="feature" value="feature" class="btn btn-outline-secondary btn-sm" title="{{if $featured}}{{$remove}}{{else}}{{$add}}{{/if}}"><i class="fa fa-star"{{if $featured}} style="color: gold"{{/if}}></i></button>{{/if}}
		</form>
	</div>
	{{/if}}
	{{/if}}
</div>
{{/if}}
{{if $navapps}}
<a class="dropdown-item{{if $app.active}} active{{/if}}" href="{{$app.url}}">{{if $icon}}<i class="generic-icons-nav fa fa-fw fa-{{$icon}}"></i>{{else}}<img src="{{$app.photo}}" width="16" height="16" style="margin-right:9px;"/>{{/if}}{{$app.name}}</a>
{{/if}}
{{if $order}}
<a href="{{$hosturl}}appman/{{$app.guid}}/moveup" class="btn btn-outline-secondary btn-sm" style="margin-bottom: 5px;"><i class="generic-icons-nav fa fa-fw fa-arrow-up"></i></a>
<a href="{{$hosturl}}appman/{{$app.guid}}/movedown" class="btn btn-outline-secondary btn-sm" style="margin-bottom: 5px;"><i class="generic-icons-nav fa fa-fw fa-arrow-down"></i></a>
{{if $icon}}<i class="generic-icons-nav fa fa-fw fa-{{$icon}}"></i>{{else}}<img src="{{$app.photo}}" width="16" height="16" style="margin-right:9px;"/>{{/if}}{{$app.name}}<br>
{{/if}}

