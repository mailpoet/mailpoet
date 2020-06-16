import React, { useState, useRef } from 'react';
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
  const titleClass = classnames('wp-block editor-post-title editor-post-title__block', {
    'is-selected': isSelected,
  });
  const { changeFormName } = useDispatch('mailpoet-form-editor');
  const { clearSelectedBlock } = useDispatch('core/block-editor');

  /**
   * Sometimes the focus is not triggered by clicking the title itself but by
   * <WritingFlow /> which automatically search for some element to focus in case
   * user clicks on space below blocks.
   * We don't want to switch focus to the title in such a case
   * so we just dispatch clearSelectedBlock().
   */
  const textArea = useRef(null);
  const handleOnFocus = (e) => {
    clearSelectedBlock();
    const focusWasRedirected = e.relatedTarget
      && e.relatedTarget.className
      && e.relatedTarget.className.includes('editor-writing-flow__click-redirect');
    if (focusWasRedirected && textArea.current) {
      textArea.current.blur();
      return;
    }
    setIsSelected(true);
  };

  return (
    <div className="edit-post-visual-editor__post-title-wrapper">
      <div className={titleClass}>
        <div>
          <label htmlFor="post-title" className="screen-reader-text">
            {MailPoet.I18n.t('addFormName')}
          </label>
          <Textarea
            id="form-title"
            ref={textArea}
            className="editor-post-title__input"
            placeholder={MailPoet.I18n.t('addFormName')}
            data-automation-id="form_title_input"
            rows={1}
            onFocus={handleOnFocus}
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
