import PropTypes from 'prop-types';
import React from 'react';
import MailPoet from 'mailpoet';
import Breadcrumb from 'newsletters/breadcrumb.jsx';
import _ from 'underscore';
import Scheduling from 'newsletters/types/notification/scheduling.jsx';
import { withRouter } from 'react-router-dom';

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
        MailPoet.Notice.error(
          response.errors.map((error) => error.message),
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
        <h1>{MailPoet.I18n.t('postNotificationNewsletterTypeTitle')}</h1>
        <Breadcrumb step="type" />

        <h3>{MailPoet.I18n.t('selectFrequency')}</h3>

        <Scheduling
          item={this.state}
          field={field}
          onValueChange={this.handleValueChange}
        />

        <p className="submit">
          <input
            className="button button-primary"
            type="button"
            onClick={this.handleNext}
            value={MailPoet.I18n.t('next')}
          />
        </p>
      </div>
    );
  }
}

NewsletterNotification.propTypes = {
  history: PropTypes.shape({
    push: PropTypes.func.isRequired,
  }).isRequired,
};

export default withRouter(NewsletterNotification);
