import classnames from 'classnames';
import { __ } from '@wordpress/i18n';
import { escapeHTML } from '@wordpress/escape-html';
import { Component } from 'react';
import { MailPoet } from 'mailpoet';
import PropTypes from 'prop-types';
import { useLocation, useParams } from 'react-router-dom';

import { confirmAlert } from 'common/confirm-alert.jsx';
import { FilterSegmentTag, SegmentTags } from 'common/tag/tags';
import { Listing } from 'listing/listing.jsx';
import { QueueStatus } from 'newsletters/listings/queue-status';
import { Statistics } from 'newsletters/listings/statistics.jsx';
import {
  addStatsCTAAction,
  checkCronStatus,
  checkMailerStatus,
} from 'newsletters/listings/utils.jsx';
import { pollExportStatus } from 'newsletters/statistics-export/poll-export-status';
import { NewsletterTypes } from 'newsletters/types';
import { GlobalContext } from 'context';
import { ErrorBoundary, withBoundary } from '../../common';
import { NewsletterShareModal, SHARE_VISIBILITY_PUBLIC } from './share-modal';

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
    name: 'name',
    label: __('Name', 'mailpoet'),
    sortable: true,
  },
  {
    name: 'status',
    label: __('Status', 'mailpoet'),
  },
  {
    name: 'segments',
    label: __('Lists', 'mailpoet'),
  },
  {
    name: 'statistics',
    label: __('Clicked, Opened', 'mailpoet'),
    display: mailpoetTrackingEnabled,
  },
  {
    name: 'sent_at',
    label: __('Sent on', 'mailpoet'),
    sortable: true,
  },
];

const handleBulkExportSuccess = (response, format) => {
  if (!response?.data?.taskId) {
    MailPoet.Notice.error(__('Could not start the export.', 'mailpoet'), {
      scroll: true,
    });
    return;
  }
  MailPoet.Notice.success(
    __(
      'Export queued. The download will start automatically when it is ready.',
      'mailpoet',
    ),
    { timeout: 6000 },
  );
  pollExportStatus(response.data.taskId, {
    onComplete: (status) => {
      if (!status.exportFileURL) {
        MailPoet.Notice.error(
          __('The export file could not be generated.', 'mailpoet'),
          { scroll: true },
        );
        return;
      }
      MailPoet.trackEvent('Email statistics export completed', {
        'File Format': format,
        'Export Type': 'bulk',
      });
      window.location.href = status.exportFileURL;
    },
    onError: (message) => {
      MailPoet.Notice.error(message, { scroll: true });
    },
  });
};

const isDetailedAnalyticsRestricted = () =>
  !MailPoet.capabilities.detailedAnalytics ||
  MailPoet.capabilities.detailedAnalytics.isRestricted;

const bulkActions = [
  {
    name: 'trash',
    label: __('Move to trash', 'mailpoet'),
    onSuccess: messages.onTrash,
  },
];

if (mailpoetTrackingEnabled && !isDetailedAnalyticsRestricted()) {
  bulkActions.push({
    name: 'export_stats',
    label: __('Export statistics', 'mailpoet'),
    getData: () => ({ format: 'csv' }),
    onSuccess: (response) => handleBulkExportSuccess(response, 'csv'),
  });
}

const confirmEdit = (newsletter) => {
  const editorHref = MailPoet.getActiveEmailEditorUrl(newsletter);

  if (
    !newsletter.queue ||
    newsletter.status !== 'sending' ||
    newsletter.queue.status !== null
  ) {
    window.location.href = editorHref;
  } else {
    confirmAlert({
      message: __(
        'Sending is in progress. Do you want to pause sending and edit the newsletter?',
        'mailpoet',
      ),
      onConfirm: () => {
        window.location.href = `${editorHref}&pauseConfirmed=yes`;
      },
    });
  }
};

const baseNewsletterActions = [
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
              escapeHTML(response.data.subject),
            ),
          );
          refresh();
        })
        .fail((response) => {
          if (response.errors.length > 0) {
            MailPoet.Notice.showApiErrorNotice(response, { scroll: true });
          }
        });
    },
  },
  {
    name: 'trash',
    className: 'mailpoet-hide-on-mobile',
  },
];

function ShareNewsletterAction({ newsletter, onOpen }) {
  return (
    <a
      href="#"
      title={newsletter.share_unavailable_reason || undefined}
      data-automation-id={`newsletter_share_${newsletter.id}`}
      onClick={(event) => {
        event.preventDefault();
        onOpen(newsletter);
      }}
    >
      {__('Share', 'mailpoet')}
    </a>
  );
}

ShareNewsletterAction.propTypes = {
  newsletter: PropTypes.shape({
    id: PropTypes.oneOfType([PropTypes.number, PropTypes.string]).isRequired,
    share_unavailable_reason: PropTypes.string,
  }).isRequired,
  onOpen: PropTypes.func.isRequired,
};

