import type { Config } from "../config.js";
import { ToolError } from "../util/errors.js";

export interface MailpitMessageSummary {
  ID: string;
  MessageID: string;
  From: { Name: string; Address: string };
  To: { Name: string; Address: string }[];
  Cc?: { Name: string; Address: string }[];
  Subject: string;
  Created: string;
  Size: number;
  Attachments: number;
  Snippet?: string;
}

export interface MailpitListResponse {
  messages: MailpitMessageSummary[];
  messages_count: number;
  total: number;
  unread: number;
  start: number;
  tags: string[];
}

export interface MailpitMessageDetail {
  ID: string;
  MessageID: string;
  From: { Name: string; Address: string };
  To: { Name: string; Address: string }[];
  Cc?: { Name: string; Address: string }[];
  Bcc?: { Name: string; Address: string }[];
  Subject: string;
  Date: string;
  Text: string;
  HTML: string;
  Size: number;
  Attachments: { FileName: string; ContentType: string; Size: number; PartID: string }[];
  Headers?: Record<string, string[]>;
}

export class MailpitClient {
  constructor(private readonly config: Config) {}

  private async request<T>(path: string, init: RequestInit = {}, parseJson = true): Promise<T> {
    const url = this.config.mailpitUrl.replace(/\/$/, "") + path;
    let response: Response;
    try {
      response = await fetch(url, { ...init, headers: { Accept: "application/json", ...(init.headers ?? {}) } });
    } catch (e) {
      const msg = e instanceof Error ? e.message : String(e);
      throw new ToolError(
        "mailpit_unreachable",
        `Mailpit not reachable at ${this.config.mailpitUrl}. Is the SMTP catcher running? (${msg})`,
      );
    }
    if (!response.ok) {
      const text = await response.text();
      throw new ToolError("mailpit_error", `Mailpit returned ${response.status}`, text.slice(0, 500));
    }
    if (!parseJson) return undefined as unknown as T;
    return (await response.json()) as T;
  }

  async listMessages(params: { start?: number; limit?: number; query?: string }): Promise<MailpitListResponse> {
    const search = new URLSearchParams();
    if (params.query) search.set("query", params.query);
    if (params.start !== undefined) search.set("start", String(params.start));
    if (params.limit !== undefined) search.set("limit", String(params.limit));
    const path = params.query
      ? `/api/v1/search?${search.toString()}`
      : `/api/v1/messages${search.size ? "?" + search.toString() : ""}`;
    return this.request(path);
  }

  async getMessage(id: string): Promise<MailpitMessageDetail> {
    return this.request(`/api/v1/message/${encodeURIComponent(id)}`);
  }

  async deleteAll(): Promise<void> {
    await this.request("/api/v1/messages", { method: "DELETE" }, false);
  }
}
