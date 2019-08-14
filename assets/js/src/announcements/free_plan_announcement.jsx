import React from 'react';
import MailPoet from 'mailpoet';

class FreePlanAnnouncement extends React.Component {
  constructor(props) {
    super(props);
    this.dismissNotice = this.dismissNotice.bind(this);
    this.state = {
      announcement_seen: window.mailpoet_free_plan_announcement_seen,
    };
  }

  dismissNotice() {
    this.setState({ announcement_seen: true });
    window.mailpoet_free_plan_announcement_seen = true;
    MailPoet.Ajax.post({
      api_version: window.mailpoet_api_version,
      endpoint: 'settings',
      action: 'set',
      data: {
        free_plan_announcement_seen: 1,
      },
    });
  }

  render() {
    return (
      (!this.state.announcement_seen)
      && (
        <div className="mailpoet_free_plan_announcement" data-automation-id="free-plan-announcement">
          <h3>{MailPoet.I18n.t('freePlanTitle')}</h3>
          <p>{MailPoet.I18n.t('freePlanDescription')}</p>
          <a
            className="button-primary"
            href={MailPoet.MailPoetUrlFactory.getFreePlanUrl()}
            target="_blank"
            rel="noopener noreferrer"
          >
            {MailPoet.I18n.t('freePlanButton')}
          </a>
          <button
            type="button"
            className="notice-dismiss"
            onClick={this.dismissNotice}
          >
            <span className="screen-reader-text">{MailPoet.I18n.t('dismissButton')}</span>
          </button>
        </div>
      )
    );
  }
}

export default FreePlanAnnouncement;
