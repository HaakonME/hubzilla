<div class="generic-content-wrapper">
	<div class="section-title-wrapper">
		{{if $notself}}
		<div class="pull-right">
			<div class="btn-group">
				<button id="connection-dropdown" class="btn btn-default btn-xs dropdown-toggle" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
					<i class="fa fa-caret-down"></i>&nbsp;{{$tools_label}}
				</button>
				<ul class="dropdown-menu dropdown-menu-right" aria-labelledby="dLabel">
					<li><a  href="{{$tools.view.url}}" title="{{$tools.view.title}}">{{$tools.view.label}}</a></li>
					<li><a  href="{{$tools.recent.url}}" title="{{$tools.recent.title}}">{{$tools.recent.label}}</a></li>
					<li class="divider"></li>
					<li><a  href="#" title="{{$tools.refresh.title}}" onclick="window.location.href='{{$tools.refresh.url}}'; return false;">{{$tools.refresh.label}}</a></li>
					<li><a  href="#" title="{{$tools.block.title}}" onclick="window.location.href='{{$tools.block.url}}'; return false;">{{$tools.block.label}}</a></li>
					<li><a  href="#" title="{{$tools.ignore.title}}" onclick="window.location.href='{{$tools.ignore.url}}'; return false;">{{$tools.ignore.label}}</a></li>
					<li><a  href="#" title="{{$tools.archive.title}}" onclick="window.location.href='{{$tools.archive.url}}'; return false;">{{$tools.archive.label}}</a></li>
					<li><a  href="#" title="{{$tools.hide.title}}" onclick="window.location.href='{{$tools.hide.url}}'; return false;">{{$tools.hide.label}}</a></li>
					<li><a  href="#" title="{{$tools.delete.title}}" onclick="window.location.href='{{$tools.delete.url}}'; return false;">{{$tools.delete.label}}</a></li>
				</ul>
			</div>
			{{if $abook_prev || $abook_next}}
			<div class="btn-group">
				{{if $abook_prev}}
				<a href="connedit/{{$abook_prev}}{{if $section}}?f=&section={{$section}}{{/if}}" class="btn btn-default btn-xs" ><i class="fa fa-backward"></i></a>
				{{/if}}
				{{if $abook_next}}
				<a href="connedit/{{$abook_next}}{{if $section}}?f=&section={{$section}}{{/if}}" class="btn btn-default btn-xs" ><i class="fa fa-forward"></i></a>
				{{/if}}
			</div>
			{{/if}}
		</div>
		{{/if}}
		<h2>{{$header}}</h2>
	</div>
	<div class="section-content-wrapper-np">
		{{if $notself}}
		{{foreach $tools as $tool}}
		{{if $tool.info}}
		<div class="section-content-danger-wrapper">
			<div>
				{{$tool.info}}
			</div>
		</div>
		{{/if}}
		{{/foreach}}
		<div class="section-content-info-wrapper">
			<div>
				{{$addr_text}} <strong>'{{$addr}}'</strong>			
			</div>
			{{if $locstr}}
			<div>
				{{$loc_text}} {{$locstr}}
			</div>
			{{/if}}
			{{if $last_update}}
			<div>
				{{$lastupdtext}} {{$last_update}}
			</div>
			{{/if}}
		</div>
		{{/if}}

		<form id="abook-edit-form" action="connedit/{{$contact_id}}" method="post" >

		<input type="hidden" name="contact_id" value="{{$contact_id}}">
		<input type="hidden" name="section" value="{{$section}}">

		<div class="panel-group" id="contact-edit-tools" role="tablist" aria-multiselectable="true">
			{{if $notself}}

			{{if $is_pending}}
			<div class="panel">
				<div class="section-subtitle-wrapper" role="tab" id="pending-tool">
					<h3>
						<a data-toggle="collapse" data-parent="#contact-edit-tools" href="#pending-tool-collapse" aria-expanded="true" aria-controls="pending-tool-collapse">
							{{$pending_label}}
						</a>
					</h3>
				</div>
				<div id="pending-tool-collapse" class="panel-collapse collapse in" role="tabpanel" aria-labelledby="pending-tool">
					<div class="section-content-tools-wrapper">
						{{include file="field_checkbox.tpl" field=$unapproved}}
						<div class="settings-submit-wrapper" >
							<button type="submit" name="done" value="{{$submit}}" class="btn btn-primary">{{$submit}}</button>
						</div>
					</div>
				</div>
			</div>
			{{/if}}
			<div class="panel">
				<div class="section-subtitle-wrapper" role="tab" id="vcard-tool">
					<h3>
						<a data-toggle="collapse" data-parent="#contact-edit-tools" href="#vcard-tool-collapse" aria-expanded="true" aria-controls="vcard-tool-collapse">
							{{$vcard_label}}
						</a>
					</h3>
				</div>

				<div id="vcard-tool-collapse" class="panel-collapse collapse{{if !$is_pending || $section == 'vcard'}} in{{/if}}" role="tabpanel" aria-labelledby="vcard-tool">

