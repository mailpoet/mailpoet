// @ts-expect-error No types available for this component
import { BlockPreview } from '@wordpress/block-editor';
import { store as editorStore } from '@wordpress/editor';
import { dispatch } from '@wordpress/data';
import {
  Modal,
  __experimentalHStack as HStack,
  Button,
} from '@wordpress/components';
import { Async } from './async';
import { usePreviewTemplates } from '../../hooks';
import { storeName } from '../../store/constants';

const BLANK_TEMPLATE = 'email-general';

export function SelectTemplateModal({ onSelectCallback }) {
  const [templates] = usePreviewTemplates();

  const handleTemplateSelection = (template) => {
    void dispatch(editorStore).resetEditorBlocks(template.patternParsed);
    void dispatch(storeName).setTemplateToPost(
      template.slug,
      // eslint-disable-next-line @typescript-eslint/no-unsafe-argument
      template.template.mailpoet_email_theme ?? {},
    );
    onSelectCallback();
  };

  const handleCloseWithoutSelection = () => {
    const blankTemplate = templates.find(
      (template) => template.slug === BLANK_TEMPLATE,
    );
    handleTemplateSelection(blankTemplate);
  };

  return (
    <Modal
      title="Select a template"
      onRequestClose={() => handleCloseWithoutSelection()}
      isFullScreen
    >
      <div className="block-editor-block-patterns-explorer">
        <div className="block-editor-block-patterns-explorer__sidebar">
          <div className="block-editor-block-patterns-explorer__sidebar__categories-list">
            <Button
              key="category"
              label="Category"
              className="block-editor-block-patterns-explorer__sidebar__categories-list__item"
              isPressed
              onClick={() => {}}
            >
              Dummy Category
            </Button>
          </div>
        </div>
        <div className="block-editor-block-patterns-explorer__list">
          <div className="block-editor-block-patterns-list" role="listbox">
            {templates.map((template) => (
              <div
                key={template.slug}
                className="block-editor-block-patterns-list__list-item"
              >
                <div
                  className="block-editor-block-patterns-list__item"
                  role="button"
                  tabIndex={0}
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
                        // @ts-expect-error No types for template
                        { css: template.template.email_theme_css },
                      ]}
                    />

                    <HStack className="block-editor-patterns__pattern-details">
                      <div className="block-editor-block-patterns-list__item-title">
                        {/* @ts-expect-error No type for template */}
                        {template.template.title.rendered}
                      </div>
                    </HStack>
                  </Async>
                </div>
              </div>
            ))}
          </div>
        </div>
      </div>
    </Modal>
  );
}
