import { SelectControl } from '@wordpress/components';
import Backbone from 'backbone';
import { ErrorBoundary } from 'common';
import { GlobalContext, useGlobalContextValue } from 'context/index.jsx';
import { useState } from 'react';
import { ExistingCoupons, Coupon } from './existingCoupons';
import { General } from './general';
import { SettingsHeader, SettingsTabs } from './settings_header';
import { UsageRestriction } from './usage_restriction';
import { UsageLimits } from './usage_limits';

type Props = {
  availableDiscountTypes: SelectControl.Option[];
  availableCoupons: Coupon[];
  getValueCallback: (name: string) => string | boolean | Backbone.Collection;
  setValueCallback: (
    name: string,
    value: string | boolean | Backbone.Collection,
  ) => void;
  priceDecimalSeparator: string;
  codePlaceholder: string;
};

function Settings({
  availableDiscountTypes,
  availableCoupons,
  getValueCallback,
  setValueCallback,
  priceDecimalSeparator,
  codePlaceholder,
}: Props): JSX.Element {
  const [activeTab, setActiveTab] = useState(getValueCallback('source'));
  return (
    <ErrorBoundary>
      <GlobalContext.Provider value={useGlobalContextValue(window)}>
        <SettingsHeader
          activeTab={activeTab}
          onClick={(value: string) => {
            setValueCallback('source', value);
            setActiveTab(value);

            // reset code placeholder and restoring existing coupon code
            if (value === SettingsTabs.createNew) {
              setValueCallback('code', codePlaceholder);
              setValueCallback('couponId', null);
            } else if (value === SettingsTabs.allCoupons) {
              const existingCoupon = availableCoupons.find(
                (coupon) => coupon.id === getValueCallback('couponId'),
              );
              if (existingCoupon) {
                setValueCallback('code', existingCoupon.text);
              }
            }
          }}
        />
        {activeTab === SettingsTabs.createNew ? (
          <>
            <General
              availableDiscountTypes={availableDiscountTypes}
              getValueCallback={getValueCallback}
              setValueCallback={setValueCallback}
            />
            <UsageRestriction
              getValueCallback={getValueCallback}
              setValueCallback={setValueCallback}
              priceDecimalSeparator={priceDecimalSeparator}
            />
            <UsageLimits
              getValueCallback={getValueCallback}
              setValueCallback={setValueCallback}
            />
          </>
        ) : (
          <ExistingCoupons
            availableDiscountTypes={availableDiscountTypes}
            availableCoupons={availableCoupons}
            getValueCallback={getValueCallback}
            setValueCallback={setValueCallback}
          />
        )}
      </GlobalContext.Provider>
    </ErrorBoundary>
  );
}

export { Settings };
