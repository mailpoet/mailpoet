import { t } from 'common/functions';
import { Label, Inputs, PageSelect } from 'settings/components';
import { useSetting } from 'settings/store/hooks';

export function ConfirmationPage() {
  const [enabled] = useSetting('signup_confirmation', 'enabled');
  const [page, setPage] = useSetting('subscription', 'pages', 'confirmation');

  if (!enabled) return null;
  return (
    <>
      <Label
        title={t('confirmationPage')}
        description={t('confirmationPageDescription')}
        htmlFor="subscription-pages-confirmation"
      />
      <Inputs>
        <PageSelect
          value={page}
          preview="confirm"
          setValue={setPage}
          id="subscription-pages-confirmation"
          automationId="page_selection"
          linkAutomationId="preview_page_link"
        />
      </Inputs>
    </>
  );
}
