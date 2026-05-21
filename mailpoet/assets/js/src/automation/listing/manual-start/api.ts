import apiFetch from '@wordpress/api-fetch';
import { normalizeManualStartError } from './helpers';
import type {
  ManualStartErrorResponse,
  ManualStartPreview,
  ManualStartPreviewRequest,
  ManualStartRequest,
  ManualStartResult,
} from './types';

type ManualStartPreviewEnvelope = {
  data: ManualStartPreview;
};

type ManualStartResultEnvelope = {
  data: ManualStartResult;
};

class ManualStartApiError extends Error implements ManualStartErrorResponse {
  code: string;

  data: ManualStartErrorResponse['data'];

  constructor(error: ManualStartErrorResponse) {
    super(error.message);
    this.name = 'ManualStartApiError';
    this.code = error.code;
    this.data = error.data;
  }
}

const manualStartPath = (automationId: number): string =>
  `/automations/${automationId}/manual-start`;

function toThrowableManualStartError(error: unknown): ManualStartApiError {
  return new ManualStartApiError(normalizeManualStartError(error));
}

function createAbortError(): Error {
  const error = new Error('Aborted');
  error.name = 'AbortError';
  return error;
}

function isAbortError(error: unknown): boolean {
  return error instanceof Error && error.name === 'AbortError';
}

export async function previewManualStart(
  automationId: number,
  request: ManualStartPreviewRequest,
  signal?: AbortSignal,
): Promise<ManualStartPreview> {
  try {
    const response = await apiFetch<ManualStartPreviewEnvelope>({
      path: `${manualStartPath(automationId)}/preview`,
      method: 'POST',
      data: request,
      signal,
    });

    if (signal?.aborted) {
      throw createAbortError();
    }

    if (!response?.data) {
      throw toThrowableManualStartError(null);
    }

    return response.data;
  } catch (error) {
    if (isAbortError(error) || signal?.aborted) {
      throw isAbortError(error) ? error : createAbortError();
    }
    throw toThrowableManualStartError(error);
  }
}

export async function startManualStart(
  automationId: number,
  request: ManualStartRequest,
): Promise<ManualStartResult> {
  try {
    const response = await apiFetch<ManualStartResultEnvelope>({
      path: manualStartPath(automationId),
      method: 'POST',
      data: request,
    });

    if (!response?.data) {
      throw toThrowableManualStartError(null);
    }

    return response.data;
  } catch (error) {
    throw toThrowableManualStartError(error);
  }
}
