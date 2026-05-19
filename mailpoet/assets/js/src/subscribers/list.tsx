import classnames from 'classnames';
import {
  Button as WordPressButton,
  CheckboxControl,
  Modal as WordPressModal,
  Notice,
  __experimentalText as Text,
  __experimentalVStack as VStack,
} from '@wordpress/components';
import { createInterpolateElement } from '@wordpress/element';
import { escapeHTML } from '@wordpress/escape-html';
import { DataViews, View, Action } from '@wordpress/dataviews';
import {
  useCallback,
  useContext,
  useEffect,
  useMemo,
  useRef,
  useState,
  type SetStateAction,
} from 'react';
import { useNavigate } from 'react-router-dom';
import { __, _n, sprintf } from '@wordpress/i18n';

import { Button } from 'common';
import { useDataViewsQuery, type ListingQueryParams } from 'common/dataviews';
import type { ListingFilters, ListingGroup } from 'common/dataviews/types';
import { Select } from 'common/form/select/select';
import { MailPoet } from 'mailpoet';
import { Modal } from 'common/modal/modal';
import { Selection } from 'form/fields/selection.jsx';
import { MssAccessNotices } from 'notices/mss-access-notices';
import { GlobalContext, type GlobalContextValue } from 'context';
import { SubscribersHeading } from './heading';
import { getSubscriberFields } from './fields';
import {
  bulkAction,
  getSubscribers,
  sendConfirmationEmail,
  type Segment,
  type Subscriber,
  type SubscriberApiError,
  type SubscriberBulkAction,
  type SubscriberBulkActionResult,
  type SubscriberBulkActionScope,
} from './api';

type Group =
  | 'all'
  | 'subscribed'
  | 'unconfirmed'
  | 'unsubscribed'
  | 'inactive'
  | 'bounced'
  | 'trash';

type PendingModalAction =
  | 'moveToList'
  | 'addToList'
  | 'removeFromList'
  | 'unsubscribe'
  | 'resendConfirmationEmails'
  | 'addTag'
  | 'removeTag';

type PendingAction = {
  action: PendingModalAction;
  targets: Subscriber[];
} | null;

const mailpoetTrackingEnabled = MailPoet.trackingConfig.emailTrackingEnabled;
const bulkConfirmationResendLimit =
  window.mailpoet_bulk_confirmation_resend_limit;
const bulkConfirmationCheckboxId = 'bulk-resend-confirmation-checkbox-input';
const listingPerPage = Number(window.mailpoet_listing_per_page);

const DEFAULT_VIEW: View = {
  type: 'table',
  perPage: listingPerPage,
  page: 1,
  sort: { field: 'created_at', direction: 'desc' },
  fields: [
    'status',
    'segments',
    'tags',
    ...(mailpoetTrackingEnabled ? ['statistics'] : []),
    'last_subscribed_at',
    'created_at',
  ],
  titleField: 'email',
  showTitle: true,
};

function parseFilter(value: string): Record<string, string> {
  const parsed = new URLSearchParams(value);
  return Array.from(parsed.entries()).reduce<Record<string, string>>(
    (filters, [key, filterValue]) =>
      filterValue ? { ...filters, [key]: filterValue } : filters,
    {},
  );
}

function parseHash(): Partial<{
  group: Group;
  page: number;
  perPage: number;
  orderby: string;
  order: 'asc' | 'desc';
  search: string;
  filter: Record<string, string>;
}> {
  return window.location.hash
    .split('/')
    .map((part) => part.replace(/\]$/, '').split('['))
    .reduce((params, [key, value]) => {
      if (!value) return params;
      if (
        key === 'group' &&
        [
          'all',
          'subscribed',
          'unconfirmed',
          'unsubscribed',
          'inactive',
          'bounced',
          'trash',
        ].includes(value)
      ) {
        return { ...params, group: value as Group };
      }
      if ((key === 'page' || key === 'paged') && Number(value) > 0) {
        return { ...params, page: Number(value) };
      }
      if ((key === 'per_page' || key === 'limit') && Number(value) > 0) {
        return { ...params, perPage: Number(value) };
      }
      if (key === 'sort_by' || key === 'orderby') {
        return { ...params, orderby: value };
      }
      if (
        (key === 'sort_order' || key === 'order') &&
        (value === 'asc' || value === 'desc')
      ) {
        return { ...params, order: value };
      }
      if (key === 'search') {
        return { ...params, search: decodeURIComponent(value) };
      }
      if (key === 'filter') {
        return { ...params, filter: parseFilter(value) };
      }
      return params;
    }, {});
}

