import classnames from 'classnames';
import jQuery from 'jquery';
import { useEffect, useRef, useState } from 'react';
import { Link, useLocation, useParams } from 'react-router-dom';
import { __ } from '@wordpress/i18n';

import { Button, Checkbox, SegmentTags, SubscriberTags } from 'common';
import { Listing } from 'listing/listing.jsx';
import { MailPoet } from 'mailpoet';
import { Modal } from 'common/modal/modal';
import { Selection } from 'form/fields/selection.jsx';
import { MssAccessNotices } from 'notices/mss-access-notices';
import { ListingsEngagementScore } from './listings-engagement-score';
import { SubscribersHeading } from './heading';

type Segment = {
  id: string;
  name: string;
  subscribers: string;
  type: 'default' | 'wp_users' | 'woocommerce_users' | 'dynamic';
};

type Subscriber = {
  id: number;
  wp_user_id: number;
  count_confirmations: number;
  status: string;
  email: string;
  first_name: string;
  last_name: string;
  engagement_score: number;
  created_at: string;
  last_subscribed_at: string;
  subscriptions: Array<{
    id: number;
    status: string;
    segment_id: number;
  }>;
  tags: Array<{
    id: string;
    tag_id: string;
    subscriber_id: string;
    name: string;
  }>;
};

type Response = {
  data?: {
    selected_count: number;
    eligible_count: number;
    queued_count: number;
    skipped_count: number;
    skipped_by_reason: Record<string, number>;
    task_id: number | null;
    message: string;
  };
  meta: {
    count: number;
    segment: string;
    tag: string;
    errors?: string[];
  };
};

const mailpoetTrackingEnabled = MailPoet.trackingConfig.emailTrackingEnabled;
const bulkConfirmationResendLimit = 20;
const bulkConfirmationCheckboxId = 'bulk-resend-confirmation-checkbox-input';
const bulkConfirmationConfirmHelpId = 'bulk-resend-confirmation-confirm-help';

const getColumns = () => [
  {
    name: 'email',
    label: __('Subscriber', 'mailpoet'),
    sortable: true,
  },
  {
    name: 'status',
    label: __('Status', 'mailpoet'),
    sortable: true,
  },
  {
    name: 'segments',
    label: __('Lists', 'mailpoet'),
  },
  {
    name: 'tags',
    label: __('Tags', 'mailpoet'),
  },
  {
    name: 'statistics',
    label: __('Score', 'mailpoet'),
    display: mailpoetTrackingEnabled,
  },
  {
    name: 'last_subscribed_at',
    label: __('Subscribed on', 'mailpoet'),
    sortable: true,
  },
  {
    name: 'created_at',
    label: __('Created on', 'mailpoet'),
    sortable: true,
  },
];

const getMessages = () => ({
  onTrash: (response: Response) => {
    const count = Number(response.meta.count);
    let message = null;

    if (count === 1) {
      message = __('1 subscriber was moved to the trash.', 'mailpoet');
    } else {
      message = __(
        '%1$d subscribers were moved to the trash.',
        'mailpoet',
      ).replace('%1$d', count.toLocaleString());
    }
    MailPoet.Notice.success(message);
  },
  onDelete: (response: Response) => {
    const count = Number(response.meta.count);
    let message = null;

    if (count === 1) {
      message = __('1 subscriber was permanently deleted.', 'mailpoet');
    } else {
      message = __(
        '%1$d subscribers were permanently deleted.',
        'mailpoet',
      ).replace('%1$d', count.toLocaleString());
    }
    MailPoet.Notice.success(message);
  },
  onRestore: (response: Response) => {
    const count = Number(response.meta.count);
    let message = null;

    if (count === 1) {
      message = __(
        '1 subscriber has been restored from the trash.',
        'mailpoet',
      );
    } else {
      message = __(
        '%1$d subscribers have been restored from the trash.',
        'mailpoet',
      ).replace('%1$d', count.toLocaleString());
    }
    MailPoet.Notice.success(message);
  },
  onNoItemsFound: (group: string, search: string) => {
    if (
      group === 'bounced' &&
      !window.mailpoet_premium_active &&
      !window.mailpoet_mss_active
    ) {
      return (
        <div>
          <p>
            {__(
              "Email addresses that are invalid or don't exist anymore are called \"bounced addresses\". It's a good practice not to send emails to bounced addresses to keep a good reputation with spam filters. Send your emails with MailPoet and we'll automatically ensure to keep a list of bounced addresses without any setup.",
              'mailpoet',
            )}
          </p>
          <p>
            <a
              href="admin.php?page=mailpoet-upgrade"
              className="button-primary"
            >
              {__('Get premium version!', 'mailpoet')}
            </a>
          </p>
        </div>
      );
    }
    if (group !== 'trash' && search) {
      const encodedSearch = encodeURIComponent(search);
      return (
        <p>
          {__('No items found.', 'mailpoet')}{' '}
          <a
            href={`#/group[trash]/search[${encodedSearch}]`}
            className="button button-link"
          >
            {__('Have you checked the Trash?', 'mailpoet')}
          </a>
        </p>
      );
    }
    // use default message
    return false;
  },
});

