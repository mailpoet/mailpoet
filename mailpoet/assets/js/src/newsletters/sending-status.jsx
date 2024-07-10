import classnames from 'classnames';
import { Link, useLocation, useParams } from 'react-router-dom';
import { memo, useEffect, useState } from 'react';
import PropTypes from 'prop-types';
import { __, _x } from '@wordpress/i18n';

import { Listing } from 'listing/listing.jsx';
import {
  checkCronStatus,
  checkMailerStatus,
} from 'newsletters/listings/utils.jsx';
import { MailPoet } from 'mailpoet';

const columns = [
  {
    name: 'subscriber_id',
    label: __('Subscriber', 'mailpoet'),
    sortable: true,
  },
  {
    name: 'status',
    label: _x(
      'Sending status',
      'an email sending status: unprocessed, sent or failed.',
      'mailpoet',
    ),
  },
  {
    name: 'failureReason',
    label: __('Failure reason (if applicable)', 'mailpoet'),
  },
];

const messages = {
  onNoItemsFound: () => __('No sending task found.', 'mailpoet'),
};

function SendingStatus() {
  const params = useParams();
  const location = useLocation();

  const [newsletter, setNewsletter] = useState({
    id: params.id,
    subject: '',
    sent: false,
  });

  useEffect(() => {
    MailPoet.Ajax.post({
      api_version: window.mailpoet_api_version,
      endpoint: 'newsletters',
      action: 'get',
      data: {
        id: newsletter.id,
      },
    })
      .done((res) =>
        setNewsletter({
          id: newsletter.id,
          subject: res.data.subject,
          sent: res.data.sent_at !== null,
        }),
      )
      .fail((res) => MailPoet.Notice.showApiErrorNotice(res));
  }, [newsletter.id]);

  return (
    <>
      <h1>
        {_x(
          'Sending status',
          'Page title. This page displays a list of emails along with their sending status: unprocessed, sent or failed.',
          'mailpoet',
        )}
      </h1>
      <StatsLink newsletter={newsletter} />
      <SendingStatusListing location={location} params={params} />
    </>
  );
}

SendingStatus.displayName = 'SendingStatus';

const compareProps = (prev, next) =>
  prev.location.pathname === next.location.pathname &&
  prev.params.id === next.params.id;

const onRenderItem = (item) => (
  <div>
    <ListingItem {...item} />
  </div>
);

const SendingStatusListing = memo(
  ({ location, params }) => (
    <Listing
      limit={window.mailpoet_listing_per_page}
      location={location}
      params={params}
      endpoint="sending_task_subscribers"
      base_url="sending-status/:id"
      onRenderItem={onRenderItem}
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
  ),
  compareProps,
);
SendingStatusListing.propTypes = {
  location: PropTypes.shape({
    pathname: PropTypes.string,
  }).isRequired,
  params: PropTypes.shape({
    id: PropTypes.string.isRequired,
  }).isRequired,
};

function StatsLink({
  newsletter = {
    id: null,
    subject: null,
    sent: false,
  },
}) {
  if (!newsletter.id || !newsletter.subject || !newsletter.sent) return null;
  return (
    <p>
      <Link to={`/stats/${newsletter.id}`}>{newsletter.subject}</Link>
    </p>
  );
}

StatsLink.propTypes = {
  newsletter: PropTypes.shape({
    id: PropTypes.string,
    subject: PropTypes.string,
    sent: PropTypes.bool,
  }),
};

function ListingItem({
  failed,
  taskId,
  processed,
  email,
  subscriberId,
  lastName,
  firstName,
  error = '',
}) {
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

  const rowClasses = classnames(
    'manage-column',
    'column-primary',
    'has-row-actions',
  );
  let status = _x(
    'Unprocessed',
    'status when the sending of a newsletter has not been processed',
    'mailpoet',
  );
  if (processed) {
    if (failed) {
      status = (
        <span>
          {_x(
            'Failed',
            'status when the sending of a newsletter has failed',
            'mailpoet',
          )}
          <br />
          <a
            className="button"
            href="#"
            onClick={(event) => {
              event.preventDefault();
              resend();
            }}
          >
            {__('Resend', 'mailpoet')}
          </a>
        </span>
      );
    } else {
      status = _x('Sent', 'status when a newsletter has been sent', 'mailpoet');
    }
  }
  return (
    <>
      <td
        data-automation-id={`name_${taskId}_${subscriberId}`}
        className={rowClasses}
      >
        <a
          className="mailpoet-listing-title"
          href={`admin.php?page=mailpoet-subscribers#/edit/${subscriberId}`}
        >
          {email}
        </a>
        <div className="mailpoet-listing-subtitle">
          {`${firstName} ${lastName}`}
        </div>
      </td>
      <td
        className="column"
        data-automation-id={`status_${taskId}_${subscriberId}`}
        data-colname={_x(
          'Sending status',
          'an email sending status: unprocessed, sent or failed.',
          'mailpoet',
        )}
      >
        {status}
      </td>
      <td
        className="column"
        data-automation-id={`error_${taskId}_${subscriberId}`}
        data-colname={__('Failure reason (if applicable)', 'mailpoet')}
      >
        {error}
      </td>
    </>
  );
}

ListingItem.propTypes = {
  error: PropTypes.string,
  email: PropTypes.string.isRequired,
  failed: PropTypes.number.isRequired,
  taskId: PropTypes.number.isRequired,
  lastName: PropTypes.string.isRequired,
  firstName: PropTypes.string.isRequired,
  processed: PropTypes.number.isRequired,
  subscriberId: PropTypes.number.isRequired,
};
ListingItem.displayName = 'ListingItem';
SendingStatus.displayName = 'SendingStatus';
export { SendingStatus };
