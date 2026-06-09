import { __ } from '@wordpress/i18n';
import { useState, useEffect } from '@wordpress/element';
import { select, dispatch } from '@wordpress/data';
import { store as coreDataStore, useEntityProp } from '@wordpress/core-data';
import { store as editorStore } from '@wordpress/editor';
import apiFetch from '@wordpress/api-fetch';
import { addQueryArgs } from '@wordpress/url';
import { MailPoet } from 'mailpoet';
import { buildRestApiPath } from 'common/dataviews';

export type Segment = {
  id: string;
  name: string;
  type: string;
  subscribers: string;
  deleted_at?: string;
  count_all?: string;
  subscribers_count?: {
    all: string;
    subscribed: string;
    unsubscribed: string;
    unconfirmed: string;
    bounced: string;
    inactive: string;
    trash: string;
  };
};

type ListingResponse<T> = {
  data: {
    items: T[];
    meta: {
      pages: number;
    };
  };
};

export type RecipientType = 'all_customers' | 'segment';

const WOOCOMMERCE_CUSTOMERS_TYPE = 'woocommerce_users';
const EMPTY_ARRAY = [];
const SEGMENTS_PER_PAGE = 100;

async function fetchSegmentPage<T>(
  path: string,
  page: number,
): Promise<ListingResponse<T>> {
  return apiFetch<ListingResponse<T>>({
    url: buildRestApiPath(
      window.mailpoet_segments_api.root,
      addQueryArgs(path, {
        page,
        per_page: SEGMENTS_PER_PAGE,
      }),
    ),
    method: 'GET',
    headers: {
      'X-WP-Nonce': window.mailpoet_segments_api.nonce,
    },
  });
}

async function fetchAllSegments<T>(path: string): Promise<T[]> {
  const items: T[] = [];
  let page = 1;
  let totalPages: number;

  do {
    // eslint-disable-next-line no-await-in-loop
    const response = await fetchSegmentPage<T>(path, page);
    items.push(...response.data.items);
    totalPages = response.data.meta.pages;
    page += 1;
  } while (page <= totalPages);

  return items;
}

export type UseRecipients = {
  isLoadingSegments: boolean;
  isWooActive: boolean;
  recipientType: RecipientType;
  handleRecipientTypeChange: (newType: RecipientType) => void;
  allowedSegments: Segment[];
  selectedAllowedSegments: Segment[];
  setSelectedSegments: (segmentNames: string[]) => void;
  recipientCount: number | null;
  isLoadingRecipientCount: boolean;
  allCustomersSegmentCount: number;
  recipientLabel: string;
  totalRecipientCount: number;
};

/**
 * Encapsulates recipient (list/segment) selection for an email. Lists and
 * dynamic segments — including the WordPress Users system list — are selectable
 * in a token field. When WooCommerce is active, its Customers list is offered
 * via a dedicated "all customers" choice rather than as a regular segment.
 * Shared between the recipients sidebar row and the review & send panel.
 */
