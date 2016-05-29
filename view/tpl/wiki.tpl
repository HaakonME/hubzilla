<style type="text/css" media="screen">
  #ace-editor { 
    position: relative;        
    width: 100%;
    height: 500px;
  }
  .fade.in {
    -webkit-transition: opacity 2s 1s ease;
    -moz-transition: opacity 2s 1s ease;
    -o-transition: opacity 2s 1s ease;
    transition: opacity 2s 1s ease;
  }
</style>
<div class="generic-content-wrapper">
  <div class="section-title-wrapper">
    <div class="pull-right">
      <button class="btn btn-primary btn-xs" onclick="$('#new-page-form-wrapper').hide(); openClose('new-wiki-form-wrapper');">New Wiki</button>
      <button class="btn btn-success btn-xs" onclick="$('#new-wiki-form-wrapper').hide(); openClose('new-page-form-wrapper');">New Page</button>
      <button id="fullscreen-btn" type="button" class="btn btn-default btn-xs" onclick="makeFullScreen();
          adjustFullscreenTopBarHeight();"><i class="fa fa-expand"></i></button>
      <button id="inline-btn" type="button" class="btn btn-default btn-xs" onclick="makeFullScreen(false);
          adjustInlineTopBarHeight();"><i class="fa fa-compress"></i></button>
    </div>
    <h2>{{$wikiheader}}</h2>
    <div class="clear"></div>
  </div>
	<div id="new-wiki-form-wrapper" class="section-content-tools-wrapper" style="display:none;">
      <form id="new-wiki-form" action="wiki/{{$channel}}/create/wiki" method="post" >
        <div class="clear"></div>
        {{include file="field_input.tpl" field=$wikiName}}
        <div class="btn-group pull-right">
            <div id="profile-jot-submit-right" class="btn-group">
                <button id="dbtn-acl" class="btn btn-default btn-sm" data-toggle="modal" data-target="#aclModal" title="Permission settings" onclick="return false;">
                    <i id="jot-perms-icon" class="fa fa-{{$lockstate}} jot-icons">{{$bang}}</i>
                </button>
            </div>
            <button id="new-wiki-submit" class="btn btn-primary" type="submit" name="submit" >Create Wiki</button>
        </div>
        <div>{{$acl}}</div>
      </form>        
      <div class="clear"></div>
      <hr>
    </div>
  
	<div id="new-page-form-wrapper" class="section-content-tools-wrapper" style="display:none;">
      <form id="new-page-form" action="wiki/create/page" method="post" >
        <div class="clear"></div>
        {{include file="field_input.tpl" field=$pageName}}
        <div class="btn-group pull-right">
            <button id="new-page-submit" class="btn btn-success" type="submit" name="submit" >Create Page</button>
        </div>
      </form>        <div class="clear"></div>
      <hr>
    </div>
  
  <div id="wiki-content-container" class="section-content-wrapper" {{if $hideEditor}}style="display: none;"{{/if}}>
    <ul class="nav nav-tabs" id="wiki-nav-tabs">
      <li><a data-toggle="tab" href="#edit-pane">Edit</a></li>
      <li class="active"><a data-toggle="tab" href="#preview-pane" id="wiki-get-preview">Preview</a></li>
      {{if $showPageControls}}
      <li class="dropdown">
        <a data-toggle="dropdown" class="dropdown-toggle" href="#">Page <b class="caret"></b></a>
        <ul class="dropdown-menu">
          <li><a id="save-page" data-toggle="tab" href="#">Save</a></li>
          <li><a id="delete-page" data-toggle="tab" href="#">Delete</a></li>
        </ul>
      </li>
      {{/if}}
    </ul>
    <div class="tab-content" id="myTabContent">

      <div id="edit-pane" class="tab-pane fade">
        <div id="ace-editor"></div>
      </div>      
      <div id="preview-pane" class="tab-pane fade in active">
        <div id="wiki-preview" class="section-content-wrapper">
          {{$renderedContent}}
        </div>
      </div>


    </div>
  </div>
</div>

<script>
  window.wiki_resource_id = '{{$resource_id}}';
  window.wiki_page_name = '{{$page}}';
  $(document).ready(function () {
    wiki_refresh_page_list();
    // Show Edit tab first. Otherwise the Ace editor does not load.
    $("#wiki-nav-tabs li:eq(1) a").tab('show');
  });

  var editor = ace.edit("ace-editor");
  editor.setTheme("ace/theme/github");
  editor.getSession().setMode("ace/mode/markdown");
  editor.getSession().setValue({{$content}});

  $('#wiki-get-preview').click(function (ev) {
    $.post("wiki/{{$channel}}/preview", {content: editor.getValue()}, function (data) {
      if (data.success) {
        $('#wiki-preview').html(data.html);
      } else {
        window.console.log('Error previewing page.');
      }
    }, 'json');
    ev.preventDefault();
  });

function wiki_delete_wiki(wikiName, resource_id) {
  if(!confirm('Are you sure you want to delete the entire wiki: ' + JSON.stringify(wikiName))) {
    return;
  }
  $.post("wiki/{{$channel}}/delete/wiki", {resource_id: resource_id}, function (data) {
      if (data.success) {
        window.console.log('Wiki deleted');
        // Refresh list and redirect page as necessary
        window.location = 'wiki/{{$channel}}';
      } else {
        alert('Error deleting wiki!');
        window.console.log('Error deleting wiki.');
      }
    }, 'json');
}


  $('#new-page-submit').click(function (ev) {
    if (window.wiki_resource_id === '') {
      window.console.log('You must have a wiki open in order to create pages.');
      ev.preventDefault();
      return false;
    }
    $.post("wiki/{{$channel}}/create/page", {name: $('#id_pageName').val(), resource_id: window.wiki_resource_id}, 
      function (data) {
        if (data.success) {
          window.location = data.url;
        } else {
          window.console.log('Error creating page.');
        }
      }, 'json');
    ev.preventDefault();
  });
  
  function wiki_refresh_page_list() {
    if (window.wiki_resource_id === '') {
      return false;
    }
  $.post("wiki/{{$channel}}/get/page/list/", {resource_id: window.wiki_resource_id}, function (data) {
      if (data.success) {
        $('#wiki_page_list_container').html(data.pages);
        $('#wiki_page_list_container').show();
      } else {
        alert('Error fetching page list!');
        window.console.log('Error fetching page list!');
      }
    }, 'json');
    return false;
  }
  
    $('#save-page').click(function (ev) {
    if (window.wiki_resource_id === '' || window.wiki_page_name === '') {
      window.console.log('You must have a wiki page open in order to edit pages.');
      ev.preventDefault();
      return false;
    }
    $.post("wiki/{{$channel}}/save/page", {content: editor.getValue(), name: window.wiki_page_name, resource_id: window.wiki_resource_id}, 
      function (data) {
        if (data.success) {
          window.console.log('Page saved successfully.');
        } else {
          alert('Error saving page.'); // TODO: Replace alerts with auto-timeout popups 
          window.console.log('Error saving page.');
        }
      }, 'json');
    ev.preventDefault();
  });
</script>