<script>
$(document).ready(function() {

	$(document).on('click', '.vcard-header, .vcard-cancel-btn', updateView);
	$(document).on('click', '.add-field', doAdd);
	$(document).on('click', '.remove-field', doRemove);

	function updateView() {
		var id = $(this).data('id');
		var action = $(this).data('action');
		var header = $('#vcard-header-' + id);
		var cancel = $('#vcard-cancel-' + id);
		var addField = $('#vcard-add-field-' + id);
		var info = $('#vcard-info-' + id);
		var vcardPreview = $('#vcard-preview-' + id);
		var fn = $('#vcard-fn-' + id);

		if(action === 'open') {
			$(header).addClass('active');
			$(cancel).show();
			$(addField).show();
			$(info).show();
			$(fn).show();
			$(vcardPreview).hide();
		}
		else {
			$(header).removeClass('active');
			$(cancel).hide();
			$(addField).hide();
			$(info).hide();
			$(fn).hide();
			$(vcardPreview).show();
		}
	}

	function doAdd() {
		var what = $(this).data('add');
		var id = $(this).data('id');
		var element = '#template-form-' + what;
		var where = '#card_form_' + id;

		$(element + ' .remove-field').attr('data-id', id)

		if(what === 'vcard-adr') {
			var adrCount = $(where + ' .form-' + what).length;
			var attrName = 'adr[' + adrCount + '][]';
			$(element + ' input').attr('name', attrName);
		}

		if(what === 'vcard-org' || what === 'vcard-title' || what === 'vcard-note') {
			$(where + ' .add-' + what).hide()
		}

		$(element).clone().removeAttr('id').appendTo(where + ' .form-' + what + '-wrapper');
	}

	function doRemove() {
		var what = $(this).data('remove');
		var element = $(this).parents('div.form-' + what);
		var where = '#card_form_' + $(this).data('id');

		if(what === 'vcard-org' || what === 'vcard-title' || what === 'vcard-note') {
			$(where + ' .add-' + what).show()
		}

		$(element).remove();
	}

});
</script>



			<div class="dropdown pull-right">
				<button data-toggle="dropdown" type="button" class="btn btn-default btn-sm dropdown-toggle"><i class="fa fa-plus"></i> {{$add_field}}</button>
				<ul class="dropdown-menu">
					<li class="add-vcard-org" style="display: none"><a href="#" data-add="vcard-org" data-id="new" class="add-field" onclick="return false;">{{$org_label}}</a></li>
					<li class="add-vcard-title" style="display: none"><a href="#" data-add="vcard-title" data-id="new" class="add-field" onclick="return false;">{{$title_label}}</a></li>
					<li class="add-vcard-tel"><a href="#" data-add="vcard-tel" data-id="new" class="add-field" onclick="return false;">{{$tel_label}}</a></li>
					<li class="add-vcard-email"><a href="#" data-add="vcard-email" data-id="new" class="add-field" onclick="return false;">{{$email_label}}</a></li>
					<li class="add-vcard-impp"><a href="#" data-add="vcard-impp" data-id="new" class="add-field" onclick="return false;">{{$impp_label}}</a></li>
					<li class="add-vcard-url"><a href="#" data-add="vcard-url" data-id="new" class="add-field" onclick="return false;">{{$url_label}}</a></li>
					<li class="add-vcard-adr"><a href="#" data-add="vcard-adr" data-id="new" class="add-field" onclick="return false;">{{$adr_label}}</a></li>
					<li class="add-vcard-note"><a href="#" data-add="vcard-note" data-id="new" class="add-field" onclick="return false;">{{$note_label}}</a></li>
				</ul>
			</div>

			<div class="vcard-fn-create form-group">
				<div class="form-vcard-fn-wrapper">
					<div class="form-group form-vcard-fn">
						<div class="vcard-nophoto"><i class="fa fa-user"></i></div><input type="text" name="fn" value="" placeholder="{{$name_label}}">
					</div>
				</div>
			</div>

			<div class="vcard-org form-group">
				<div class="form-vcard-org-wrapper">
					<div class="form-group form-vcard-org">
						<input type="text" name="org" value="" placeholder="{{$org_label}}">
						<i data-remove="vcard-org" data-id="new" class="fa fa-trash-o remove-field drop-icons fakelink"></i>
					</div>
				</div>
			</div>

			<div class="vcard-title form-group">
				<div class="form-vcard-title-wrapper">
					<div class="form-group form-vcard-title">
						<input type="text" name="title" value="" placeholder="{{$title_label}}">
						<i data-remove="vcard-title" data-id="new" class="fa fa-trash-o remove-field drop-icons fakelink"></i>
					</div>
				</div>
			</div>

			<div class="vcard-tel form-group">
				<div class="form-vcard-tel-wrapper">
					<div class="form-group form-vcard-tel">
						<select name="tel_type[]">
							<option value="CELL">{{$mobile}}</option>
							<option value="HOME">{{$home}}</option>
							<option value="WORK">{{$work}}</option>
							<option value="OTHER">{{$other}}</option>
						</select>
						<input type="text" name="tel[]" value="" placeholder="{{$tel_label}}">
						<i data-remove="vcard-tel" data-id="new" class="fa fa-trash-o remove-field drop-icons fakelink"></i>
					</div>
				</div>
			</div>


			<div class="vcard-email form-group">
				<div class="form-vcard-email-wrapper">
					<div class="form-group form-vcard-email">
						<select name="email_type[]">
							<option value="HOME">{{$home}}</option>
							<option value="WORK">{{$work}}</option>
							<option value="OTHER">{{$other}}</option>
						</select>
						<input type="text" name="email[]" value="" placeholder="{{$email_label}}">
						<i data-remove="vcard-email" data-id="new" class="fa fa-trash-o remove-field drop-icons fakelink"></i>
					</div>
				</div>
			</div>

			<div class="vcard-impp form-group">
				<div class="form-vcard-impp-wrapper">
				</div>
			</div>

			<div class="vcard-url form-group">
				<div class="form-vcard-url-wrapper">
				</div>
			</div>

			<div class="vcard-adr form-group">
				<div class="form-vcard-adr-wrapper">
				</div>
			</div>

			<div class="vcard-note form-group">
				<div class="form-vcard-note-wrapper">
				</div>
			</div>

			<div class="clear"></div>



