<?php

declare(strict_types=1);

namespace App\Helpers;

/**
 * API Documentation Generator
 * 
 * Usage:
 *   $docs = new ApiDocumentation();
 *   echo $docs->generate();
 */
class ApiDocumentation
{
    private array $endpoints = [];

    public function __construct()
    {
        $this->registerEndpoints();
    }

    private function registerEndpoints(): void
    {
        $this->endpoints = [
            [
                'method' => 'GET',
                'path' => '/api/v1/products',
                'description' => 'List products with pagination',
                'parameters' => [
                    ['name' => 'page', 'type' => 'integer', 'required' => false, 'description' => 'Page number (default: 1)'],
                    ['name' => 'per_page', 'type' => 'integer', 'required' => false, 'description' => 'Items per page (default: 20, max: 100)'],
                    ['name' => 'category_id', 'type' => 'integer', 'required' => false, 'description' => 'Filter by category ID'],
                    ['name' => 'search', 'type' => 'string', 'required' => false, 'description' => 'Search keyword'],
                ],
                'response' => [
                    'success' => true,
                    'data' => [
                        ['id' => 1, 'name' => 'Product Name', 'price' => 100000, 'sale_price' => 80000],
                    ],
                    'pagination' => [
                        'current_page' => 1,
                        'last_page' => 5,
                        'per_page' => 20,
                        'total' => 100,
                    ],
                ],
            ],
            [
                'method' => 'GET',
                'path' => '/api/v1/products/{id}',
                'description' => 'Get single product details',
                'parameters' => [
                    ['name' => 'id', 'type' => 'integer', 'required' => true, 'description' => 'Product ID'],
                ],
                'response' => [
                    'success' => true,
                    'data' => [
                        'id' => 1,
                        'name' => 'Product Name',
                        'description' => 'Product description',
                        'price' => 100000,
                        'sale_price' => 80000,
                        'image_url' => '/path/to/image.jpg',
                        'category' => ['id' => 1, 'name' => 'Category'],
                        'brand' => ['id' => 1, 'name' => 'Brand'],
                        'stock' => ['quantity' => 10],
                        'in_stock' => true,
                    ],
                ],
            ],
            [
                'method' => 'GET',
                'path' => '/api/v1/categories',
                'description' => 'List all categories with product count',
                'parameters' => [],
                'response' => [
                    'success' => true,
                    'data' => [
                        ['idloaihang' => 1, 'tenloaihang' => 'Category Name', 'product_count' => 10],
                    ],
                ],
            ],
            [
                'method' => 'GET',
                'path' => '/api/v1/orders',
                'description' => 'List orders for authenticated user',
                'parameters' => [
                    ['name' => 'page', 'type' => 'integer', 'required' => false, 'description' => 'Page number'],
                    ['name' => 'per_page', 'type' => 'integer', 'required' => false, 'description' => 'Items per page'],
                ],
                'response' => [
                    'success' => true,
                    'data' => [],
                    'pagination' => [],
                ],
                'auth' => true,
            ],
            [
                'method' => 'GET',
                'path' => '/api/v1/orders/{id}',
                'description' => 'Get order details',
                'parameters' => [
                    ['name' => 'id', 'type' => 'integer', 'required' => true, 'description' => 'Order ID'],
                ],
                'response' => [
                    'success' => true,
                    'data' => [
                        'order' => [],
                        'items' => [],
                    ],
                ],
                'auth' => true,
            ],
            [
                'method' => 'GET',
                'path' => '/api/v1/search',
                'description' => 'Search products',
                'parameters' => [
                    ['name' => 'q', 'type' => 'string', 'required' => true, 'description' => 'Search keyword'],
                    ['name' => 'page', 'type' => 'integer', 'required' => false, 'description' => 'Page number'],
                    ['name' => 'per_page', 'type' => 'integer', 'required' => false, 'description' => 'Items per page'],
                ],
                'response' => [
                    'success' => true,
                    'data' => [],
                    'pagination' => [],
                ],
            ],
            [
                'method' => 'GET',
                'path' => '/api/v1/stats',
                'description' => 'Get dashboard statistics',
                'parameters' => [],
                'response' => [
                    'success' => true,
                    'data' => [
                        'products' => 100,
                        'orders' => 50,
                        'categories' => 10,
                    ],
                ],
                'auth' => true,
            ],
        ];
    }

