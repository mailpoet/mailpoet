import _ from 'underscore';
import { Component } from 'react';
import jQuery from 'jquery';
import PropTypes from 'prop-types';

import { Background } from 'common/background/background';
import { Button } from 'common/button/button';
import { Heading } from 'common/typography/heading/heading';
import { GlobalContext } from 'context/index.jsx';
import { Grid } from 'common/grid';
import { ListingHeadingStepsRoute } from 'newsletters/listings/heading_steps_route';
import { EventScheduling } from 'newsletters/automatic_emails/events/event_scheduling.jsx';
import { EventOptions } from 'newsletters/automatic_emails/events/event_options';
import { MailPoet } from 'mailpoet';
import { Selection } from 'form/fields/selection.jsx';

const defaultAfterTimeType = 'immediate';
const defaultAfterTimeNumber = 1;

class EventsConditions extends Component {
  static getEventOptions(event) {
    return event.options || null;
  }

  static getEventOptionsFirstValue(eventOptions) {
    if (!eventOptions) return null;

    return _.isArray(eventOptions.values) && eventOptions.values[0].id
      ? eventOptions.values[0].id
      : null;
  }

  constructor(props) {
    super(props);
    const { email, name } = props;
    this.handleChange = this.handleChange.bind(this);
    this.handleNextStep = this.handleNextStep.bind(this);
    this.email = email;
    this.emailEvents = this.email.events;
    this.segments = _.filter(
      window.mailpoet_segments,
      (segment) => segment.deleted_at === null,
    );

    const currentEvent = this.getEvent(name);
    const currentEventOptions = this.constructor.getEventOptions(currentEvent);
    const currentEventOptionValue =
      this.constructor.getEventOptionsFirstValue(currentEventOptions);

    this.state = {
      event: currentEvent,
      eventSlug: currentEvent.slug,
      eventOptionValue: currentEventOptionValue,
      segment: currentEvent.sendToLists ? this.getFirstSegment() : null,
      afterTimeType: currentEvent.defaultAfterTimeType || defaultAfterTimeType,
      afterTimeNumber: null,
    };
  }

  handleChange(data) {
    const { segment, afterTimeNumber } = this.state;
    const newState = data;

    if (newState.eventSlug) {
      newState.event = this.getEvent(newState.eventSlug);

      // keep the existing segment (if set) or set it to the first segment in the list
      newState.segment = newState.event.sendToLists
        ? segment || this.getFirstSegment()
        : null;

      // if the new event doesn't have options, reset the currently selected option value
      const eventOptions = this.constructor.getEventOptions(newState.event);
      newState.eventOptionValue = eventOptions
        ? this.constructor.getEventOptionsFirstValue(eventOptions)
        : null;
    }

    if (newState.afterTimeType && newState.afterTimeType === 'immediate') {
      newState.afterTimeNumber = null;
    } else if (
      newState.afterTimeType &&
      !newState.afterTimeNumber &&
      !afterTimeNumber
    ) {
      newState.afterTimeNumber = defaultAfterTimeNumber;
    }

    this.setState(newState);
    // we need to reset validation due to changes in the afterTimeType field
    this.resetValidationErrors();
    if (this.isValid()) {
      this.validate();
    }
  }

  handleNextStep(e) {
    e.preventDefault();
    if (!this.isValid()) {
      this.validate();
      return;
    }
    const { history } = this.props;
    const {
      eventSlug,
      afterTimeType,
      afterTimeNumber,
      event,
      segment,
      eventOptionValue,
    } = this.state;
    const options = {
      group: this.email.slug,
      event: eventSlug,
      afterTimeType,
    };

    if (afterTimeNumber) options.afterTimeNumber = afterTimeNumber;
    options.sendTo = event.sendToLists ? 'segment' : 'user';
    if (segment) options.segment = segment;
    if (eventOptionValue) {
      options.meta = JSON.stringify({ option: eventOptionValue });
    }

    MailPoet.Ajax.post({
      api_version: window.mailpoet_api_version,
      endpoint: 'newsletters',
      action: 'create',
      data: {
        type: 'automatic',
        subject: MailPoet.I18n.t('draftNewsletterTitle'),
        options,
      },
    })
      .done((response) => {
        MailPoet.trackEvent('Emails > New Automatic Email Created', {
          'Event type': options.event,
          'Schedule type': options.afterTimeType,
          'Schedule value': options.afterTimeNumber,
        });
        history.push(`/template/${response.data.id}`);
      })
      .fail((response) => {
        if (response.errors.length > 0) {
          this.context.notices.error(
            response.errors.map((error) => (
              <p key={error.message}>{error.message}</p>
            )),
            { scroll: true },
          );
        }
      });
  }

