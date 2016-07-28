/**
 * JavaScript for mod/cloud
 */

$(document).ready(function () {
	// call initialization file
	if (window.File && window.FileList && window.FileReader) {
		UploadInit();
	}
});

//
// initialize
function UploadInit() {

	var fileselect = $("#files-upload");
	var filedrag = $("#cloud-drag-area");
	var submit = $("#upload-submit");

	// is XHR2 available?
	var xhr = new XMLHttpRequest();
	if (xhr.upload) {

		// file select
		fileselect.on("change", UploadFileSelectHandler);

		// file submit
		submit.on("click", fileselect, UploadFileSelectHandler);

		// file drop
		filedrag.on("dragover", DragDropUploadFileHover);
		filedrag.on("dragleave", DragDropUploadFileHover);
		filedrag.on("drop", DragDropUploadFileSelectHandler);
	}

	window.filesToUpload = 0;
	window.fileUploadsCompleted = 0;
}

// file drag hover
function DragDropUploadFileHover(e) {
	e.stopPropagation();
	e.preventDefault();
	e.currentTarget.className = (e.type == "dragover" ? "hover" : "");
}

// file selection via drag/drop
function DragDropUploadFileSelectHandler(e) {
	// cancel event and hover styling
	DragDropUploadFileHover(e);

	// fetch FileList object
	var files = e.target.files || e.originalEvent.dataTransfer.files;

	$('.new-upload').remove();

	// process all File objects
	for (var i = 0, f; f = files[i]; i++) {
		prepareHtml(f, i);
		UploadFile(f, i);
	}
}

// file selection via input
function UploadFileSelectHandler(e) {
	// fetch FileList object
	if(e.type === 'click') {
		e.preventDefault();
		var files = e.data[0].files;
	}
	else {
		var files = e.target.files;
	}

	$('.new-upload').remove();

	// process all File objects
	for (var i = 0, f; f = files[i]; i++) {
		prepareHtml(f, i);
		if(e.type === 'click')
			UploadFile(f, i);
	}
}

function prepareHtml(f, i) {
	$("#cloud-index tr:nth-child(2)").after(
		'<tr id=\"new-upload-' + i + '\" class=\"new-upload\" style=\"background: url(\'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mOM2RHTAwAE5gH+VJ1dlgAAAABJRU5ErkJggg==\') repeat-y; background-size: 0%;\">' +
		'<td><i class=\"fa ' + getIconFromType(f.type) + '\" title=\"' + f.type + '\"></i></td>' +
		'<td>' + f.name + '</td>' +
		'<td id=\"upload-progress-' + i + '\"></td><td></td><td></td><td></td><td></td>' +
		'<td class=\"hidden-xs\">' + formatSizeUnits(f.size) + '</td><td class=\"hidden-xs\"></td>' +
		'</tr>'
	);
}

function formatSizeUnits(bytes){
        if      (bytes>=1000000000) {bytes=(bytes/1000000000).toFixed(2)+' GB';}
        else if (bytes>=1000000)    {bytes=(bytes/1000000).toFixed(2)+' MB';}
        else if (bytes>=1000)       {bytes=(bytes/1000).toFixed(2)+' KB';}
        else if (bytes>1)           {bytes=bytes+' bytes';}
        else if (bytes==1)          {bytes=bytes+' byte';}
        else                        {bytes='0 byte';}
        return bytes;
}

// this is basically a js port of include/text.php getIconFromType() function
function getIconFromType(type) {
	var map = {
		//Common file
		'application/octet-stream': 'fa-file-o',
		//Text
		'text/plain': 'fa-file-text-o',
		'application/msword': 'fa-file-word-o',
		'application/pdf': 'fa-file-pdf-o',
		'application/vnd.oasis.opendocument.text': 'fa-file-word-o',
		'application/epub+zip': 'fa-book',
		//Spreadsheet
		'application/vnd.oasis.opendocument.spreadsheet': 'fa-file-excel-o',
		'application/vnd.ms-excel': 'fa-file-excel-o',
		//Image
		'image/jpeg': 'fa-picture-o',
		'image/png': 'fa-picture-o',
		'image/gif': 'fa-picture-o',
		'image/svg+xml': 'fa-picture-o',
		//Archive
		'application/zip': 'fa-file-archive-o',
		'application/x-rar-compressed': 'fa-file-archive-o',
		//Audio
		'audio/mpeg': 'fa-file-audio-o',
		'audio/wav': 'fa-file-audio-o',
		'application/ogg': 'fa-file-audio-o',
		'audio/ogg': 'fa-file-audio-o',
		'audio/webm': 'fa-file-audio-o',
		'audio/mp4': 'fa-file-audio-o',
		//Video
		'video/quicktime': 'fa-file-video-o',
		'video/webm': 'fa-file-video-o',
		'video/mp4': 'fa-file-video-o',
		'video/x-matroska': 'fa-file-video-o'
	};

	var iconFromType = 'fa-file-o';

	if (type in map) {
		iconFromType = map[type];
	}

	return iconFromType;
}

// upload  files
function UploadFile(file, idx) {

	window.filesToUpload = window.filesToUpload + 1;

	var xhr = new XMLHttpRequest();

	xhr.withCredentials = true;   // Include the SESSION cookie info for authentication

	(xhr.upload || xhr).addEventListener('progress', function (e) {
		var done = e.position || e.loaded;
		var total = e.totalSize || e.total;
		// Dynamically update the percentage complete displayed in the file upload list
		$('#upload-progress-' + idx).html(Math.round(done / total * 100) + '%');
		$('#new-upload-' + idx).css('background-size', Math.round(done / total * 100) + '%');
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

	data.append('file', file);

	xhr.send(data);
}
