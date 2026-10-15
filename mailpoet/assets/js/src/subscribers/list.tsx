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
import { DataViews, View, Action, type Filter } from '@wordpress/dataviews';
import {
  useCallback,
  useContext,
  useEffect,
  useMemo,
  useRef,
  useState,
} from 'react';
import { useNavigate } from 'react-router-dom';
import { __, _n, sprintf } from '@wordpress/i18n';

import { Button } from 'common';
import {
  filterToExtraParams,
  getDataViewsPreference,
  usePersistedDataViewsPreference,
  useDataViewsQuery,
  type ListingQueryParams,
} from 'common/dataviews';
import type { ListingGroup } from 'common/dataviews/types';
import { MailPoet } from 'mailpoet';
import { Modal } from 'common/modal/modal';
import { Selection } from 'form/fields/selection.jsx';
import { MssAccessNotices } from 'notices/mss-access-notices';
import { GlobalContext, type GlobalContextValue } from 'context';
import { SubscribersHeading } from './heading';
import {
  getSubscriberFields,
  getSubscriberSortableFieldIds,
  type FieldElement,
} from './fields';
import {
  WITHOUT_LIST,
  getExactStatus,
  requestFilterToViewFilters,
  toOrderby,
  viewFiltersToRequestFilter,
} from './filters';
import {
  buildSubscribersHash,
  parseSubscribersHash,
  type SubscribersHashState,
} from './hash-state';
import { getInitialPickerValue, type PickerConfig } from './picker-value';
import { SelectAllBanner } from './select-all-banner';
import { selectAllBannerState, shouldWarnLargeOperation } from './select-all';
import { pruneUnknownIds } from './filter-sync';
import { canTriggerAutomations } from './bulk-action-payload';
import {
  bulkAction,
  getSubscribers,
  sendConfirmationEmail,
  type Subscriber,
  type SubscriberApiError,
  type SubscriberBulkAction,
  type SubscriberBulkActionResult,
  type SubscriberBulkActionScope,
} from './api';

type Group = 'all' | 'trash';

type PendingModalAction =
  | 'moveToList'
  | 'addToList'
  | 'removeFromList'
  | 'unsubscribe'
  | 'resendConfirmationEmails'
  | 'addTag'
  | 'removeTag';

type PendingAction = {
  action: SubscriberBulkAction;
  selectAll: boolean;
  scope: SubscriberBulkActionScope;
  count: number;
} | null;

const SELECT_ALL_GENERIC_CONFIRM_ACTIONS: SubscriberBulkAction[] = [
  'trash',
  'restore',
  'delete',
  'removeFromAllLists',
];

const mailpoetTrackingEnabled = MailPoet.trackingConfig.emailTrackingEnabled;
const bulkConfirmationResendLimit =
  window.mailpoet_bulk_confirmation_resend_limit;
const bulkConfirmationCheckboxId = 'bulk-resend-confirmation-checkbox-input';
const listingPerPage = Number(window.mailpoet_listing_per_page);

const DEFAULT_SORT_FIELD = 'created_at';

