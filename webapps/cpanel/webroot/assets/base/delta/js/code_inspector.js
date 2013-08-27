loadCss('/assets/base/delta/css/app_code_inspector.css');

function loadCss(href) {
  var head = document.getElementsByTagName("head")[0];
  var link = document.createElement("link");

  link.rel = "stylesheet";
  link.type = "text/css";
  link.href = href;

  var links = head.getElementsByTagName("link");

  for(var i = 0; i < links.length; i++) {
    if(links[i].href == link.href) {
      return false;
    } 
  }

  head.appendChild(link);
}

require(["/assets/base/jquery-ui-1.8.16.custom/js/jquery-1.6.2.min.js"], function(someModule) {
  $().ready(function () {
    $(".delta-stack-traces dt").click(function (e) {
      var prefix = "trace_point_";
      var target = "#trace_point_detail_" + this.id.substring(prefix.length + 0);

      $(target).slideToggle();
    });

    $('.delta-stack-traces dt').mouseover(function () {
      $(this).css("background-color", "#dddddd");
    });

    $('.delta-stack-traces dt').mouseout(function () {
      $(this).css("background-color", "#ffffff");
    });
  });
});
