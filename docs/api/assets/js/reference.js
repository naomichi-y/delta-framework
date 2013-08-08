var visibleProperties = true;
var visibleMethods = true;

$(function(){
  $('#tree').treeview({
    collapsed: true,
    animated: 'fast',
    control:'#tree_control',
    prerendered: true,
    persist: 'location'
  });

  $('#toggleProperties').click(function() {
    if (visibleProperties) {
      $('#toggleProperties').html('<a href="#">Show inherited properties</a>');
      $('.inheritanceProperties').fadeOut(300);

    } else {
      $('#toggleProperties').html('<a href="#">Hide inherited properties</a>');
      $('.inheritanceProperties').fadeIn(300);
    }

    visibleProperties = !visibleProperties;

    return false;
  });

  $('#toggleMethods').click(function() {
    if (visibleMethods) {
      $('#toggleMethods').html('<a href="#">Show inherited methods</a>');
      $('.inheritanceMethods').fadeOut(300);

    } else {
      $('#toggleMethods').html('<a href="#">Hide inherited methods</a>');
      $('.inheritanceMethods').fadeIn(300);
    }

    visibleMethods = !visibleMethods;

    return false;
  });

  $('a[href^=#]').click(function(){
    var Hash = $(this.hash);
    var HashOffset = $(Hash).offset().top;
    $('html,body').animate({
      scrollTop: HashOffset
    }, 1000);
    return false;
  });
});
