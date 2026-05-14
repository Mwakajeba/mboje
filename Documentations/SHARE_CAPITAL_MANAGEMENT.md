## Share Capital Management Module Specification

### 1. Purpose & Scope

The **Share Capital Management** module manages all equity instruments and related transactions for the entity, ensuring full compliance with **IFRS** (IAS 1, IAS 32, IFRS 2, IFRS 3, IFRS 7, IFRS 9, IFRS 10, IFRS 12, IFRS 13, IFRS 33) and **IPSAS** (IPSAS 1, IPSAS 3, IPSAS 20, IPSAS 28–32, IPSAS 41) and with local company law.

- **Scope includes**:
  - Initial and subsequent share issues.
  - Partly paid shares, calls, forfeitures, and re‑issues.
  - Buybacks and treasury shares.
  - Dividends (cash and bonus/share dividends).
  - Rights issues, bonus issues, share splits/reverse splits.
  - Conversions (e.g., preference to ordinary, convertible debt to equity).
  - Basic support for share‑based payments (options, RSUs).
  - Full subledger, GL integration, and disclosure reporting.

---

### 2. Core Concepts & Classifications

- **Share classes** (master data):
  - Ordinary/common shares.
  - Preference shares (cumulative/non‑cumulative, redeemable/irreedeemable, convertible/non‑convertible, participating/non‑participating).
  - Other equity instruments (founders’ shares, non‑voting shares, golden shares, etc.).

- **IFRS/IPSAS classification (IAS 32 / IPSAS 28–32)**:
  - **Equity instruments**: No contractual obligation to deliver cash/another financial asset; settlement in own equity instruments on a fixed‑for‑fixed basis.
  - **Financial liabilities**: Instruments that obligate the issuer to deliver cash or another financial asset, or are not fixed‑for‑fixed.
  - **Compound instruments**: Instruments with both liability and equity components (e.g., convertible bonds).

- **Equity structure (GL level)**:
  - `Share Capital – <Class>`
  - `Share Premium / Additional Paid‑in Capital`
  - `Treasury Shares (Contra‑equity)`
  - `Retained Earnings / Accumulated Surplus (Deficit)`
  - `Other Reserves` (capital reserve, revaluation reserve, translation reserve, hedging reserve, etc.)
  - `Non‑controlling Interests` (for groups).

---

### 3. Master Data & Configuration (Logical Design)

#### 3.1 Entity Setup

- **Entity profile**:
  - Legal name, registration number, jurisdiction, regulator.
  - Functional and presentation currency.
  - Reporting framework: `IFRS` or `IPSAS` (and version/year).

- **Capital structure settings**:
  - Whether the entity is for‑profit, public sector, or mixed.
  - Whether ownership interests are state/government or private.
  - Whether consolidated reporting is required (IFRS 10 / IPSAS 35).

#### 3.2 Share Class Master

For each share/instrument class, store at minimum:

- **Identification**:
  - Code, name, description.
  - ISIN or local security code (if applicable).
- **Economic terms**:
  - Par/nominal value and currency (or flag if no par value).
  - Voting rights (full/limited/none).
  - Dividend rights (fixed, discretionary, participating, priority).
  - Liquidation preference and ranking.
- **Special terms**:
  - Redeemable / irredeemable; fixed or discretionary redemption.
  - Cumulative / non‑cumulative dividends.
  - Convertible terms (into which class, ratio, conditions).
  - Callable by issuer, puttable by holder (affects liability vs equity).
- **Accounting classification**:
  - Classification flag: `equity | liability | compound`.
  - Mapping to:
    - `Share Capital – <Class>` (equity portion).
    - `Financial Liability – <Instrument>` (liability portion for compound).
    - `Equity Component of Convertible Instruments` (if compound).

#### 3.3 Authorized Capital

- Per share class:
  - Authorized number of shares.
  - Authorized value (if relevant under local law).
  - Issued, outstanding, and treasury shares counts.
  - **System validation**: prevent issue beyond authorized capital unless a capital increase corporate action is processed and approved.

#### 3.4 Shareholder Master

- **Core fields**:
  - Shareholder ID, legal name, type (individual, corporate, government, director, related party entity, employee).
  - Country of residence, tax ID, registration number.
  - Contact details, banking details (for dividends).
- **Regulatory/IFRS/IPSAS attributes**:
  - Related‑party flags and relationships (IFRS 24 / IPSAS 20).
  - Ultimate beneficial owner information (if required).
  - Public, private, government, institutional, retail classifications.

