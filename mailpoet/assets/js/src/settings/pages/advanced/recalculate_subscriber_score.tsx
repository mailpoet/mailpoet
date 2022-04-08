import { useContext } from 'react';

import Button from 'common/button/button';
import { t } from 'common/functions';
import { GlobalContext } from 'context';
import { useAction } from 'settings/store/hooks';
import { Label, Inputs } from 'settings/components';

export function RecalculateSubscriberScore(): JSX.Element {
  const recalculateSubscribersScore = useAction('recalculateSubscribersScore');
  // eslint-disable-next-line @typescript-eslint/no-explicit-any
  const { notices } = useContext<any>(GlobalContext);
  const onClick = async (): Promise<void> => {
    await recalculateSubscribersScore();
    notices.info(<p>{t('recalculateSubscribersScoreNotice')}</p>, {
      scroll: true,
    });
  };
  return (
    <>
      <Label
        title={t('recalculateSubscribersScoreTitle')}
        description={t('recalculateSubscribersScoreDescription')}
        htmlFor=""
      />
      <Inputs>
        <Button
          type="button"
          onClick={onClick}
          variant="secondary"
          dimension="small"
        >
          {t('recalculateSubscribersScoreNow')}
        </Button>
      </Inputs>
    </>
  );
}
