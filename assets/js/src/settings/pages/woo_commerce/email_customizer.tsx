import React from 'react';
import { t, onToggle } from 'common/functions';
import { Label, Inputs } from 'settings/components';
import { useSetting, useAction } from 'settings/store/hooks';

export default function EmailCustomizer() {
  const [enabled, setEnabled] = useSetting('woocommerce', 'use_mailpoet_editor');
  const [newsletterId] = useSetting('woocommerce', 'transactional_email_id');
  const openWoocommerceCustomizer = useAction('openWoocommerceCustomizer');
  const openEditor = () => openWoocommerceCustomizer(newsletterId);
  return (
    <>
      <Label
        title={t('wcCustomizerTitle')}
        description={t('wcCustomizerDescription')}
        htmlFor="mailpoet_wc_customizer"
      />
      <Inputs>
        <input
          type="checkbox"
          id="mailpoet_wc_customizer"
          data-automation-id="mailpoet_wc_customizer"
          checked={enabled === '1'}
          onChange={onToggle(setEnabled, '')}
        />
        <br />
        <button type="button" className="button-secondary mailpoet_woocommerce_editor_button" onClick={openEditor}>
          {t('openTemplateEditor')}
        </button>
      </Inputs>
    </>
  );
}
