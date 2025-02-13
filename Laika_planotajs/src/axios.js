import axios from 'axios';

const axiosInstance = axios.create({
    baseURL: 'http://127.0.0.1:8000', // Ensure this URL points to your Laravel backend
    headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
    },
    withCredentials: true, // Ensure credentials are included
});

axiosInstance.interceptors.request.use(config => {
    const tokenElement = document.head.querySelector('meta[name="csrf-token"]');
    if (tokenElement) {
        config.headers['X-CSRF-TOKEN'] = tokenElement.getAttribute('content');
    }
    return config;
}, error => {
    return Promise.reject(error);
});

export default axiosInstance;