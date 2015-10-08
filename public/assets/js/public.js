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

		var self = $(this),
			id = $(this).attr("data-id"),
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
				        self.attr("data-id", thisVideoId);
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

	// $("#searchMusic").on("submit", function (e) {
	// 	e.preventDefault();
	// 	data = $(this).serialize();

	// 	request.create({
	// 		action: '/home/searchMusic',
	// 		data: data,
	// 		callback: function (data) {
	// 			console.log(data);
	// 		}
	// 	});
	// })
});

function embedId(id) {
	var src = "https://www.youtube.com/embed/" + id;
	$("#embedIt").attr("src", src);
}