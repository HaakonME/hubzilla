	$(document).ready(function() {
//		$("#id_permissions_role").sSelect();
		$("#id_name").blur(function() {
			$("#name-spinner").spin('small');
			var zreg_name = $("#id_name").val();
			$.get("new_channel/autofill.json?f=&name=" + encodeURIComponent(zreg_name),function(data) {
				$("#id_nickname").val(data);
				zFormError("#newchannel-name-feedback",data.error);
				$("#name-spinner").spin(false);
			});
		});

		$("#id_nickname").blur(function() {
			$("#nick-spinner").spin('small');
			var zreg_nick = $("#id_nickname").val();
			$.get("new_channel/checkaddr.json?f=&nick=" + encodeURIComponent(zreg_nick),function(data) {
				$("#id_nickname").val(data);
				zFormError("#newchannel-nickname-feedback",data.error);
				$("#nick-spinner").spin(false);
			});
		});

	});
