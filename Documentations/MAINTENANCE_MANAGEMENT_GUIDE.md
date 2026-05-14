# Maintenance Management Module - User Guide

## Overview
The Maintenance Management module helps you **schedule preventive maintenance, manage work orders, and track repairs** for your fleet vehicles. This ensures your vehicles stay in optimal condition, reduces unexpected breakdowns, and helps you control maintenance costs.

---

## What Maintenance Management Does for You

### 1. **Preventive Maintenance Scheduling** 📅
- **Schedule recurring maintenance** based on time intervals (e.g., every 3 months) or mileage (e.g., every 10,000 km)
- **Automatic alerts** when maintenance is due
- **Prevents dispatch** of vehicles with overdue maintenance
- **Reduces breakdowns** by maintaining vehicles proactively

**Example Use Cases:**
- Schedule oil changes every 5,000 km
- Schedule tire rotation every 6 months
- Schedule major service every 20,000 km

### 2. **Work Order Management** 🔧
Create and track maintenance work orders with:
- **Work Order Numbering**: Automatic unique work order numbers (WO-2026-001, etc.)
- **Maintenance Types**:
  - **Preventive**: Scheduled maintenance to prevent issues
  - **Corrective**: Fixing existing problems
  - **Major Overhaul**: Complete vehicle overhaul
- **Priority Levels**: Low, Medium, High, Urgent
- **Execution Types**:
  - **In House**: Done by your own technicians
  - **External Vendor**: Done by external service providers
  - **Mixed**: Combination of both

### 3. **Cost Tracking & Budgeting** 💰
- **Estimated Costs**: Plan maintenance expenses upfront
- **Actual Costs**: Track what you actually spent
- **Cost Variance**: Compare estimated vs actual to improve budgeting
- **Cost Classification**: Categorize costs (labor, parts, etc.)

### 4. **Vehicle Status Management** 🚗
- **Automatic Status Updates**: 
  - When work starts → Vehicle status changes to "In Repair"
  - When work completes → Vehicle status changes back to "Available"
- **Prevents Conflicts**: Can't dispatch a vehicle that's in repair
- **Downtime Tracking**: Track how long vehicles are out of service

### 5. **Workflow Management** ✅
- **Status Tracking**: Draft → Pending Approval → Approved → In Progress → Completed
- **Approval Process**: Finance officers can approve/reject work orders
- **Completion Tracking**: Record actual work done, costs, and completion date
- **Timeline View**: See when work was created, approved, started, and completed

### 6. **Vendor & Technician Management** 👥
- **Vendor Selection**: Choose external vendors for maintenance work
- **Technician Assignment**: Assign in-house technicians to work orders
- **Mixed Execution**: Support both vendor and technician on same work order

### 7. **Maintenance History** 📊
- **Complete History**: View all maintenance work done on each vehicle
- **Cost Analysis**: See total maintenance costs per vehicle
- **Frequency Analysis**: Identify vehicles that need frequent repairs
- **Schedule Compliance**: Track which scheduled maintenance was completed on time

---

## How It Works - Step by Step

### Step 1: Create a Maintenance Schedule (One-Time Setup)
1. Go to **Maintenance Management → Schedules**
2. Select a vehicle
3. Set schedule name (e.g., "Oil Change Schedule")
4. Choose interval type (Time-based or Mileage-based)
5. Set interval (e.g., every 5,000 km or every 3 months)
6. Set estimated cost
7. Save

**Result**: System will automatically alert you when maintenance is due.

### Step 2: Create a Work Order
1. Go to **Maintenance Management → Work Orders → Create**
2. Select vehicle (required)
3. Optionally link to a maintenance schedule
4. Choose maintenance type (Preventive/Corrective/Major Overhaul)
5. Set priority (Low/Medium/High/Urgent)
6. Enter work description
7. Choose execution type (In House/External Vendor/Mixed)
8. If external vendor → Select vendor
9. If in-house → Select technician
10. Set scheduled dates
11. Enter estimated cost
12. Add notes
13. Save

**Result**: Work order created in "Draft" status.

### Step 3: Approve Work Order (Finance Officer)
1. View work order details
2. Review estimated costs
3. Click **"Approve"** or **"Reject"**
4. If approved, status changes to "Approved"

**Result**: Work order is ready to be started.

