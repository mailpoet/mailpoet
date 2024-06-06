import { ErrorBoundary } from 'common';
import { GlobalContext, useGlobalContextValue } from 'context';
import { useState } from 'react';
import { __ } from '@wordpress/i18n';
import { SelectControlProps } from '@wordpress/components/build-types/select-control/types';
import { privateApis as componentsPrivateApis } from '@wordpress/components';
import { useCallback } from '@wordpress/element';
import { ExistingCoupons } from './existing-coupons';
import { General } from './general';
import { GetValueCallback, SetValueCallback } from './types';
import { UsageRestriction } from './usage-restriction';
import { UsageLimits } from './usage-limits';

import { unlock } from '../../lock-unlock';

const { Tabs } = unlock(componentsPrivateApis);

enum SettingsTabs {
  allCoupons = 'allCoupons',
  createNew = 'createNew',
}

type Props = {
  availableDiscountTypes: SelectControlProps['options'];
  getValueCallback: GetValueCallback;
  setValueCallback: SetValueCallback;
  priceDecimalSeparator: string;
  codePlaceholder: string;
};

function Settings({
  availableDiscountTypes,
  getValueCallback,
  setValueCallback,
  priceDecimalSeparator,
  codePlaceholder,
}: Props): JSX.Element {
  const [activeTab, setActiveTab] = useState(getValueCallback('source'));

  const onSelectTab = useCallback(
    (value: string) => {
      setValueCallback('source', value);
      setActiveTab(value);

      // reset code placeholder and restoring existing coupon code
      if (value === SettingsTabs.createNew) {
        setValueCallback('code', codePlaceholder);
        setValueCallback('couponId', '');
        // Make visible the coupon overlay when creating a new coupon
        jQuery('.mailpoet_editor_coupon_overlay').css('visibility', 'visible');
      } else if (value === SettingsTabs.allCoupons) {
        // Hide the coupon overlay when a specific coupon is selected
        jQuery('.mailpoet_editor_coupon_overlay').css('visibility', 'hidden');
      }
    },
    [codePlaceholder, setValueCallback],
  );

  return (
    <ErrorBoundary>
      <GlobalContext.Provider value={useGlobalContextValue(window)}>
        <Tabs selectedTabId={activeTab} onSelect={onSelectTab}>
          <div className="components-panel__header interface-complementary-area-header edit-post-sidebar__panel-tabs">
            <Tabs.TabList>
              <Tabs.Tab tabId={SettingsTabs.allCoupons}>
                {__('All coupons', 'mailpoet')}
              </Tabs.Tab>
              <Tabs.Tab tabId={SettingsTabs.createNew}>
                {__('Create new', 'mailpoet')}
              </Tabs.Tab>
            </Tabs.TabList>
          </div>

          <Tabs.TabPanel tabId={SettingsTabs.allCoupons}>
            <ExistingCoupons
              availableDiscountTypes={availableDiscountTypes}
              getValueCallback={getValueCallback}
              setValueCallback={setValueCallback}
            />
          </Tabs.TabPanel>
          <Tabs.TabPanel tabId={SettingsTabs.createNew}>
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
          </Tabs.TabPanel>
        </Tabs>
      </GlobalContext.Provider>
    </ErrorBoundary>
  );
}

export { Settings };
