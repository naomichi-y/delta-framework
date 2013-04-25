jQuery.fn.wordbreak = function () {
  return this.each(function () {
    if($.browser.msie){//IE
      $(this).css( "word-break","break-all" );
      return;
    }
    var re = /(.*)(< [^>]+>)(.*)(< \/[^>]+>)(.*)/g;
    var tagSplit = $(this).html().split( re );
    var texts = [];
    var t;

    $.each(tagSplit, function(i, str) {
      if (str.charAt(0) != '< '){
        if($.browser.opera){//opera
          t = $.trim( str.split("").join("-") );
        }else{//others
          t = $.trim( str.split("").join(String.fromCharCode(8203)) );
        }
        texts.push(t);
      }else{
        texts.push(str);
      }
    });
    $(this).html( texts.join("") );
  });
}