class NewsletterListStandardComponent extends Component {
  constructor(props) {
    super(props);
    this.refreshListingRef = { current: null };
    this.state = {
      newslettersCount: undefined,
      shareNewsletter: null,
    };
  }

  openShareModal = (newsletter) => {
    this.setState({ shareNewsletter: newsletter });
    MailPoet.trackEvent('Emails > Share modal opened');
  };

  renderShareAction = (newsletter) => (
    <ShareNewsletterAction
      newsletter={newsletter}
      onOpen={this.openShareModal}
    />
  );

  getNewsletterActions = () =>
    addStatsCTAAction([
      baseNewsletterActions[0],
      {
        name: 'share',
        className: 'mailpoet-hide-on-mobile',
        link: this.renderShareAction,
      },
      ...baseNewsletterActions.slice(1),
    ]);

  closeShareModal = () => {
    this.setState({ shareNewsletter: null });
  };

  makeNewsletterPublic = (newsletter) =>
    MailPoet.Ajax.post({
      api_version: window.mailpoet_api_version,
      endpoint: 'newsletters',
      action: 'updateShareVisibility',
      data: {
        id: newsletter.id,
        share_visibility: SHARE_VISIBILITY_PUBLIC,
      },
    })
      .done((response) => {
        this.setState({ shareNewsletter: response.data });
        if (typeof this.refreshListingRef.current === 'function') {
          this.refreshListingRef.current();
        }
        MailPoet.trackEvent('Emails > Share email made public');
      })
      .fail((response) => {
        if (response.errors.length > 0) {
          MailPoet.Notice.showApiErrorNotice(response, { scroll: true });
        }
      });

  renderItem = (newsletter, actions, meta) => {
    const rowClasses = classnames(
      'manage-column',
      'column-primary',
      'has-row-actions',
    );

    const subject =
      newsletter.queue.newsletter_rendered_subject || newsletter.subject;

    return (
      <div>
        <td className={rowClasses}>
          <a
            className="mailpoet-listing-title"
            href="#"
            onClick={(event) => {
              event.preventDefault();
              confirmEdit(newsletter);
            }}
          >
            {newsletter.campaign_name ? (
              <>
                {newsletter.campaign_name} <br />
                <span className="mailpoet-listing-subtitle">{subject}</span>
              </>
            ) : (
              subject
            )}
          </a>
          {actions}
        </td>
        <td
          className="column mailpoet-listing-status-column"
          data-colname={__('Status', 'mailpoet')}
        >
          <QueueStatus newsletter={newsletter} mailerLog={meta.mta_log} />
        </td>
        <td
          className="column mailpoet-hide-on-mobile"
          data-colname={__('Lists', 'mailpoet')}
        >
          <ErrorBoundary>
            <SegmentTags segments={newsletter.segments} dimension="large" />
            <FilterSegmentTag newsletter={newsletter} dimension="large" />
          </ErrorBoundary>
        </td>
        {mailpoetTrackingEnabled === true ? (
          <td
            className="column mailpoet-listing-stats-column"
            data-colname={__('Clicked, Opened', 'mailpoet')}
          >
            <Statistics
              newsletter={newsletter}
              currentTime={meta.current_time}
            />
          </td>
        ) : null}
        <td
          className="column-date mailpoet-hide-on-mobile"
          data-colname={__('Sent on', 'mailpoet')}
        >
          {newsletter.sent_at ? (
            <>
              {MailPoet.Date.short(newsletter.sent_at)}
              <br />
              {MailPoet.Date.time(newsletter.sent_at)}
            </>
          ) : null}
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
            filter={(type) => type.slug === 'standard'}
            hideScreenOptions={false}
          />
        )}
        {this.state.newslettersCount !== 0 && (
          <Listing
            refreshRef={this.refreshListingRef}
            limit={window.mailpoet_listing_per_page}
            location={this.props.location}
            params={this.props.params}
            endpoint="newsletters"
            type="standard"
            base_url="standard"
            onRenderItem={this.renderItem}
            isItemInactive={this.isItemInactive}
            columns={columns}
            bulk_actions={bulkActions}
            item_actions={this.getNewsletterActions()}
            messages={messages}
            auto_refresh
            sort_by="sent_at"
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
        {this.state.shareNewsletter && (
          <NewsletterShareModal
            newsletter={this.state.shareNewsletter}
            onClose={this.closeShareModal}
            onMakePublic={this.makeNewsletterPublic}
          />
        )}
      </>
    );
  }
}

NewsletterListStandardComponent.contextType = GlobalContext;

NewsletterListStandardComponent.propTypes = {
  location: PropTypes.object.isRequired, // eslint-disable-line react/forbid-prop-types
  params: PropTypes.object.isRequired, // eslint-disable-line react/forbid-prop-types
};
NewsletterListStandardComponent.displayName = 'NewsletterListStandard';

const WithBoundary = withBoundary(NewsletterListStandardComponent);

export function NewsletterListStandard(props) {
  const location = useLocation();
  const params = useParams();
  return <WithBoundary {...props} location={location} params={params} />;
}
