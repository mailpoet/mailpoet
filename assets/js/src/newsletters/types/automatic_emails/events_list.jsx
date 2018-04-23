import React from 'react';
import AutomaticEmailsBreadcrumb from 'newsletters/types/automatic_emails/breadcrumb.jsx';
import MailPoet from 'mailpoet';
import _ from 'underscore';
import PropTypes from 'prop-types';

class AutomaticEmailEventsList extends React.Component {
  constructor(props) {
    super(props);
    this.email = this.props.route.data.email;
    this.emailEvents = this.email.events;
    this.eventsConfigurator = this.eventsConfigurator.bind(this);
  }

  eventsConfigurator(eventSlug) {
    this.props.router.push(`new/${this.email.slug}/${eventSlug}/conditions`);
  }

  displayEvents() {
    const events = _.map(this.emailEvents, (event, index) => {
      let action;

      if (this.email.premium) {
        action = (
          <a
            href="?page=mailpoet-premium"
            target="_blank"
          >
            {MailPoet.I18n.t('premiumFeatureLink')}
          </a>
        );
      } else {
        const disabled = event.soon;

        action = (
          <a
            className="button button-primary"
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
      .replace('%1s', this.email.title);

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

AutomaticEmailEventsList.propTypes = {

  route: PropTypes.shape({
    data: PropTypes.shape({
      email: PropTypes.string.isRequired,
    }).isRequired,
  }).isRequired,

  router: PropTypes.shape({
    push: PropTypes.func.isRequired,
  }).isRequired,

};

module.exports = AutomaticEmailEventsList;
