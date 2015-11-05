<?php


function ffsapi_content(&$a) {

$baseurl = z_root();
$name = sprintf( t('Social Provider on %1$s'), get_config('system','sitename'));
$description = t('Share content from Firefox to $Projectname');
$author = 'Mike Macgirvin';
$homepage = 'http://hubzilla.org';
$activate = t('Activate the Firefox $Projectname provider');

$s = <<< EOT

<script>

var baseurl = '$baseurl';

var data = {
  "origin": baseurl,
  // currently required
  "name": '$name',
  "iconURL": baseurl+"/images/hz-16.png",
  "icon32URL": baseurl+"/images/hz-32.png",
  "icon64URL": baseurl+"/images/hz-64.png",

  // at least one of these must be defined
  // "workerURL": baseurl+"/worker.js",
  // "sidebarURL": baseurl+"/sidebar.htm",
  "shareURL": baseurl+"/rpost?f=&url=%{url}",

  // status buttons are scheduled for Firefox 26 or 27
  //"statusURL": baseurl+"/statusPanel.html",

  // social bookmarks are available in Firefox 26
  //"markURL": baseurl+"/mark.html?url=%{url}",
  // icons should be 32x32 pixels
  //"markedIcon": baseurl+"/unchecked.jpg",
  //"unmarkedIcon": baseurl+"/checked.jpg",

  // should be available for display purposes
  "description": '$description',
  "author": '$author',
  "homepageURL": '$homepage',

  // optional
  "version": "1.0"
}

function activate(node) {
  var event = new CustomEvent("ActivateSocialFeature");
  var jdata = JSON.stringify(data);
  node.setAttribute("data-service", JSON.stringify(data));
  node.dispatchEvent(event);
}
</script>

<button onclick="activate(this)" title="activate the demo provider">$activate</button>

EOT;

return $s;

}