#### 3.5 Shareholding Ledger (Subledger)

- For each **shareholder + class + lot**:
  - Number of shares held (issued, forfeited, redeemed, converted, cancelled).
  - Paid‑up vs unpaid portion (per share and total).
  - Acquisition date and source (issue, transfer, conversion, corporate action).
  - Issue/transaction price and currency.
  - Status: `active | forfeited | treasury | cancelled | converted`.

- **Design principle**:
  - The shareholding ledger behaves like an **equity subledger**, analogous to AR/AP subledgers, and must reconcile to GL equity accounts at any date.

#### 3.6 Corporate Action Registry

- Corporate actions supported:
  - New issues (primary offerings).
  - Rights issues.
  - Bonus/share issues.
  - Share splits and reverse splits.
  - Buybacks and cancellations.
  - Conversions (preference to ordinary, convertible debt to equity, etc.).
  - Dividend declarations (cash and share).

- Each action record must store:
  - Type, parameters (ratios, prices, record date, payment/execution date).
  - Linked share class(es) and shareholder population (if applicable).
  - Workflow state: `draft | pending approval | approved | executed | cancelled`.
  - References to documents (board/shareholder resolutions, prospectus).
  - Audit trail of approvals and changes.

---

### 4. Workflow: Initial Capitalization & New Issues

#### 4.1 Business Flow

1. Configure share classes and authorized capital.
2. Capture initial subscriptions (cash and non‑cash).
3. Allocate amounts to **share capital** and **share premium** (if applicable).
4. Record direct issue costs, reducing equity (not expensed).
5. Update shareholding ledger and GL.

#### 4.2 Accounting Entries

- **Initial cash subscription at par**  
  Example: 1,000,000 ordinary shares, par value 1.00, fully paid in cash.

  - Dr `Bank` – 1,000,000  
    Cr `Share Capital – Ordinary` – 1,000,000

- **Initial cash subscription above par (with premium)**  
  Example: 1,000,000 ordinary shares, par 1.00, issue price 1.50.

  - Dr `Bank` – 1,500,000  
    Cr `Share Capital – Ordinary` – 1,000,000  
    Cr `Share Premium` – 500,000

- **Non‑cash contribution of assets (at fair value)**  
  Example: PPE contributed at fair value of 5,000,000 for shares of par 1.00 and issue price 2.00.

  - Dr `Property, Plant & Equipment` – 5,000,000  
    Cr `Share Capital – <Class>` – (par value × number of shares)  
    Cr `Share Premium` – (fair value – par value portion)

- **Direct issue costs attributable to equity** (IAS 32 / IPSAS 28–32):

  - Dr `Share Premium / Other Equity Reserve` – issue costs  
    Cr `Bank` – issue costs

---

### 5. Workflow: Subsequent Share Issues (Primary Issues)

#### 5.1 Business Flow

1. Define new issue terms (class, number of shares, price, currency, par value).
2. Optionally manage **application stage** (applications, oversubscriptions, refunds).
3. Allocate and approve allotments.
4. Collect consideration (cash or non‑cash).
5. Update shareholding ledger and GL.

#### 5.2 Accounting Entries

- **With application stage**:

  - On receipt of application money:
    - Dr `Bank` – application money received  
      Cr `Share Application Money Pending Allotment` – liability or equity‑pending account

  - On allotment:
    - Dr `Share Application Money Pending Allotment` – total allotted amount  
      Cr `Share Capital – <Class>` – par value × shares allotted  
      Cr `Share Premium` – excess over par (if any)

  - On refund of excess application money (if oversubscribed and refunded):
    - Dr `Share Application Money Pending Allotment` – refund amount  
      Cr `Bank` – refund amount

- **Without application stage (simple issue)**:

  - Dr `Bank` – gross proceeds  
    Cr `Share Capital – <Class>` – par value × shares  
    Cr `Share Premium` – excess over par

---

### 6. Workflow: Partly Paid Shares & Calls

#### 6.1 Business Flow

1. Issue shares that are partly paid (e.g., 40% paid on application, 60% unpaid).
2. Create scheduled **calls** (Call 1, Call 2, etc.) with due dates and amounts.
3. Generate call notices and track receivables.
4. Process receipts; handle arrears, penalties, and possible forfeiture.
5. Ensure the system can report:
   - Total called‑up capital.
   - Paid‑up vs unpaid capital by shareholder and in total.

