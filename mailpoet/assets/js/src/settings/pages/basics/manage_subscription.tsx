import ReactStringReplace from 'react-string-replace';
import { t } from 'common/functions';
import { useSetting } from 'settings/store/hooks';
import { Inputs, Label, PageSelect } from 'settings/components';

export function ManageSubscription() {
  const [page, setPage] = useSetting('subscription', 'pages', 'manage');
  return (
    <>
      <Label
        title={t('manageSubTitle')}
        description={
          <>
            {t('manageSubDescription1')}{' '}
            {ReactStringReplace(
              t('manageSubDescription2'),
              /\[link\](.*?)\[\/link\]/,
              (text) => (
                <a
                  className="mailpoet-link"
                  key={text}
                  href="https://kb.mailpoet.com/article/222-customize-your-manage-subscription-page"
                  rel="noopener noreferrer"
                  data-beacon-article="59ddd0bb2c7d3a40f0ed5b57"
                  target="_blank"
                >
                  {text}
                </a>
              ),
            )}
          </>
        }
        htmlFor="subscription-manage-page"
      />
      <Inputs>
        <PageSelect
          value={page}
          preview="manage"
          setValue={setPage}
          id="subscription-manage-page"
          automationId="subscription-manage-page-selection"
          linkAutomationId="preview_manage_subscription_page_link"
        />

        <p>
          {ReactStringReplace(
            t('hideListFromManageSubPage'),
            /\[link\](.*?)\[\/link\]/,
            (text) => (
              <a
                className="mailpoet-link"
                key={text}
                href="/wp-admin/admin.php?page=mailpoet-segments#/lists"
                rel="noopener noreferrer"
                target="_blank"
              >
                {text}
              </a>
            ),
          )}
        </p>
      </Inputs>
    </>
  );
}
