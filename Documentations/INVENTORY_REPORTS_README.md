# Inventory Reports System - User Guide

## Overview
The Smart Accounting System provides a comprehensive suite of 12 inventory reports designed to help businesses manage their stock effectively, make informed decisions, and optimize inventory operations. Each report serves a specific purpose and provides unique insights into your inventory management.

---

## 📊 Inventory Reports Overview

### 1. **Stock on Hand Report**
**Purpose:** Provides a real-time snapshot of current inventory levels across all items.

**Key Features:**
- Current stock quantities for each item
- Unit costs and total values
- Category and location breakdown
- Total inventory value summary

**Advantages:**
- ✅ **Real-time visibility** into current stock levels
- ✅ **Quick assessment** of inventory value
- ✅ **Location-based** stock tracking
- ✅ **Category-wise** inventory analysis
- ✅ **Cost control** through unit cost monitoring

**When to Use:**
- Daily inventory checks
- Monthly stock assessments
- Preparing for audits
- Quick value calculations

**Calculation Method:**
```
Total Quantity = SUM(current_stock) for all items
Total Value = SUM(current_stock × cost_price) for all items
Unit Cost = cost_price (from inventory_items table)
On Hand = current_stock (from inventory_items table)
```

---

### 2. **Stock Valuation Report**
**Purpose:** Calculates the total monetary value of inventory using different costing methods.

**Key Features:**
- Weighted average costing
- Total inventory value by category
- Location-wise valuation
- Cost method comparison

**Advantages:**
- ✅ **Financial accuracy** in inventory valuation
- ✅ **Compliance** with accounting standards
- ✅ **Cost method flexibility** (FIFO, LIFO, Weighted Average)
- ✅ **Audit trail** for financial reporting
- ✅ **Tax calculation** support

**When to Use:**
- Monthly financial reporting
- Tax preparation
- Audit requirements
- Cost analysis for pricing decisions

**Calculation Method:**
```
Item Value = current_stock × cost_price
Total Inventory Value = SUM(Item Value) for all items
Category Value = SUM(Item Value) for items in same category
Location Value = SUM(Item Value) for items in same location
Weighted Average Cost = Total Value ÷ Total Quantity
```

---

### 3. **Movement Register Report**
**Purpose:** Tracks all inventory movements (in/out) with detailed transaction history.

**Key Features:**
- Complete movement history
- Transaction types (purchase, sale, adjustment, transfer)
- Running balance calculations
- User and date tracking

**Advantages:**
- ✅ **Complete audit trail** of all inventory movements
- ✅ **Transaction tracking** for accountability
- ✅ **Running balance** calculations
- ✅ **User activity** monitoring
- ✅ **Discrepancy identification**

**When to Use:**
- Investigating stock discrepancies
- Audit trail requirements
- User activity monitoring
- Transaction verification

**Calculation Method:**
```
Movement Types:
- IN: inventory_in, transfer_in, purchased, adjustment_in
- OUT: transfer_out, sold, adjustment_out

Running Balance Calculation:
- For IN movements: running_qty += quantity, running_value += total_cost
- For OUT movements: running_qty -= quantity, running_value -= total_cost
- Unit Cost = running_value ÷ running_qty (when running_qty > 0)
```

---

### 4. **Aging Stock Report**
**Purpose:** Identifies slow-moving or obsolete inventory that may need attention.

**Key Features:**
- Days since last movement
- Stock aging categories (fast, slow, obsolete)
- Value of aged inventory
- Last movement dates

**Advantages:**
- ✅ **Obsolete stock identification**
- ✅ **Cash flow optimization** through stock clearance
- ✅ **Storage cost reduction**
- ✅ **Inventory turnover** analysis
- ✅ **Strategic planning** for stock management

**When to Use:**
- Quarterly inventory reviews
- Obsolete stock clearance planning
- Storage optimization
- Cash flow management

**Calculation Method:**
```
Days Inactive = Current Date - Last Movement Date
Aging Categories:
- Fast Moving: < 30 days
- Slow Moving: 30-90 days  
- Obsolete: > 90 days

Item Value = current_stock × cost_price
Status Assignment:
- If days_inactive > 90: "obsolete"
- If days_inactive > 30: "slow"
- Else: "fast"
```

---

### 5. **Reorder Report**
**Purpose:** Identifies items that need to be reordered based on minimum stock levels.

**Key Features:**
- Items below reorder level
- Suggested reorder quantities
- Supplier information
- Critical stock alerts

**Advantages:**
- ✅ **Prevents stockouts** through proactive reordering
- ✅ **Automated alerts** for low stock
- ✅ **Optimal reorder quantities** calculation
- ✅ **Supplier management** integration
- ✅ **Inventory planning** support

**When to Use:**
- Daily stock level monitoring
- Purchase planning
- Supplier communication
- Inventory optimization

