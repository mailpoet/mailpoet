import React, { useState } from 'react';
import { useDispatch, useSelect } from '@wordpress/data';
import classnames from 'classnames';

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

  return (
    <div className="editor-post-title">
      <div className={titleClass}>
        <div>
          <label htmlFor="post-title" className="screen-reader-text">
            Add form name
          </label>
          <textarea
            id="form-title"
            className="editor-post-title__input"
            placeholder="Add form name"
            rows="1"
            style={{
              overflow: 'hidden',
              overflowWrap: 'break-word',
              resize: 'none',
            }}
            spellCheck="false"
            onFocus={() => setIsSelected(true)}
            onKeyPress={() => setIsSelected(false)}
            onChange={(e) => changeFormName(e.target.value)}
            value={title}
          />
        </div>
      </div>
    </div>
  );
};
