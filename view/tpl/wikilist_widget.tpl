<div id="wiki_list" class="widget">
	<h3>{{$header}}</h3>
	<ul class="nav nav-pills nav-stacked">
		{{if $wikis}}		
		{{foreach $wikis as $wiki}}
		<li>{{if $owner}}<a href="#" onclick="$('div[id^=\'edit-wiki-form-wrapper\']').hide(); $('div[id^=\'new-wiki-form-wrapper\']').hide(); openClose('edit-wiki-form-wrapper-{{$wiki.resource_id}}'); return false;" class="pull-right wikilist" title="{{$edit}}"><i class="fa fa-pencil"></i></a>{{/if}}
			<a href="#" onclick="wiki_download_wiki('{{$wiki.resource_id}}'); return false;" title="{{$download}}" class="pull-right wikilist"><i class="fa fa-download"></i></a>
			<a href="/wiki/{{$channel}}/{{$wiki.urlName}}/Home" title="{{$view}}"{{if $wiki.active}} class="active"{{/if}}>{{$wiki.title}}</a>
			{{if $owner}}
			<div id="edit-wiki-form-wrapper-{{$wiki.resource_id}}" class="section-content-tools-wrapper" style="display:none;">
				<form id="edit-wiki-form" action="wiki/edit/wiki" method="post" >
					<div class="clear"></div>
					<div class="btn-group pull-right">
						<button class="btn btn-xs btn-danger" onclick="wiki_delete_wiki('{{$wiki.title}}', '{{$wiki.resource_id}}'); return false;"><i class="fa fa-trash-o"></i>&nbsp;Delete Wiki</button>
					</div>
				</form>
				<div class="clear"></div>
			</div>
			{{/if}}
		</li> 
		{{/foreach}}
		{{/if}}
		{{if $owner}}
		<li><a href="#" class="fakelink" onclick="wiki_show_new_wiki_form(); return false;"><i id="new-wiki-button" class="fa fa-plus-circle"></i>&nbsp;{{$addnew}}</a></li>
		{{/if}}
	</ul>
	{{if $owner}}
	<div id="new-wiki-form-wrapper" class="sub-menu" style="display:none;">
		<form id="new-wiki-form" action="wiki/{{$channel}}/create/wiki" method="post" class="acl-form" data-form_id="new-wiki-form" data-allow_cid='{{$allow_cid}}' data-allow_gid='{{$allow_gid}}' data-deny_cid='{{$deny_cid}}' data-deny_gid='{{$deny_gid}}'>
			{{include file="field_input.tpl" field=$wikiName}}
			{{include file="field_checkbox.tpl" field=$notify}}
			<div>
				<div id="profile-jot-submit-right" class="btn-group">
					<button id="dbtn-acl" class="btn btn-default btn-sm" data-toggle="modal" data-target="#aclModal" title="Permission settings" onclick="return false;">
						<i id="jot-perms-icon" class="fa fa-{{$lockstate}} jot-icons"></i>{{$bang}}
					</button>
					<button id="new-wiki-submit" class="btn btn-primary btn-sm" type="submit" name="submit" >{{$create}}</button>
				</div>
			</div>
		</form>
		<div class="clear"></div>
	</div>
	{{/if}}
</div>
{{$acl}}
