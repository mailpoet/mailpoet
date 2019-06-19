import React from 'react';
import MailPoet from 'mailpoet';
import PropTypes from 'prop-types';
import classNames from 'classnames';
import { Link } from 'react-router-dom';
import Listing from 'listing/listing.jsx';
import { CronMixin, MailerMixin } from 'newsletters/listings/mixins.jsx';

const columns = [
  {
    name: 'subscriber_id',
    label: MailPoet.I18n.t('subscriber'),
    sortable: true,
  },
  {
    name: 'status',
    label: MailPoet.I18n.t('sendingStatus'),
  },
  {
    name: 'failureReason',
    label: MailPoet.I18n.t('failureReason'),
  },
];

const messages = {
  onNoItemsFound: () => MailPoet.I18n.t('noSendingTaskFound'),
};

const SendingStatus = (props) => {
  const newsletterId = props.match.params.id;
  const [newsletterSubject, setNewsletterSubject] = React.useState('');

  React.useEffect(() => {
    MailPoet.Ajax.post({
      api_version: window.mailpoet_api_version,
      endpoint: 'newsletters',
      action: 'get',
      data: {
        id: newsletterId,
      },
    })
      .done(res => setNewsletterSubject(res.data.subject))
      .fail(res => MailPoet.Notice.showApiErrorNotice(res));
  }, [newsletterId]);

  return (
    <>
      <h1>{MailPoet.I18n.t('sendingStatusTitle')}</h1>
      <StatsLink
        newsletterId={newsletterId}
        newsletterSubject={newsletterSubject}
      />
      <SendingStatusListing location={props.location} params={props.match.params} />
    </>
  );
};
SendingStatus.propTypes = {
  location: PropTypes.shape({
    pathname: PropTypes.string,
  }).isRequired,
  match: PropTypes.shape({
    params: PropTypes.shape({
      id: PropTypes.string.isRequired,
    }).isRequired,
  }).isRequired,
};

const compareProps = (prev, next) => (
  prev.location.pathname === next.location.pathname
  && prev.params.id === next.params.id
);

const SendingStatusListing = React.memo(({ location, params }) => (
  <Listing
    limit={window.mailpoet_listing_per_page}
    location={location}
    params={params}
    endpoint="sending_task_subscribers"
    base_url="sending-status/:id"
    onRenderItem={item => <div><ListingItem {...item} /></div>}
    getListingItemKey={item => `${item.taskId}-${item.subscriberId}`}
    columns={columns}
    messages={messages}
    auto_refresh
    sort_by="created_at"
    afterGetItems={(state) => {
      MailerMixin.checkMailerStatus(state);
      CronMixin.checkCronStatus(state);
    }}
  />
), compareProps);
SendingStatusListing.propTypes = {
  location: PropTypes.shape({
    pathname: PropTypes.string,
  }).isRequired,
  params: PropTypes.shape({
    id: PropTypes.string.isRequired,
  }).isRequired,
};

const StatsLink = ({ newsletterId, newsletterSubject }) => {
  if (!newsletterId || !newsletterSubject) return null;
  if (window.mailpoet_premium_active) {
    return <p><Link to={`/stats/${newsletterId}`}>{ newsletterSubject }</Link></p>;
  }
  return <p><a href="admin.php?page=mailpoet-premium">{newsletterSubject}</a></p>;
};
StatsLink.propTypes = {
  newsletterId: PropTypes.string,
  newsletterSubject: PropTypes.string,
};
StatsLink.defaultProps = {
  newsletterId: null,
  newsletterSubject: null,
};

const ListingItem = ({
  error, failed, taskId, processed, email, subscriberId, lastName, firstName,
}) => {
  const resend = () => {
    MailPoet.Ajax.post({
      api_version: window.mailpoet_api_version,
      endpoint: 'sending_task_subscribers',
      action: 'resend',
      data: { taskId, subscriberId },
    })
      .done(() => window.mailpoet_listing.forceUpdate())
      .fail(res => MailPoet.Notice.showApiErrorNotice(res));
  };

  const rowClasses = classNames(
    'manage-column',
    'column-primary',
    'has-row-actions'
  );
  let status = MailPoet.I18n.t('unprocessed');
  if (processed === '1') {
    if (failed === '1') {
      status = (
        <span>
          {MailPoet.I18n.t('failed')}
          <br />
          <a
            className="button"
            href="javascript:;"
            onClick={resend}
          >
            {MailPoet.I18n.t('resend')}
          </a>
        </span>
      );
    } else {
      status = MailPoet.I18n.t('sent');
    }
  }
  return (
    <>
      <td data-automation-id={`name_${taskId}_${subscriberId}`} className={rowClasses}>
        <strong>
          <a
            className="row-title"
            href={`admin.php?page=mailpoet-subscribers#/edit/${subscriberId}`}
          >
            { email }
          </a>
        </strong>
        <p style={{ margin: 0 }}>
          { `${firstName} ${lastName}` }
        </p>
      </td>
      <td className="column" data-automation-id={`status_${taskId}_${subscriberId}`} data-colname={MailPoet.I18n.t('sendingStatus')}>
        { status }
      </td>
      <td className="column" data-automation-id={`error_${taskId}_${subscriberId}`} data-colname={MailPoet.I18n.t('failureReason')}>
        { error }
      </td>
    </>
  );
};
ListingItem.propTypes = {
  error: PropTypes.string,
  email: PropTypes.string.isRequired,
  failed: PropTypes.string.isRequired,
  taskId: PropTypes.string.isRequired,
  lastName: PropTypes.string.isRequired,
  firstName: PropTypes.string.isRequired,
  processed: PropTypes.string.isRequired,
  subscriberId: PropTypes.string.isRequired,
};
ListingItem.defaultProps = {
  error: '',
};

export default SendingStatus;
