var ytplayer = "",
    index = -1,
    playlist = [],
    currentTime = '',
    duration = '',
    timerOn = false,
    tVar,
    alsoPlay = false;

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
    playlist = [];
    index = -1;

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
function playThis(track, id) {
    if (!isYTPlayer()) {
        return false;
    }

    // check if current track is the playing track
    while (playlist.length !== 0 && playlist[index].yid != id) {
        ++index;    // find the index of current playing track
        if (playlist.length == index) {
            index = 0;
        }
    }

    startPlayback(id);
    startTimer();
    initjPlayer(track);
}

function alreadyFound(track, artist) {
    var found = false,
        yid = false;
    playlist.forEach(function (current, index) {
        if (playlist[index].track == track && playlist[index].artist == artist) {
            yid = playlist[index].yid;
            found = true;
            return false;
        }
    });
    return (found) ? yid : found;
}

/** Add Track to the playlist **/
function addToPlaylist(track, artist, mbid, yid) {
    var inPlaylist = false;
    if (playlist.length == 0) {
        index = 0;
        playThis(track, yid);
    } else {
        playlist.forEach(function (current, index) {
            if (playlist[index].yid == yid) {
                inPlaylist = true;
                return false;
            }
        });
    }

    if (!inPlaylist) {
        playlist.push({
            mbid: mbid,
            yid: yid,
            track: track,
            artist: artist
        });
    }
}

/** Play next song **/
function playNextSong() {
    if (playlist.length == 0) {
        return false;
    }

    stopTimer();
    ++index; // Increment the index of current track in playlist

    if (playlist.length == index) { // end of playlist
        index = 0; // play from start
    }
    playThis(playlist[index].track, playlist[index].yid);
}

/** Play previous song **/
function playPrevSong() {
    if (playlist.length == 0) {
        return false;
    }

    stopTimer();
    --index;

    if (index <= -1) {
        index = 0;  // checking of out of bounds
    }
    playThis(playlist[index].track, playlist[index].yid);
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

    // Adding a song to playlist
    $(".addToPlaylist").on("click", function () {
        var track = $(this).attr("data-track"),
            artist = $(this).attr("data-artist"),
            mbid = $(this).attr("data-mbid");

        alsoPlay = false;
        findSong(track, artist, mbid);
    });

    // playing a song
    $(".playThisTrack").on("click", function () {
        var track = $(this).attr("data-track"),
            artist = $(this).attr("data-artist"),
            mbid = $(this).attr("data-mbid");

        alsoPlay = true;
        findSong(track, artist, mbid);
    });

    // clearing the playlist
    $(".clearPlaylist").on("click", function () {
        ytplayer.stopVideo();
        stopTimer();
        stopjPlayer();
    });

    /****** Player controls *********/
    // Play/pause
    $(".jp-play").on("click", function () {
        if (playlist.length !== 0) {
            ytplayer.playVideo();
            startTimer();    
        } else {
            return false;
        }

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
        $(this).css({
            display: 'none'
        });
        $(".jp-unmute").css({
            display: 'inline-block'
        });
        ytplayer.setVolume(0);
    });
    $(".jp-unmute").on("click", function () {
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
    $('.jp-progress').on("click", function (e) {
        console.log(e);
        // var parentOffset = $(this).parent().offset();
        // var relX = e.pageX - parentOffset.left;
        // var pos  = (100 * relX)/($(this).width());
        
        // if(pos <0) pos = 0;
        // if(pos >100) pos = 100;
        
        // $(".jp-play-bar" ).width(pos+"%");

        // var size = $(".jp-play-bar").width();
        // var pos = (size/100)*(ytplayer.getDuration());
        // // console.log(size);
        // // console.log(pos);
        // // console.log("Seeking ytplayer");
        // seekTo(pos);
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
        // show some buffering modal
    }
    if (state == YT.PlayerState.CUED) {}
}

function findSong(track, artist, mbid) {
    yid = alreadyFound(track, artist);
    if (yid) {
        if (alsoPlay) {
            playThis(track, yid);
        }
    } else {
        request.create({
            action: '/home/findTrack',
            data: {action: 'findTrack', track: track, artist: artist},
            callback: function (data) {
                if (data != "Error") {
                    addToPlaylist(track, artist, mbid, data);

                    if (alsoPlay) {
                        playThis(track, data);
                    }
                }
            }
        });    
    }

    
}
