function downloadJS () {

	// console.log('downloadJS');
/*	var script = '<script ' + scriptHead + '>' + scriptBody + '</script>';
	$(document).ready(function() {
		$("body").append(script);
		console.log('script added');
	});
*/
	downloadScripts.forEach(function(item, i, arr) {
		// console.log( i + ": " + item);
		var script = '<script ' + item[0] + '>' + item[1] + '</script>';
		$("head").append(script);
		console.log('script added');
	});
}

/*$(document).ready(function() {
	console.log('run downloadJS');
	downloadJS();
});
*/