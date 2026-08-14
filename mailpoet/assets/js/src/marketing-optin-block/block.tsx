/* eslint-disable react/react-in-jsx-scope */
/**
 * External dependencies
 */
import { CheckboxControl } from '@woocommerce/blocks-checkout';
import { RawHTML, useEffect, useState } from '@wordpress/element';
import { getSetting } from '@woocommerce/settings';

const {
  optinEnabled,
  defaultText,
  trackingConsentEnabled,
  trackingConsentText,
} = getSetting('mailpoet_data');

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
  // Deliberately separate state from the marketing opt-in above it. Consent to
  // open and click tracking may never be bundled with the opt-in, and it always
  // starts unticked: a pre-ticked consent box is not valid consent under EU law
  // (CJEU Planet49).
  const [consentChecked, setConsentChecked] = useState(false);
  const { setExtensionData } = checkoutExtensionData || {};

  useEffect(() => {
    if (optinEnabled && setExtensionData) {
      setExtensionData('mailpoet', 'optin', checked);
    }
  }, [checked, setExtensionData]);

  useEffect(() => {
    if (optinEnabled && trackingConsentEnabled && setExtensionData) {
      setExtensionData('mailpoet', 'tracking_consent', consentChecked);
    }
  }, [consentChecked, setExtensionData]);

  if (!optinEnabled) {
    return null;
  }

  return (
    <>
      <CheckboxControl
        checked={checked}
        onChange={setChecked}
        label={<RawHTML>{text || defaultText}</RawHTML>}
      />
      {trackingConsentEnabled ? (
        <CheckboxControl
          checked={consentChecked}
          onChange={setConsentChecked}
          label={trackingConsentText}
        />
      ) : null}
    </>
  );
}
