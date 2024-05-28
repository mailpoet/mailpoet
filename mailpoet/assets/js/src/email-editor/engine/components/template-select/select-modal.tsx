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
import { storeName, TemplatePreview } from '../../store';

const BLANK_TEMPLATE = 'email-general';

export function SelectTemplateModal({ onSelectCallback }) {
  const [templates] = usePreviewTemplates();

  const handleTemplateSelection = (template: TemplatePreview) => {
    void dispatch(editorStore).resetEditorBlocks(template.patternParsed);
    void dispatch(storeName).setTemplateToPost(
      template.slug,
      template.template.mailpoet_email_theme ?? {},
    );
    onSelectCallback();
  };

  const handleCloseWithoutSelection = () => {
    const blankTemplate = templates.find(
      (template) => template.slug === BLANK_TEMPLATE,
    ) as unknown as TemplatePreview;
    if (!blankTemplate) return; // Prevent close if blank template is still not loaded
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
                        { css: template.template.email_theme_css },
                      ]}
                    />

                    <HStack className="block-editor-patterns__pattern-details">
                      <div className="block-editor-block-patterns-list__item-title">
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
