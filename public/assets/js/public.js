$(document).ready(function() {
	$("a.play.text-ellipsis").on("click", function (e) {
		e.preventDefault();

		var current = $(this);
		console.log(current);
		var id = current.attr("data-id");
		var src = "https://www.youtube.com/embed/" + id;
		
		var iframe = $("#embedIt").attr("src", src);
	});
});