function getListingPath(
  group: Group,
  view: View,
  filter: Record<string, string>,
): string {
  const filterValue = new URLSearchParams(filter).toString();
  const entries: Array<[string, string | number | undefined]> = [
    ['group', group],
    ['filter', filterValue || undefined],
    ['search', view.search ? encodeURIComponent(view.search) : undefined],
    ['page', view.page && view.page !== 1 ? view.page : undefined],
    [
      'limit',
      view.perPage && view.perPage !== listingPerPage
        ? view.perPage
        : undefined,
    ],
    [
      'sort_by',
      view.sort?.field && view.sort.field !== 'created_at'
        ? view.sort.field
        : undefined,
    ],
    [
      'sort_order',
      view.sort?.direction && view.sort.direction !== 'desc'
        ? view.sort.direction
        : undefined,
    ],
  ];
  const path = entries.reduce(
    (hash, [key, value]) => (value ? `${hash}/${key}[${value}]` : hash),
    '',
  );
  return path || '/';
}

function updateHash(
  group: Group,
  view: View,
  filter: Record<string, string>,
): void {
  const path = getListingPath(group, view, filter);
  const hash = `#${path}`;
  if (window.location.hash !== hash) {
    window.history.replaceState(null, '', hash);
  }
}

function isItemDeletable(subscriber: Subscriber): boolean {
  return (
    Number(subscriber.wp_user_id) === 0 &&
    Number(subscriber.is_woocommerce_user) === 0
  );
}

function formatCount(count: number): string {
  return count.toLocaleString();
}

type PickerKind = 'segment' | 'tag';

type PickerConfig = {
  kind: PickerKind;
  fieldId: string;
  endpoint: 'segments' | 'tags';
  filter?: (segment: Segment) => boolean;
};

const PICKERS: Record<
  Exclude<PendingModalAction, 'unsubscribe' | 'resendConfirmationEmails'>,
  PickerConfig
> = {
  moveToList: {
    kind: 'segment',
    fieldId: 'move_to_segment',
    endpoint: 'segments',
    filter: (segment) => !!(!segment.deleted_at && segment.type === 'default'),
  },
  addToList: {
    kind: 'segment',
    fieldId: 'add_to_segment',
    endpoint: 'segments',
    filter: (segment) => !!(!segment.deleted_at && segment.type === 'default'),
  },
  removeFromList: {
    kind: 'segment',
    fieldId: 'remove_from_segment',
    endpoint: 'segments',
    filter: (segment) => segment.type === 'default',
  },
  addTag: {
    kind: 'tag',
    fieldId: 'add_tag',
    endpoint: 'tags',
  },
  removeTag: {
    kind: 'tag',
    fieldId: 'remove_tag',
    endpoint: 'tags',
  },
};

function modalTitle(action: PendingModalAction): string {
  const titles = {
    moveToList: __('Move to list...', 'mailpoet'),
    addToList: __('Add to list...', 'mailpoet'),
    removeFromList: __('Remove from list...', 'mailpoet'),
    addTag: __('Add tag...', 'mailpoet'),
    removeTag: __('Remove tag...', 'mailpoet'),
    unsubscribe: __('Unsubscribe', 'mailpoet'),
    resendConfirmationEmails: __('Resend confirmation emails', 'mailpoet'),
  };
  return titles[action];
}

