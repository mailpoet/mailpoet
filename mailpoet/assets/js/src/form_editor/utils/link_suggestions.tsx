import apiFetch from '@wordpress/api-fetch';
import { addQueryArgs } from '@wordpress/url';
import { decodeEntities } from '@wordpress/html-entities';
import MailPoet from 'mailpoet';
import type { WP_REST_API_Search_Results } from 'wp-types';

/**
 * This is based on function used in post editor
 * @see https://github.com/WordPress/gutenberg/blob/5941c924425f1d09bc724652cc316f5df90d4d32/packages/editor/src/components/provider/index.js#L31
 */
const fetchLinkSuggestions = async (search: string, { perPage = 20 } = {}) => {
  const posts: WP_REST_API_Search_Results = await apiFetch({
    path: addQueryArgs('/wp/v2/search', {
      search,
      per_page: perPage,
      type: 'post',
    }),
  });

  if (!Array.isArray(posts)) {
    return [];
  }

  return posts.map((post) => ({
    id: post.id,
    url: post.url,
    title: decodeEntities(post.title) || `(${MailPoet.I18n.t('noName')})`,
    type: post.subtype || post.type,
  }));
};

export default fetchLinkSuggestions;
