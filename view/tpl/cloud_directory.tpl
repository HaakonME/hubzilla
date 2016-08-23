<div id="cloud-drag-area" class="section-content-wrapper-np">
	<table id="cloud-index">
		<tr>
			<th width="1%"></th>
			<th width="92%">{{$name}}</th>
			<th width="1%"></th><th width="1%"></th><th width="1%"></th><th width="1%"></th>
			<th width="1%">{{*{{$type}}*}}</th>
			<th width="1%" class="hidden-xs">{{$size}}</th>
			<th width="1%" class="hidden-xs">{{$lastmod}}</th>
		</tr>
	{{if $parentpath}}
		<tr>
			<td><i class="fa fa-level-up"></i>{{*{{$parentpath.icon}}*}}</td>
			<td><a href="{{$parentpath.path}}" title="{{$parent}}">..</a></td>
			<td></td><td></td><td></td><td></td>
			<td>{{*[{{$parent}}]*}}</td>
			<td class="hidden-xs"></td>
			<td class="hidden-xs"></td>
		</tr>
	{{/if}}
		<tr id="new-upload-progress-bar--1"></tr> {{* this is needed to append the upload files in the right order *}}
	{{foreach $entries as $item}}
		<tr id="cloud-index-{{$item.attachId}}">
			<td><i class="fa {{$item.iconFromType}}" title="{{$item.type}}"></i></td>
			<td><a href="{{$item.fullPath}}">{{$item.displayName}}</a></td>
	{{if $item.is_owner}}
			<td class="cloud-index-tool">{{$item.attachIcon}}</td>
			<td id="file-edit-{{$item.attachId}}" class="cloud-index-tool"></td>
			<td class="cloud-index-tool"><i class="fakelink fa fa-pencil" onclick="filestorage(event, '{{$nick}}', {{$item.attachId}});"></i></td>
			<td class="cloud-index-tool"><a href="#" title="{{$delete}}" onclick="dropItem('{{$item.fileStorageUrl}}/{{$item.attachId}}/delete', '#cloud-index-{{$item.attachId}},#cloud-tools-{{$item.attachId}}'); return false;"><i class="fa fa-trash-o drop-icons"></i></a></td>

	{{else}}
			<td></td><td></td><td></td><td></td>
	{{/if}}
			<td>{{*{{$item.type}}*}}</td>
			<td class="hidden-xs">{{$item.sizeFormatted}}</td>
			<td class="hidden-xs">{{$item.lastmodified}}</td>
		</tr>
		<tr id="cloud-tools-{{$item.attachId}}">
			<td id="perms-panel-{{$item.attachId}}" colspan="9"></td>
		</tr>

	{{/foreach}}
	</table>
</div>
