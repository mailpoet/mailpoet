import type { KeyboardEvent as ReactKeyboardEvent } from 'react';
import { __ } from '@wordpress/i18n';
import { memo, useMemo, useState } from '@wordpress/element';
import { useSelect } from '@wordpress/data';
import * as BlockEditor from '@wordpress/block-editor';
import { Spinner } from '@wordpress/components';
import { store as coreStore } from '@wordpress/core-data';
import { QUERY_CONTEXT, DISPLAY_LAYOUT_CONTEXT, MAX_POSTS } from '../constants';
import { DEFAULT_PASSWORD_PROTECTED } from '../password-protected';
import type { PasswordProtectedMode } from '../password-protected';

const {
  useBlockProps,
  useInnerBlocksProps,
  store: blockEditorStore,
} = BlockEditor;

// BlockContextProvider and __experimentalUseBlockPreview exist at runtime in
// @wordpress/block-editor but are missing from the bundled type definitions,
// so we access them through a narrow typed cast.
type BlockContextProviderType = React.ComponentType<{
  value: Record<string, unknown>;
  children: React.ReactNode;
}>;
type UseBlockPreview = (args: {
  blocks: unknown;
  props?: Record<string, unknown>;
}) => Record<string, unknown>;

const { BlockContextProvider } = BlockEditor as unknown as {
  BlockContextProvider: BlockContextProviderType;
};
const castBlockEditor = BlockEditor as unknown as {
  __experimentalUseBlockPreview: UseBlockPreview;
};
// eslint-disable-next-line no-underscore-dangle
const useBlockPreview = castBlockEditor.__experimentalUseBlockPreview;

type Term = { id: number; taxonomy: string };

type LatestPostsQuery = {
  selectionMode?: 'latest' | 'manual';
  perPage?: number;
  postType?: string;
  order?: string;
  offset?: number;
  terms?: Term[];
  inclusionType?: 'include' | 'exclude';
  posts?: number[];
  passwordProtected?: PasswordProtectedMode;
};

type TemplateContext = {
  [QUERY_CONTEXT]?: LatestPostsQuery;
  [DISPLAY_LAYOUT_CONTEXT]?: { columns?: number };
};

type EditProps = {
  clientId: string;
  context: TemplateContext;
};

type PostRecord = {
  id: number;
  type: string;
  content?: { protected?: boolean };
};
type BlockContext = { postId: number; postType: string; queryId: number };

// Core's post blocks show an editable field bound to the source post unless they
// are inside a post loop, which they decide from a finite queryId. Without it the
// email editor would let someone rewrite the post being previewed, and the
// editable field takes its value as HTML rather than as text.
const QUERY_ID = 0;

/**
 * What to ask the REST API for. The API cannot filter by password, so under
 * 'exclude' the posts are dropped in the browser and the paging has to happen
 * after that: the fetch takes no offset and asks for enough posts to still fill
 * the preview once the protected ones are gone. Each record carries the post's
 * rendered content, so the headroom is bounded rather than maxed out.
 *
 * Two ways the preview can then show fewer posts than the email sends: a run of
 * protected posts longer than the headroom, and an offset that reaches past the
 * fetched window, which previews as no posts at all. The block offers no offset
 * control, so only a hand-written attribute reaches the second one.
 */
export function getFetchWindow(
  passwordProtected: PasswordProtectedMode,
  perPage: number,
  offset: number,
): { perPage: number; offset: number } {
  if (passwordProtected !== 'exclude') {
    return { perPage, offset };
  }
  return {
    perPage: Math.min(MAX_POSTS, (offset + perPage) * 3 + 10),
    offset: 0,
  };
}

/**
 * The posts the block shows, in the order it shows them, out of what
 * getFetchWindow() asked for.
 */
export function selectVisiblePosts(
  posts: PostRecord[],
  {
    passwordProtected,
    isManual,
    perPage,
    offset,
  }: {
    passwordProtected: PasswordProtectedMode;
    isManual: boolean;
    perPage: number;
    offset: number;
  },
): PostRecord[] {
  if (passwordProtected !== 'exclude') {
    return posts;
  }
  const kept = posts.filter((post) => !post.content?.protected);
  // A manual selection shows exactly the posts picked, and the server ignores
  // the offset for it.
  return isManual ? kept : kept.slice(offset, offset + perPage);
}

/**
 * The context each previewed post is rendered under. Exported so the queryId
 * cannot be dropped without a test noticing.
 */
export function buildBlockContexts(posts: PostRecord[]): BlockContext[] {
  return posts.map((post) => ({
    postId: post.id,
    postType: post.type,
    queryId: QUERY_ID,
  }));
}

// This is the default layout for each post. Users can customize it by adding, removing, or rearranging the inner blocks below.
const POST_TEMPLATE: Array<[string, Record<string, unknown>?]> = [
  ['core/post-featured-image'],
  ['core/post-title', { level: 3, isLink: true }],
  ['core/post-excerpt'],
];

function TemplateInnerBlocks(): JSX.Element {
  const innerBlocksProps = useInnerBlocksProps(
    { className: 'mailpoet-latest-posts__item' },
    { template: POST_TEMPLATE, templateLock: false },
  );
  return <div {...innerBlocksProps} />;
}

