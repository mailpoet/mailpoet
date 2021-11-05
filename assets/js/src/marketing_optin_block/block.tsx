/* eslint-disable react/react-in-jsx-scope */
/**
 * External dependencies
 */
import { CheckboxControl } from '@woocommerce/blocks-checkout';
import { useState, useEffect } from '@wordpress/element';
import { getSetting } from '@woocommerce/settings';

const { optinEnabled, defaultText, defaultStatus } = getSetting('mailpoet_data');

const Block = (
  {
    text,
    checkoutExtensionData,
  }: {
    text: string,
    checkoutExtensionData: {
      setExtensionData: (namespace: string, key: string, value: unknown) => void
    }
  }
): JSX.Element => {
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
    <CheckboxControl
      checked={checked}
      onChange={setChecked}
    >
      {/* eslint-disable-next-line react/no-danger */}
      <span dangerouslySetInnerHTML={{
        __html: text || defaultText,
      }}
      />
    </CheckboxControl>
  );
};

export default Block;
