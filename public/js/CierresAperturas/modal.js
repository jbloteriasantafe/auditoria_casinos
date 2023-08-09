$(function(e){
  $('[data-minimizar]').click(function() {
    const minimizar = $(this).data('minimizar');
    $('.modal-backdrop').css('opacity',minimizar? '0.1' : '0.5');
    $(this).data("minimizar", !minimizar);
  });

  $('.modal').on('shown.bs.modal',function(){
    const min = $(this).find('[data-minimizar]');
    if(min.length == 0) return;
    if(!min.data('minimizar')){
      setTimeout(function(){
        min.click();
      },250);
    }
  });
});
