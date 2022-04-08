import { t } from 'common/functions';
import Button from 'common/button/button';
import Checkbox from 'common/form/checkbox/checkbox';
import { Label, Inputs } from 'settings/components';
import { useSetting, useAction } from 'settings/store/hooks';

export default function EmailCustomizer() {
  const [enabled, setEnabled] = useSetting(
    'woocommerce',
    'use_mailpoet_editor',
  );
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
        <Checkbox
          id="mailpoet_wc_customizer"
          automationId="mailpoet_wc_customizer"
          checked={enabled === '1'}
          onCheck={(isChecked) => setEnabled(isChecked ? '1' : '')}
        />
        <div className="mailpoet-settings-inputs-row">
          <Button
            type="button"
            onClick={openEditor}
            variant="secondary"
            dimension="small"
          >
            {t('openTemplateEditor')}
          </Button>
        </div>
      </Inputs>
    </>
  );
}