<div id="template-form-vcard-org" class="form-group form-vcard-org">
	<div class="form-group form-vcard-org">
		<input type="text" name="org" value="" placeholder="{{$org_label}}">
		<i data-remove="vcard-org" data-id="" class="fa fa-trash-o remove-field drop-icons fakelink"></i>
	</div>
</div>

<div id="template-form-vcard-title" class="form-group form-vcard-title">
	<div class="form-group form-vcard-title">
		<input type="text" name="title" value="" placeholder="{{$title_label}}">
		<i data-remove="vcard-title" data-id="" class="fa fa-trash-o remove-field drop-icons fakelink"></i>
	</div>
</div>

<div id="template-form-vcard-tel" class="form-group form-vcard-tel">
	<select name="tel_type[]">
		<option value="CELL">{{$mobile}}</option>
		<option value="HOME">{{$home}}</option>
		<option value="WORK">{{$work}}</option>
		<option value="OTHER">{{$other}}</option>
	</select>
	<input type="text" name="tel[]" value="" placeholder="{{$tel_label}}">
	<i data-remove="vcard-tel" data-id="" class="fa fa-trash-o remove-field drop-icons fakelink"></i>
</div>

<div id="template-form-vcard-email" class="form-group form-vcard-email">
	<select name="email_type[]">
		<option value="HOME">{{$home}}</option>
		<option value="WORK">{{$work}}</option>
		<option value="OTHER">{{$other}}</option>
	</select>
	<input type="text" name="email[]" value="" placeholder="{{$email_label}}">
	<i data-remove="vcard-email" data-id="" class="fa fa-trash-o remove-field drop-icons fakelink"></i>
