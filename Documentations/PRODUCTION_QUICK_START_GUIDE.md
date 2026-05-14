# 🏭 Production Work Orders - Quick Start Guide

## 📍 How to Access Production Work Orders

### Step 1: Navigation
1. **Login** to your SmartAccounting system
2. Look for the **"Production"** menu in the left sidebar
3. Click on **"Production"** to expand the submenu
4. Click on **"Work Orders"**

### Step 2: Direct URL Access
If you can't find the menu, you can directly access:
```
http://your-domain.com/production/work-orders
```

---

## 🚀 Getting Started with Work Orders

### Creating Your First Work Order

1. **Go to Work Orders page** (Production → Work Orders)
2. Click the **"+ Add Work Order"** button (red button at top)
3. Fill in the required information:
   - **Customer**: Select from dropdown
   - **Product Name**: e.g., "Cotton Sweater"
   - **Style**: e.g., "Round Neck", "V-Neck"
   - **Sizes & Quantities**: 
     - Small: 50 pieces
     - Medium: 100 pieces
     - Large: 75 pieces
   - **Due Date**: When you need it completed
   - **Requires Logo**: Check if embroidery needed

4. **Add Materials (BOM - Bill of Materials)**:
   - Cotton yarn: 500g per sweater
   - Thread: 50m per sweater
   - Labels: 1 per sweater

5. Click **"Create Work Order"**

---

## 📋 Production Workflow (9 Stages)

Your work order will progress through these stages:

1. **PLANNED** ✅ - Initial creation
2. **MATERIAL_ISSUED** 📦 - Raw materials issued to production
3. **KNITTING** 🧶 - Yarn knitting process
4. **CUTTING** ✂️ - Pattern cutting
5. **JOINING** 🪡 - Assembly/sewing
6. **EMBROIDERY** 🎨 - Logo/design embroidery (if required)
7. **IRONING_FINISHING** 👔 - Final finishing
8. **QC** ✅ - Quality check
9. **PACKAGING** 📦 - Final packaging
10. **DISPATCHED** 🚚 - Ready for delivery

---

## 🎯 Key Features Available

### Work Order Management
- ✅ **Create New Orders**: Full sweater production workflow
- ✅ **Track Progress**: See completion % for each stage
- ✅ **Stage Advancement**: Move orders through production stages
- ✅ **Quality Control**: Built-in QC checkpoints
- ✅ **Material Tracking**: Issue and track materials used

### Production Machines
- ✅ **Machine Management**: Assign machines to production stages
- ✅ **Gauge Tracking**: For knitting machines (12GG, 14GG, etc.)
- ✅ **Location Assignment**: Track where machines are located

---

## 🔧 Quick Actions

### Daily Production Tasks
1. **Check Today's Work Orders**:
   - Go to Production → Work Orders
   - Filter by due date or status

2. **Advance Work Order to Next Stage**:
   - Click the "Advance Stage" button
   - Fill in stage-specific details
   - Confirm advancement

3. **Issue Materials**:
   - Click "Issue Materials" on PLANNED orders
   - Confirm material quantities
   - Materials will be deducted from inventory

4. **Record Production**:
   - Track quantities completed at each stage
   - Note any issues or waste

5. **Quality Check**:
   - Inspect completed items
   - Mark as passed/failed
   - Record any defects

---

## 📊 Monitoring & Reports

### Progress Tracking
- **Dashboard View**: See all active work orders
- **Stage Status**: Visual progress bars
- **Completion Metrics**: Track efficiency and timelines

### Available Reports
- Go to **Reports → Production Reports**
- View productivity metrics
- Track material usage
- Monitor machine utilization

---

## ⚡ Troubleshooting

### Can't Find Production Menu?
1. Check with your administrator about permissions
2. Ensure your user role has production access
3. Try logging out and back in

### Direct Access URLs
- **Work Orders**: `/production/work-orders`
- **Production Machines**: `/production/machines`
- **Create New Work Order**: `/production/work-orders/create`

### Support
If you need help:
1. Check the system logs for errors
2. Contact your system administrator
3. Refer to this guide for common procedures

---

## 🎯 Next Steps

1. **Create your first work order** following the steps above
2. **Set up your production machines** (Production → Production Machine)
3. **Train your team** on the 9-stage workflow
4. **Start tracking production progress** through the dashboard

**Happy Production Management! 🏭**