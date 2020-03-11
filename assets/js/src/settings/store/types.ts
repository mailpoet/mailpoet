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
    enabled: boolean
    automated: boolean
    address: string
  }
  subscriber_email_notification: {
    enabled: boolean
    address: string
  }
  // ...
}

type Segment = {
  id: string
  name: string
  subscribers: string
}

export type State = {
  data: Settings
  segments: Segment[]
  flags: {
    woocommerce: boolean
    newUser: boolean
  }
  save: {
    inProgress: boolean
    error: any
  }
}

export type Action =
  | { type: 'SET_SETTING'; value: any; path: string[] }
  | { type: 'SAVE_STARTED' }
  | { type: 'SAVE_DONE' }
  | { type: 'SAVE_FAILED'; error: any }
