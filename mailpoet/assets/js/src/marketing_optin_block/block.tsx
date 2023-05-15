/* eslint-disable react/react-in-jsx-scope */
/**
 * External dependencies
 */
import { CheckboxControl } from '@woocommerce/blocks-checkout';
import { RawHTML, useEffect, useState } from '@wordpress/element';
import { getSetting } from '@woocommerce/settings';

const { optinEnabled, defaultText } = getSetting('mailpoet_data');

export function FrontendBlock({
  text,
  checkoutExtensionData,
}: {
  text: string;
  checkoutExtensionData: {
    setExtensionData: (namespace: string, key: string, value: unknown) => void;
  };
}): JSX.Element {
  const [checked, setChecked] = useState(false);
  const { setExtensionData } = checkoutExtensionData || {};
  useEffect(() => {
    if (optinEnabled && setExtensionData) {
      setExtensionData('mailpoet', 'optin', checked);
    }
  }, [checked, setExtensionData]);

  if (!optinEnabled) {
    return null;
  }

  return (
    <CheckboxControl checked={checked} onChange={setChecked}>
      <RawHTML>{text || defaultText}</RawHTML>
    </CheckboxControl>
  );
}
