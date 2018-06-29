import React from 'react';
import AutomaticEmailsBreadcrumb from 'newsletters/types/automatic_emails/breadcrumb.jsx';
import AutomaticEmailEvent from 'newsletters/types/automatic_emails/event.jsx';
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
    MailPoet.trackEvent('Emails > Automatic Type selected', {
      'MailPoet Free version': window.mailpoet_version,
      'MailPoet Premium version': window.mailpoet_premium_version,
      'Email type': eventSlug,
    });
    this.props.router.push(`new/${this.email.slug}/${eventSlug}/conditions`);
  }

  displayEvents() {
    const events = _.map(this.emailEvents, (event, index) => (
      <AutomaticEmailEvent
        premium={this.email.premium}
        event={event}
        key={index}
        eventsConfigurator={this.eventsConfigurator}
      />
    )
    );

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
          {heading}
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
      email: PropTypes.shape({
        title: PropTypes.string.isRequired,
        slug: PropTypes.string.isRequired,
        premium: PropTypes.bool,
      }).isRequired,
    }).isRequired,
  }).isRequired,
  router: PropTypes.shape({
    push: PropTypes.func.isRequired,
  }).isRequired,
};

module.exports = AutomaticEmailEventsList;
