import classnames from 'classnames';
import { __, _x } from '@wordpress/i18n';
import { Component, Fragment } from 'react';
import PropTypes from 'prop-types';
import { Link, withRouter } from 'react-router-dom';
import ReactStringReplace from 'react-string-replace';

import { Toggle } from 'common/form/toggle/toggle';
import { FilterSegmentTag, SegmentTags } from 'common/tag/tags';
import { ScheduledIcon } from 'common/listings/newsletter-status';
import { Listing } from 'listing/listing.jsx';
import { MailPoet } from 'mailpoet';
import { Statistics } from 'newsletters/listings/statistics.jsx';
import {
  addStatsCTAAction,
  checkCronStatus,
  checkMailerStatus,
  confirmEdit,
} from 'newsletters/listings/utils.jsx';
import { NewsletterTypes } from 'newsletters/types';
import { withBoundary } from 'common';

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
    label: __('Clicked, Opened', 'mailpoet'),
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
            MailPoet.Notice.showApiErrorNotice(response, { scroll: true });
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

class NewsletterListReEngagementComponent extends Component {
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
            __('Your Re-engagement Email is now activated!', 'mailpoet'),
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
          </Link>
        </p>
      </div>
    );
  };

  renderSettings = (newsletter) => {
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

    let frequency = _x(
      'month',
      'month in the sentence "1 month after inactivity"',
      'mailpoet',
    );
    if (
      newsletter.options.afterTimeNumber > 1 &&
      newsletter.options.afterTimeType === 'months'
    ) {
      frequency = _x(
        'months',
        'months in the sentence "5 months after inactivity"',
        'mailpoet',
      );
    } else if (
      newsletter.options.afterTimeNumber > 1 &&
      newsletter.options.afterTimeType === 'weeks'
    ) {
      frequency = _x(
        'weeks',
        'weeks in the sentence "5 weeks after inactivity"',
        'mailpoet',
      );
    } else if (
      newsletter.options.afterTimeNumber === 1 &&
      newsletter.options.afterTimeType === 'weeks'
    ) {
      frequency = _x(
        'week',
        'week in the sentence "1 week after inactivity"',
        'mailpoet',
      );
    }

    const sendingFrequency = _x(
      '{$count} {$frequency} after inactivity',
      'example: "5 months after inactivity"',
      'mailpoet',
    )
      .replace('{$count}', newsletter.options.afterTimeNumber)
      .replace('{$frequency}', frequency);

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
            filter={(type) => type.slug === 're_engagement'}
            hideScreenOptions={false}
          />
        )}
        {this.state.newslettersCount !== 0 && (
          <Listing
            limit={window.mailpoet_listing_per_page}
            location={this.props.location}
            params={this.props.match.params}
            endpoint="newsletters"
            type="re_engagement"
            base_url="re_engagement"
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

NewsletterListReEngagementComponent.propTypes = {
  location: PropTypes.object.isRequired, // eslint-disable-line react/forbid-prop-types
  match: PropTypes.shape({
    params: PropTypes.object, // eslint-disable-line react/forbid-prop-types
  }).isRequired,
};
NewsletterListReEngagementComponent.displayName = 'NewsletterListReEngagement';
export const NewsletterListReEngagement = withRouter(
  withBoundary(NewsletterListReEngagementComponent),
);
