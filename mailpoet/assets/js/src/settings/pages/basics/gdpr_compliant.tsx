import React from 'react';
import { t } from 'common/functions';
import { Label, Inputs } from 'settings/components';

export default function GdprCompliant() {
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
          data-beacon-article="5a9e8cdd04286374f7089a8c"
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
