/**
 * Product Filter JavaScript
 * Handles all filter interactions, AJAX requests, and UI updates
 */

class ProductFilter {
  constructor() {
    this.filters = {
      minPrice: 0,
      maxPrice: 100000000,
      colors: [],
      sizes: [],
      minRating: 0,
    };

    this.init();
  }

  init() {
    this.setupEventListeners();
    this.setupRatingFilter();
    this.loadFilterOptions();
    this.updatePriceDisplay();

    // Load filters from URL on page load
    this.loadFiltersFromURL();
  }

  setupEventListeners() {
    // Price range slider
    const priceSliders = document.querySelectorAll(".price-range-input");
    priceSliders.forEach((slider) => {
      slider.addEventListener("input", () => this.handlePriceChange());
    });

    // Color checkboxes
    const colorCheckboxes = document.querySelectorAll(".color-option input");
    colorCheckboxes.forEach((checkbox) => {
      checkbox.addEventListener("change", (e) => this.handleColorChange(e));
    });

    // Size checkboxes
    const sizeCheckboxes = document.querySelectorAll(".size-option input");
    sizeCheckboxes.forEach((checkbox) => {
      checkbox.addEventListener("change", (e) => this.handleSizeChange(e));
    });

    // Clear filters button
    const clearBtn = document.querySelector(".btn-clear-filters");
    if (clearBtn) {
      clearBtn.addEventListener("click", () => this.clearAllFilters());
    }

    // Apply filters button (mobile)
    const applyBtn = document.querySelector(".btn-apply-filters");
    if (applyBtn) {
      applyBtn.addEventListener("click", () => this.applyFilters());
    }

    // Mobile filter toggle
    const toggleBtn = document.querySelector(".filter-toggle-btn");
    if (toggleBtn) {
      toggleBtn.addEventListener("click", () => this.toggleMobileFilter());
    }

    const closeBtn = document.querySelector(".filter-close-btn");
    if (closeBtn) {
      closeBtn.addEventListener("click", () => this.closeMobileFilter());
    }

    const backdrop = document.querySelector(".filter-backdrop");
    if (backdrop) {
      backdrop.addEventListener("click", () => this.closeMobileFilter());
    }

    // Auto-apply filters on desktop (debounced)
    if (window.innerWidth > 992) {
      this.setupAutoApply();
    }
  }

  setupAutoApply() {
    let timeout;
    const filterInputs = document.querySelectorAll(".filter-section input");

    filterInputs.forEach((input) => {
      input.addEventListener("change", () => {
        clearTimeout(timeout);
        timeout = setTimeout(() => {
          this.applyFilters();
        }, 500);
      });
    });
  }

  handlePriceChange() {
    const minSlider = document.getElementById("minPrice");
    const maxSlider = document.getElementById("maxPrice");

    if (minSlider && maxSlider) {
      let minVal = parseInt(minSlider.value);
      let maxVal = parseInt(maxSlider.value);

      // Ensure min is always less than max
      if (minVal > maxVal - 1000000) {
        minVal = maxVal - 1000000;
        minSlider.value = minVal;
      }

      this.filters.minPrice = minVal;
      this.filters.maxPrice = maxVal;

      this.updatePriceDisplay();
    }
  }

  updatePriceDisplay() {
    const minDisplay = document.getElementById("minPriceDisplay");
    const maxDisplay = document.getElementById("maxPriceDisplay");

    if (minDisplay && maxDisplay) {
      minDisplay.textContent = this.formatCurrency(this.filters.minPrice);
      maxDisplay.textContent = this.formatCurrency(this.filters.maxPrice);
    }
  }

  handleColorChange(e) {
    const colorValue = e.target.value;
    const colorOption = e.target.closest(".color-option");

    if (e.target.checked) {
      this.filters.colors.push(colorValue);
      colorOption.classList.add("selected");
    } else {
      this.filters.colors = this.filters.colors.filter((c) => c !== colorValue);
      colorOption.classList.remove("selected");
    }

    this.updateActiveFilters();
  }

