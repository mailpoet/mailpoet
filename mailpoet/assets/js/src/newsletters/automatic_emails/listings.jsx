import _ from 'underscore';
import classnames from 'classnames';
import { Component } from 'react';
import ReactStringReplace from 'react-string-replace';
import PropTypes from 'prop-types';
import { Link, withRouter } from 'react-router-dom';

import {
  addStatsCTAAction,
  checkMailerStatus,
  confirmEdit,
} from 'newsletters/listings/utils.jsx';
import { Listing } from 'listing/listing.jsx';
import { MailPoet } from 'mailpoet';
import { NewsletterTypes } from 'newsletters/types';
import { ScheduledIcon, StringTags, Toggle, withBoundary } from 'common';
import { Statistics } from 'newsletters/listings/statistics.jsx';

const mailpoetTrackingEnabled = MailPoet.trackingConfig.emailTrackingEnabled;
const automaticEmails = window.mailpoet_woocommerce_automatic_emails || {};

const messages = {
  onTrash: (response) => {
    const count = Number(response.meta.count);
    let message = null;

    if (count === 1) {
      message = MailPoet.I18n.t('oneNewsletterTrashed');
    } else {
      message = MailPoet.I18n.t('multipleNewslettersTrashed').replace(
        '%1$d',
        count.toLocaleString(),
      );
    }
    MailPoet.Notice.success(message);
  },
  onDelete: (response) => {
    const count = Number(response.meta.count);
    let message = null;

    if (count === 1) {
      message = MailPoet.I18n.t('oneNewsletterDeleted');
    } else {
      message = MailPoet.I18n.t('multipleNewslettersDeleted').replace(
        '%1$d',
        count.toLocaleString(),
      );
    }
    MailPoet.Notice.success(message);
  },
  onRestore: (response) => {
    const count = Number(response.meta.count);
    let message = null;

    if (count === 1) {
      message = MailPoet.I18n.t('oneNewsletterRestored');
    } else {
      message = MailPoet.I18n.t('multipleNewslettersRestored').replace(
        '%1$d',
        count.toLocaleString(),
      );
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
    name: 'settings',
    label: MailPoet.I18n.t('settings'),
  },
  {
    name: 'statistics',
    label: MailPoet.I18n.t('statistics'),
    display: mailpoetTrackingEnabled,
  },
  {
    name: 'status',
    label: MailPoet.I18n.t('status'),
    width: 145,
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

let newsletterActions = [
  {
    name: 'view',
    link: function link(newsletter) {
      return (
        <a
          href={newsletter.preview_url}
          target="_blank"
          rel="noopener noreferrer"
        >
          {MailPoet.I18n.t('preview')}
        </a>
      );
    },
  },
  {
    name: 'edit',
    className: 'mailpoet-hide-on-mobile',
    label: MailPoet.I18n.t('edit'),
    onClick: confirmEdit,
  },
  {
    name: 'duplicate',
    className: 'mailpoet-hide-on-mobile',
    label: MailPoet.I18n.t('duplicate'),
    onClick: (newsletter, refresh) =>
      MailPoet.Ajax.post({
        api_version: window.mailpoet_api_version,
        endpoint: 'newsletters',
        action: 'duplicate',
        data: {
          id: newsletter.id,
        },
      })
        .done((response) => {
          MailPoet.Notice.success(
            MailPoet.I18n.t('newsletterDuplicated').replace(
              '%1$s',
              response.data.subject,
            ),
          );
          refresh();
        })
        .fail((response) => {
          if (response.errors.length > 0) {
            MailPoet.Notice.error(
              response.errors.map((error) => error.message),
              { scroll: true },
            );
          }
        }),
  },
  {
    name: 'trash',
    className: 'mailpoet-hide-on-mobile',
  },
];

newsletterActions = addStatsCTAAction(newsletterActions);

class ListingsComponent extends Component {
  constructor(props) {
    super(props);
    this.state = {
      eventCounts: {},
      newslettersCount: undefined,
    };
    this.afterGetItems = this.afterGetItems.bind(this);
  }

  updateStatus = (checked, e) => {
    // make the event persist so that we can still override the selected value
    // in the ajax callback
    e.persist();

    MailPoet.Ajax.post({
      api_version: window.mailpoet_api_version,
      endpoint: 'newsletters',
      action: 'setStatus',
      data: {
        id: Number(e.target.getAttribute('data-id')),
        status: checked ? 'active' : 'draft',
      },
    })
      .done((response) => {
        if (response.data.status === 'active') {
          const newsletterGroup = e.target.getAttribute('data-group');
          const email = automaticEmails[newsletterGroup];
          MailPoet.Notice.success(
            MailPoet.I18n.t('automaticEmailActivated').replace(
              '%1s',
              email.title,
            ),
          );
        }
        // force refresh of listing so that groups are updated
        this.forceUpdate();
      })
      .fail((response) => {
        MailPoet.Notice.showApiErrorNotice(response);

        // reset value to previous newsletter's status
        e.target.checked = !checked;
      });
  };

  renderStatus = (newsletter) => {
    const totalSent =
      parseInt(newsletter.total_sent, 10) > -1
        ? MailPoet.I18n.t('sentToXCustomers').replace(
            '%1$d',
            newsletter.total_sent.toLocaleString(),
          )
        : null;

    return (
      <div>
        <Toggle
          className="mailpoet-listing-status-toggle"
          onCheck={this.updateStatus}
          data-id={newsletter.id}
          data-group={newsletter.options.group}
          dimension="small"
          defaultChecked={newsletter.status === 'active'}
        />
        <p className="mailpoet-listing-notification-status">
          {totalSent && (
            <Link to={`/sending-status/${newsletter.id}`}>{totalSent}</Link>
          )}
          {!totalSent && (
            <span className="mailpoet-listing-status-unknown mailpoet-font-extra-small">
              {MailPoet.I18n.t('notSentYet')}
            </span>
          )}
        </p>
      </div>
    );
  };

  renderSettings = (newsletter) => {
    const event =
      automaticEmails[newsletter.options.group].events[
        newsletter.options.event
      ];
    let meta;
    try {
      meta = JSON.parse(newsletter.options.meta || null);
    } catch (e) {
      meta = newsletter.options.meta || null;
    }
    const metaOptionValues =
      meta && meta.option ? _.pluck(meta.option, 'name') : [];

    if (meta && _.isEmpty(metaOptionValues)) {
      return (
        <span className="mailpoet-listing-error">
          {MailPoet.I18n.t('automaticEmailEventOptionsNotConfigured')}
        </span>
      );
    }

    // set sending event
    let displayText;
    if (
      metaOptionValues.length > 1 &&
      'listingScheduleDisplayTextPlural' in event
    ) {
      displayText = ReactStringReplace(
        event.listingScheduleDisplayTextPlural,
        '%s',
        (match, i) => <StringTags strings={metaOptionValues} key={i} />,
      );
    } else {
      displayText = ReactStringReplace(
        event.listingScheduleDisplayText,
        '%s',
        (match, i) => <StringTags strings={metaOptionValues} key={i} />,
      );
    }

    // set sending delay
    let sendingDelay;
    if (displayText && newsletter.options.afterTimeType !== 'immediate') {
      switch (newsletter.options.afterTimeType) {
        case 'minutes':
          sendingDelay = MailPoet.I18n.t('sendingDelayMinutes').replace(
            '%1$d',
            newsletter.options.afterTimeNumber,
          );
          break;

        case 'hours':
          sendingDelay = MailPoet.I18n.t('sendingDelayHours').replace(
            '%1$d',
            newsletter.options.afterTimeNumber,
          );
          break;

        case 'days':
          sendingDelay = MailPoet.I18n.t('sendingDelayDays').replace(
            '%1$d',
            newsletter.options.afterTimeNumber,
          );
          break;

        case 'weeks':
          sendingDelay = MailPoet.I18n.t('sendingDelayWeeks').replace(
            '%1$d',
            newsletter.options.afterTimeNumber,
          );
          break;

        default:
          sendingDelay = MailPoet.I18n.t('sendingDelayInvalid');
          break;
      }
    }

    return (
      <span>
        {displayText}
        {sendingDelay && (
          <div className="mailpoet-listing-schedule">
            <div className="mailpoet-listing-schedule-icon">
              <ScheduledIcon />
            </div>
            {sendingDelay}
          </div>
        )}
      </span>
    );
  };

  renderItem = (newsletter, actions) => {
    const rowClasses = classnames(
      'manage-column',
      'column-primary',
      'has-row-actions',
    );

    return (
      <div>
        <td className={rowClasses}>
          <a
            className="mailpoet-listing-title"
            href={`?page=mailpoet-newsletter-editor&id=${newsletter.id}`}
          >
            {newsletter.subject}
          </a>
          {actions}
        </td>
        <td
          className="column mailpoet-hide-on-mobile"
          data-colname={MailPoet.I18n.t('settings')}
        >
          {this.renderSettings(newsletter)}
        </td>
        {mailpoetTrackingEnabled === true ? (
          <td
            className="column mailpoet-listing-stats-column"
            data-colname={MailPoet.I18n.t('statistics')}
          >
            <Statistics
              newsletter={newsletter}
              isSent={newsletter.total_sent > 0 && !!newsletter.statistics}
            />
          </td>
        ) : null}
        <td className="column" data-colname={MailPoet.I18n.t('status')}>
          {this.renderStatus(newsletter)}
        </td>
        <td
          className="column-date mailpoet-hide-on-mobile"
          data-colname={MailPoet.I18n.t('lastModifiedOn')}
        >
          {MailPoet.Date.short(newsletter.updated_at)}
          <br />
          {MailPoet.Date.time(newsletter.updated_at)}
        </td>
      </div>
    );
  };

  isItemInactive = (newsletter) => newsletter.status === 'draft';

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
    const counts =
      eventCounts.woocommerce_product_purchased +
      eventCounts.woocommerce_product_purchased_in_category +
      eventCounts.woocommerce_first_purchase;

    if (!counts) return null;
    if (window.mailpoet_woocommerce_optin_on_checkout === '1') return null;

    return (
      <div className="notice error">
        <p>{MailPoet.I18n.t('wooCommerceEmailsWarning')}</p>
        <p>
          <a href="?page=mailpoet-settings#woocommerce">
            {MailPoet.I18n.t('wooCommerceEmailsWarningLink')}
          </a>
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
            hideScreenOptions={false}
            hideClosingButton
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
            isItemInactive={this.isItemInactive}
            columns={columns}
            bulk_actions={bulkActions}
            item_actions={newsletterActions}
            messages={messages}
            auto_refresh
            sort_by="updated_at"
            sort_order="desc"
            afterGetItems={(state) => {
              if (!state.loading) {
                const total = state.groups.reduce(
                  (count, group) => count + group.count,
                  0,
                );
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

ListingsComponent.propTypes = {
  match: PropTypes.shape({
    params: PropTypes.shape({
      tab: PropTypes.string,
    }).isRequired,
  }).isRequired,
  location: PropTypes.object.isRequired, // eslint-disable-line react/forbid-prop-types
};
ListingsComponent.displayName = 'ListingsComponent';
export const Listings = withRouter(withBoundary(ListingsComponent));
