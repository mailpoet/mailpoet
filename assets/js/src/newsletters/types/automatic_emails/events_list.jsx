import React from 'react';
import AutomaticEmailsBreadcrumb from 'newsletters/types/automatic_emails/breadcrumb.jsx';
import MailPoet from 'mailpoet';
import _ from 'underscore';

class AutomaticEmailEventsList extends React.Component {
  constructor(props) {
    super(props);
    this.automaticEmail = this.props.route.data.automaticEmail;
    this.automaticEmailEvents = this.automaticEmail.events;
    this.eventsConfigurator = this.eventsConfigurator.bind(this);
  }

  eventsConfigurator(eventSlug) {
    this.props.router.push(`new/${this.automaticEmail.slug}/${eventSlug}/conditions`);
  }

  displayEvents() {
    const events = _.map(this.automaticEmailEvents, (event, index) => {
      let action;

      if (this.automaticEmail.premium) {
        action = (
          <a href="?page=mailpoet-premium"
            target="_blank"
          >
            {MailPoet.I18n.t('premiumFeatureLink')}
          </a>
        );
      } else {
        const disabled = event.soon;

        action = (
          <a className="button button-primary"
            disabled={disabled}
            onClick={!disabled ? this.eventsConfigurator.bind(null, event.slug) : null}
          >
            {event.actionButtonTitle || MailPoet.I18n.t('setUp')}
          </a>
        );
      }

      return (
        <li key={index} data-type={event.slug}>
          <div>
            <div className="mailpoet_thumbnail">
              {event.thumbnailImage ? <img src={event.thumbnailImage} alt="" /> : null}
            </div>
            <div className="mailpoet_description">
              <div className="title_and_badge">
                <h3>{event.title} {event.soon ? `(${MailPoet.I18n.t('soon').toLowerCase()})` : ''}</h3>
                {event.badge ? (
                  <span className={`mailpoet_badge mailpoet_badge_${event.badge.style}`}>
                    {event.badge.text}
                  </span>
                  ) : ''
                }
              </div>
              <p>{event.description}</p>
            </div>
            <div className="mailpoet_actions">
              {action}
            </div>
          </div>
        </li>
      );
    });

    return (
      <ul className="mailpoet_boxes woocommerce clearfix">
        {events}
      </ul>
    );
  }

  render() {
    const heading = MailPoet.I18n.t('selectAutomaticEmailsEventsHeading')
      .replace('%1s', this.automaticEmail.title);

    return (
      <div>
        <h1>
          {heading} ({MailPoet.I18n.t('beta').toLowerCase()})
        </h1>

        <AutomaticEmailsBreadcrumb step="events" />

        {this.displayEvents()}
      </div>
    );
  }
}

module.exports = AutomaticEmailEventsList;
