import { __ } from '@wordpress/i18n';
import { MailPoet } from 'mailpoet';
import { isErrorResponse } from 'ajax';

export type ClassicTemplate = {
  id: number;
  name: string;
  categories: string;
  thumbnail: string | null;
  readonly: boolean;
  updated_at: string | null;
};

function request<T>(action: string, data: object = {}): Promise<T> {
  return new Promise((resolve, reject) => {
    void MailPoet.Ajax.post({
      api_version: window.mailpoet_api_version,
      endpoint: 'newsletterTemplates',
      action,
      data,
    })
      .done((response) => resolve(response.data as T))
      .fail((response) => {
        const message = isErrorResponse(response)
          ? response.errors.map((error) => error.message).join(' ')
          : __(
              'The action could not be completed. Please try again.',
              'mailpoet',
            );
        reject(new Error(message));
      });
  });
}

function parseCategories(value: string): string[] {
  try {
    const categories: unknown = JSON.parse(value);
    return Array.isArray(categories) ? categories.map(String) : [];
  } catch {
    return [];
  }
}

// Built-in templates store [type, "all"|"blank"], saved ones ["saved", type].
export function getClassicTemplateType(template: ClassicTemplate): string {
  const categories = parseCategories(template.categories);
  return (template.readonly ? categories[0] : categories[1]) ?? 'standard';
}

export async function getClassicTemplates(): Promise<ClassicTemplate[]> {
  const templates = await request<ClassicTemplate[]>('getAll');
  // Recently sent templates are managed by MailPoet, not by the user.
  return templates.filter(
    (template) =>
      template.readonly || parseCategories(template.categories)[0] === 'saved',
  );
}

export function duplicateClassicTemplate(id: number): Promise<ClassicTemplate> {
  return request('duplicate', { id });
}

export function renameClassicTemplate(
  id: number,
  name: string,
): Promise<ClassicTemplate> {
  return request('rename', { id, name });
}

export function deleteClassicTemplate(id: number): Promise<void> {
  return request('delete', { id });
}

export async function getClassicTemplateEditUrl(id: number): Promise<string> {
  const data = await request<{ editor_url: string }>('edit', { id });
  return data.editor_url;
}

export function createClassicTemplate(name: string): Promise<ClassicTemplate> {
  return request('create', { name });
}

const importableTypes = ['standard', 'welcome', 'notification'];

function readTemplateFile(text: string): Record<string, unknown> | null {
  try {
    const data: unknown = JSON.parse(text);
    return data && typeof data === 'object' && 'body' in data
      ? (data as Record<string, unknown>)
      : null;
  } catch {
    return null;
  }
}

// Saves a .json file exported from MailPoet as a new template.
export async function importClassicTemplate(
  file: File,
): Promise<ClassicTemplate> {
  const data = readTemplateFile(await file.text());
  if (!data) {
    throw new Error(
      __(
        'This template file appears to be damaged. Please try another one.',
        'mailpoet',
      ),
    );
  }
  const type =
    parseCategories(String(data.categories ?? '[]')).find((category) =>
      importableTypes.includes(category),
    ) ?? 'standard';
  const thumbnails = Object.fromEntries(
    ['thumbnail', 'thumbnail_data']
      .filter((key) => typeof data[key] === 'string')
      .map((key) => [key, data[key]]),
  );
  return request('save', {
    name: typeof data.name === 'string' ? data.name : file.name,
    body: JSON.stringify(data.body),
    categories: JSON.stringify(['saved', type]),
    ...thumbnails,
  });
}