#### 6.2 Accounting Entries

- **On issue of partly paid shares** (par 1.00, paid 0.40, unpaid 0.60):

  - Dr `Bank` – 0.40 × shares  
  - Dr `Calls in Arrears / Unpaid Share Capital` – 0.60 × shares  
    Cr `Share Capital – <Class>` – 1.00 × shares

- **On call becoming due** (if using a call receivable account):

  - Dr `Shareholders – Call Receivable` – call amount  
    Cr `Call Money Due` – call amount

- **On receipt of call money**:

  - Dr `Bank` – call amount received  
    Cr `Shareholders – Call Receivable` – call amount received

---

### 7. Workflow: Forfeiture & Re‑issue of Shares

#### 7.1 Forfeiture for Non‑Payment of Calls

**Business flow:**

1. System identifies overdue calls beyond defined grace periods.
2. Based on legal rules and company policy, shares are marked for forfeiture.
3. Forfeiture is approved (board/shareholder) and executed as a corporate action.
4. Shares are removed from the default shareholder’s holding.
5. Amounts already paid are transferred to a **Forfeited Shares / Capital Reserve**.

**Example accounting entry** (par 1.00, paid 0.60, unpaid 0.40):

- Dr `Share Capital – <Class>` – 1.00 × forfeited shares  
  Cr `Forfeited Shares / Capital Reserve` – 0.60 × shares  
  Cr `Calls in Arrears / Unpaid Capital` – 0.40 × shares

#### 7.2 Re‑issue of Forfeited Shares

- **Business rules**:
  - Discount on re‑issue (if any) must not exceed forfeited amounts (jurisdictional).
  - System validates re‑issue price vs forfeiture reserve.

- **Example entry (re‑issue at discount)**: Par 1.00, forfeited with 0.60 paid, re‑issued at 0.90.

  - Dr `Bank` – 0.90 × shares  
  - Dr `Forfeited Shares / Capital Reserve` – 0.10 × shares  
    Cr `Share Capital – <Class>` – 1.00 × shares

- **Transfer remaining forfeiture surplus** (if any) to capital reserve:

  - Dr `Forfeited Shares / Capital Reserve` – remaining balance  
    Cr `Capital Reserve` – remaining balance

---

### 8. Workflow: Share Buybacks & Treasury Shares

*(IAS 32 / IPSAS 28–32: own equity instruments are deducted from equity and are not assets.)*

#### 8.1 Business Flow

1. Configure buyback limits (percentage of paid‑up capital and free reserves; jurisdiction‑specific).
2. Record board/shareholder authorization and terms (maximum size, price range, validity period).
3. Execute buyback transactions (market or off‑market).
4. Classify acquired shares as **Treasury Shares (contra‑equity)**.
5. Decide on future treatment: hold, re‑issue, or cancel.

#### 8.2 Accounting Entries

- **On acquisition of own shares (treasury shares)**:

  - Dr `Treasury Shares (Equity – Contra)` – buyback price  
    Cr `Bank` – buyback price

- **On re‑issue of treasury shares above cost**:

  - Dr `Bank` – re‑issue proceeds  
    Cr `Treasury Shares` – cost of shares  
    Cr `Share Premium / Capital Reserve` – excess over cost

- **On re‑issue of treasury shares below cost**:

  - Dr `Bank` – re‑issue proceeds  
  - Dr `Share Premium / Capital Reserve` – to absorb part of loss (if available)  
  - Dr `Retained Earnings` – remaining loss, if necessary  
    Cr `Treasury Shares` – original cost

- **On cancellation of treasury shares**:

  - Dr `Share Capital – <Class>` – par value × shares cancelled  
  - Dr `Share Premium / Capital Reserve` – if required to balance  
    Cr `Treasury Shares` – carrying cost  
    Cr `Retained Earnings / Other Reserve` – balancing figure

---

### 9. Workflow: Dividends & Distributions

#### 9.1 Cash Dividends

**Business flow:**

1. Propose dividend (per share or total amount), with reference to retained earnings and legal limits.
2. Identify eligible shareholders based on record date.
3. Approve and **declare** dividend.
4. Process payments and withhold taxes where applicable.
5. Track unclaimed dividends and remittances to dormant/unclaimed funds if required.

**Accounting entries:**

- **On declaration of dividend**:

  - Dr `Retained Earnings / Accumulated Surplus` – gross dividend amount  
    Cr `Dividend Payable` – net amount payable to shareholders  
    Cr `Withholding Tax Payable` – tax amount to be remitted

