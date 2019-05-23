import Hooks from 'wp-js-hooks';
import MailPoet from 'mailpoet';
import PropTypes from 'prop-types';
import classNames from 'classnames';
import React, {Fragment} from 'react';
import { Link } from 'react-router-dom';
import Listing from 'listing/listing.jsx';
import { CronMixin, MailerMixin } from 'newsletters/listings/mixins.jsx';

const SendingStatus = props => {
  const newsletterId = props.match.params.id;
  const [state, setState] = React.useState({
    error: null,
    newsletterSubject: null,
  });

  React.useEffect(() => {
    MailPoet.Ajax.post({
      api_version: window.mailpoet_api_version,
      endpoint: 'newsletters',
      action: 'get',
      data: {
        id: newsletterId,
      },
    })
    .done(res => setState({
      error: null,
      newsletterSubject: res.data.subject,
    }))
    .fail(() => setState({
      newsletterSubject: null,
      error: 'loadingNewsletterError',
    }));
  }, [newsletterId]);

  const {error, newsletterSubject} = state;
  return (
    <Fragment>
      <h1>{MailPoet.I18n.t('sendingStatusTitle')}</h1>
      <LoadingError error={error} />
      <StatsLink
        newsletterId={newsletterId}
        newsletterSubject={newsletterSubject}
      />
      {newsletterSubject && <Listing
        limit={window.mailpoet_listing_per_page}
        location={props.location}
        params={props.match.params}
        endpoint="sending_task_subscribers"
        base_url="sending-status/:id"
        onRenderItem={item => <div><ListingItem {...item} /></div>}
        getListingItemKey={item => item.taskId + '-' + item.subscriberId}
        columns={columns}
        messages={messages}
        auto_refresh
        sort_by="created_at"
        afterGetItems={(state) => {
          MailerMixin.checkMailerStatus(state);
          CronMixin.checkCronStatus(state);
        }}
      /> }
    </Fragment>
  );
};
SendingStatus.propTypes = {
  location: PropTypes.object.isRequired, // eslint-disable-line react/forbid-prop-types
  match: PropTypes.shape({
    params: PropTypes.shape({
      id: PropTypes.string.isRequired,
    }).isRequired,
  }).isRequired,
};

const LoadingError = ({error}) => {
  if (!error) return null;
  return (
    <div className="notice notice-error">
      <p>{ MailPoet.I18n.t(error) }</p>
    </div>
  );
}
LoadingError.propTypes = {
  error: PropTypes.string,
};

const StatsLink = ({newsletterId, newsletterSubject}) => {
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

const ListingItem = ({error, failed, taskId, processed, email, subscriberId, lastName, firstName}) => {
  const rowClasses = classNames(
    'manage-column',
    'column-primary',
    'has-row-actions'
  );
  let status = MailPoet.I18n.t('unprocessed');
  if (processed === '1') {
    if (failed === '1') {
      status = MailPoet.I18n.t('failed');
    } else {
      status = MailPoet.I18n.t('sent');
    }
  }
  return (
    <Fragment>
      <td className={rowClasses}>
        <strong>
          <a
            className="row-title"
            href={`admin.php?page=mailpoet-subscribers#/edit/1`}
          >
            { email }
          </a>
        </strong>
        <p style={{ margin: 0 }}>
          { firstName + ' ' + lastName }
        </p>
      </td>
      <td className="column" data-colname={MailPoet.I18n.t('sendingStatus')}>
        { status }
      </td>
      <td className="column" data-colname={MailPoet.I18n.t('failureReason')}>
        { error }
      </td>
    </Fragment>
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
  onNoItemsFound: () => MailPoet.I18n.t('noSendingTaskFound')
};

export default SendingStatus;
