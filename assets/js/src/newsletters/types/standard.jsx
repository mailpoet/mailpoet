import PropTypes from 'prop-types';
import React from 'react';
import MailPoet from 'mailpoet';
import Breadcrumb from 'newsletters/breadcrumb.jsx';
import { withRouter } from 'react-router-dom';

class NewsletterStandard extends React.Component {
  componentDidMount() {
    // No options for this type, create a newsletter upon mounting
    MailPoet.Ajax.post({
      api_version: window.mailpoet_api_version,
      endpoint: 'newsletters',
      action: 'create',
      data: {
        type: 'standard',
      },
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

  showTemplateSelection = (newsletterId) => {
    this.props.history.push(`/template/${newsletterId}`);
  };

  render() {
    return (
      <div>
        <h1>{MailPoet.I18n.t('regularNewsletterTypeTitle')}</h1>
        <Breadcrumb step="type" />
      </div>
    );
  }
}

NewsletterStandard.propTypes = {
  history: PropTypes.shape({
    push: PropTypes.func.isRequired,
  }).isRequired,
};

export default withRouter(NewsletterStandard);
