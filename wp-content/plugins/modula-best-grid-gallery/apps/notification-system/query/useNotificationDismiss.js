import { useMutation } from '@tanstack/react-query';
import apiFetch from '@wordpress/api-fetch';

const dismissNotice = async ( id ) => {
	const response = await apiFetch( {
		path: '/modula-api/v1/notifications/' + id,
		method: 'DELETE',
	} );
	return response;
};

export const useNotificationDismiss = () => {

	return useMutation( {
		mutationFn: dismissNotice,
	} );
};