  handleSizeChange(e) {
    const sizeValue = e.target.value;

    if (e.target.checked) {
      this.filters.sizes.push(sizeValue);
    } else {
      this.filters.sizes = this.filters.sizes.filter((s) => s !== sizeValue);
    }

    this.updateActiveFilters();
  }

  setupRatingFilter() {
    const ratingCheckboxes = document.querySelectorAll(".rating-checkbox");
    ratingCheckboxes.forEach((checkbox) => {
      checkbox.addEventListener("change", (e) => this.handleRatingChange(e));
    });
  }

  handleRatingChange(e) {
    const ratingValue = parseInt(e.target.value);
    const ratingOption = e.target.closest(".rating-option");

    // Uncheck all other rating checkboxes (single selection)
    document.querySelectorAll(".rating-checkbox").forEach((cb) => {
      if (cb !== e.target) {
        cb.checked = false;
        cb.closest(".rating-option").classList.remove("selected");
      }
    });

    if (e.target.checked) {
      this.filters.minRating = ratingValue;
      ratingOption.classList.add("selected");
    } else {
      this.filters.minRating = 0;
      ratingOption.classList.remove("selected");
    }

    this.updateActiveFilters();
    
    // Auto-apply on desktop
    if (window.innerWidth > 992) {
      this.applyFilters();
    }
  }

  updateActiveFilters() {
    const container = document.getElementById("activeFilters");
    if (!container) return;

    let html = "";
    const hasFilters =
      this.filters.colors.length > 0 || this.filters.sizes.length > 0 || this.filters.minRating > 0;

    if (hasFilters) {
      container.classList.remove("empty");

      // Add color filters
      this.filters.colors.forEach((color) => {
        html += `<span class="filter-tag" data-type="color" data-value="${color}">
                    ${this.getColorName(color)}
                    <i class="fas fa-times" onclick="productFilter.removeFilter('color', '${color}')"></i>
                </span>`;
      });

      // Add size filters
      this.filters.sizes.forEach((size) => {
        html += `<span class="filter-tag" data-type="size" data-value="${size}">
                    ${size}
                    <i class="fas fa-times" onclick="productFilter.removeFilter('size', '${size}')"></i>
                </span>`;
      });

      // Add rating filter
      if (this.filters.minRating > 0) {
        const ratingText = this.filters.minRating === 5 ? "5 sao" : `${this.filters.minRating} sao trở lên`;
        html += `<span class="filter-tag" data-type="rating" data-value="${this.filters.minRating}">
                    <i class="fas fa-star" style="color: #ffc107;"></i> ${ratingText}
                    <i class="fas fa-times" onclick="productFilter.removeFilter('rating', '${this.filters.minRating}')"></i>
                </span>`;
      }
    } else {
      container.classList.add("empty");
    }

    container.innerHTML = html;
  }

  removeFilter(type, value) {
    if (type === "color") {
      this.filters.colors = this.filters.colors.filter((c) => c !== value);
      const checkbox = document.querySelector(
        `.color-option input[value="${value}"]`
      );
      if (checkbox) {
        checkbox.checked = false;
        checkbox.closest(".color-option").classList.remove("selected");
      }
    } else if (type === "size") {
      this.filters.sizes = this.filters.sizes.filter((s) => s !== value);
      const checkbox = document.querySelector(
        `.size-option input[value="${value}"]`
      );
      if (checkbox) {
        checkbox.checked = false;
      }
    } else if (type === "rating") {
      this.filters.minRating = 0;
      const checkbox = document.querySelector(
        `.rating-checkbox[value="${value}"]`
      );
      if (checkbox) {
        checkbox.checked = false;
        checkbox.closest(".rating-option").classList.remove("selected");
      }
    }

    this.updateActiveFilters();
    this.applyFilters();
  }

