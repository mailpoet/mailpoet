import jQuery from 'jquery';

jQuery(($) => {
  $(document).on(
    'click',
    '.mailpoet-dismissible-notice .notice-dismiss',
    function dismiss() {
      const type = $(this)
        .closest('.mailpoet-dismissible-notice')
        .data('notice');
      $.ajax(window.ajaxurl, {
        type: 'POST',
        data: {
          action: 'dismissed_notice_handler',
          type,
        },
      });
    },
  );
});
