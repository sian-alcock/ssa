var tracking = tracking || {};

(function ($) {
  tracking = {
    init() {
      $(document).on('gform_confirmation_loaded', function (event, formId) {
        // code to be trigger when confirmation page is loaded of an ajax submission
        var formWrapper = $("*[data-form-id='" + formId + "']");
        var name = formId;

        if (formWrapper.length) {
          name = $(formWrapper).data('form-name');
        }
        if (window.dataLayer) {
          dataLayer.push({
            event: 'form-submission',
            name: name,
          });
        }
      });
    },
  };

  tracking.init();
})(jQuery);
