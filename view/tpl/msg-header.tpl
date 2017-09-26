<script src="library/blueimp_upload/js/vendor/jquery.ui.widget.js"></script>
<script src="library/blueimp_upload/js/jquery.iframe-transport.js"></script>
<script src="library/blueimp_upload/js/jquery.fileupload.js"></script>
<script>
	$(document).ready(function() {

		$("#prvmail-text").editor_autocomplete(baseurl+"/acl");

		$('#invisible-wall-file-upload').fileupload({
			url: 'wall_attach/{{$nickname}}',
			dataType: 'json',
			dropZone: $('#prvmail-text'),
			maxChunkSize: 4 * 1024 * 1024,
			add: function(e,data) {
				$('#prvmail-rotator').show();
				data.submit();
			},
			done: function(e,data) {
				addmailtext(data.result.message);
				$('#jot-media').val($('#jot-media').val() + data.result.message);
			},
			stop: function(e,data) {
				preview_mail();
				$('#prvmail-rotator').hide();
			},
		});

		$('#prvmail-attach-wrapper').click(function(event) { event.preventDefault(); $('#invisible-wall-file-upload').trigger('click'); return false;});
		$('#prvmail-attach-wrapper-sub').click(function(event) { event.preventDefault(); $('#invisible-wall-file-upload').trigger('click'); return false;});


	});

	function prvmailJotGetLink() {
		reply = prompt("{{$linkurl}}");
		if(reply && reply.length) {
			$('#prvmail-rotator').show();
			$.get('linkinfo?f=&url=' + reply, function(data) {
				addmailtext(data);
				preview_mail();
				$('#prvmail-rotator').hide();
			});
		}
	}

	function prvmailGetExpiry() {
		reply = prompt("{{$expireswhen}}", $('#inp-prvmail-expires').val());
		if(reply && reply.length) {
			$('#inp-prvmail-expires').val(reply);
		}
	}

	function linkdropper(event) {
		var linkFound = event.dataTransfer.types.contains("text/uri-list");
		if(linkFound)
			event.preventDefault();
	}

	function linkdrop(event) {
		var reply = event.dataTransfer.getData("text/uri-list");
		event.target.textContent = reply;
		event.preventDefault();
		if(reply && reply.length) {
			$('#prvmail-rotator').show();
			$.get('linkinfo?f=&url=' + reply, function(data) {
				addmailtext(data);
				preview_mail();
				$('#prvmail-rotator').hide();
			});
		}
	}

	function addmailtext(data) {
		var currentText = $("#prvmail-text").val();
		$("#prvmail-text").val(currentText + data);
	}	



</script>