  clearAllFilters() {
    // Reset price sliders
    const minSlider = document.getElementById("minPrice");
    const maxSlider = document.getElementById("maxPrice");

    if (minSlider && maxSlider) {
      minSlider.value = 0;
      maxSlider.value = 100000000;
      this.filters.minPrice = 0;
      this.filters.maxPrice = 100000000;
      this.updatePriceDisplay();
    }

    // Uncheck all color options
    document
      .querySelectorAll(".color-option input:checked")
      .forEach((checkbox) => {
        checkbox.checked = false;
        checkbox.closest(".color-option").classList.remove("selected");
      });
    this.filters.colors = [];

    // Uncheck all size options
    document
      .querySelectorAll(".size-option input:checked")
      .forEach((checkbox) => {
        checkbox.checked = false;
      });
    this.filters.sizes = [];

    // Uncheck all rating options
    document
      .querySelectorAll(".rating-checkbox:checked")
      .forEach((checkbox) => {
        checkbox.checked = false;
        checkbox.closest(".rating-option").classList.remove("selected");
      });
    this.filters.minRating = 0;

    this.updateActiveFilters();
    this.applyFilters();
  }

  async applyFilters() {
    const productsColumn = document.querySelector(".products-column");
    if (!productsColumn) return;

    // Add loading state
    productsColumn.classList.add("filtering");

    // Update URL with filter parameters
    this.updateURL();

    try {
      // Build query string
      const params = new URLSearchParams();
      params.append("min_price", this.filters.minPrice);
      params.append("max_price", this.filters.maxPrice);

      if (this.filters.colors.length > 0) {
        params.append("colors", this.filters.colors.join(","));
      }

      if (this.filters.sizes.length > 0) {
        params.append("sizes", this.filters.sizes.join(","));
      }

      if (this.filters.minRating > 0) {
        params.append("min_rating", this.filters.minRating);
      }

      // Get current category if any
      const urlParams = new URLSearchParams(window.location.search);
      const reqView = urlParams.get("reqView");
      if (reqView) {
        params.append("reqView", reqView);
      }

      // Make AJAX request - sử dụng đường dẫn tương đối từ lequocanh/index.php
      const response = await fetch(
        `api/filter_products.php?${params.toString()}`
      );
      const data = await response.json();

      if (data.success) {
        this.updateProductDisplay(data.products, data.total);
      } else {
        console.error("Filter error:", data.message);
        this.showError("Có lỗi xảy ra khi lọc sản phẩm");
      }
    } catch (error) {
      console.error("Filter request failed:", error);
      this.showError("Không thể kết nối đến server");
    } finally {
      productsColumn.classList.remove("filtering");
      this.closeMobileFilter();
    }
  }

  updateProductDisplay(products, total) {
    const productGrid = document.querySelector(".product-list-grid");
    if (!productGrid) return;

    // Update results count
    const resultsCount = document.querySelector(".results-count");
    if (resultsCount) {
      resultsCount.textContent = `Hiển thị ${products.length} trong tổng số ${total} sản phẩm`;
    }

    // Clear current products
    productGrid.innerHTML = "";

    if (products.length === 0) {
      productGrid.innerHTML = `
                <div class="col-12">
                    <div class="alert alert-info text-center">
                        <i class="fas fa-info-circle me-2"></i>
                        Không tìm thấy sản phẩm nào phù hợp với bộ lọc của bạn.
                    </div>
                </div>
            `;
      return;
    }

    // Add filtered products
    products.forEach((product) => {
      productGrid.innerHTML += this.createProductCard(product);
    });
  }