const DEFAULT_VIEW: View = {
  type: 'table',
  perPage: listingPerPage,
  page: 1,
  sort: { field: DEFAULT_SORT_FIELD, direction: 'desc' },
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

function readPreferredView(): View {
  return getDataViewsPreference(
    'subscribers',
    DEFAULT_VIEW,
    getSubscriberFields(() => '', undefined, { segments: [], tags: [] }),
  );
}

function isSortable(field: string | undefined): field is string {
  return (
    !!field &&
    getSubscriberSortableFieldIds(mailpoetTrackingEnabled).includes(field)
  );
}

// `defaults` is the preference-merged view the listing falls back to when the
// URL omits a param. Comparing against it (not the hardcoded site defaults)
// keeps the URL round-trip lossless: reloading a URL without explicit params
// resolves to the same view the URL was written from.
function getListingPath(group: Group, view: View, defaults: View): string {
  return buildSubscribersHash(
    {
      group,
      filter: viewFiltersToRequestFilter(view.filters, {
        trackingEnabled: mailpoetTrackingEnabled,
      }),
      page: view.page,
      perPage: view.perPage,
      order: view.sort?.direction,
      orderby: view.sort?.field,
      search: view.search,
    },
    {
      orderby: defaults.sort?.field ?? DEFAULT_SORT_FIELD,
      order: defaults.sort?.direction ?? 'desc',
      perPage: defaults.perPage ?? listingPerPage,
    },
  ).slice(1);
}

function updateHash(group: Group, view: View, defaults: View): void {
  const hash = `#${getListingPath(group, view, defaults)}`;
  if (window.location.hash !== hash) {
    window.history.replaceState(null, '', hash);
  }
}

// The view fields a hash controls. `page` and `perPage` fall back to the
// preference-merged defaults like a freshly opened URL.
function viewStateFromHash(
  hash: SubscribersHashState,
  defaults: View,
  page: number,
): Pick<View, 'filters' | 'page' | 'perPage' | 'search' | 'sort'> {
  const field = [hash.orderby, defaults.sort?.field].find(isSortable);
  return {
    filters: requestFilterToViewFilters(hash.filter, {
      trackingEnabled: mailpoetTrackingEnabled,
    }),
    page,
    perPage: hash.perPage ?? defaults.perPage,
    search: hash.search ?? '',
    sort: {
      field: field ?? DEFAULT_SORT_FIELD,
      direction: hash.order ?? defaults.sort?.direction ?? 'desc',
    },
  };
}

// Stable text for the filters a view sends, so equal filters compare equal
// regardless of the order they were added in.
function filterKey(filters: Filter[] | undefined): string {
  const request = viewFiltersToRequestFilter(filters, {
    trackingEnabled: mailpoetTrackingEnabled,
  }) as Record<string, unknown>;
  return JSON.stringify(
    Object.keys(request)
      .sort()
      .map((key) => [key, request[key]]),
  );
}

function isFilterSame(
  current: Filter[] | undefined,
  next: Filter[] | undefined,
): boolean {
  return filterKey(current) === filterKey(next);
}

function isSameViewState(
  view: View,
  next: Pick<View, 'filters' | 'page' | 'perPage' | 'search' | 'sort'>,
): boolean {
  return (
    filterKey(view.filters) === filterKey(next.filters) &&
    (view.search ?? '') === (next.search ?? '') &&
    (view.page ?? 1) === (next.page ?? 1) &&
    view.perPage === next.perPage &&
    view.sort?.field === next.sort?.field &&
    view.sort?.direction === next.sort?.direction
  );
}

function getBaseSegmentElements(): FieldElement[] {
  return [
    {
      value: WITHOUT_LIST,
      label: __('Subscribers without a list', 'mailpoet'),
    },
    ...(window.mailpoet_segments ?? [])
      .filter((segment) => Number(segment.id) > 0)
      .map((segment) => ({ value: String(segment.id), label: segment.name })),
  ];
}

function getTagElements(): FieldElement[] {
  return (window.mailpoet_tags ?? []).map((tag) => ({
    value: String(tag.id),
    label: tag.name,
  }));
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

function isPickerAction(
  action: SubscriberBulkAction,
): action is keyof typeof PICKERS {
  return action in PICKERS;
}

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
    const kept = Number(result.kept ?? 0);
    const deletedMessage = sprintf(
      _n(
        '%s subscriber was permanently deleted.',
        '%s subscribers were permanently deleted.',
        count,
        'mailpoet',
      ),
      formatCount(count),
    );
    if (kept > 0) {
      const keptMessage = sprintf(
        _n(
          '%s subscriber was kept because it is linked to a WordPress user or WooCommerce customer.',
          '%s subscribers were kept because they are linked to a WordPress user or WooCommerce customer.',
          kept,
          'mailpoet',
        ),
        formatCount(kept),
      );
      MailPoet.Notice.success(`${deletedMessage} ${keptMessage}`);
    } else {
      MailPoet.Notice.success(deletedMessage);
    }
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
  caveat,
  selectionNotice,
  requireChoice,
  showAutomationOptIn,
  onApply,
  onClose,
}: {
  title: string;
  config: PickerConfig;
  caveat?: JSX.Element | null;
  selectionNotice?: JSX.Element | null;
  requireChoice?: boolean;
  showAutomationOptIn?: boolean;
  onApply: (value: number, triggerAutomations: boolean) => void;
  onClose: () => void;
}) {
  // The legacy `Selection` form widget wraps Select2 + jQuery. We treat it as
  // a controlled component by reading values out of its `onValueChange` callback
  // — no jQuery DOM lookups leak into this file.
  const [value, setValue] = useState<number>(() =>
    requireChoice ? 0 : getInitialPickerValue(config),
  );
  const [triggerAutomations, setTriggerAutomations] = useState(false);
  const fieldConfig = useMemo(
    () => ({
      id: config.fieldId,
      name: config.fieldId,
      endpoint: config.endpoint,
      filter: config.filter,
      forceSelect2: true,
      // A placeholder forces Select2 to render a blank leading option and
      // preselect nothing, so the Apply button's disabled-until-chosen state
      // reads as intentional rather than broken — and a select-all bulk action
      // can't be applied to the first list/tag by an accidental click.
      placeholder:
        config.kind === 'segment'
          ? __('Select a list...', 'mailpoet')
          : __('Select a tag...', 'mailpoet'),
    }),
    [config],
  );

  const handleApply = (): void => {
    if (!value) return;
    onApply(value, triggerAutomations);
  };

  return (
    <Modal
      title={title}
      onRequestClose={onClose}
      isDismissible
      contentClassName="mailpoet-subscribers-picker-modal"
    >
      {caveat}
      {selectionNotice}
      <Selection
        field={fieldConfig}
        width="100%"
        onValueChange={(event: { target: { value: string | number } }) => {
          const next = Number(event.target.value);
          setValue(Number.isFinite(next) ? next : 0);
        }}
      />
      {showAutomationOptIn && (
        <div data-automation-id="bulk-trigger-automations-checkbox">
          <CheckboxControl
            label={__('Trigger automations', 'mailpoet')}
            help={__(
              'Matching automations may send emails to these subscribers.',
              'mailpoet',
            )}
            checked={triggerAutomations}
            onChange={setTriggerAutomations}
          />
        </div>
      )}
      <div className="mailpoet-subscribers-bulk-confirm-actions">
        <Button onClick={handleApply} isDisabled={!value}>
          {__('Apply', 'mailpoet')}
        </Button>
      </div>
    </Modal>
  );
}

