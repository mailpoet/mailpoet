// import { useMemo } from '@wordpress/element';
import { parse } from '@wordpress/blocks';
import { BlockInstance } from '@wordpress/blocks/index';
import { useSelect } from '@wordpress/data';
import { store as blockEditorStore } from '@wordpress/block-editor';
import {
	storeName,
	EmailTemplatePreview,
	TemplatePreview,
	EmailEditorPostType,
} from '../store';

/**
 * We need to merge pattern blocks and template blocks for BlockPreview component.
 * @param templateBlocks - Parsed template blocks
 * @param innerBlocks    - Blocks to be set as content blocks for the template preview
 */
function setPostContentInnerBlocks(
	templateBlocks: BlockInstance[],
	innerBlocks: BlockInstance[]
): BlockInstance[] {
	return templateBlocks.map( ( block: BlockInstance ) => {
		if ( block.name === 'core/post-content' ) {
			return {
				...block,
				name: 'core/group', // Change the name to group to render the innerBlocks
				innerBlocks,
			};
		}
		if ( block.innerBlocks?.length ) {
			return {
				...block,
				innerBlocks: setPostContentInnerBlocks(
					block.innerBlocks,
					innerBlocks
				),
			};
		}
		return block;
	} );
}

const InternalCssThemeCache = {};

/**
 * We are reusing the template CSS and mailpoet theme by fetching the template from
 * the list of email editor available templates.
 * Note: This function may need an update when https://mailpoet.atlassian.net/browse/MAILPOET-6335 is merged
 * @param post
 * @param allTemplates
 */
function generateTemplateCssTheme(
	post: EmailEditorPostType,
	allTemplates: TemplatePreview[] = []
) {
	const contentTemplate = post.template;

	const defaultReturnObject = {
		mailpoet_email_theme: null,
		email_theme_css: '',
	};

	if ( ! contentTemplate ) {
		return defaultReturnObject;
	}

	if ( InternalCssThemeCache[ contentTemplate ] ) {
		return InternalCssThemeCache[ contentTemplate ];
	}

	const postTemplate = allTemplates.find(
		( template ) => template.slug === contentTemplate
	);

	if ( ! postTemplate ) {
		return defaultReturnObject;
	}

	const cssTheme = {
		mailpoet_email_theme:
			postTemplate?.template?.mailpoet_email_theme || null,
		email_theme_css: postTemplate?.template?.email_theme_css || '',
	};

	InternalCssThemeCache[ contentTemplate ] = cssTheme;

	return cssTheme;
}

export function usePreviewTemplates(
	customEmailContent = ''
): TemplatePreview[][] {
	const { templates, patterns, emailPosts } = useSelect( ( select ) => {
		const contentBlockId =
			// @ts-expect-error getBlocksByName is not defined in types
			select( blockEditorStore ).getBlocksByName(
				'core/post-content'
			)?.[ 0 ];
		return {
			templates: select( storeName ).getEmailTemplates(),
			patterns:
				// @ts-expect-error getPatternsByBlockTypes is not defined in types
				select( blockEditorStore ).getPatternsByBlockTypes(
					[ 'core/post-content' ],
					contentBlockId
				),
			emailPosts: select( storeName ).getSentEmailEditorPosts(),
		};
	}, [] );

	if ( ! templates || ( ! patterns.length && ! customEmailContent ) ) {
		return [ [] ];
	}

	let contentPatternBlocksGeneral = null;
	let contentPatternBlocks = null;
	const parsedCustomEmailContent =
		customEmailContent && parse( customEmailContent );

	// If there is a custom email content passed from outside we use it as email content for preview
	// otherwise we pick first suitable from patterns
	if ( parsedCustomEmailContent ) {
		contentPatternBlocksGeneral = parsedCustomEmailContent;
		contentPatternBlocks = parsedCustomEmailContent;
	} else {
		// Pick first pattern that comes from mailpoet and is for general email template
		contentPatternBlocksGeneral = patterns.find(
			( pattern ) =>
				// eslint-disable-next-line @typescript-eslint/no-unsafe-return
				pattern?.templateTypes?.includes( 'email-general-template' )
		)?.blocks as BlockInstance[];

		// Pick first pattern that comes from mailpoet and is for template with header and footer content separated
		contentPatternBlocks = patterns.find(
			( pattern ) =>
				// eslint-disable-next-line @typescript-eslint/no-unsafe-return
				pattern?.templateTypes?.includes( 'email-template' )
		)?.blocks as BlockInstance[];
	}

	const allTemplates = templates.map(
		( template: EmailTemplatePreview ): TemplatePreview => {
			let parsedTemplate = parse( template.content?.raw );
			parsedTemplate = setPostContentInnerBlocks(
				parsedTemplate,
				template.slug === 'email-general'
					? contentPatternBlocksGeneral
					: contentPatternBlocks
			);

			return {
				id: template.id,
				slug: template.slug,
				// eslint-disable-next-line @typescript-eslint/no-unsafe-argument
				previewContentParsed: parsedTemplate,
				emailParsed:
					template.slug === 'email-general'
						? contentPatternBlocksGeneral
						: contentPatternBlocks,
				template,
				category: 'basic', // TODO: This will be updated once template category is implemented
				type: template.type,
			};
		}
	);

	return [
		allTemplates,
		emailPosts?.map( ( post: EmailEditorPostType ) => {
			const parsedPostContent = parse( post.content?.raw );
			const cssThemeData = generateTemplateCssTheme( post, allTemplates );
			return {
				id: post.id,
				slug: post.slug,
				previewContentParsed: parsedPostContent,
				emailParsed: parsedPostContent,
				template: {
					...post,
					title: {
						raw: post?.mailpoet_data?.subject || post.title.raw,
						rendered:
							post?.mailpoet_data?.subject || post.title.rendered, // use MailPoet subject as title
					},
					...cssThemeData,
				},
				category: 'recent',
				type: post.type,
			};
		} ) as unknown as TemplatePreview[],
	];
}
