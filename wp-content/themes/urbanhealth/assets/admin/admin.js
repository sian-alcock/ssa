var admin = admin || {};

(function ($) {
  admin = {
    init() {
      this.heroFeaturedImageEnforce();
      this.pageNavigation();
    },

    pageNavigation() {
      // Page navigation toggle
      var field = acf.getField('field_5fb3c2a4f343b');
      if (!field.val()) {
        $('.js-in-page-navigation').hide();
      }
      field.on('change', function () {
        if (field.val()) {
          $('.js-in-page-navigation').show();
        } else {
          $('.js-in-page-navigation').hide();
        }
      });
    },
    // If the hero chosen requires a featured image ensure one is present
    heroFeaturedImageEnforce() {
      $('#publish').on('click', function (e) {
        var heroTypeField = $('.js-hero-type');
        if (!heroTypeField.length) return;
        var heroTypeSelected = $('.selected input', heroTypeField).val();

        if (heroTypeSelected === 'hero_regular') {
          var imageSourceFieldRegular = $('.js-image-source-regular');
          if (!imageSourceFieldRegular.length) return;

          var imageSourceSelectedRegular = $('.selected input', imageSourceFieldRegular).val();
          if (imageSourceSelectedRegular !== 'featuredImage') return;
        } else {
          var imageSourceField = $('.js-image-source');
          if (!imageSourceField.length) return;

          var imageSourceSelected = $('.selected input', imageSourceField).val();
          if (imageSourceSelected !== 'featuredImage') return;
        }

        var hasFeaturedImage = $('#set-post-thumbnail').find('img').size() > 0;
        if (!hasFeaturedImage) {
          if (
            heroTypeSelected === 'hero_large' ||
            heroTypeSelected === 'hero_article' ||
            heroTypeSelected === 'hero_regular'
          ) {
            e.preventDefault();
            alert(
              'You have chosen to use a featured image for the hero but none supplied. Please upload a featured image.'
            );
          }
        }
      });
    },
  };

  admin.init();
})(jQuery);