**Calculation Method:**
```
Reorder Condition: current_stock <= reorder_level
Suggested Quantity = MAX(
    reorder_level - current_stock + 10,  // Add 10 unit buffer
    reorder_level                        // Minimum reorder quantity
)
Available Stock = current_stock - reserved_quantity (if reserved_quantity exists)
Status = "critical" if current_stock <= reorder_level, else "normal"
```

---

### 6. **Over/Understock Report**
**Purpose:** Identifies items with excessive or insufficient stock levels.

**Key Features:**
- Stock level variance analysis
- Minimum and maximum level comparisons
- Overstock and understock identification
- Variance calculations

**Advantages:**
- ✅ **Storage optimization** through overstock identification
- ✅ **Stockout prevention** through understock alerts
- ✅ **Working capital optimization**
- ✅ **Storage cost reduction**
- ✅ **Inventory balance** maintenance

**When to Use:**
- Weekly inventory reviews
- Storage space optimization
- Working capital management
- Inventory level adjustments

**Calculation Method:**
```
Status Determination:
- If current_stock < minimum_stock: "understock"
- If current_stock > maximum_stock: "overstock"  
- Else: "ok"

Variance Calculation:
- Understock Variance = current_stock - minimum_stock (negative value)
- Overstock Variance = current_stock - maximum_stock (positive value)
- OK Variance = 0

Item Value = current_stock × cost_price
```

---

### 7. **Item Ledger Report**
**Purpose:** Provides detailed transaction history for specific inventory items.

**Key Features:**
- Item-specific movement history
- Running quantity and value balances
- Transaction details and references
- Date range filtering

**Advantages:**
- ✅ **Detailed item tracking** for specific products
- ✅ **Transaction history** analysis
- ✅ **Running balance** calculations
- ✅ **Item performance** evaluation
- ✅ **Discrepancy investigation** support

**When to Use:**
- Investigating specific item issues
- Item performance analysis
- Transaction verification
- Detailed audit trails

**Calculation Method:**
```
Filter by: item_id, date_from, date_to
Running Balance Calculation (same as Movement Register):
- For IN movements: running_qty += quantity, running_value += total_cost
- For OUT movements: running_qty -= quantity, running_value -= total_cost
- Unit Cost = running_value ÷ running_qty (when running_qty > 0)

Entry Structure:
- Movement details (date, type, reference)
- Running quantity and value
- Calculated unit cost
```

---

### 8. **Cost Changes Report**
**Purpose:** Tracks changes in item costs over time for price analysis.

**Key Features:**
- Cost change history
- Price trend analysis
- Cost adjustment tracking
- Historical cost comparison

**Advantages:**
- ✅ **Price trend analysis** for strategic planning
- ✅ **Cost control** through change monitoring
- ✅ **Pricing strategy** support
- ✅ **Supplier negotiation** data
- ✅ **Profit margin** analysis

**When to Use:**
- Quarterly cost reviews
- Pricing strategy development
- Supplier negotiations
- Profit margin analysis

**Calculation Method:**
```
Filter: movement_type IN ('adjustment_in', 'adjustment_out')
Cost Change Analysis:
- Previous Cost = cost before adjustment
- New Cost = cost after adjustment  
- Change Amount = New Cost - Previous Cost
- Change Percentage = (Change Amount ÷ Previous Cost) × 100

Movement Types:
- adjustment_in: Cost increase
- adjustment_out: Cost decrease
```

---

### 9. **Stock Take Variance Report**
**Purpose:** Compares physical stock counts with system records to identify discrepancies.

**Key Features:**
- Physical vs. system stock comparison
- Variance calculations
- Count batch tracking
- Location-wise variance analysis

**Advantages:**
- ✅ **Accuracy verification** of inventory records
- ✅ **Discrepancy identification** and resolution
- ✅ **Audit compliance** support
- ✅ **System reliability** validation
- ✅ **Loss prevention** through variance tracking

**When to Use:**
- Monthly stock takes
- Audit preparation
- System accuracy verification
- Loss investigation

**Calculation Method:**
```
Variance Calculation:
- System Quantity = current_stock (from inventory_items)
- Physical Quantity = actual count from stock take
- Variance = Physical Quantity - System Quantity
- Variance Percentage = (Variance ÷ System Quantity) × 100

Status Classification:
- Exact Match: Variance = 0
- Over Count: Variance > 0
- Short Count: Variance < 0

Note: Currently shows placeholder data as StockTake model is not implemented
```

---

### 10. **Location Bin Report**
**Purpose:** Provides detailed analysis of inventory distribution across different locations and bins.

**Key Features:**
- Location-wise stock distribution
- Bin utilization analysis
- Storage efficiency metrics
- Location performance comparison

**Advantages:**
- ✅ **Storage optimization** through location analysis
- ✅ **Space utilization** improvement
- ✅ **Location performance** evaluation
- ✅ **Warehouse management** support
- ✅ **Efficiency improvement** opportunities

**When to Use:**
- Warehouse optimization
- Storage space planning
- Location performance reviews
- Efficiency improvement projects

