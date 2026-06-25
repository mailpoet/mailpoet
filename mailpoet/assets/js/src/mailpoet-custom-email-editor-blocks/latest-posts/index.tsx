import './style.scss';
import { registerBlockType } from '@wordpress/blocks';
import type { BlockEditProps } from '@wordpress/blocks';
import { InnerBlocks } from '@wordpress/block-editor';
import { layout } from '@wordpress/icons';
import latestPostsMetadata from './block.json';
import postTemplateMetadata from './post-template/block.json';
import { Edit as LatestPostsEdit } from './edit';
import { Edit as PostTemplateEdit } from './post-template/edit';

const saveInnerBlocks = (): JSX.Element => <InnerBlocks.Content />;

registerBlockType(latestPostsMetadata, {
  edit: LatestPostsEdit as React.ComponentType<
    BlockEditProps<Record<string, unknown>>
  >,
  save: saveInnerBlocks,
});

registerBlockType(postTemplateMetadata, {
  icon: { src: layout },
  edit: PostTemplateEdit as unknown as React.ComponentType<
    BlockEditProps<Record<string, unknown>>
  >,
  save: saveInnerBlocks,
});
