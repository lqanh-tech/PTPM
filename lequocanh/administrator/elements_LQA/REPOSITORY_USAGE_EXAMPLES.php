<?php
/**
 * Repository Pattern Usage Examples
 *
 * Shows how to use the new repositories instead of direct class instantiation.
 * Old code continues to work — repositories are for NEW code.
 */

// ============================================================
// 1. Via Container (recommended for new code)
// ============================================================

require_once __DIR__ . '/mod/Container.php';
require_once __DIR__ . '/mod/ProductRepository.php';
require_once __DIR__ . '/mod/CartRepository.php';
require_once __DIR__ . '/mod/PriceRepository.php';
require_once __DIR__ . '/mod/OrderRepository.php';

ServiceProvider::register();

// Product operations
$productRepo = app('ProductRepositoryInterface');
$products = $productRepo->getAll();
$product = $productRepo->getById(1);
$newId = $productRepo->add(['tenhanghoa' => 'SP mới', 'giathamkhao' => 100000, 'idloaihang' => 1]);
$productRepo->update($newId, ['tenhanghoa' => 'SP đã sửa']);
$productRepo->delete($newId);
$found = $productRepo->search('iPhone');
$stock = $productRepo->getStock(1);

// Cart operations
$cartRepo = app('CartRepositoryInterface');
$cartRepo->addItem('user123', 1, 2);
$cart = $cartRepo->getCart('user123');
$count = $cartRepo->getItemCount('user123');
$cartRepo->removeItem('user123', 1);
$cartRepo->clearCart('user123');

// Price operations
$priceRepo = app('PriceRepositoryInterface');
$prices = $priceRepo->getByProduct(1);
$active = $priceRepo->getActiveByProduct(1);
$history = $priceRepo->getPriceHistory(1);

// Order operations
$orderRepo = app('OrderRepositoryInterface');
$orders = $orderRepo->getAll(['status' => 'approved', 'limit' => 20]);
$order = $orderRepo->getById(1);
$items = $orderRepo->getItems(1);
$stats = $orderRepo->getStatistics();
$orderRepo->updateStatus(1, 'delivered');
$revenue = $orderRepo->getRevenueByDate('2026-01-01', '2026-05-04');

// ============================================================
// 2. Direct instantiation (simple alternative)
// ============================================================

$productRepo = new ProductRepository(); // uses Database::getInstance() internally
$products = $productRepo->getAll();

// With custom PDO (DI):
$pdo = new PDO('mysql:host=localhost;dbname=sales_management', 'root', 'pw');
$productRepo = new ProductRepository($pdo);

// ============================================================
// 3. Old code still works (no changes needed)
// ============================================================

// hanghoaCls removed - use Product:: directly
$all = $hanghoa->HanghoaGetAll(); // still works

// ============================================================
// 4. Migration pattern (gradual)
// ============================================================
// When refactoring a view/controller:
//   OLD: (removed)
//   NEW: $repo = app('ProductRepositoryInterface'); $result = $repo->getAll();
//
// Only migrate when touching the file for other reasons.
// Don't do mass rewrites.
