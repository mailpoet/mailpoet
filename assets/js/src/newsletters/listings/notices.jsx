import React from "react";
import MailPoet from "mailpoet";

import { MailerMixin } from 'newsletters/listings/mixins.jsx'

const ListingNotices = React.createClass({
  mixins: [MailerMixin],
  render() {
    // display sending error
    if (this.props.mailer_log.error) {
      let mailer_error_notice = null;
      if (this.props.mailer_log.error.operation === 'send') {
        mailer_error_notice =
          MailPoet.I18n.t('mailerSendErrorNotice')
            .replace('%$1s', this.props.mailer_config.method)
            .replace('%$2s', this.props.mailer_log.error.error_message);
      } else {
        mailer_error_notice =
          MailPoet.I18n.t('mailerConnectionErrorNotice')
            .replace('%$1s', this.props.mailer_log.error.error_message);
      }
      return (
        <div className="mailpoet_notice mailpoet_sending_status error">
          <p>{ mailer_error_notice }</p>
          <p>{ MailPoet.I18n.t('mailerResumeSendingNotice') }</p>
          <p>
            <a href="javascript:;"
               className="button"
               onClick={ this.resumeSending }>{ MailPoet.I18n.t('mailerResumeSendingButton') }</a>
          </p>
        </div>
      )
    }
    return null;
  }
});

module.exports = ListingNotices;