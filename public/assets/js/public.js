(function (window, Model) {
	window.request = Model.initialize();
	window.opts = {};
}(window, window.Model));

var fbinit = false;
var thisVideoId = null;

$(document).ready(function() {
	// facebook login
	$.ajaxSetup({ cache: true });
	$.getScript('//connect.facebook.net/en_US/sdk.js', getFBScript);


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

	$("#fbLogin").on("click", function (e) {
		e.preventDefault();
		var token = $("#accessToken").attr("value");
		if (!fbinit) {
			getFBScript();
		}
		isLoggedIn(token);
	})
});

function embedId(id) {
	var src = "https://www.youtube.com/embed/" + id;
	$("#embedIt").attr("src", src);
}

function getFBScript() {
	FB.init({
		appId: '755804614543052',
		version: 'v2.4'
	});
	fbinit = true;
}

function isLoggedIn(token) {
	FB.getLoginStatus(function (response) {
		if (response.status === 'connected') {
			getFBInfo(token);	// User logged into fb and app
		} else {
			FB.login(function (response) {
				if (response.status === 'connected') {
					getFBInfo(token);
				} else {
					// alert the user
					alert("You need to give access to playmusic.net");
				}
			}, {scope: 'public_profile,email'});
		}
	});
}

function getFBInfo(token) {
	FB.api('/me?fields=name,email', function (response) {
		request.create({
			action: '/users/fbLogin',
			data: {action: 'fbLogin', email: response.email, name: response.name, token: token},
			callback: function (data) {
				if (data == "Success") {
					window.location.href = "/profile";
				} else {
					// @todo replace with alert modal
					alert('Something went wrong');
				}
			}
		});
	});
}