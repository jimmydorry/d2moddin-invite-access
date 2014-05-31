$(document).ready(function() { 
	$("body").css("overflow", "visible");
	var nice = $("html").niceScroll();  // The document page (body)
});

//<![CDATA[
	$(window).load(function() { // makes sure the whole site is loaded
		$("#preloader").delay(1500).fadeOut("slow"); // will fade out the white DIV that covers the website.
	})
	//]]>

/*
 * Function for image slider background
 */ 
$(function(){
	$('#maximage').maximage({
		cycleOptions: {
			fx: slidertransition,
			speed: sliderspeed, // Has to match the speed for CSS transitions in jQuery.maximage.css (lines 30 - 33)
			timeout: slidertimeout
			},
		onFirstImageLoaded: function(){
			jQuery('#maximage').fadeIn('fast');
			}
      });
});

/*
 * Function for scroller
 */
$(document).ready( function() {
	$('.tweet_list').listVerticalScroller({
			direction:scrolldirection,
			duration:tweetduration,
			speed:scrollspeed
	});
});


/*
 * Modal close
 */
$(function(){
         $('#show_modal_1').click(function(e){
              $('#about').modal('show');
              $('#index, #counter, #subscribe_to, #g_map, #contact_us').modal('hide');
              e.stopPropagation();
          });
          $('#show_modal_2').click(function(e){
              $('#subscribe_to').modal('show');
              $('#index, #counter, #about, #g_map, #contact_us').modal('hide');
              e.stopPropagation();
          });
          $('#show_modal_3').click(function(e){
              $('#g_map').modal('show');
              $('#index, #counter, #about, #subscribe_to, #contact_us').modal('hide');
              e.stopPropagation();  
          });
          $('#show_modal_4').click(function(e){
              $('#contact_us').modal('show');
              $('#index, #counter, #about, #subscribe_to, #g_map').modal('hide');
              e.stopPropagation();
          });
          $('#show_modal').click(function(e){
          	  $('#index').show();
              $('#about, #subscribe_to, #g_map, #contact_us').modal('hide');
              e.stopPropagation();
          });
});

/*
 * Remove the anchor tag
 */
$('#show_modal,#show_modal_1,#show_modal_2,#show_modal_3,#show_modal_4,.tubular-play,.tubular-pause,.tubular-volume-up,.tubular-volume-down,.tubular-mute').click(function(event){
        event.preventDefault();
        //the rest of the function is the same.
});
         
/*
 * Function for the Contact Form
 */
$(function(){
$('#contact').validate({
submitHandler: function(form) {
    $(form).ajaxSubmit({
    url: 'contact.php',
    success: function() {
    $('#contact').hide();
    $('#contact-form').append(contact_sent)
    }
    });
    }
});         
});


/*
 * Function for placeholders
 */
$('input, textarea').placeholder();

/*
 * Function to detect mobile device
 */
function isMobile() {
	return (( /Android|webOS|iPhone|iPod|BlackBerry/i.test(navigator.userAgent) ) || ($(window).width() <= 480));
}

if(isMobile()) {
(function(){
	$('#tubular-shield').css("z-index", "0");
	$('#vimeoplayer-mask').css("z-index", "0");
})
();
};

/*
 * Function to detect iPad rotation
 */
addEventListener("load", function()
{
    setTimeout(updateLayout, 0);
}, false);
var currentWidth = 0;
function updateLayout()
{
    if (window.innerWidth != currentWidth)
    {
        currentWidth = window.innerWidth;
        var orient = currentWidth == 320 ? "profile" : "landscape";
        document.body.setAttribute("orient", orient);
        setTimeout(function()
        {
            window.scrollTo(0, 1);
        }, 100);            
    }
}
setInterval(updateLayout, 400);
