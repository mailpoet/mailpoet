type NewsLetterType = 'standard';

export type NewsLetter = {
  body: {
    blockDefaults: unknown;
    content: unknown;
    globalStyles: {
      body: CSSStyleDeclaration;
      h1: CSSStyleDeclaration;
      h2: CSSStyleDeclaration;
      h3: CSSStyleDeclaration;
      link: CSSStyleDeclaration;
      text: CSSStyleDeclaration;
      wrapper: CSSStyleDeclaration;
    }
  }
  created_at: string;
  deleted_at: null | string;
  ga_campaign: string;
  hash: string;
  id: string;
  options: {
    isScheduled: string;
    scheduledAt: string;
    disabled?: string;
  };
  parent_id: null | string;
  preheader: string;
  queue: boolean;
  reply_to_address: string;
  reply_to_name: string
  segments: Array<unknown>
  sender_address: string;
  sender_name: string;
  sent_at: null | string;
  status: string;
  subject: string;
  type: NewsLetterType;
  unsubscribe_token: string;
  updated_at: string;
}
