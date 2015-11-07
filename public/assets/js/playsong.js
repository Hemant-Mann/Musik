(function (window, Model) {
    window.request = Model.initialize();
    window.opts = {};
}(window, window.Model));

var ytplayer = "",
    index = -1,
    playlist = [],
    currentTime = '',
    duration = '',
    timerOn = false,
    tVar,
    alsoPlay = false,
    playlistItems = false;

function emptyPlaylist() {
    if (playlist.length === 0) {
        return true;
    }
    return false;
}

// initialize YTPlayer
function onYouTubeIframeAPIReady() {
    ytplayer = new YT.Player('ytplayer', {
        height: '0',
        width: '0',
        events: {
            'onStateChange': onPlayerStateChange
        }
    });
}

function isYTPlayer() {
    if (typeof ytplayer !== 'object') {
        return false;
    } else {
        return true;
    }
}

function startTimer() {
    timerOn = true;
    updateProgressBar();
}

function stopTimer() {
    timerOn = false;
    clearTimeout(tVar);
}

function mmss(sec) {
    var min = sec / 60 | 0,
        seconds = (sec - min * 60) | 0;

    if (min < 10) {
        min = "0" + min;
    }
    if (seconds < 10) {
        seconds = "0" + seconds;
    }
    var time = min + ":" + seconds;
    return time;
}

function initjPlayer(track) {
    $('.jp-title').html(track);
    $(".jp-play-bar").width('0%');

    $(".jp-play").css({ display: 'none' });
    $(".jp-pause").css({ display: 'inline-block' });
    duration.html(mmss(ytplayer.getDuration()));
}

function stopjPlayer() {
    $('.jp-title').html("");
    $(".jp-play-bar").width('0%');

    $(".jp-current-time").html("00:00");
    duration.html("00:00");
    $(".jp-play").css({ display: 'inline-block' });
    $(".jp-pause").css({ display: 'none' });       
}

function updateProgressBar() {
    if (!isYTPlayer()) {
        return false;
    }

    if (!timerOn) {
        return false;
    }
    var cTime = ytplayer.getCurrentTime(),
        dur = ytplayer.getDuration(),
        pos = (cTime * 100) / dur;

    currentTime.html(mmss(cTime));
    duration.html(mmss(dur));
    $(".jp-play-bar").width(pos + "%");

    tVar = setTimeout(function () {
        updateProgressBar()
    }, 500);
}

/** Starts the player **/
function startPlayback(id) {
    ytplayer.loadVideoById(id, 0, "small");
    ytplayer.setPlaybackQuality("small");
    ytplayer.playVideo();
}

/** Play the Given track **/
function playThis(track, playingIndex) {
    if (!isYTPlayer() || emptyPlaylist()) {
        return false;
    }

    index = playingIndex;
    var x = $('#activeTrack');
    if (x) {
        x.removeClass('active');
        x.attr('id', '');
    }
    
    x = playlistItems.find('[data-index="' + index + '"]');
    x = x.parent();
    x.addClass('active');
    x.attr('id', 'activeTrack');

    startPlayback(playlist[index].yid);
    startTimer();
    initjPlayer(track);
}

/** Add Track to the playlist **/
function addToPlaylist(track, artist, mbid, yid) {
    var i = playlist.length;
    playlistItems.removeClass('hide');

    if (playlist.length == 0) {
        index = 0;
        alsoPlay = true;
    }

    playlist.push({
        mbid: mbid,
        yid: yid,
        track: track,
        artist: artist,
        isSaved: false,
        deleted: false,
        ptrackid: false
    });

    $('#playlist-empty-banner').addClass('hide');
    $("#clearPlaylist").removeClass('hide');
    $("#savePlaylist").removeClass('hide');
    
    playlistItems.append(
        '<div class="list-group-item"><a href="#jp_container_N" data-index="'+i+'" class="pull-right btn-remove item removeThisTrack"><i class="fa fa-times text"></i></a><a href="#jp_container_N" data-track="'+track+'" data-artist="'+artist+'" data-index="'+i+'" data-mbid="'+mbid+'" data-yid="'+yid+'" class="playThisTrack"><i class="icon-control-play text"></i></a><span class="btn-track-info trackInfo"> '+track+'</span><br/><span class="artistInfo text-muted btn-artist-info"> '+artist+'</span></div>'
    );

    return i;
}

