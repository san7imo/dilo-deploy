import axios from 'axios';

window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
// Include CSRF token from blade meta tag for non-GET requests
const tokenMeta = document.querySelector('meta[name="csrf-token"]');
if (tokenMeta) {
	window.axios.defaults.headers.common['X-CSRF-TOKEN'] = tokenMeta.content;
}

// Always request JSON from the backend
window.axios.defaults.headers.common['Accept'] = 'application/json';
// If using cookies for auth, ensure withCredentials when needed (commented by default)
// window.axios.defaults.withCredentials = true;
