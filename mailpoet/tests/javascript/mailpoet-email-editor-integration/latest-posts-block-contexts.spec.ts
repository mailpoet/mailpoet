import NodeModule from 'module';

/**
 * Core's post blocks fall back to an editable field bound to the source post
 * unless the context says they are inside a post loop, so the queryId has to
 * survive any rewrite of the template block.
 */

type ContextModule = {
  buildBlockContexts: (
    posts: { id: number; type: string }[],
  ) => { postId: number; postType: string; queryId: number }[];
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

describe('Latest Posts block contexts', () => {
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
});
