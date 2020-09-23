import React from 'react';
import _ from 'underscore';
import MailPoet from 'mailpoet';
import PropTypes from 'prop-types';
import Background from 'common/background/background';
import Button from 'common/button/button';
import Heading from 'common/typography/heading/heading';
import Grid from 'common/grid';
import ListingHeadingStepsRoute from 'newsletters/listings/heading_steps_route.jsx';
import WelcomeScheduling from './scheduling.jsx';

const field = {
  name: 'options',
  label: 'Event',
  type: 'reactComponent',
  component: WelcomeScheduling,
};

class NewsletterWelcome extends React.Component {
  constructor(props) {
    super(props);
    let availableSegments = window.mailpoet_segments || [];
    let defaultSegment = 1;
    availableSegments = availableSegments.filter((segment) => segment.type === 'default');

    if (_.size(availableSegments) > 0) {
      defaultSegment = _.first(availableSegments).id;
    }

    this.state = {
      options: {
        event: 'segment',
        segment: defaultSegment,
        role: 'subscriber',
        afterTimeNumber: 1,
        afterTimeType: 'immediate',
      },
    };

    this.handleValueChange = this.handleValueChange.bind(this);
    this.handleNext = this.handleNext.bind(this);
  }

  handleValueChange(event) {
    const { state } = this;
    state[event.target.name] = event.target.value;
    this.setState(state);
  }

  handleNext() {
    MailPoet.Ajax.post({
      api_version: window.mailpoet_api_version,
      endpoint: 'newsletters',
      action: 'create',
      data: _.extend({}, this.state, {
        type: 'welcome',
        subject: MailPoet.I18n.t('draftNewsletterTitle'),
      }),
    }).done((response) => {
      this.showTemplateSelection(response.data.id);
    }).fail((response) => {
      if (response.errors.length > 0) {
        MailPoet.Notice.error(
          response.errors.map((error) => error.message),
          { scroll: true }
        );
      }
    });
  }

  showTemplateSelection(newsletterId) {
    this.props.history.push(`/template/${newsletterId}`);
  }

  render() {
    return (
      <div>
        <Background color="#fff" />

        <ListingHeadingStepsRoute emailType="welcome" automationId="welcome_email_creation_heading" />

        <Grid.Column align="center" className="mailpoet-schedule-email">
          <Heading level={4}>{MailPoet.I18n.t('selectEventToSendWelcomeEmail')}</Heading>

          <WelcomeScheduling
            item={this.state}
            field={field}
            onValueChange={this.handleValueChange}
          />

          <Button
            isFullWidth
            onClick={this.handleNext}
            type="button"
          >
            {MailPoet.I18n.t('next')}
          </Button>
        </Grid.Column>
      </div>
    );
  }
}

NewsletterWelcome.propTypes = {
  history: PropTypes.shape({
    push: PropTypes.func.isRequired,
  }).isRequired,
};

export default NewsletterWelcome;
