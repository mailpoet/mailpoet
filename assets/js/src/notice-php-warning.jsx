import jQuery from 'jquery';

jQuery(($) => {
  $(document).on('click', '.notice-php-warning .notice-dismiss', function xyz() {
    const type = $(this).closest('.notice-php-warning').data('notice');
    $.ajax(window.ajaxurl,
      {
        type: 'POST',
        data: {
          action: 'dismissed_notice_handler',
          type,
        },
      });
  });
});
