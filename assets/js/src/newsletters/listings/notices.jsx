import React from "react";
import MailPoet from "mailpoet";

const ListingNotices = React.createClass({
  resumeSending() {
    MailPoet.Ajax.post({
      endpoint: 'mailer',
      action: 'resumeSending'
    }).done(function() {
      MailPoet.Notice.hide('mailpoet_mailer_error');
      MailPoet.Notice.success(MailPoet.I18n.t('mailerSendingResumedNotice'));
    }).fail((response) => {
      if (response.errors.length > 0) {
        MailPoet.Notice.error(
          response.errors.map(function(error) { return error.message; }),
          { scroll: true }
        );
      }
    });
  },
  render() {
    let mailer_error_notice;
    if (this.props.mta_log.error.operation === 'send') {
      mailer_error_notice =
        MailPoet.I18n.t('mailerSendErrorNotice')
          .replace('%$1s', this.props.mta_method)
          .replace('%$2s', this.props.mta_log.error.error_message);
    } else {
      mailer_error_notice =
        MailPoet.I18n.t('mailerConnectionErrorNotice')
          .replace('%$1s', this.props.mta_log.error.error_message);
    }
    return (
      <div>
        <p>{ mailer_error_notice }</p>
        <p>{ MailPoet.I18n.t('mailerResumeSendingNotice') }</p>
        <p>
          <a href="javascript:;"
             className="button"
             onClick={ this.resumeSending }
          >{ MailPoet.I18n.t('mailerResumeSendingButton') }</a>
        </p>
      </div>
    );
  }
});

module.exports = ListingNotices;