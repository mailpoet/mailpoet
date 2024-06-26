import { useNavigate } from 'react-router-dom';

import { MailPoet } from 'mailpoet';
import { Button } from 'common/button/button';
import { t } from 'common/functions';
import { useAction } from 'settings/store/hooks';

export function ActivateOrCancel() {
  const saveSettings = useAction('saveSettings');
  const loadSettings = useAction('loadSettings');
  const navigate = useNavigate();
  const activate = async () => {
    await saveSettings();
    navigate('/mta');
  };
  const cancel = async () => {
    MailPoet.Modal.loading(true);
    await loadSettings();
    navigate('/mta');
    MailPoet.Modal.loading(false);
  };
  return (
    <div className="mailpoet-settings-save">
      <Button type="button" onClick={activate}>
        {t('activate')}
      </Button>
      <Button onClick={cancel} variant="tertiary">
        {t('orCancel')}
      </Button>
    </div>
  );
}
