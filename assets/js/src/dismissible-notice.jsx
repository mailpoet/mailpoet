import jQuery from 'jquery';
import MailPoet from 'mailpoet';

jQuery(($) => {
  $(document).on('click', '.mailpoet-dismissible-notice .notice-dismiss', function dismiss() {
    const type = $(this).closest('.mailpoet-dismissible-notice').data('notice');
    $.ajax(window.ajaxurl,
      {
        type: 'POST',
        data: {
          action: 'dismissed_notice_handler',
          type,
        },
      });
  });
  $(document).on('click', '.notice .mailpoet-js-button-resume-sending', function resumeSending() {
    const noticeElement = $(this).closest('.notice');
    MailPoet.Ajax.post({
      api_version: window.mailpoet_api_version,
      endpoint: 'mailer',
      action: 'resumeSending',
    }).done(() => {
      noticeElement.slideUp();
      MailPoet.Notice.success(MailPoet.I18n.t('mailerSendingResumedNotice'));
      if (window.mailpoet_listing) { window.mailpoet_listing.forceUpdate(); }
    }).fail((response) => {
      MailPoet.Notice.showApiErrorNotice(response, { scroll: true });
    });
  });
});