const createModal = (submitModal, closeModal, field, title) => (
  <Modal title={title} onRequestClose={closeModal} isDismissible>
    <Selection field={field} />
    <span className="mailpoet-gap-half" />
    <Button onClick={submitModal} dimension="small" variant="secondary">
      {__('Apply', 'mailpoet')}
    </Button>
  </Modal>
);

const getFocusableElements = (container: HTMLElement) =>
  Array.from(
    container.querySelectorAll<HTMLElement>(
      'a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])',
    ),
  ).filter((element) => element.offsetParent !== null);

const trapModalTabFocus = (event: KeyboardEvent, container: HTMLElement) => {
  if (event.key !== 'Tab') {
    return;
  }

  const focusableElements = getFocusableElements(container);

  if (focusableElements.length === 0) {
    return;
  }

  const firstElement = focusableElements[0];
  const lastElement = focusableElements[focusableElements.length - 1];

  if (event.shiftKey && document.activeElement === firstElement) {
    event.preventDefault();
    lastElement.focus();
  } else if (!event.shiftKey && document.activeElement === lastElement) {
    event.preventDefault();
    firstElement.focus();
  }
};

function BulkResendConfirmationEmailsModal({
  submitModal,
  closeModal,
  count,
}: {
  submitModal: () => void;
  closeModal: () => void;
  count: number;
}) {
  const [isChecked, setIsChecked] = useState(false);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const modalContentRef = useRef<HTMLDivElement>(null);

  useEffect(() => {
    document.getElementById(bulkConfirmationCheckboxId)?.focus();
  }, []);

  useEffect(() => {
    const modalFrame = modalContentRef.current?.closest<HTMLElement>(
      '.mailpoet-modal-frame',
    );
    if (!modalFrame) {
      return undefined;
    }

    const handleKeyDown = (event: KeyboardEvent) => {
      trapModalTabFocus(event, modalFrame);
    };
    modalFrame.addEventListener('keydown', handleKeyDown);
    return () => modalFrame.removeEventListener('keydown', handleKeyDown);
  }, []);

  const handleSubmit = () => {
    if (!isChecked || isSubmitting) {
      return;
    }
    setIsSubmitting(true);
    submitModal();
  };

  return (
    <Modal
      title={__('Resend confirmation emails', 'mailpoet')}
      onRequestClose={closeModal}
      isDismissible
    >
      <div ref={modalContentRef}>
        <p>
          {__(
            'You selected %1$s subscribers. MailPoet will queue confirmation emails for up to %2$s eligible unconfirmed subscribers.',
            'mailpoet',
          )
            .replace('%1$s', Number(count).toLocaleString())
            .replace('%2$s', bulkConfirmationResendLimit.toLocaleString())}
        </p>
        <p>
          {__(
            'Subscribers who reached the resend limit, were emailed recently, are too old, or are no longer unconfirmed will be skipped.',
            'mailpoet',
          )}
        </p>
        <Checkbox
          id={bulkConfirmationCheckboxId}
          automationId="bulk-resend-confirmation-checkbox"
          checked={isChecked}
          disabled={isSubmitting}
          onCheck={(checked) => setIsChecked(checked)}
          isFullWidth
        >
          {__(
            'I understand these confirmation emails will be queued and only sent to eligible unconfirmed subscribers.',
            'mailpoet',
          )}
        </Checkbox>
        <p id={bulkConfirmationConfirmHelpId} className="description">
          {__('Confirm the checkbox to queue confirmation emails.', 'mailpoet')}
        </p>
        <span className="mailpoet-gap-half" />
        <Button
          onClick={handleSubmit}
          dimension="small"
          isDisabled={!isChecked || isSubmitting}
          withSpinner={isSubmitting}
          aria-describedby={bulkConfirmationConfirmHelpId}
          automationId="bulk-resend-confirmation-confirm"
        >
          {__('Queue emails', 'mailpoet')}
        </Button>
      </div>
    </Modal>
  );
}

