(function (window, Model) {
	window.request = Model.initialize();
	window.opts = {};
}(window, window.Model));

(function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){(i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
})(window,document,'script','//www.google-analytics.com/analytics.js','ga');

ga('create', 'UA-55629503-3', 'auto');
ga('send', 'pageview');

var fbinit = false,
	thisVideoId = null,
	lyrics = false;

$(document).ready(function() {
	// facebook login
	$.ajaxSetup({ cache: true });
	$.getScript('//connect.facebook.net/en_US/sdk.js', getFBScript);

	$("a.playThisVideo").on("click", function (e) {
		e.preventDefault();

		var self = $(this),
			id = $(this).attr("data-id"),
			track = $(this).attr("data-track"),
			artist = $(this).attr("data-artist"),
			model = $("#play_video");

		self.addClass('disabled');
		if (id === undefined) {
			request.create({
				action: '/home/findTrack',
				data: {action: 'findTrack', track: track, artist: artist, videoType: "Official Video"},
				callback: function (data) {
					if (data != "Error") {
						self.removeClass('disabled');
						thisVideoId = data;
						self.attr("data-id", thisVideoId);
						embedId(data);
						model.modal('show');
					}
				}
			});	
		} else {
			self.removeClass('disabled');
			embedId(id);
			model.modal('show');
		}
		
	});

	$(".findLyrics").on("click", function (e) {
		e.preventDefault();
		var self = $(this);
		self.addClass('disabled');
		if (lyrics) {
			var el = $("#lyrics");
			if (!el.length) {
				$('.trackWiki').before('<div id="lyrics"></div>');
				el = $("#lyrics");
			}
			el.addClass('alert alert-default alert-dismissible fade in');
			el.attr('role', 'close');
			el.html('<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">×</span></button>' + lyrics);

			self.removeClass('disabled');
			return;
		}

		if (thisVideoId) {
			var yid = thisVideoId;
		} else {
			var yid = "";
		}
		$.ajax({
			url: '/home/findLyrics',
			type: 'POST',
			data: {action: 'findLyrics', track: $(this).data('track'), artist: $(this).data('artist'), mbid: $(this).data('mbid'), yid: yid}
		})
		.done(function(data) {
			self.removeClass('disabled');
			lyrics = data;
			var el = $("#lyrics");
			el.addClass('alert alert-default alert-dismissible fade in');
			el.attr('role', 'close');
			el.html('<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">×</span></button>' + data);
		})
		.fail(function() {
			self.removeClass('disabled');
			console.log("error");
		});
	});

	$("#fbLogin").on("click", function (e) {
		e.preventDefault();
		var self = $(this);
		self.addClass('disabled');
		if (!fbinit) {
			getFBScript();
		}
		isLoggedIn();
		self.removeClass('disabled');
	});

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

function isLoggedIn() {
	FB.getLoginStatus(function (response) {
		if (response.status === 'connected') {
			getFBInfo();	// User logged into fb and app
		} else {
			FB.login(function (response) {
				if (response.status === 'connected') {
					getFBInfo();
				} else {
					$('#alertMessage').html("You need to give access to playmusic.net");
					$('#alertModal').modal('show');
				}
			}, {scope: 'public_profile,email'});
		}
	});
}

function getFBInfo() {
	FB.api('/me?fields=name,email', function (response) {
		request.create({
			action: '/users/fbLogin',
			data: {action: 'fbLogin', email: response.email, name: response.name, token: $("#accessToken").attr("value")},
			callback: function (data) {
				if (data == "Success") {
					window.location.href = "/profile";
				} else {
					$('#alertMessage').html("Something went wrong");
					$('#alertModal').modal('show');
				}
			}
		});
	});
}