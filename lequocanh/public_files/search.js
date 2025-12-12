/**
 * Search Suggestions Script
 * Handles autocomplete search functionality for product search
 */
console.log("Search suggestions script loaded"); // Debug log to verify script loading

$(document).ready(function () {
  // Variables
  const searchInput = $("#searchInput");
  const searchResults = $("#searchResults");
  let searchTimeout;

  console.log(
    "Search input element:",
    searchInput.length ? "Found" : "Not found"
  ); // Debug log

  // Test if search_suggestions.php is accessible
  function testSearchEndpoint() {
    console.log("Testing search_suggestions.php endpoint...");
    $.ajax({
      url: "search_suggestions.php",
      method: "GET",
      data: { query: "test" },
      dataType: "json",
      success: function (data) {
        console.log("Search endpoint test successful. Response:", data);
      },
      error: function (xhr, status, error) {
        console.error("Search endpoint test failed:", error);
        console.log("XHR status:", status);
        console.log("XHR response:", xhr.responseText);
        // Hiển thị lỗi trong console để debug
        console.error("Detailed error information:", {
          error: error,
          status: status,
          responseText: xhr.responseText,
          readyState: xhr.readyState,
          statusText: xhr.statusText
        });
      },
    });
  }

  // Run the test
  testSearchEndpoint();

  // Function to handle search input
  function handleSearchInput() {
    const term = searchInput.val().trim();
    console.log("Search term:", term); // Debug log

    // Clear previous results
    searchResults.empty();

    // Clear any pending timeout
    clearTimeout(searchTimeout);

    // Only search if term is at least 2 characters
    if (term.length < 2) {
      searchResults.hide();
      return;
    }

    // Set a small timeout to prevent searching on every keystroke
    searchTimeout = setTimeout(function () {
      console.log("Executing search for:", term); // Debug log

      // Show loading indicator
      searchResults.html(
        '<div class="text-center p-2"><i class="fas fa-spinner fa-spin"></i> Đang tìm kiếm...</div>'
      );
      searchResults.show();

      // Ajax request to get search suggestions
      $.ajax({
        url: "search_suggestions.php",
        method: "GET",
        data: { query: term },
        dataType: "json",
        success: function (data) {
          console.log("Search results received:", data); // Debug log

          // Clear previous results
          searchResults.empty();

          if (!data || data.length === 0) {
            searchResults.html(
              '<div class="text-center p-3">Không tìm thấy sản phẩm nào</div>'
            );
            searchResults.show();
            return;
          }

          // Create results list
          const resultsList = $('<div class="search-results-list"></div>');

          // Add each item to results
          data.forEach(function (item) {
            console.log("Processing item:", item); // Debug log

            // Đảm bảo các thuộc tính tồn tại
            const id = item.id || '';
            const name = item.name || 'Sản phẩm không tên';
            const price = item.price || 'Liên hệ';
            const image = item.image || 'administrator/elements_LQA/img_LQA/no-image.png';
            const hasDiscount = item.has_discount || false;
            const originalPrice = item.original_price || '';

            // Tạo HTML cho giá (có hoặc không có khuyến mãi)
            let priceHtml = '';
            if (hasDiscount && originalPrice) {
              // Có khuyến mãi: hiển thị giá KM + giá gốc gạch ngang
              priceHtml = `
                <div class="search-item-price" style="color: #dc3545; font-weight: bold;">${price}</div>
                <div class="search-item-original-price" style="color: #999; font-size: 12px; text-decoration: line-through;">${originalPrice}</div>
              `;
            } else {
              // Không khuyến mãi: chỉ hiển thị giá
              priceHtml = `<div class="search-item-price">${price}</div>`;
            }

            const resultItem = `
                            <a href="index.php?reqHanghoa=${id}" class="search-item">
                                <div class="search-item-image">
                                    <img src="${image}" alt="${name}" onerror="this.src='administrator/elements_LQA/img_LQA/no-image.png'">
                                </div>
                                <div class="search-item-info">
                                    <div class="search-item-name">${name}</div>
                                    ${priceHtml}
                                </div>
                            </a>
                        `;
            resultsList.append(resultItem);
          });

          // Append results to container
          searchResults.append(resultsList);
          searchResults.show();
          console.log("Search results displayed"); // Debug log
        },
        error: function (xhr, status, error) {
          console.error("Search error:", error);
          console.log("XHR status:", status);
          console.log("XHR response:", xhr.responseText);

          // Hiển thị thông tin lỗi chi tiết trong console
          console.error("Detailed search error:", {
            error: error,
            status: status,
            responseText: xhr.responseText,
            readyState: xhr.readyState,
            statusText: xhr.statusText
          });

          // Hiển thị thông báo lỗi với thêm thông tin để debug
          searchResults.html(
            `<div class="text-center p-3 text-danger">
              <div>Có lỗi xảy ra khi tìm kiếm</div>
              <div class="small text-muted mt-2">${xhr.statusText || 'Unknown error'}</div>
            </div>`
          );
          searchResults.show();
        },
      });
    }, 300); // 300ms delay
  }

  // Event handlers
  searchInput.on("input", handleSearchInput);

  // Hide search results when clicking outside
  $(document).on("click", function (e) {
    if (!$(e.target).closest(".search-container").length) {
      searchResults.hide();
    }
  });

  // Prevent form submission when selecting a search result
  searchResults.on("click", "a", function (e) {
    e.preventDefault();
    window.location.href = $(this).attr("href");
  });

  // Handle Enter key press
  searchInput.on("keypress", function (e) {
    if (e.which === 13) {
      // If a search result is focused, navigate to it instead of submitting form
      const focusedResult = searchResults.find(".search-item:focus");
      if (focusedResult.length) {
        e.preventDefault();
        window.location.href = focusedResult.attr("href");
      }
      // Otherwise, form will submit normally
    }
  });
});
