import { __ } from '@wordpress/i18n';
import { SelectAllBannerMode } from './select-all';

type Props = {
  mode: SelectAllBannerMode;
  totalCount: number;
  pageItemCount: number;
  onSelectAll: () => void;
  onClear: () => void;
};

function formatCount(value: number): string {
  return value.toLocaleString();
}

export function SelectAllBanner({
  mode,
  totalCount,
  pageItemCount,
  onSelectAll,
  onClear,
}: Props): JSX.Element | null {
  if (mode === 'hidden') {
    return null;
  }

  if (mode === 'active') {
    return (
      <div
        className="mailpoet-subscribers-select-all-banner"
        data-automation-id="subscribers_select_all_banner"
      >
        <span>
          {__(
            'All %s subscribers matching this view are selected.',
            'mailpoet',
          ).replace('%s', formatCount(totalCount))}
        </span>{' '}
        <button
          type="button"
          className="button button-link"
          data-automation-id="subscribers_select_all_clear"
          onClick={onClear}
        >
          {__('Clear selection', 'mailpoet')}
        </button>
      </div>
    );
  }

  return (
    <div
      className="mailpoet-subscribers-select-all-banner"
      data-automation-id="subscribers_select_all_banner"
    >
      <span>
        {__(
          'All %s subscribers on this page are selected.',
          'mailpoet',
        ).replace('%s', formatCount(pageItemCount))}
      </span>{' '}
      <button
        type="button"
        className="button button-link"
        data-automation-id="subscribers_select_all_offer"
        onClick={onSelectAll}
      >
        {__('Select all %s subscribers matching this view', 'mailpoet').replace(
          '%s',
          formatCount(totalCount),
        )}
      </button>
    </div>
  );
}

SelectAllBanner.displayName = 'SelectAllBanner';
