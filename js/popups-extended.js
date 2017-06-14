(function ($) {

  var hash = window.location.hash;

  function PopupsExtended(el) {
    var $popup = $(el);
    var self = this;
    if (spuvar.ajax_mode) {
      new Foundation.Reveal($popup);
    }

    this.el = el;
    this.$popup = $popup;
    this.isAdmin = spuvar.is_admin;

    this.spuId = parseInt($popup.data('spuId'), 10);
    this.triggerMethod = $popup.data('spuTrigger');
    this.triggerNumber = parseInt($popup.data('spuTriggerNumber'), 10);
    this.autoHide = parseInt($popup.data('spuAutoHide'), 10) === 1;
    this.cookieDays = $popup.data('spuCookie');
    this.closeCookieDays = parseInt($popup.data('spuCloseCookie'), 10);
    this.testMode = parseInt($popup.data('spuTestMode'), 10) === 1;
    this.conversionClose = parseInt($popup.data('spuConversionClose'), 10) === 1;
    this.eventCategory = $popup.data('eventCategory');
    this.eventLabel = $popup.data('eventLabel');

    if (spuvar.safe_mode) {
      this.$popup.prependTo('.body');
    }

    if (this.isActive()) {
      switch (this.triggerMethod) {
        case 'percentage':
          this.triggerHeight();
          break;
        case 'seconds':
          this.triggerTimer();
          break;
        case 'exit':
          this.triggerExit();
          break;
      }
    }

    // Always show the popup if it's referenced in the URL hash.
    if (hash && hash.length && hash.substring(1) === $popup.prop('id')) {
      this.openPopup();
    }

    // Direct link.
    $(document).on('click', '.spu-open-' + this.spuId, function (e) {
      self.openPopup();
      e.preventDefault();
      return false;
    });


    this.$popup.on('click', '.spu-close-link', function (e) {
      self.closePopup();
    });

    this.$popup.on('click', '.spu-convert-link', function () {
      self.convert();
      self.closePopup();
    });

    $(document).on('gform_confirmation_loaded', function (e, formId) {
      self.convert();
    });

    $(document).on('closed.zf.reveal', function (e) {
      // Exit if the event is for a different dialog.
      if (e.target !== el) {
        return;
      }
      // The conversion process already tracked
      if (!self.isConverted) {
        self.setCookie();
        self.trackEvent('Close');
      }
    });
  }

  PopupsExtended.prototype.openPopup = function() {
    this.trackEvent('Show');
    this.$popup.foundation('open');
  };

  PopupsExtended.prototype.closePopup = function() {
    this.$popup.foundation('close');
  };

  PopupsExtended.prototype.convert = function() {
    this.isConverted = true;
    this.setCookie();
    this.trackEvent('Convert');
  };

  PopupsExtended.prototype.isTestMode = function() {
    return this.testMode && this.isAdmin;
  };

  PopupsExtended.prototype.isActive = function() {
    return !Cookies.get('spu_box' + this.spuId) || this.isTestMode();
  };

  PopupsExtended.prototype.setCookie = function() {
    var days = this.isConverted ? this.cookieDays : this.closeCookieDays;
    Cookies.set('spu_box' + this.spuId, true, { expires: days });
  };

  /**
   * Track an event using wp-genero-analytics if available.
   */
  PopupsExtended.prototype.trackEvent = function (eventAction) {
    var isTrackEvent = !this.isTestMode() && window.Gevent && this.eventCategory && this.eventCategory.length;

    if (isTrackEvent) {
      window.Gevent(this.eventCategory, eventAction, this.eventLabel);
    }
    console.log('Track Event: ' + this.eventCategory + ' - ' + eventAction + ' - ' + this.eventLabel);
  };

  PopupsExtended.prototype.triggerExit = function () {
    var self = this;
    Bounceback.init({
      maxDisplay: 0,
      aggressive: true,
      cookieLife: 0,
      onBounce: function() {
        self.openPopup();
        Bounceback.disable();
      }
    })
  };

  PopupsExtended.prototype.triggerHeight = function() {
    var $window = $(window);
    var windowHeight = $window.height();
    var self = this;

    var triggerPercentage = this.triggerNumber / 100;
    var triggerHeight = triggerPercentage * $(document).height();

    $window.on('scroll.popupsExtended', Foundation.util.throttle(function () {
      var scrollY = $(window).scrollTop();
      var isTriggered = ((scrollY + windowHeight) >= triggerHeight);
      if (isTriggered) {
        $window.off('scroll.popupsExtended');
        self.openPopup();
      }
    }, 100))
  };

  PopupsExtended.prototype.triggerTimer = function() {
    var self = this;
    var ms = this.triggerNumber * 1000;
    window.setTimeout(function () {
      self.openPopup();
    }, ms);
  };

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
    $('.spu-box form').each(function () {
      var $this = $(this);
      var action = $this.prop('action');
      if (action) {
        action = action.replace('?spu_action=spu_load', '?');
        $this.attr('action', action);
      }
    });
  }

  function init() {
    $('.spu-box').each(function () {
      this.popupsExtended = new PopupsExtended(this);
    });
  }

  $(document).on('ready', function () {
    if (spuvar.ajax_mode) {
      requestAjaxPopups(function (response) {
        $('body').append(response);
        $('.spu-box').imagesLoaded(function() {
          init();
          reloadForms();
        });
      });
    }
    else {
      $('.spu-box').imagesLoaded(function() {
        init();
      });
    }
  });
}(jQuery));
