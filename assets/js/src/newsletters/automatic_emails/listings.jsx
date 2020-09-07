import React from 'react';
import Listing from 'listing/listing.jsx';
import { checkMailerStatus, addStatsCTAAction } from 'newsletters/listings/utils.jsx';
import Statistics from 'newsletters/listings/statistics.jsx';
import NewsletterTypes from 'newsletters/types.jsx';
import classNames from 'classnames';
import MailPoet from 'mailpoet';
import _ from 'underscore';
import Hooks from 'wp-js-hooks';
import PropTypes from 'prop-types';
import { Link, withRouter } from 'react-router-dom';

const mailpoetTrackingEnabled = (!!(window.mailpoet_tracking_enabled));
const automaticEmails = window.mailpoet_woocommerce_automatic_emails || {};

const messages = {
  onTrash: (response) => {
    const count = Number(response.meta.count);
    let message = null;

    if (count === 1) {
      message = (
        MailPoet.I18n.t('oneNewsletterTrashed')
      );
    } else {
      message = (
        MailPoet.I18n.t('multipleNewslettersTrashed')
      ).replace('%$1d', count.toLocaleString());
    }
    MailPoet.Notice.success(message);
  },
  onDelete: (response) => {
    const count = Number(response.meta.count);
    let message = null;

    if (count === 1) {
      message = (
        MailPoet.I18n.t('oneNewsletterDeleted')
      );
    } else {
      message = (
        MailPoet.I18n.t('multipleNewslettersDeleted')
      ).replace('%$1d', count.toLocaleString());
    }
    MailPoet.Notice.success(message);
  },
  onRestore: (response) => {
    const count = Number(response.meta.count);
    let message = null;

    if (count === 1) {
      message = (
        MailPoet.I18n.t('oneNewsletterRestored')
      );
    } else {
      message = (
        MailPoet.I18n.t('multipleNewslettersRestored')
      ).replace('%$1d', count.toLocaleString());
    }
    MailPoet.Notice.success(message);
  },
};

const columns = [
  {
    name: 'subject',
    label: MailPoet.I18n.t('subject'),
    sortable: true,
  },
  {
    name: 'status',
    label: MailPoet.I18n.t('status'),
    width: 145,
  },
  {
    name: 'settings',
    label: MailPoet.I18n.t('settings'),
  },
  {
    name: 'statistics',
    label: MailPoet.I18n.t('statistics'),
    display: mailpoetTrackingEnabled,
  },
  {
    name: 'updated_at',
    label: MailPoet.I18n.t('lastModifiedOn'),
    sortable: true,
  },
];

const bulkActions = [
  {
    name: 'trash',
    label: MailPoet.I18n.t('moveToTrash'),
    onSuccess: messages.onTrash,
  },
];

const newsletterActions = [
  {
    name: 'view',
    link: function link(newsletter) {
      return (
        <a href={newsletter.preview_url} target="_blank" rel="noopener noreferrer">
          {MailPoet.I18n.t('preview')}
        </a>
      );
    },
  },
  {
    name: 'edit',
    link: function link(newsletter) {
      return (
        <a href={`?page=mailpoet-newsletter-editor&id=${newsletter.id}`}>
          {MailPoet.I18n.t('edit')}
        </a>
      );
    },
  },
  {
    name: 'duplicate',
    label: MailPoet.I18n.t('duplicate'),
    onClick: (newsletter, refresh) => MailPoet.Ajax.post({
      api_version: window.mailpoet_api_version,
      endpoint: 'newsletters',
      action: 'duplicate',
      data: {
        id: newsletter.id,
      },
    }).done((response) => {
      MailPoet.Notice.success((MailPoet.I18n.t('newsletterDuplicated')).replace('%$1s', response.data.subject));
      refresh();
    }).fail((response) => {
      if (response.errors.length > 0) {
        MailPoet.Notice.error(
          response.errors.map((error) => error.message),
          { scroll: true }
        );
      }
    }),
  },
  {
    name: 'trash',
  },
];

