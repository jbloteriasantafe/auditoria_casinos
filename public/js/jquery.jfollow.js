(function($){

	$.fn.jfollow = function(follow, pad){
		
		return this.each(function(){
		
			var that = $(this),
				followme = $(follow),
				oldcss = {
					position: that.css('position'),
					top: that.css('top'),
					left: that.css('left')
				}
			;
			
			var followfn = function(){
				
				if( $(window).scrollTop() >= followme.offset().top - pad ){
					
					that.css({
						position: 'fixed',
						top: pad,
						left: followme.offset().left - $(window).scrollLeft()
					});
					
				} else {
					
					that.css(oldcss);
				};
				
			};
			
			followfn();
			
			$(window).bind('resize scroll', followfn);
			
		});
		
	};
	
})(jQuery);