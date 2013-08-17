$().ready( function () {
  var dialogWidth = 900;

  jQuery.tablesorter.addParser({
    id: 'number',
    is: function(s) {
      return /^[0-9]?[0-9,\.]*$/.test(s);
    },
    format: function(s) {
      return jQuery.tablesorter.formatFloat( s.replace(/,/g,'') );
    },
    type: 'numeric'
  });

  var dates = $('#from, #to').datepicker({
    defaultDate: '+1w',
    dateFormat: 'yy-mm-dd',
    duration: 'slow',
    changeMonth: true,
    numberOfMonths: 2,
    onSelect: function(selectedDate) {
      var option = this.id == 'from' ? 'minDate' : 'maxDate',
        instance = $(this).data('datepicker'),
        date = $.datepicker.parseDate(
          instance.settings.dateFormat ||
          $.datepicker._defaults.dateFormat,
          selectedDate, instance.settings );
      dates.not(this).datepicker('option', option, date);
    }
  });

  $('#tabs').tabs({
    cookie: {
      expires: 1
    },
    show: function(event, ui) {
      var tabId = '#ui-tabs-' + (ui.index + 1);
      $(tabId).html('<p class="center"><img src="/assets/base/ajax-loader.gif" /></p>');
    },
    select: function(event, ui) {
      if (ui.index == 1) {
        $('#type').attr('value', 'default');

      } else if (ui.index == 2) {
        $('#type').attr('value', 'prepare');
      }
    }
  });

  $('.detail_action').click(function() {
    var buttonPrefix = 'detail_';
    var pos = this.id.indexOf('_', buttonPrefix.length);
    var target = this.id.substring(pos + 1);
    var dialogId = this.id.substring(buttonPrefix.length, pos);
    var detailDialog = $('#detail_dialog_' + dialogId);

    if (detailDialog.size() == 0) {
      $("#dynamic_dialog").prepend('<div id="detail_dialog_' + dialogId + '"></div>');
      detailDialog = $('#detail_dialog_' + dialogId);

      detailDialog.dialog({
        autoOpen: false,
        width: dialogWidth,
        resizable: false,
        show: 'fade',
        hide: 'fade',
        open: function(event) {
          var id = $('#' + event.target.id);

          id.html('<p class="center"><img src="/assets/base/ajax-loader.gif" /></p>');
          id.load($(this).dialog('option', 'url'));
        }
      });
    }

    $('.ui-draggable').draggable({
      opacity: 0.8,
      containment: 'DOM'
    });

    var detailButtonOffset = $(this).offset();
    var detailButtonWidth = $(this).width();
    var dialogOffset = detailDialog.dialog('option', 'width');
    pos = target.indexOf('::');

    var url = '/cpanel/analyzeActionDetail.do'
             +'?module=' + target.substring(0, pos)
             +'&action=' + target.substring(pos + 2)
             +'&from=' + $('#from').val()
             +'&to=' + $('#to').val()

    detailDialog.dialog('option', 'position', [($(window).width() - dialogOffset) / 2, detailButtonOffset.top - $(window).scrollTop() + 25]);
    detailDialog.dialog('option', 'url', url);
    detailDialog.dialog('open');

    $('#ui-dialog-title-detail_dialog_' + dialogId).text('アクションの実行ログ (' + target + ')');
  });

  $('.delete_action').click(function() {
    var buttonPrefix = 'delete_';
    var pos = this.id.indexOf('_', buttonPrefix.length);
    var target = this.id.substring(pos + 1);
    var dialogId = this.id.substring(buttonPrefix.length, pos);

    pos = target.indexOf('::');
    var url = '/cpanel/analyzeActionDelete.do'
             +'?module=' + target.substring(0, pos)
             +'&action' + target.substring(pos + 2);

    $.post(url, null, null, 'html');

    var speed = 200;

    $('#col_rank_' + dialogId).slideUp(speed);
    $('#col_module_name_' + dialogId).slideUp(speed);
    $('#col_action_name_' + dialogId).slideUp(speed);
    $('#col_request_count_' + dialogId).slideUp(speed);
    $('#col_average_process_time_' + dialogId).slideUp(speed);
    $('#col_last_access_date_' + dialogId).slideUp(speed);
    $('#col_action_' + dialogId).slideUp(speed);

    setTimeout(function() { $('#row_action_' + dialogId).hide(); }, speed);
  });

  $('.detail_action_statement').click(function() {
    var prefix = 'path_';
    var pos = this.id.indexOf('_', prefix.length);
    var actionRequestId = this.id.substring(prefix.length, pos);
    var requestPath = this.id.substring(pos + 1);
    var hash = $.md5(requestPath);
    var detailDialog = $('#detail_dialog_' + hash);

    if (detailDialog.size() == 0) {
      $("#dynamic_dialog").prepend('<div id="detail_dialog_' + hash + '"></div>');
      detailDialog = $('#detail_dialog_' + hash);

      detailDialog.dialog({
        autoOpen: false,
        width: dialogWidth,
        resizable: false,
        show: 'fade',
        hide: 'fade',
        open: function(event) {
          var id = $('#' + event.target.id);

          id.html('<p class="center"><img src="/assets/base/ajax-loader.gif" /></p>');
          id.load($(this).dialog('option', 'url'));
        }
      });
    }

    $('.ui-draggable').draggable({
      opacity: 0.8,
      containment: 'DOM'
    });

    var detailButtonOffset = $(this).offset();
    var detailButtonWidth = $(this).width();
    var dialogOffset = detailDialog.dialog('option', 'width');
    var url = '/cpanel/analyzeActionSQL.do'
             +'?actionRequestId=' + actionRequestId
             +'&requestPath=' + encodeURIComponent(requestPath);

    detailDialog.dialog('option', 'position', [($(window).width() - dialogOffset) / 2, detailButtonOffset.top - $(window).scrollTop() + 25]);
    detailDialog.dialog('option', 'url', url);
    detailDialog.dialog('open');

    $('#ui-dialog-title-detail_dialog_' + hash).text('アクションで実行されたステートメント (' + requestPath + ')');
  });

  $('.detail_statement').click(function() {
    var buttonPrefix;
    var title;
    var hash = null;
    var sqlRequestId = null;

    // SQL の解析タブから遷移
    if (this.id.indexOf('hash') >= 0) {
      buttonPrefix = 'hash_';
      var pos = this.id.indexOf('_', buttonPrefix.length);

      title = ' ステートメントの実行結果 (ランク: ' + this.id.substring(buttonPrefix.length, pos) + ')';
      hash = this.id.substring(pos + 1);

    // アクションの解析タブから遷移
    } else {
      buttonPrefix = 'sqlRequestId_';
      title = 'ステートメントの実行結果';
      sqlRequestId = this.id.substring(buttonPrefix.length);
    }

    var detailDialog = $('#detail_dialog_' + sqlRequestId);

    if (detailDialog.size() == 0) {
      $("#dynamic_dialog").prepend('<div id="detail_dialog_' + sqlRequestId + '"></div>');
      detailDialog = $('#detail_dialog_' + sqlRequestId);

      detailDialog.dialog({
        autoOpen: false,
        width: dialogWidth,
        resizable: false,
        show: 'fade',
        hide: 'fade',
        open: function(event) {
          var id = $('#' + event.target.id);

          id.html('<p class="center"><img src="/assets/base/ajax-loader.gif" /></p>');
          id.load($(this).dialog('option', 'url'));
        }
      });
    }

    $('.ui-draggable').draggable({
      opacity: 0.8,
      containment: 'DOM'
    });

    var detailButtonOffset = $(this).offset();
    var detailButtonWidth = $(this).width();
    var dialogOffset = detailDialog.dialog('option', 'width');
    var url = '/cpanel/analyzeSQLDetail.do'
             +'?type=' + $('#type').val()
             +'&from=' + $('#from').val()
             +'&to=' + $('#to').val();
    var moduleName = $('#module').val();

    if (moduleName.length) {
      url += '&module=' + moduleName;
    }

    if (hash !== null) {
      url += '&hash=' + hash;
    } else {
      url += '&id=' + sqlRequestId;
    }

    detailDialog.dialog('option', 'position', [($(window).width() - dialogOffset) / 2, detailButtonOffset.top - $(window).scrollTop() + 25]);
    detailDialog.dialog('option', 'url', url);
    detailDialog.dialog('open');

    $('#ui-dialog-title-detail_dialog_' + sqlRequestId).text(title);
  });

  $('.delete_statement').click(function() {
    var buttonPrefix = 'delete_';
    var pos = this.id.indexOf('_', buttonPrefix.length);
    var target = this.id.substring(pos + 1);
    var dialogId = this.id.substring(buttonPrefix.length, pos);

    pos = target.indexOf('::');
    var url = '/cpanel/analyzeSQLDelete.do'
             +'?hash=' + target.substring(pos);
    $.post(url, null, null, 'html');

    var speed = 200;

    $('#col_rank_' + dialogId).slideUp(speed);
    $('#col_statement_' + dialogId).slideUp(speed);
    $('#col_request_count_' + dialogId).slideUp(speed);
    $('#col_average_process_time_' + dialogId).slideUp(speed);
    $('#col_max_process_time_' + dialogId).slideUp(speed);
    $('#col_last_access_date_' + dialogId).slideUp(speed);
    $('#col_action_' + dialogId).slideUp(speed);

    var type = $('#type').val();
    setTimeout(function() { $('#row_sql_' + type + '_' + dialogId).hide(); }, speed);
  });

  $('#reset').click(function() {
    $('#reset_confirm').dialog({
      modal: true,
      width: 400,
      minHeight: 'auto',
      buttons: {
        'はい': function() {
          $(this).dialog('close');

          var analyzeDataReset = $('#analyze_data_reset');
          analyzeDataReset.hide();

          var progress = $('#progress');
          progress.show();
          progress.html('<img src="/assets/base/ajax-loader.gif" style="margin-top: 0.4em" />');

          $.post(
            '/cpanel/analyzeDataReset.do',
            null,
            function(data, status) {
              analyzeDataReset.toggleClass('success', true);
              analyzeDataReset.hide();
              analyzeDataReset.text('全てのログを削除しました。');
              analyzeDataReset.show('slow');

              progress.hide();
            },
            'html'
          );
        },
        'いいえ': function() {
          $(this).dialog('close');
        }
      }
    });
  });

  var changeSort = function(target) {
    var data = {};
    data['type'] = 'POST';
    data['data'] = {};
    data['data'][target] = $('#' + target).val();

    $('#tabs').tabs({ajaxOptions: data});

    var currentIndex = $('#tabs').tabs('option', 'selected');
    $('#tabs').tabs('load', currentIndex);
  };

  // "アクションの解析" タブでソートボタンが押下された
  $('#orderByAction').change(function() {
    changeSort('orderByAction');
  });

  // "SQLの解析" タブでソートボタンが押下された
  $('#orderBySqlDefault').change(function() {
    changeSort('orderBySqlDefault');
  });

  // "SQLの解析(プリペアードステートメント)" タブでソートボタンが押下された
  $('#orderBySqlPrepared').change(function() {
    changeSort('orderBySqlPrepared');
  });

  $('#uninstall').click(function() {
    $('#uninstall_confirm').dialog({
      modal: true,
      width: 400,
      minHeight: 'auto',
      buttons: {
        'はい': function() {
          $(this).dialog('close');
          location.href = '/cpanel/performanceAnalyzerUninstall.do';
        },
        'いいえ': function() {
          $(this).dialog('close');
        }
      }
    });
  });
});
