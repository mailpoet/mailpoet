import React from 'react';
import { t } from 'settings/utils';
import { useSetting } from 'settings/store/hooks';
import { Label, Inputs, PagesSelect } from 'settings/components';

export default function UnsubscribePage() {
  const [page, setPage] = useSetting('subscription', 'pages', 'unsubscribe');
  return (
    <>
      <Label
        title={t`unsubscribeTitle`}
        description={(
          <>
            {t`unsubscribeDescription1`}
            <br />
            {t`unsubscribeDescription2`}
          </>
        )}
        htmlFor="subscription-pages-unsubscribe"
      />
      <Inputs>
        <PagesSelect
          value={page}
          preview="unsubscribe"
          setValue={setPage}
          id="subscription-pages-unsubscribe"
          linkAutomationId="unsubscribe_page_preview_link"
        />
      </Inputs>
    </>
  );
}