/** Play next song **/
function playNextSong() {
    if (emptyPlaylist() || allDeleted()) {
        return false;
    }

    stopTimer();
    ++index; // Increment the index of current track in playlist

    if (playlist.length == index) { // end of playlist
        index = 0; // play from start
    }

    if (playlist[index].deleted) {
        playNextSong();
    }
    playThis(playlist[index].track, index);
}

/** Play previous song **/
function playPrevSong() {
    if (emptyPlaylist() || allDeleted()) {
        return false;
    }

    stopTimer();
    --index;

    if (index <= -1) {
        index = playlist.length - 1;  // checking of out of bounds
    }

    if (playlist[index].deleted) {
        playPrevSong();
    }
    playThis(playlist[index].track, index);
}

/** Seek the player to given seconds **/
function seekTo(sec) {
    if (ytplayer.getPlayerState() == -1) {
        return false;
    }
    ytplayer.seekTo(sec, true);
}

$(document).ready(function () {
    duration = $(".jp-duration");
    currentTime = $(".jp-current-time");
    $(".jp-play-bar").width("0%");
    playlistItems = $("#playlist-items");

    initPlaylist(playlistItems);

    // Adding a song to playlist
    $(".addToPlaylist").on("click", function (e) {
        e.preventDefault();
        var track = $(this).attr("data-track"),
            self = $(this),
            yid = $(this).attr("data-yid"),
            artist = $(this).attr("data-artist"),
            mbid = $(this).attr("data-mbid");
        self.addClass('disabled');
        alsoPlay = false;
        var i = false;
        if (yid === undefined) {
            i = inPlaylist(track, artist);
            if (i === false) {
                findSong(track, artist, mbid, self);
            } else {
                self.attr('data-yid', playlist[i].yid);

                if (playlist[i].deleted) {
                    playlist[i].deleted = false;
                    var x = playlistItems.find('[data-index="' + i + '"]');
                    x.parent().removeClass('hide');
                }
                self.removeClass('disabled');
                window.location.href = self.attr('href');
            }
        } else {
            if (emptyPlaylist()) {
                i = addToPlaylist(track, artist, mbid, yid);
                playThis(track, i);
            } else {    // Track could have been removed from playlist
                i = inPlaylist(track, artist, yid);

                if (i !== false && playlist[i].deleted) {
                    playlist[i].deleted = false;
                    var x = playlistItems.find('[data-index="' + i + '"]');
                    x.parent().removeClass('hide');
                }
            }
            self.removeClass('disabled');
            window.location.href = self.attr('href');
        }
    });

    // playing a song
    $(document.body).on("click", ".playThisTrack", function (e) {
        e.preventDefault();
        var track = $(this).attr("data-track"),
            self = $(this),
            yid = $(this).attr("data-yid"),
            artist = $(this).attr("data-artist"),
            mbid = $(this).attr("data-mbid"),
            playingIndex = $(this).attr("data-index");

        self.addClass('disabled');
        alsoPlay = true;
        var i = false;
        if (yid === undefined) {
            i = inPlaylist(track, artist);

            if (i === false) {
                findSong(track, artist, mbid, self);
            } else {
                self.attr('data-yid', playlist[i].yid);

                if (playlist[i].deleted) {
                    playlist[i].deleted = false;
                    var x = playlistItems.find('[data-index="' + i + '"]');
                    x.parent().removeClass('hide');
                }
                playThis(track, i);
                self.removeClass('disabled');
                window.location.href = self.attr('href');
            }
        } else {
            if (emptyPlaylist()) {
                i = addToPlaylist(track, artist, mbid, yid);
            } else if (playingIndex) {
                i = playingIndex;
            } else {
                i = inPlaylist(track, artist, yid);

                if (i !== false && playlist[i].deleted) {
                    playlist[i].deleted = false;
                    var x = playlistItems.find('[data-index="' + i + '"]');
                    x.parent().removeClass('hide');
                }
            }
            playThis(track, i);
            self.removeClass('disabled');
            window.location.href = self.attr('href');
        }
    });

    // clearing the playlist
    $("#clearPlaylist").on("click", function () {
        if (emptyPlaylist()) {
            return false;
        }
        clearPlaylist();
    });

    // removing a track from playlist
    $(document.body).on("click",  ".removeThisTrack",function (e) {
        e.preventDefault();
        var i = $(this).data('index');

        var x = confirm('Are you sure to remove this track from Playlist?');
        if (x) {
            $(this).parent().addClass('hide');
            playlist[i].deleted = true;
            savePlaylist();
            if (i == index) {
                playNextSong();
            }
        } else {
            return;
        }
    });

    $("#savePlaylist").on("click", function () {
        if (emptyPlaylist()) {
            return false;
        }
        savePlaylist();
    });

    $("#download-mp3").on("click", function (e) {
        e.preventDefault();
        
        if (emptyPlaylist() || index === -1) {
            return false;
        }
        var download = $("#downloadModal"),
            btn = $("#startDownloading");
        
        download.find('.modal-title').html(playlist[index].artist + ' - ' + playlist[index].track);
        btn.data('yid', playlist[index].yid);
        btn.data('track', playlist[index].track);
        btn.data('artist', playlist[index].artist);
        btn.data('mbid', playlist[index].mbid);
        download.modal('show');
    });

    $("#startDownloading").on('click', function(e) {
        e.preventDefault();
        var self = $(this),
            yid = self.data('yid'),
            track = self.data('track'),
            artist = self.data('artist'),
            mbid = self.data('mbid');

        self.html('<i class="fa fa-spinner fa-pulse"></i> Please Wait...');
        request.create({
            action: '/home/download/' + yid + '/'  + track,
            data: {action: 'downloadMusic', track: track, artist: artist, mbid: mbid},
            callback: function (data) {
                self.html('<i class="fa fa-download"></i> Download');
                $("#downloadModal").modal('hide');
                if (data == "Success") {
                    window.location.href = '/home/download/' + yid + '/' + track;
                } else {
                    $('#alertMessage').html(data);
                    $('#alertModal').modal('show');
                }
            }
        })
    });

    // Find Artist/Track Info
    $(".artistInfo").on("click", function (e) {
        e.preventDefault();

        // @todo
    });

    $(".trackInfo").on("click", function (e) {
        e.preventDefault();

        // @todo
    });

    /****** Player controls *********/
    // Play/pause
    $(".jp-play").on("click", function () {
        if (emptyPlaylist()) {
            return false;
        }

        ytplayer.playVideo();
        startTimer();

        $(this).css({
            display: 'none'
        });
        $(".jp-pause").css({
            display: 'inline-block'
        });
        
    });
    $(".jp-pause").on("click", function () {
        $(this).css({
            display: 'none'
        });
        $(".jp-play").css({
            display: 'inline-block'
        });
        ytplayer.pauseVideo();
    });

    // Volume Controls = Mute/Unmute
    $(".jp-mute").on("click", function () {
        if (emptyPlaylist()) {
            return false;
        }

        $(this).css({
            display: 'none'
        });
        $(".jp-unmute").css({
            display: 'inline-block'
        });
        ytplayer.setVolume(0);
    });
    $(".jp-unmute").on("click", function () {
        if (emptyPlaylist()) {
            return false;
        }

        $(this).css({
            display: 'none'
        });
        $(".jp-mute").css({
            display: 'inline-block'
        });
        ytplayer.setVolume(100);
    });

    // Previous/Next button
    $(".jp-next").on("click", function () {
        playNextSong();
    });
    $(".jp-previous").on("click", function () {
        playPrevSong();
    });

    // Seek bar
    $('.jp-seek-bar').on("click", function (e) {
        var parentOffset = $(this).parent().offset();
        var relX = e.pageX - parentOffset.left;
        var pos  = (100 * relX)/($(this).width());
        if(pos <0) pos = 0;
        if(pos >100) pos = 100;

        $(".jp-play-bar" ).width(pos+"%");

        var seek = (pos/100)*(ytplayer.getDuration());
        
        seekTo(seek);
    });

    // Volume bar
    $('.jp-volume-bar').on('click', function (e) {
        var parentOffset = $(this).parent().offset();
        var relX = e.pageX - parentOffset.left;
        var pos  = (100 * relX)/($(this).width());
        if(pos <0) pos = 0;
        if(pos >100) pos = 100;

        $('.jp-volume-bar-value').width(pos+"%");
        ytplayer.setVolume(pos);
    });

});

