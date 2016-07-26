/**
 * JavaScript for mod/cloud
 */

$(document).ready(function () {
  // call initialization file
  if (window.File && window.FileList && window.FileReader) {
    DragDropUploadInit();
  }
});

//
// initialize
function DragDropUploadInit() {

  var fileselect = $("#fileselect"),
          filedrag = $("#filedrag");

  // file select
  fileselect.on("change", DragDropUploadFileSelectHandler);

  // is XHR2 available?
  var xhr = new XMLHttpRequest();
  if (xhr.upload) {

    // file drop
    filedrag.on("dragover", DragDropUploadFileHover);
    filedrag.on("dragleave", DragDropUploadFileHover);
    filedrag.on("drop", DragDropUploadFileSelectHandler);
    filedrag.show();

  }

  window.filesToUpload = 0;
  window.fileUploadsCompleted = 0;


}

// file drag hover
function DragDropUploadFileHover(e) {
  e.stopPropagation();
  e.preventDefault();
  e.target.className = (e.type == "dragover" ? "hover" : "");
}

// file selection
function DragDropUploadFileSelectHandler(e) {

  // cancel event and hover styling
  DragDropUploadFileHover(e);

  // fetch FileList object
  var files = e.target.files || e.originalEvent.dataTransfer.files;
  $("#file-upload-list").empty();
  // process all File objects
  for (var i = 0, f; f = files[i]; i++) {
    $("#file-upload-list").append(
            "<p>" + "<span id='upload-progress-" + i + "'></span> -> File: <strong>" + f.name +
            "</strong> type: <strong>" + f.type +
            "</strong> size: <strong>" + f.size +
            "</strong> bytes</p>"
            );
    DragDropUploadFile(f, i);
  }

}

// upload  files
function DragDropUploadFile(file, idx) {

  window.filesToUpload = window.filesToUpload + 1;

  var xhr = new XMLHttpRequest();
  xhr.withCredentials = true;   // Include the SESSION cookie info for authentication
  (xhr.upload || xhr).addEventListener('progress', function (e) {
    var done = e.position || e.loaded;
    var total = e.totalSize || e.total;
    // Dynamically update the percentage complete displayed in the file upload list
    $('#upload-progress-' + idx).html(Math.round(done / total * 100) + '%');
  });
  xhr.addEventListener('load', function (e) {
    //console.log('xhr upload complete', e);
    window.fileUploadsCompleted = window.fileUploadsCompleted + 1;
    // When all the uploads have completed, refresh the page
    if (window.filesToUpload > 0 && window.fileUploadsCompleted === window.filesToUpload) {
      window.fileUploadsCompleted = window.filesToUpload = 0;
      // After uploads complete, refresh browser window to display new files
      window.location.href = window.location.href;
    }
  });
  // POST to the entire cloud path 
  xhr.open('post', window.location.pathname, true);

  var data = new FormData(document.getElementById("ajax-upload-files"));
  data.append('file[]', file);
  xhr.send(data);
}
