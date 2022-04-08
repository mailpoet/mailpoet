import PropTypes from 'prop-types';
import { Component } from 'react';
import MailPoet from 'mailpoet';
import ListingHeadingStepsRoute from 'newsletters/listings/heading_steps_route';
import { withRouter } from 'react-router-dom';
import { GlobalContext } from 'context/index.jsx';

class NewsletterStandard extends Component {
  componentDidMount() {
    // No options for this type, create a newsletter upon mounting
    MailPoet.Ajax.post({
      api_version: window.mailpoet_api_version,
      endpoint: 'newsletters',
      action: 'create',
      data: {
        type: 'standard',
      },
    })
      .done((response) => {
        this.showTemplateSelection(response.data.id);
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

  showTemplateSelection = (newsletterId) => {
    this.props.history.push(`/template/${newsletterId}`);
  };

  render() {
    return (
      <div>
        <ListingHeadingStepsRoute
          emailType="standard"
          automationId="standard_newsletter_creation_heading"
        />
      </div>
    );
  }
}

NewsletterStandard.contextType = GlobalContext;

NewsletterStandard.propTypes = {
  history: PropTypes.shape({
    push: PropTypes.func.isRequired,
  }).isRequired,
};

export default withRouter(NewsletterStandard);
