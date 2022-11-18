import { useContext } from 'react';

import { MailPoet } from 'mailpoet';
import { Button } from 'common/button/button';
import { t } from 'common/functions';
import { GlobalContext } from 'context';
import { useAction } from 'settings/store/hooks';
import { Label, Inputs } from 'settings/components';

export function Reinstall() {
  const reinstall = useAction('reinstall');
  // eslint-disable-next-line @typescript-eslint/no-explicit-any
  const { notices } = useContext<any>(GlobalContext);
  const onClick = async () => {
    if (
      // eslint-disable-next-line no-alert
      window.confirm(t('reinstallConfirmation'))
    ) {
      type Result = { type: 'SAVE_FAILED' | 'SAVE_DONE'; error?: string[] };
      // eslint-disable-next-line @typescript-eslint/no-explicit-any
      const action = (await reinstall()) as any as Result;
      if (action.type === 'SAVE_FAILED') {
        notices.error(
          action.error.map((err) => <p>{err}</p>),
          { scroll: true },
        );
      } else {
        window.location.href = `admin.php?page=${MailPoet.mainPageSlug}`;
      }
    }
  };
  return (
    <>
      <Label
        title={t('reinstallTitle')}
        description={t('reinstallDescription')}
        htmlFor=""
      />
      <Inputs>
        <Button
          type="button"
          onClick={onClick}
          automationId="reinstall-button"
          variant="destructive"
        >
          {t('reinstallNow')}
        </Button>
      </Inputs>
    </>
  );
}
