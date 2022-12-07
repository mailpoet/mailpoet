import ReactDOM from 'react-dom';
import { useSelect } from '@wordpress/data';
import { transformStyles } from '@wordpress/block-editor';
import css from 'css';

const FormStyles = () => {
  const element = document.getElementById('mailpoet-form-editor-form-styles');

  const formStyles = useSelect(
    (select) => select('mailpoet-form-editor').getFormStyles(),
    [],
  );

  try {
    css.parse(formStyles);
  } catch (e) {
    return ReactDOM.createPortal(null, element);
  }

  const transoformedStyles = transformStyles(
    [{ css: formStyles }],
    '.editor-styles-wrapper',
  );
  return ReactDOM.createPortal(transoformedStyles[0], element);
};

FormStyles.displayName = 'FormStyles';
export { FormStyles };
