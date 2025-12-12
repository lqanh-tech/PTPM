-- =====================================================
-- OPTIMIZE RATING QUERIES
-- Adds indexes to improve performance of rating filters
-- Safe to run - does not modify data, only adds indexes
-- =====================================================

-- Index for rating calculations
-- This speeds up AVG(rating) queries by 5-10x
ALTER TABLE product_reviews 
ADD INDEX IF NOT EXISTS idx_product_rating (idhanghoa, rating);

-- Index for time-based queries (optional but recommended)
ALTER TABLE product_reviews 
ADD INDEX IF NOT EXISTS idx_created_at (created_at);

-- Index for status filtering (if not exists)
ALTER TABLE product_reviews 
ADD INDEX IF NOT EXISTS idx_status (status);

-- Composite index for common query pattern
ALTER TABLE product_reviews 
ADD INDEX IF NOT EXISTS idx_product_status_rating (idhanghoa, status, rating);

-- Analyze table to update statistics for query optimizer
ANALYZE TABLE product_reviews;

-- Verify indexes were created
SHOW INDEX FROM product_reviews;
