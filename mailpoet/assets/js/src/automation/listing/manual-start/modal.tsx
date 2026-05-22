import { Button, Modal, Spinner } from '@wordpress/components';
import { __, _n, sprintf } from '@wordpress/i18n';
import { Icon, help } from '@wordpress/icons';
import {
  useCallback,
  useEffect,
  useMemo,
  useRef,
  useState,
  type RefObject,
  type ReactNode,
} from 'react';
import { ReactSelect } from 'common/form/react-select/react-select';
import { Toggle } from 'common/form/toggle/toggle';
import { Tooltip } from 'common/tooltip/tooltip';
import type { AutomationItem } from '../store/types';
import { previewManualStart, startManualStart } from './api';
import {
  canConfirmManualStart,
  getManualStartDefaultListOptions,
  getManualStartDynamicSegmentOptions,
  getManualStartErrorState,
  getSegmentIdNumber,
  isBlockingManualStartError,
  normalizeManualStartError,
  previewMatchesSelections,
} from './helpers';
import type {
  ManualStartErrorResponse,
  ManualStartPreview,
  ManualStartResult,
  ManualStartSegmentOption,
} from './types';

type QueuedContext = {
  listName: string;
  filterName: string | null;
};

type SegmentSelectOption = {
  value: string;
  label: string;
  count?: string;
};

type ManualStartModalProps = {
  automation: AutomationItem;
  onClose: () => void;
};

const skippedReasonLabels: Record<string, string> = {
  already_entered: __('Already entered this automation', 'mailpoet'),
  not_subscribed: __('Not subscribed', 'mailpoet'),
  unconfirmed: __('Unconfirmed', 'mailpoet'),
  unsubscribed: __('Unsubscribed', 'mailpoet'),
  bounced: __('Bounced', 'mailpoet'),
  deleted: __('Deleted', 'mailpoet'),
  not_in_list: __('No longer in the selected list', 'mailpoet'),
  dynamic_filter_mismatch: __(
    'Does not match the optional segment filter',
    'mailpoet',
  ),
  trigger_filter_mismatch: __('Automation trigger filters', 'mailpoet'),
  run_create_hook_rejected: __('Automation run hook rejected', 'mailpoet'),
  automation_inactive: __('Automation is inactive', 'mailpoet'),
  subscriber_missing: __('Subscriber no longer exists', 'mailpoet'),
  step_scheduling_failed: __('Step scheduling failed', 'mailpoet'),
};
const PREVIEW_DEBOUNCE_MS = 200;
const LARGE_AUDIENCE_SOFT_CAP = 50000;

function formatCount(count: number): string {
  return Number(count).toLocaleString();
}

function skippedReasonLabel(reason: string): string {
  return skippedReasonLabels[reason] ?? reason.replaceAll('_', ' ');
}

function findOptionName(
  options: ManualStartSegmentOption[],
  id: string | number | null | undefined,
): string | null {
  const numericId = getSegmentIdNumber(id);
  const option = options.find(
    (candidate) => getSegmentIdNumber(candidate.id) === numericId,
  );
  return option?.name ?? null;
}

function formatOptionCount(
  count: string | number | undefined,
): string | undefined {
  if (count === undefined) {
    return undefined;
  }
  const numericCount = Number(count);
  return Number.isFinite(numericCount)
    ? numericCount.toLocaleString()
    : String(count);
}

function toSelectOption(
  segment: ManualStartSegmentOption,
): SegmentSelectOption {
  return {
    value: segment.id,
    label: segment.name,
    count: formatOptionCount(segment.subscribers),
  };
}

function Alert({
  children,
  alertRef,
}: {
  children: ReactNode;
  alertRef: RefObject<HTMLDivElement>;
}): JSX.Element {
  return (
    <div
      className="notice notice-error mailpoet-automation-manual-start-alert"
      role="alert"
      tabIndex={-1}
      ref={alertRef}
    >
      {children}
    </div>
  );
}

function StatusNotice({ children }: { children: ReactNode }): JSX.Element {
  return (
    <div
      className="notice notice-success mailpoet-automation-manual-start-alert"
      role="status"
      aria-live="polite"
      tabIndex={-1}
    >
      {children}
    </div>
  );
}

function WarningNotice({ children }: { children: ReactNode }): JSX.Element {
  return (
    <div
      className="notice notice-warning mailpoet-automation-manual-start-alert"
      role="status"
      aria-live="polite"
      tabIndex={-1}
    >
      {children}
    </div>
  );
}

