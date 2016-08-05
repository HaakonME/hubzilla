<style type="text/css" media="screen">
  #ace-editor { 
    position: relative;        
    width: 100%;
    height: 500px;
  }
  .fade.in {
    -webkit-transition: opacity 0.5s 0.5s ease;
    -moz-transition: opacity 0.5s 0.5s ease;
    -o-transition: opacity 0.5s 0.5s ease;
    transition: opacity 0.5s 0.5s ease;
  }
</style>
<div class="generic-content-wrapper">
  <div class="section-title-wrapper">
    <div class="pull-right">
      {{if $showNewWikiButton}}
      <button class="btn btn-primary btn-xs acl-form-trigger" onclick="$('#new-page-form-wrapper').hide(); openClose('new-wiki-form-wrapper');" data-form_id="new-wiki-form">New Wiki</button>
      {{/if}}
      {{if $showNewPageButton}}
      <button class="btn btn-success btn-xs" onclick="$('#new-wiki-form-wrapper').hide(); openClose('new-page-form-wrapper');">New Page</button>
      {{/if}}
      <button id="fullscreen-btn" type="button" class="btn btn-default btn-xs" onclick="makeFullScreen();
          adjustFullscreenTopBarHeight();"><i class="fa fa-expand"></i></button>
      <button id="inline-btn" type="button" class="btn btn-default btn-xs" onclick="makeFullScreen(false);
          adjustInlineTopBarHeight();"><i class="fa fa-compress"></i></button>
    </div>
    <h2><span id="wiki-header-name">{{$wikiheaderName}}</span>: <span id="wiki-header-page">{{$wikiheaderPage}}</span></h2>
    <div class="clear"></div>
  </div>
	<div id="new-wiki-form-wrapper" class="section-content-tools-wrapper" style="display:none;">
      <form id="new-wiki-form" action="wiki/{{$channel}}/create/wiki" method="post" class="acl-form" data-form_id="new-wiki-form" data-allow_cid='{{$allow_cid}}' data-allow_gid='{{$allow_gid}}' data-deny_cid='{{$deny_cid}}' data-deny_gid='{{$deny_gid}}'>
        <div class="clear"></div>
        {{include file="field_input.tpl" field=$wikiName}}
        
        <div id="post-visible-container" class="form-group field checkbox"> 
          <span style="font-size:1.2em;" class="pull-left">Send notification post?</span>                            
          <div style="margin-left:20px" class="pull-left">
              <input name="postVisible" id="postVisible" value="0" type="checkbox">
              <label class="switchlabel" for="postVisible"> 
                  <span class="onoffswitch-inner" data-on="Post" data-off="None"></span>
                  <span class="onoffswitch-switch"></span>
              </label>
          </div>
        </div>
        
        <div class="btn-group pull-right">
            <div id="profile-jot-submit-right" class="btn-group">
                <button id="dbtn-acl" class="btn btn-default btn-sm" data-toggle="modal" data-target="#aclModal" title="Permission settings" onclick="return false;">
                    <i id="jot-perms-icon" class="fa fa-{{$lockstate}} jot-icons"></i>{{$bang}}
                </button>
                <button id="new-wiki-submit" class="btn btn-primary" type="submit" name="submit" >Create Wiki</button>
            </div>
        </div>
      </form>        
     {{$acl}}
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
  
    <div id="rename-page-form-wrapper" class="section-content-tools-wrapper" style="display:none;">
      <form id="rename-page-form" action="wiki/rename/page" method="post" >
        <div class="clear"></div>
        {{include file="field_input.tpl" field=$pageRename}}
        <div class="btn-group pull-right">
            <button id="rename-page-submit" class="btn btn-warning" type="submit" name="submit" >Rename Page</button>
        </div>
      </form>        <div class="clear"></div>
      <hr>
    </div>

  <div id="wiki-content-container" class="section-content-wrapper" {{if $hideEditor}}style="display: none;"{{/if}}>
    <ul class="nav nav-tabs" id="wiki-nav-tabs">
      <li><a data-toggle="tab" href="#edit-pane">Edit</a></li>
      <li class="active"><a data-toggle="tab" href="#preview-pane" id="wiki-get-preview">Preview</a></li>
      <li {{if $hidePageHistory}}style="display: none;"{{/if}}><a data-toggle="tab" href="#page-history-pane" id="wiki-get-history">History</a></li>
      {{if $showPageControls}}
      <li class="dropdown">
        <a data-toggle="dropdown" class="dropdown-toggle" href="#">Page <b class="caret"></b></a>
        <ul class="dropdown-menu">
          <li><a id="save-page" data-toggle="tab" href="#">Save</a></li>
          <li><a id="rename-page" data-toggle="tab" href="#">Rename</a></li>
          <li><a id="delete-page" data-toggle="tab" href="#">Delete</a></li>
          <li class="divider"></li>
          <li><a id="embed-image" data-toggle="tab" href="#">Embed image</a></li>
          
        </ul>
      </li>
      {{/if}}
    </ul>
    <div class="tab-content" id="wiki-page-tabs">

      <div id="edit-pane" class="tab-pane fade">
        <div id="ace-editor"></div>
      </div>      
      <div id="preview-pane" class="tab-pane fade in active">
        <div id="wiki-preview" class="section-content-wrapper">
          {{$renderedContent}}
        </div>
      </div>
      <div id="page-history-pane" class="tab-pane fade" {{if $hidePageHistory}}style="display: none;"{{/if}}>
        <div id="page-history-list" class="section-content-wrapper">
        </div>
      </div>     


    </div>
  </div>
  {{if $showCommitMsg}}
  <div class="section-content-wrapper">
    {{include file="field_input.tpl" field=$commitMsg}}    
  </div>
  {{/if}}