  getEvent(eventSlug) {
    return this.emailEvents[eventSlug];
  }

  getFirstSegment() {
    return _.isArray(this.segments) && this.segments[0].id
      ? this.segments[0].id
      : null;
  }

  isValid = () => {
    if (jQuery('#newsletter_scheduling').parsley()) {
      return jQuery('#newsletter_scheduling').parsley().isValid();
    }
    return true;
  };

  validate = () => {
    if (jQuery('#newsletter_scheduling').parsley()) {
      jQuery('#newsletter_scheduling').parsley().validate();
    }
  };

  resetValidationErrors = () => {
    if (jQuery('#newsletter_scheduling').parsley()) {
      jQuery('#newsletter_scheduling').parsley().reset();
    }
  };

  displayHeader() {
    const { event } = this.state;
    return event.title;
  }

  displayEventOptions() {
    const { eventSlug, event } = this.state;
    const props = {
      emailSlug: this.email.slug,
      eventSlug,
      eventOptions: this.constructor.getEventOptions(event),
      onValueChange: this.handleChange,
    };

    return (
      <EventOptions
        emailSlug={props.emailSlug}
        eventSlug={props.eventSlug}
        eventOptions={props.eventOptions}
        onValueChange={props.onValueChange}
      />
    );
  }

  displaySegments() {
    const { event } = this.state;
    if (!event.sendToLists) return null;

    const props = {
      field: {
        id: 'segments',
        forceSelect2: true,
        values: this.segments,
        extendSelect2Options: {
          minimumResultsForSearch: Infinity,
        },
      },
      onValueChange: (e) => this.handleChange({ segment: e.target.value }),
    };

    return (
      <div className="event-segment-selection">
        <Selection field={props.field} onValueChange={props.onValueChange} />
      </div>
    );
  }

  displayScheduling() {
    const { afterTimeNumber, afterTimeType, event } = this.state;
    const props = {
      item: {
        afterTimeNumber,
        afterTimeType,
      },
      event,
      onValueChange: this.handleChange,
    };

    return (
      <EventScheduling
        item={props.item}
        event={props.event}
        onValueChange={props.onValueChange}
      />
    );
  }

  displayEventTip() {
    const { event } = this.state;
    return event.tip ? (
      <p className="description">
        <strong>{MailPoet.I18n.t('tip')}</strong> {event.tip}
      </p>
    ) : null;
  }

  render() {
    return (
      <div>
        <Background color="#fff" />

        <ListingHeadingStepsRoute
          emailType="woocommerce"
          automationId="woocommerce_email_creation_heading"
        />

        <Grid.Column align="center" className="mailpoet-schedule-email">
          <form id="newsletter_scheduling">
            <Heading level={4}>{this.displayHeader()}</Heading>
            <div>{this.displayEventOptions()}</div>
            <div>{this.displaySegments()}</div>
            <div>{this.displayScheduling()}</div>

            <Button isFullWidth onClick={this.handleNextStep} type="submit">
              {MailPoet.I18n.t('next')}
            </Button>
          </form>
        </Grid.Column>

        {this.displayEventTip()}
      </div>
    );
  }
}

EventsConditions.contextType = GlobalContext;

EventsConditions.propTypes = {
  history: PropTypes.shape({
    push: PropTypes.func.isRequired,
  }).isRequired,
  name: PropTypes.string.isRequired,
  email: PropTypes.shape({
    title: PropTypes.string.isRequired,
    slug: PropTypes.string.isRequired,
  }).isRequired,
};

EventsConditions.displayName = 'EventsConditions';

export { EventsConditions };