    /**
     * Generate HTML documentation.
     */
    public function toHtml(): string
    {
        $html = '<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>API Documentation</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; line-height: 1.6; color: #333; padding: 20px; }
        .container { max-width: 1000px; margin: 0 auto; }
        h1 { color: #2c3e50; margin-bottom: 20px; }
        h2 { color: #34495e; margin: 30px 0 15px; }
        .endpoint { background: #fff; border: 1px solid #ddd; border-radius: 8px; margin-bottom: 20px; overflow: hidden; }
        .endpoint-header { padding: 15px; background: #f8f9fa; border-bottom: 1px solid #ddd; display: flex; align-items: center; gap: 10px; }
        .method { padding: 4px 8px; border-radius: 4px; font-weight: bold; font-size: 12px; text-transform: uppercase; }
        .method-get { background: #28a745; color: #fff; }
        .method-post { background: #007bff; color: #fff; }
        .method-put { background: #ffc107; color: #000; }
        .method-delete { background: #dc3545; color: #fff; }
        .path { font-family: monospace; font-size: 14px; }
        .auth-badge { background: #17a2b8; color: #fff; padding: 2px 6px; border-radius: 3px; font-size: 11px; }
        .endpoint-body { padding: 15px; }
        .description { margin-bottom: 15px; color: #666; }
        .params-table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        .params-table th, .params-table td { padding: 8px 12px; text-align: left; border-bottom: 1px solid #eee; }
        .params-table th { background: #f8f9fa; font-weight: 600; }
        .required { color: #dc3545; }
        .response { background: #f8f9fa; padding: 15px; border-radius: 4px; overflow-x: auto; }
        .response pre { margin: 0; font-size: 13px; }
        .info { background: #e7f3ff; border: 1px solid #b3d7ff; padding: 15px; border-radius: 8px; margin-bottom: 20px; }
        .info h3 { color: #0056b3; margin-bottom: 10px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>📚 API Documentation</h1>
        
        <div class="info">
            <h3>Base URL</h3>
            <code>https://your-domain.com/api/v1</code>
            
            <h3>Authentication</h3>
            <p>Some endpoints require authentication. Include session cookie or API token in requests.</p>
            
            <h3>Rate Limiting</h3>
            <p>API requests are limited to 60 requests per minute per IP address.</p>
            
            <h3>CSRF Protection</h3>
            <p>POST/PUT/DELETE requests require CSRF token in header <code>X-CSRF-TOKEN</code> or form field <code>csrf_token</code>.</p>
        </div>
        
        <h2>Endpoints</h2>';

        foreach ($this->endpoints as $endpoint) {
            $methodClass = 'method-' . strtolower($endpoint['method']);
            $authBadge = $endpoint['auth'] ?? false ? '<span class="auth-badge">🔒 Auth Required</span>' : '';
            
            $html .= '
        <div class="endpoint">
            <div class="endpoint-header">
                <span class="method ' . $methodClass . '">' . $endpoint['method'] . '</span>
                <span class="path">' . $endpoint['path'] . '</span>
                ' . $authBadge . '
            </div>
            <div class="endpoint-body">
                <p class="description">' . $endpoint['description'] . '</p>';

            if (!empty($endpoint['parameters'])) {
                $html .= '
                <h4>Parameters</h4>
                <table class="params-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Type</th>
                            <th>Required</th>
                            <th>Description</th>
                        </tr>
                    </thead>
                    <tbody>';
                
                foreach ($endpoint['parameters'] as $param) {
                    $required = $param['required'] ? '<span class="required">Yes</span>' : 'No';
                    $html .= '
                        <tr>
                            <td><code>' . $param['name'] . '</code></td>
                            <td>' . $param['type'] . '</td>
                            <td>' . $required . '</td>
                            <td>' . $param['description'] . '</td>
                        </tr>';
                }
                
                $html .= '
                    </tbody>
                </table>';
            }

            if (!empty($endpoint['response'])) {
                $html .= '
                <h4>Response Example</h4>
                <div class="response">
                    <pre>' . json_encode($endpoint['response'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . '</pre>
                </div>';
            }

            $html .= '
            </div>
        </div>';
        }

        $html .= '
    </div>
</body>
</html>';

        return $html;
    }

    /**
     * Generate JSON documentation.
     */
    public function toJson(): string
    {
        return json_encode([
            'openapi' => '3.0.0',
            'info' => [
                'title' => 'E-Commerce API',
                'version' => '1.0.0',
                'description' => 'API for e-commerce application',
            ],
            'servers' => [
                ['url' => '/api/v1', 'description' => 'Production'],
            ],
            'paths' => $this->generatePaths(),
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    private function generatePaths(): array
    {
        $paths = [];
        
        foreach ($this->endpoints as $endpoint) {
            $path = $endpoint['path'];
            $method = strtolower($endpoint['method']);
            
            $operation = [
                'summary' => $endpoint['description'],
                'responses' => [
                    '200' => [
                        'description' => 'Successful response',
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'success' => ['type' => 'boolean'],
                                        'data' => ['type' => 'object'],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ];

            if (!empty($endpoint['parameters'])) {
                $operation['parameters'] = array_map(function ($param) {
                    return [
                        'name' => $param['name'],
                        'in' => 'query',
                        'required' => $param['required'],
                        'schema' => ['type' => $param['type']],
                        'description' => $param['description'],
                    ];
                }, $endpoint['parameters']);
            }

            if ($endpoint['auth'] ?? false) {
                $operation['security'] = [['sessionAuth' => []]];
            }

            $paths[$path][$method] = $operation;
        }

        return $paths;
    }
}
