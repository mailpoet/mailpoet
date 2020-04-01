import React from 'react';
import { t, onToggle, onChange } from 'common/functions';
import { Label, Inputs } from 'settings/components';
import { useSetting, useAction } from 'settings/store/hooks';

export default function CheckoutOptin() {
  const [enabled, setEnabled] = useSetting('woocommerce', 'optin_on_checkout', 'enabled');
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
