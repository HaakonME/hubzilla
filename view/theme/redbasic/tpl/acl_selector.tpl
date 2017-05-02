<form>
<div class="modal" id="aclModal">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<h4 class="modal-title">
					<i id="dialog-perms-icon" class="fa fa-fw"></i> {{$aclModalTitle}}
					{{if $helpUrl}}
					<a target="hubzilla-help" href="{{$helpUrl}}" class="contextual-help-tool" title="Help and documentation"><i class="fa fa-fw fa-question"></i></a>
					{{/if}}
				</h4>
				<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
			</div>
			<div class="section-content-wrapper">
				{{if $aclModalDesc}}
				<div id="acl-dialog-description" class="section-content-info-wrapper">{{$aclModalDesc}}</div>
				{{/if}}
				<label for="acl-select">{{$select_label}}</label>
				<select id="acl-select" name="optionsRadios" class="form-control form-group">
					<option id="acl-showall" value="public" {{$public_selected}}>{{$showall}}</option>
					{{$groups}}
					<option id="acl-onlyme" value="onlyme" {{$justme_selected}}>{{$onlyme}}</option>
					<option id="acl-custom" value="custom" {{$custom_selected}}>{{$custom}}</option>
				</select>

				{{if $showallOrigin}}
				<div id="acl-info" class="form-group">
					<i class="fa fa-info-circle"></i>&nbsp;{{$showallOrigin}}
				</div>
				{{/if}}

				<div id="acl-wrapper">
					<div id="acl-list">
						<input class="form-control" type="text" id="acl-search" placeholder="&#xf002; {{$search}}">
						<small class="text-muted">{{$showlimitedDesc}}</small>
						<div id="acl-list-content"></div>
					</div>
				</div>

				<div class="acl-list-item" rel="acl-template" style="display:none">
					<div class="acl-item-header">
						<img class="menu-img-1" data-src="{0}"> {1}
					</div>
					<button class="acl-button-hide btn btn-sm btn-outline-danger"><i class="fa fa-times"></i> {{$hide}}</button>
					<button class="acl-button-show btn btn-sm btn-outline-success"><i class="fa fa-check"></i> {{$show}}</button>
				</div>
			</div>
			<div class="modal-footer clear">
				<button type="button" class="btn btn-outline-secondary" data-dismiss="modal">{{$aclModalDismiss}}</button>
			</div>
		</div><!-- /.modal-content -->
	</div><!-- /.modal-dialog -->
</div><!-- /.modal -->
</form>
<script>
	// compatibility issue with bootstrap v4
	//$('[data-toggle="popover"]').popover(); // Init the popover, if present

	if(typeof acl=="undefined"){
		acl = new ACL(
			baseurl+"/acl"
		);
	}
</script>