</div>

{{$wikiModal}}


<div class="modal" id="embedPhotoModal" tabindex="-1" role="dialog" aria-labelledby="embedPhotoLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
        <h4 class="modal-title" id="embedPhotoModalLabel">{{$embedPhotosModalTitle}}</h4>
      </div>
     <div class="modal-body" id="embedPhotoModalBody" >
         <div id="embedPhotoModalBodyAlbumListDialog" class="hide">
            <div id="embedPhotoModalBodyAlbumList"></div>
         </div>
         <div id="embedPhotoModalBodyAlbumDialog" class="hide">
         </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">{{$embedPhotosModalCancel}}</button>
        <button id="embed-photo-OKButton" type="button" class="btn btn-primary">{{$embedPhotosModalOK}}</button>
      </div>
    </div><!-- /.modal-content -->
  </div><!-- /.modal-dialog -->
</div><!-- /.modal -->

<script>
  window.wiki_resource_id = '{{$resource_id}}';
  window.wiki_page_name = '{{$page}}';
  window.wiki_page_content = {{$content}};
  window.wiki_page_commit = '{{$commit}}';
  
  if (window.wiki_page_name === 'Home') {
    $('#delete-page').hide();
    $('#rename-page').hide();
  }
  
  $("#generic-modal-ok-{{$wikiModalID}}").removeClass('btn-primary');
  $("#generic-modal-ok-{{$wikiModalID}}").addClass('btn-danger');
  
  $('#rename-page').click(function (ev) {
    $('#rename-page-form-wrapper').show();
  });
  
  $( "#rename-page-form" ).submit(function( event ) {
    $.post("wiki/{{$channel}}/rename/page", 
      {
        oldName: window.wiki_page_name, 
        newName: $('#id_pageRename').val(), 
        resource_id: window.wiki_resource_id
      }, 
      function (data) {
      if (data.success) {
        $('#rename-page-form-wrapper').hide();
        window.console.log('data: ' + JSON.stringify(data));
        window.wiki_page_name = data.name.urlName;
        $('#wiki-header-page').html(data.name.htmlName);
        wiki_refresh_page_list();
      } else {
        window.console.log('Error renaming page.');
      }
      }, 'json');    
    event.preventDefault();
  });
  
  $(document).ready(function () {
    wiki_refresh_page_list();
    $("#wiki-toc").toc({content: "#wiki-preview", headings: "h1,h2,h3,h4"});
    // Show Edit tab first. Otherwise the Ace editor does not load.
    $("#wiki-nav-tabs li:eq(1) a").tab('show');
  });

  var editor = ace.edit("ace-editor");
  editor.setTheme("ace/theme/github");
  editor.getSession().setMode("ace/mode/markdown");
  editor.getSession().setValue(window.wiki_page_content);

  $('#wiki-get-preview').click(function (ev) {
    $.post("wiki/{{$channel}}/preview", {content: editor.getValue(), resource_id: window.wiki_resource_id}, function (data) {
      if (data.success) {
        $('#wiki-preview').html(data.html);
        $("#wiki-toc").toc({content: "#wiki-preview", headings: "h1,h2,h3,h4"});
      } else {
        window.console.log('Error previewing page.');
      }
    }, 'json');
    ev.preventDefault();
  });

  $('#wiki-get-history').click(function (ev) {
    $.post("wiki/{{$channel}}/history/page", {name: window.wiki_page_name, resource_id: window.wiki_resource_id}, function (data) {
      if (data.success) {
        $('#page-history-list').html(data.historyHTML);
      } else {
        window.console.log('Error getting page history.');
      }
    }, 'json');
    ev.preventDefault();
  });
  