const showBulkResendConfirmationNotice = (response: Response) => {
  const data = response.data;
  if (!data) {
    MailPoet.Notice.success(__('Confirmation emails were queued.', 'mailpoet'));
    return;
  }

  const queuedCount = Number(data.queued_count);
  const skippedCount = Number(data.skipped_count);
  let message;

  if (queuedCount === 0) {
    message = __(
      'No confirmation emails were queued. No selected subscribers were eligible.',
      'mailpoet',
    );
  } else if (queuedCount === 1 && skippedCount === 1) {
    message = __(
      'Confirmation emails were queued for 1 subscriber. 1 selected subscriber was skipped.',
      'mailpoet',
    );
  } else if (queuedCount === 1 && skippedCount > 0) {
    message = __(
      'Confirmation emails were queued for 1 subscriber. %1$s selected subscribers were skipped.',
      'mailpoet',
    ).replace('%1$s', skippedCount.toLocaleString());
  } else if (skippedCount === 1) {
    message = __(
      'Confirmation emails were queued for %1$s subscribers. 1 selected subscriber was skipped.',
      'mailpoet',
    ).replace('%1$s', queuedCount.toLocaleString());
  } else if (skippedCount > 0) {
    message = __(
      'Confirmation emails were queued for %1$s subscribers. %2$s selected subscribers were skipped.',
      'mailpoet',
    )
      .replace('%1$s', queuedCount.toLocaleString())
      .replace('%2$s', skippedCount.toLocaleString());
  } else if (queuedCount === 1) {
    message = __(
      'Confirmation emails were queued for 1 subscriber.',
      'mailpoet',
    );
  } else {
    message = __(
      'Confirmation emails were queued for %1$s subscribers.',
      'mailpoet',
    ).replace('%1$s', queuedCount.toLocaleString());
  }

  MailPoet.Notice.success(message, {
    onOpen: (element) => {
      element.attr('role', 'status');
      element.attr('aria-live', 'polite');
    },
  });
};

