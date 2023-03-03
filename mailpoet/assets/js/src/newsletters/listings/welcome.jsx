import _ from 'underscore';
import classnames from 'classnames';
import { __, _x } from '@wordpress/i18n';
import { Component } from 'react';
import PropTypes from 'prop-types';
import { Link, withRouter } from 'react-router-dom';

import ReactStringReplace from 'react-string-replace';
import { Toggle } from 'common/form/toggle/toggle';
import { Tag } from 'common/tag/tag';
import { ScheduledIcon } from 'common/listings/newsletter_status';
import { Listing } from 'listing/listing.jsx';
import { Statistics } from 'newsletters/listings/statistics.jsx';
import {
  addStatsCTAAction,
  checkCronStatus,
  checkMailerStatus,
  confirmEdit,
} from 'newsletters/listings/utils.jsx';

import { NewsletterTypes } from 'newsletters/types';
import { MailPoet } from 'mailpoet';
import { withBoundary } from 'common';

const mailpoetRoles = window.mailpoet_roles || {};
const mailpoetSegments = window.mailpoet_segments || {};
const mailpoetTrackingEnabled = MailPoet.trackingConfig.emailTrackingEnabled;

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
    name: 'statistics',
    label: __('Statistics', 'mailpoet'),
    display: mailpoetTrackingEnabled,
  },
  {
    name: 'status',
    label: __('Status', 'mailpoet'),
    width: 145,
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
          {__('Preview', 'mailpoet')}
        </a>
      );
    },
  },
  {
    name: 'duplicate',
    className: 'mailpoet-hide-on-mobile',
    label: __('Duplicate', 'mailpoet'),
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
            __('Email "%1$s" has been duplicated.', 'mailpoet').replace(
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
    name: 'edit',
    className: 'mailpoet-hide-on-mobile',
    label: __('Edit', 'mailpoet'),
    onClick: confirmEdit,
  },
  {
    name: 'trash',
    className: 'mailpoet-hide-on-mobile',
  },
];
newsletterActions = addStatsCTAAction(newsletterActions);

class NewsletterListWelcomeComponent extends Component {
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
            __('Your Welcome Email is now activated!', 'mailpoet'),
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
    const totalSentMessage = _x(
      '%1$d sent',
      'number of welcome emails sent',
      'mailpoet',
    ).replace('%1$d', newsletter.total_sent.toLocaleString());
    const totalScheduledMessage = _x(
      '%1$d scheduled',
      'number of welcome emails scheduled to be sent',
      'mailpoet',
    ).replace('%1$d', newsletter.total_scheduled.toLocaleString());

    return (
      <div>
        <Toggle
          className="mailpoet-listing-status-toggle"
          onCheck={this.updateStatus}
          data-id={newsletter.id}
          dimension="small"
          defaultChecked={newsletter.status === 'active'}
        />
        <p className="mailpoet-listing-stats-description">
          <Link
            to={`/sending-status/${newsletter.id}`}
            data-automation-id={`sending_status_${newsletter.id}`}
          >
            {totalSentMessage}
          </Link>{' '}
          <br />
          {totalScheduledMessage}
        </p>
      </div>
    );
  };

  renderSettings = (newsletter) => {
    let sendingEvent;
    let sendingDelay;
    let segment;

    // set sending event
    switch (newsletter.options.event) {
      case 'user':
        // WP User
        if (newsletter.options.role === 'mailpoet_all') {
          sendingEvent = __(
            'Sent when a new WordPress user is added to your site.',
            'mailpoet',
          );
        } else {
          sendingEvent = ReactStringReplace(
            __(
              'Sent when a new WordPress user with the role %1$s is added to your site.',
              'mailpoet',
            ),
            '%1$s',
            (match, i) => (
              <Tag variant="list" key={i}>
                {mailpoetRoles[newsletter.options.role]}
              </Tag>
            ),
          );
        }
        break;

      default:
        // get segment
        segment = _.find(
          mailpoetSegments,
          (seg) => Number(seg.id) === Number(newsletter.options.segment),
        );

        if (segment === undefined) {
          return (
            <Link
              className="mailpoet-listing-error"
              to={`/send/${newsletter.id}`}
            >
              {__('You need to select a list to send to.', 'mailpoet')}
            </Link>
          );
        }

        sendingEvent = ReactStringReplace(
          __('Sent when someone subscribes to the list: %1$s.', 'mailpoet'),
          '%1$s',
          (match, i) => (
            <Tag variant="list" key={i}>
              {segment.name}
            </Tag>
          ),
        );

        break;
    }

    // set sending delay
    if (sendingEvent) {
      if (newsletter.options.afterTimeType !== 'immediate') {
        switch (newsletter.options.afterTimeType) {
          case 'minutes':
            sendingDelay = __('%1$d minute(s) later', 'mailpoet').replace(
              '%1$d',
              newsletter.options.afterTimeNumber,
            );
            break;

          case 'hours':
            sendingDelay = __('%1$d hour(s) later', 'mailpoet').replace(
              '%1$d',
              newsletter.options.afterTimeNumber,
            );
            break;

          case 'days':
            sendingDelay = __('%1$d day(s) later', 'mailpoet').replace(
              '%1$d',
              newsletter.options.afterTimeNumber,
            );
            break;

          case 'weeks':
            sendingDelay = __('%1$d week(s) later', 'mailpoet').replace(
              '%1$d',
              newsletter.options.afterTimeNumber,
            );
            break;

          default:
            sendingDelay = __('Invalid sending delay.', 'mailpoet');
            break;
        }
      }
    }

    return (
      <span>
        {sendingEvent}
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
        {mailpoetTrackingEnabled === true ? (
          <td
            className="column mailpoet-listing-stats-column"
            data-colname={__('Clicked, Opened', 'mailpoet')}
          >
            <Statistics
              newsletter={newsletter}
              isSent={newsletter.total_sent > 0 && !!newsletter.statistics}
            />
          </td>
        ) : null}
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
            filter={(type) => type.slug === 'welcome'}
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
            type="welcome"
            base_url="welcome"
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

NewsletterListWelcomeComponent.propTypes = {
  location: PropTypes.object.isRequired, // eslint-disable-line react/forbid-prop-types
  match: PropTypes.shape({
    params: PropTypes.object, // eslint-disable-line react/forbid-prop-types
  }).isRequired,
};
NewsletterListWelcomeComponent.displayName = 'NewsletterListWelcome';
export const NewsletterListWelcome = withRouter(
  withBoundary(NewsletterListWelcomeComponent),
);
