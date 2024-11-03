jQuery(document).ready(function ($) {

  $('#tpx-submit').click(function (e) {

    // var $attachmentID = $('#tpx-image-id').val()

    // $('#tpx-submit').attr('disable', true).css("background", "grey");
    // $('#tpx-loader-image').show();
    // $('#tpx-loader-text').show();

    data = {
      action: 'tpx_get_results',
    }

    $.post(ajaxurl, data, function (response) {
      $('#tpx-results').html(response);
      console.log('response:', response['attributes']['location']['name']);
      $('#tpx-submit').attr('disable', false).css("background", "red");
    })
    return false
  })

})