const getBulkActions = () => {
  const bulkActions = [
    {
      name: 'moveToList',
      label: __('Move to list...', 'mailpoet'),
      onSelect: function onSelect(submitModal, closeModal) {
        const field = {
          id: 'move_to_segment',
          name: 'move_to_segment',
          endpoint: 'segments',
          filter: function filter(segment) {
            return !!(!segment.deleted_at && segment.type === 'default');
          },
        };

        return createModal(
          submitModal,
          closeModal,
          field,
          __('Move to list...', 'mailpoet'),
        );
      },
      getData: function getData() {
        return {
          segment_id: Number(jQuery('#move_to_segment').val()),
        };
      },
      onSuccess: function onSuccess(response: Response) {
        MailPoet.Notice.success(
          __(
            '%1$d subscribers were moved to list <strong>%2$s</strong>.',
            'mailpoet',
          )
            .replace('%1$d', Number(response.meta.count).toLocaleString())
            .replace('%2$s', response.meta.segment),
        );
      },
    },
    {
      name: 'addToList',
      label: __('Add to list...', 'mailpoet'),
      onSelect: function onSelect(submitModal, closeModal) {
        const field = {
          id: 'add_to_segment',
          name: 'add_to_segment',
          endpoint: 'segments',
          filter: function filter(segment) {
            return !!(!segment.deleted_at && segment.type === 'default');
          },
        };

        return createModal(
          submitModal,
          closeModal,
          field,
          __('Add to list...', 'mailpoet'),
        );
      },
      getData: function getData() {
        return {
          segment_id: Number(jQuery('#add_to_segment').val()),
        };
      },
      onSuccess: function onSuccess(response: Response) {
        MailPoet.Notice.success(
          __(
            '%1$d subscribers were added to list <strong>%2$s</strong>.',
            'mailpoet',
          )
            .replace('%1$d', Number(response.meta.count).toLocaleString())
            .replace('%2$s', response.meta.segment),
        );
      },
    },
    {
      name: 'removeFromList',
      label: __('Remove from list...', 'mailpoet'),
      onSelect: function onSelect(submitModal, closeModal) {
        const field = {
          id: 'remove_from_segment',
          name: 'remove_from_segment',
          endpoint: 'segments',
          filter: function filter(segment) {
            return segment.type === 'default';
          },
        };

        return createModal(
          submitModal,
          closeModal,
          field,
          __('Remove from list...', 'mailpoet'),
        );
      },
      getData: function getData() {
        return {
          segment_id: Number(jQuery('#remove_from_segment').val()),
        };
      },
      onSuccess: function onSuccess(response: Response) {
        MailPoet.Notice.success(
          __(
            '%1$d subscribers were removed from list <strong>%2$s</strong>.',
            'mailpoet',
          )
            .replace('%1$d', Number(response.meta.count).toLocaleString())
            .replace('%2$s', response.meta.segment),
        );
      },
    },
    {
      name: 'removeFromAllLists',
      label: __('Remove from all lists', 'mailpoet'),
      onSuccess: function onSuccess(response: Response) {
        MailPoet.Notice.success(
          __(
            '%1$d subscribers were removed from all lists.',
            'mailpoet',
          ).replace('%1$d', Number(response.meta.count).toLocaleString()),
        );
      },
    },
    {
      name: 'trash',
      label: __('Move to trash', 'mailpoet'),
      onSuccess: getMessages().onTrash,
    },
    {
      name: 'unsubscribe',
      label: __('Unsubscribe', 'mailpoet'),
      display: ({ group }) => group !== 'unsubscribed',
      onSelect: (submitModal, closeModal, bulkActionProps) => {
        const count =
          bulkActionProps.selection !== 'all'
            ? bulkActionProps.selected_ids.length
            : bulkActionProps.count;
        return (
          <Modal
            title={__('Unsubscribe', 'mailpoet')}
            onRequestClose={closeModal}
            isDismissible
          >
            <p>
              {__(
                'This action will unsubscribe %s subscribers from all lists. This action cannot be undone. Are you sure, you want to continue?',
                'mailpoet',
              ).replace('%s', Number(count).toLocaleString())}
            </p>
            <span className="mailpoet-gap-half" />
            <Button
              onClick={submitModal}
              dimension="small"
              variant="secondary"
              automationId="bulk-unsubscribe-confirm"
            >
              {__('Apply', 'mailpoet')}
            </Button>
          </Modal>
        );
      },
    },
    {
      name: 'resendConfirmationEmails',
      label: __('Resend confirmation emails', 'mailpoet'),
      display: ({ group }) => group === 'unconfirmed',
      onSelect: (submitModal, closeModal, bulkActionProps) => {
        const count =
          bulkActionProps.selection !== 'all'
            ? bulkActionProps.selected_ids.length
            : bulkActionProps.count;
        return (
          <BulkResendConfirmationEmailsModal
            submitModal={submitModal}
            closeModal={closeModal}
            count={count}
          />
        );
      },
      onSuccess: showBulkResendConfirmationNotice,
    },
    {
      name: 'addTag',
      label: __('Add tag...', 'mailpoet'),
      onSelect: function onSelect(submitModal, closeModal) {
        const field = {
          id: 'add_tag',
          name: 'add_tag',
          endpoint: 'tags',
        };

        return createModal(
          submitModal,
          closeModal,
          field,
          __('Add tag...', 'mailpoet'),
        );
      },
      getData: function getData() {
        return {
          tag_id: Number(jQuery('#add_tag').val()),
        };
      },
      onSuccess: function onSuccess(response: Response) {
        MailPoet.Notice.success(
          __(
            'Tag <strong>%1$s</strong> was added to %2$d subscribers.',
            'mailpoet',
          )
            .replace('%1$s', response.meta.tag)
            .replace('%2$d', Number(response.meta.count).toLocaleString()),
        );
      },
    },
    {
      name: 'removeTag',
      label: __('Remove tag...', 'mailpoet'),
      onSelect: function onSelect(submitModal, closeModal) {
        const field = {
          id: 'remove_tag',
          name: 'remove_tag',
          endpoint: 'tags',
        };

        return createModal(
          submitModal,
          closeModal,
          field,
          __('Remove tag...', 'mailpoet'),
        );
      },
      getData: function getData() {
        return {
          tag_id: Number(jQuery('#remove_tag').val()),
        };
      },
      onSuccess: function onSuccess(response: Response) {
        MailPoet.Notice.success(
          __(
            'Tag <strong>%1$s</strong> was removed from %2$d subscribers.',
            'mailpoet',
          )
            .replace('%1$s', response.meta.tag)
            .replace('%2$d', Number(response.meta.count).toLocaleString()),
        );
      },
    },
  ];

  return bulkActions;
};