function PreviewCounts({
  preview,
}: {
  preview: ManualStartPreview;
}): JSX.Element {
  return (
    <div className="mailpoet-automation-manual-start-recipient-count">
      {__('Estimated recipients', 'mailpoet')}:
      <Tooltip place="right" id="manual-start-estimated-count-tooltip">
        {__('This count may change before subscribers are queued.', 'mailpoet')}
      </Tooltip>
      <span
        data-tip
        data-tooltip-id="manual-start-estimated-count-tooltip"
        className="estimated-recipient-count"
      >
        {formatCount(preview.eligible_count)}
      </span>
      {preview.selected_count !== preview.eligible_count && (
        <span className="mailpoet-automation-manual-start-recipient-count-detail">
          {sprintf(
            // translators: %s is the number of selected subscribers before eligibility checks.
            __('%s selected before skips', 'mailpoet'),
            formatCount(preview.selected_count),
          )}
        </span>
      )}
    </div>
  );
}

function Counts({
  preview,
  result,
}: {
  preview?: ManualStartPreview;
  result?: ManualStartResult;
}): JSX.Element {
  const selectedCount = preview?.selected_count ?? result?.selected_count ?? 0;
  const eligibleCount = preview?.eligible_count ?? result?.eligible_count ?? 0;

  return (
    <dl className="mailpoet-automation-manual-start-counts">
      <div>
        <dt>{__('Selected', 'mailpoet')}</dt>
        <dd>{formatCount(selectedCount)}</dd>
      </div>
      <div>
        <dt>{__('Eligible', 'mailpoet')}</dt>
        <dd>{formatCount(eligibleCount)}</dd>
      </div>
      {result && (
        <div>
          <dt>{__('Queued', 'mailpoet')}</dt>
          <dd>{formatCount(result.queued_count)}</dd>
        </div>
      )}
    </dl>
  );
}

function SkippedReasons({
  skippedByReason,
  deferredReasonKeys = [],
}: {
  skippedByReason: Record<string, number>;
  deferredReasonKeys?: string[];
}): JSX.Element | null {
  const skippedEntries = Object.entries(skippedByReason).filter(
    ([, count]) => Number(count) > 0,
  );

  if (skippedEntries.length === 0 && deferredReasonKeys.length === 0) {
    return null;
  }

  return (
    <div className="mailpoet-automation-manual-start-skips">
      {skippedEntries.length > 0 && (
        <>
          <h3>{__('Skipped subscribers', 'mailpoet')}</h3>
          <ul>
            {skippedEntries.map(([reason, count]) => (
              <li key={reason}>
                <span>{skippedReasonLabel(reason)}</span>
                <strong>{formatCount(count)}</strong>
              </li>
            ))}
          </ul>
        </>
      )}
      {deferredReasonKeys.length > 0 && (
        <p>
          {sprintf(
            // translators: %s is a comma-separated list of reasons.
            __(
              'More subscribers may be skipped while the queued task runs: %s.',
              'mailpoet',
            ),
            deferredReasonKeys.map(skippedReasonLabel).join(', '),
          )}
        </p>
      )}
    </div>
  );
}

function getErrorMessage(error: ManualStartErrorResponse): string {
  const state = getManualStartErrorState(error);
  if (error.message) {
    return error.message;
  }
  if (state === 'duplicate-in-progress') {
    return __(
      'Subscribers are already queued for this automation. Wait for the current manual start to finish before starting another one.',
      'mailpoet',
    );
  }
  if (state === 'stale-preview') {
    return __(
      'The audience changed since the last preview. Refresh the preview before queueing subscribers.',
      'mailpoet',
    );
  }
  if (state === 'zero-eligible') {
    return __(
      'No subscribers are eligible to start this automation.',
      'mailpoet',
    );
  }

  return __('An unknown error occurred.', 'mailpoet');
}