function EmptyContent({
  group,
  filters,
  search,
  onCheckTrash,
}: {
  group: Group;
  filters: Filter[] | undefined;
  search?: string;
  onCheckTrash: () => void;
}) {
  if (
    group !== 'trash' &&
    getExactStatus(filters) === 'bounced' &&
    (filters ?? []).length === 1 &&
    !search &&
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
  const [hashState] = useState<SubscribersHashState>(() =>
    parseSubscribersHash(window.location.hash),
  );
  const [group, setGroup] = useState<Group>(hashState.group);
  const [selection, setSelection] = useState<string[]>([]);
  const [selectAll, setSelectAll] = useState<boolean>(false);
  const [pendingAction, setPendingAction] = useState<PendingAction>(null);
  const triggerElementRef = useRef<HTMLElement | null>(null);
  // Synchronous guard that blocks re-entrancy while a confirmation-modal action
  // is in flight. The modal stays mounted until the REST call settles, so a
  // double-click on Apply / Resend emails / Unsubscribe must not fan out into
  // two bulk-action requests.
  const pendingActionInFlightRef = useRef(false);
  const [initialView] = useState<View>(() => {
    const preferredView = readPreferredView();
    return {
      ...preferredView,
      ...viewStateFromHash(
        hashState,
        preferredView,
        hashState.page ?? preferredView.page ?? 1,
      ),
    };
  });

  // Identifies the request the latest response answers, so group counts are
  // only trusted once they belong to the current group, filters and search.
  const loadedScopeRef = useRef('');
  const load = useCallback(
    async (params: ListingQueryParams, signal?: AbortSignal) => {
      const response = await getSubscribers({ ...params, group }, signal);
      loadedScopeRef.current = JSON.stringify([
        group,
        params.filter ?? {},
        params.search ?? '',
      ]);
      return response;
    },
    [group],
  );

  const {
    view,
    setView,
    onChangeView,
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
    extraParams: (currentView) => ({
      ...filterToExtraParams(
        viewFiltersToRequestFilter(currentView.filters, {
          trackingEnabled: mailpoetTrackingEnabled,
        }),
      ),
      orderby: currentView.sort ? toOrderby(currentView.sort.field) : undefined,
    }),
  });
  const viewRef = useRef(view);
  viewRef.current = view;

  const requestFilter = useMemo(
    () =>
      viewFiltersToRequestFilter(view.filters, {
        trackingEnabled: mailpoetTrackingEnabled,
      }),
    [view.filters],
  );
  const exactStatus = getExactStatus(view.filters);

  // Resolve the URL defaults at write time (not mount time) so preferences
  // persisted during the session are reflected.
  const getPreferredView = readPreferredView;

  useEffect(() => {
    updateHash(group, view, getPreferredView());
  }, [group, view, getPreferredView]);

  // List and tag options never shrink while filtering. Lists that exist only
  // in the Trash (not in the page bootstrap data) are added as the Trash
  // responses reveal them.
  const baseSegments = useMemo(getBaseSegmentElements, []);
  const tagElements = useMemo(getTagElements, []);
  const [trashedLists, setTrashedLists] = useState<FieldElement[]>([]);
  useEffect(() => {
    if (group !== 'trash') {
      return;
    }
    const known = new Set([
      ...baseSegments.map((element) => element.value),
      ...trashedLists.map((element) => element.value),
    ]);
    const added = (filters.segment ?? [])
      .filter((option) => Number(option.value) > 0)
      .filter((option) => !known.has(String(option.value)))
      .map((option) => ({
        value: String(option.value),
        label: option.label,
      }));
    if (added.length > 0) {
      setTrashedLists((current) => [...current, ...added]);
    }
  }, [baseSegments, filters.segment, group, trashedLists]);
  const segmentElements = useMemo(
    () => [...baseSegments, ...trashedLists],
    [baseSegments, trashedLists],
  );

  // Status labels carry the global per-status counts from the latest response.
  const [statusLabels, setStatusLabels] = useState<Record<string, string>>({});
  useEffect(() => {
    const next = (filters.status ?? []).reduce<Record<string, string>>(
      (labels, option) =>
        option.value
          ? { ...labels, [String(option.value)]: option.label }
          : labels,
      {},
    );
    if (Object.keys(next).length === 0) {
      return;
    }
    setStatusLabels((current) =>
      JSON.stringify(current) === JSON.stringify(next) ? current : next,
    );
  }, [filters.status]);

  // List and tag ids that came from the URL but are no longer selectable (for
  // example a deleted list) are dropped once a listing response has arrived, so
  // the full list shows. The check waits for a response because the Trash lists
  // are only known from it.
  const pendingPruneRef = useRef(true);
  const wasLoadingRef = useRef(false);
  useEffect(() => {
    const finishedLoading = wasLoadingRef.current && !isLoading;
    wasLoadingRef.current = isLoading;
    if (!finishedLoading || loadError || !pendingPruneRef.current) {
      return;
    }
    pendingPruneRef.current = false;
    const knownSegments = [
      ...segmentElements.map((element) => element.value),
      ...(group === 'trash'
        ? (filters.segment ?? []).map((option) => String(option.value))
        : []),
    ];
    const pruned = pruneUnknownIds(
      viewFiltersToRequestFilter(viewRef.current.filters, {
        trackingEnabled: mailpoetTrackingEnabled,
      }),
      { segments: knownSegments, tags: tagElements.map((tag) => tag.value) },
    );
    if (!pruned) {
      return;
    }
    setSelection([]);
    setSelectAll(false);
    setView((currentView) => ({
      ...currentView,
      filters: requestFilterToViewFilters(pruned, {
        trackingEnabled: mailpoetTrackingEnabled,
      }),
      page: 1,
    }));
  }, [
    filters.segment,
    group,
    isLoading,
    loadError,
    segmentElements,
    setView,
    tagElements,
  ]);

  // DataViews has no built-in URL state, so back/forward inside the listing
  // is wired manually. Browser navigation fires `hashchange`; programmatic
  // `replaceState` from `updateHash` does not, so this can't loop with our
  // own writes. The view only changes when the hash differs from it.
  useEffect(() => {
    const applyHash = (): void => {
      const next = parseSubscribersHash(window.location.hash);
      setGroup(next.group);
      setSelection([]);
      setSelectAll(false);
      clearLoadError();
      pendingPruneRef.current = true;
      // Fill hash segments the URL omits from the preference-merged defaults
      // (not the in-memory view) so back/forward resolves a URL exactly like
      // reopening it.
      const nextState = viewStateFromHash(
        next,
        getPreferredView(),
        next.page ?? 1,
      );
      setView((currentView) =>
        isSameViewState(currentView, nextState)
          ? currentView
          : {
              ...currentView,
              ...nextState,
              filters: isFilterSame(currentView.filters, nextState.filters)
                ? currentView.filters
                : nextState.filters,
            },
      );
    };
    window.addEventListener('hashchange', applyHash);
    return () => window.removeEventListener('hashchange', applyHash);
  }, [clearLoadError, getPreferredView, setView]);

  const backUrl = useMemo(
    () => getListingPath(group, view, getPreferredView()),
    [group, view, getPreferredView],
  );
  const backUrlRef = useRef(backUrl);
  backUrlRef.current = backUrl;
  const getBackUrl = useCallback((): string => backUrlRef.current, []);

  const groupCounts = useMemo(() => {
    const counts: Record<Group, number | null> = { all: null, trash: null };
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
      loadedScopeRef.current ===
        JSON.stringify([group, requestFilter, view.search ?? '']) &&
      !isLoading &&
      !loadError &&
      groupCounts.trash === 0 &&
      !view.search &&
      (view.filters ?? []).length === 0
    ) {
      setGroup('all');
      setSelection([]);
      setSelectAll(false);
      setView((currentView) => ({ ...currentView, page: 1 }));
    }
  }, [
    group,
    groupCounts.trash,
    isLoading,
    loadError,
    requestFilter,
    setView,
    view.filters,
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
    (action: SubscriberBulkAction, targets: Subscriber[]): void => {
      triggerElementRef.current = document.activeElement as HTMLElement | null;
      const selectedIds = targets.map((subscriber) => Number(subscriber.id));
      setPendingAction({
        action,
        selectAll,
        count: selectAll ? meta.count : targets.length,
        scope: {
          group,
          filter: requestFilter,
          search: view.search || '',
          selection: selectAll ? [] : selectedIds,
          ...(selectAll ? { selectAll: true } : {}),
        },
      });
    },
    [group, meta.count, requestFilter, selectAll, view.search],
  );

  const handleSelectionChange = useCallback((next: string[]): void => {
    // A manual checkbox change means the user is hand-picking rows, so the
    // "all matching" intent no longer applies. Programmatic page changes go
    // through handleViewChange (which clears selection itself), not this path.
    setSelectAll(false);
    setSelection(next);
  }, []);

  const handleViewChange = useCallback(
    (nextView: View): void => {
      // DataViews can't keep row checkboxes ticked across pages, so "select all
      // matching" would leave the banner claiming everything is selected while
      // the new page shows empty checkboxes. Drop the intent on any view change
      // (page, per-page, search, sort) so the banner never contradicts the rows.
      setSelectAll(false);
      setSelection([]);
      onChangeView(nextView);
    },
    [onChangeView],
  );
  const persistedViewChange = usePersistedDataViewsPreference(
    'subscribers',
    view,
    handleViewChange,
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
        setSelectAll(false);
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
      if (selectAll) {
        await runBulkAction(
          action,
          {
            group,
            filter: requestFilter,
            search: view.search || '',
            selection: [],
            selectAll: true,
          },
          extra,
        );
        return;
      }
      if (targets.length === 0) return;
      const selectedIds = targets.map((subscriber) => Number(subscriber.id));
      await runBulkAction(
        action,
        {
          group,
          filter: requestFilter,
          search: view.search || '',
          selection: selectedIds,
        },
        extra,
      );
    },
    [group, requestFilter, runBulkAction, selectAll, view.search],
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
        filter: requestFilter,
        search: view.search || '',
        selection: [],
        selectAll: true,
      },
      {},
    );
  }, [group, requestFilter, runBulkAction, view.search]);

  const handlePendingActionSubmit = useCallback(
    async (extra: Record<string, unknown> = {}): Promise<void> => {
      if (!pendingAction || pendingActionInFlightRef.current) return;
      pendingActionInFlightRef.current = true;
      const { action, scope } = pendingAction;
      try {
        await runBulkAction(action, scope, extra);
      } finally {
        pendingActionInFlightRef.current = false;
        setPendingAction(null);
        window.setTimeout(restoreTriggerFocus);
      }
    },
    [pendingAction, restoreTriggerFocus, runBulkAction],
  );

  const handleSendConfirmationEmail = useCallback(
    async (subscriber: Subscriber): Promise<void> => {
      try {
        await sendConfirmationEmail(Number(subscriber.id));
        MailPoet.Notice.success(
          __('1 confirmation email has been sent.', 'mailpoet'),
        );
      } catch (error) {
        // Route through `handleApiError` with the bulk action key so the
        // `mailpoet_subscribers_confirmation_disabled` response gets the
        // same actionable Settings-link notice as the bulk path.
        handleApiError(error as SubscriberApiError, 'resendConfirmationEmails');
      }
    },
    [handleApiError],
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
        supportsBulk: true,
        isEligible: () => group !== 'trash',
        callback: (targets) => {
          if (selectAll) {
            openPendingAction('trash', targets);
            return;
          }
          void handleBulkAction('trash', targets);
        },
      },
      {
        id: 'restore',
        label: __('Restore', 'mailpoet'),
        supportsBulk: true,
        isEligible: () => group === 'trash',
        callback: (targets) => {
          if (selectAll) {
            openPendingAction('restore', targets);
            return;
          }
          void handleBulkAction('restore', targets);
        },
      },
      {
        id: 'delete',
        label: __('Delete permanently', 'mailpoet'),
        supportsBulk: true,
        isDestructive: true,
        isEligible: (item) => group === 'trash' && isItemDeletable(item),
        callback: (targets) => {
          if (selectAll) {
            openPendingAction('delete', targets);
            return;
          }
          void handleBulkAction('delete', targets.filter(isItemDeletable));
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
          if (selectAll) {
            openPendingAction('removeFromAllLists', targets);
            return;
          }
          void handleBulkAction('removeFromAllLists', targets);
        },
      },
      {
        id: 'unsubscribe',
        label: __('Unsubscribe', 'mailpoet'),
        context: 'list',
        supportsBulk: true,
        isEligible: () => group !== 'trash' && exactStatus !== 'unsubscribed',
        callback: (targets) => openPendingAction('unsubscribe', targets),
      },
      {
        id: 'resendConfirmationEmails',
        label: __('Resend confirmation emails', 'mailpoet'),
        context: 'list',
        supportsBulk: true,
        isEligible: () =>
          group !== 'trash' &&
          exactStatus === 'unconfirmed' &&
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
    ],
    [
      exactStatus,
      getBackUrl,
      group,
      handleBulkAction,
      handleSendConfirmationEmail,
      navigate,
      openPendingAction,
      selectAll,
    ],
  );

  const handleGroupSelect = (nextGroup: Group): void => {
    if (nextGroup === group) return;
    setGroup(nextGroup);
    setSelection([]);
    setSelectAll(false);
    clearLoadError();
    setView((currentView) => ({ ...currentView, page: 1 }));
  };

  const handleCheckTrash = (): void => {
    setGroup('trash');
    setSelection([]);
    setSelectAll(false);
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

  const fields = useMemo(
    () =>
      getSubscriberFields(getBackUrl, group, {
        segments: segmentElements,
        tags: tagElements,
        statusLabels,
      }),
    [getBackUrl, group, segmentElements, tagElements, statusLabels],
  );

  const paginationInfo = useMemo(
    () => ({ totalItems: meta.count, totalPages: meta.pages }),
    [meta],
  );

  const isPageFullySelected =
    items.length > 0 && selection.length === items.length;
  const bannerMode = selectAllBannerState({
    pageItemCount: items.length,
    totalCount: meta.count,
    totalPages: meta.pages,
    isPageFullySelected,
    isSelectAll: selectAll,
  });

  const renderPendingActionModal = (): JSX.Element | null => {
    if (!pendingAction) return null;
    const { action, count, selectAll: pendingSelectAll } = pendingAction;
    const largeOpCaveat =
      pendingSelectAll && shouldWarnLargeOperation(count) ? (
        <Notice
          status="warning"
          isDismissible={false}
          className="mailpoet-subscribers-select-all-warning"
        >
          {__(
            'Large operations may take a while and could time out on very large lists!',
            'mailpoet',
          )}
        </Notice>
      ) : null;
    // Select-all permanent delete keeps WordPress users and WooCommerce
    // customers (SubscribersRepository::bulkDelete only deletes rows with no
    // linked WP/Woo account), so the count above can overstate what is removed.
    const deleteSkipCaveat =
      pendingSelectAll && action === 'delete' ? (
        <p className="mailpoet-subscribers-select-all-caveat">
          {__(
            'WordPress users and WooCommerce customers are kept and will not be permanently deleted.',
            'mailpoet',
          )}
        </p>
      ) : null;

    if (SELECT_ALL_GENERIC_CONFIRM_ACTIONS.includes(action)) {
      return (
        <Modal
          title={__('Confirm bulk action', 'mailpoet')}
          onRequestClose={closePendingAction}
          isDismissible
        >
          {largeOpCaveat}
          <p>
            {__(
              'This action will be applied to all %s subscribers matching the current view.',
              'mailpoet',
            ).replace('%s', formatCount(count))}
          </p>
          {deleteSkipCaveat}
          <div className="mailpoet-subscribers-bulk-confirm-actions">
            <Button
              onClick={() => handlePendingActionSubmit()}
              className={
                action === 'delete'
                  ? 'mailpoet-subscribers-confirm-destructive'
                  : undefined
              }
              automationId="bulk-select-all-confirm"
            >
              {__('Apply', 'mailpoet')}
            </Button>
          </div>
        </Modal>
      );
    }

    if (action === 'unsubscribe') {
      return (
        <Modal
          title={__('Unsubscribe', 'mailpoet')}
          onRequestClose={closePendingAction}
          isDismissible
        >
          {largeOpCaveat}
          <p>
            {__(
              'This action will unsubscribe %s subscribers from all lists. This action cannot be undone. Are you sure, you want to continue?',
              'mailpoet',
            ).replace('%s', formatCount(count))}
          </p>
          <div className="mailpoet-subscribers-bulk-confirm-actions">
            <Button
              onClick={() => handlePendingActionSubmit()}
              automationId="bulk-unsubscribe-confirm"
            >
              {__('Apply', 'mailpoet')}
            </Button>
          </div>
        </Modal>
      );
    }

    if (action === 'resendConfirmationEmails') {
      return (
        <BulkResendConfirmationEmailsModal
          submitModal={() => handlePendingActionSubmit()}
          closeModal={closePendingAction}
          count={count}
        />
      );
    }

    if (!isPickerAction(action)) {
      return null;
    }
    const config = PICKERS[action];
    const showAutomationOptIn = canTriggerAutomations(action);
    const selectAllScopeNotice = pendingSelectAll ? (
      <p>
        {__(
          'This action will be applied to all %s subscribers matching the current view.',
          'mailpoet',
        ).replace('%s', formatCount(count))}
      </p>
    ) : null;
    return (
      <PickerModal
        title={modalTitle(action)}
        config={config}
        caveat={largeOpCaveat}
        selectionNotice={selectAllScopeNotice}
        requireChoice={pendingSelectAll}
        showAutomationOptIn={showAutomationOptIn}
        onApply={(value, triggerAutomations) => {
          const target =
            config.kind === 'segment'
              ? { segment_id: value }
              : { tag_id: value };
          // `buildBulkActionPayload` drops `trigger_automations` for actions
          // that cannot trigger automations, so it is safe to always pass it.
          void handlePendingActionSubmit({
            ...target,
            trigger_automations: triggerAutomations,
          });
        }}
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

      <SelectAllBanner
        mode={bannerMode}
        totalCount={meta.count}
        pageItemCount={items.length}
        onSelectAll={() => setSelectAll(true)}
        onClear={() => {
          setSelectAll(false);
          setSelection([]);
        }}
      />

      <div
        className="mailpoet-dataviews mailpoet-subscribers-dataviews"
        data-automation-id="subscribers_listing"
      >
        <DataViews<Subscriber>
          data={items}
          fields={fields}
          view={view}
          onChangeView={persistedViewChange}
          actions={actions}
          paginationInfo={paginationInfo}
          defaultLayouts={{ table: {} }}
          getItemId={(item) => String(item.id)}
          selection={selection}
          onChangeSelection={handleSelectionChange}
          isLoading={isLoading}
          empty={
            <EmptyContent
              group={group}
              filters={view.filters}
              search={view.search}
              onCheckTrash={handleCheckTrash}
            />
          }
        >
          <div className="mailpoet-dataviews__toolbar mailpoet-subscribers-dataviews__toolbar">
            <DataViews.Search label={__('Search', 'mailpoet')} />
            <DataViews.FiltersToggle />
            {group === 'trash' && (
              <span className="mailpoet-listing-filters-empty-trash">
                <Button
                  variant="secondary"
                  onClick={() => {
                    void handleEmptyTrash();
                  }}
                  automationId="empty_trash"
                >
                  {__('Empty Trash', 'mailpoet')}
                </Button>
              </span>
            )}
            <div className="mailpoet-dataviews__toolbar-end">
              <DataViews.ViewConfig />
            </div>
          </div>
          <DataViews.Filters />
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
