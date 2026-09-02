import jQuery from 'jquery';

jQuery(($) => {
  $(document).on(
    'click',
    '.mailpoet-dismissible-notice .notice-dismiss',
    function dismiss() {
      const notice = $(this).closest('.mailpoet-dismissible-notice');
      const type = notice.data('notice');
      // notices without a name render no data attributes and cannot be persisted
      if (!type) return;
      $.ajax(window.ajaxurl, {
        type: 'POST',
        data: {
          action: 'dismissed_notice_handler',
          type,
          nonce: notice.data('nonce'),
        },
      });
    },
  );
});
