import React from 'react';

import { t } from 'common/functions';
import { GlobalContext } from 'context';
import { useAction } from 'settings/store/hooks';
import { Label, Inputs } from 'settings/components';

export default function Reinstall() {
  const reinstall = useAction('reinstall');
  const { notices } = React.useContext<any>(GlobalContext);
  const onClick = async () => {
    if (window.confirm(t('reinstallConfirmation'))) { // eslint-disable-line
      type Result = { type: 'SAVE_FAILED' | 'SAVE_DONE', error?: any }
      const action = (await reinstall()) as any as Result;
      if (action.type === 'SAVE_FAILED') {
        notices.error(action.error.map((err) => <p>{err}</p>), { scroll: true });
      } else {
        window.location.href = 'admin.php?page=mailpoet-newsletters';
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
        <button
          type="button"
          className="button"
          onClick={onClick}
          data-automation-id="reinstall-button"
        >
          {t('reinstallNow')}
        </button>
      </Inputs>
    </>
  );
}
