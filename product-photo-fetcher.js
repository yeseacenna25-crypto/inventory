/**
 * Product Photo Fetcher Utility
 * Handles fetching and displaying product photos from different storage methods
 */

class ProductPhotoFetcher {
    constructor(baseUrl = './') {
        this.baseUrl = baseUrl;
        this.cache = new Map();
    }

    /**
     * Fetch all products with photos
     */
    async fetchAllProducts(search = '') {
        try {
            const url = `${this.baseUrl}fetch_product_photos.php?action=list${search ? '&search=' + encodeURIComponent(search) : ''}`;
            const response = await fetch(url);
            const data = await response.json();
            
            if (data.success) {
                return data.products;
            } else {
                throw new Error(data.error || 'Failed to fetch products');
            }
        } catch (error) {
            console.error('Error fetching products:', error);
            throw error;
        }
    }

    /**
     * Fetch single product with photo
     */
    async fetchProduct(productId) {
        try {
            if (this.cache.has(productId)) {
                return this.cache.get(productId);
            }

            const url = `${this.baseUrl}fetch_product_photos.php?action=single&id=${productId}`;
            const response = await fetch(url);
            const data = await response.json();
            
            if (data.success) {
                this.cache.set(productId, data.product);
                return data.product;
            } else {
                throw new Error(data.error || 'Product not found');
            }
        } catch (error) {
            console.error('Error fetching product:', error);
            throw error;
        }
    }

    /**
     * Display product photo in an img element
     */
    displayPhoto(product, imgElement, fallbackSrc = 'ASSETS/icon.jpg') {
        if (!imgElement) return;

        const photo = product.photo;
        
        if (photo.type === 'base64' && photo.data) {
            imgElement.src = photo.data;
            imgElement.alt = product.name;
        } else if (photo.type === 'path' && !photo.error) {
            imgElement.src = photo.data;
            imgElement.alt = product.name;
        } else {
            imgElement.src = fallbackSrc;
            imgElement.alt = 'No image available';
        }

        // Add error handling
        imgElement.onerror = function() {
            this.src = fallbackSrc;
            this.alt = 'Image not found';
        };
    }

    /**
     * Create product card HTML with photo
     */
    createProductCard(product) {
        const stockStatus = this.getStockStatus(product.quantity);
        
        return `
            <div class="col-md-4 mb-4">
                <div class="card h-100 shadow-sm product-card" data-product-id="${product.id}">
                    <span class="position-absolute top-0 end-0 mt-2 me-2 badge ${stockStatus.badgeClass}">
                        ${stockStatus.text}
                    </span>
                    
                    <div class="card-img-container d-flex align-items-center justify-content-center" style="height: 200px; overflow: hidden;">
                        <img class="card-img-top product-photo" 
                             style="object-fit: cover; max-height: 100%; width: auto;"
                             alt="${product.name}">
                    </div>
                    
                    <div class="card-body d-flex flex-column">
                        <h5 class="card-title">${this.escapeHtml(product.name)}</h5>
                        <p class="card-text flex-grow-1" style="max-height: 80px; overflow: hidden;">
                            ${this.escapeHtml(product.description)}
                        </p>
                        <div class="mt-auto">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="text-dark fw-semibold mb-0">₱${product.price}</h5>
                                <span class="text-muted">Stock: ${product.quantity}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }

    /**
     * Get stock status for product
     */
    getStockStatus(quantity) {
        if (quantity <= 0) {
            return { text: 'Out of Stock', badgeClass: 'bg-danger' };
        } else if (quantity <= 10) {
            return { text: 'Low Stock', badgeClass: 'bg-warning text-dark' };
        } else {
            return { text: 'In Stock', badgeClass: 'bg-success' };
        }
    }

    /**
     * Escape HTML to prevent XSS
     */
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    /**
     * Render products in a container
     */
    async renderProducts(containerId, search = '') {
        const container = document.getElementById(containerId);
        if (!container) {
            console.error('Container not found:', containerId);
            return;
        }

        try {
            // Show loading
            container.innerHTML = '<div class="col-12 text-center"><div class="spinner-border" role="status"><span class="visually-hidden">Loading...</span></div></div>';
            
            const products = await this.fetchAllProducts(search);
            
            if (products.length === 0) {
                container.innerHTML = '<div class="col-12 text-center"><div class="alert alert-info"><i class="fa fa-info-circle me-2"></i> No products found.</div></div>';
                return;
            }

            container.innerHTML = products.map(product => this.createProductCard(product)).join('');
            
            // Load photos for each product card
            products.forEach(product => {
                const card = container.querySelector(`[data-product-id="${product.id}"]`);
                if (card) {
                    const img = card.querySelector('.product-photo');
                    if (img) {
                        this.displayPhoto(product, img);
                    }
                }
            });

        } catch (error) {
            console.error('Error rendering products:', error);
            container.innerHTML = '<div class="col-12 text-center"><div class="alert alert-danger"><i class="fa fa-exclamation-triangle me-2"></i> Error loading products.</div></div>';
        }
    }
}

// Create global instance
window.productPhotoFetcher = new ProductPhotoFetcher();