  createProductCard(product) {
    // Chuyển đổi giá thành số để kiểm tra chính xác
    const giakhuyenmai = parseFloat(product.giakhuyenmai) || 0;
    const giathamkhao = parseFloat(product.giathamkhao) || 0;

    // Kiểm tra có khuyến mãi: giakhuyenmai phải > 0 và < giá gốc
    const hasDiscount = giakhuyenmai > 0 && giakhuyenmai < giathamkhao;
    const discountPercent = hasDiscount
      ? Math.round(((giathamkhao - giakhuyenmai) / giathamkhao) * 100)
      : 0;

    const imageUrl = product.hinhanh
      ? `./administrator/elements_LQA/mhanghoa/displayImage.php?id=${product.hinhanh}`
      : "./administrator/elements_LQA/img_LQA/no-image.png";

    let badge = "";
    if (product.is_featured == 1) {
      badge =
        '<span class="product-badge badge-featured"><i class="fas fa-star"></i> Nổi bật</span>';
    } else if (product.is_new == 1 || this.isNewProduct(product.created_at)) {
      badge =
        '<span class="product-badge badge-new"><i class="fas fa-sparkles"></i> Mới</span>';
    } else if (hasDiscount) {
      badge =
        '<span class="product-badge badge-sale"><i class="fas fa-fire"></i> Sale</span>';
    }

    // Generate rating HTML
    const avgRating = parseFloat(product.average_rating) || 0;
    const reviewCount = parseInt(product.review_count) || 0;
    let ratingHtml = '';
    
    if (reviewCount > 0) {
      const highRating = avgRating >= 4.5 ? 'high-rating' : '';
      let starsHtml = '';
      for (let i = 1; i <= 5; i++) {
        if (i <= Math.floor(avgRating)) {
          starsHtml += '<i class="fas fa-star" style="color: #ffc107;"></i>';
        } else if (i === Math.ceil(avgRating) && (avgRating - Math.floor(avgRating) >= 0.5)) {
          starsHtml += '<i class="fas fa-star-half-alt" style="color: #ffc107;"></i>';
        } else {
          starsHtml += '<i class="far fa-star" style="color: #ffc107;"></i>';
        }
      }
      ratingHtml = `
        <div class="product-rating ${highRating}" style="display: flex; align-items: center; gap: 4px; margin-bottom: 8px; font-size: 13px;">
          ${starsHtml}
          <span style="font-weight: 600; color: #333; margin-left: 4px;">${avgRating.toFixed(1)}</span>
          <span style="color: #6c757d; font-size: 12px;">(${reviewCount})</span>
        </div>
      `;
    } else {
      ratingHtml = `
        <div class="product-rating" style="display: flex; align-items: center; gap: 4px; margin-bottom: 8px; font-size: 13px; opacity: 0.5;">
          <i class="far fa-star" style="color: #ddd;"></i>
          <i class="far fa-star" style="color: #ddd;"></i>
          <i class="far fa-star" style="color: #ddd;"></i>
          <i class="far fa-star" style="color: #ddd;"></i>
          <i class="far fa-star" style="color: #ddd;"></i>
          <span style="color: #999; font-size: 11px; margin-left: 4px;">Chưa có đánh giá</span>
        </div>
      `;
    }

    return `
            <div class="col">
                <div class="card h-100">
                    ${
                      hasDiscount
                        ? `<span class="discount-badge">-${discountPercent}%</span>`
                        : ""
                    }
                    ${badge}
                    <img src="${imageUrl}" class="card-img-top" alt="${this.escapeHtml(
      product.tenhanghoa
    )}">
                    <div class="card-body">
                        <h5 class="card-title">${this.escapeHtml(
                          product.tenhanghoa
                        )}</h5>
                        ${ratingHtml}
                        <p class="card-text text-danger fw-bold">
                            ${
                              hasDiscount
                                ? `<span style="font-size: 20px;">${this.formatCurrency(
                                    giakhuyenmai
                                  )}</span><br>
                                   <span style="font-size: 14px; color: #999; text-decoration: line-through;">${this.formatCurrency(
                                     giathamkhao
                                   )}</span>`
                                : this.formatCurrency(giathamkhao)
                            }
                        </p>
                        <div class="d-flex justify-content-between align-items-center">
                            <a href="./index.php?reqHanghoa=${
                              product.idhanghoa
                            }" class="btn btn-outline-primary">
                                Xem chi tiết
                            </a>
                            <div class="form-check">
                                <input class="form-check-input compare-checkbox" type="checkbox" 
                                    value="${product.idhanghoa}" id="compare_${
      product.idhanghoa
    }">
                                <label class="form-check-label" for="compare_${
                                  product.idhanghoa
                                }">
                                    So sánh
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
  }

  isNewProduct(createdAt) {
    if (!createdAt) return false;
    const createdDate = new Date(createdAt);
    const thirtyDaysAgo = new Date();
    thirtyDaysAgo.setDate(thirtyDaysAgo.getDate() - 30);
    return createdDate >= thirtyDaysAgo;
  }

  toggleMobileFilter() {
    const filterColumn = document.querySelector(".filter-column");
    const backdrop = document.querySelector(".filter-backdrop");

    if (filterColumn && backdrop) {
      filterColumn.classList.toggle("active");
      backdrop.classList.toggle("active");
      document.body.style.overflow = filterColumn.classList.contains("active")
        ? "hidden"
        : "";
    }
  }

  closeMobileFilter() {
    const filterColumn = document.querySelector(".filter-column");
    const backdrop = document.querySelector(".filter-backdrop");

    if (filterColumn && backdrop) {
      filterColumn.classList.remove("active");
      backdrop.classList.remove("active");
      document.body.style.overflow = "";
    }
  }

  updateURL() {
    const params = new URLSearchParams(window.location.search);

    // Update price params
    params.set("min_price", this.filters.minPrice);
    params.set("max_price", this.filters.maxPrice);

    // Update color params
    if (this.filters.colors.length > 0) {
      params.set("colors", this.filters.colors.join(","));
    } else {
      params.delete("colors");
    }

    // Update size params
    if (this.filters.sizes.length > 0) {
      params.set("sizes", this.filters.sizes.join(","));
    } else {
      params.delete("sizes");
    }

    // Update rating params
    if (this.filters.minRating > 0) {
      params.set("min_rating", this.filters.minRating);
    } else {
      params.delete("min_rating");
    }

    // Update URL without page reload
    const newURL = `${window.location.pathname}?${params.toString()}`;
    window.history.pushState({}, "", newURL);
  }

  loadFiltersFromURL() {
    const params = new URLSearchParams(window.location.search);

    // Load price from URL
    const minPrice = params.get("min_price");
    const maxPrice = params.get("max_price");

    if (minPrice !== null) {
      this.filters.minPrice = parseInt(minPrice);
      const minSlider = document.getElementById("minPrice");
      if (minSlider) minSlider.value = minPrice;
    }

    if (maxPrice !== null) {
      this.filters.maxPrice = parseInt(maxPrice);
      const maxSlider = document.getElementById("maxPrice");
      if (maxSlider) maxSlider.value = maxPrice;
    }

    this.updatePriceDisplay();

    // Load colors from URL
    const colors = params.get("colors");
    if (colors) {
      this.filters.colors = colors.split(",");
      this.filters.colors.forEach((color) => {
        const checkbox = document.querySelector(
          `.color-option input[value="${color}"]`
        );
        if (checkbox) {
          checkbox.checked = true;
          checkbox.closest(".color-option").classList.add("selected");
        }
      });
    }

    // Load sizes from URL
    const sizes = params.get("sizes");
    if (sizes) {
      this.filters.sizes = sizes.split(",");
      this.filters.sizes.forEach((size) => {
        const checkbox = document.querySelector(
          `.size-option input[value="${size}"]`
        );
        if (checkbox) {
          checkbox.checked = true;
        }
      });
    }

    // Load rating from URL
    const minRating = params.get("min_rating");
    if (minRating) {
      this.filters.minRating = parseInt(minRating);
      const checkbox = document.querySelector(
        `.rating-checkbox[value="${minRating}"]`
      );
      if (checkbox) {
        checkbox.checked = true;
        checkbox.closest(".rating-option").classList.add("selected");
      }
    }

    this.updateActiveFilters();

    // Apply filters if any are set
    if (minPrice || maxPrice || colors || sizes || minRating) {
      this.applyFilters();
    }
  }

  async loadFilterOptions() {
    try {
      // Colors are now server-side rendered, no need to load dynamically
      // Just attach event listeners to existing color options
      this.attachColorEventListeners();

      // Get current category if any
      const urlParams = new URLSearchParams(window.location.search);
      const reqView = urlParams.get("reqView");

      const params = reqView ? `?reqView=${reqView}` : "";
      const response = await fetch(`api/get_filter_options.php${params}`);
      const data = await response.json();

      if (data.success) {
        // Filter options are already in HTML, but we could update them dynamically here if needed
        console.log("Available filter options:", data);
      }
    } catch (error) {
      console.error("Failed to load filter options:", error);
    }
  }

  attachColorEventListeners() {
    const container = document.getElementById("colorFilterContainer");
    if (!container) return;

    const colorCheckboxes = container.querySelectorAll(".color-option input");
    colorCheckboxes.forEach((checkbox) => {
      checkbox.addEventListener("change", (e) => this.handleColorChange(e));
    });

    console.log(`Attached event listeners to ${colorCheckboxes.length} color options`);
  }

  async loadDynamicColors() {
    const container = document.getElementById("colorFilterContainer");
    if (!container) {
      console.warn("colorFilterContainer not found!");
      return;
    }

    console.log("Loading dynamic colors...");

    try {
      // Đường dẫn từ root, hoạt động ở mọi trang
      const apiUrl = "/lequocanh/administrator/elements_LQA/mod/getAvailableColors.php";
      console.log("Fetching from:", apiUrl);
      
      const response = await fetch(apiUrl);
      console.log("Response status:", response.status);
      
      const data = await response.json();
      console.log("Color data:", data);

      if (data.success && data.colors.length > 0) {
        let html = "";
        data.colors.forEach((color) => {
          html += `
                        <label class="color-option" title="${color.display} (${color.count} sản phẩm)">
                            <input type="checkbox" value="${color.en}">
                            <div class="color-swatch ${color.css_class}"></div>
                            <i class="fas fa-check checkmark"></i>
                        </label>
                    `;
        });
        container.innerHTML = html;

        // Re-attach event listeners for new checkboxes
        const colorCheckboxes = container.querySelectorAll("input");
        colorCheckboxes.forEach((checkbox) => {
          checkbox.addEventListener("change", (e) => this.handleColorChange(e));
        });

        // Restore selected colors from URL
        const urlParams = new URLSearchParams(window.location.search);
        const colors = urlParams.get("colors");
        if (colors) {
          colors.split(",").forEach((color) => {
            const checkbox = container.querySelector(`input[value="${color}"]`);
            if (checkbox) {
              checkbox.checked = true;
              checkbox.closest(".color-option").classList.add("selected");
            }
          });
        }
      } else {
        container.innerHTML =
          '<p class="text-muted small">Chưa có màu sắc nào</p>';
      }
    } catch (error) {
      console.error("Failed to load dynamic colors:", error);
      container.innerHTML =
        '<p class="text-danger small">Lỗi tải màu sắc</p>';
    }
  }

  formatCurrency(amount) {
    return new Intl.NumberFormat("vi-VN", {
      style: "currency",
      currency: "VND",
    }).format(amount);
  }

  escapeHtml(text) {
    const div = document.createElement("div");
    div.textContent = text;
    return div.innerHTML;
  }

  getColorName(colorCode) {
    const colorNames = {
      red: "Đỏ",
      blue: "Xanh dương",
      green: "Xanh lá",
      yellow: "Vàng",
      orange: "Cam",
      purple: "Tím",
      pink: "Hồng",
      black: "Đen",
      white: "Trắng",
      gray: "Xám",
      brown: "Nâu",
      navy: "Xanh navy",
      gold: "Vàng kim",
      silver: "Bạc",
    };

    return colorNames[colorCode] || colorCode;
  }

  showError(message) {
    // Simple error display - could be enhanced with a toast notification
    alert(message);
  }
}

// Initialize filter when DOM is ready
let productFilter;
if (document.readyState === "loading") {
  document.addEventListener("DOMContentLoaded", () => {
    productFilter = new ProductFilter();
  });
} else {
  productFilter = new ProductFilter();
}
