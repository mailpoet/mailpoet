const wp = window.wp;
const apiFetch = wp.apiFetch;
const { addQueryArgs } = wp.url;

const NEWSLETTER_EMBEDS_PATH = '/mailpoet/v1/newsletter-embeds';
const SELECTOR_LIMIT = 20;

async function getNewsletterEmbeds(search) {
  const response = await apiFetch({
    path: addQueryArgs(NEWSLETTER_EMBEDS_PATH, {
      search,
      limit: SELECTOR_LIMIT,
    }),
    method: 'GET',
  });

  if (!response || !response.data || !Array.isArray(response.data.items)) {
    return [];
  }

  return response.data.items;
}

export { getNewsletterEmbeds };
