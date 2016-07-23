<div id="files-mkdir-tools" class="section-content-tools-wrapper">
	<label for="files-mkdir">{{$folder_header}}</label>
	<form method="post" action="">
		<input type="hidden" name="sabreAction" value="mkcol">
		<input id="files-mkdir" type="text" name="name" class="form-control form-group">
		<button class="btn btn-primary btn-sm pull-right" type="submit" value="{{$folder_submit}}">{{$folder_submit}}</button>
	</form>
	<div class="clear"></div>
</div>
<div id="files-upload-tools" class="section-content-tools-wrapper">
	{{if $quota.limit || $quota.used}}<div class="{{if $quota.warning}}section-content-danger-wrapper{{else}}section-content-info-wrapper{{/if}}">{{if $quota.warning}}<strong>{{$quota.warning}} </strong>{{/if}}{{$quota.desc}}</div>{{/if}}
	<!--
    <label for="files-upload">{{$upload_header}}</label>
	<form method="post" action="" enctype="multipart/form-data">
		<input type="hidden" name="sabreAction" value="put">
		<input class="form-group" id="files-upload" type="file" name="file">
		<button class="btn btn-primary btn-sm pull-right" type="submit" value="{{$upload_submit}}">{{$upload_submit}}</button>
	</form>
	<div class="clear"></div>
    -->
    <form id="ajax-upload-files" action="" method="POST" enctype="multipart/form-data">
		<input type="hidden" name="sabreAction" value="put">

      <fieldset>

      <input type="hidden" name="sabreAction" value="put">
      <div>
          <label for="fileselect">Files to upload:</label>
<!--          <input type="file" id="fileselect" name="fileselect[]" multiple="multiple" />-->
<!--          <input type="file" id="fileselect" name="fileselect[]"  />-->
          <div id="filedrag">or drop files here</div>
      </div>

      <div id="submitbutton">
          <button type="submit">Upload Files</button>
      </div>

      </fieldset>
    <div id="file-upload-list"></div>
    
    <div id="profile-rotator-wrapper">
        <div id="profile-rotator"></div>
    </div>
    </form>
    <div class="clear"></div>
</div>

<style>  

  #filedrag
  {
      display: none;
      font-weight: bold;
      text-align: center;
      padding: 1em 0;
      margin: 1em 0;
      color: #555;
      border: 2px dashed #555;
      border-radius: 7px;
      cursor: default;
  }

  #filedrag.hover
  {
      color: #f00;
      border-color: #f00;
      border-style: solid;
      box-shadow: inset 0 3px 4px #888;
  }

</style>

<script>
$(document).ready(function() {  
//  
//    try {
//        var file_uploader = new window.AjaxUpload('submitbutton',
//        { action: 'cloud',
//            name: 'userfile',
//            title: 'Upload',
//            onSubmit: function(file,ext) { $('#profile-rotator').spin('tiny'); },
//            onComplete: function(file,response) {
//                $("#file-upload-list").append("Upload complete!");
//                $('#profile-rotator').spin(false);
//            }
//        });
//    } catch(e) {
//    }
    // call initialization file
    if (window.File && window.FileList && window.FileReader) {
        DragDropUploadInit();
    }
});

//
// initialize
function DragDropUploadInit() {

	var fileselect = $("#fileselect"),
		filedrag = $("#filedrag"),
		submitbutton = $("#submitbutton");

	// file select
	fileselect.on("change", FileSelectHandler);

	// is XHR2 available?
	var xhr = new XMLHttpRequest();
	if (xhr.upload) {
	
		// file drop
		filedrag.on("dragover", FileDragHover);
		filedrag.on("dragleave", FileDragHover);
		filedrag.on("drop", FileSelectHandler);
		filedrag.show();
		
		// remove submit button
		submitbutton.hide();
	}
    

}

// file drag hover
function FileDragHover(e) {
	e.stopPropagation();
	e.preventDefault();
	e.target.className = (e.type == "dragover" ? "hover" : "");
}

// file selection
function FileSelectHandler(e) {

	// cancel event and hover styling
	FileDragHover(e);

	// fetch FileList object
	var files = e.target.files || e.originalEvent.dataTransfer.files;
    $("#file-upload-list").empty();
	// process all File objects
	for (var i = 0, f; f = files[i]; i++) {
		$("#file-upload-list").append(
          "<p>File information: <strong>" + f.name +
          "</strong> type: <strong>" + f.type +
          "</strong> size: <strong>" + f.size +
          "</strong> bytes</p>"
        );
        DragDropUploadFile(f);
	}

}

// upload  files
function DragDropUploadFile(file) {

    // Serialize the data in the form
    //var serializedData = $('#ajax-upload-files').serialize();
//
//    $.ajax({
//      type: "POST",
//      url: '',
//      data: serializedData,
//      success: function(result){
//        //window.console.log(result);
//        return false;
//      }
//    });
//    

    var xhr = new XMLHttpRequest();
    (xhr.upload || xhr).addEventListener('progress', function(e) {
        var done = e.position || e.loaded
        var total = e.totalSize || e.total;
        console.log('xhr progress: ' + Math.round(done/total*100) + '%');
    });
    xhr.addEventListener('load', function(e) {
        //console.log('xhr upload complete', e, this.responseText);
        console.log('xhr upload complete', e);
    });
    xhr.open('post', 'cloud', true);
	
    var data = new FormData(document.getElementById("ajax-upload-files"));
    data.append('file', file);
    xhr.send(data);
//
//	var xhr = new XMLHttpRequest();
//	if (xhr.upload) {
//		// start upload
//        window.console.log("Uploading...");
//		xhr.open("POST", $("#ajax-upload-files").action, true);
//		xhr.setRequestHeader("X_FILENAME", file.name);
//		xhr.send(file);
//
//	}

}


</script>