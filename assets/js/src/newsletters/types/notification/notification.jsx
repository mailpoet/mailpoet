import PropTypes from 'prop-types';
import React from 'react';
import MailPoet from 'mailpoet';
import ListingHeadingStepsRoute from 'newsletters/listings/heading_steps_route.jsx';
import _ from 'underscore';
import Scheduling from 'newsletters/types/notification/scheduling.jsx';
import Background from 'common/background/background';
import Button from 'common/button/button';
import Heading from 'common/typography/heading/heading';
import Grid from 'common/grid';
import { withRouter } from 'react-router-dom';
import { GlobalContext } from 'context/index.jsx';

const field = {
  name: 'options',
  type: 'reactComponent',
  component: Scheduling,
};

class NewsletterNotification extends React.Component {
  constructor(props) {
    super(props);
    this.state = {
      options: {
        intervalType: 'daily',
        timeOfDay: 0,
        weekDay: 1,
        monthDay: 0,
        nthWeekDay: 1,
      },
    };
  }

  handleValueChange = (event) => {
    const state = this.state;
    state[event.target.name] = event.target.value;
    this.setState(state);
  };

  handleNext = () => {
    MailPoet.Ajax.post({
      api_version: window.mailpoet_api_version,
      endpoint: 'newsletters',
      action: 'create',
      data: _.extend({}, this.state, {
        type: 'notification',
        subject: MailPoet.I18n.t('draftPostNotificationTitle'),
      }),
    }).done((response) => {
      this.showTemplateSelection(response.data.id);
    }).fail((response) => {
      if (response.errors.length > 0) {
        this.context.notices.error(
          response.errors.map((error) => <p key={error.message}>{error.message}</p>),
          { scroll: true }
        );
      }
    });
  };

  showTemplateSelection = (newsletterId) => {
    this.props.history.push(`/template/${newsletterId}`);
  };

  render() {
    return (
      <div>
        <Background color="#fff" />

        <ListingHeadingStepsRoute emailType="notification" automationId="post_notification_creation_heading" />

        <Grid.Column align="center" className="mailpoet-schedule-email">
          <Heading level={4}>{MailPoet.I18n.t('selectFrequency')}</Heading>

          <Scheduling
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

NewsletterNotification.contextType = GlobalContext;

NewsletterNotification.propTypes = {
  history: PropTypes.shape({
    push: PropTypes.func.isRequired,
  }).isRequired,
};

export default withRouter(NewsletterNotification);
