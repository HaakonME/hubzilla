<div class="generic-content-wrapper">
	<div class="section-title-wrapper">
		<div class="pull-right">
			<button class="btn btn-success btn-xs" onclick="openClose('form');">{{$addrepo}}</button>
		</div>
		<h2 id="title">{{$title}} - {{$page}}</h2>
		<div class="clear"></div>
	</div>
	<div id="form" class="section-content-tools-wrapper"{{if !$expandform}} style="display:none;"{{/if}}>
		{{$form}}
	</div>
	<div class="clear"></div>
    <div id="chat-rotator-wrapper" class="center-block">
        <div id="chat-rotator"></div>
    </div>
    <div class="clear"></div>
    <div class="section-content-info-wrapper">
      <h3>Installed Addon Repositories</h3>
      {{foreach $addonrepos as $repo}}
      <div class="section-content-tools-wrapper">	
		<div>
          <div class="pull-left">{{$repo.name}}</div>
          <!--<button class="btn btn-xs btn-primary pull-right" onclick="switchAddonRepoBranch('{{$repo.name}}'); return false;">{{$repoBranchButton}}</button>-->
          <button class="btn btn-xs btn-danger pull-right" onclick="removeAddonRepo('{{$repo.name}}'); return false;"><i class='fa fa-trash-o'></i>&nbsp;{{$repoRemoveButton}}</button>
          &nbsp;
          <button class="btn btn-xs btn-success pull-right" onclick="updateAddonRepo('{{$repo.name}}'); return false;"><i class='fa fa-download'></i>&nbsp;{{$repoUpdateButton}}</button>
		</div>
      </div>
      <div class="clear"></div>
      {{/foreach}}
    </div>
	<div class="section-content-wrapper-np">
      {{foreach $plugins as $p}}
      <div class="section-content-tools-wrapper" id="pluginslist">		
		<div class="contact-info plugin {{$p.1}}">
            {{if ! $p.2.disabled}}				
            <a class='toggleplugin' href='{{$baseurl}}/admin/{{$function}}/{{$p.0}}?a=t&amp;t={{$form_security_token}}' title="{{if $p.1==on}}Disable{{else}}Enable{{/if}}" ><i class='fa {{if $p.1==on}}fa-check-square-o{{else}}fa-square-o{{/if}} admin-icons'></i></a>
            {{else}}
            <i class='fa fa-stop admin-icons'></i>
            {{/if}}
            <a href='{{$baseurl}}/admin/{{$function}}/{{$p.0}}'><span class='name'>{{$p.2.name}}</span></a> - <span class="version">{{$p.2.version}}</span>{{if $p.2.disabled}} {{$disabled}}{{/if}}
            {{if $p.2.experimental}} {{$experimental}} {{/if}}{{if $p.2.unsupported}} {{$unsupported}} {{/if}}

            <div class='desc'>{{$p.2.description}}</div>
		</div>
	</div>
    {{/foreach}}
      
	</div>
</div>
{{$newRepoModal}}
<script>
  
  function adminPluginsAddRepo() {
      var repoURL = $('#id_repoURL').val();
      var repoName = $('#id_repoName').val();
      $('#chat-rotator').spin('tiny');
      $.post(
        "/admin/plugins/addrepo", {repoURL: repoURL, repoName: repoName}, 
            function(response) {
                $('#chat-rotator').spin(false);
                if (response.success) {
                  var modalBody = $('#generic-modal-body-{{$newRepoModalID}}');
                  modalBody.html('<div>'+response.repo.readme+'</div>');
                  modalBody.append('<h2>Repo Info</h2><p>Message: ' + response.message + '</p>');
                  modalBody.append('<h4>Branches</h4><p>'+JSON.stringify(response.repo.branches)+'</p>');
                  modalBody.append('<h4>Remotes</h4><p>'+JSON.stringify(response.repo.remote)+'</p>');
                  $('.modal-dialog').width('80%');
                  $("#generic-modal-ok-{{$newRepoModalID}}").click(function () {
                    installAddonRepo();
                  });
                  $('#generic-modal-{{$newRepoModalID}}').modal();
                } else {
                    window.console.log('Error adding repo :' + response['message']);
                }
                return false;
            },
        'json');
  }
  
  function installAddonRepo() {
    // TODO: Link store/git/sys/reponame to /extend/addon/ and run util/add_addon_repo script
      var repoURL = $('#id_repoURL').val();
      var repoName = $('#id_repoName').val();
      $.post(
        "/admin/plugins/installrepo", {repoURL: repoURL, repoName: repoName}, 
            function(response) {
                if (response.success) {
                  $('#generic-modal-title-{{$newRepoModalID}}').html('Addon repo installed');
                  var modalBody = $('#generic-modal-body-{{$newRepoModalID}}');
                  modalBody.html('<h2>Repo Info</h2><p>Message: ' + response.message + '</p>');
                  modalBody.append('<h4>Branches</h4><p>'+JSON.stringify(response.repo.branches)+'</p>');
                  modalBody.append('<h4>Remotes</h4><p>'+JSON.stringify(response.repo.remote)+'</p>');
                  $('.modal-dialog').width('80%');
                  //$("#generic-modal-cancel-{{$newRepoModalID}}").hide();
                  $("#generic-modal-ok-{{$newRepoModalID}}").html('OK');
                  $("#generic-modal-ok-{{$newRepoModalID}}").off('click');
                  $("#generic-modal-ok-{{$newRepoModalID}}").click(function () {
                    $('#generic-modal-{{$newRepoModalID}}').modal('hide');
                    location.reload();
                  });
                  $('#generic-modal-{{$newRepoModalID}}').modal();
              
                } else {
                    window.console.log('Error installing repo :' + response['message']);
                }
                return false;
            },
        'json');
  }
  function updateAddonRepo(repoName) {
    window.console.log('updateAddonRep:; ' + repoName);
    // TODO: Update an existing repo
    if(confirm('Are you sure you want to update the addon repo ' + repoName + '?')) {
      $.post(
        "/admin/plugins/updaterepo", {repoName: repoName}, 
            function(response) {
                if (response.success) {
                  window.console.log('Addon repo'+repoName+'successfully updated :' + response['message']);
                  alert('Repo updated');
                } else {
                  window.console.log('Error installing repo :' + response['message']);
                }
                return false;
            },
        'json');
    }
  }
  function switchAddonRepoBranch(repoName) {
    window.console.log('switchAddonRepoBranch: ' + repoName);
    // TODO: Discover the available branches and create an interface to switch between them
  }
  function removeAddonRepo(repoName) {
    window.console.log('removeAddonRepo: ' + repoName);
    // TODO: Unlink the addons
    if(confirm('Are you sure you want to remove the addon repo ' + repoName + '?')) {
      $.post(
        "/admin/plugins/removerepo", {repoName: repoName}, 
            function(response) {
                if (response.success) {
                  window.console.log('Addon repo'+repoName+'successfully removed :' + response['message']);
                  alert('Repo deleted');
                } else {
                  window.console.log('Error installing repo :' + response['message']);
                }
                return false;
            },
        'json');
    }
  }  
  
</script>