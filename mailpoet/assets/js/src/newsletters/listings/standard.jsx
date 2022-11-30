import classnames from 'classnames';
import { Component } from 'react';
import { MailPoet } from 'mailpoet';
import PropTypes from 'prop-types';
import { withRouter } from 'react-router-dom';

import { confirmAlert } from 'common/confirm_alert.jsx';
import { SegmentTags } from 'common/tag/tags';
import { Listing } from 'listing/listing.jsx';
import { QueueStatus } from 'newsletters/listings/queue_status.jsx';
import { Statistics } from 'newsletters/listings/statistics.jsx';
import {
  addStatsCTAAction,
  checkCronStatus,
  checkMailerStatus,
} from 'newsletters/listings/utils.jsx';
import { NewsletterTypes } from 'newsletters/types';
import { GlobalContext } from 'context/index.jsx';

const mailpoetTrackingEnabled = MailPoet.trackingConfig.emailTrackingEnabled;

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
    name: 'status',
    label: MailPoet.I18n.t('status'),
  },
  {
    name: 'segments',
    label: MailPoet.I18n.t('lists'),
  },
  {
    name: 'statistics',
    label: MailPoet.I18n.t('statistics'),
    display: mailpoetTrackingEnabled,
  },
  {
    name: 'sent_at',
    label: MailPoet.I18n.t('sentOn'),
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

const confirmEdit = (newsletter) => {
  const redirectToEditing = () => {
    window.location.href = `?page=mailpoet-newsletter-editor&id=${newsletter.id}`;
  };
  if (
    !newsletter.queue ||
    newsletter.status !== 'sending' ||
    newsletter.queue.status !== null
  ) {
    redirectToEditing();
  } else {
    confirmAlert({
      message: MailPoet.I18n.t('confirmEdit'),
      onConfirm: redirectToEditing,
    });
  }
};

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
newsletterActions = addStatsCTAAction(newsletterActions);

class NewsletterListStandardComponent extends Component {
  constructor(props) {
    super(props);
    this.state = {
      newslettersCount: undefined,
    };
  }

  renderItem = (newsletter, actions, meta) => {
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
            href="#"
            onClick={(event) => {
              event.preventDefault();
              confirmEdit(newsletter);
            }}
          >
            {newsletter.queue.newsletter_rendered_subject || newsletter.subject}
          </a>
          {actions}
        </td>
        <td
          className="column mailpoet-listing-status-column"
          data-colname={MailPoet.I18n.t('status')}
        >
          <QueueStatus newsletter={newsletter} mailerLog={meta.mta_log} />
        </td>
        <td
          className="column mailpoet-hide-on-mobile"
          data-colname={MailPoet.I18n.t('lists')}
        >
          <SegmentTags segments={newsletter.segments} dimension="large" />
        </td>
        {mailpoetTrackingEnabled === true ? (
          <td
            className="column mailpoet-listing-stats-column"
            data-colname={MailPoet.I18n.t('statistics')}
          >
            <Statistics
              newsletter={newsletter}
              currentTime={meta.current_time}
            />
          </td>
        ) : null}
        <td
          className="column-date mailpoet-hide-on-mobile"
          data-colname={MailPoet.I18n.t('sentOn')}
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
            hideClosingButton
          />
        )}
        {this.state.newslettersCount !== 0 && (
          <Listing
            limit={window.mailpoet_listing_per_page}
            location={this.props.location}
            params={this.props.match.params}
            endpoint="newsletters"
            type="standard"
            base_url="standard"
            onRenderItem={this.renderItem}
            isItemInactive={this.isItemInactive}
            columns={columns}
            bulk_actions={bulkActions}
            item_actions={newsletterActions}
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
      </>
    );
  }
}

NewsletterListStandardComponent.contextType = GlobalContext;

NewsletterListStandardComponent.propTypes = {
  location: PropTypes.object.isRequired, // eslint-disable-line react/forbid-prop-types
  match: PropTypes.shape({
    params: PropTypes.object, // eslint-disable-line react/forbid-prop-types
  }).isRequired,
};

export const NewsletterListStandard = withRouter(
  NewsletterListStandardComponent,
);
