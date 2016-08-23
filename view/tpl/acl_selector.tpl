<form>
<div class="modal" id="aclModal">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
				{{if $helpUrl}}
				<a type="button" target="hubzilla-help" href="{{$helpUrl}}" class="contextual-help-tool" title="Help and documentation"><i class="fa fa-question"></i></a>
				{{/if}}
				<h4 class="modal-title"><i id="dialog-perms-icon" class="fa fa-fw"></i> {{$aclModalTitle}}</h4>
			</div>
			<div class="section-content-wrapper">
				{{if $aclModalDesc}}
				<div id="acl-dialog-description" class="section-content-info-wrapper">{{$aclModalDesc}}</div>
				{{/if}}
				<label for="acl-select">{{$select_label}}</label>
				<select id="acl-select" name="optionsRadios" class="form-control form-group">
					<option id="acl-showall" value="public" selected>{{$showall}}</option>
					<option id="acl-onlyme" value="onlyme">{{$onlyme}}</option>
					<option id="acl-showlimited" value="limited">{{$showlimited}}</option>
				</select>

				{{if $showallOrigin}}
				<div id="acl-info" class="form-group">
					<i class="fa fa-info-circle"></i>&nbsp;{{$showallOrigin}}
				</div>
				{{/if}}

				{{if $jotnets}}
				<div class="jotnets-wrapper" role="tab" id="jotnets-wrapper">
					<a data-toggle="collapse" class="btn btn-block btn-default" href="#jotnets-collapse" aria-expanded="false" aria-controls="jotnets-collapse">{{$jnetModalTitle}} <span class="caret"></span></a>
				</div>
				<div id="jotnets-collapse" class="panel-collapse collapse" role="tabpanel" aria-labelledby="acl-select">
					{{$jotnets}}
					<div class="clear"></div>
				</div>
				{{/if}}

				<div id="acl-wrapper">
					<div id="acl-list">
						<div id="acl-search-wrapper">
							<input type="text" id="acl-search" placeholder="&#xf002; {{$search}}">
						</div>
						<div id="acl-list-content-wrapper">
							<div id=acl-showlimited-description>{{$showlimitedDesc}}</div>
							<div id="acl-list-content"></div>
						</div>
					</div>
					<span id="acl-fields"></span>
				</div>
				<div class="acl-list-item" rel="acl-template" style="display:none">
					<img data-src="{0}"><p>{1}</p>
					<button class="acl-button-hide btn btn-xs btn-default"><i class="fa fa-times"></i> {{$hide}}</button>
					<button class="acl-button-show btn btn-xs btn-default"><i class="fa fa-check"></i> {{$show}}</button>
				</div>
			</div>
			<div class="modal-footer clear">
				<button type="button" class="btn btn-default" data-dismiss="modal">{{$aclModalDismiss}}</button>
			</div>
		</div><!-- /.modal-content -->
	</div><!-- /.modal-dialog -->
</div><!-- /.modal -->
</form>
<script>
	$('[data-toggle="popover"]').popover(); // Init the popover, if present

	if(typeof acl=="undefined"){
		acl = new ACL(
			baseurl+"/acl"
		);
	}
</script>