function onPlayerStateChange(event) {
    var state = event.data;

    if (state == YT.PlayerState.ENDED) {
        playNextSong();
    }

    if (state == YT.PlayerState.PLAYING) {}

    if (state == YT.PlayerState.PAUSED) {
        stopTimer();
    }

    if (state == YT.PlayerState.BUFFERING) {
        // @todo: show some buffering modal
    }
    if (state == YT.PlayerState.CUED) {}
}

function findSong(track, artist, mbid, selector) {
    var playingIndex;
    request.create({
        action: '/home/findTrack',
        data: {action: 'findTrack', track: track, artist: artist},
        callback: function (yid) {
            if (yid != "Error") {
                playingIndex = addToPlaylist(track, artist, mbid, yid);
                selector.attr("data-yid", yid);
                selector.removeClass('disabled');
                window.location.href = selector.attr('href');

                if (alsoPlay) {
                    playThis(track, playingIndex);
                }
            }
        }
    });    
}

function initPlaylist(items) {
    items.children().each(function(i, el) {
        var el = $(this).find('.playThisTrack');
        playlist.push({
            track: el.data('track'),
            artist: el.data('artist'),
            yid: el.data('yid'),
            mbid: el.data('mbid'),
            isSaved: true,
            deleted: false,
            ptrackid: el.data('ptrackid')
        });
    });
    if (!emptyPlaylist()) {
        playThis(playlist[0].track, 0);
        $('#playlist-empty-banner').addClass('hide');
        $('#clearPlaylist').removeClass('hide');
        $("#savePlaylist").removeClass('hide');
    } else {
        $('#playlist-empty-banner').removeClass('hide');
        $('#clearPlaylist').addClass('hide');
        $("#savePlaylist").addClass('hide');
    }
}