export function useRecipients(): UseRecipients {
  const [mailpoetEmailData] = useEntityProp(
    'postType',
    'mailpoet_email',
    'mailpoet_data',
  );

  const selectedSegmentIds = mailpoetEmailData?.segment_ids || EMPTY_ARRAY;

  const [segments, setSegments] = useState<Segment[]>([]);
  const [isLoadingSegments, setIsLoadingSegments] = useState(true);
  const [recipientType, setRecipientType] = useState<RecipientType>('segment');
  const [recipientCount, setRecipientCount] = useState<number | null>(null);
  const [isLoadingRecipientCount, setIsLoadingRecipientCount] = useState(false);

  // Fetch segments on mount.
  useEffect(() => {
    let mounted = true;
    setIsLoadingSegments(true);

    // Fetch both segments and dynamic_segments in parallel
    const segmentsPromise = fetchAllSegments<Segment>('/mailpoet/v1/segments');
    const dynamicSegmentsPromise = fetchAllSegments<Segment>(
      '/mailpoet/v1/dynamic-segments',
    );

    Promise.all([segmentsPromise, dynamicSegmentsPromise])
      .then(([staticSegments, dynamicSegments]) => {
        const allSegments = [
          ...staticSegments,
          ...dynamicSegments.map((segment) => ({
            ...segment,
            type: 'dynamic',
          })),
        ];
        const activeSegments = allSegments.filter(
          (segment: Segment) => !segment.deleted_at,
        );
        if (mounted) {
          setSegments(activeSegments);
        }
      })
      .catch(() => {
        if (mounted) {
          setSegments([]);
        }
      })
      .finally(() => {
        if (mounted) {
          setIsLoadingSegments(false);
        }
      });

    return () => {
      mounted = false;
    };
  }, []);

  const wooCustomersSegment = segments.find(
    (segment) => segment.type === WOOCOMMERCE_CUSTOMERS_TYPE,
  );
  const isWooActive = Boolean(wooCustomersSegment);

  // Reflect the current selection in the recipient type (all customers vs a
  // list/segment selection).
  useEffect(() => {
    if (segments.length === 0) {
      return;
    }

    if (selectedSegmentIds.length === 0) {
      setRecipientType('segment');
      return;
    }

    const firstSegment = segments.find(
      (segment) => segment.id.toString() === selectedSegmentIds[0].toString(),
    );
    if (firstSegment?.type === WOOCOMMERCE_CUSTOMERS_TYPE) {
      setRecipientType('all_customers');
    } else {
      setRecipientType('segment');
    }
  }, [selectedSegmentIds, segments]);

  // Fetch the deduplicated subscriber count for an explicit list/segment
  // selection. The "all customers" count comes from the segment metadata.
  useEffect(() => {
    if (recipientType !== 'segment' || selectedSegmentIds.length === 0) {
      setRecipientCount(null);
      setIsLoadingRecipientCount(false);
      return undefined;
    }

    let mounted = true;
    setIsLoadingRecipientCount(true);

    void MailPoet.Ajax.post({
      api_version: window.mailpoet_api_version,
      endpoint: 'segments',
      action: 'subscriberCount',
      data: {
        segmentIds: selectedSegmentIds,
      },
    })
      .then((response) => {
        if (mounted) {
          setRecipientCount(response?.data?.count as number);
        }
      })
      .catch(() => {
        if (mounted) {
          setRecipientCount(null);
        }
      })
      .always(() => {
        if (mounted) {
          setIsLoadingRecipientCount(false);
        }
      });

    return () => {
      mounted = false;
    };
  }, [recipientType, selectedSegmentIds]);

  const updateSegmentIds = (segmentIds: string[]) => {
    const postId = select(editorStore).getCurrentPostId();
    const editedPost = select(coreDataStore).getEditedEntityRecord(
      'postType',
      'mailpoet_email',
      postId,
    );
    // @ts-expect-error Property 'mailpoet_data' does not exist on type 'Updatable<Attachment<any>>'.
    const mailpoetData = editedPost?.mailpoet_data || {};
    void dispatch(coreDataStore).editEntityRecord(
      'postType',
      'mailpoet_email',
      postId,
      {
        mailpoet_data: {
          ...mailpoetData,
          segment_ids: segmentIds,
        },
      },
    );
  };

  // Everything except the WooCommerce Customers list, which is offered as the
  // dedicated "all customers" choice.
  const allowedSegments = segments.filter(
    (segment) => segment.type !== WOOCOMMERCE_CUSTOMERS_TYPE,
  );

  const setSelectedSegments = (segmentNames: string[]) => {
    const segmentIds = segmentNames
      .map((name) => {
        const segment = allowedSegments.find(
          (_segment) => _segment.name === name,
        );
        return segment ? segment.id : null;
      })
      .filter((id): id is string => id !== null);

    updateSegmentIds(segmentIds);
  };

  const handleRecipientTypeChange = (newType: RecipientType) => {
    setRecipientType(newType);

    if (newType === 'all_customers' && wooCustomersSegment) {
      updateSegmentIds([wooCustomersSegment.id]);
    } else {
      updateSegmentIds([]);
    }
  };

  const allCustomersSegmentCount = parseInt(
    wooCustomersSegment?.subscribers_count?.subscribed || '0',
    10,
  );

  const selectedSegments: Segment[] = selectedSegmentIds
    .map((id: string): Segment | undefined =>
      segments.find(
        (segment: Segment) => segment.id.toString() === id.toString(),
      ),
    )
    .filter((segment): segment is Segment => segment !== undefined);

  const selectedAllowedSegments = selectedSegments.filter(
    (segment) => segment.type !== WOOCOMMERCE_CUSTOMERS_TYPE,
  );

  let recipientLabel: string = __('Select recipients', 'mailpoet');
  if (recipientType === 'all_customers') {
    recipientLabel = __('All customers', 'mailpoet');
  } else if (selectedAllowedSegments.length > 0) {
    recipientLabel = selectedAllowedSegments
      .map((segment) => segment.name)
      .join(', ');
  }

  const totalRecipientCount =
    recipientType === 'all_customers'
      ? allCustomersSegmentCount
      : recipientCount ?? 0;

  return {
    isLoadingSegments,
    isWooActive,
    recipientType,
    handleRecipientTypeChange,
    allowedSegments,
    selectedAllowedSegments,
    setSelectedSegments,
    recipientCount,
    isLoadingRecipientCount,
    allCustomersSegmentCount,
    recipientLabel,
    totalRecipientCount,
  };
}
