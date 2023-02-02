import { SelectControl } from '@wordpress/components';
import { ErrorBoundary } from 'common';
import { GlobalContext, useGlobalContextValue } from 'context/index.jsx';
import { CreateCouponTab } from './create_coupon_tab';
import { SettingsHeader } from './settings_header';

type Props = {
  availableDiscountTypes: SelectControl.Option[];
  getValueCallback: (name: string) => string | boolean;
  setValueCallback: (name: string, value: string | boolean) => void;
};

function Settings({
  availableDiscountTypes,
  getValueCallback,
  setValueCallback,
}: Props): JSX.Element {
  return (
    <ErrorBoundary>
      <GlobalContext.Provider value={useGlobalContextValue(window)}>
        <SettingsHeader
          source={getValueCallback('source')}
          onClick={(source) => setValueCallback('source', source as string)}
        />
        <CreateCouponTab
          availableDiscountTypes={availableDiscountTypes}
          getValueCallback={getValueCallback}
          setValueCallback={setValueCallback}
        />
      </GlobalContext.Provider>
    </ErrorBoundary>
  );
}

export { Settings };
