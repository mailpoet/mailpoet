import './style.scss';
import { registerBlockType } from '@wordpress/blocks';
import type { BlockEditProps } from '@wordpress/blocks';
import { InnerBlocks } from '@wordpress/block-editor';
import { layout, postContent, postList } from '@wordpress/icons';
import latestPostsMetadata from './block.json';
import postTemplateMetadata from './post-template/block.json';
import postContentMetadata from './post-content/block.json';
import { Edit as LatestPostsEdit } from './edit';
import { Edit as PostTemplateEdit } from './post-template/edit';
import { Edit as PostContentEdit } from './post-content/edit';

const saveInnerBlocks = (): JSX.Element => <InnerBlocks.Content />;

registerBlockType(latestPostsMetadata, {
  icon: { src: postList },
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

registerBlockType(postContentMetadata, {
  icon: { src: postContent },
  edit: PostContentEdit as unknown as React.ComponentType<
    BlockEditProps<Record<string, unknown>>
  >,
  save: () => null,
});
