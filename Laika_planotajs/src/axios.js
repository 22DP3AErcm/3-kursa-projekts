import axios from 'axios';

const axiosInstance = axios.create({
    baseURL: 'http://127.0.0.1:8000', // Ensure this URL points to your Laravel backend
    headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
    },
    withCredentials: true, // Ensure credentials are included
    withXSRFToken: true, // Ensure the XSRF-TOKEN is sent
});

axios.interceptors.request.use((config) => {
    config.headers.Authorization = `Bearer ${localStorage.getItem("token")}`;
    return config;
});

export default axiosInstance;