const getItemActions = () => [
  {
    name: 'statistics',
    label: __('Statistics', 'mailpoet'),
    link: function link(subscriber: Subscriber, location) {
      return (
        <Link
          to={`/stats/${subscriber.id}`}
          state={{
            backUrl: location?.pathname,
          }}
        >
          {__('Statistics', 'mailpoet')}
        </Link>
      );
    },
  },
  {
    name: 'edit',
    label: __('Edit', 'mailpoet'),
    link: function link(subscriber: Subscriber, location) {
      return (
        <Link
          to={`/edit/${subscriber.id}`}
          state={{
            backUrl: location?.pathname,
          }}
        >
          {__('Edit', 'mailpoet')}
        </Link>
      );
    },
  },
  {
    name: 'sendConfirmationEmail',
    className: 'mailpoet-hide-on-mobile',
    label: __('Resend confirmation email', 'mailpoet'),
    display: function display(subscriber: Subscriber) {
      return subscriber.status === 'unconfirmed';
    },
    onClick: function onClick(subscriber) {
      return MailPoet.Ajax.post({
        api_version: window.mailpoet_api_version,
        endpoint: 'subscribers',
        action: 'sendConfirmationEmail',
        data: {
          id: subscriber.id,
        },
      })
        .done(() =>
          MailPoet.Notice.success(
            __('1 confirmation email has been sent.', 'mailpoet'),
          ),
        )
        .fail((response) => MailPoet.Notice.showApiErrorNotice(response));
    },
  },
  {
    name: 'trash',
    className: 'mailpoet-hide-on-mobile',
  },
];

const isItemDeletable = (subscriber) =>
  Number(subscriber.wp_user_id) === 0 &&
  Number(subscriber.is_woocommerce_user) === 0;

const getSegmentFromId = (segmentId): Segment => {
  let result: Segment | null = null;
  window.mailpoet_segments.forEach((segment: Segment) => {
    if (segment.id === segmentId) {
      result = segment;
    }
  });
  return result;
};

