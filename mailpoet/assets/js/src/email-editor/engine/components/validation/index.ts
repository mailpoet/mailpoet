import { subscribe, select } from '@wordpress/data';
import { storeName } from 'email-editor/engine/store';
import { debounce } from '@wordpress/compose';
import { validateContent } from './validate-content';
import './index.scss';

// Caches results for performance reasons.
let cachedContent = '';

// Subscribe to email engine store and queue validation when content is changed.
const debouncedValidateContent = debounce(validateContent, 1000);

subscribe(() => {
  const content = select(storeName).getEditedEmailContent();

  if (content === cachedContent) {
    return;
  }

  cachedContent = content;
  debouncedValidateContent(content);
}, storeName);

export * from './validate-content';
export * from './validation-notices';
export * from './utils';
