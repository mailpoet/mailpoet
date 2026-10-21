import {
  addPasswordProtectedContext,
  hidesProtectedContent,
  PROTECTED_POST_BLOCKS,
} from '../../../assets/js/src/mailpoet-email-editor-integration/latest-posts-protected-content';
import { QUERY_CONTEXT } from '../../../assets/js/src/mailpoet-custom-email-editor-blocks/latest-posts/constants';
import {
  hidesPostContent,
  isProtectedRecord,
} from '../../../assets/js/src/mailpoet-custom-email-editor-blocks/latest-posts/password-protected';

describe('Latest Posts protected content', () => {
  it('covers the core blocks that print post content', () => {
    expect(PROTECTED_POST_BLOCKS).to.have.members([
      'core/post-excerpt',
      'core/post-content',
    ]);
  });

  it('passes the query context to the blocks that print post content', () => {
    PROTECTED_POST_BLOCKS.forEach((name) => {
      const settings = addPasswordProtectedContext(
        { title: name, category: 'theme', attributes: {} },
        name,
      );
      expect(settings.usesContext).to.deep.equal([QUERY_CONTEXT]);
    });
  });

  it('keeps the context keys the block already asked for', () => {
    const settings = addPasswordProtectedContext(
      {
        title: 'Excerpt',
        category: 'theme',
        attributes: {},
        usesContext: ['postId', 'postType'],
      },
      'core/post-excerpt',
    );
    expect(settings.usesContext).to.deep.equal([
      'postId',
      'postType',
      QUERY_CONTEXT,
    ]);
  });

  it('leaves other blocks untouched', () => {
    const settings = { title: 'Paragraph', category: 'text', attributes: {} };
    expect(addPasswordProtectedContext(settings, 'core/paragraph')).to.equal(
      settings,
    );
  });

  it('adds the context key only once', () => {
    const settings = {
      title: 'Excerpt',
      category: 'theme',
      attributes: {},
      usesContext: ['postId', QUERY_CONTEXT],
    };
    expect(addPasswordProtectedContext(settings, 'core/post-excerpt')).to.equal(
      settings,
    );
  });

  it('reads the mark the REST API puts on a post with a password', () => {
    expect(isProtectedRecord({ content: { protected: true } })).to.equal(true);
    expect(isProtectedRecord({ content: { protected: false } })).to.equal(
      false,
    );
    expect(isProtectedRecord({ content: {} })).to.equal(false);
    expect(isProtectedRecord(null)).to.equal(false);
    expect(isProtectedRecord()).to.equal(false);
  });

  it('keeps the content to itself in every handling but full', () => {
    expect(hidesPostContent('title_only')).to.equal(true);
    expect(hidesPostContent('exclude')).to.equal(true);
    expect(hidesPostContent(undefined)).to.equal(true);
    expect(hidesPostContent('full')).to.equal(false);
  });

  it('hides the content of a protected post by default', () => {
    expect(
      hidesProtectedContent({
        name: 'core/post-excerpt',
        context: { postId: 1, postType: 'post', [QUERY_CONTEXT]: {} },
      }),
    ).to.equal(true);
  });

  it('hides the content when the posts are left out', () => {
    expect(
      hidesProtectedContent({
        name: 'core/post-excerpt',
        context: { [QUERY_CONTEXT]: { passwordProtected: 'exclude' } },
      }),
    ).to.equal(true);
  });

  it('shows the content when protected posts are treated like any other', () => {
    expect(
      hidesProtectedContent({
        name: 'core/post-excerpt',
        context: { [QUERY_CONTEXT]: { passwordProtected: 'full' } },
      }),
    ).to.equal(false);
  });

  it('leaves blocks outside a Latest Posts template alone', () => {
    expect(
      hidesProtectedContent({
        name: 'core/post-excerpt',
        context: { postId: 1, postType: 'post' },
      }),
    ).to.equal(false);
    expect(hidesProtectedContent({ name: 'core/post-excerpt' })).to.equal(
      false,
    );
  });

  it('leaves blocks that do not print post content alone', () => {
    expect(
      hidesProtectedContent({
        name: 'core/post-title',
        context: { [QUERY_CONTEXT]: {} },
      }),
    ).to.equal(false);
  });
});
