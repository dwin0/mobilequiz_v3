var gallery;

var openPhotoSwipe = function(image) {
    var pswpElement = document.querySelectorAll('.pswp')[0];
    
    var items = [
        {
            src: image.src,
            w: image.width * 3,
            h: image.height * 3
        }
    ];
    
    var options = {
        history: false,
        focus: true,

        showAnimationDuration: 0,
        hideAnimationDuration: 0
    };
    
    gallery = new PhotoSwipe( pswpElement, PhotoSwipeUI_Default, items, options);
    gallery.init();
};

var closePhotoSwipe = function(event) {
	gallery.close();
	event.preventDefault();
}

$(document).on('touchstart click', function(event){
	  
	  var element = event.target;
	  
	  switch(element.id) {
	  	case 'questionImage':
		  	openPhotoSwipe(element);
		  	break;
	  	case 'closePhoto':
	  		closePhotoSwipe(event);
		  	break;
	  }
	});