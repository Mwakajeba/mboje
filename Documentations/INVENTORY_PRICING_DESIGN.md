# Inventory pricing: separation of concerns

## The problem

- An **item** is created once at **company level**.
- If the item stores a **single selling price**, then:
  - When Location A changes the price, it changes for Location B.
  - Stock transfers and profit reports use the wrong price.
- This only works when the company has one location or all branches sell at the same price.

## Correct design: three separate things

| Concept | Where it lives | Purpose |
|--------|----------------|---------|
| **What the item is** | `inventory_items` (code, name, category, unit of measure, etc.) | Identity and master data. May hold a **default** selling price and cost for fallback only. |
| **How much each location sells it for** | `inventory_item_prices` (per branch), `inventory_item_location_prices` (per location) | **Source of truth** for selling price. Location price overrides branch price overrides item default. |
| **What the inventory cost is** | Item default `cost_price` + `inventory_cost_layers` (FIFO/weighted avg) | Cost for valuation and COGS. Cost layers are the actual cost; item `cost_price` is default/reference. |

## Rules

1. **Selling price**
   - **Do not** use the item’s `unit_price` as the only source when a branch/location context exists.
   - **Do** resolve price in this order: location price → branch price → item default (`unit_price`).
   - **Do** store the **resolved** price on the sale line (e.g. `sales_invoice_items.unit_price`) at the time of sale. Reports then use the line price, not the item.
   - When a location “changes the price”, update only `inventory_item_location_prices` (or `inventory_item_prices` for branch). **Do not** update `inventory_items.unit_price` for that.

2. **Cost**
   - Inventory cost comes from cost layers (and optionally location/branch cost overrides). Item `cost_price` is a default only.
   - For profit reports, prefer cost stored on the sale line or from cost layers; avoid using only `inventory_items.cost_price` when location-specific cost exists.

3. **Transfers**
   - Transfers move quantity and **cost** (from cost layers). Selling price is not “transferred”; the destination location uses its own selling price from `inventory_item_location_prices` / `inventory_item_prices`.

## Implementation

- **Resolve price:** `Item::getUnitPriceForBranchOrLocation($branchId, $locationId)` and `getCostPriceForBranchOrLocation(...)`.
- **Set price:** Edit item → “Prices by branch” / “Prices by location” → save to `inventory_item_prices` / `inventory_item_location_prices`. Item “Selling price” is the default only.
- **Sales flows:** All “get item” APIs and create/edit views use resolved price for the current branch/location so that the price suggested and stored on the document is correct.
