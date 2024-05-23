import classnames from 'classnames';
import { Component, Fragment } from 'react';
import { __ } from '@wordpress/i18n';
import { Link, withRouter } from 'react-router-dom';
import PropTypes from 'prop-types';
import ReactStringReplace from 'react-string-replace';

import { Button } from 'common/button/button';
import { ScheduledIcon } from 'common/listings/newsletter-status';
import { Listing } from 'listing/listing.jsx';
import { MailPoet } from 'mailpoet';
import { NewsletterTypes } from 'newsletters/types';
import {
  monthDayValues,
  nthWeekDayValues,
  timeOfDayValues,
  weekDayValues,
} from 'newsletters/scheduling/common.jsx';
import { FilterSegmentTag, SegmentTags } from 'common/tag/tags';
import { Toggle } from 'common/form/toggle/toggle';

import {
  checkCronStatus,
  checkMailerStatus,
  confirmEdit,
  sanitizeHTML,
} from 'newsletters/listings/utils.jsx';
import { withBoundary } from '../../common';

const messages = {
  onNoItemsFound: (group, search) =>
    search
      ? __('No emails found.', 'mailpoet')
      : __(
          "Nothing here yet! But, don't fret - there's no reason to get upset. Pretty soon, you’ll be sending emails faster than a turbo-jet.",
          'mailpoet',
        ),
  onTrash: (response) => {
    const count = Number(response.meta.count);
    let message = null;

    if (count === 1) {
      message = __('1 email was moved to the trash.', 'mailpoet');
    } else {
      message = __('%1$d emails were moved to the trash.', 'mailpoet').replace(
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
      message = __('1 email was permanently deleted.', 'mailpoet');
    } else {
      message = __('%1$d emails were permanently deleted.', 'mailpoet').replace(
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
      message = __('1 email has been restored from the Trash.', 'mailpoet');
    } else {
      message = __(
        '%1$d emails have been restored from the Trash.',
        'mailpoet',
      ).replace('%1$d', count.toLocaleString());
    }
    MailPoet.Notice.success(message);
  },
};

const columns = [
  {
    name: 'subject',
    label: __('Subject', 'mailpoet'),
    sortable: true,
  },
  {
    name: 'settings',
    label: __('Settings', 'mailpoet'),
  },
  {
    name: 'history',
    label: __('History', 'mailpoet'),
    width: 100,
  },
  {
    name: 'status',
    label: __('Status', 'mailpoet'),
    width: 100,
  },
  {
    name: 'updated_at',
    label: __('Last modified on', 'mailpoet'),
    sortable: true,
  },
];

const bulkActions = [
  {
    name: 'trash',
    label: __('Move to trash', 'mailpoet'),
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
          {__('Preview', 'mailpoet')}
        </a>
      );
    },
  },
  {
    name: 'edit',
    className: 'mailpoet-hide-on-mobile',
    label: __('Edit', 'mailpoet'),
    onClick: confirmEdit,
  },
  {
    name: 'duplicate',
    className: 'mailpoet-hide-on-mobile',
    label: __('Duplicate', 'mailpoet'),
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
            __('Email "%1$s" has been duplicated.', 'mailpoet').replace(
              '%1$s',
              sanitizeHTML(response.data.subject),
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
          MailPoet.Notice.success(
            __('Your post notification is now active!', 'mailpoet'),
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
          {__('You need to select a list to send to.', 'mailpoet')}
        </Link>
      );
    }

    const sendingToSegments = ReactStringReplace(
      __('Send to %1$s', 'mailpoet'),
      '%1$s',
      (match, i) => (
        <Fragment key={i}>
          <SegmentTags segments={newsletter.segments} key={`segment-${i}`} />
          <FilterSegmentTag
            key={`filter-segment-${i}`}
            newsletter={newsletter}
          />
        </Fragment>
      ),
    );

    // set sending frequency
    switch (newsletter.options.intervalType) {
      case 'daily':
        sendingFrequency = __('Daily at %1$s', 'mailpoet').replace(
          '%1$s',
          timeOfDayValues[newsletter.options.timeOfDay],
        );
        break;

      case 'weekly':
        sendingFrequency = __('Weekly on %1$s at %2$s', 'mailpoet')
          .replace('%1$s', weekDayValues[newsletter.options.weekDay])
          .replace('%2$s', timeOfDayValues[newsletter.options.timeOfDay]);
        break;

      case 'monthly':
        sendingFrequency = __('Monthly on the %1$s at %2$s', 'mailpoet')
          .replace('%1$s', monthDayValues[newsletter.options.monthDay])
          .replace('%2$s', timeOfDayValues[newsletter.options.timeOfDay]);
        break;

      case 'nthWeekDay':
        sendingFrequency = __(
          'Every %1$s %2$s of the month at %3$s',
          'mailpoet',
        )
          .replace('%1$s', nthWeekDayValues[newsletter.options.nthWeekDay])
          .replace('%2$s', weekDayValues[newsletter.options.weekDay])
          .replace('%3$s', timeOfDayValues[newsletter.options.timeOfDay]);
        break;

      case 'immediately':
        sendingFrequency = __('Immediately', 'mailpoet');
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
          {__('Not sent yet', 'mailpoet')}
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
          {__('View history', 'mailpoet')}
        </Button>
        <Button
          className="mailpoet-show-on-mobile mailpoet-listing-notification-status"
          dimension="small"
          variant="secondary"
        >
          {__('View history', 'mailpoet')}
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
            onClick={(event) => {
              event.preventDefault();
              confirmEdit(newsletter);
            }}
          >
            {newsletter.subject}
          </a>
          {actions}
        </td>
        <td
          className="column mailpoet-hide-on-mobile"
          data-colname={__('Settings', 'mailpoet')}
        >
          {this.renderSettings(newsletter)}
        </td>
        <td className="column" data-colname={__('History', 'mailpoet')}>
          {this.renderHistoryLink(newsletter)}
        </td>
        <td className="column" data-colname={__('Status', 'mailpoet')}>
          {this.renderStatus(newsletter)}
        </td>
        <td
          className="column-date mailpoet-hide-on-mobile"
          data-colname={__('Last modified on', 'mailpoet')}
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
NewsletterListNotificationComponent.displayName = 'NewsletterListNotification';
export const NewsletterListNotification = withRouter(
  withBoundary(NewsletterListNotificationComponent),
);
