import React from 'react';
import { Hooks } from 'wp-js-hooks';
import { getCurrentDates } from '@woocommerce/date';
import { useSelect } from '@wordpress/data';
import { __, sprintf } from '@wordpress/i18n';
import { Query, storeName } from '../../store';
import { PremiumModal } from '../../../../../../common/premium_modal';

type DummyFilterTypes = {
  onClick: () => void;
};
function DummyFilter({ onClick }: DummyFilterTypes): JSX.Element {
  const { query } = useSelect((select) => ({
    query: select(storeName).getCurrentQuery(),
  })) as {
    query: Query;
  };
  const defaultDateRange = 'period=month&compare=previous_year';

  const { primary: primaryDate, secondary: secondaryDate } = getCurrentDates(
    query,
    defaultDateRange,
  );

  return (
    <div className="woocommerce-filters-filter">
      <span className="woocommerce-filters-label">
        {__('Date range:', 'mailpoet')}
      </span>
      <div className="components-dropdown" tabIndex={-1}>
        <button
          type="button"
          aria-expanded="false"
          onClick={onClick}
          className="components-button woocommerce-dropdown-button is-multi-line"
        >
          <div className="woocommerce-dropdown-button__labels">
            <span>
              {primaryDate.label} ({primaryDate.range})
            </span>
            <span>
              {sprintf(
                // translators: %1$s is the date range label, %2$s is the date range. E.g. "vs. Previous period (Jan 1 - Feb 10 2023)"
                __('vs. %1$s (%2$s)', 'mailpoet'),
                secondaryDate.label,
                secondaryDate.range,
              )}
            </span>
          </div>
        </button>
      </div>
    </div>
  );
}

export function Filter(): JSX.Element {
  const [showUpsell, setShowUpsell] = React.useState(false);
  const component = Hooks.applyFilters(
    'mailpoet_analytics_filter',
    <DummyFilter
      onClick={() => {
        setShowUpsell(true);
      }}
    />,
  );
  return (
    <div className="mailpoet-analytics-filter-element woocommerce-layout">
      {component}
      {showUpsell && (
        <PremiumModal
          tracking={{
            utm_medium: 'upsell_modal',
            utm_campaign: 'automation_analytics_date_range_filter',
          }}
          onRequestClose={() => setShowUpsell(false)}
        >
          {__('Changing the date range is a premium feature.', 'mailpoet')}
        </PremiumModal>
      )}
    </div>
  );
}