### Step 4: Start Work Order (Maintenance Officer)
1. View approved work order
2. Click **"Start Work Order"**
3. System automatically:
   - Changes work order status to "In Progress"
   - Changes vehicle status to "In Repair"
   - Records start date/time

**Result**: Vehicle is now unavailable for dispatch.

### Step 5: Complete Work Order
1. When work is finished, view the work order
2. Click **"Complete Work Order"**
3. Enter:
   - Actual completion date
   - Actual total cost
   - Actual labor hours (if applicable)
   - Work performed details
4. Save

**Result**: 
- Work order status changes to "Completed"
- Vehicle status changes back to "Available"
- Cost variance is calculated (Actual vs Estimated)

---

## Key Features & Benefits

### ✅ **Prevents Vehicle Breakdowns**
- Scheduled maintenance catches issues before they become major problems
- Reduces unexpected downtime
- Extends vehicle lifespan

### ✅ **Cost Control**
- Track estimated vs actual costs
- Identify cost overruns
- Budget better for future maintenance

### ✅ **Compliance & Safety**
- Ensure vehicles meet safety standards
- Track maintenance compliance
- Maintain service records for audits

### ✅ **Operational Efficiency**
- Know which vehicles are available
- Prevent dispatching vehicles in repair
- Plan maintenance around vehicle usage

### ✅ **Vendor Management**
- Track which vendors perform best
- Compare vendor costs
- Maintain vendor relationships

### ✅ **Reporting & Analytics**
- View maintenance history per vehicle
- Analyze maintenance costs
- Identify vehicles needing frequent repairs
- Track maintenance schedule compliance

---

## Integration with Other Modules

### **Asset Management**
- Vehicles are linked to Asset Management
- Vehicle status updates automatically
- Maintenance costs affect asset value

### **Cost Management**
- Maintenance costs can be tracked as trip costs
- Links to GL accounts for accounting

### **Fuel Management**
- Fuel efficiency can indicate maintenance needs
- Poor fuel efficiency may trigger maintenance alerts

### **Trip Management**
- Vehicles in repair cannot be dispatched
- Maintenance schedules prevent overdue maintenance during trips

---

## Common Scenarios

### Scenario 1: Scheduled Oil Change
1. **Setup**: Create schedule for "Oil Change" every 5,000 km
2. **Alert**: System alerts when vehicle reaches 5,000 km
3. **Create WO**: Create work order from schedule
4. **Approve**: Finance approves the work order
5. **Start**: Maintenance officer starts work
6. **Complete**: Record actual cost and completion
7. **Result**: Vehicle is maintained, history recorded

### Scenario 2: Emergency Repair
1. **Breakdown**: Vehicle breaks down during trip
2. **Create WO**: Create corrective maintenance work order (Urgent priority)
3. **Assign Vendor**: Select external vendor for quick repair
4. **Approve**: Fast-track approval
5. **Start**: Start work immediately
6. **Complete**: Record repair details and costs
7. **Result**: Vehicle back in service, costs tracked

### Scenario 3: Major Overhaul
1. **Create WO**: Create major overhaul work order
2. **Mixed Execution**: Use both vendor and in-house technician
3. **Schedule**: Plan over multiple days
4. **Track Costs**: Monitor costs as work progresses
5. **Complete**: Record all costs and work performed
6. **Result**: Vehicle fully refurbished, complete cost history

---

## Best Practices

1. **Set Up Schedules Early**: Create maintenance schedules when vehicles are added
2. **Regular Reviews**: Review maintenance history monthly
3. **Cost Analysis**: Compare estimated vs actual costs to improve estimates
4. **Priority Management**: Use priority levels to manage urgent vs routine work
5. **Documentation**: Always add detailed work descriptions and notes
6. **Timely Completion**: Complete work orders promptly to keep vehicle status accurate

---

## Summary

The Maintenance Management module helps you:
- ✅ **Schedule** preventive maintenance automatically
- ✅ **Manage** work orders from creation to completion
- ✅ **Track** repairs and maintenance history
- ✅ **Control** maintenance costs
- ✅ **Ensure** vehicle availability and safety
- ✅ **Maintain** compliance and service records

This results in **reduced breakdowns**, **lower maintenance costs**, **better vehicle availability**, and **improved fleet reliability**.
