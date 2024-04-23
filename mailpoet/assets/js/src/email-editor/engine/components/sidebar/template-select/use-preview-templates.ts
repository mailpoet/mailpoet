import { BlockInstance, parse } from '@wordpress/blocks';
import { useSelect } from '@wordpress/data';
import { store as blockEditorStore } from '@wordpress/block-editor';
import { storeName } from '../../../store/constants';

/**
 * We need to merge pattern blocks and template blocks for BlockPreview component.
 * @param templateBlocks - Parsed template blocks
 * @param innerBlocks - Blocks to be set as content blocks for the template preview
 */
function setPostContentInnerBlocks(
  templateBlocks: BlockInstance[],
  innerBlocks: BlockInstance[],
): BlockInstance[] {
  return templateBlocks.map((block: BlockInstance) => {
    if (block.name === 'core/post-content') {
      return {
        ...block,
        name: 'core/group', // Change the name to group to render the innerBlocks
        innerBlocks,
      };
    }
    if (block.innerBlocks?.length) {
      return {
        ...block,
        innerBlocks: setPostContentInnerBlocks(block.innerBlocks, innerBlocks),
      };
    }
    return block;
  });
}

export function usePreviewTemplates() {
  const { templates, patterns } = useSelect((select) => {
    const contentBlockId =
      // @ts-expect-error getBlocksByName is not defined in types
      select(blockEditorStore).getBlocksByName('core/post-content')?.[0];
    return {
      templates: select(storeName).getEmailTemplates(),
      patterns:
        // @ts-expect-error getPatternsByBlockTypes is not defined in types
        select(blockEditorStore).getPatternsByBlockTypes(
          ['core/post-content'],
          contentBlockId,
        ),
    };
  }, []);
  if (!templates || !patterns.length) {
    return [[]];
  }

  const contentPatternBlocks = patterns[0].blocks as BlockInstance[];

  // eslint-disable-next-line @typescript-eslint/no-unsafe-return
  return [
    templates.map((template) => {
      // @ts-expect-error Missing property type
      // eslint-disable-next-line @typescript-eslint/no-unsafe-argument
      let parsedTemplate = parse(template.content?.raw);
      parsedTemplate = setPostContentInnerBlocks(
        parsedTemplate,
        contentPatternBlocks,
      );

      return {
        // @ts-expect-error Missing property type
        slug: template.slug,
        // eslint-disable-next-line @typescript-eslint/no-unsafe-argument
        contentParsed: parsedTemplate,
        patternParsed: contentPatternBlocks,
        template,
      };
    }),
  ];
}
