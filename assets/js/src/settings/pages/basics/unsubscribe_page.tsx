import React from 'react';
import ReactStringReplace from 'react-string-replace';
import { t } from 'common/functions';
import { useSetting } from 'settings/store/hooks';
import { Label, Inputs, PagesSelect } from 'settings/components';

export default function UnsubscribePage() {
  const [unsubscribePage, setUnsubscribePage] = useSetting('subscription', 'pages', 'unsubscribe');
  const [unsubscribeConfirmationPage, setUnsubscribeConfirmationPage] = useSetting('subscription', 'pages', 'confirm_unsubscribe');

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
            {' '}
            {
              ReactStringReplace(
                t('unsubscribeDescription2'),
                /\[link\](.*?)\[\/link\]/,
                (text) => (
                  <a
                    key={text}
                    href="https://kb.mailpoet.com/article/221-customize-your-unsubscribe-page"
                    rel="noopener noreferrer"
                    data-beacon-article="59dcea10042863379ddc8b4d"
                    target="_blank"
                  >
                    {text}
                  </a>
                )
              )
            }
          </>
        )}
        htmlFor="subscription-pages-unsubscribe"
      />
      <Inputs>
        {t('confirmationPageTitle')}
        :
        <br />
        <PagesSelect
          value={unsubscribeConfirmationPage}
          preview="confirm_unsubscribe"
          setValue={setUnsubscribeConfirmationPage}
          id="subscription-pages-unsubscribe-confirmation"
          linkAutomationId="unsubscribe_page_preview_link_confirmation"
        />
        <br />
        <br />
        {t('successPageTitle')}
        :
        <br />
        <PagesSelect
          value={unsubscribePage}
          preview="unsubscribe"
          setValue={setUnsubscribePage}
          id="subscription-pages-unsubscribe"
          linkAutomationId="unsubscribe_page_preview_link"
        />
      </Inputs>
    </>
  );
}
