import Hooks from 'wp-js-hooks';
import MailPoet from 'mailpoet';
import PropTypes from 'prop-types';
import React, {Fragment} from 'react';
import { Link } from 'react-router-dom';

const SendingStatus = props => {
  const newsletterId = props.match.params.id;
  const [error, setError] = React.useState(null);
  const [newsletterSubject, setNewsletterSubject] = React.useState(null);
  React.useEffect(() => {
    MailPoet.Ajax.post({
      api_version: window.mailpoet_api_version,
      endpoint: 'newsletters',
      action: 'get',
      data: {
        id: newsletterId,
      },
    })
    .done(response => setNewsletterSubject(response.data.subject))
    .fail(() => setError('loadingNewsletterError'));
  }, [newsletterId]);

  const title = <h1>{MailPoet.I18n.t('sendingStatusTitle')}</h1>;
  if (error) {
    return (
      <Fragment>
        {title}
        <div className="notice notice-error">
          <p>{ MailPoet.I18n.t(error) }</p>
        </div>
      </Fragment>
    );
  }
  return (
    <Fragment>
      {title}
      <StatsLink id={newsletterId} subject={newsletterSubject} />
      <SubscribersListing id={newsletterId} />
    </Fragment>
  );
};

SendingStatus.propTypes = {
  match: PropTypes.shape({
    params: PropTypes.shape({
      id: PropTypes.string,
    }).isRequired,
  }).isRequired,
};

const StatsLink = ({id, subject}) => {
  if (window.mailpoet_premium_active) {
    return <p><Link to={`/stats/${id}`}>{subject}</Link></p>;
  }
  return <p><a href="admin.php?page=mailpoet-premium">{subject}</a></p>;
};

StatsLink.propTypes = {
  id: PropTypes.string.isRequired,
  subject: PropTypes.string.isRequired,
};

const SubscribersListing = () => null

export default SendingStatus