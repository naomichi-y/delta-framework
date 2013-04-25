$().ready( function () {
  var overcells = $('table td');
  var hoverClass = 'highlight';
  var currentRow;

  overcells.hover(
    function(){
      var $this = $(this);
      (currentRow =$this.parent().children('table td')).addClass(hoverClass);
    },

    function(){
      currentRow.removeClass(hoverClass);
    }
  );
});
