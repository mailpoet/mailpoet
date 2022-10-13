import { t } from 'common/functions';
import { Radio } from 'common/form/radio/radio';
import { useSetting } from 'settings/store/hooks';
import { Label, Inputs } from 'settings/components';

export function ShareData() {
  const [enabled, setEnabled] = useSetting('analytics', 'enabled');

  return (
    <>
      <Label
        title={t('shareDataTitle')}
        description={
          <>
            {t('shareDataDescription')}{' '}
            <a
              className="mailpoet-link"
              href="https://kb.mailpoet.com/article/130-sharing-your-data-with-us"
              data-beacon-article="57ce0aaac6979108399a0454"
              rel="noopener noreferrer"
              target="_blank"
            >
              {t('readMore')}
            </a>
          </>
        }
        htmlFor=""
      />
      <Inputs>
        <Radio
          id="share-data-enabled"
          value="1"
          checked={enabled === '1'}
          onCheck={setEnabled}
          automationId="analytics-yes"
        />
        <label htmlFor="share-data-enabled">{t('yes')}</label>
        <span className="mailpoet-gap" />
        <Radio
          id="share-data-disabled"
          value=""
          checked={enabled === ''}
          onCheck={setEnabled}
          automationId="analytics-no"
        />
        <label htmlFor="share-data-disabled">{t('no')}</label>
      </Inputs>
    </>
  );
}
