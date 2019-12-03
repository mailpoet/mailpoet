import React, { useState } from 'react';
import { useDispatch, useSelect } from '@wordpress/data';
import classnames from 'classnames';
import MailPoet from 'mailpoet';
import Textarea from 'react-autosize-textarea';

export default () => {
  const [isSelected, setIsSelected] = useState(false);
  const title = useSelect(
    (select) => select('mailpoet-form-editor').getFormName(),
    []
  );
  const titleClass = classnames('wp-block editor-post-title__block', {
    'is-selected': isSelected,
  });
  const { changeFormName } = useDispatch('mailpoet-form-editor');
  const { clearSelectedBlock } = useDispatch('core/block-editor');
  return (
    <div className="editor-post-title">
      <div className={titleClass}>
        <div>
          <label htmlFor="post-title" className="screen-reader-text">
            {MailPoet.I18n.t('addFormName')}
          </label>
          <Textarea
            id="form-title"
            className="editor-post-title__input"
            placeholder={MailPoet.I18n.t('addFormName')}
            data-automation-id="form_title_input"
            rows={1}
            onFocus={() => {
              setIsSelected(true);
              clearSelectedBlock();
            }}
            onKeyPress={() => setIsSelected(false)}
            onBlur={() => setIsSelected(false)}
            onChange={(e) => changeFormName(e.target.value)}
            value={title}
          />
        </div>
      </div>
    </div>
  );
};
