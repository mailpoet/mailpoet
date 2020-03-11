export type Settings = {
  sender: {
    name: string
    address: string
  }
  reply_to: {
    name: string
    address: string
  }
  subscribe: {
    on_comment: {
      enabled: '1' | '0'
      label: string
      segments: string[]
    }
    on_register: {
      enabled: '1' | '0'
      label: string
      segments: string[]
    }
  }
  subscription: {
    pages: {
      manage: string
      unsubscribe: string
      confirmation: string
      captcha: string
    }
    segments: string[]
  }
  stats_notifications: {
    enabled: '0' | '1'
    automated: '0' | '1'
    address: string
  }
  subscriber_email_notification: {
    enabled: '0' | '1'
    address: string
  }
  // ...
}

type Segment = {
  id: string
  name: string
  subscribers: string
}
type Page = {
  id: number
  title: string
  url: {
    unsubscribe: string
    manage: string
    confirm: string
  }
}
export type State = {
  data: Settings
  segments: Segment[]
  pages: Page[]
  flags: {
    woocommerce: boolean
    newUser: boolean
    error: boolean
  }
  save: {
    inProgress: boolean
    error: any
  }
}

export type Action =
  | { type: 'SET_SETTING'; value: any; path: string[] }
  | { type: 'SET_ERROR_FLAG'; value: boolean }
  | { type: 'SAVE_STARTED' }
  | { type: 'SAVE_DONE' }
  | { type: 'SAVE_FAILED'; error: any }
