import {
  limitToPostTemplate,
  TEMPLATE_CORE_BLOCKS,
} from '../../../assets/js/src/mailpoet-email-editor-integration/latest-posts-template-blocks';
import { TEMPLATE_BLOCK_NAME } from '../../../assets/js/src/mailpoet-custom-email-editor-blocks/latest-posts/constants';

describe('Latest Posts template blocks', () => {
  it('limits the post blocks to the Post Template block', () => {
    TEMPLATE_CORE_BLOCKS.forEach((name) => {
      const settings = limitToPostTemplate(
        { name, title: name, category: 'theme', attributes: {} },
        name,
      );
      expect(settings.ancestor).to.deep.equal([TEMPLATE_BLOCK_NAME]);
    });
  });

  it('keeps ancestors set by other extensions', () => {
    const settings = limitToPostTemplate(
      {
        name: 'core/post-title',
        title: 'Post Title',
        category: 'theme',
        attributes: {},
        ancestor: ['other/post-list'],
      },
      'core/post-title',
    );
    expect(settings.ancestor).to.deep.equal([
      'other/post-list',
      TEMPLATE_BLOCK_NAME,
    ]);
  });

  it('leaves other blocks untouched', () => {
    const settings = {
      name: 'core/paragraph',
      title: 'Paragraph',
      category: 'text',
      attributes: {},
    };
    expect(limitToPostTemplate(settings, 'core/paragraph')).to.equal(settings);
  });
});
