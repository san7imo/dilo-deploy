import axios from 'axios';

window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
// Let axios attach XSRF token from the XSRF-TOKEN cookie.

// Always request JSON from the backend
window.axios.defaults.headers.common['Accept'] = 'application/json';
// If using cookies for auth, ensure withCredentials when needed (commented by default)
// window.axios.defaults.withCredentials = true;
