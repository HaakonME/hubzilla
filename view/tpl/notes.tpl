<div class="widget">
	<h3>{{$banner}}</h3>
	<textarea name="note_text" id="note-text">{{$text}}</textarea>
	<script>
		var noteSaveTimer = null;
		var noteText = $('#note-text');

		$(document).ready(function(){
			noteText.height(noteText[0].scrollHeight);
		});

		$(document).on('focusout',"#note-text",function(e){
			if(noteSaveTimer)
				clearTimeout(noteSaveTimer);
			notePostFinal();
			noteSaveTimer = null;
		});

		$(document).on('focusin',"#note-text",function(e){
			noteSaveTimer = setTimeout(noteSaveChanges,10000);
		});

		function notePostFinal() {
			$.post('notes/sync', { 'note_text' : $('#note-text').val() });
		}

		function noteSaveChanges() {
			$.post('notes', { 'note_text' : $('#note-text').val() });
			noteSaveTimer = setTimeout(noteSaveChanges,10000);
		}
	</script>
</div>