Hooks.addFilter('mailpoet_newsletters_listings_automatic_email_actions', 'mailpoet', addStatsCTAAction);

class Listings extends React.Component {
  constructor(props) {
    super(props);
    this.state = {
      eventCounts: {},
      newslettersCount: undefined,
    };
    this.afterGetItems = this.afterGetItems.bind(this);
  }

  updateStatus = (e) => {
    // make the event persist so that we can still override the selected value
    // in the ajax callback
    e.persist();

    MailPoet.Ajax.post({
      api_version: window.mailpoet_api_version,
      endpoint: 'newsletters',
      action: 'setStatus',
      data: {
        id: Number(e.target.getAttribute('data-id')),
        status: e.target.value,
      },
    }).done((response) => {
      if (response.data.status === 'active') {
        MailPoet.Notice.success(MailPoet.I18n.t('automaticEmailActivated'));
      }
      // force refresh of listing so that groups are updated
      this.forceUpdate();
    }).fail((response) => {
      MailPoet.Notice.showApiErrorNotice(response);

      // reset value to actual newsletter's status
      e.target.value = response.status;
    });
  };

  renderStatus = (newsletter) => {
    const totalSent = (parseInt(newsletter.total_sent, 10))
      ? MailPoet.I18n.t('sentToXCustomers')
        .replace('%$1d', newsletter.total_sent.toLocaleString())
      : MailPoet.I18n.t('notSentYet');

    return (
      <div>
        <p>
          <select
            data-id={newsletter.id}
            defaultValue={newsletter.status}
            onChange={this.updateStatus}
          >
            <option value="active">{ MailPoet.I18n.t('active') }</option>
            <option value="draft">{ MailPoet.I18n.t('inactive') }</option>
          </select>
        </p>
        <p><Link to={`/sending-status/${newsletter.id}`}>{ totalSent }</Link></p>
      </div>
    );
  };

  renderSettings = (newsletter) => {
    const event = automaticEmails[newsletter.options.group].events[newsletter.options.event];
    let meta;
    try {
      meta = JSON.parse(newsletter.options.meta || null);
    } catch (e) {
      meta = newsletter.options.meta || null;
    }
    const metaOptionValues = (meta && meta.option) ? _.pluck(meta.option, 'name') : [];

    if (meta && _.isEmpty(metaOptionValues)) {
      return (
        <span className="mailpoet_error">
          { MailPoet.I18n.t('automaticEmailEventOptionsNotConfigured') }
        </span>
      );
    }

    // set sending event
    let displayText;
    if (metaOptionValues.length > 1 && 'listingScheduleDisplayTextPlural' in event) {
      displayText = event.listingScheduleDisplayTextPlural.replace('%s', metaOptionValues.join(', '));
    } else {
      displayText = event.listingScheduleDisplayText.replace('%s', metaOptionValues.join(', '));
    }

    // set sending delay
    let sendingDelay;
    if (displayText && newsletter.options.afterTimeType !== 'immediate') {
      switch (newsletter.options.afterTimeType) {
        case 'minutes':
          sendingDelay = MailPoet.I18n.t('sendingDelayMinutes').replace('%$1d', newsletter.options.afterTimeNumber);
          break;

        case 'hours':
          sendingDelay = MailPoet.I18n.t('sendingDelayHours').replace('%$1d', newsletter.options.afterTimeNumber);
          break;

        case 'days':
          sendingDelay = MailPoet.I18n.t('sendingDelayDays').replace('%$1d', newsletter.options.afterTimeNumber);
          break;

        case 'weeks':
          sendingDelay = MailPoet.I18n.t('sendingDelayWeeks').replace('%$1d', newsletter.options.afterTimeNumber);
          break;

        default:
          sendingDelay = MailPoet.I18n.t('sendingDelayInvalid');
          break;
      }
    }

    return (
      <span>
        { displayText }
        { sendingDelay && (
          <>
            <br />
            { sendingDelay }
          </>
        ) }
      </span>
    );
  };