</div>

<div id="template-form-vcard-impp" class="form-group form-vcard-impp">
	<select name="impp_type[]">
		<option value="HOME">{{$home}}</option>
		<option value="WORK">{{$work}}</option>
		<option value="OTHER">{{$other}}</option>
	</select>
	<input type="text" name="impp[]" value="" placeholder="{{$impp_label}}">
	<i data-remove="vcard-impp" data-id="" class="fa fa-trash-o remove-field drop-icons fakelink"></i>
</div>

<div id="template-form-vcard-url" class="form-group form-vcard-url">
	<select name="url_type[]">
		<option value="HOME">{{$home}}</option>
		<option value="WORK">{{$work}}</option>
		<option value="OTHER">{{$other}}</option>
	</select>
	<input type="text" name="url[]" value="" placeholder="{{$url_label}}">
	<i data-remove="vcard-url" data-id="" class="fa fa-trash-o remove-field drop-icons fakelink"></i>
</div>

<div id="template-form-vcard-adr" class="form-group form-vcard-adr">
	<div class="form-group">
		<select name="adr_type[]">
			<option value="HOME">{{$home}}</option>
			<option value="WORK">{{$work}}</option>
			<option value="OTHER">{{$other}}</option>
		</select>
		<label>{{$adr_label}}</label>
		<i data-remove="vcard-adr" data-id="" class="fa fa-trash-o remove-field drop-icons fakelink"></i>
	</div>
	<div class="form-group">
		<input type="text" name="" value="" placeholder="{{$po_box}}">
	</div>
	<div class="form-group">
		<input type="text" name="" value="" placeholder="{{$extra}}">
	</div>
	<div class="form-group">
		<input type="text" name="" value="" placeholder="{{$street}}">
	</div>
	<div class="form-group">
		<input type="text" name="" value="" placeholder="{{$locality}}">
	</div>
	<div class="form-group">
		<input type="text" name="" value="" placeholder="{{$region}}">
	</div>
	<div class="form-group">
		<input type="text" name="" value="" placeholder="{{$zip_code}}">
	</div>
	<div class="form-group">
		<input type="text" name="" value="" placeholder="{{$country}}">
	</div>
</div>

<div id="template-form-vcard-note" class="form-group form-vcard-note">
	<label>{{$note_label}}</label>
	<i data-remove="vcard-note" data-id="" class="fa fa-trash-o remove-field drop-icons fakelink"></i>
	<textarea name="note" class="form-control"></textarea>
