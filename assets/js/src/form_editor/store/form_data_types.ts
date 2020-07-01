type PlacementStyles = {
  width: {
    unit: string
    value: number
  }
}

export type FormSettingsType = {
  alignment: string
  backgroundImageDisplay?: string
  backgroundImageUrl?: string
  belowPostStyles: PlacementStyles
  borderColor?: string
  borderRadius: number
  borderSize: number
  errorValidationColor?: string
  fixedBarFormDelay: number
  fixedBarFormPosition: string
  fixedBarStyles: PlacementStyles
  fontFamily?: string
  formPadding: number
  inputPadding: number
  otherStyles: PlacementStyles
  placeFixedBarFormOnAllPages: boolean
  placeFixedBarFormOnAllPosts: boolean
  placeFormBellowAllPages: boolean
  placeFormBellowAllPosts: boolean
  placePopupFormOnAllPages: boolean
  placePopupFormOnAllPosts: boolean
  placeSlideInFormOnAllPages: boolean
  placeSlideInFormOnAllPosts: boolean
  popupFormDelay: number
  popupStyles: PlacementStyles
  segments: Array<string>
  slideInFormDelay: number
  slideInFormPosition: string
  slideInStyles: PlacementStyles
  successValidationColor?: string
};

export type InputBlockStyles = {
  fullWidth: boolean
  inheritFromTheme: boolean
  bold?: boolean
  backgroundColor?: string
  borderSize?: number
  fontSize?: number
  fontColor?: string
  borderRadius?: number
  borderColor?: string
  padding?: number
  fontFamily?: string
}