function actionSuccessMessage(
  action: SubscriberBulkAction,
  result: SubscriberBulkActionResult,
): void {
  const count = Number(result.count ?? 0);
  // Segment / tag names are user-controlled and end up rendered as HTML by
  // `MailPoet.Notice.success` (jQuery `.html()`), so escape before splicing
  // into the `<strong>%s</strong>` templates below.
  const segmentName = escapeHTML(result.segment?.name ?? '');
  const tagName = escapeHTML(result.tag?.name ?? '');
  if (action === 'trash') {
    MailPoet.Notice.success(
      count === 1
        ? __('1 subscriber was moved to the trash.', 'mailpoet')
        : __('%1$d subscribers were moved to the trash.', 'mailpoet').replace(
            '%1$d',
            formatCount(count),
          ),
    );
  } else if (action === 'delete') {
    MailPoet.Notice.success(
      count === 1
        ? __('1 subscriber was permanently deleted.', 'mailpoet')
        : __('%1$d subscribers were permanently deleted.', 'mailpoet').replace(
            '%1$d',
            formatCount(count),
          ),
    );
  } else if (action === 'restore') {
    MailPoet.Notice.success(
      count === 1
        ? __('1 subscriber has been restored from the trash.', 'mailpoet')
        : __(
            '%1$d subscribers have been restored from the trash.',
            'mailpoet',
          ).replace('%1$d', formatCount(count)),
    );
  } else if (action === 'moveToList') {
    MailPoet.Notice.success(
      __(
        '%1$d subscribers were moved to list <strong>%2$s</strong>.',
        'mailpoet',
      )
        .replace('%1$d', formatCount(count))
        .replace('%2$s', segmentName),
    );
  } else if (action === 'addToList') {
    MailPoet.Notice.success(
      __(
        '%1$d subscribers were added to list <strong>%2$s</strong>.',
        'mailpoet',
      )
        .replace('%1$d', formatCount(count))
        .replace('%2$s', segmentName),
    );
  } else if (action === 'removeFromList') {
    MailPoet.Notice.success(
      __(
        '%1$d subscribers were removed from list <strong>%2$s</strong>.',
        'mailpoet',
      )
        .replace('%1$d', formatCount(count))
        .replace('%2$s', segmentName),
    );
  } else if (action === 'removeFromAllLists') {
    MailPoet.Notice.success(
      __('%1$d subscribers were removed from all lists.', 'mailpoet').replace(
        '%1$d',
        formatCount(count),
      ),
    );
  } else if (action === 'unsubscribe') {
    MailPoet.Notice.success(
      count === 1
        ? __('1 subscriber was unsubscribed from all lists.', 'mailpoet')
        : __(
            '%1$d subscribers were unsubscribed from all lists.',
            'mailpoet',
          ).replace('%1$d', formatCount(count)),
    );
  } else if (action === 'addTag') {
    MailPoet.Notice.success(
      __('Tag <strong>%1$s</strong> was added to %2$d subscribers.', 'mailpoet')
        .replace('%1$s', tagName)
        .replace('%2$d', formatCount(count)),
    );
  } else if (action === 'removeTag') {
    MailPoet.Notice.success(
      __(
        'Tag <strong>%1$s</strong> was removed from %2$d subscribers.',
        'mailpoet',
      )
        .replace('%1$s', tagName)
        .replace('%2$d', formatCount(count)),
    );
  }
}

function showBulkResendConfirmationNotice(
  result: SubscriberBulkActionResult,
): void {
  const queue = result.queue;
  if (!queue) {
    MailPoet.Notice.success(
      __('Confirmation emails are being resent.', 'mailpoet'),
    );
    return;
  }

  const queuedCount = Number(queue.queued_count);
  const skippedCount = Number(queue.skipped_count);

  if (queuedCount === 0) {
    MailPoet.Notice.success(
      __(
        'No confirmation emails were resent. The selected subscribers could not receive another confirmation email right now.',
        'mailpoet',
      ),
    );
    return;
  }

  const messageParts = [
    String(
      sprintf(
        _n(
          'MailPoet is resending confirmation emails to %d subscriber.',
          'MailPoet is resending confirmation emails to %d subscribers.',
          queuedCount,
          'mailpoet',
        ),
        queuedCount,
      ),
    ),
  ];

  if (skippedCount > 0) {
    messageParts.push(
      String(
        sprintf(
          _n(
            '%d selected subscriber was skipped.',
            '%d selected subscribers were skipped.',
            skippedCount,
            'mailpoet',
          ),
          skippedCount,
        ),
      ),
    );
  }

  MailPoet.Notice.success(messageParts.join(' '), {
    onOpen: (element) => {
      element.attr('role', 'status');
      element.attr('aria-live', 'polite');
    },
  });
}

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

  useEffect(() => {
    document.getElementById(bulkConfirmationCheckboxId)?.focus();
  }, []);

  const handleSubmit = () => {
    if (!isChecked || isSubmitting) {
      return;
    }
    setIsSubmitting(true);
    submitModal();
  };

  return (
    <WordPressModal
      title={__('Resend confirmation emails', 'mailpoet')}
      onRequestClose={closeModal}
    >
      <VStack spacing={3}>
        <Text as="p">
          {sprintf(
            __(
              'You selected %1$s subscribers. MailPoet can resend confirmation emails to up to %2$s of them at a time.',
              'mailpoet',
            ),
            Number(count).toLocaleString(),
            bulkConfirmationResendLimit.toLocaleString(),
          )}
        </Text>
        <Text as="p">
          {__(
            'Some subscribers may be skipped if they already received too many confirmation emails, got one recently, or were added too long ago.',
            'mailpoet',
          )}
        </Text>
        <div data-automation-id="bulk-resend-confirmation-checkbox">
          <CheckboxControl
            id={bulkConfirmationCheckboxId}
            label={__(
              'I confirm these subscribers asked to join my list.',
              'mailpoet',
            )}
            checked={isChecked}
            disabled={isSubmitting}
            onChange={(checked) => setIsChecked(checked)}
          />
        </div>
        <div>
          <WordPressButton
            variant="primary"
            onClick={handleSubmit}
            disabled={!isChecked || isSubmitting}
            isBusy={isSubmitting}
            data-automation-id="bulk-resend-confirmation-confirm"
          >
            {__('Resend emails', 'mailpoet')}
          </WordPressButton>
        </div>
      </VStack>
    </WordPressModal>
  );
}