export function ManualStartModal({
  automation,
  onClose,
}: ManualStartModalProps): JSX.Element {
  const alertRef = useRef<HTMLDivElement>(null);
  const successRef = useRef<HTMLDivElement>(null);
  const [segmentId, setSegmentId] = useState('');
  const [filterSegmentId, setFilterSegmentId] = useState('');
  const [isFilterSegmentEnabled, setIsFilterSegmentEnabled] = useState(false);
  const [preview, setPreview] = useState<ManualStartPreview | null>(null);
  const [previewError, setPreviewError] =
    useState<ManualStartErrorResponse | null>(null);
  const [startError, setStartError] = useState<ManualStartErrorResponse | null>(
    null,
  );
  const [startResult, setStartResult] = useState<ManualStartResult | null>(
    null,
  );
  const [queuedContext, setQueuedContext] = useState<QueuedContext | null>(
    null,
  );
  const [isPreviewLoading, setIsPreviewLoading] = useState(false);
  const [isStarting, setIsStarting] = useState(false);
  const [refreshCount, setRefreshCount] = useState(0);

  const segments = useMemo(() => window.mailpoet_segments ?? [], []);
  const defaultLists = useMemo(
    () => getManualStartDefaultListOptions(segments, automation.manual_start),
    [automation.manual_start, segments],
  );
  const defaultListOptions = useMemo(
    () => defaultLists.map(toSelectOption),
    [defaultLists],
  );
  const dynamicSegments = useMemo(
    () => getManualStartDynamicSegmentOptions(segments),
    [segments],
  );
  const dynamicSegmentOptions = useMemo(
    () => dynamicSegments.map(toSelectOption),
    [dynamicSegments],
  );
  const selectedListName = useMemo(
    () => findOptionName(defaultLists, segmentId),
    [defaultLists, segmentId],
  );
  const selectedListOption = useMemo(
    () =>
      defaultListOptions.find((option) => option.value === segmentId) ?? null,
    [defaultListOptions, segmentId],
  );
  const activeFilterSegmentId = isFilterSegmentEnabled ? filterSegmentId : '';
  const filterSelectionRequired =
    isFilterSegmentEnabled && !activeFilterSegmentId;
  const selectedFilterName = useMemo(
    () =>
      activeFilterSegmentId
        ? findOptionName(dynamicSegments, activeFilterSegmentId)
        : null,
    [activeFilterSegmentId, dynamicSegments],
  );
  const selectedFilterOption = useMemo(
    () =>
      dynamicSegmentOptions.find(
        (option) => option.value === activeFilterSegmentId,
      ) ?? null,
    [activeFilterSegmentId, dynamicSegmentOptions],
  );
  const currentPreviewMatches = previewMatchesSelections(
    preview,
    segmentId,
    activeFilterSegmentId,
  );
  const startErrorState = getManualStartErrorState(startError);
  const previewErrorState = getManualStartErrorState(previewError);
  const confirmDisabled =
    isStarting ||
    isPreviewLoading ||
    startResult !== null ||
    filterSelectionRequired ||
    isBlockingManualStartError(startError) ||
    !canConfirmManualStart(preview, segmentId, activeFilterSegmentId);

  const focusHeading = useCallback((): void => {
    const heading = document.querySelector<HTMLElement>(
      '.mailpoet-automation-manual-start-modal .components-modal__header-heading',
    );
    if (heading) {
      heading.tabIndex = -1;
      heading.focus();
    }
  }, []);

  useEffect(() => {
    window.setTimeout(() => {
      if (defaultLists.length > 0) {
        document
          .getElementById('mailpoet-automation-manual-start-list')
          ?.focus();
        return;
      }
      focusHeading();
    });
  }, [defaultLists.length, focusHeading]);

  useEffect(() => {
    if (!segmentId || filterSelectionRequired || startResult) {
      setPreview(null);
      setPreviewError(null);
      setStartError(null);
      setIsPreviewLoading(false);
      return undefined;
    }

    const numericSegmentId = getSegmentIdNumber(segmentId);
    if (!numericSegmentId) {
      return undefined;
    }

    const numericFilterSegmentId = getSegmentIdNumber(activeFilterSegmentId);
    const controller = new AbortController();
    const timeout = window.setTimeout(() => {
      void previewManualStart(
        automation.id,
        {
          segment_id: numericSegmentId,
          filter_segment_id: numericFilterSegmentId,
        },
        controller.signal,
      )
        .then((nextPreview) => {
          if (!controller.signal.aborted) {
            setPreview(nextPreview);
            setPreviewError(null);
            if (
              previewMatchesSelections(
                nextPreview,
                segmentId,
                activeFilterSegmentId,
              )
            ) {
              setStartError(null);
            }
          }
        })
        .catch((error) => {
          if (!controller.signal.aborted) {
            setPreview(null);
            setPreviewError(normalizeManualStartError(error));
          }
        })
        .finally(() => {
          if (!controller.signal.aborted) {
            setIsPreviewLoading(false);
          }
        });
    }, PREVIEW_DEBOUNCE_MS);

    setIsPreviewLoading(true);
    setPreviewError(null);
    setStartError(null);

    return () => {
      window.clearTimeout(timeout);
      controller.abort();
    };
  }, [
    activeFilterSegmentId,
    automation.id,
    filterSelectionRequired,
    refreshCount,
    segmentId,
    startResult,
  ]);

  useEffect(() => {
    if (
      previewErrorState ||
      startErrorState ||
      (preview && currentPreviewMatches && preview.eligible_count === 0) ||
      preview?.duplicate_in_progress
    ) {
      window.setTimeout(() => alertRef.current?.focus());
    }
  }, [currentPreviewMatches, preview, previewErrorState, startErrorState]);

  useEffect(() => {
    if (startResult) {
      window.setTimeout(() => successRef.current?.focus());
    }
  }, [startResult]);

  const refreshPreview = useCallback((): void => {
    if (
      !segmentId ||
      filterSelectionRequired ||
      isPreviewLoading ||
      isStarting
    ) {
      return;
    }
    setStartError(null);
    setPreviewError(null);
    setIsPreviewLoading(true);
    setRefreshCount((count) => count + 1);
  }, [filterSelectionRequired, isPreviewLoading, isStarting, segmentId]);

  const handleListChange = useCallback((selectedOption: unknown): void => {
    const option = selectedOption as SegmentSelectOption | null;
    const nextSegmentId = option?.value ?? '';
    setSegmentId(nextSegmentId);
    if (!nextSegmentId) {
      setIsFilterSegmentEnabled(false);
      setFilterSegmentId('');
    }
  }, []);

  const handleFilterToggle = useCallback((checked: boolean): void => {
    setIsFilterSegmentEnabled(checked);
    if (!checked) {
      setFilterSegmentId('');
    }
  }, []);

  const handleFilterChange = useCallback((selectedOption: unknown): void => {
    const option = selectedOption as SegmentSelectOption | null;
    setFilterSegmentId(option?.value ?? '');
  }, []);

  const handleStart = useCallback(async (): Promise<void> => {
    if (confirmDisabled || !preview) {
      return;
    }

    const numericSegmentId = getSegmentIdNumber(segmentId);
    if (!numericSegmentId) {
      return;
    }

    setIsStarting(true);
    setStartError(null);
    try {
      const result = await startManualStart(automation.id, {
        segment_id: numericSegmentId,
        filter_segment_id: getSegmentIdNumber(activeFilterSegmentId),
        preview_signature: preview.preview_signature,
      });
      setQueuedContext({
        listName:
          selectedListName ??
          sprintf(
            // translators: %d is a list ID.
            __('List #%d', 'mailpoet'),
            result.segment_id,
          ),
        filterName: selectedFilterName,
      });
      setStartResult(result);
    } catch (error) {
      const normalizedError = normalizeManualStartError(error);
      if (
        normalizedError.code === 'manual_start_stale_preview' &&
        normalizedError.data.preview
      ) {
        setPreview(normalizedError.data.preview);
      } else {
        setPreview(null);
      }
      setStartError(normalizedError);
    } finally {
      setIsStarting(false);
    }
  }, [
    automation.id,
    activeFilterSegmentId,
    confirmDisabled,
    preview,
    segmentId,
    selectedFilterName,
    selectedListName,
  ]);

  const handleClose = useCallback((): void => {
    if (!isStarting) {
      onClose();
    }
  }, [isStarting, onClose]);

  const renderBlockingAlert = (): JSX.Element | null => {
    if (previewError) {
      return (
        <Alert alertRef={alertRef}>
          <p>{getErrorMessage(previewError)}</p>
          <Button
            variant="secondary"
            onClick={refreshPreview}
            disabled={isPreviewLoading || isStarting || !segmentId}
          >
            {__('Refresh preview', 'mailpoet')}
          </Button>
        </Alert>
      );
    }

    if (startError) {
      return (
        <Alert alertRef={alertRef}>
          <p>{getErrorMessage(startError)}</p>
          {startErrorState === 'stale-preview' && startError.data.preview && (
            <p>
              {sprintf(
                // translators: %s is the number of eligible subscribers.
                _n(
                  'The refreshed preview has %s eligible subscriber.',
                  'The refreshed preview has %s eligible subscribers.',
                  startError.data.preview.eligible_count,
                  'mailpoet',
                ),
                formatCount(startError.data.preview.eligible_count),
              )}
            </p>
          )}
          <Button
            variant="secondary"
            onClick={refreshPreview}
            disabled={isPreviewLoading || isStarting || !segmentId}
          >
            {__('Refresh preview', 'mailpoet')}
          </Button>
        </Alert>
      );
    }

    if (preview?.duplicate_in_progress) {
      return (
        <Alert alertRef={alertRef}>
          <p>
            {__(
              'Subscribers are already queued for this automation. Wait for the current manual start to finish before starting another one.',
              'mailpoet',
            )}
          </p>
          <Button
            variant="secondary"
            onClick={refreshPreview}
            disabled={isPreviewLoading || isStarting || !segmentId}
          >
            {__('Refresh preview', 'mailpoet')}
          </Button>
        </Alert>
      );
    }

    if (preview && currentPreviewMatches && preview.eligible_count === 0) {
      return (
        <Alert alertRef={alertRef}>
          <p>
            {__(
              'No subscribers are eligible to start this automation for the selected audience.',
              'mailpoet',
            )}
          </p>
        </Alert>
      );
    }

    return null;
  };

  const renderPreview = (): JSX.Element | null => {
    if (!segmentId) {
      return (
        <p className="mailpoet-automation-manual-start-muted" role="status">
          {__('Choose a list to preview eligible subscribers.', 'mailpoet')}
        </p>
      );
    }

    if (filterSelectionRequired) {
      return (
        <p className="mailpoet-automation-manual-start-muted" role="status">
          {__(
            'Choose a segment filter to preview eligible subscribers.',
            'mailpoet',
          )}
        </p>
      );
    }

    if (preview && currentPreviewMatches) {
      return (
        <div aria-live="polite">
          {preview.eligible_count >= LARGE_AUDIENCE_SOFT_CAP && (
            <WarningNotice>
              <p>
                {sprintf(
                  // translators: %s is the recommended maximum number of eligible subscribers before narrowing the audience.
                  __(
                    'Large audiences may take longer to queue. We recommend narrowing audiences above %s eligible subscribers with a segment filter.',
                    'mailpoet',
                  ),
                  formatCount(LARGE_AUDIENCE_SOFT_CAP),
                )}
              </p>
            </WarningNotice>
          )}
          <PreviewCounts preview={preview} />
          <SkippedReasons
            skippedByReason={preview.skipped_by_reason}
            deferredReasonKeys={preview.deferred_reason_keys}
          />
          {isPreviewLoading && (
            <div
              className="mailpoet-automation-manual-start-loading"
              role="status"
              aria-live="polite"
            >
              <Spinner />
              <span>{__('Refreshing preview...', 'mailpoet')}</span>
            </div>
          )}
        </div>
      );
    }

    if (isPreviewLoading) {
      return (
        <div
          className="mailpoet-automation-manual-start-loading"
          role="status"
          aria-live="polite"
        >
          <Spinner />
          <span>{__('Loading preview...', 'mailpoet')}</span>
        </div>
      );
    }

    return null;
  };

  if (startResult && queuedContext) {
    return (
      <Modal
        className="mailpoet-automation-manual-start-modal"
        title={__('Start automation for existing subscribers', 'mailpoet')}
        onRequestClose={handleClose}
      >
        <div ref={successRef} tabIndex={-1}>
          <StatusNotice>
            <p>
              {sprintf(
                // translators: %1$s is the number of queued subscribers, %2$d is the task ID.
                _n(
                  'MailPoet queued %1$s subscriber for this automation. Task ID: %2$d.',
                  'MailPoet queued %1$s subscribers for this automation. Task ID: %2$d.',
                  startResult.queued_count,
                  'mailpoet',
                ),
                formatCount(startResult.queued_count),
                startResult.task_id,
              )}
            </p>
          </StatusNotice>
          <p>
            {sprintf(
              // translators: %s is a list name.
              __('List: %s', 'mailpoet'),
              queuedContext.listName,
            )}
          </p>
          {queuedContext.filterName && (
            <p>
              {sprintf(
                // translators: %s is a dynamic segment name.
                __('Segment filter: %s', 'mailpoet'),
                queuedContext.filterName,
              )}
            </p>
          )}
          <Counts result={startResult} />
          <SkippedReasons skippedByReason={startResult.skipped_by_reason} />
          <p className="mailpoet-automation-manual-start-muted">
            {__(
              'Subscribers are queued asynchronously. Delays and conditions in the automation may mean emails are not sent immediately.',
              'mailpoet',
            )}
          </p>
          <div className="mailpoet-automation-manual-start-footer">
            <Button variant="primary" onClick={handleClose}>
              {__('Close', 'mailpoet')}
            </Button>
          </div>
        </div>
      </Modal>
    );
  }

  return (
    <Modal
      className="mailpoet-automation-manual-start-modal"
      title={__('Start automation for existing subscribers', 'mailpoet')}
      onRequestClose={handleClose}
    >
      <div aria-busy={isPreviewLoading || isStarting}>
        <p>
          {__(
            'Choose which existing subscribers to queue into this automation.',
            'mailpoet',
          )}
        </p>
        <p className="mailpoet-automation-manual-start-muted">
          {__(
            'Subscribers are queued asynchronously. Delays and conditions in the automation may mean emails are not sent immediately.',
            'mailpoet',
          )}
        </p>
        {defaultLists.length === 0 && (
          <Alert alertRef={alertRef}>
            <p>
              {__(
                'No available default lists match this automation trigger.',
                'mailpoet',
              )}
            </p>
          </Alert>
        )}
        <div className="mailpoet-automation-manual-start-fields">
          <div>
            <label
              className="mailpoet-automation-manual-start-field-heading"
              htmlFor="mailpoet-automation-manual-start-list"
            >
              {__('Send to', 'mailpoet')}
            </label>
            <p
              id="mailpoet-automation-manual-start-list-description"
              className="mailpoet-automation-manual-start-field-tip"
            >
              {__(
                'Subscribers who already entered this automation will be skipped.',
                'mailpoet',
              )}
            </p>
          </div>
          <ReactSelect
            inputId="mailpoet-automation-manual-start-list"
            value={selectedListOption}
            options={defaultListOptions}
            placeholder={__('Choose', 'mailpoet')}
            disabled={defaultLists.length === 0 || isStarting}
            isDisabled={defaultLists.length === 0 || isStarting}
            isClearable
            onChange={handleListChange}
            automationId="automation_manual_start_list"
            isFullWidth
            aria-describedby="mailpoet-automation-manual-start-list-description"
          />
          <div className="mailpoet-automation-manual-start-filter-toggle">
            <Toggle
              checked={isFilterSegmentEnabled}
              disabled={
                isStarting || !segmentId || dynamicSegments.length === 0
              }
              name="manualStartFilterSegmentEnabled"
              onCheck={handleFilterToggle}
              automationId="automation_manual_start_filter_toggle"
              aria-label={__('Filter by segment', 'mailpoet')}
            />
            <span className="mailpoet-form-toggle-text">
              {__('Filter by segment', 'mailpoet')}
              <Icon
                data-tip
                data-tooltip-id="manual-start-filter-segment-tooltip"
                className="filter-segment-tooltip"
                icon={help}
              />
            </span>
            <Tooltip place="right" id="manual-start-filter-segment-tooltip">
              <div>
                {__(
                  'Subscribers selected in Send to will only be queued if they also belong to this segment.',
                  'mailpoet',
                )}
              </div>
            </Tooltip>
          </div>
          {isFilterSegmentEnabled && (
            <ReactSelect
              inputId="mailpoet-automation-manual-start-filter"
              value={selectedFilterOption}
              options={dynamicSegmentOptions}
              placeholder={__('Choose', 'mailpoet')}
              disabled={isStarting || !segmentId}
              isDisabled={isStarting || !segmentId}
              isClearable
              onChange={handleFilterChange}
              automationId="automation_manual_start_filter"
              isFullWidth
              aria-label={__('Segment filter', 'mailpoet')}
            />
          )}
        </div>
        {renderBlockingAlert()}
        {renderPreview()}
        <div className="mailpoet-automation-manual-start-footer">
          <div className="mailpoet-automation-manual-start-actions">
            <Button
              variant="tertiary"
              onClick={handleClose}
              disabled={isStarting}
            >
              {__('Cancel', 'mailpoet')}
            </Button>
            <Button
              variant="primary"
              onClick={() => void handleStart()}
              disabled={confirmDisabled}
              isBusy={isStarting}
              data-automation-id="automation_manual_start_confirm"
            >
              {__('Queue subscribers', 'mailpoet')}
            </Button>
          </div>
        </div>
      </div>
    </Modal>
  );
}
