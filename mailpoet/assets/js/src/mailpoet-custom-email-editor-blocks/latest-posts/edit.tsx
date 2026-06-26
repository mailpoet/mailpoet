import type { BlockEditProps } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';
import { useSelect } from '@wordpress/data';
import { store as coreStore } from '@wordpress/core-data';
import {
  useBlockProps,
  useInnerBlocksProps,
  InspectorControls,
} from '@wordpress/block-editor';
import {
  PanelBody,
  BaseControl,
  RangeControl,
  SelectControl,
  ToggleControl,
  FormTokenField,
} from '@wordpress/components';
import { TEMPLATE_BLOCK_NAME } from './constants';

type Term = { id: number; taxonomy: string };

type LatestPostsQuery = {
  selectionMode: 'latest' | 'manual';
  perPage: number;
  postType: string;
  order: string;
  offset: number;
  terms: Term[];
  inclusionType: 'include' | 'exclude';
  posts: number[];
};

type Attributes = {
  query: LatestPostsQuery;
  displayLayout: { columns: number };
};

type TermRecord = { id: number; name: string; taxonomy: string };
type PostRecord = { id: number; title?: { rendered?: string } };

// Lock the single template child so it cannot be removed or moved, while still
// letting users edit (and add to) the blocks composed inside it.
const INNER_TEMPLATE: Array<[string, Record<string, unknown>]> = [
  [TEMPLATE_BLOCK_NAME, { lock: { move: true, remove: true } }],
];

const MODE_OPTIONS: { label: string; value: string }[] = [
  { label: __('Latest posts', 'mailpoet'), value: 'latest' },
  { label: __('Manual selection', 'mailpoet'), value: 'manual' },
];

const POST_TYPE_OPTIONS: { label: string; value: string }[] = [
  { label: __('Posts', 'mailpoet'), value: 'post' },
  { label: __('Pages', 'mailpoet'), value: 'page' },
];

const ORDER_OPTIONS: { label: string; value: string }[] = [
  { label: __('Newest to oldest', 'mailpoet'), value: 'newest' },
  { label: __('Oldest to newest', 'mailpoet'), value: 'oldest' },
];

const postTitle = (post: PostRecord): string =>
  post.title?.rendered?.trim() || __('(no title)', 'mailpoet');

function TermControl({
  value,
  onChange,
}: {
  value: Term[];
  onChange: (terms: Term[]) => void;
}): JSX.Element {
  const records = useSelect((select) => {
    const { getEntityRecords } = select(coreStore);
    const restQuery = { per_page: -1, orderby: 'name', order: 'asc' };
    const categories = (getEntityRecords('taxonomy', 'category', restQuery) ??
      []) as unknown as TermRecord[];
    const tags = (getEntityRecords('taxonomy', 'post_tag', restQuery) ??
      []) as unknown as TermRecord[];
    return [...categories, ...tags];
  }, []);

  const labelFor = (record: TermRecord): string =>
    record.taxonomy === 'post_tag'
      ? `${record.name} ${__('(tag)', 'mailpoet')}`
      : record.name;

  const labelToTerm = new Map<string, Term>();
  const idToLabel = new Map<string, string>();
  records.forEach((record) => {
    const label = labelFor(record);
    labelToTerm.set(label, { id: record.id, taxonomy: record.taxonomy });
    idToLabel.set(`${record.taxonomy}:${record.id}`, label);
  });

  const selectedLabels = value
    .map((term) => idToLabel.get(`${term.taxonomy}:${term.id}`))
    .filter((label): label is string => Boolean(label));

  const handleChange = (tokens: (string | { value: string })[]): void => {
    const terms = tokens
      .map((token) => (typeof token === 'string' ? token : token.value))
      .map((label) => labelToTerm.get(label))
      .filter((term): term is Term => Boolean(term));
    onChange(terms);
  };

  return (
    <FormTokenField
      label={__('Categories & tags', 'mailpoet')}
      value={selectedLabels}
      suggestions={[...labelToTerm.keys()]}
      onChange={handleChange}
      __experimentalExpandOnFocus
      __nextHasNoMarginBottom
    />
  );
}

