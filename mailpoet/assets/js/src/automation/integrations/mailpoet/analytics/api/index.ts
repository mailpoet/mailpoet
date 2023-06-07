import apiFetch from '@wordpress/api-fetch';
import { dispatch, select } from '@wordpress/data';
import { getCurrentDates } from '@woocommerce/date';
import { addQueryArgs } from '@wordpress/url';
import { api } from '../config';
import { storeName } from '../store/constants';
import { Query, Section, SectionData } from '../store/types';

export type ApiError = {
  code?: string;
  message?: string;
  data?: {
    status?: number;
    details?: Error;
    params?: Record<string, string>;
    errors?: unknown[];
  };
};

export const initializeApi = () => {
  const apiUrl = `${api.root}/mailpoet/v1/`;
  apiFetch.use(apiFetch.createRootURLMiddleware(apiUrl));
  apiFetch.use(apiFetch.createNonceMiddleware(api.nonce));
};

export async function updateSection(
  section: Section,
  queryParam: Query | undefined = undefined,
) {
  const query = queryParam ?? select(storeName).getCurrentQuery();

  const defaultDateRange = 'period=month&compare=previous_year';

  const { primary: primaryDate, secondary: secondaryDate } = getCurrentDates(
    query,
    defaultDateRange,
  );

  const dates = {
    primary: {
      after: primaryDate.after.toDate().toISOString(),
      before: primaryDate.before.toDate().toISOString(),
    },
    secondary: {
      after: secondaryDate.after.toDate().toISOString(),
      before: secondaryDate.before.toDate().toISOString(),
    },
  };

  const id = select(storeName).getAutomation().id;
  dispatch(storeName).setSectionData({
    ...section,
    data: undefined,
  });

  const path = addQueryArgs(section.endpoint, { id, query: dates });
  const method = 'GET';
  const response: {
    data: SectionData;
  } = await apiFetch({
    path,
    method,
  });

  dispatch(storeName).setSectionData({
    ...section,
    data: response.data,
  });
}
