(function (window, Model) {
    window.request = Model.initialize();
    window.opts = {};
}(window, window.Model));

var thisVideoId = null;

$(document).ready(function() {
	$("a.play.text-ellipsis").on("click", function (e) {
		e.preventDefault();

		embedId($(this).attr("data-id"));
	});

	$("a.playThisVideo").on("click", function (e) {
		e.preventDefault();

		var id = $(this).attr("data-id"),
			track = $(this).attr("data-track"),
			artist = $(this).attr("data-artist"),
			model = $("#play_video");

		if (id === undefined) {
			request.create({
				action: '/home/findTrack',
				data: {action: 'findTrack', track: track, artist: artist},
				callback: function (data) {
				    if (data != "Error") {
				        thisVideoId = data;
				        embedId(data);
				       	model.modal('show');
				    }
				}
			});	
		} else {
			embedId(thisVideoId);
			model.modal('show');
		}
		
	});
});

function embedId(id) {
	var src = "https://www.youtube.com/embed/" + id;
	$("#embedIt").attr("src", src);
}