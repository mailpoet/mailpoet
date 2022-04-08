import { t } from 'common/functions';
import { useSetting } from 'settings/store/hooks';
import { Label, Inputs, PagesSelect } from 'settings/components';

export function ReEngagementPage(): JSX.Element {
  const [reEngagementPage, setReEngagementPage] = useSetting(
    'reEngagement',
    'page',
  );

  return (
    <>
      <Label
        title={t('reEngagementTitle')}
        description={t('reEngagementDescription')}
        htmlFor="re-engagement-page"
      />
      <Inputs>
        <PagesSelect
          value={reEngagementPage}
          preview="re_engagement"
          setValue={setReEngagementPage}
          id="re-engagement-page"
        />
      </Inputs>
    </>
  );
}
