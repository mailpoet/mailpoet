// @ts-expect-error No types available for this component
import { BlockPreview } from '@wordpress/block-editor';
import { store as editorStore } from '@wordpress/editor';
import { dispatch } from '@wordpress/data';
import { Modal } from '@wordpress/components';
import { Async } from 'email-editor/engine/components/sidebar/template-select/async';
import { usePreviewTemplates } from './use-preview-templates';
import { storeName } from '../../../store/constants';

export function SelectTemplateModal({ setIsOpen }) {
  const [templates] = usePreviewTemplates();

  const handleTemplateSelection = (template) => {
    setIsOpen(false);
    void dispatch(editorStore).resetEditorBlocks(template.patternParsed);
    void dispatch(storeName).setTemplateToPost(
      template.slug,
      // eslint-disable-next-line @typescript-eslint/no-unsafe-argument
      template.template.email_theme?.theme ?? {},
    );
  };

  return (
    // eslint-disable-next-line @typescript-eslint/no-unsafe-return
    <Modal title="Select a template" onRequestClose={() => setIsOpen(false)}>
      {templates.map((template) => (
        <div key={template.slug}>
          {/* eslint-disable-next-line @typescript-eslint/restrict-template-expressions */}
          <h2>{`Template ${template.slug}`}</h2>
          <div
            role="button"
            tabIndex={0}
            style={{
              width: '450px',
              border: '1px solid #000',
              padding: '20px',
              display: 'block',
              cursor: 'pointer',
            }}
            onClick={() => {
              handleTemplateSelection(template);
            }}
            onKeyPress={(event) => {
              if (event.key === 'Enter' || event.key === ' ') {
                handleTemplateSelection(template);
              }
            }}
          >
            <Async placeholder={<p>rendering template</p>}>
              <BlockPreview
                blocks={template.contentParsed}
                viewportWidth={900}
                minHeight={300}
                additionalStyles={[
                  // @ts-expect-error No types available for template
                  { css: template.template.email_theme?.css },
                ]}
              />
            </Async>
          </div>
        </div>
      ))}
    </Modal>
  );
}
