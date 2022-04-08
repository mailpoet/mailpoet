/* eslint-disable react/react-in-jsx-scope */
/**
 * External dependencies
 */
import { CheckboxControl } from '@woocommerce/blocks-checkout';
import { useState, useEffect, RawHTML } from '@wordpress/element';
import { getSetting } from '@woocommerce/settings';

const { optinEnabled, defaultText, defaultStatus } =
  getSetting('mailpoet_data');

function Block({
  text,
  checkoutExtensionData,
}: {
  text: string;
  checkoutExtensionData: {
    setExtensionData: (namespace: string, key: string, value: unknown) => void;
  };
}): JSX.Element {
  const [checked, setChecked] = useState(defaultStatus);
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

export default Block;