- **On payment of dividend**:

  - Dr `Dividend Payable` – net amount  
    Cr `Bank` – net amount

- **On remittance of withholding tax**:

  - Dr `Withholding Tax Payable` – tax amount  
    Cr `Bank` – tax amount

#### 9.2 Share (Bonus) Dividends

**Business flow:**

1. Decide ratio of bonus issue (e.g., 1 bonus share for every 5 held).
2. Identify eligible shareholders and compute shares to be issued.
3. Capitalize retained earnings or share premium into share capital.
4. Issue bonus shares and update shareholding ledger.

**Accounting entry:**

- Dr `Retained Earnings / Share Premium / Other Reserves` – capitalization amount  
  Cr `Share Capital – <Class>` – par value × bonus shares issued

---

### 10. Workflow: Rights Issues & Bonus Issues

#### 10.1 Rights Issues

**Business flow:**

1. Define rights ratio (e.g., 1 new share for every 4 held), subscription price, and record date.
2. System calculates entitlements per shareholder.
3. Allow subscription, renunciation, and lapse handling.
4. Collect subscription funds and issue new shares to subscribers.
5. Update shareholding ledger and GL.

**Accounting entry (subscription and issue):**

- Dr `Bank` – subscription proceeds  
  Cr `Share Capital – <Class>` – par value × rights shares issued  
  Cr `Share Premium` – excess over par

#### 10.2 Bonus Issues (Capitalization of Reserves)

*(Covered in 9.2 but included here for corporate action completeness.)*

- Dr `Share Premium / Retained Earnings / Other Reserves` – total bonus amount  
  Cr `Share Capital – <Class>` – par value × bonus shares

---

### 11. Workflow: Share Splits, Reverse Splits & Conversions

#### 11.1 Share Splits and Reverse Splits

**Business flow:**

1. Define split ratio (e.g., 1 share of par 10.00 becomes 2 shares of par 5.00).
2. System updates:
   - Number of shares per shareholder.
   - Par value per share.
   - EPS and other per‑share metrics (for disclosure).
3. No change in total share capital or total equity (typically).

**Accounting treatment:**

- Usually **no journal entry**. Only master data and shareholding ledger are adjusted (unless local law requires adjustments between share capital and reserves).

#### 11.2 Conversions (Preference Shares, Convertible Debt, etc.)

**Business flow:**

1. Identify instruments eligible for conversion (by date, trigger events, or holder election).
2. Capture conversion terms (ratio, converted class, rounding, cash settlement elements).
3. Derecognize liability component (if any), recognize share capital and premium.
4. Update shareholding ledger and GL.

**Example entries:**

- **Conversion of preference shares to ordinary equity** (pure equity instruments):

  - Dr `Share Capital – Preference` – par value of converted shares  
    Cr `Share Capital – Ordinary` – par value of new shares  
    (If par differs, adjust via `Share Premium` or `Capital Reserve` as required.)

- **Conversion of convertible bond (compound instrument)**:

  - Dr `Convertible Liability (Debt)` – carrying amount of liability component  
  - Dr `Equity Component of Convertible Bond` – balance of equity component (if kept separately)  
    Cr `Share Capital – <Class>` – par value of shares issued  
    Cr `Share Premium` – balancing figure

---

### 12. Share‑based Payments (High‑Level)

*(IFRS 2 / IPSAS 41 – for a world‑class ERP, even if implemented in a later phase.)*

#### 12.1 Employee Stock Options (ESOP), RSUs, and Similar Schemes

- **Business flow:**
  1. Define plan (grant date, vesting period, performance conditions).
  2. Determine grant‑date fair value (valuation engine or manual input).
  3. Recognize expense over vesting period.
  4. On exercise or vesting, issue shares and move reserves to share capital/premium.

- **Accounting entries:**

  - During vesting:
    - Dr `Staff Cost / Share‑based Payment Expense` – periodic expense  
      Cr `Share‑based Payment Reserve (Equity)` – periodic credit

  - On exercise of options:
    - Dr `Bank` – cash received (exercise price)  
    - Dr `Share‑based Payment Reserve` – cumulative reserve related to options exercised  
      Cr `Share Capital – <Class>` – par value × shares issued  
      Cr `Share Premium` – balancing figure

  - On lapse/forfeiture of unexercised options:
    - Dr `Share‑based Payment Reserve` – lapsed amount  
      Cr `Retained Earnings / Other Reserve` – lapsed amount