</div>


		</div>
		</div>





			{{if $affinity }}
			<div class="panel">
				<div class="section-subtitle-wrapper" role="tab" id="affinity-tool">
					<h3>
						<a data-toggle="collapse" data-parent="#contact-edit-tools" href="#affinity-tool-collapse" aria-expanded="true" aria-controls="affinity-tool-collapse">
							{{$affinity }}
						</a>
					</h3>
				</div>
				<div id="affinity-tool-collapse" class="panel-collapse collapse{{if !$is_pending || $section == 'affinity'}} in{{/if}}" role="tabpanel" aria-labelledby="affinity-tool">
					<div class="section-content-tools-wrapper">
						{{if $slide}}
						<div class="form-group"><strong>{{$lbl_slider}}</strong></div>
						{{$slide}}
						<input id="contact-closeness-mirror" type="hidden" name="closeness" value="{{$close}}" />
						{{/if}}

						{{if $multiprofs }}
						<div class="form-group">
							<strong>{{$lbl_vis2}}</strong>
							{{$profile_select}}
						</div>
						{{/if}}
						<div class="settings-submit-wrapper" >
							<button type="submit" name="done" value="{{$submit}}" class="btn btn-primary">{{$submit}}</button>
						</div>
					</div>
				</div>
			</div>
			{{/if}}

			{{if $connfilter}}
			<div class="panel">
				<div class="section-subtitle-wrapper" role="tab" id="fitert-tool">
					<h3>
						<a data-toggle="collapse" data-parent="#contact-edit-tools" href="#fitert-tool-collapse" aria-expanded="true" aria-controls="fitert-tool-collapse">
							{{$connfilter_label}}
						</a>
					</h3>
				</div>
				<div id="fitert-tool-collapse" class="panel-collapse collapse{{if ( !$is_pending && !($slide || $multiprofs)) || $section == 'filter' }} in{{/if}}" role="tabpanel" aria-labelledby="fitert-tool">
					<div class="section-content-tools-wrapper">
						{{include file="field_textarea.tpl" field=$incl}}
						{{include file="field_textarea.tpl" field=$excl}}
						<div class="settings-submit-wrapper" >
							<button type="submit" name="done" value="{{$submit}}" class="btn btn-primary">{{$submit}}</button>
						</div>
					</div>
				</div>
			</div>
			{{else}}
			<input type="hidden" name="{{$incl.0}}" value="{{$incl.2}}" />
			<input type="hidden" name="{{$excl.0}}" value="{{$excl.2}}" />
			{{/if}}

			{{if $rating}}
			<div class="panel">
				<div class="section-subtitle-wrapper" role="tab" id="rating-tool">
					<h3>
						<a data-toggle="collapse" data-parent="#contact-edit-tools" href="#rating-tool-collapse" aria-expanded="true" aria-controls="rating-tool-collapse">
							{{$lbl_rating}}
						</a>
					</h3>
				</div>
				<div id="rating-tool-collapse" class="panel-collapse collapse{{if !$is_pending && !($slide || $multiprofs) && !$connfilter}} in{{/if}}" role="tabpanel" aria-labelledby="rating-tool">
					<div class="section-content-tools-wrapper">
						<div class="section-content-warning-wrapper">
							{{$rating_info}}
						</div>
						<div class="form-group"><strong>{{$lbl_rating_label}}</strong></div>
						{{$rating}}
						{{include file="field_textarea.tpl" field=$rating_text}}
						<input id="contact-rating-mirror" type="hidden" name="rating" value="{{$rating_val}}" />
						<div class="settings-submit-wrapper" >
							<button type="submit" name="done" value="{{$submit}}" class="btn btn-primary">{{$submit}}</button>
						</div>
					</div>
				</div>
			</div>
			{{/if}}

			{{/if}}

			{{if ! $is_pending}}
			<div class="panel">
				{{if $notself}}
				<div class="section-subtitle-wrapper" role="tab" id="perms-tool">
					<h3>
						<a data-toggle="collapse" data-parent="#contact-edit-tools" href="#perms-tool-collapse" aria-expanded="true" aria-controls="perms-tool-collapse">
							{{$permlbl}}
						</a>
					</h3>
				</div>
				{{/if}}
				<div id="perms-tool-collapse" class="panel-collapse collapse{{if $self || $section === 'perms'}} in{{/if}}" role="tabpanel" aria-labelledby="perms-tool">
					<div class="section-content-tools-wrapper">
						<div class="section-content-warning-wrapper">
						{{if $notself}}{{$permnote}}{{/if}}
						{{if $self}}{{$permnote_self}}{{/if}}
						</div>

						<table id="perms-tool-table" class=form-group>
							<tr>
								<td></td>
								{{if $notself}}
								<td class="abook-them">{{$them}}</td>
								{{/if}}
								<td colspan="2" class="abook-me">{{$me}}</td>
							</tr>
							{{foreach $perms as $prm}}
							{{include file="field_acheckbox.tpl" field=$prm}}
							{{/foreach}}
						</table>

						{{if $self}}
						<div>
							<div class="section-content-info-wrapper">
								{{$autolbl}}
							</div>
							{{include file="field_checkbox.tpl" field=$autoperms}}
						</div>
						{{/if}}

						<div class="settings-submit-wrapper" >
							<button type="submit" name="done" value="{{$submit}}" class="btn btn-primary">{{$submit}}</button>
						</div>
					</div>
				</div>
			</div>
			{{/if}}
		</div>
		</form>
	</div>
</div>
