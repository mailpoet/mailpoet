import jQuery from 'jquery';

jQuery(($) => {
  $(document).on(
    'click',
    '.mailpoet-dismissible-notice .notice-dismiss',
    function dismiss() {
      const notice = $(this).closest('.mailpoet-dismissible-notice');
      $.ajax(window.ajaxurl, {
        type: 'POST',
        data: {
          action: 'dismissed_notice_handler',
          type: notice.data('notice'),
          nonce: notice.data('nonce'),
        },
      });
    },
  );
});
