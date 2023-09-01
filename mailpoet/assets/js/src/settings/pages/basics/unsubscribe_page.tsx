import ReactStringReplace from 'react-string-replace';
import { t } from 'common/functions';
import { useSetting } from 'settings/store/hooks';
import { Inputs, Label, PageSelect } from 'settings/components';

export function UnsubscribePage() {
  const [unsubscribePage, setUnsubscribePage] = useSetting(
    'subscription',
    'pages',
    'unsubscribe',
  );
  const [unsubscribeConfirmationPage, setUnsubscribeConfirmationPage] =
    useSetting('subscription', 'pages', 'confirm_unsubscribe');

  return (
    <>
      <Label
        title={t('unsubscribeTitle')}
        description={
          <>
            {ReactStringReplace(
              t('unsubscribeDescription1'),
              '[mailpoet_page]',
              () => (
                <code key="mp">[mailpoet_page]</code>
              ),
            )}{' '}
            {ReactStringReplace(
              t('unsubscribeDescription2'),
              /\[link\](.*?)\[\/link\]/,
              (text) => (
                <a
                  className="mailpoet-link"
                  key={text}
                  href="https://kb.mailpoet.com/article/221-customize-your-unsubscribe-page"
                  rel="noopener noreferrer"
                  target="_blank"
                >
                  {text}
                </a>
              ),
            )}
          </>
        }
        htmlFor="subscription-pages-unsubscribe"
      />
      <Inputs>
        <div className="mailpoet-settings-inputs-row">
          {t('confirmationPageTitle')}:
        </div>
        <PageSelect
          value={unsubscribeConfirmationPage}
          preview="confirm_unsubscribe"
          setValue={setUnsubscribeConfirmationPage}
          id="subscription-pages-unsubscribe-confirmation"
          automationId="unsubscribe-confirmation-page-selection"
          linkAutomationId="unsubscribe_page_preview_link_confirmation"
        />
        <div className="mailpoet-settings-inputs-row">
          {t('successPageTitle')}:
        </div>
        <PageSelect
          value={unsubscribePage}
          preview="unsubscribe"
          setValue={setUnsubscribePage}
          id="subscription-pages-unsubscribe"
          automationId="unsubscribe-success-page-selection"
          linkAutomationId="unsubscribe_page_preview_link"
        />
      </Inputs>
    </>
  );
}