function PickerModal({
  title,
  config,
  onApply,
  onClose,
}: {
  title: string;
  config: PickerConfig;
  onApply: (value: number) => void;
  onClose: () => void;
}) {
  // The legacy `Selection` form widget wraps Select2 + jQuery. We treat it as
  // a controlled component by reading values out of its `onValueChange` callback
  // — no jQuery DOM lookups leak into this file.
  const [value, setValue] = useState<number>(0);
  const fieldConfig = useMemo(
    () => ({
      id: config.fieldId,
      name: config.fieldId,
      endpoint: config.endpoint,
      filter: config.filter,
      forceSelect2: true,
    }),
    [config],
  );

  const handleApply = (): void => {
    if (!value) return;
    onApply(value);
  };

  return (
    <Modal title={title} onRequestClose={onClose} isDismissible>
      <Selection
        field={fieldConfig}
        onValueChange={(event: { target: { value: string | number } }) => {
          const next = Number(event.target.value);
          setValue(Number.isFinite(next) ? next : 0);
        }}
      />
      <span className="mailpoet-gap-half" />
      <Button
        onClick={handleApply}
        dimension="small"
        variant="secondary"
        isDisabled={!value}
      >
        {__('Apply', 'mailpoet')}
      </Button>
    </Modal>
  );
}

function SubscriberFilters({
  filters,
  filter,
  group,
  onSelectFilter,
  onEmptyTrash,
}: {
  filters: ListingFilters;
  filter: Record<string, string>;
  group: Group;
  onSelectFilter: (name: string, value: string) => void;
  onEmptyTrash: () => void;
}) {
  const availableFilters = Object.keys(filters).filter(
    (filterName) =>
      !(
        filters[filterName].length === 0 ||
        (filters[filterName].length === 1 && !filters[filterName][0].value)
      ),
  );

  return (
    <div className="mailpoet-listing-filters">
      {availableFilters.map((filterName) => (
        <Select
          isMinWidth
          dimension="small"
          key={`filter-${filterName}`}
          name={filterName}
          value={filter[filterName] ?? ''}
          automationId={`listing_filter_${filterName}`}
          onChange={(event) =>
            onSelectFilter(filterName, event.currentTarget.value)
          }
        >
          {filters[filterName].map((option) => (
            <option value={option.value} key={`filter-option-${option.value}`}>
              {option.label}
            </option>
          ))}
        </Select>
      ))}
      {group === 'trash' && (
        <span className="mailpoet-listing-filters-empty-trash">
          <Button
            variant="secondary"
            onClick={onEmptyTrash}
            automationId="empty_trash"
          >
            {__('Empty Trash', 'mailpoet')}
          </Button>
        </span>
      )}
    </div>
  );
}

function EmptyContent({
  group,
  search,
  onCheckTrash,
}: {
  group: Group;
  search?: string;
  onCheckTrash: () => void;
}) {
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
          <a href="admin.php?page=mailpoet-upgrade" className="button-primary">
            {__('Get premium version!', 'mailpoet')}
          </a>
        </p>
      </div>
    );
  }
  if (group !== 'trash' && search) {
    return (
      <p>
        {__('No items found.', 'mailpoet')}{' '}
        <a
          href={`#/group[trash]/search[${encodeURIComponent(search)}]`}
          className="button button-link"
          onClick={(event) => {
            event.preventDefault();
            onCheckTrash();
          }}
        >
          {__('Have you checked the Trash?', 'mailpoet')}
        </a>
      </p>
    );
  }
  return <div>{__('No items found.', 'mailpoet')}</div>;
}

