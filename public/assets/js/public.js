(function (window, Model) {
	window.request = Model.initialize();
	window.opts = {};
}(window, window.Model));

(function (i, s, o, g, r, a, m) {i['GoogleAnalyticsObject'] = r;i[r] = i[r] || function () {(i[r].q = i[r].q || []).push(arguments)}, i[r].l = 1 * new Date();a = s.createElement(o), m = s.getElementsByTagName(o)[0];	a.async = 1;	a.src = g;m.parentNode.insertBefore(a, m)})(window, document, 'script', '//www.google-analytics.com/analytics.js', 'ga');
ga('create', 'UA-55629503-3', 'auto');
ga('send', 'pageview');

/*** Home Controller ****/
(function (window, jQ) {
	var Home = (function () {
		function Home() {
      var main = this;
			this.bootbox = {
				_modal: jQ('#alertModal'),
				_message: jQ('#alertMessage'),
				alert: function (msg) {
					this._message.html(msg);
					this._modal.modal('show');
				}
			};

			this.video = {
				_start: function (id) {
					jQ('#embedIt').attr('src', "https://www.youtube.com/embed/" + id);
					jQ('#play_video').modal('show');
				},
        hide: function () {
          jQ('#play_video').modal('hide');
        },
				play: function (selector) {
					var self = this,
						id = selector.attr('data-yid');
					
					if (id === undefined) {
						window.request.create({
							action: 'home/findTrack',
							data: { action: 'findTrack', track: selector.attr('data-track'), artist: selector.attr('data-artist'), videoType: "Official Video" },
							callback: function (data) {
								selector.removeClass('disabled');
								if (data != "Error") {
									selector.attr("data-yid", data);
									self._start(data);
								} else {
									main.bootbox.alert('Something went wrong');
								}
							}
						});
					} else {
						selector.removeClass('disabled');
						self._start(id);
					}
				}
			};

			this.download = {
				_modal: jQ("#downloadModal"),
				_btn: jQ("#startDownloading"),
        _check: function (opts) {
          // This means user is downloading a video not a song from playlist
          if (opts.type !== 'playlist' && !opts.artist) {
            var result = opts.track.split('|');
            if (result.length > 1) {
              opts.artist = result.pop().trim();
              opts.track = result.join('');  
            } else {
              opts.artist = 'Video Song';
            }
          }
          return opts;
        },
				init: function (opts) {
          opts = this._check(opts);
					this._btn.data('yid', opts.yid);
					this._btn.data('track', opts.track);
					this._btn.data('artist', opts.artist);
					this._btn.data('mbid', opts.mbid);
          this._btn.data('type', opts.type);

          this._modal.find('.modal-title').html(opts.artist + ' - ' + opts.track);
          if (opts.type == 'playlist') {
            this._modal.modal('show');  
          }
				},
        start: function () {
          var self = this;

          self._btn.html('<i class="fa fa-spinner fa-spin"></i> Please Wait...');
          window.request.create({
              action: 'home/download/' + this._btn.data('yid') + '/'  + this._btn.data('track'),
              data: {action: 'downloadMusic', track: this._btn.data('track'), artist: this._btn.data('artist'), mbid: this._btn.data('mbid')},
              callback: function (data) {
                  self._btn.html('<i class="fa fa-download"></i> Download');
                  self._modal.modal('hide');

                  if (data == "Success") {
                    window.location.href = '/home/download/' + self._btn.data('yid') + '/' + self._btn.data('track').replace(/\./g,'');
                  } else {
                    main.bootbox.alert(data);
                  }
              }
          });
        }
			};
		}

		Home.prototype = {

		};
		return Home;
	}());

	window.Home = new Home();
}(window, $));

/**** FbModel: Controls facebook login/authentication ******/
(function (window, Home) {
	var FbModel = (function () {
		function FbModel() {
			this.loaded = false;
		}

		FbModel.prototype = {
			init: function (FB) {
				if (!FB) { return false; }

				FB.init({
					appId: '755804614543052',
					version: 'v2.4'
				});
				this.loaded = true;
			},
			login: function (jQ) {
				var self = this;
				if (!this.loaded) {
					self.init(window.FB);
				}
				window.FB.getLoginStatus(function (response) {
					if (response.status === 'connected') {
						self._info(jQ); // User logged into fb and app
					} else {
						window.FB.login(function (response) {
							if (response.status === 'connected') {
								self._info(jQ);
							} else {
								Home.bootbox.alert('You need to give access to playmusic.net');
							}
						}, {
							scope: 'public_profile, email'
						});
					}
				});
			},
			_info: function (jQ) {
				window.FB.api('/me?fields=name,email', function (response) {
					window.request.create({
						action: 'users/fbLogin',
						data: { action: 'fbLogin', email: response.email,	name: response.name, token: jQ("#accessToken").attr("value") },
						callback: function (data) {
							if (data == "Success") {
								window.location.href = "/profile";
							} else {
								Home.bootbox.alert('Something went wrong');
							}
						}
					});
				});
			}
		};
		return FbModel;
	}());

	window.FbModel = new FbModel();
}(window, window.Home));

var	lyrics = false, thisVideoId = null;

$(document).ready(function () {
	$.ajaxSetup({ cache: true });
	$.getScript('//connect.facebook.net/en_US/sdk.js', FbModel.init(window.FB));

	$("a.playThisVideo").on("click", function (e) {
		e.preventDefault();
    var self = $(this);
		Home.video.play(self);
    Home.download.init({track: self.data('track'), artist: self.data('artist'), yid: self.data('yid'), mbid: self.data('mbid'), type: 'video'});
	});

  $('#download-video').on('click', function (e) {
    e.preventDefault();
    Home.video.hide();
    Home.download._modal.modal('show');
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
				data: { action: 'findLyrics', track: $(this).data('track'), artist: $(this).data('artist'),	mbid: $(this).data('mbid'), yid: yid }
			})
			.done(function (data) {
				self.removeClass('disabled');
				lyrics = data;
				var el = $("#lyrics");
				el.addClass('alert alert-default alert-dismissible fade in');
				el.attr('role', 'close');
				el.html('<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">×</span></button>' + data);
			})
			.fail(function () {
				self.removeClass('disabled');
				console.log("error");
			});
	});

	$("#fbLogin").on("click", function (e) {
		e.preventDefault();

		$(this).addClass('disabled');
		FbModel.login($);
		$(this).removeClass('disabled');
	});
});
