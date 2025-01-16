/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { addQueryArgs } from '@wordpress/url';
import apiFetch from '@wordpress/api-fetch';
import { AutoCompleter } from '@woocommerce/components/build-types/search/autocompleters';

const tagAutoCompleter: AutoCompleter = {
  name: 'tags',
  className: 'woocommerce-search__product-result',
  options(search) {
    const query = search
      ? {
          search,
          per_page: 10,
          orderby: 'count',
        }
      : {};
    return apiFetch({
      path: addQueryArgs('/wp/v2/product_tag', query),
    });
  },
  isDebounced: true,
  getOptionIdentifier(tag) {
    return tag.id as number;
  },
  getOptionKeywords(tag) {
    return [tag.name] as string[];
  },
  getFreeTextOptions(query) {
    const label = (
      <span key="name" className="woocommerce-search__result-name">
        {__('Search results', 'mailpoet')}
      </span>
    );
    const titleOption = {
      key: 'title',
      label,
      value: { id: query, name: query },
    };

    return [titleOption];
  },
  getOptionLabel(tag) {
    return (
      <span
        key="name"
        className="woocommerce-search__result-name"
        aria-label={tag.name}
      >
        {tag.name}
      </span>
    );
  },
  // This is slightly different than gutenberg/Autocomplete, we don't support different methods
  // of replace/insertion, so we can just return the value.
  getOptionCompletion(tag) {
    const value = {
      key: tag.id,
      label: tag.name,
    };
    return value;
  },
};

export default tagAutoCompleter;