// Translates the block's query attributes into a WP REST query, mirroring the
// server-side BlockPostQuery so the editor preview matches the sent email.
function buildRestQuery({
  isManual,
  manualPosts,
  perPage,
  offset,
  order,
  terms,
  inclusionType,
}: {
  isManual: boolean;
  manualPosts: number[];
  perPage: number;
  offset: number;
  order: string;
  terms: Term[];
  inclusionType: 'include' | 'exclude';
}): Record<string, unknown> {
  if (isManual) {
    // include: [0] resolves to no posts, so an empty manual selection previews
    // as "no posts" instead of falling back to the latest ones.
    return manualPosts.length
      ? {
          include: manualPosts,
          per_page: Math.min(manualPosts.length, MAX_POSTS),
          orderby: 'include',
        }
      : { include: [0], per_page: 1 };
  }

  const restQuery: Record<string, unknown> = {
    per_page: perPage,
    offset,
    order: order === 'oldest' ? 'asc' : 'desc',
    orderby: 'date',
  };

  const categoryIds = terms
    .filter((term) => term.taxonomy === 'category')
    .map((term) => term.id);
  const tagIds = terms
    .filter((term) => term.taxonomy === 'post_tag')
    .map((term) => term.id);

  const isExclude = inclusionType === 'exclude';
  if (categoryIds.length) {
    restQuery[isExclude ? 'categories_exclude' : 'categories'] = categoryIds;
  }
  if (tagIds.length) {
    restQuery[isExclude ? 'tags_exclude' : 'tags'] = tagIds;
  }

  return restQuery;
}

function TemplateBlockPreview({
  blocks,
  blockContextId,
  isHidden,
  setActiveBlockContextId,
}: {
  blocks: unknown;
  blockContextId: number;
  isHidden: boolean;
  setActiveBlockContextId: (id: number) => void;
}): JSX.Element {
  const blockPreviewProps = useBlockPreview({
    blocks,
    props: { className: 'mailpoet-latest-posts__item' },
  });

  const handleOnClick = (): void => setActiveBlockContextId(blockContextId);

  const handleOnKeyDown = (event: ReactKeyboardEvent): void => {
    if (event.key !== 'Enter' && event.key !== ' ') {
      return;
    }
    event.preventDefault();
    handleOnClick();
  };

  return (
    <div
      {...blockPreviewProps}
      tabIndex={0}
      role="button"
      onClick={handleOnClick}
      onKeyDown={handleOnKeyDown}
      style={{ display: isHidden ? 'none' : undefined }}
    />
  );
}

const MemoizedTemplateBlockPreview = memo(TemplateBlockPreview);

export function Edit({ clientId, context }: EditProps): JSX.Element {
  const query = context[QUERY_CONTEXT] || {};
  const displayLayout = context[DISPLAY_LAYOUT_CONTEXT] || {};
  const {
    selectionMode = 'latest',
    perPage = 3,
    postType = 'post',
    order = 'newest',
    offset = 0,
    terms = [],
    inclusionType = 'include',
    posts: manualPosts = [],
    passwordProtected = DEFAULT_PASSWORD_PROTECTED,
  } = query;
  const columns = displayLayout.columns || 1;
  const [activeBlockContextId, setActiveBlockContextId] = useState<number>();

  const isManual = selectionMode === 'manual';

  const { perPage: fetchPerPage, offset: fetchOffset } = getFetchWindow(
    passwordProtected,
    perPage,
    offset,
  );

  const { posts, blocks } = useSelect(
    (select) => {
      const { getEntityRecords } = select(coreStore);
      const { getBlocks } = select(blockEditorStore) as unknown as {
        getBlocks: (clientId: string) => unknown[];
      };

      const restQuery = buildRestQuery({
        isManual,
        manualPosts,
        perPage: fetchPerPage,
        offset: fetchOffset,
        order,
        // Categories and tags only apply to posts; ignore stale terms after a
        // switch to pages so the preview matches the server-side query.
        terms: postType === 'post' ? terms : [],
        inclusionType,
      });

      return {
        posts: getEntityRecords('postType', postType, restQuery) as unknown as
          | PostRecord[]
          | null,
        blocks: getBlocks(clientId),
      };
    },
    [
      isManual,
      manualPosts.join(','),
      fetchPerPage,
      postType,
      order,
      fetchOffset,
      JSON.stringify(terms),
      inclusionType,
      clientId,
    ],
  );

  const visiblePosts = useMemo<PostRecord[]>(
    () =>
      selectVisiblePosts(posts ?? [], {
        passwordProtected,
        isManual,
        perPage,
        offset,
      }),
    [posts, passwordProtected, isManual, perPage, offset],
  );

  const blockContexts = useMemo<BlockContext[]>(
    () => buildBlockContexts(visiblePosts),
    [visiblePosts],
  );

  const blockProps = useBlockProps({
    className:
      columns > 1
        ? `mailpoet-latest-posts columns-${columns}`
        : 'mailpoet-latest-posts',
  });

  if (!posts) {
    return (
      <p {...blockProps}>
        <Spinner />
      </p>
    );
  }

  if (!visiblePosts.length) {
    return <p {...blockProps}>{__('No posts found.', 'mailpoet')}</p>;
  }

  // The post the user picked can leave the preview, for example once protected
  // posts are left out. Falling back keeps the editable template on screen.
  const activeContextId = blockContexts.some(
    (blockContext) => blockContext.postId === activeBlockContextId,
  )
    ? activeBlockContextId
    : blockContexts[0]?.postId;

  return (
    <div {...blockProps}>
      {blockContexts.map((blockContext) => (
        <BlockContextProvider key={blockContext.postId} value={blockContext}>
          {blockContext.postId === activeContextId ? (
            <TemplateInnerBlocks />
          ) : null}
          <MemoizedTemplateBlockPreview
            blocks={blocks}
            blockContextId={blockContext.postId}
            setActiveBlockContextId={setActiveBlockContextId}
            isHidden={blockContext.postId === activeContextId}
          />
        </BlockContextProvider>
      ))}
    </div>
  );
}
