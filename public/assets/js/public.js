(function (window, Model) {
    window.request = Model.initialize();
    window.opts = {};
}(window, window.Model));

var artists = '/artists/';

$(document).ready(function() {
	$("a.play.text-ellipsis").on("click", function (e) {
		e.preventDefault();

		var src = "https://www.youtube.com/embed/" + $(this).attr("data-id");
		
		var iframe = $("#embedIt").attr("src", src);
	});
	
});