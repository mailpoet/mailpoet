import React from 'react';
import { useHistory } from 'react-router-dom';

import MailPoet from 'mailpoet';
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
  const cancel = async (e) => {
    e.preventDefault();
    MailPoet.Modal.loading(true);
    await loadSettings();
    history.push('/mta');
    MailPoet.Modal.loading(false);
  };
  return (
    <p>
      <button
        type="button"
        onClick={activate}
        className="mailpoet_mta_setup_save button button-primary"
      >
        {t('activate')}
      </button>
      <a
        href=""
        onClick={cancel}
        className="mailpoet_mta_setup_cancel"
      >
        {t('orCancel')}
      </a>
    </p>
  );
}
