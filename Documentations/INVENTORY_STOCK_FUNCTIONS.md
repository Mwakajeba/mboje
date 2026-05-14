# Inventory Stock Functions Documentation

This document explains how to use the new inventory stock calculation functions that work with the `inventory_movements` table.

## Overview

The system now provides comprehensive functions to calculate current stock quantities for items across different locations using all movement types:
- `opening_balance` - Initial stock
- `transfer_in` - Stock received from transfers
- `transfer_out` - Stock sent via transfers  
- `sold` - Stock sold to customers
- `purchased` - Stock purchased from suppliers
- `adjustment_in` - Stock adjustments (increases)
- `adjustment_out` - Stock adjustments (decreases)
- `write_off` - Stock written off

## Available Functions

### 1. Item Model Methods

#### `getStockAtLocation($locationId)`
Get current stock quantity for an item at a specific location.

```php
$item = Item::find(1);
$stock = $item->getStockAtLocation(5); // Returns float
echo "Stock at location 5: " . $stock;
```

#### `getStockByLocation()`
Get stock quantities for an item across all locations.

```php
$item = Item::find(1);
$stockByLocation = $item->getStockByLocation();
// Returns collection with location_id, location_name, quantity
foreach ($stockByLocation as $location) {
    echo "Location: {$location['location_name']} - Stock: {$location['quantity']}";
}
```

#### `getTotalStockAttribute`
Get total stock quantity for an item across all locations.

```php
$item = Item::find(1);
$totalStock = $item->total_stock; // Access as attribute
echo "Total stock: " . $totalStock;
```

### 2. InventoryStockService Methods

#### `getItemStockAtLocation($itemId, $locationId)`
```php
$stockService = new InventoryStockService();
$stock = $stockService->getItemStockAtLocation(1, 5);
echo "Item 1 at location 5: " . $stock;
```

#### `getItemStockByLocation($itemId)`
```php
$stockService = new InventoryStockService();
$stockByLocation = $stockService->getItemStockByLocation(1);
// Returns collection with location details and quantities
```

#### `getItemTotalStock($itemId)`
```php
$stockService = new InventoryStockService();
$totalStock = $stockService->getItemTotalStock(1);
echo "Total stock for item 1: " . $totalStock;
```

#### `getLocationStockSummary($locationId)`
Get all items and their quantities at a specific location.

```php
$stockService = new InventoryStockService();
$locationStock = $stockService->getLocationStockSummary(5);
// Returns collection of items with quantities at location 5
```

#### `getComprehensiveStockReport($companyId = null)`
Get complete stock report for all items across all locations.

```php
$stockService = new InventoryStockService();
$report = $stockService->getComprehensiveStockReport(auth()->user()->company_id);
// Returns comprehensive report with item details and location breakdowns
```

#### `getLowStockItemsAtLocation($locationId)`
Get items that are below reorder level at a specific location.

```php
$stockService = new InventoryStockService();
$lowStockItems = $stockService->getLowStockItemsAtLocation(5);
// Returns items below reorder level with status (low_stock/out_of_stock)
```

#### `getOutOfStockItemsAtLocation($locationId)`
Get items that are out of stock at a specific location.

```php
$stockService = new InventoryStockService();
$outOfStockItems = $stockService->getOutOfStockItemsAtLocation(5);
// Returns items with zero or negative stock
```

#### `getItemLocationMovementHistory($itemId, $locationId, $limit = 50)`
Get movement history for an item at a specific location.

```php
$stockService = new InventoryStockService();
$history = $stockService->getItemLocationMovementHistory(1, 5, 100);
// Returns recent movements with details
```

## API Endpoints

### Get Item Stock
```
GET /inventory/items/{encodedId}/stock
```
Returns JSON with item details, total stock, and stock by location.

### Get Stock Report
```
GET /inventory/stock-report
```
Returns comprehensive stock report for all items.

### Get Location Stock
```
GET /inventory/location/{locationId}/stock
```
Returns stock summary, low stock items, and out of stock items for a location.

## Usage Examples

### Example 1: Check if item has stock at location
```php
$item = Item::find(1);
$locationId = 5;
$stock = $item->getStockAtLocation($locationId);

if ($stock > 0) {
    echo "Item has {$stock} units in stock at this location";
} else {
    echo "Item is out of stock at this location";
}
```

### Example 2: Get all locations where item has stock
```php
$item = Item::find(1);
$stockByLocation = $item->getStockByLocation();

if ($stockByLocation->isEmpty()) {
    echo "Item is out of stock everywhere";
} else {
    echo "Item is available at:";
    foreach ($stockByLocation as $location) {
        echo "- {$location['location_name']}: {$location['quantity']} units";
    }
}
```

### Example 3: Find low stock items
```php
$stockService = new InventoryStockService();
$locationId = 5;
$lowStockItems = $stockService->getLowStockItemsAtLocation($locationId);

foreach ($lowStockItems as $item) {
    if ($item['status'] === 'out_of_stock') {
        echo "URGENT: {$item['item_name']} is out of stock!";
    } else {
        echo "WARNING: {$item['item_name']} is low on stock ({$item['current_stock']} units)";
    }
}
```

### Example 4: Generate stock report
```php
$stockService = new InventoryStockService();
$report = $stockService->getComprehensiveStockReport(auth()->user()->company_id);

foreach ($report as $item) {
    echo "Item: {$item['item_name']} (Total: {$item['total_stock']})";
    foreach ($item['locations'] as $location) {
        echo "  - {$location['location_name']}: {$location['quantity']}";
    }
}
```

## Database Structure

The functions work with the `inventory_movements` table which tracks all stock changes:

- `item_id` - The inventory item
- `location_id` - The location where the movement occurred
- `movement_type` - Type of movement (opening_balance, transfer_in, etc.)
- `quantity` - Quantity moved (positive for inbound, negative for outbound)
- `movement_date` - When the movement occurred

## Performance Notes

- All functions use SQL aggregation for optimal performance
- Results are cached at the query level
- Use specific location/item filters when possible to improve performance
- For large datasets, consider pagination or date filtering

## Error Handling

All functions return safe defaults:
- Stock quantities default to 0 if no movements exist
- Empty collections are returned if no data found
- Invalid IDs are handled gracefully with appropriate error responses
