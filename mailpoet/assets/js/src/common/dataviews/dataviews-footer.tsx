import { useEffect, useState } from 'react';
import {
  Button,
  __experimentalNumberControl as NumberControl,
} from '@wordpress/components';
import { DataViews, type View } from '@wordpress/dataviews';
import { __, sprintf, isRTL } from '@wordpress/i18n';
import { next as nextIcon, previous as previousIcon } from '@wordpress/icons';

/**
 * A drop-in replacement for `<DataViews.Footer />`.
 *
 * It is intentionally identical to the stock footer — same `dataviews-footer`
 * markup, and the native bulk-actions bar is reused verbatim via the public
 * `DataViews.BulkActionToolbar` compound component — with a single change: the
 * page control is a numeric `<input>` (min/max/current) instead of the native
 * `<SelectControl>`.
 *
 * The native pagination renders one `<option>` per page-of-results, so on a
 * listing with millions of rows (e.g. 5M subscribers / 20 per page = 250k
 * pages) it builds and reconciles 250k `<option>` DOM nodes on *every* re-render
 * — and a group/status switch triggers several re-renders back to back. That is
 * O(totalPages) work per switch and stalls the browser. A numeric input is O(1).
 */
type PaginationInfo = {
  totalItems: number;
  totalPages: number;
};

type DataViewsFooterProps = {
  view: View;
  onChangeView: (view: View) => void;
  paginationInfo: PaginationInfo;
  isLoading?: boolean;
  hasData?: boolean;
};

function PageNumberPagination({
  view,
  onChangeView,
  totalPages,
}: {
  view: View;
  onChangeView: (view: View) => void;
  totalPages: number;
}) {
  const currentPage = view.page ?? 1;
  // Local draft so typing a page number doesn't refetch on every keystroke;
  // the change is committed on blur/Enter (the native select commits once too).
  const [draft, setDraft] = useState<string>(String(currentPage));

  useEffect(() => {
    setDraft(String(currentPage));
  }, [currentPage]);

  const goToPage = (page: number): void => {
    onChangeView({ ...view, page });
  };

  const commit = (raw: string | undefined): void => {
    const parsed = Number.parseInt(raw ?? '', 10);
    const clamped = Number.isNaN(parsed)
      ? currentPage
      : Math.min(Math.max(parsed, 1), totalPages);
    setDraft(String(clamped));
    if (clamped !== currentPage) {
      goToPage(clamped);
    }
  };

  return (
    <div
      className="dataviews-pagination"
      style={{
        display: 'flex',
        flexDirection: 'row',
        justifyContent: 'flex-end',
        alignItems: 'center',
        gap: '24px',
      }}
    >
      <div
        className="dataviews-pagination__page-select"
        style={{
          display: 'flex',
          flexDirection: 'row',
          alignItems: 'center',
          gap: '8px',
        }}
      >
        <span aria-hidden="true">{__('Page', 'mailpoet')}</span>
        <NumberControl
          label={__('Current page', 'mailpoet')}
          hideLabelFromVision
          size="small"
          spinControls="none"
          isDragEnabled={false}
          min={1}
          max={totalPages}
          value={draft}
          onChange={(value) => setDraft(value ?? '')}
          onBlur={(event) => commit(event.currentTarget.value)}
          onKeyDown={(event) => {
            if (event.key === 'Enter') {
              event.preventDefault();
              commit(event.currentTarget.value);
              event.currentTarget.blur();
            }
          }}
          // Grow with the page-count magnitude so the value is never clipped.
          __unstableInputWidth={`${Math.max(
            String(totalPages).length + 1,
            3,
          )}em`}
        />
        <span aria-hidden="true">
          {sprintf(
            // translators: %d: total number of pages.
            __('of %d', 'mailpoet'),
            totalPages,
          )}
        </span>
      </div>
      <div
        style={{
          display: 'flex',
          flexDirection: 'row',
          alignItems: 'center',
          gap: '4px',
        }}
      >
        <Button
          size="compact"
          icon={isRTL() ? nextIcon : previousIcon}
          label={__('Previous page', 'mailpoet')}
          onClick={() => goToPage(currentPage - 1)}
          disabled={currentPage === 1}
          accessibleWhenDisabled
          showTooltip
          tooltipPosition="top"
        />
        <Button
          size="compact"
          icon={isRTL() ? previousIcon : nextIcon}
          label={__('Next page', 'mailpoet')}
          onClick={() => goToPage(currentPage + 1)}
          disabled={currentPage >= totalPages}
          accessibleWhenDisabled
          showTooltip
          tooltipPosition="top"
        />
      </div>
    </div>
  );
}

export function DataViewsFooter({
  view,
  onChangeView,
  paginationInfo,
  isLoading,
  hasData,
}: DataViewsFooterProps): JSX.Element | null {
  const { totalItems, totalPages } = paginationInfo;
  const isRefreshing = Boolean(isLoading) && Boolean(hasData);

  // Mirror the native footer's null-guard: nothing to show when there are no
  // items (and we're not mid-refresh over an existing dataset).
  if (!totalItems && !isRefreshing) {
    return null;
  }

  return (
    <div className="dataviews-footer">
      <div
        className={`dataviews-footer__content${
          isRefreshing ? ' is-refreshing' : ''
        }`}
        style={{
          display: 'flex',
          flexDirection: 'row',
          justifyContent: 'flex-end',
          alignItems: 'center',
          gap: '8px',
        }}
      >
        {/* Native bulk-actions bar, reused unchanged. */}
        <DataViews.BulkActionToolbar />
        {totalPages > 1 && (
          <PageNumberPagination
            view={view}
            onChangeView={onChangeView}
            totalPages={totalPages}
          />
        )}
      </div>
    </div>
  );
}

DataViewsFooter.displayName = 'DataViewsFooter';