---

### 13. IFRS / IPSAS Compliance Features

#### 13.1 Classification & Presentation

- **Key rules (IAS 32 / IPSAS 28–32):**
  - Instruments with an unavoidable obligation to deliver cash/another financial asset are **liabilities**.
  - Instruments settled in own shares must pass the **fixed‑for‑fixed** test to be equity.
  - Compound instruments must be split into liability and equity components on initial recognition.

- **ERP requirements:**
  - Instrument‑level classification flags and logic.
  - Ability to generate:
    - Statement of Financial Position with separate equity line items:
      - Share capital by class.
      - Share premium.
      - Treasury shares (as a deduction).
      - Retained earnings / accumulated surplus.
      - Other reserves.
      - Non‑controlling interests.
    - Statement of Changes in Equity (SOCIE) with detailed movements.

#### 13.2 Disclosures and Note Support

- **Share capital notes**:
  - Reconciliation of opening to closing balances by share class:
    - Opening balance (shares and amount).
    - Issues, buybacks, conversions, bonus issues, splits.
    - Closing balance (shares and amount).
  - Rights, preferences, and restrictions by class.

- **Capital management disclosures** (IFRS 7 / IAS 1 / IPSAS equivalents):
  - Quantitative information about capital structure and gearing.
  - Details of externally imposed capital requirements (e.g., covenants).

- **Ownership interest and related‑party disclosures** (IFRS 12, IFRS 24 / IPSAS 20):
  - Major shareholders and ownership percentages.
  - Interests of key management and related parties.

---

### 14. Controls, Workflow & Audit Trail

#### 14.1 Approval Workflows

- Configurable, multi‑level approvals for:
  - New share issues.
  - Capital reductions and buybacks.
  - Dividends (proposal, approval, declaration).
  - Corporate actions (rights, bonus, splits, reverse splits, conversions).
- Support:
  - Maker–checker segregation.
  - Separate approval for financial posting vs legal corporate action.

#### 14.2 Validation Rules

- Do not allow:
  - Issued capital to exceed authorized capital without approved increase.
  - Buybacks beyond regulatory limits or available distributable reserves.
  - Negative share counts, par values, or paid‑up amounts.
- Require:
  - Record date and ex‑date consistency checks for dividends and rights.
  - Locking of share capital movements after period close without special override.

#### 14.3 Audit Trail

- Log every:
  - Creation, update, approval, and execution of corporate actions.
  - Change to share class parameters and authorized capital.
  - Change in shareholder holdings (lot‑level).
- Store:
  - User ID, timestamp, old value, new value, and reason/justification.

---

### 15. Integration with GL & Reporting

#### 15.1 Subledger–GL Integration

- Treat Share Capital Management as an **equity subledger**:
  - Every subledger transaction generates balanced GL entries.
  - Configurable account mappings by share class and transaction type.
  - Periodic reconciliation reports:
    - Shareholding ledger totals vs GL balances for:
      - Share capital.
      - Share premium.
      - Treasury shares.
      - Forfeited shares/capital reserves.

#### 15.2 Reporting & Analytics

- Core reports:
  - Share register by shareholder and share class with ownership percentages.
  - Movements in share capital and reserves over time.
  - Dividend history (by share class, by shareholder, by period).
  - Buyback and treasury share activity.
  - EPS and diluted EPS support (IFRS 33) – linkage to profit data.
- Public sector specifics (IPSAS):
  - Ownership interests in controlled entities and contributions from owners.
  - Distinction between **contributions from owners** and **non‑exchange revenue**.

---

### 16. Minimum Viable vs World‑Class Implementation

- **Minimum compliant version:**
  - Master data for entities, share classes, and shareholders.
  - Core workflows:
    - Initial and subsequent issues (fully paid).
    - Cash dividends.
    - Simple buybacks and cancellations.
  - Correct IFRS/IPSAS classification and basic SOCIE support.

- **World‑class version:**
  - Full corporate actions engine (rights, bonus, splits, forfeiture, re‑issues, complex buybacks, conversions).
  - Share‑based payments module.
  - Configurable multi‑level workflows and comprehensive audit trails.
  - Automated note and disclosure generation in IFRS/IPSAS format.
  - What‑if simulations for capital structure changes (impact on leverage, EPS, ownership).

---

This specification is intended to be directly implementable in your ERP (e.g., as database models, services, and APIs). You can now map each workflow into detailed screens, routes, and controller logic in your application.


