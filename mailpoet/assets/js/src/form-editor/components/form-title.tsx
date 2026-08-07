import { TextControl } from '@wordpress/components';
import { useDispatch, useSelect } from '@wordpress/data';
import { MailPoet } from 'mailpoet';
import { storeName } from '../store';

export function FormTitle(): JSX.Element {
  const title = useSelect((select) => select(storeName).getFormName(), []);
  const { changeFormName } = useDispatch(storeName);

  return (
    <TextControl
      className="form-editor-title"
      label={MailPoet.I18n.t('addFormName')}
      hideLabelFromVision
      placeholder={MailPoet.I18n.t('addFormName')}
      value={title}
      onChange={changeFormName}
      data-automation-id="form_title_input"
    />
  );
}
