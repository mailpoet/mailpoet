export function Icon(): JSX.Element {
  return (
    <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
      {/* Calendar base */}
      <path
        fillRule="evenodd"
        clipRule="evenodd"
        d="M19 3H18V1H16V3H8V1H6V3H5C3.89 3 3 3.89 3 5V19C3 20.1 3.89 21 5 21H19C20.1 21 21 20.1 21 19V5C21 3.89 20.1 3 19 3ZM19 19H5V8H19V19Z"
      />
      {/* Calendar grid lines */}
      <path
        fillRule="evenodd"
        clipRule="evenodd"
        d="M7 10H9V12H7V10ZM11 10H13V12H11V10ZM15 10H17V12H15V10ZM7 14H9V16H7V14ZM11 14H13V16H11V14ZM15 14H17V16H15V14Z"
      />
      {/* Check mark to indicate "created/confirmed" */}
      <path
        fillRule="evenodd"
        clipRule="evenodd"
        d="M9 16L7 14L8.41 12.59L9 13.17L10.59 11.58L12 13L9 16Z"
      />
    </svg>
  );
}
