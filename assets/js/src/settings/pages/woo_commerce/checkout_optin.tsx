import React from 'react';
import { t, onToggle, onChange } from 'common/functions';
import { Label, Inputs, SegmentsSelect } from 'settings/components';
import { useSetting, useAction } from 'settings/store/hooks';

export default function CheckoutOptin() {
  const [enabled, setEnabled] = useSetting('woocommerce', 'optin_on_checkout', 'enabled');
  const [segments, setSegments] = useSetting('woocommerce', 'optin_on_checkout', 'segments');
  const [message, setMessage] = useSetting('woocommerce', 'optin_on_checkout', 'message');
  const setErrorFlag = useAction('setErrorFlag');
  const emptyMessage = message.trim() === '';
  React.useEffect(() => {
    setErrorFlag(emptyMessage);
  }, [emptyMessage, setErrorFlag]);

  return (
    <>
      <Label
        title={t('wcOptinTitle')}
        description={t('wcOptinDescription')}
        htmlFor="mailpoet_wc_checkout_optin"
      />
      <Inputs>
        <input
          type="checkbox"
          id="mailpoet_wc_checkout_optin"
          data-automation-id="mailpoet_wc_checkout_optin"
          checked={enabled === '1'}
          onChange={onToggle(setEnabled, '')}
        />
        {enabled === '1' && (
          <>
            <br />
            <label htmlFor="mailpoet_wc_checkout_optin_segments">
              {t('wcOptinSegmentsTitle')}
              <br />
              <span>{t('leaveEmptyToSubscribeToWCCustomers')}</span>
            </label>
            <br />
            <SegmentsSelect
              id="mailpoet_wc_checkout_optin_segments"
              value={segments}
              setValue={setSegments}
              placeholder={t('wcOptinSegmentsPlaceholder')}
            />
          </>
        )}
      </Inputs>
      {enabled === '1' && (
        <>
          <Label
            title={t('wcOptinMsgTitle')}
            description={t('wcOptinMsgDescription')}
            htmlFor="mailpoet_wc_checkout_optin_message"
          />
          <Inputs>
            <input
              type="text"
              className="regular-text"
              id="mailpoet_wc_checkout_optin_message"
              data-automation-id="mailpoet_wc_checkout_optin_message"
              value={message}
              onChange={onChange(setMessage)}
              placeholder={t('wcOptinMsgPlaceholder')}
            />
            <br />
            {emptyMessage && (
              <span className="mailpoet_error_item mailpoet_error">
                {t('wcOptinMsgCannotBeEmpty')}
              </span>
            )}
          </Inputs>
        </>
      )}
    </>
  );
}
