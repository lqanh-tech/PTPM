/**
 * API Client with CSRF Protection
 *
 * Usage:
 *   import { api } from './api-client.js';
 *
 *   const products = await api.get('/api/v1/products');
 *   const result = await api.post('/api/v1/orders', { items: [...] });
 */

class ApiClient {
	constructor(baseUrl = "/api/v1") {
		this.baseUrl = baseUrl;
		this.csrfToken = null;
		this.init();
	}

	/**
	 * Initialize CSRF token from meta tag or cookie.
	 */
	init() {
		// Try to get from meta tag
		const metaTag = document.querySelector('meta[name="csrf-token"]');
		if (metaTag) {
			this.csrfToken = metaTag.getAttribute("content");
		}

		// Try to get from cookie
		if (!this.csrfToken) {
			const match = document.cookie.match(/csrf_token=([^;]+)/);
			if (match) {
				this.csrfToken = match[1];
			}
		}

		// Setup fetch interceptor
		this.setupFetchInterceptor();
	}

	/**
	 * Setup fetch interceptor for CSRF.
	 */
	setupFetchInterceptor() {
		const originalFetch = window.fetch;
		const self = this;

		window.fetch = function (url, options = {}) {
			// Add CSRF token to non-GET requests
			if (options.method && options.method.toUpperCase() !== "GET") {
				options.headers = options.headers || {};
				if (self.csrfToken) {
					options.headers["X-CSRF-TOKEN"] = self.csrfToken;
				}
			}

			// Add default headers
			options.headers = {
				Accept: "application/json",
				"X-Requested-With": "XMLHttpRequest",
				...options.headers,
			};

			// Add credentials
			options.credentials = options.credentials || "same-origin";

			return originalFetch.call(this, url, options);
		};
	}

	/**
	 * Make GET request.
	 */
	async get(endpoint, params = {}) {
		const url = new URL(this.baseUrl + endpoint, window.location.origin);
		Object.keys(params).forEach((key) => {
			if (params[key] !== undefined && params[key] !== null) {
				url.searchParams.append(key, params[key]);
			}
		});

		const response = await fetch(url.toString());
		return this.handleResponse(response);
	}

	/**
	 * Make POST request.
	 */
	async post(endpoint, data = {}) {
		const response = await fetch(this.baseUrl + endpoint, {
			method: "POST",
			headers: {
				"Content-Type": "application/json",
			},
			body: JSON.stringify(data),
		});
		return this.handleResponse(response);
	}

	/**
	 * Make PUT request.
	 */
	async put(endpoint, data = {}) {
		const response = await fetch(this.baseUrl + endpoint, {
			method: "PUT",
			headers: {
				"Content-Type": "application/json",
			},
			body: JSON.stringify(data),
		});
		return this.handleResponse(response);
	}

	/**
	 * Make DELETE request.
	 */
	async delete(endpoint, data = {}) {
		const response = await fetch(this.baseUrl + endpoint, {
			method: "DELETE",
			headers: {
				"Content-Type": "application/json",
			},
			body: JSON.stringify(data),
		});
		return this.handleResponse(response);
	}

	/**
	 * Handle response.
	 */
	async handleResponse(response) {
		const data = await response.json();

		if (!response.ok) {
			const error = new Error(data.message || "Request failed");
			error.status = response.status;
			error.data = data;
			throw error;
		}

		return data;
	}

	/**
	 * Update CSRF token.
	 */
	setCsrfToken(token) {
		this.csrfToken = token;
	}
}

// Create singleton instance
const api = new ApiClient();

// Product API methods
const productsApi = {
	/**
	 * Get products with pagination.
	 */
	async list(params = {}) {
		return api.get("/products", params);
	},

	/**
	 * Get single product.
	 */
	async get(id) {
		return api.get(`/products/${id}`);
	},

	/**
	 * Search products.
	 */
	async search(query, params = {}) {
		return api.get("/search", { q: query, ...params });
	},
};

// Category API methods
const categoriesApi = {
	/**
	 * Get all categories.
	 */
	async list() {
		return api.get("/categories");
	},
};

// Order API methods
const ordersApi = {
	/**
	 * Get orders list.
	 */
	async list(params = {}) {
		return api.get("/orders", params);
	},

	/**
	 * Get single order.
	 */
	async get(id) {
		return api.get(`/orders/${id}`);
	},
};

// Stats API methods
const statsApi = {
	/**
	 * Get dashboard stats.
	 */
	async get() {
		return api.get("/stats");
	},
};

// Export
export { api, productsApi, categoriesApi, ordersApi, statsApi };

// Also make available globally
window.api = api;
window.productsApi = productsApi;
window.categoriesApi = categoriesApi;
window.ordersApi = ordersApi;
window.statsApi = statsApi;
