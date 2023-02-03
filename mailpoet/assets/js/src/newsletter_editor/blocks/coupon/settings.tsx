import { SelectControl } from '@wordpress/components';
import { ErrorBoundary } from 'common';
import { GlobalContext, useGlobalContextValue } from 'context/index.jsx';
import { useState } from 'react';
import { General } from './general';
import { SettingsHeader } from './settings_header';
import { UsageRestriction } from './usage_restriction';

type Props = {
  availableDiscountTypes: SelectControl.Option[];
  getValueCallback: (name: string) => string | boolean;
  setValueCallback: (name: string, value: string | boolean) => void;
  priceDecimalSeparator: string;
};

function Settings({
  availableDiscountTypes,
  getValueCallback,
  setValueCallback,
  priceDecimalSeparator,
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
          }}
        />
        {activeTab === 'createNew' ? (
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
          </>
        ) : null}
      </GlobalContext.Provider>
    </ErrorBoundary>
  );
}

export { Settings };