function SubscriberList() {
  const navigate = useNavigate();
  const { notices } = useContext<GlobalContextValue>(GlobalContext);
  const hashState = parseHash();
  const [group, setGroup] = useState<Group>(hashState.group ?? 'all');
  const [filter, setFilter] = useState<Record<string, string>>(
    hashState.filter ?? {},
  );
  const [selection, setSelection] = useState<string[]>([]);
  const [pendingAction, setPendingAction] = useState<PendingAction>(null);
  const triggerElementRef = useRef<HTMLElement | null>(null);
  // Synchronous guard that blocks re-entrancy while a confirmation-modal action
  // is in flight. The modal stays mounted until the REST call settles, so a
  // double-click on Apply / Resend emails / Unsubscribe must not fan out into
  // two bulk-action requests.
  const pendingActionInFlightRef = useRef(false);
  const [initialView] = useState<View>(() => ({
    ...DEFAULT_VIEW,
    page: hashState.page ?? DEFAULT_VIEW.page,
    perPage: hashState.perPage ?? DEFAULT_VIEW.perPage,
    search: hashState.search,
    sort: {
      field: hashState.orderby ?? DEFAULT_VIEW.sort?.field ?? 'created_at',
      direction: hashState.order ?? DEFAULT_VIEW.sort?.direction ?? 'desc',
    },
  }));

  const load = useCallback(
    (params: ListingQueryParams, signal?: AbortSignal) =>
      getSubscribers(
        {
          ...params,
          group,
          filter,
        },
        signal,
      ),
    [filter, group],
  );

  const {
    view,
    setView,
    items,
    meta,
    filters,
    groups,
    isLoading,
    error: loadError,
    clearError: clearLoadError,
    refresh,
  } = useDataViewsQuery<Subscriber>({
    initialView,
    load,
  });

  useEffect(() => {
    updateHash(group, view, filter);
  }, [filter, group, view]);

  // DataViews has no built-in URL state, so back/forward inside the listing
  // is wired manually. Browser navigation fires `hashchange`; programmatic
  // `replaceState` from `updateHash` does not, so this can't loop with our
  // own writes.
  useEffect(() => {
    const applyHash = (): void => {
      const next = parseHash();
      setGroup((current) => next.group ?? current);
      setFilter(next.filter ?? {});
      setSelection([]);
      clearLoadError();
      setView((currentView) => ({
        ...currentView,
        page: next.page ?? 1,
        perPage: next.perPage ?? currentView.perPage,
        search: next.search ?? '',
        sort: {
          field: next.orderby ?? currentView.sort?.field ?? 'created_at',
          direction: next.order ?? currentView.sort?.direction ?? 'desc',
        },
      }));
    };
    window.addEventListener('hashchange', applyHash);
    return () => window.removeEventListener('hashchange', applyHash);
  }, [clearLoadError, setView]);

  const backUrl = useMemo(
    () => getListingPath(group, view, filter),
    [filter, group, view],
  );
  const backUrlRef = useRef(backUrl);
  backUrlRef.current = backUrl;
  const getBackUrl = useCallback((): string => backUrlRef.current, []);

  const groupCounts = useMemo(() => {
    const counts: Record<Group, number | null> = {
      all: null,
      subscribed: null,
      unconfirmed: null,
      unsubscribed: null,
      inactive: null,
      bounced: null,
      trash: null,
    };
    (groups ?? []).forEach((entry: ListingGroup) => {
      if (entry.name in counts) {
        counts[entry.name as Group] = entry.count;
      }
    });
    return counts;
  }, [groups]);

  useEffect(() => {
    if (
      group === 'trash' &&
      !isLoading &&
      !loadError &&
      groupCounts.trash === 0 &&
      !view.search &&
      Object.keys(filter).length === 0
    ) {
      setGroup('all');
      setSelection([]);
      setView((currentView) => ({ ...currentView, page: 1 }));
    }
  }, [
    filter,
    group,
    groupCounts.trash,
    isLoading,
    loadError,
    setView,
    view.search,
  ]);

  const restoreTriggerFocus = useCallback((): void => {
    const triggerElement = triggerElementRef.current;
    triggerElementRef.current = null;
    if (triggerElement && document.contains(triggerElement)) {
      triggerElement.focus();
    }
  }, []);

  const closePendingAction = useCallback((): void => {
    setPendingAction(null);
    window.setTimeout(restoreTriggerFocus);
  }, [restoreTriggerFocus]);

  const openPendingAction = useCallback(
    (action: PendingModalAction, targets: Subscriber[]): void => {
      triggerElementRef.current = document.activeElement as HTMLElement | null;
      setPendingAction({ action, targets });
    },
    [],
  );

  const handleViewChange = useCallback(
    (nextView: SetStateAction<View>) => {
      setSelection([]);
      setView(nextView);
    },
    [setView],
  );

  const handleApiError = useCallback(
    (error: SubscriberApiError, action?: SubscriberBulkAction): void => {
      if (
        action === 'resendConfirmationEmails' &&
        error.code === 'mailpoet_subscribers_confirmation_disabled'
      ) {
        notices.error(
          <p>
            {createInterpolateElement(
              __(
                'Sign-up confirmation is disabled in your <link>MailPoet settings</link>. Please enable it to resend confirmation emails or update your subscriber’s status manually.',
                'mailpoet',
              ),
              {
                link: <a href="admin.php?page=mailpoet-settings#/signup"> </a>,
              },
            )}
          </p>,
          { scroll: true },
        );
        return;
      }
      const message =
        error.message ||
        __(
          'The bulk action could not be completed. Please try again.',
          'mailpoet',
        );
      notices.error(<p>{message}</p>, { scroll: true });
    },
    [notices],
  );

  const runBulkAction = useCallback(
    async (
      action: SubscriberBulkAction,
      scope: SubscriberBulkActionScope,
      extra: Record<string, unknown>,
    ): Promise<void> => {
      try {
        const response = await bulkAction(action, scope, extra);
        const result = response.data;
        setSelection([]);
        if (action === 'resendConfirmationEmails') {
          showBulkResendConfirmationNotice(result);
        } else {
          actionSuccessMessage(action, result);
        }
        refresh();
      } catch (error) {
        handleApiError(error as SubscriberApiError, action);
      }
    },
    [handleApiError, refresh],
  );

  const handleBulkAction = useCallback(
    async (
      action: SubscriberBulkAction,
      targets: Subscriber[],
      extra: Record<string, unknown> = {},
    ): Promise<void> => {
      if (targets.length === 0) return;
      const selectedIds = targets.map((subscriber) => Number(subscriber.id));
      await runBulkAction(
        action,
        {
          group,
          filter,
          search: view.search || '',
          selection: selectedIds,
        },
        extra,
      );
    },
    [filter, group, runBulkAction, view.search],
  );

  // "Empty Trash" is the only listing-scoped destructive call we make with no
  // selected ids — it asks the backend to delete everything in the current
  // group. Keep it out of `handleBulkAction` so the destructive "no-selection
  // means everything" semantics are visible at the call site instead of
  // hiding behind a guard in the generic handler.
  const handleEmptyTrash = useCallback(async (): Promise<void> => {
    if (group !== 'trash') return;
    await runBulkAction(
      'delete',
      {
        group: 'trash',
        filter,
        search: view.search || '',
        selection: [],
      },
      {},
    );
  }, [filter, group, runBulkAction, view.search]);

  const handlePendingActionSubmit = useCallback(
    async (extra: Record<string, unknown> = {}): Promise<void> => {
      if (!pendingAction || pendingActionInFlightRef.current) return;
      pendingActionInFlightRef.current = true;
      const { action, targets } = pendingAction;
      try {
        await handleBulkAction(action, targets, extra);
      } finally {
        pendingActionInFlightRef.current = false;
        setPendingAction(null);
        window.setTimeout(restoreTriggerFocus);
      }
    },
    [handleBulkAction, pendingAction, restoreTriggerFocus],
  );

  const handleSendConfirmationEmail = useCallback(
    async (subscriber: Subscriber): Promise<void> => {
      try {
        await sendConfirmationEmail(Number(subscriber.id));
        MailPoet.Notice.success(
          __('1 confirmation email has been sent.', 'mailpoet'),
        );
      } catch (error) {
        const message = (error as SubscriberApiError).message;
        MailPoet.Notice.error(
          message ||
            __(
              'There was a problem sending the confirmation email.',
              'mailpoet',
            ),
        );
      }
    },
    [],
  );

  const actions = useMemo<Action<Subscriber>[]>(
    () => [
      {
        id: 'statistics',
        label: __('Statistics', 'mailpoet'),
        context: 'single',
        supportsBulk: false,
        isEligible: () => group !== 'trash',
        callback: (targets) => {
          const subscriber = targets[0];
          if (subscriber) {
            navigate(`/stats/${subscriber.id}`, {
              state: { backUrl: getBackUrl() },
            });
          }
        },
      },
      {
        id: 'edit',
        label: __('Edit', 'mailpoet'),
        context: 'single',
        isPrimary: true,
        supportsBulk: false,
        isEligible: () => group !== 'trash',
        callback: (targets) => {
          const subscriber = targets[0];
          if (subscriber) {
            navigate(`/edit/${subscriber.id}`, {
              state: { backUrl: getBackUrl() },
            });
          }
        },
      },
      {
        id: 'sendConfirmationEmail',
        label: __('Resend confirmation email', 'mailpoet'),
        context: 'single',
        supportsBulk: false,
        isEligible: (item) =>
          group !== 'trash' &&
          item.status === 'unconfirmed' &&
          window.mailpoet_signup_confirmation_enabled,
        callback: (targets) => {
          if (targets[0]) {
            void handleSendConfirmationEmail(targets[0]);
          }
        },
      },
      {
        id: 'trash',
        label: __('Move to trash', 'mailpoet'),
        context: 'single',
        supportsBulk: false,
        isEligible: () => group !== 'trash',
        callback: (targets) => {
          void handleBulkAction('trash', targets);
        },
      },
      {
        id: 'restore',
        label: __('Restore', 'mailpoet'),
        context: 'single',
        supportsBulk: false,
        isEligible: () => group === 'trash',
        callback: (targets) => {
          void handleBulkAction('restore', targets);
        },
      },
      {
        id: 'delete',
        label: __('Delete permanently', 'mailpoet'),
        context: 'single',
        supportsBulk: false,
        isDestructive: true,
        isEligible: (item) => group === 'trash' && isItemDeletable(item),
        callback: (targets) => {
          void handleBulkAction('delete', targets);
        },
      },
      {
        id: 'moveToList',
        label: __('Move to list...', 'mailpoet'),
        context: 'list',
        supportsBulk: true,
        isEligible: () => group !== 'trash',
        callback: (targets) => openPendingAction('moveToList', targets),
      },
      {
        id: 'addToList',
        label: __('Add to list...', 'mailpoet'),
        context: 'list',
        supportsBulk: true,
        isEligible: () => group !== 'trash',
        callback: (targets) => openPendingAction('addToList', targets),
      },
      {
        id: 'removeFromList',
        label: __('Remove from list...', 'mailpoet'),
        context: 'list',
        supportsBulk: true,
        isEligible: () => group !== 'trash',
        callback: (targets) => openPendingAction('removeFromList', targets),
      },
      {
        id: 'removeFromAllLists',
        label: __('Remove from all lists', 'mailpoet'),
        context: 'list',
        supportsBulk: true,
        isEligible: () => group !== 'trash',
        callback: (targets) => {
          void handleBulkAction('removeFromAllLists', targets);
        },
      },
      {
        id: 'unsubscribe',
        label: __('Unsubscribe', 'mailpoet'),
        context: 'list',
        supportsBulk: true,
        isEligible: () => group !== 'trash' && group !== 'unsubscribed',
        callback: (targets) => openPendingAction('unsubscribe', targets),
      },
      {
        id: 'resendConfirmationEmails',
        label: __('Resend confirmation emails', 'mailpoet'),
        context: 'list',
        supportsBulk: true,
        isEligible: () =>
          group === 'unconfirmed' &&
          window.mailpoet_signup_confirmation_enabled,
        callback: (targets) =>
          openPendingAction('resendConfirmationEmails', targets),
      },
      {
        id: 'addTag',
        label: __('Add tag...', 'mailpoet'),
        context: 'list',
        supportsBulk: true,
        isEligible: () => group !== 'trash',
        callback: (targets) => openPendingAction('addTag', targets),
      },
      {
        id: 'removeTag',
        label: __('Remove tag...', 'mailpoet'),
        context: 'list',
        supportsBulk: true,
        isEligible: () => group !== 'trash',
        callback: (targets) => openPendingAction('removeTag', targets),
      },
      {
        id: 'bulkTrash',
        label: __('Move to trash', 'mailpoet'),
        context: 'list',
        supportsBulk: true,
        isEligible: () => group !== 'trash',
        callback: (targets) => {
          void handleBulkAction('trash', targets);
        },
      },
      {
        id: 'bulkRestore',
        label: __('Restore', 'mailpoet'),
        context: 'list',
        supportsBulk: true,
        isEligible: () => group === 'trash',
        callback: (targets) => {
          void handleBulkAction('restore', targets);
        },
      },
      {
        id: 'bulkDelete',
        label: __('Delete permanently', 'mailpoet'),
        context: 'list',
        supportsBulk: true,
        isDestructive: true,
        isEligible: (item) => group === 'trash' && isItemDeletable(item),
        callback: (targets) => {
          void handleBulkAction('delete', targets.filter(isItemDeletable));
        },
      },
    ],
    [
      getBackUrl,
      group,
      handleBulkAction,
      handleSendConfirmationEmail,
      navigate,
      openPendingAction,
    ],
  );

  const handleGroupSelect = (nextGroup: Group): void => {
    if (nextGroup === group) return;
    setGroup(nextGroup);
    setSelection([]);
    clearLoadError();
    setView((currentView) => ({ ...currentView, page: 1 }));
  };

  const handleFilterSelect = (filterName: string, value: string): void => {
    setFilter((currentFilter) => {
      const nextFilter = { ...currentFilter };
      if (value) {
        nextFilter[filterName] = value;
      } else {
        delete nextFilter[filterName];
      }
      return nextFilter;
    });
    setSelection([]);
    setView((currentView) => ({ ...currentView, page: 1 }));
  };

  const handleCheckTrash = (): void => {
    setGroup('trash');
    setSelection([]);
    clearLoadError();
    setView((currentView) => ({ ...currentView, page: 1 }));
  };

  const groupsToRender = useMemo(
    () =>
      (groups ?? [])
        .filter(
          (entry) =>
            !(entry.name === 'trash' && entry.count === 0 && group !== 'trash'),
        )
        .filter((entry) => entry.name in groupCounts),
    [group, groupCounts, groups],
  );

  const fields = useMemo(() => getSubscriberFields(getBackUrl), [getBackUrl]);

  const paginationInfo = useMemo(
    () => ({ totalItems: meta.count, totalPages: meta.pages }),
    [meta],
  );

  const renderPendingActionModal = (): JSX.Element | null => {
    if (!pendingAction) return null;
    const { action, targets } = pendingAction;

    if (action === 'unsubscribe') {
      return (
        <Modal
          title={__('Unsubscribe', 'mailpoet')}
          onRequestClose={closePendingAction}
          isDismissible
        >
          <p>
            {__(
              'This action will unsubscribe %s subscribers from all lists. This action cannot be undone. Are you sure, you want to continue?',
              'mailpoet',
            ).replace('%s', formatCount(targets.length))}
          </p>
          <span className="mailpoet-gap-half" />
          <Button
            onClick={() => handlePendingActionSubmit()}
            dimension="small"
            variant="secondary"
            automationId="bulk-unsubscribe-confirm"
          >
            {__('Apply', 'mailpoet')}
          </Button>
        </Modal>
      );
    }

    if (action === 'resendConfirmationEmails') {
      return (
        <BulkResendConfirmationEmailsModal
          submitModal={() => handlePendingActionSubmit()}
          closeModal={closePendingAction}
          count={targets.length}
        />
      );
    }

    const config = PICKERS[action];
    return (
      <PickerModal
        title={modalTitle(action)}
        config={config}
        onApply={(value) =>
          handlePendingActionSubmit(
            config.kind === 'segment'
              ? { segment_id: value }
              : { tag_id: value },
          )
        }
        onClose={closePendingAction}
      />
    );
  };

  return (
    <div>
      <SubscribersHeading />

      <MssAccessNotices />

      {loadError && (
        <Notice status="error" onRemove={clearLoadError}>
          {loadError === 'Failed to load data.'
            ? __('Failed to load subscribers.', 'mailpoet')
            : loadError}
        </Notice>
      )}

      <div className="mailpoet-categories mailpoet-dataviews__tabs mailpoet-subscribers-dataviews__tabs">
        <div className="components-tab-panel__tabs" role="tablist">
          {groupsToRender.map((entry) => {
            const tabClasses = classnames(
              'components-button',
              'components-tab-panel__tabs-item',
              `mailpoet-dataviews-group-${entry.name}`,
              {
                'is-active': entry.name === group,
              },
            );
            return (
              <a
                key={entry.name}
                href="#"
                className={tabClasses}
                data-automation-id={`filters_${entry.name}`}
                onClick={(event) => {
                  event.preventDefault();
                  handleGroupSelect(entry.name as Group);
                }}
              >
                <span data-title={entry.label}>{entry.label}</span>
                {Number(entry.count) > 0 && (
                  <span className="count">
                    {Number(entry.count).toLocaleString()}
                  </span>
                )}
              </a>
            );
          })}
        </div>
      </div>

      <div
        className="mailpoet-dataviews mailpoet-subscribers-dataviews"
        data-automation-id="subscribers_listing"
      >
        <DataViews<Subscriber>
          data={items}
          fields={fields}
          view={view}
          onChangeView={handleViewChange}
          actions={actions}
          paginationInfo={paginationInfo}
          defaultLayouts={{ table: {} }}
          getItemId={(item) => String(item.id)}
          selection={selection}
          onChangeSelection={setSelection}
          isLoading={isLoading}
          empty={
            <EmptyContent
              group={group}
              search={view.search}
              onCheckTrash={handleCheckTrash}
            />
          }
        >
          <div className="mailpoet-dataviews__toolbar mailpoet-subscribers-dataviews__toolbar">
            <DataViews.Search label={__('Search', 'mailpoet')} />
            <SubscriberFilters
              filters={filters}
              filter={filter}
              group={group}
              onSelectFilter={handleFilterSelect}
              onEmptyTrash={() => {
                void handleEmptyTrash();
              }}
            />
          </div>
          <DataViews.Layout />
          <DataViews.Footer />
        </DataViews>
      </div>

      {renderPendingActionModal()}
    </div>
  );
}

SubscriberList.displayName = 'SubscriberList';
export { SubscriberList };
