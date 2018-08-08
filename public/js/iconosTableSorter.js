var headTarget;

$('table th').click(function(e){
  headTarget = e.currentTarget;
});

$("table").on("sortEnd",function(e) {

    $("table thead tr th i").removeClass().addClass('fa').addClass('fa-sort');

    if($(headTarget).hasClass('headerSortDown')){
      $(headTarget).children('i').removeClass().addClass('fa').addClass('fa-sort-asc');
    }
    else if($(headTarget).hasClass('headerSortUp')){
      $(headTarget).children('i').removeClass().addClass('fa').addClass('fa-sort-desc');
    }
});
