import { t } from 'common/functions';
import { useSetting } from 'settings/store/hooks';
import { Label, Inputs } from 'settings/components';
import Checkbox from 'common/form/checkbox/checkbox';

export default function SubscribeOldCustomers() {
  const [enabled, setEnabled] = useSetting(
    'mailpoet_subscribe_old_woocommerce_customers',
    'enabled',
  );

  return (
    <>
      <Label
        title={t('subscribeOldWCTitle')}
        description={t('subscribeOldWCDescription')}
        htmlFor="mailpoet_subscribe_old_wc_customers"
      />
      <Inputs>
        <Checkbox
          id="mailpoet_subscribe_old_wc_customers"
          automationId="mailpoet_subscribe_old_wc_customers"
          checked={enabled === '1'}
          onCheck={(isChecked) => setEnabled(isChecked ? '1' : '')}
        />
      </Inputs>
    </>
  );
}
