// @ts-expect-error No types available for this component
import { BlockPreview } from '@wordpress/block-editor';
import {
  __experimentalHStack as HStack, // eslint-disable-line
} from '@wordpress/components';
import { Async } from './async';

export function TemplateList({templates, onTemplateSelection}) {

  return (
    <div className="block-editor-block-patterns-explorer__list">
      <div
        className="block-editor-block-patterns-list"
        role="listbox"
      >
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
                onTemplateSelection(template);
              }}
              onKeyPress={(event) => {
                if (
                  event.key === 'Enter' ||
                  event.key === ' '
                ) {
                  onTemplateSelection(template);
                }
              }}
            >
              <Async
                placeholder={
                  <p>rendering template</p>
                }
              >
                <BlockPreview
                  blocks={template.previewContentParsed}
                  viewportWidth={900}
                  minHeight={300}
                  additionalStyles={[
                    {
                      css: template.template.email_theme_css,
                    },
                  ]}
                />

                <HStack className="block-editor-patterns__pattern-details">
                  <div className="block-editor-block-patterns-list__item-title">
                    {
                      template.template.title.rendered
                    }
                  </div>
                </HStack>
              </Async>
            </div>
          </div>
        ))}
      </div>
    </div>
  );
}
