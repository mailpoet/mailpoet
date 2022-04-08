import { t } from 'common/functions';
import { Label, Inputs } from 'settings/components';

export default function SPF() {
  return (
    <>
      <Label
        title={t('spfTitle')}
        description={t('spfDescription')}
        htmlFor=""
      />
      <Inputs>{t('spfSetup')}</Inputs>
    </>
  );
}
