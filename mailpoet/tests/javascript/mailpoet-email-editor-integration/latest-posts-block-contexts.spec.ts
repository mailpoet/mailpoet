import NodeModule from 'module';

/**
 * The pure helpers behind the Post Template preview: which posts it shows, and
 * the context it shows each of them under.
 */

type PostRecord = {
  id: number;
  type: string;
  content?: { protected?: boolean };
};

type ContextModule = {
  buildBlockContexts: (
    posts: PostRecord[],
  ) => { postId: number; postType: string; queryId: number }[];
  selectVisiblePosts: (
    posts: PostRecord[],
    options: {
      passwordProtected: 'title_only' | 'exclude' | 'full';
      isManual: boolean;
      perPage: number;
      offset: number;
    },
  ) => PostRecord[];
  getFetchWindow: (
    passwordProtected: 'title_only' | 'exclude' | 'full',
    perPage: number,
    offset: number,
  ) => { perPage: number; offset: number };
};
type ModuleLoader = (
  request: string,
  parent: unknown,
  isMain: boolean,
) => unknown;
type ModuleWithLoader = Record<string, ModuleLoader>;
type TestRequire = (path: string) => unknown;

const moduleLoadProperty = '_load';
const testRequire = (
  NodeModule as unknown as { createRequire: (path: string) => TestRequire }
).createRequire(`${process.cwd()}/package.json`);

let contextModule: ContextModule;
let originalModuleLoad: ModuleLoader;

describe('Latest Posts post template', () => {
  before(() => {
    const moduleWithLoader = NodeModule as unknown as ModuleWithLoader;
    originalModuleLoad = moduleWithLoader[moduleLoadProperty];
    moduleWithLoader[moduleLoadProperty] = function loadModule(
      request: string,
      parent: unknown,
      isMain: boolean,
    ) {
      // The template block pulls in editor packages that need a browser.
      if (request.startsWith('@wordpress/')) {
        return new Proxy(
          {},
          {
            get: () => () => undefined,
          },
        );
      }
      return originalModuleLoad.apply(this, [request, parent, isMain]);
    };
    contextModule = testRequire(
      './assets/js/src/mailpoet-custom-email-editor-blocks/latest-posts/post-template/edit',
    ) as ContextModule;
  });

  after(() => {
    (NodeModule as unknown as ModuleWithLoader)[moduleLoadProperty] =
      originalModuleLoad;
  });

  /**
   * Core's post blocks fall back to an editable field bound to the source post
   * unless the context says they are inside a post loop, so the queryId has to
   * survive any rewrite of the template block.
   */
  it('marks every previewed post as part of a post loop', () => {
    const contexts = contextModule.buildBlockContexts([
      { id: 11, type: 'post' },
      { id: 12, type: 'page' },
    ]);

    expect(contexts).to.have.lengthOf(2);
    contexts.forEach((context) => {
      expect(Number.isFinite(context.queryId)).to.equal(true);
    });
    expect(contexts[0].postId).to.equal(11);
    expect(contexts[0].postType).to.equal('post');
    expect(contexts[1].postId).to.equal(12);
  });

  it('returns nothing when there are no posts', () => {
    expect(contextModule.buildBlockContexts([])).to.have.lengthOf(0);
  });

  const protectedPost = (id: number): PostRecord => ({
    id,
    type: 'post',
    content: { protected: true },
  });
  const publicPost = (id: number): PostRecord => ({ id, type: 'post' });
  const ids = (posts: PostRecord[]): number[] => posts.map((post) => post.id);

  it('shows posts that need a password unless they are left out', () => {
    const posts = [publicPost(1), protectedPost(2)];

    (['title_only', 'full'] as const).forEach((passwordProtected) => {
      const visible = contextModule.selectVisiblePosts(posts, {
        passwordProtected,
        isManual: false,
        perPage: 3,
        offset: 0,
      });
      expect(ids(visible)).to.deep.equal([1, 2]);
    });
  });

  it('drops posts that need a password when they are left out', () => {
    const visible = contextModule.selectVisiblePosts(
      [publicPost(1), protectedPost(2), publicPost(3)],
      { passwordProtected: 'exclude', isManual: false, perPage: 3, offset: 0 },
    );

    expect(ids(visible)).to.deep.equal([1, 3]);
  });

  it('fills the preview up to the number of posts asked for', () => {
    const visible = contextModule.selectVisiblePosts(
      [protectedPost(1), publicPost(2), publicPost(3), publicPost(4)],
      { passwordProtected: 'exclude', isManual: false, perPage: 2, offset: 0 },
    );

    expect(ids(visible)).to.deep.equal([2, 3]);
  });

  // The server leaves the protected posts out in SQL and skips the offset after
  // that, so the offset has to count the posts that are left.
  it('counts the offset after the protected posts are gone', () => {
    const visible = contextModule.selectVisiblePosts(
      [protectedPost(1), publicPost(2), publicPost(3), publicPost(4)],
      { passwordProtected: 'exclude', isManual: false, perPage: 2, offset: 1 },
    );

    expect(ids(visible)).to.deep.equal([3, 4]);
  });

  it('asks the REST API for exactly the posts it shows', () => {
    (['title_only', 'full'] as const).forEach((passwordProtected) => {
      expect(
        contextModule.getFetchWindow(passwordProtected, 3, 2),
      ).to.deep.equal({ perPage: 3, offset: 2 });
    });
  });

  // The server leaves the protected posts out in SQL and pages after that, so
  // the preview cannot let the API page for it.
  it('asks for headroom and no offset when protected posts are left out', () => {
    const window = contextModule.getFetchWindow('exclude', 3, 2);

    expect(window.offset).to.equal(0);
    expect(window.perPage).to.be.greaterThan(5);
    expect(window.perPage).to.be.at.most(100);
  });

  it('never asks for more posts than the REST API allows', () => {
    expect(contextModule.getFetchWindow('exclude', 10, 500).perPage).to.equal(
      100,
    );
  });

  it('shows every post picked by hand that is left', () => {
    const visible = contextModule.selectVisiblePosts(
      [publicPost(1), protectedPost(2), publicPost(3), publicPost(4)],
      { passwordProtected: 'exclude', isManual: true, perPage: 2, offset: 1 },
    );

    expect(ids(visible)).to.deep.equal([1, 3, 4]);
  });
});
