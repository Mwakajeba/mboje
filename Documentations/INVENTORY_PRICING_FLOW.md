# Inventory pricing – full flow

## When do I see “Prices by branch” and “Prices by location”?

**Only on the Edit Item form.**  
On **Create Item** you only set the **default** cost and selling price. After the item is saved, open it again with **Edit** – then you’ll see **Prices by branch** and **Prices by location** below the default pricing section.

---

## Full flow (step by step)

### 1. Create the item (one time)

- Go to **Inventory → Items → Create New Item**.
- Fill in identity: Code, Name, Category, Unit of measure, etc.
- Set **Default cost price** and **Default selling price** (required). These are the fallbacks used when no branch/location price is set.
- Save.

You do **not** set branch/location prices here; that happens in Edit.

---

### 2. Set branch and location prices (Edit)

- Go to **Inventory → Items**, find the item, click **Edit**.
- You’ll see:
  - **Pricing Information** (same default cost and selling price as on create).
  - **Prices by branch** – a table with one row per branch. Enter **Cost price** and **Selling price** per branch. Leave a cell blank to use the default.
  - **Prices by location (optional)** – a table with one row per location. Enter prices per location. Blank = use branch price, or default if branch has no price.
- Click **Update Item**.

So: **Create = save default only; Edit = set branch/location prices.**

---

### 3. How the system chooses the price (when selling)

When you add an item to a sale (Cash Sale, POS, Invoice, Order, etc.) the system uses:

1. **Location price** (if set for the current location)  
2. Else **Branch price** (if set for the current branch)  
3. Else **Default** (the item’s default cost/selling price from the form)

The **current** branch/location comes from the user’s session (the branch and location they’re working in). So the same item can show a different price in different branches/locations.

---

### 4. Where prices are stored

| What | Where |
|------|--------|
| Default cost & selling price | Item record (Pricing Information on Create/Edit) |
| Price per branch | **Edit Item** → “Prices by branch” table → saved to `inventory_item_prices` |
| Price per location | **Edit Item** → “Prices by location” table → saved to `inventory_item_location_prices` |

Changing a branch or location price does **not** change the item’s default; it only updates the branch/location table.

---

### 5. Quick reference

| Action | Where |
|--------|--------|
| Create item and set default price | **Inventory → Items → Create** |
| Set/edit branch and location prices | **Inventory → Items → [Item] → Edit** (scroll to “Prices by branch” and “Prices by location”) |
| See which price is used on a sale | The price shown when adding the item in Cash Sale / POS / Invoice / etc. is the resolved price for the current branch/location. |

---

## 6. How pricing is used in sales

In **sales** (Cash Sale, POS, Sales Invoice, Sales Order, Proforma, Credit Note, Delivery), the system uses item pricing as follows.

### Selling price (what the customer pays)

- When you **add an item** to a sale, the **unit price** shown and stored is the **resolved selling price** for the current branch/location:
  - **Location price** (if set) → else **Branch price** (if set) → else **Default selling price** from the item.
- This applies in:
  - **Cash Sale** – item dropdown and “get item” API use resolved price.
  - **POS** – product list and item lookup use resolved price.
  - **Sales Invoice** – item dropdown and get-inventory-item API use resolved price.
  - **Sales Order** – item dropdown and get-item-details API use resolved price.
  - **Proforma** – same as order.
  - **Credit Note** – item dropdown and get-inventory-item API use resolved price.
  - **Delivery** – item list uses resolved price when adding items.
- The **price stored on the sale line** (e.g. invoice line, order line) is the value at the time the line was added (the resolved price for that branch/location). Reports and documents use this stored value.

### Cost (for profit/COGS)

- Where the system needs **cost** (e.g. POS cost price, reports), it uses the **resolved cost** for the current branch/location:
  - **Location cost** (if set) → else **Branch cost** (if set) → else **Default cost price** from the item.
- Profit and COGS reports use the **stored line cost** when available (e.g. from cost layers at time of sale); otherwise they use the item’s cost (or resolved cost where implemented).

---

## 7. How pricing is used in purchases

In **purchases** (Cash Purchase, Purchase Invoice, Purchase Order, Quotation, Debit Note, GRN), the system uses **the same resolution flow as sales** for **cost** (location → branch → default). Purchases do **not** use selling price.

### Same flow as sales: location → branch → default

- **Cost** on purchase documents (unit cost per line) is resolved the same way as in sales:
  1. **Location cost** (if set for the current location)  
  2. Else **Branch cost** (if set for the current branch)  
  3. Else **Default cost price** from the item.
- So when you add an item to a **Purchase Order**, **Cash Purchase**, **Purchase Invoice**, **Quotation**, **Debit Note**, or **GRN**, the **prefilled unit cost** is the resolved cost for the current branch/location. You can still change it before saving.
- **Debit Note** “get inventory item” API and all purchase create/edit views that list items use this resolved cost for dropdowns and prefills.

### Cost (what you pay the supplier)

- **Purchases deal with cost only** (no selling price). Each line has a **unit cost** (and quantity, VAT, etc.).
- **Unit cost** is **prefilled** with the **resolved cost** (location → branch → default) and can be **edited** by the user (e.g. to match the supplier’s actual price).
- The **cost stored on the purchase line** is what you entered or confirmed. That value is used for:
  - **Inventory cost layers** (e.g. when receiving stock from a purchase),
  - **Reports** (purchase value, supplier spend),
  - **COGS** when the stock is later sold (via cost layers).

### No selling price in purchases

- Purchases do **not** use selling price. Selling price is only used in **sales** (see section 6).

---

## 8. Opening balance and import – which cost?

### Opening balance (create form)

- You enter **quantity** and **unit cost** per line. The **unit cost** is **whatever you type** (or leave as prefilled).
- When you **select an item** in the “Add item” modal, the **Unit cost** field is **prefilled** with the **resolved cost** for the current branch/location (location → branch → default), so it matches your “Prices by branch” / “Prices by location” if you have set them. You can still change it before adding the line.
- So: **prefill** = resolved cost; **saved** = what you entered (your value or the prefill).

### Opening balance (import from CSV)

- The CSV must include a **`unit_cost`** column. The system uses **that value** for each row; it does **not** use the item’s default or branch/location cost.
- So: cost = **from your CSV** (you decide the cost in the file).

### Item import (import items from CSV)

- This creates/updates **item masters** (name, code, etc.). It sets each item’s **default** `cost_price` and `unit_price` from the CSV columns. It does **not** create opening balance movements or set branch/location prices.
- So: **default cost and selling price** on the item = from CSV (or blank); **no** location → branch → default resolution here (that applies when you **use** the item in sales/purchases/opening balance).

---

**Summary:** Create the item with default price → Edit the item → set **Prices by branch** and **Prices by location** on the Edit form. Those sections are only visible when editing an existing item. In **sales**, the system uses resolved **selling price** (and cost where needed): location → branch → default. In **purchases**, the system uses the **same resolution** for **cost** only: location → branch → default (prefilled; user can override).