function wiki_delete_wiki(wikiHtmlName, resource_id) {
  if(!confirm('Are you sure you want to delete the entire wiki: ' + JSON.stringify(wikiHtmlName))) {
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
    var currentContent = editor.getValue();
    if (window.wiki_page_content === currentContent) {
      window.console.log('No edits to save.');
      ev.preventDefault();
      return false;
    }
    $.post("wiki/{{$channel}}/save/page", 
      { content: currentContent, 
        commitMsg: $('#id_commitMsg').val(),
        name: window.wiki_page_name, 
        resource_id: window.wiki_resource_id
      }, 
      function (data) {
        if (data.success) {
          window.console.log('Page saved successfully.');
          window.wiki_page_content = currentContent;
          $('#id_commitMsg').val(''); // Clear the commit message box
          $('#wiki-get-history').click();
        } else {
          alert('Error saving page.'); // TODO: Replace alerts with auto-timeout popups 
          window.console.log('Error saving page.');
        }
      }, 'json');
    ev.preventDefault();
  });
  
    $('#delete-page').click(function (ev) {
    if (window.wiki_resource_id === '' || window.wiki_page_name === '' || window.wiki_page_name === 'Home') {
      window.console.log('You must have a wiki page open in order to delete pages.');
      ev.preventDefault();
      return false;
    }
    if(!confirm('Are you sure you want to delete the page: ' + window.wiki_page_name)) {
      return;
    }
    $.post("wiki/{{$channel}}/delete/page", {name: window.wiki_page_name, resource_id: window.wiki_resource_id}, 
      function (data) {
        if (data.success) {
          window.console.log('Page deleted successfully.');
          var url = window.location.href;
          if (url.substr(-1) == '/') url = url.substr(0, url.length - 2);
          url = url.split('/');
          url.pop();
          window.location = url.join('/');
        } else {
          alert('Error deleting page.'); // TODO: Replace alerts with auto-timeout popups 
          window.console.log('Error deleting page.');
        }
      }, 'json');
    ev.preventDefault();
  });
  
  function wiki_revert_page(commitHash) {
    if (window.wiki_resource_id === '' || window.wiki_page_name === '') {
      window.console.log('You must have a wiki page open in order to revert pages.');
      return false;
    }
    $.post("wiki/{{$channel}}/revert/page", {commitHash: commitHash, name: window.wiki_page_name, resource_id: window.wiki_resource_id}, 
      function (data) {
        if (data.success) {
          $('button[id^=revert-]').removeClass('btn-success');
          $('button[id^=revert-]').addClass('btn-danger');
          $('button[id^=revert-]').html('Revert');
          $('#revert-'+commitHash).removeClass('btn-danger');
          $('#revert-'+commitHash).addClass('btn-success');
          $('#revert-'+commitHash).html('Page reverted<br>but not saved');
          window.wiki_page_commit = commitHash;
          // put contents in editor
          editor.getSession().setValue(data.content);
        } else {
          window.console.log('Error reverting page.');
        }
      }, 'json');
  }
  
  function wiki_compare_page(compareCommit) {
    if (window.wiki_resource_id === '' || window.wiki_page_name === '' || window.wiki_page_commit === '') {
      window.console.log('You must have a wiki page open in order to revert pages.');
      return false;
    }
    $.post("wiki/{{$channel}}/compare/page", 
      {
        compareCommit: compareCommit, 
        currentCommit: window.wiki_page_commit, 
        name: window.wiki_page_name, 
        resource_id: window.wiki_resource_id
      }, 
      function (data) {
        if (data.success) {
          var modalBody = $('#generic-modal-body-{{$wikiModalID}}');
          modalBody.html('<div class="descriptive-text">'+data.diff+'</div>');
          $('.modal-dialog').width('80%');
          $("#generic-modal-ok-{{$wikiModalID}}").off('click');
          $("#generic-modal-ok-{{$wikiModalID}}").click(function () {
            wiki_revert_page(compareCommit);
            $('#generic-modal-{{$wikiModalID}}').modal('hide');
          });
          $('#generic-modal-{{$wikiModalID}}').modal();
        } else {
          window.console.log('Error comparing page.');
        }
      }, 'json');
  }
  
  $('#embed-image').click(function (ev) {
    initializeEmbedPhotoDialog();
    ev.preventDefault();
  });


    var initializeEmbedPhotoDialog = function () {
        $('.embed-photo-selected-photo').each(function (index) {
            $(this).removeClass('embed-photo-selected-photo');
        });
        getPhotoAlbumList();
        $('#embedPhotoModalBodyAlbumDialog').off('click');
        $('#embedPhotoModal').modal();
    };

    var choosePhotoFromAlbum = function (album) {
        $.post("embedphotos/album", {name: album},
            function(data) {
                if (data['status']) {
                    $('#embedPhotoModalLabel').html('{{$modalchooseimages}}');
                    $('#embedPhotoModalBodyAlbumDialog').html('\
                            <div><ul class="nav">\n\
                                <li><a href="#" onclick="initializeEmbedPhotoDialog();return false;">\n\
                                    <i class="fa fa-chevron-left"></i>&nbsp\n\
                                    {{$modaldiffalbum}}\n\
                                    </a>\n\
                                </li>\n\
                            </ul><br></div>')
                    $('#embedPhotoModalBodyAlbumDialog').append(data['content']);
                    $('#embedPhotoModalBodyAlbumDialog').click(function (evt) {
                        evt.preventDefault();
                        var image = document.getElementById(evt.target.id);
                        if (typeof($(image).parent()[0]) !== 'undefined') {
                            var imageparent = document.getElementById($(image).parent()[0].id);
                            $(imageparent).toggleClass('embed-photo-selected-photo');
                        }
                    });
                    $('#embedPhotoModalBodyAlbumListDialog').addClass('hide');
                    $('#embedPhotoModalBodyAlbumDialog').removeClass('hide');
                    $('#embed-photo-OKButton').click(function () {
                        $('.embed-photo-selected-photo').each(function (index) {
                            var href = $(this).attr('href');
                            $.post("embedphotos/photolink", {href: href},
                                function(ddata) {
                                    if (ddata['status']) {
                                      var imgURL = ddata['photolink'].replace( /\[.*\]\[.*\](.*)\[.*\]\[.*\]/, '\n![image]($1)' )
                                      editor.getSession().insert(editor.getCursorPosition(), imgURL)
                                    } else {
                                      window.console.log('{{$modalerrorlink}}' + ':' + ddata['errormsg']);
                                    }
                                    return false;
                                },
                            'json');
                        });
                        $('#embedPhotoModalBodyAlbumDialog').html('');
                        $('#embedPhotoModalBodyAlbumDialog').off('click');
                        $('#embedPhotoModal').modal('hide');
                    });
                } else {
                    window.console.log('{{$modalerroralbum}} ' + JSON.stringify(album) + ':' + data['errormsg']);
                }
                return false;
            },
        'json');
    };

    var getPhotoAlbumList = function () {
        $.post("embedphotos/albumlist", {},
            function(data) {
                if (data['status']) {
                    var albums = data['albumlist']; //JSON.parse(data['albumlist']);
                    $('#embedPhotoModalLabel').html('{{$modalchoosealbum}}');
                    $('#embedPhotoModalBodyAlbumList').html('<ul class="nav"></ul>');
                    for(var i=0; i<albums.length; i++) {
                        var albumName = albums[i].text;
                        var albumLink = '<li>';
                        albumLink += '<a href="#" onclick="choosePhotoFromAlbum(\'' + albumName + '\');return false;">' + albumName + '</a>';
                        albumLink += '</li>';
                        $('#embedPhotoModalBodyAlbumList').find('ul').append(albumLink);
                    }
                    $('#embedPhotoModalBodyAlbumDialog').addClass('hide');
                    $('#embedPhotoModalBodyAlbumListDialog').removeClass('hide');
                } else {
                    window.console.log('{{$modalerrorlist}}' + ':' + data['errormsg']);
                }
                return false;
            },
        'json');
    };
    
</script>
