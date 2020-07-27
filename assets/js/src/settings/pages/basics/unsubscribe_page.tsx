import React from 'react';
import ReactStringReplace from 'react-string-replace';
import { t } from 'common/functions';
import { useSetting } from 'settings/store/hooks';
import { Label, Inputs, PagesSelect } from 'settings/components';

export default function UnsubscribePage() {
  const [page, setPage] = useSetting('subscription', 'pages', 'unsubscribe');
  return (
    <>
      <Label
        title={t('unsubscribeTitle')}
        description={(
          <>
            {
              ReactStringReplace(
                t('unsubscribeDescription1'),
                '[mailpoet_page]',
                () => <code key="mp">[mailpoet_page]</code>
              )
            }
            <br /><br />
            {
              ReactStringReplace(
                t('unsubscribeDescription2'),
                'mailpoet_unsubscribe_confirmation_page',
                () => <code key="mpcp">mailpoet_unsubscribe_confirmation_page</code>
              )
            }
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
