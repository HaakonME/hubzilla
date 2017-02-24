$(document).ready(function() {
	$('form').areYouSure(); // Warn user about unsaved settings
	$('textarea').bbco_autocomplete('bbcode');

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
		var where = '#profile-edit-form';

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
		var where = '#profile-edit-form' + $(this).data('id');

		if(what === 'vcard-org' || what === 'vcard-title' || what === 'vcard-note') {
			$(where + ' .add-' + what).show()
		}

		$(element).remove();
	}

});
