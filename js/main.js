window.onload = function(){
var output = document.getElementById('output');

	// Detect when the value of the files input changes.
	document.getElementById('files').onchange = function(e) {
		// Retrieve the file list from the input element
		uploadFiles(e.target.files);
		
		// Outputs file names to div id "output"
        output.innerText = "";
		for (var i in e.target.files)
			output.innerText  = output.innerText + e.target.files[i].webkitRelativePath+"\n";
	}
}


function uploadFiles(files){

	// Create a new HTTP requests, Form data item (data we will send to the server) and an empty string for the file paths.
	xhr = new XMLHttpRequest();
	data = new FormData();
	paths = "";
	

	// Set how to handle the response text from the server
	xhr.onreadystatechange = function(ev){
		console.debug(xhr.responseText);
	};
	
	// Loop through the file list
	for (var i in files){
		// Append the current file path to the paths variable (delimited by tripple hash signs - ###)
		paths += files[i].webkitRelativePath+"###";
		// Append current file to our FormData with the index of i
		data.append(i, files[i]);
	};
	console.log('start of console: ');
	console.log(paths);
	console.log('END of console...');
	// Append the paths variable to our FormData to be sent to the server
	// Currently, As far as I know, HTTP requests do not natively carry the path data
	// So we must add it to the request manually.


	data.append('paths', paths);
		
	// Open and send HHTP requests to upload.php
	xhr.open('POST', "upload.php", true);
	xhr.send(this.data);
	
}