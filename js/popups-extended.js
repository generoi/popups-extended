(function ($) {

  function popupsExtended() {
    var $popups = $('.spu-box');

    $popups.each(function () {
      var $popup = $(this);
      var reveal = new Foundation.Reveal($popup);
      var hash = window.location.hash;

      var id = parseInt($popup.data('spuId'), 10);
      var triggerMethod = $popup.data('spuTrigger');
      var triggerNumber = parseInt($popup.data('spuTriggerNumber'), 10);
      var autoHide = parseInt($popup.data('spuAutoHide'), 10) === 1;
      var cookieDays = $popup.data('spuCookie');
      var closeCookieDays = parseInt($popup.data('spuCloseCookie'), 10);
      var testMode = parseInt($popup.data('spuTestMode'), 10) === 1;
      var conversionClose = parseInt($popup.data('spuConversionClose'), 10) === 1;

      var triggerSeconds = triggerNumber;
      var triggerPercentage = triggerMethod === 'percentage' ? (triggerNumber / 100) : 0.8;
      var triggerHeight = triggerPercentage * $(document).height();

      var cookieId = 'spu_box' + id;
      var cookieValue = Cookies.get(cookieId);

      // Exit if already shown.
      if (cookieValue && !(spuvar.is_admin && testMode)) {
        return;
      }

      if (spuvar.safe_mode) {
        $popup.prependTo('.body');
      }

      switch (triggerMethod) {
        case 'percentage':
          triggerHeight($popup, triggerHeight);
          break;
        case 'seconds':
          triggerTimer($popup, triggerSeconds);
          break;
        case 'exit':
          triggerExit($popup);
          break;
      }

      // Always show the popup if it's referenced in the URL hash.
      if (hash && hash.length && hash.substring(1) === $popup.prop('id')) {
        openPopup($popup);
      }

      $popup.on('closed.zf.reveal', function () {
        Cookies.set(cookieId, true, { expires: closeCookieDays });
      });
      $popup.on('open.zf.reveal', function () {
      });
    });
  }

  function openPopup($popup) {
    $popup.foundation('open');
  }
  function closePopup($popup) {
    $popup.foundation('close');
  }

  function triggerExit($popup) {
    Bounceback.init({
      maxDisplay: 0,
      aggressive: true,
      cookieLife: 0,
      onBounce: function() {
        openPopup($popup);
        Bounceback.disable();
      }
    })
  }

  function triggerHeight($popup, triggerHeight) {
    var $window = $(window);
    var windowHeight = $window.height();

    $window.on('scroll.popupsExtended', Foundation.util.throttle(function () {
      var scrollY = $(window).scrollTop();
      var isTriggered = ((scrollY + windowHeight) >= triggerHeight);
      if (isTriggered) {
        $window.off('scroll.popupsExtended');
        openPopup($popup);
      } else {
        closePopup($popup);
      }
    }, 100))
  }

  function triggerTimer($popup, seconds) {
    window.setTimeout(function () {
      openPopup($popup);
    }, seconds + 1000);
  }

  function requestAjaxPopups(cb) {
    $.ajax({
      url: spuvar.ajax_mode_url,
      dataType: 'html',
      cache: false,
      type: 'POST',
      timeout: 30000,
      data: {
        pid: spuvar.pid,
        referrer: document.referrer,
        query_string: document.location.search,
        is_category: spuvar.is_category,
        is_archive: spuvar.is_archive
      },
      success: cb,
      error: function (data, error, errorThrown) {
        console.log('Problem loading popups - error: ' + error + ' - ' + errorThrown);
      }
    });
  }

  function reloadForms() {
    $('.spu-box').each(function () {
      var $this = $(this);
      var action = $this.prop('action');
      if (action) {
        $this.prop('action', action.replace('?spu_action=spu_load', ''));
      }
    });
  }

  $(document).on('ready', function () {
    if (spuvar.ajax_mode) {
      requestAjaxPopups(function (response) {
        $('body').append(response);
        $('.spu-box').imagesLoaded(function() {
          //window.SPU = SPU_master();
          window.popupsExtended = popupsExtended();
          reloadForms();
        });
      });
    }
  });
}(jQuery));
