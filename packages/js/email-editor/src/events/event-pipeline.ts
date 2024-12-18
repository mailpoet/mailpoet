const EMAIL_STRING = 'email-editor-events';

const dispatcher = new EventTarget();

const recordEvent = ( name: string, data = {} ) => {
	const recordedData = typeof data !== 'object' ? { data } : data;

	const eventData = {
		name: `${ EMAIL_STRING }_${ name }`,
		...recordedData,
	};

	dispatcher.dispatchEvent(
		new CustomEvent( EMAIL_STRING, { detail: eventData } )
	);
};

export { recordEvent, EMAIL_STRING, dispatcher };