function inPlaylist(track, artist, yid) {
    var found = false;
    playlist.forEach(function (c, i) {
        if (!yid && (c.track == track && c.artist == artist)) {
            found = i;
            return false;
        }

        if (yid && c.yid == yid) {
            found = i;
            return false;
        }
    });
    return found;
}

function savePlaylist() {
    request.create({
        action: '/users/savePlaylist',
        data: {action: 'savePlaylist', playlist: playlist, playlistId: $('#currentPlaylist').data('id')},
        callback: function (data) {
            if (data == "Success") {
                $('#alertMessage').html("Your playlist has been saved");
                $('#alertModal').modal('show');
            } else if (data == "Login") {
                // alert('Login to save Playlist!');
            } else {
                $('#alertMessage').html("Your Playlist could not be saved");
                $('#alertModal').modal('show');
            }
        }
    });
}

function clearPlaylist() {
    $('#playlist-empty-banner').removeClass('hide');
    $('#clearPlaylist').addClass('hide');
    $("#savePlaylist").addClass('hide');
    $('#playlist-items').html("").addClass('hide');
    playlist = [];
    index = -1;

    stopTimer();
    ytplayer.stopVideo();
    stopjPlayer();
}

function allDeleted() {
    var assume = false;

    for (var i = 0; i < playlist.length; ++i) {
        if (playlist[i].deleted === false) {
            assume = false;
            break;
        } else if (playlist[i].deleted === true) {
            assume = true;
        }
    }

    if (assume == true) {
        clearPlaylist();
    }
    return assume;
}
