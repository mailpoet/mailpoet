import type { Config } from '../config.js';
import { ToolError } from '../util/errors.js';

export class CompanionClient {
  constructor(private readonly config: Config) {}

  async request<T>(
    path: string,
    init: {
      method?: string;
      query?: Record<string, string | number | undefined>;
      body?: unknown;
    } = {},
  ): Promise<T> {
    const url = new URL(
      this.config.companionBaseUrl.replace(/\/$/, '') +
        '/' +
        path.replace(/^\//, ''),
    );
    if (init.query) {
      for (const [k, v] of Object.entries(init.query)) {
        if (v !== undefined) url.searchParams.set(k, String(v));
      }
    }

    const headers: Record<string, string> = {
      'X-MailPoet-Dev-Secret': this.config.companionSecret,
      Accept: 'application/json',
    };
    let body: string | undefined;
    if (init.body !== undefined) {
      headers['Content-Type'] = 'application/json';
      body = JSON.stringify(init.body);
    }

    let response: Response;
    try {
      response = await fetch(url, {
        method: init.method ?? 'GET',
        headers,
        body,
      });
    } catch (e) {
      const msg = e instanceof Error ? e.message : String(e);
      throw new ToolError(
        'companion_unreachable',
        `Could not reach MailPoet dev companion at ${url.origin}. Is wp-env running? (${msg})`,
      );
    }

    const text = await response.text();
    if (!response.ok) {
      let parsed: unknown;
      try {
        parsed = JSON.parse(text);
      } catch {
        parsed = text;
      }
      throw new ToolError(
        'companion_error',
        `Companion returned ${response.status}`,
        parsed,
      );
    }

    try {
      return JSON.parse(text) as T;
    } catch (e) {
      throw new ToolError(
        'companion_bad_json',
        'Companion response was not JSON',
        { sample: text.slice(0, 500) },
      );
    }
  }
}
