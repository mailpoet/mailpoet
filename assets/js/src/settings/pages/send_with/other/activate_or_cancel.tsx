import React from 'react';
import { useHistory } from 'react-router-dom';

import MailPoet from 'mailpoet';
import Button from 'common/button/button';
import { t } from 'common/functions';
import { useAction } from 'settings/store/hooks';

export default function ActivateOrCancel() {
  const saveSettings = useAction('saveSettings');
  const loadSettings = useAction('loadSettings');
  const history = useHistory();
  const activate = async () => {
    await saveSettings();
    history.push('/mta');
  };
  const cancel = async () => {
    MailPoet.Modal.loading(true);
    await loadSettings();
    history.push('/mta');
    MailPoet.Modal.loading(false);
  };
  return (
    <p>
      <Button
        type="button"
        onClick={activate}
      >
        {t('activate')}
      </Button>
      <Button
        onClick={cancel}
        variant="link"
      >
        {t('orCancel')}
      </Button>
    </p>
  );
}
