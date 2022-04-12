import _ from 'underscore';
import { Component } from 'react';
import PropTypes from 'prop-types';
import { withRouter } from 'react-router-dom';

import { AutomaticEmailEvent } from 'newsletters/types/automatic_emails/event.jsx';
import { MailPoet } from 'mailpoet';

class AutomaticEmailEventsListComponent extends Component {
  constructor(props) {
    super(props);
    this.email = this.props.email;
    this.emailEvents = this.email.events;
    this.eventsConfigurator = this.eventsConfigurator.bind(this);
  }

  eventsConfigurator(eventSlug) {
    MailPoet.trackEvent('Emails > Automatic Type selected', {
      'Email type': eventSlug,
    });
    this.props.history.push(`/new/${this.email.slug}/${eventSlug}/conditions`);
  }

  render() {
    const events = _.map(this.emailEvents, (event, index) => (
      <AutomaticEmailEvent
        premium={this.email.premium}
        event={event}
        key={index}
        eventsConfigurator={this.eventsConfigurator}
      />
    ));

    return events;
  }
}

AutomaticEmailEventsListComponent.propTypes = {
  email: PropTypes.shape({
    title: PropTypes.string.isRequired,
    slug: PropTypes.string.isRequired,
    premium: PropTypes.bool,
  }).isRequired,
  history: PropTypes.shape({
    push: PropTypes.func.isRequired,
  }).isRequired,
};

export const AutomaticEmailEventsList = withRouter(
  AutomaticEmailEventsListComponent,
);