function SubscriberList() {
  const location = useLocation();
  const params = useParams();

  const renderItem = (subscriber: Subscriber, actions) => {
    const rowClasses = classnames(
      'manage-column',
      'column-primary',
      'has-row-actions',
      'column-username',
    );

    let status = '';

    switch (subscriber.status) {
      case 'subscribed':
        status = __('Subscribed', 'mailpoet');
        break;

      case 'unconfirmed':
        status = __('Unconfirmed', 'mailpoet');
        break;

      case 'unsubscribed':
        status = __('Unsubscribed', 'mailpoet');
        break;

      case 'inactive':
        status = __('Inactive', 'mailpoet');
        break;

      case 'bounced':
        status = __('Bounced', 'mailpoet');
        break;

      default:
        status = 'Invalid';
        break;
    }

    const subscribedSegments = [];

    // Subscriptions
    if (subscriber.subscriptions.length > 0) {
      subscriber.subscriptions.forEach((subscription) => {
        const segment = getSegmentFromId(subscription.segment_id);
        if (segment === null) return;
        if (subscription.status === 'subscribed') {
          subscribedSegments.push(segment);
        }
      });
    }

    return (
      <>
        <td className={rowClasses}>
          <Link
            className="mailpoet-listing-title"
            to={`/edit/${subscriber.id}`}
            state={{
              backUrl: location?.pathname,
            }}
          >
            {subscriber.email}
          </Link>
          <div className="mailpoet-listing-subtitle">
            {subscriber.first_name} {subscriber.last_name}
          </div>
          {actions}
        </td>
        <td className="column" data-colname={__('Status', 'mailpoet')}>
          {status}
        </td>
        <td className="column" data-colname={__('Lists', 'mailpoet')}>
          <SegmentTags segments={subscribedSegments} dimension="large" />
        </td>
        <td className="column" data-colname={__('Tags', 'mailpoet')}>
          <SubscriberTags
            subscribers={subscriber.tags}
            variant="wordpress"
            isInverted
          />
        </td>
        {mailpoetTrackingEnabled === true ? (
          <td
            className="column mailpoet-listing-stats-column"
            data-colname={__('Score', 'mailpoet')}
          >
            <div className="mailpoet-listing-stats">
              <a
                key={`stats-link-${subscriber.id}`}
                href={`#/stats/${subscriber.id}`}
              >
                <ListingsEngagementScore
                  id={subscriber.id}
                  engagementScore={subscriber.engagement_score}
                />
              </a>
            </div>
          </td>
        ) : null}
        <td
          className="column-date mailpoet-hide-on-mobile"
          data-colname={__('Confirmed on', 'mailpoet')}
        >
          {subscriber.last_subscribed_at ? (
            <>
              {MailPoet.Date.short(subscriber.last_subscribed_at)}
              <br />
              {MailPoet.Date.time(subscriber.last_subscribed_at)}
            </>
          ) : null}
        </td>
        <td
          className="column-date mailpoet-hide-on-mobile"
          data-colname={__('Subscribed on', 'mailpoet')}
        >
          {subscriber.created_at ? (
            <>
              {MailPoet.Date.short(subscriber.created_at)}
              <br />
              {MailPoet.Date.time(subscriber.created_at)}
            </>
          ) : null}
        </td>
      </>
    );
  };

  return (
    <div>
      <SubscribersHeading />

      <MssAccessNotices />

      <Listing
        limit={window.mailpoet_listing_per_page}
        location={location}
        params={params}
        endpoint="subscribers"
        onRenderItem={renderItem}
        columns={getColumns()}
        bulk_actions={getBulkActions()}
        item_actions={getItemActions()}
        messages={getMessages()}
        sort_by="created_at"
        sort_order="desc"
        isItemDeletable={isItemDeletable}
      />
    </div>
  );
}

SubscriberList.displayName = 'SubscriberList';
export { SubscriberList };
