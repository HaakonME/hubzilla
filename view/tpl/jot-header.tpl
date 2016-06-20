<script language="javascript" type="text/javascript">

var editor = false;
var plaintext = '{{$editselect}}';
var pretext = '{{$pretext}}';

function initEditor(cb){
	if (editor==false){
		$("#profile-jot-text-loading").spin('small').show();
		if(plaintext == 'none') {
			$("#profile-jot-text-loading").spin(false).hide();
			$("#profile-jot-text").css({ 'height': 200, 'color': '#000', 'line-height': 'inherit' });
			{{if $bbco_autocomplete}}
			$("#profile-jot-text").bbco_autocomplete('{{$bbco_autocomplete}}'); // autocomplete bbcode
			{{/if}}
			{{if $editor_autocomplete}}
			if(typeof channelId === 'undefined')
				$("#profile-jot-text").editor_autocomplete(baseurl+"/acl");
			else
				$("#profile-jot-text").editor_autocomplete(baseurl+"/acl",[channelId]); // Also gives suggestions from current channel's connections
			{{/if}}
			editor = true;
			  $("a#jot-perms-icon").colorbox({ 
				  'inline' : true, 
				  'transition' : 'elastic' 
			});
			$(".jothidden").show();
			if (typeof cb!="undefined") cb();
			if(pretext.length)
				addeditortext(pretext);
			return;
		}
		tinyMCE.init({
			theme : "advanced",
			mode : "specific_textareas",
			editor_selector: {{$editselect}},
			auto_focus: "profile-jot-text",
			plugins : "bbcode,paste,autoresize, inlinepopups",
			theme_advanced_buttons1 : "bold,italic,underline,undo,redo,link,unlink,image,forecolor,formatselect,code",
			theme_advanced_buttons2 : "",
			theme_advanced_buttons3 : "",
			theme_advanced_toolbar_location : "top",
			theme_advanced_toolbar_align : "center",
			theme_advanced_blockformats : "blockquote,code",
			gecko_spellcheck : true,
			paste_text_sticky : true,
			entity_encoding : "raw",
			add_unload_trigger : false,
			remove_linebreaks : false,
			force_p_newlines : false,
			force_br_newlines : true,
			forced_root_block : '',
			convert_urls: false,
			content_css: "{{$baseurl}}/view/custom_tinymce.css",
			theme_advanced_path : false,
			file_browser_callback : "fcFileBrowser",
			setup : function(ed) {
				cPopup = null;
				ed.onKeyDown.add(function(ed,e) {
					if(cPopup !== null)
						cPopup.onkey(e);
				});

				ed.onKeyUp.add(function(ed, e) {
					var txt = tinyMCE.activeEditor.getContent();
					match = txt.match(/@([^ \n]+)$/);
					if(match!==null) {
						if(cPopup === null) {
							cPopup = new ACPopup(this,baseurl+"/acl");
						}
						if(cPopup.ready && match[1]!==cPopup.searchText) cPopup.search(match[1]);
						if(! cPopup.ready) cPopup = null;
					}
					else {
						if(cPopup !== null) { cPopup.close(); cPopup = null; }
					}
				});

				ed.onInit.add(function(ed) {
					ed.pasteAsPlainText = true;
					$("#profile-jot-text-loading").spin(false).hide();
					$(".jothidden").show();
					if (typeof cb!="undefined") cb();
				});

			}
		});

		editor = true;
	} else {
		if (typeof cb!="undefined") cb();
	}
}

