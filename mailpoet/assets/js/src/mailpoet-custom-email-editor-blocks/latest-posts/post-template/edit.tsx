import { __ } from '@wordpress/i18n';
import { memo, useMemo, useState } from '@wordpress/element';
import { useSelect } from '@wordpress/data';
import * as BlockEditor from '@wordpress/block-editor';
import { Spinner } from '@wordpress/components';
import { store as coreStore } from '@wordpress/core-data';
import { QUERY_CONTEXT, DISPLAY_LAYOUT_CONTEXT } from '../constants';

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
};

type TemplateContext = {
  [QUERY_CONTEXT]?: LatestPostsQuery;
  [DISPLAY_LAYOUT_CONTEXT]?: { columns?: number };
};

type EditProps = {
  clientId: string;
  context: TemplateContext;
};

type PostRecord = { id: number; type: string };
type BlockContext = { postId: number; postType: string };

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
          per_page: manualPosts.length,
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

  return (
    <div
      {...blockPreviewProps}
      tabIndex={0}
      role="button"
      onClick={handleOnClick}
      onKeyDown={handleOnClick}
      onKeyUp={handleOnClick}
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
  } = query;
  const columns = displayLayout.columns || 1;
  const [activeBlockContextId, setActiveBlockContextId] = useState<number>();

  const isManual = selectionMode === 'manual';

  const { posts, blocks } = useSelect(
    (select) => {
      const { getEntityRecords } = select(coreStore);
      const { getBlocks } = select(blockEditorStore) as unknown as {
        getBlocks: (clientId: string) => unknown[];
      };

      const restQuery = buildRestQuery({
        isManual,
        manualPosts,
        perPage,
        offset,
        order,
        terms,
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
      perPage,
      postType,
      order,
      offset,
      JSON.stringify(terms),
      inclusionType,
      clientId,
    ],
  );

  const blockContexts = useMemo<BlockContext[]>(
    () =>
      posts?.map((post) => ({
        postId: post.id,
        postType: post.type,
      })) ?? [],
    [posts],
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

  if (!posts.length) {
    return <p {...blockProps}>{__('No posts found.', 'mailpoet')}</p>;
  }

  const activeContextId = activeBlockContextId || blockContexts[0]?.postId;

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
