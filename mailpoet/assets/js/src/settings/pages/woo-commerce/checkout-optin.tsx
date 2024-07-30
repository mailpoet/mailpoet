import { useEffect } from 'react';
import { onChange } from 'common/functions';
import { Checkbox } from 'common/form/checkbox/checkbox';
import { Input } from 'common/form/input/input';
import { Label, Inputs, SegmentsSelect } from 'settings/components';
import { useSetting, useAction } from 'settings/store/hooks';
import { __ } from '@wordpress/i18n';

export function CheckoutOptin() {
  const [enabled, setEnabled] = useSetting(
    'woocommerce',
    'optin_on_checkout',
    'enabled',
  );
  const [segments, setSegments] = useSetting(
    'woocommerce',
    'optin_on_checkout',
    'segments',
  );
  const [message, setMessage] = useSetting(
    'woocommerce',
    'optin_on_checkout',
    'message',
  );
  const setErrorFlag = useAction('setErrorFlag');
  const emptyMessage = message.trim() === '';
  useEffect(() => {
    void setErrorFlag(emptyMessage);
  }, [emptyMessage, setErrorFlag]);

  return (
    <>
      <Label
        // translators: settings area: add an email opt-in checkbox on the checkout page (e-commerce websites)
        title={__('Opt-in on checkout', 'mailpoet')}
        description={__(
          'Customers can subscribe to the "WooCommerce Customers" list and optionally other lists via a checkbox on the checkout page.',
          'mailpoet',
        )}
        htmlFor="mailpoet_wc_checkout_optin"
      />
      <Inputs>
        <Checkbox
          id="mailpoet_wc_checkout_optin"
          automationId="mailpoet_wc_checkout_optin"
          checked={enabled === '1'}
          onCheck={(isChecked) => setEnabled(isChecked ? '1' : '')}
        />
        {enabled === '1' && (
          <>
            <br />
            <label htmlFor="mailpoet_wc_checkout_optin_segments">
              {__('Lists to also subscribe customers to:', 'mailpoet')}
              <br />
              <span>
                {__(
                  'Leave empty to subscribe only to "WooCommerce Customers" list',
                )}
              </span>
            </label>
            <br />
            <SegmentsSelect
              id="mailpoet_wc_checkout_optin_segments"
              value={segments}
              setValue={setSegments}
              placeholder={__('Select lists...', 'mailpoet')}
            />
          </>
        )}
      </Inputs>
      {enabled === '1' && (
        <>
          <Label
            // translators: settings area: set the email opt-in message on the checkout page (e-commerce websites)
            title={__('Checkbox opt-in message', 'mailpoet')}
            description={__(
              'This is the checkbox message your customers will see on your WooCommerce checkout page to subscribe to the "WooCommerce Customers" list and lists selected in "Opt-in on checkout" setting.',
            )}
            htmlFor="mailpoet_wc_checkout_optin_message"
          />
          <Inputs>
            <Input
              dimension="small"
              type="text"
              id="mailpoet_wc_checkout_optin_message"
              data-automation-id="mailpoet_wc_checkout_optin_message"
              value={message}
              onChange={onChange(setMessage)}
              // translators: placeholder text for the WooCommerce checkout opt-in message
              placeholder={__('Checkbox opt-in message', 'mailpoet')}
            />
            <br />
            {emptyMessage && (
              <span className="mailpoet_error_item mailpoet_error">
                {__('The checkbox opt-in message cannot be empty.', 'mailpoet')}
              </span>
            )}
          </Inputs>
        </>
      )}
    </>
  );
}
