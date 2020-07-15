(function($){

	$.fn.gallery = function(options){

		options = $.extend({
			duration: 400,
			easing: 'swing',
			rows: 1,
			cols: 5
		}, options);

		return this.each(function(){
			// grab the stuff we're going to use
			var gallery = $(this),
				next = $('#' + gallery.attr('id') + '-next'),
				prev = $('#' + gallery.attr('id') + '-prev'),
				pos = $('#' + gallery.attr('id') + '-pos'),
				viewer = $('#' + gallery.attr('id') + '-viewer'),
				viewnext = $('#' + gallery.attr('id') + '-viewer-next'),
				viewprev = $('#' + gallery.attr('id') + '-viewer-prev'),
				thumbs = gallery.children(),
				pages = Math.ceil(thumbs.size() / (options.rows * options.cols)),
				tiles = options.rows * options.cols,
				newview,
				alignImage = function(view){
					var height = view.css('height'),
						width = view.css('width'),
						img = view.find('img'),
						imgheight = img.get(0).height,
						imgwidth = img.get(0).width,
						hpadding = (parseInt(height,10) - parseInt(imgheight,10)) / 2,
						vpadding = (parseInt(width,10) - parseInt(imgwidth,10)) / 2
					;

					if (hpadding > 0 && imgheight > 0) {
						img.css('margin-top',hpadding);
					}

					if (vpadding > 0 && imgwidth > 0) {
						img.css('margin-left',vpadding);
					}

				},
				changePage = function(next){

					// set page variables
					var currpage = parseInt(pos.text().slice(pos.text().indexOf(' ')+1,pos.text().indexOf(' of')),10),
						nextpage = next === true ? currpage + 1 : currpage - 1
					;

					// if we are below the range, set to last page
					if (nextpage < 1) {
						nextpage = pages;

					// else, if we are above the range, set to first page
					} else if (nextpage > pages) {
						nextpage = 1;
					}

					var first = (nextpage - 1) * tiles;

					// initialize the next view
					gallery.fadeOut(options.duration,function(){

						thumbs.hide();
						pos.text('Página ' + nextpage + ' de ' + pages);

						if (nextpage === pages) {
							thumbs.slice(first).show();
						} else {
							thumbs.slice(first,first+tiles).show();
						}

						gallery.fadeIn(options.duration);
					});

				}
			;

			// set up the gallery size
			gallery.css({
				'width': (thumbs.width()+parseInt(thumbs.css('margin-left'),10)+parseInt(thumbs.css('margin-right'),10)+parseInt(thumbs.css('padding-left'),10)+parseInt(thumbs.css('padding-right'),10))*(options.cols+1),
				'height': (thumbs.height()+parseInt(thumbs.css('margin-bottom'),10)+parseInt(thumbs.css('margin-top'),10)+parseInt(thumbs.css('padding-bottom'),10)+parseInt(thumbs.css('padding-top'),10))*options.rows,
				'overflow': 'hidden'
			});

			// set up the thumbnails
			thumbs.css({
				'cursor':'pointer',
				'display':'block'

			});

			thumbs.each(function(){
				$(this).bind('click', function(){

					// grab the image location
					var img = $(this).find('img').attr('src'),
						//fullimg = img.substr(0,img.indexOf('thumbs')) + img.substr(img.lastIndexOf('/')+1)
						fullimg = img;
					;

					// place the image in the viewer
					viewer.fadeOut(options.duration,function(){
						viewer.empty();
						viewer.append('<img src="'+ fullimg + '" />');
						viewer.find('img').imagesLoaded(function(){

							// align the image and show the viewer
							viewer.fadeIn(options.duration);
							alignImage(viewer);
						});
					});
				}).imagesLoaded(function(){

					//alignImage($(this));
				});

			});

			// set up thumbnail tabs
			thumbs.hide(0).slice(0,tiles).fadeIn(options.duration);

			// set up thumbnail tab navigation indicator
			if (thumbs.length != 0) {
				pos.text('Página 1 de ' + pages);
			}
			else {
				pos.text('No se han encontrado resultados');
			}

			// set up the previous tab button
			prev.bind('click',function(){

				changePage(false);

				return false;
			});

			// set up the next tab button
			next.bind('click',function(){

				changePage(true);

				return false;
			});

			// place the first image in the viewer
			var initimg = thumbs.filter(':first').find('img').attr('src');
			//initimg = initimg.substr(0,initimg.indexOf('/thumbs')) + initimg.substr(initimg.lastIndexOf('/'));
			viewer.hide().empty().append('<img src="'+ initimg + '" />');
			viewer.find('img').imagesLoaded(function(){
				viewer.fadeIn(options.duration);
				alignImage(viewer);
			});

			thumbs.filter(':lt(5)').each(function( index ) {
				  $(this).children().css('max-width', '100%')
					.css('margin-top','0px')
					.css('margin-left','0px')
					.css('max-height', '100%')
					.css('display', 'block')
					//$(this).children().css({ 'margin-top' : '', 'margin-left' : '' });
			});

			// viewer next button
			viewnext.bind('click',function(){

				// get the next image
				var viewerimg = viewer.find('img').attr('src'),
					nextimg = viewerimg.substr(0,viewerimg.lastIndexOf('/')) + '/thumbs' + viewerimg.substr(viewerimg.lastIndexOf('/')),
					index = 0
				;

				// find the next image and navigate to it
				thumbs.each(function(){
					var thumbimg = $(this).find('img').attr('src');

					// if the thumbnail scanned is equal to the current viewer image, the next thumbnail should be loaded
					if (thumbimg === nextimg) {

						// if the next index is higher than the number of thumbnails, the next image is the first thumbnail
						if (index + 1 > thumbs.length - 1) {
							nextimg = $(thumbs[0]).find('img').attr('src');
						// else, the next image is the next thumbnail
						} else {
							nextimg = $(thumbs[index+1]).find('img').attr('src');
						}
						nextimg = nextimg.substr(0,nextimg.indexOf('thumbs')) + nextimg.substr(nextimg.lastIndexOf('/')+1);

						// place the image in the viewer
						viewer.fadeOut(options.duration, function(){
							viewer.empty();
							viewer.append('<img src="'+ nextimg + '" />');
							viewer.find('img').imagesLoaded(function(){
								viewer.fadeIn(options.duration);
								alignImage(viewer);
							});
						});

						// escape the loop
						return false;
					}

					index++;
				});

				return false;
			});

			// viewer previous button
			viewprev.bind('click',function(){

				// get the next image
				var viewerimg = viewer.find('img').attr('src'),
					nextimg = viewerimg.substr(0,viewerimg.lastIndexOf('/')) + '/thumbs' + viewerimg.substr(viewerimg.lastIndexOf('/'));

				var index = 0;

				// find the next image and navigate to it
				thumbs.each(function(){
					var thumbimg = $(this).find('img').attr('src');

					// if the current scanned image is equal to the image in the viewer, the previous thumbnail should be navigated to
					if (thumbimg === nextimg) {
						// if this is the first thumbnail, the last thumbnail is the correct image
						if (index === 0) {
							nextimg = $(thumbs[thumbs.length-1]).find('img').attr('src');
						// else, the previous thumbnail is the correct image
						} else {
							nextimg = $(thumbs[index-1]).find('img').attr('src');
						}
						nextimg = nextimg.substr(0,nextimg.indexOf('thumbs')) + nextimg.substr(nextimg.lastIndexOf('/')+1);

						// place the image in the viewer
						viewer.fadeOut(options.duration, function(){
							viewer.empty();
							viewer.append('<img src="'+ nextimg + '" />');
							viewer.find('img').imagesLoaded(function(){
								viewer.fadeIn(options.duration);
								alignImage(viewer);
							});
						});

						// escape the loop
						return false;
					}

					index++;
				});

				return false;
			});

		});

	};
})(jQuery);
