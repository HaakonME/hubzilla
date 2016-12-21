$(document).ready(function() {

	$('form').areYouSure({'addRemoveFieldsMarksDirty':true, 'message': aStr['leavethispage'] }); // Warn user about unsaved settings

	if(typeof(after_following) !== 'undefined' && after_following) {
		if(typeof(connectDefaultShare) !== 'undefined')
			connectDefaultShare();
		else
			connectFullShare();
	}

	$('#id_pending').click(function() {
		if(typeof(connectDefaultShare) !== 'undefined')
			connectDefaultShare();
		else
			connectFullShare();
	});

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
		var where = '#abook-edit-form';

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
		var where = '#abook_edit_form' + $(this).data('id');

		if(what === 'vcard-org' || what === 'vcard-title' || what === 'vcard-note') {
			$(where + ' .add-' + what).show()
		}

		$(element).remove();
	}

});

function connectFullShare() {
	$('.abook-edit-me').each(function() {
		if(! $(this).is(':disabled'))
			$(this).removeAttr('checked');
	});
	$('#me_id_perms_view_stream').attr('checked','checked');
	$('#me_id_perms_view_profile').attr('checked','checked');
	$('#me_id_perms_view_contacts').attr('checked','checked');
	$('#me_id_perms_view_storage').attr('checked','checked');
	$('#me_id_perms_view_pages').attr('checked','checked');
	$('#me_id_perms_send_stream').attr('checked','checked');
	$('#me_id_perms_post_wall').attr('checked','checked');
	$('#me_id_perms_post_comments').attr('checked','checked');
	$('#me_id_perms_post_mail').attr('checked','checked');
	$('#me_id_perms_chat').attr('checked','checked');
	$('#me_id_perms_view_storage').attr('checked','checked');
	$('#me_id_perms_republish').attr('checked','checked');
	$('#me_id_perms_post_like').attr('checked','checked');
}
