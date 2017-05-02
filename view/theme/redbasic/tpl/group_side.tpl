<div class="widget" id="group-sidebar">
	<h3>{{$title}}</h3>
	<div>
		<ul class="nav nav-pills flex-column">
			{{foreach $groups as $group}}
			<li class="nav-item nav-item-hack">
				{{if $group.cid}}
				<i id="group-{{$group.id}}" class="widget-nav-pills-checkbox fa fa-fw {{if $group.ismember}}fa-check-square-o{{else}}fa-square-o{{/if}}" onclick="contactgroupChangeMember('{{$group.id}}','{{$group.enc_cid}}'); return true;"></i>
				{{/if}}
				{{if $group.edit}}
				<a href="{{$group.edit.href}}" class="nav-link{{if $group.selected}} active{{/if}} widget-nav-pills-icons" title="{{$edittext}}"><i class="fa fa-fw fa-pencil"></i></a>
				{{/if}}
				<a class="nav-link{{if $group.selected}} active{{/if}}" href="{{$group.href}}">{{$group.text}}</a>
			</li>
			{{/foreach}}
			<li class="nav-item">
				<a class="nav-link" href="group/new" title="{{$createtext}}" ><i class="fa fa-plus-circle"></i> {{$createtext}}</a>
			</li>
		</ul>

	</div>
</div>




