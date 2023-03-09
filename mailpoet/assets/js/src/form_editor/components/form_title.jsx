import { useState } from 'react';
import { useDispatch, useSelect } from '@wordpress/data';
import classnames from 'classnames';
import { MailPoet } from 'mailpoet';
import { storeName } from '../store';

export function FormTitle() {
  const [isSelected, setIsSelected] = useState(false);
  const title = useSelect((select) => select(storeName).getFormName(), []);
  const titleClass = classnames({
    'is-selected': isSelected,
  });
  const { changeFormName } = useDispatch(storeName);

  return (
    <div className={titleClass}>
      <label htmlFor="post-title" className="screen-reader-text">
        {MailPoet.I18n.t('addFormName')}
      </label>
      <input
        id="form-title"
        className="form-editor-title"
        placeholder={MailPoet.I18n.t('addFormName')}
        data-automation-id="form_title_input"
        type="text"
        onKeyPress={() => setIsSelected(false)}
        onBlur={() => setIsSelected(false)}
        onChange={(e) => changeFormName(e.target.value)}
        value={title}
      />
    </div>
  );
}
