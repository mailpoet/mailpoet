/**
 * External dependencies
 */
import React from 'react';
import { CheckboxControl } from '@woocommerce/blocks-checkout';
import { useState, useEffect } from '@wordpress/element';
import { getSetting } from '@woocommerce/settings';

const { newsletterEnabled, newsletterDefaultText } = getSetting('mailpoet_data');

const Block = ({ text, checkoutExtensionData }) => {
  const [checked, setChecked] = useState(false);
  const { setExtensionData } = checkoutExtensionData || {};
  useEffect(() => {
    if (newsletterEnabled && setExtensionData) {
      setExtensionData('mailpoet', 'optin', checked);
    }
  }, [checked, setExtensionData]);

  if (!newsletterEnabled) {
    return null;
  }

  return (
    <CheckboxControl
      label={text || newsletterDefaultText}
      checked={checked}
      onChange={setChecked}
    />
  );
};

export default Block;
