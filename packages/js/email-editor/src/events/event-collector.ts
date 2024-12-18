import { doAction } from '@wordpress/hooks';
import { EMAIL_STRING, dispatcher } from './event-pipeline';

const eventListenerHandler = ( eventData ) => {
	doAction( 'mailpoet-email-editor-events', eventData.detail );
};

const initEventCollector = () => {
	dispatcher.addEventListener( EMAIL_STRING, eventListenerHandler );
};

window.addEventListener( 'unload', function () {
	dispatcher.removeEventListener( EMAIL_STRING, eventListenerHandler );
} );

export { initEventCollector };