function enableOnUser(){
	if (editor) return;
	$(this).val("");
	initEditor();
}
</script>
<script type="text/javascript" src="{{$baseurl}}/view/js/ajaxupload.js" ></script>
<script>
	$(document).ready(function() {
		/* enable tinymce on focus and click */
		$("#profile-jot-text").focus(enableOnUser);
		$("#profile-jot-text").click(enableOnUser);

		var upload_title = $('#wall-image-upload').attr('title');
		var attach_title = $('#wall-file-upload').attr('title');
		try {
			var uploader = new window.AjaxUpload('wall-image-upload',
			{ action: '{{$baseurl}}/wall_upload/{{$nickname}}',
				name: 'userfile',
				title: upload_title,
				onSubmit: function(file,ext) { $('#profile-rotator').spin('tiny'); },
				onComplete: function(file,response) {
					addeditortext(response);
					$('#jot-media').val($('#jot-media').val() + response);
					$('#profile-rotator').spin(false);
				}
			});
		} catch (e) {
		}
		try {
			var uploader_sub = new window.AjaxUpload('wall-image-upload-sub',
			{ action: '{{$baseurl}}/wall_upload/{{$nickname}}',
				name: 'userfile',
				title: upload_title,
				onSubmit: function(file,ext) { $('#profile-rotator').spin('tiny'); },
				onComplete: function(file,response) {
					addeditortext(response);
					$('#jot-media').val($('#jot-media').val() + response);
					$('#profile-rotator').spin(false);
				}
			});
		} catch(e) {
		}
		try {
			var file_uploader = new window.AjaxUpload('wall-file-upload',
			{ action: '{{$baseurl}}/wall_attach/{{$nickname}}',
				name: 'userfile',
				title: attach_title,
				onSubmit: function(file,ext) { $('#profile-rotator').spin('tiny'); },
				onComplete: function(file,response) {
					addeditortext(response);
					$('#jot-media').val($('#jot-media').val() + response);
					$('#profile-rotator').spin(false);
				}
			});
		} catch(e) {
		}
		try {
			var file_uploader_sub = new window.AjaxUpload('wall-file-upload-sub',
			{ action: '{{$baseurl}}/wall_attach/{{$nickname}}',
				name: 'userfile',
				title: attach_title,
				onSubmit: function(file,ext) { $('#profile-rotator').spin('tiny'); },
				onComplete: function(file,response) {
					addeditortext(response);
					$('#jot-media').val($('#jot-media').val() + response);
					$('#profile-rotator').spin(false);
				}
			});
		} catch(e) {
		}
	});

	function deleteCheckedItems() {
		var checkedstr = '';

		$('.item-select').each( function() {
			if($(this).is(':checked')) {
				if(checkedstr.length != 0) {
					checkedstr = checkedstr + ',' + $(this).val();
				}
				else {
					checkedstr = $(this).val();
				}
			}
		});
		$.post('item', { dropitems: checkedstr }, function(data) {
			window.location.reload();
		});
	}

	function jotGetLink() {
            textarea = document.getElementById('profile-jot-text');
            if (textarea.selectionStart || textarea.selectionStart == "0") {
                    var start = textarea.selectionStart;
                    var end = textarea.selectionEnd;	
                    if (end > start) {
                        reply = prompt("{{$linkurl}}");
                        if(reply && reply.length) {
                            textarea.value = textarea.value.substring(0, start) + "[url=" + reply + "]" + textarea.value.substring(start, end) + "[/url]" + textarea.value.substring(end, textarea.value.length);
                        }
                    } else {
                        reply = prompt("{{$linkurl}}");
                        if(reply && reply.length) {
                            reply = bin2hex(reply);
                            $('#profile-rotator').spin('tiny');
                            $.get('{{$baseurl}}/linkinfo?f=&binurl=' + reply, function(data) {
                                    addeditortext(data);
                                    $('#profile-rotator').spin(false);
                            });
                        }
                    }
            }
	}

	function jotGetLocation() {
		reply = prompt("{{$whereareu}}", $('#jot-location').val());
		if(reply && reply.length) {
			$('#jot-location').val(reply);
		}
	}

	function jotGetExpiry() {
		//reply = prompt("{{$expirewhen}}", $('#jot-expire').val());
		$('#expiryModal').modal();
		$('#expiry-modal-OKButton').on('click', function() {
			reply=$('#expiration-date').val();
			if(reply && reply.length) {
				$('#jot-expire').val(reply);
				$('#expiryModal').modal('hide');
			}
		})
	}

	function jotGetPubDate() {
		//reply = prompt("{{$expirewhen}}", $('#jot-expire').val());
		$('#createdModal').modal();
		$('#created-modal-OKButton').on('click', function() {
			reply=$('#created-date').val();
			if(reply && reply.length) {
				$('#jot-created').val(reply);
				$('#createdModal').modal('hide');
			}
		})
	}


	function jotShare(id) {
		if ($('#jot-popup').length != 0) $('#jot-popup').show();

		$('#like-rotator-' + id).spin('tiny');
		$.get('{{$baseurl}}/share/' + id, function(data) {
			if (!editor) $("#profile-jot-text").val("");
			initEditor(function(){
				addeditortext(data);
				$('#like-rotator-' + id).spin(false);
				$(window).scrollTop(0);
			});
		});
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
			reply = bin2hex(reply);
			$('#profile-rotator').spin('tiny');
			$.get('{{$baseurl}}/linkinfo?f=&binurl=' + reply, function(data) {
				if (!editor) $("#profile-jot-text").val("");
				initEditor(function(){
					addeditortext(data);
					$('#profile-rotator').spin(false);
				});
			});
		}
	}

	function itemTag(id) {
		reply = prompt("{{$term}}");
		if(reply && reply.length) {
			reply = reply.replace('#','');
			if(reply.length) {

				commentBusy = true;
				$('body').css('cursor', 'wait');

				$.get('{{$baseurl}}/tagger/' + id + '?term=' + reply);
				if(timer) clearTimeout(timer);
				timer = setTimeout(NavUpdate,3000);
				liking = 1;
			}
		}
	}

	function itemFiler(id) {

		var bordercolor = $("input").css("border-color");

		$.get('filer/', function(data){
			$.colorbox({html:data});
			$("#id_term").keypress(function(){
				$(this).css("border-color",bordercolor);
			})
			$("#select_term").change(function(){
				$("#id_term").css("border-color",bordercolor);
			})

			$("#filer_save").click(function(e){
				e.preventDefault();
				reply = $("#id_term").val();
				if(reply && reply.length) {
					commentBusy = true;
					$('body').css('cursor', 'wait');
					$.get('{{$baseurl}}/filer/' + id + '?term=' + reply, NavUpdate);
//					if(timer) clearTimeout(timer);
//					timer = setTimeout(NavUpdate,3000);
					liking = 1;
					$.colorbox.close();
				} else {
					$("#id_term").css("border-color","#FF0000");
				}
				return false;
			});
		});
		
	}

	function itemBookmark(id) {
		$.get('{{$baseurl}}/bookmarks?f=&item=' + id);
		if(timer) clearTimeout(timer);
		timer = setTimeout(NavUpdate,1000);
	}

	function itemAddToCal(id) {
		$.get('{{$baseurl}}/events/add/' + id);
		if(timer) clearTimeout(timer);
		timer = setTimeout(NavUpdate,1000);
	}

	function toggleVoting() {
		if($('#jot-consensus').val() > 0) {
			$('#jot-consensus').val(0);
			$('#profile-voting, #profile-voting-sub').removeClass('fa-check-square-o').addClass('fa-square-o');
		}
		else {
			$('#jot-consensus').val(1);
			$('#profile-voting, #profile-voting-sub').removeClass('fa-square-o').addClass('fa-check-square-o');
		}
	}

	function jotReact(id,icon) {
		if(id && icon) {
			$.get('{{$baseurl}}/react?f=&postid=' + id + '&emoji=' + icon);
			if(timer) clearTimeout(timer);
			timer = setTimeout(NavUpdate,1000);
		}
	}

	function jotClearLocation() {
		$('#jot-coord').val('');
		$('#profile-nolocation-wrapper').attr('disabled', true);
	}

	{{$geotag}}

</script>

<script>
$( document ).on( "click", ".wall-item-delete-link,.page-delete-link,.layout-delete-link,.block-delete-link", function(e) {
	var link = $(this).attr("href"); // "get" the intended link in a var

	if (typeof(eval($.fn.modal)) === 'function'){
		e.preventDefault();
		bootbox.confirm("<h4>{{$confirmdelete}}</h4>",function(result) {
			if (result) {
				document.location.href = link;
			}
		});
	} else {
		return confirm("{{$confirmdelete}}");
	}
});
</script>
