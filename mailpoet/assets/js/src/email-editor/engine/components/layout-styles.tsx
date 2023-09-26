import { useSelect } from '@wordpress/data';
import { store as editorStore } from '@wordpress/editor';
import { EmailData } from 'email-editor/engine/types';

export function LayoutStyles() {
  const { emailData } = useSelect((select) => ({
    emailData:
      (select(editorStore).getEditedPostAttribute(
        // eslint-disable-next-line @typescript-eslint/ban-ts-comment
        // @ts-ignore
        // The getEditedPostAttribute accepts an attribute but typescript thinks it doesn't
        'email_data',
      ) as EmailData) ?? null,
  }));

  const css = `
    .edit-post-visual-editor__content-area {
        max-width: ${emailData.layout_styles.width}px;
    }
    .edit-post-visual-editor {
    background: ${emailData.layout_styles.background};
  }`;

  return <style>{css}</style>;
}