  renderItem = (newsletter, actions) => {
    const rowClasses = classNames(
      'manage-column',
      'column-primary',
      'has-row-actions'
    );

    return (
      <div>
        <td className={rowClasses}>
          <strong>
            <a
              className="row-title"
              href={`?page=mailpoet-newsletter-editor&id=${newsletter.id}`}
            >
              { newsletter.subject }
            </a>
          </strong>
          { actions }
        </td>
        <td className="column" data-colname={MailPoet.I18n.t('status')}>
          { this.renderStatus(newsletter) }
        </td>
        <td className="column" data-colname={MailPoet.I18n.t('settings')}>
          { this.renderSettings(newsletter) }
        </td>
        { (mailpoetTrackingEnabled === true) ? (
          <td className="column" data-colname={MailPoet.I18n.t('statistics')}>
            <Statistics
              newsletter={newsletter}
              isSent={newsletter.total_sent > 0 && !!newsletter.statistics}
            />
          </td>
        ) : null }
        <td className="column-date" data-colname={MailPoet.I18n.t('lastModifiedOn')}>
          <abbr>{ MailPoet.Date.format(newsletter.updated_at) }</abbr>
        </td>
      </div>
    );
  };

  afterGetItems(state) {
    checkMailerStatus(state);
    this.countEmailTypes(state);
  }

  countEmailTypes(state) {
    const initialCounts = {
      woocommerce_product_purchased: 0,
      woocommerce_product_purchased_in_category: 0,
      woocommerce_first_purchase: 0,
    };
    if (Array.isArray(state.items)) {
      const counts = state.items
        .map((item) => item.options.event)
        .reduce((acc, event) => {
          if (acc[event]) {
            acc[event] += 1;
          } else {
            acc[event] = 1;
          }
          return acc;
        }, initialCounts);
      this.setState({ eventCounts: counts });
    }
  }

  renderWarning() {
    const { eventCounts } = this.state;
    const counts = eventCounts.woocommerce_product_purchased
      + eventCounts.woocommerce_product_purchased_in_category
      + eventCounts.woocommerce_first_purchase;

    if (!counts) return null;
    if (window.mailpoet_woocommerce_optin_on_checkout === '1') return null;

    return (
      <div className="mailpoet_base_notice mailpoet_error_notice">
        <p>{MailPoet.I18n.t('wooCommerceEmailsWarning')}</p>
        <p>
          <a href="?page=mailpoet-settings#woocommerce">{MailPoet.I18n.t('wooCommerceEmailsWarningLink')}</a>
        </p>
      </div>
    );
  }

  render() {
    const { match, location } = this.props;

    return (
      <>
        {this.renderWarning()}

        {this.state.newslettersCount === 0 && (
          <NewsletterTypes
            filter={(type) => type.slug === 'woocommerce'}
          />
        )}
        {this.state.newslettersCount !== 0 && (
          <Listing
            limit={window.mailpoet_listing_per_page}
            location={location}
            params={match.params}
            endpoint="newsletters"
            type="automatic"
            base_url="woocommerce"
            onRenderItem={this.renderItem}
            columns={columns}
            bulk_actions={bulkActions}
            item_actions={newsletterActions}
            messages={messages}
            auto_refresh
            sort_by="updated_at"
            sort_order="desc"
            afterGetItems={(state) => {
              if (!state.loading) {
                const total = state.groups.reduce((count, group) => (count + group.count), 0);
                this.setState({ newslettersCount: total });
              }
              this.afterGetItems(state);
            }}
          />
        )}
      </>
    );
  }
}

Listings.propTypes = {
  match: PropTypes.shape({
    params: PropTypes.shape({
      tab: PropTypes.string,
    }).isRequired,
  }).isRequired,
  location: PropTypes.object.isRequired, // eslint-disable-line react/forbid-prop-types
};

export default withRouter(Listings);
