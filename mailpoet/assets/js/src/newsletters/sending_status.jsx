import React from 'react';
import MailPoet from 'mailpoet';
import PropTypes from 'prop-types';
import classNames from 'classnames';
import { Link } from 'react-router-dom';
import Listing from 'listing/listing.jsx';
import { checkCronStatus, checkMailerStatus } from 'newsletters/listings/utils.jsx';

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
  const [newsletter, setNewsletter] = React.useState({
    id: props.match.params.id,
    subject: '',
    sent: false,
  });

  React.useEffect(() => {
    MailPoet.Ajax.post({
      api_version: window.mailpoet_api_version,
      endpoint: 'newsletters',
      action: 'get',
      data: {
        id: newsletter.id,
      },
    })
      .done((res) => setNewsletter({
        id: newsletter.id,
        subject: res.data.subject,
        sent: res.data.sent_at !== null,
      }))
      .fail((res) => MailPoet.Notice.showApiErrorNotice(res));
  }, [newsletter.id]);

  return (
    <>
      <h1>{MailPoet.I18n.t('sendingStatusTitle')}</h1>
      <StatsLink
        newsletter={newsletter}
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
    onRenderItem={
      (item) => (
        <div>
          { /* eslint-disable-next-line react/jsx-props-no-spreading */ }
          <ListingItem {...item} />
        </div>
      )
    }
    getListingItemKey={(item) => `${item.taskId}-${item.subscriberId}`}
    columns={columns}
    messages={messages}
    auto_refresh
    sort_by="failed"
    sort_order="desc"
    afterGetItems={(state) => {
      checkMailerStatus(state);
      checkCronStatus(state);
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

const StatsLink = ({ newsletter }) => {
  if (!newsletter.id || !newsletter.subject || !newsletter.sent) return null;
  return <p><Link to={`/stats/${newsletter.id}`}>{ newsletter.subject }</Link></p>;
};
StatsLink.propTypes = {
  newsletter: PropTypes.shape({
    id: PropTypes.string,
    subject: PropTypes.string,
    sent: PropTypes.bool,
  }),
};
StatsLink.defaultProps = {
  newsletter: {
    id: null,
    subject: null,
    sent: false,
  },
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
      .fail((res) => MailPoet.Notice.showApiErrorNotice(res));
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
            href="#"
            onClick={(event) => {
              event.preventDefault();
              resend();
            }}
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
        <a
          className="mailpoet-listing-title"
          href={`admin.php?page=mailpoet-subscribers#/edit/${subscriberId}`}
        >
          { email }
        </a>
        <div className="mailpoet-listing-subtitle">
          { `${firstName} ${lastName}` }
        </div>
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
