// eslint-disable-next-line @typescript-eslint/no-unused-vars
// @ts-expect-error No types available for this component
import { BlockPreview } from '@wordpress/block-editor';
import { Modal } from '@wordpress/components';
import { Async } from 'email-editor/engine/components/sidebar/template-select/async';
import { getTemplatesForPreview } from './templates-data';

export function SelectTemplateModal({ isOpen, setIsOpen }) {
  if (!isOpen) {
    return null;
  }
  const templates = getTemplatesForPreview();
  return (
    // eslint-disable-next-line @typescript-eslint/no-unsafe-return
    <Modal title="Select a template" onRequestClose={() => setIsOpen(false)}>
      {templates.map((template) => (
        <div key={template.id}>
          <h2>{`Template ${template.id}`}</h2>
          <div
            style={{
              width: '450px',
              border: '1px solid #000',
              padding: '20px',
              display: 'block',
            }}
          >
            <Async placeholder={<p>rendering template</p>}>
              <BlockPreview
                blocks={template.contentParsed}
                viewportWidth={1200}
              />
            </Async>
          </div>
        </div>
      ))}
    </Modal>
  );
}
