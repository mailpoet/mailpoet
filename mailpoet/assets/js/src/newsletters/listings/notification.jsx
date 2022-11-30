import classnames from 'classnames';
import { Component } from 'react';
import { Link, withRouter } from 'react-router-dom';
import PropTypes from 'prop-types';
import ReactStringReplace from 'react-string-replace';

import { Button } from 'common/button/button';
import { ScheduledIcon } from 'common/listings/newsletter_status';
import { Listing } from 'listing/listing.jsx';
import { MailPoet } from 'mailpoet';
import { NewsletterTypes } from 'newsletters/types';
import {
  timeOfDayValues,
  weekDayValues,
  monthDayValues,
  nthWeekDayValues,
} from 'newsletters/scheduling/common.jsx';
import { SegmentTags } from 'common/tag/tags';
import { Toggle } from 'common/form/toggle/toggle';

import {
  checkCronStatus,
  checkMailerStatus,
  confirmEdit,
} from 'newsletters/listings/utils.jsx';

const messages = {
  onNoItemsFound: (group, search) =>
    MailPoet.I18n.t(search ? 'noItemsFound' : 'emptyListing'),
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
    name: 'history',
    label: MailPoet.I18n.t('history'),
    width: 100,
  },
  {
    name: 'status',
    label: MailPoet.I18n.t('status'),
    width: 100,
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
    onClick: function onClick(newsletter, refresh) {
      return MailPoet.Ajax.post({
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
        });
    },
  },
  {
    name: 'trash',
    className: 'mailpoet-hide-on-mobile',
  },
];

class NewsletterListNotificationComponent extends Component {
  constructor(props) {
    super(props);
    this.state = {
      newslettersCount: undefined,
    };
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
          MailPoet.Notice.success(MailPoet.I18n.t('postNotificationActivated'));
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

  renderStatus = (newsletter) => (
    <Toggle
      className="mailpoet-listing-status-toggle"
      onCheck={this.updateStatus}
      data-id={newsletter.id}
      dimension="small"
      defaultChecked={newsletter.status === 'active'}
    />
  );

  renderSettings = (newsletter) => {
    let sendingFrequency;

    // check if the user has specified segments to send to
    if (newsletter.segments.length === 0) {
      return (
        <Link className="mailpoet-listing-error" to={`/send/${newsletter.id}`}>
          {MailPoet.I18n.t('sendingToSegmentsNotSpecified')}
        </Link>
      );
    }

    const sendingToSegments = ReactStringReplace(
      MailPoet.I18n.t('sendTo'),
      '%1$s',
      (match, i) => <SegmentTags segments={newsletter.segments} key={i} />,
    );

    // set sending frequency
    switch (newsletter.options.intervalType) {
      case 'daily':
        sendingFrequency = MailPoet.I18n.t('sendDaily').replace(
          '%1$s',
          timeOfDayValues[newsletter.options.timeOfDay],
        );
        break;

      case 'weekly':
        sendingFrequency = MailPoet.I18n.t('sendWeekly')
          .replace('%1$s', weekDayValues[newsletter.options.weekDay])
          .replace('%2$s', timeOfDayValues[newsletter.options.timeOfDay]);
        break;

      case 'monthly':
        sendingFrequency = MailPoet.I18n.t('sendMonthly')
          .replace('%1$s', monthDayValues[newsletter.options.monthDay])
          .replace('%2$s', timeOfDayValues[newsletter.options.timeOfDay]);
        break;

      case 'nthWeekDay':
        sendingFrequency = MailPoet.I18n.t('sendNthWeekDay')
          .replace('%1$s', nthWeekDayValues[newsletter.options.nthWeekDay])
          .replace('%2$s', weekDayValues[newsletter.options.weekDay])
          .replace('%3$s', timeOfDayValues[newsletter.options.timeOfDay]);
        break;

      case 'immediately':
        sendingFrequency = MailPoet.I18n.t('sendImmediately');
        break;

      default:
        sendingFrequency = 'Invalid sending frequency';
        break;
    }

    return (
      <span>
        {sendingToSegments}
        <div className="mailpoet-listing-schedule">
          <div className="mailpoet-listing-schedule-icon">
            <ScheduledIcon />
          </div>
          {sendingFrequency}
        </div>
      </span>
    );
  };

  renderHistoryLink = (newsletter) => {
    const childrenCount = Number(newsletter.children_count);
    if (childrenCount === 0) {
      return (
        <span className="mailpoet-listing-status-unknown mailpoet-font-extra-small mailpoet-listing-notification-status">
          {MailPoet.I18n.t('notSentYet')}
        </span>
      );
    }
    return (
      <Link
        className="mailpoet-nowrap"
        data-automation-id={`history-${newsletter.id}`}
        to={`/notification/history/${newsletter.id}`}
      >
        <Button className="mailpoet-hide-on-mobile" dimension="small">
          {MailPoet.I18n.t('viewHistory')}
        </Button>
        <Button
          className="mailpoet-show-on-mobile mailpoet-listing-notification-status"
          dimension="small"
          variant="secondary"
        >
          {MailPoet.I18n.t('viewHistory')}
        </Button>
      </Link>
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
        <td className="column" data-colname={MailPoet.I18n.t('history')}>
          {this.renderHistoryLink(newsletter)}
        </td>
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

  render() {
    return (
      <>
        {this.state.newslettersCount === 0 && (
          <NewsletterTypes
            filter={(type) => type.slug === 'notification'}
            hideScreenOptions={false}
            hideClosingButton
          />
        )}
        {this.state.newslettersCount !== 0 && (
          <Listing
            limit={window.mailpoet_listing_per_page}
            location={this.props.location}
            params={this.props.match.params}
            endpoint="newsletters"
            type="notification"
            base_url="notification"
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
              checkMailerStatus(state);
              checkCronStatus(state);
            }}
          />
        )}
      </>
    );
  }
}

NewsletterListNotificationComponent.propTypes = {
  location: PropTypes.object.isRequired, // eslint-disable-line react/forbid-prop-types
  match: PropTypes.shape({
    params: PropTypes.object, // eslint-disable-line react/forbid-prop-types
  }).isRequired,
};

export const NewsletterListNotification = withRouter(
  NewsletterListNotificationComponent,
);
