import { Component } from 'react';
import _ from 'underscore';
import jQuery from 'jquery';
import PropTypes from 'prop-types';
import { Background } from 'common/background/background';
import { Button } from 'common/button/button';
import { Heading } from 'common/typography/heading/heading';
import { Grid } from 'common/grid';
import { ListingHeadingStepsRoute } from 'newsletters/listings/heading_steps_route';
import { MailPoet } from 'mailpoet';
import { WelcomeScheduling } from './scheduling.jsx';

const field = {
  name: 'options',
  label: 'Event',
  type: 'reactComponent',
  component: WelcomeScheduling,
};

class NewsletterWelcome extends Component {
  constructor(props) {
    super(props);
    let availableSegments = window.mailpoet_segments || [];
    let defaultSegment = 1;
    availableSegments = availableSegments.filter(
      (segment) => segment.type === 'default',
    );

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

  handleNext(event) {
    event.preventDefault();
    if (!this.isValid()) {
      this.validate();
      return;
    }
    MailPoet.Ajax.post({
      api_version: window.mailpoet_api_version,
      endpoint: 'newsletters',
      action: 'create',
      data: _.extend({}, this.state, {
        type: 'welcome',
        subject: MailPoet.I18n.t('draftNewsletterTitle'),
      }),
    })
      .done((response) => {
        this.showTemplateSelection(response.data.id);
      })
      .fail((response) => {
        if (response.errors.length > 0) {
          MailPoet.Notice.error(
            response.errors.map((error) => error.message),
            { scroll: true },
          );
        }
      });
  }

  isValid = () => jQuery('#welcome_scheduling').parsley().isValid();

  validate = () => jQuery('#welcome_scheduling').parsley().validate();

  showTemplateSelection(newsletterId) {
    this.props.history.push(`/template/${newsletterId}`);
  }

  render() {
    return (
      <div>
        <Background color="#fff" />

        <ListingHeadingStepsRoute
          emailType="welcome"
          automationId="welcome_email_creation_heading"
        />

        <Grid.Column align="center" className="mailpoet-schedule-email">
          <Heading level={4}>
            {MailPoet.I18n.t('selectEventToSendWelcomeEmail')}
          </Heading>
          <form id="welcome_scheduling">
            <WelcomeScheduling
              item={this.state}
              field={field}
              onValueChange={this.handleValueChange}
            />

            <Button isFullWidth type="submit" onClick={this.handleNext}>
              {MailPoet.I18n.t('next')}
            </Button>
          </form>
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

NewsletterWelcome.displayName = 'NewsletterWelcome';

export { NewsletterWelcome };
