import { useEffect } from 'react';
import { onChange, Checkbox, Input, Select } from 'common';
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
  const [position, setPosition] = useSetting(
    'woocommerce',
    'optin_on_checkout',
    'position',
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
      {enabled === '1' && (
        <>
          <Label
            // translators: settings area: choose the email opt-in position on the checkout page (e-commerce websites)
            title={__('Checkbox opt-in position', 'mailpoet')}
            description={__(
              'Select where the marketing opt-in checkbox appears on the checkout page.',
              'mailpoet',
            )}
            htmlFor="mailpoet_wc_checkout_optin_position"
          />
          <Inputs>
            <Select
              id="mailpoet_wc_checkout_optin_position"
              value={position}
              onChange={onChange(setPosition)}
              automationId="mailpoet_wc_checkout_optin_position"
              isMinWidth
              dimension="small"
            >
              <option
                value="after_billing_info"
                data-automation-id="after_billing_info"
              >
                {
                  // translators: one of options for opt-in position at the checkout page
                  __('After billing info', 'mailpoet')
                }
              </option>
              <option
                value="after_order_notes"
                data-automation-id="after_order_notes"
              >
                {
                  // translators: one of options for opt-in position at the checkout page
                  __('After order notes', 'mailpoet')
                }
              </option>
              <option
                value="after_terms_and_conditions"
                data-automation-id="after_terms_and_conditions"
              >
                {
                  // translators: one of options for opt-in position at the checkout page
                  __('After terms and conditions', 'mailpoet')
                }
              </option>
              <option
                value="before_payment_methods"
                data-automation-id="before_payment_methods"
              >
                {
                  // translators: one of options for opt-in position at the checkout page
                  __('Before payment methods', 'mailpoet')
                }
              </option>
              <option
                value="before_terms_and_conditions"
                data-automation-id="before_terms_and_conditions"
              >
                {
                  // translators: one of options for opt-in position at the checkout page
                  __('Before terms and conditions', 'mailpoet')
                }
              </option>
            </Select>
          </Inputs>
        </>
      )}
    </>
  );
}
