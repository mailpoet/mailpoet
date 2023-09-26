import { t } from 'common/functions';
import { Label, Inputs } from 'settings/components';

export function GdprCompliant() {
  return (
    <>
      <Label
        title={t('gdprTitle')}
        description={t('gdprDescription')}
        htmlFor="gdpr-compliant"
      />
      <Inputs>
        <a
          className="mailpoet-link"
          href="https://kb.mailpoet.com/article/246-guide-to-conform-to-gdpr"
          title={t('readGuide')}
          target="_blank"
          rel="noopener noreferrer"
        >
          {t('readGuide')}
        </a>
      </Inputs>
    </>
  );
}
