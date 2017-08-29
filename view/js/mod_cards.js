$(document).ready( function() {
	$(".autotime").timeago();

	/* autocomplete @nicknames */
	$(".comment-edit-form  textarea").editor_autocomplete(baseurl+"/acl?f=&n=1");
	/* autocomplete bbcode */
	$(".comment-edit-form  textarea").bbco_autocomplete('bbcode');

});