import { useState } from '@wordpress/element';

import { useSelect, dispatch } from '@wordpress/data';
import { store as blockEditorStore } from '@wordpress/block-editor';
import { store as editorStore } from '@wordpress/editor';
import { Modal, Button } from '@wordpress/components';

export function SelectTemplate() {
  const { isCleanNew } = useSelect((select) => ({
    isCleanNew: select(editorStore).isCleanNewPost(),
  }));

  const [isTemplateModalOpen, setTemplateModalOpen] = useState(isCleanNew);
  const closeModal = () => setTemplateModalOpen(false);
  if (!isTemplateModalOpen) {
    return null;
  }

  return (
    <Modal
      title="Select Template"
      isDismissible={false}
      onRequestClose={() => null}
    >
      <Button
        variant="secondary"
        onClick={() => {
          dispatch(blockEditorStore).resetBlocks([
            {
              clientId: '6489457d-4145-4a4d-9dda-6c3de664ba56',
              name: 'core/paragraph',
              isValid: true,
              originalContent: '<p>Hello Writer</p>',
              attributes: {
                content: 'Hello Writer!',
                dropCap: false,
              },
              innerBlocks: [],
            },
          ]);
          closeModal();
        }}
      >
        Select Basic Template
      </Button>
      <Button
        variant="secondary"
        onClick={() => {
          closeModal();
        }}
      >
        Start from scratch
      </Button>
    </Modal>
  );
}
