-- Update v_product_review_stats view to only count visible reviews

CREATE OR REPLACE VIEW `v_product_review_stats` AS
SELECT 
    pr.ma_san_pham,
    COUNT(*) as total_reviews,
    AVG(pr.rating) as average_rating,
    SUM(CASE WHEN pr.rating = 5 THEN 1 ELSE 0 END) as five_star,
    SUM(CASE WHEN pr.rating = 4 THEN 1 ELSE 0 END) as four_star,
    SUM(CASE WHEN pr.rating = 3 THEN 1 ELSE 0 END) as three_star,
    SUM(CASE WHEN pr.rating = 2 THEN 1 ELSE 0 END) as two_star,
    SUM(CASE WHEN pr.rating = 1 THEN 1 ELSE 0 END) as one_star
FROM product_reviews pr
WHERE pr.is_approved = 1 
  AND (pr.status = 'visible' OR pr.status IS NULL)
GROUP BY pr.ma_san_pham;

SELECT 'View updated successfully!' as status;