function ManualPostsControl({
  postType,
  value,
  onChange,
}: {
  postType: string;
  value: number[];
  onChange: (posts: number[]) => void;
}): JSX.Element {
  const [search, setSearch] = useState('');

  const { searchResults, selectedPosts } = useSelect(
    (select) => {
      const { getEntityRecords } = select(coreStore);
      return {
        searchResults: search
          ? ((getEntityRecords('postType', postType, {
              search,
              per_page: 20,
            }) ?? []) as unknown as PostRecord[])
          : ([] as PostRecord[]),
        selectedPosts: value.length
          ? ((getEntityRecords('postType', postType, {
              include: value,
              per_page: value.length,
              orderby: 'include',
            }) ?? []) as unknown as PostRecord[])
          : ([] as PostRecord[]),
      };
    },
    [search, postType, value.join(',')],
  );

  const titleToId = new Map<string, number>();
  [...selectedPosts, ...searchResults].forEach((post) => {
    titleToId.set(postTitle(post), post.id);
  });

  const selectedTitles = value
    .map((id) => selectedPosts.find((post) => post.id === id))
    .filter((post): post is PostRecord => Boolean(post))
    .map(postTitle);

  const handleChange = (tokens: (string | { value: string })[]): void => {
    const ids = tokens
      .map((token) => (typeof token === 'string' ? token : token.value))
      .map((title) => titleToId.get(title))
      .filter((id): id is number => typeof id === 'number');
    onChange(ids);
  };

  return (
    <FormTokenField
      label={__('Choose posts', 'mailpoet')}
      value={selectedTitles}
      suggestions={searchResults.map(postTitle)}
      onInputChange={setSearch}
      onChange={handleChange}
      __experimentalExpandOnFocus
      __nextHasNoMarginBottom
    />
  );
}

export function Edit({
  attributes,
  setAttributes,
}: BlockEditProps<Attributes>): JSX.Element {
  const { query, displayLayout } = attributes;
  const blockProps = useBlockProps();
  const innerBlocksProps = useInnerBlocksProps(blockProps, {
    allowedBlocks: [TEMPLATE_BLOCK_NAME],
    template: INNER_TEMPLATE,
    templateLock: false,
  });

  const updateQuery = (next: Partial<LatestPostsQuery>): void =>
    setAttributes({ query: { ...query, ...next } });

  const isManual = query.selectionMode === 'manual';

  return (
    <>
      <InspectorControls>
        <PanelBody title={__('Posts', 'mailpoet')}>
          <SelectControl
            label={__('Selection', 'mailpoet')}
            value={query.selectionMode}
            options={MODE_OPTIONS}
            onChange={(value) =>
              updateQuery({
                selectionMode: value === 'manual' ? 'manual' : 'latest',
              })
            }
            __nextHasNoMarginBottom
          />
          <SelectControl
            label={__('Content type', 'mailpoet')}
            value={query.postType}
            options={POST_TYPE_OPTIONS}
            onChange={(value) => updateQuery({ postType: value })}
            __nextHasNoMarginBottom
          />
          {isManual && (
            <ManualPostsControl
              postType={query.postType}
              value={query.posts ?? []}
              onChange={(posts) => updateQuery({ posts })}
            />
          )}
          {!isManual && (
            <>
              <RangeControl
                label={__('Number of posts', 'mailpoet')}
                value={query.perPage}
                min={1}
                max={10}
                onChange={(value) => updateQuery({ perPage: value ?? 1 })}
                __nextHasNoMarginBottom
              />
              <SelectControl
                label={__('Order', 'mailpoet')}
                value={query.order}
                options={ORDER_OPTIONS}
                onChange={(value) => updateQuery({ order: value })}
                __nextHasNoMarginBottom
              />
              <BaseControl>
                <TermControl
                  value={query.terms ?? []}
                  onChange={(terms) => updateQuery({ terms })}
                />
              </BaseControl>
              <ToggleControl
                label={__('Exclude selected categories & tags', 'mailpoet')}
                checked={query.inclusionType === 'exclude'}
                onChange={(exclude) =>
                  updateQuery({
                    inclusionType: exclude ? 'exclude' : 'include',
                  })
                }
                __nextHasNoMarginBottom
              />
            </>
          )}
        </PanelBody>
        <PanelBody title={__('Layout', 'mailpoet')}>
          <RangeControl
            label={__('Columns', 'mailpoet')}
            value={displayLayout.columns}
            min={1}
            max={2}
            onChange={(value) =>
              setAttributes({ displayLayout: { columns: value ?? 1 } })
            }
          />
        </PanelBody>
      </InspectorControls>
      <div {...innerBlocksProps} />
    </>
  );
}
