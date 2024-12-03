import { Button } from '@wordpress/components';
import { info } from '@wordpress/icons';
import { __ } from '@wordpress/i18n';
import { TemplateCategory } from '../../store';

type Props = {
	selectedCategory?: TemplateCategory;
	templateCategories: Array< { name: TemplateCategory; label: string } >;
	onClickCategory: ( name: TemplateCategory ) => void;
};

export function TemplateCategoriesListSidebar( {
	selectedCategory,
	templateCategories,
	onClickCategory,
}: Props ) {
	const baseClassName = 'block-editor-block-patterns-explorer__sidebar';
	return (
		<div className={ baseClassName }>
			<div className={ `${ baseClassName }__categories-list` }>
				{ templateCategories.map( ( { name, label } ) => {
					const isRecentButton = name === 'recent';
					return (
						<Button
							key={ name }
							label={ label }
							className={ `${ baseClassName }__categories-list__item` }
							isPressed={ selectedCategory === name }
							onClick={ () => {
								onClickCategory( name );
							} }
							showTooltip={ isRecentButton }
							tooltipPosition="top"
							aria-label="This is some content"
							icon={ isRecentButton ? info : null }
							iconPosition="right"
							describedBy={
								isRecentButton
									? __(
											'Templates created on the legacy editor will not appear here',
											'mailpoet'
									  )
									: null
							}
						>
							{ label }
						</Button>
					);
				} ) }
			</div>
		</div>
	);
}