**Calculation Method:**
```
Utilization Calculation:
- Utilization % = (current_stock ÷ maximum_stock) × 100

Status Classification:
- Empty: current_stock = 0
- Normal: 0 < utilization ≤ 90%
- Overfull: utilization > 90%

Location Analysis:
- Total Items per Location
- Average Utilization per Location
- Storage Efficiency Metrics
```

---

### 11. **Category/Brand Mix Report**
**Purpose:** Analyzes inventory distribution across different categories and brands.

**Key Features:**
- Category-wise inventory analysis
- Brand performance comparison
- Mix percentage calculations
- Value distribution analysis

**Advantages:**
- ✅ **Strategic planning** through category analysis
- ✅ **Brand performance** evaluation
- ✅ **Product mix optimization**
- ✅ **Market trend** identification
- ✅ **Inventory diversification** analysis

**When to Use:**
- Strategic planning sessions
- Product mix optimization
- Brand performance reviews
- Market analysis

**Calculation Method:**
```
Category Analysis:
- Total Quantity = SUM(current_stock) for items in category
- Total Value = SUM(current_stock × cost_price) for items in category
- Category % = (Category Value ÷ Grand Total Value) × 100

Grand Totals:
- Grand Total Quantity = SUM(current_stock) for all items
- Grand Total Value = SUM(current_stock × cost_price) for all items

Mix Analysis:
- Quantity Distribution by Category
- Value Distribution by Category
- Percentage Breakdown
```

---

### 12. **Profit Margin Report**
**Purpose:** Analyzes profitability of inventory items based on sales and cost data.

**Key Features:**
- Item-wise profit margin calculations
- Sales revenue vs. cost analysis
- Gross margin percentages
- Profitability ranking

**Advantages:**
- ✅ **Profitability analysis** for each item
- ✅ **Pricing strategy** optimization
- ✅ **Product performance** evaluation
- ✅ **Revenue optimization** opportunities
- ✅ **Cost management** insights

**When to Use:**
- Monthly profitability reviews
- Pricing strategy development
- Product performance analysis
- Revenue optimization planning

**Calculation Method:**
```
Sales Data (from SalesInvoiceItem):
- Sales Revenue = SUM(quantity × unit_price) for item
- Sold Quantity = SUM(quantity) for item

Cost Analysis:
- Cost of Goods Sold = SUM(quantity × inventoryItem.cost_price) for item
- Gross Margin = Sales Revenue - Cost of Goods Sold
- Gross Margin % = (Gross Margin ÷ Sales Revenue) × 100

Profitability Metrics:
- Revenue per Unit = Sales Revenue ÷ Sold Quantity
- Cost per Unit = Cost of Goods Sold ÷ Sold Quantity
- Profit per Unit = Revenue per Unit - Cost per Unit
```

---

## 🎯 Best Practices for Using Inventory Reports

### **Daily Operations:**
- Check **Stock on Hand** for current levels
- Review **Reorder Report** for urgent reorders
- Monitor **Over/Understock** for level adjustments

### **Weekly Reviews:**
- Analyze **Movement Register** for transaction patterns
- Review **Item Ledger** for specific item issues
- Check **Location Bin** for storage optimization

### **Monthly Analysis:**
- Run **Stock Valuation** for financial reporting
- Analyze **Aging Stock** for clearance planning
- Review **Profit Margin** for pricing decisions

### **Quarterly Planning:**
- Comprehensive **Category/Brand Mix** analysis
- **Cost Changes** trend analysis
- **Stock Take Variance** for accuracy verification

---

## 🔧 Technical Features

### **Filtering Options:**
- Date range filtering
- Location-based filtering
- Category-based filtering
- Item-specific filtering
- User-based filtering

### **Export Capabilities:**
- PDF export for reports
- Excel export for data analysis
- Print-friendly formats
- Email sharing options

### **Real-time Updates:**
- Live data from database
- Automatic refresh capabilities
- Real-time calculations
- Current stock levels

---

## 📈 Business Benefits

### **Operational Efficiency:**
- Reduced stockouts through proactive monitoring
- Optimized storage space utilization
- Improved inventory accuracy
- Streamlined reorder processes

### **Financial Benefits:**
- Better cash flow management
- Reduced carrying costs
- Improved profit margins
- Accurate financial reporting

### **Strategic Advantages:**
- Data-driven decision making
- Improved supplier relationships
- Better customer service
- Competitive advantage through optimization

---

## 🚀 Getting Started

1. **Access Reports:** Navigate to Reports → Inventory Reports
2. **Select Report:** Choose the report that meets your current needs
3. **Apply Filters:** Use available filters to focus on specific data
4. **Analyze Results:** Review the data and identify action items
5. **Take Action:** Implement changes based on report insights
6. **Schedule Regular Reviews:** Set up routine report analysis

---

## 📞 Support

For questions about inventory reports or assistance with implementation, please contact your system administrator or refer to the system documentation.

---

*This inventory reporting system is designed to provide comprehensive insights into your inventory management, helping you make informed decisions and optimize your business operations.*
