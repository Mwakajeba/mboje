import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../services/parent_api_service.dart';
import '../../providers/language_provider.dart';
import 'invoice_details_screen.dart';
import 'prepaid_transactions_screen.dart';

// Fees Page
class FeesPage extends StatefulWidget {
  final int studentId;
  const FeesPage({super.key, required this.studentId});

  @override
  State<FeesPage> createState() => _FeesPageState();
}

class _FeesPageState extends State<FeesPage> {
  Map<String, dynamic>? feesData;
  bool isLoading = true;
  bool _isFeesSummaryExpanded = true; // Expanded by default to show all information

  @override
  void initState() {
    super.initState();
    _loadFees();
  }

  Future<void> _loadFees() async {
    final data = await ParentApiService.getStudentFees(widget.studentId);
    if (mounted) {
      setState(() {
        feesData = data;
        isLoading = false;
      });
      // Debug: Print fees data structure
      print('=== FEES DATA DEBUG ===');
      print('Summary: ${data?['summary']}');
      print('Opening Balance: ${data?['summary']?['opening_balance']}');
      print('Prepaid Balance: ${data?['summary']?['prepaid_balance']}');
      print('Prepaid Balance Type: ${data?['summary']?['prepaid_balance'].runtimeType}');
      print('Has Opening Balance: ${data?['summary']?['opening_balance'] != null}');
      print('Has Prepaid Balance: ${data?['summary']?['prepaid_balance'] != null}');
    }
  }

  @override
  Widget build(BuildContext context) {
    final languageProvider = Provider.of<LanguageProvider>(context);
    final trans = AppTranslations(languageProvider.currentLanguage);
    
    return Scaffold(
      backgroundColor: Colors.grey.shade50,
      appBar: AppBar(
        elevation: 0,
        flexibleSpace: Container(
          decoration: BoxDecoration(
            gradient: LinearGradient(
              colors: [Colors.blue.shade700, Colors.blue.shade500],
              begin: Alignment.topLeft,
              end: Alignment.bottomRight,
            ),
          ),
        ),
        title: Text(
          trans.get('fees_title'),
          style: TextStyle(
            fontWeight: FontWeight.bold,
            fontSize: 22,
            letterSpacing: 0.5,
          ),
        ),
        foregroundColor: Colors.white,
      ),
      body: isLoading
          ? Center(
              child: Column(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  CircularProgressIndicator(
                    valueColor: AlwaysStoppedAnimation<Color>(Colors.blue.shade700),
                  ),
                  const SizedBox(height: 16),
                  Text(
                    'Inapakia...',
                    style: TextStyle(
                      color: Colors.grey.shade600,
                      fontSize: 14,
                    ),
                  ),
                ],
              ),
            )
          : feesData == null
              ? Center(
                  child: Column(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      Container(
                        padding: const EdgeInsets.all(24),
                        decoration: BoxDecoration(
                          color: Colors.grey.shade100,
                          shape: BoxShape.circle,
                        ),
                        child: Icon(
                          Icons.payment_outlined,
                          size: 64,
                          color: Colors.grey.shade400,
                        ),
                      ),
                      const SizedBox(height: 24),
                      Text(
                        trans.get('no_fees_data'),
                        style: TextStyle(
                          fontSize: 16,
                          color: Colors.grey.shade600,
                          fontWeight: FontWeight.w500,
                        ),
                      ),
                    ],
                  ),
                )
              : Container(
                  decoration: BoxDecoration(
                    gradient: LinearGradient(
                      begin: Alignment.topCenter,
                      end: Alignment.bottomCenter,
                      colors: [
                        Colors.blue.shade50,
                        Colors.grey.shade50,
                      ],
                    ),
                  ),
                  child: SingleChildScrollView(
                    child: Column(
                      children: [
                        // Summary Header Card - Collapsible
                        Container(
                          margin: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                          decoration: BoxDecoration(
                            gradient: LinearGradient(
                              colors: [Colors.blue.shade700, Colors.blue.shade500],
                              begin: Alignment.topLeft,
                              end: Alignment.bottomRight,
                            ),
                            borderRadius: BorderRadius.circular(12),
                            boxShadow: [
                              BoxShadow(
                                color: Colors.blue.withOpacity(0.2),
                                blurRadius: 8,
                                offset: const Offset(0, 2),
                              ),
                            ],
                          ),
                          child: Column(
                            mainAxisSize: MainAxisSize.min,
                            children: [
                              // Clickable Header
                              InkWell(
                                onTap: () {
                                  setState(() {
                                    _isFeesSummaryExpanded = !_isFeesSummaryExpanded;
                                  });
                                },
                                borderRadius: BorderRadius.circular(12),
                                child: Padding(
                                  padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
                                  child: Row(
                                    children: [
                                      Container(
                                        padding: const EdgeInsets.all(6),
                                        decoration: BoxDecoration(
                                          color: Colors.white.withOpacity(0.2),
                                          borderRadius: BorderRadius.circular(8),
                                        ),
                                        child: const Icon(
                                          Icons.account_balance_wallet,
                                          color: Colors.white,
                                          size: 16,
                                        ),
                                      ),
                                      const SizedBox(width: 10),
                                      Expanded(
                                        child: Text(
                                          trans.get('payment_summary'),
                                          style: const TextStyle(
                                            color: Colors.white,
                                            fontSize: 14,
                                            fontWeight: FontWeight.bold,
                                          ),
                                        ),
                                      ),
                                      Icon(
                                        _isFeesSummaryExpanded ? Icons.expand_less : Icons.expand_more,
                                        color: Colors.white,
                                        size: 20,
                                      ),
                                    ],
                                  ),
                                ),
                              ),
                              // Expandable Content
                              if (_isFeesSummaryExpanded) ...[
                                Padding(
                                  padding: const EdgeInsets.fromLTRB(12, 0, 12, 12),
                                  child: Column(
                                    children: [
                                      // Clear Payment Summary - Invoice Amount, Opening Balance, Total Debt
                                      Builder(
                                        builder: (context) {
                                          final summary = feesData!['summary'];
                                          final invoiceAmount = summary?['total_amount'] ?? 0.0;
                                          final openingBalance = summary?['opening_balance'];
                                          final openingBalanceAmount = openingBalance != null 
                                              ? (openingBalance['balance_due'] ?? openingBalance['amount'] ?? 0.0)
                                              : 0.0;
                                          final totalDebt = (summary?['due_amount'] ?? 0.0) + openingBalanceAmount;
                                          
                                          return Column(
                                            children: [
                                              // Invoice Amount
                                              Container(
                                                padding: const EdgeInsets.all(12),
                                                decoration: BoxDecoration(
                                                  color: Colors.white.withOpacity(0.2),
                                                  borderRadius: BorderRadius.circular(8),
                                                  border: Border.all(color: Colors.white.withOpacity(0.3)),
                                                ),
                                                child: Row(
                                                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                                                  children: [
                                                    Row(
                                                      children: [
                                                        const Icon(Icons.receipt, color: Colors.white, size: 20),
                                                        const SizedBox(width: 8),
                                                        Text(
                                                          'Jumla ya Ada (Invoices)',
                                                          style: TextStyle(
                                                            color: Colors.white.withOpacity(0.9),
                                                            fontSize: 13,
                                                            fontWeight: FontWeight.w500,
                                                          ),
                                                        ),
                                                      ],
                                                    ),
                                                    Text(
                                                      'TZS ${_formatAmount(invoiceAmount)}',
                                                      style: const TextStyle(
                                                        color: Colors.white,
                                                        fontSize: 14,
                                                        fontWeight: FontWeight.bold,
                                                      ),
                                                    ),
                                                  ],
                                                ),
                                              ),
                                              const SizedBox(height: 8),
                                              // Opening Balance
                                              if (openingBalance != null)
                                                Container(
                                                  padding: const EdgeInsets.all(12),
                                                  decoration: BoxDecoration(
                                                    color: Colors.white.withOpacity(0.2),
                                                    borderRadius: BorderRadius.circular(8),
                                                    border: Border.all(color: Colors.white.withOpacity(0.3)),
                                                  ),
                                                  child: Row(
                                                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                                                    children: [
                                                      Row(
                                                        children: [
                                                          const Icon(Icons.account_balance, color: Colors.white, size: 20),
                                                          const SizedBox(width: 8),
                                                          Text(
                                                            'Salio la Mwanzo',
                                                            style: TextStyle(
                                                              color: Colors.white.withOpacity(0.9),
                                                              fontSize: 13,
                                                              fontWeight: FontWeight.w500,
                                                            ),
                                                          ),
                                                        ],
                                                      ),
                                                      Text(
                                                        'TZS ${_formatAmount(openingBalanceAmount)}',
                                                        style: const TextStyle(
                                                          color: Colors.white,
                                                          fontSize: 14,
                                                          fontWeight: FontWeight.bold,
                                                        ),
                                                      ),
                                                    ],
                                                  ),
                                                ),
                                              if (openingBalance != null) const SizedBox(height: 8),
                                              // Total Debt
                                              Container(
                                                padding: const EdgeInsets.all(12),
                                                decoration: BoxDecoration(
                                                  color: Colors.orange.withOpacity(0.3),
                                                  borderRadius: BorderRadius.circular(8),
                                                  border: Border.all(color: Colors.orange.withOpacity(0.5), width: 1.5),
                                                ),
                                                child: Row(
                                                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                                                  children: [
                                                    Row(
                                                      children: [
                                                        const Icon(Icons.warning_amber_rounded, color: Colors.white, size: 20),
                                                        const SizedBox(width: 8),
                                                        Text(
                                                          'Jumla ya Deni',
                                                          style: const TextStyle(
                                                            color: Colors.white,
                                                            fontSize: 14,
                                                            fontWeight: FontWeight.bold,
                                                          ),
                                                        ),
                                                      ],
                                                    ),
                                                    Text(
                                                      'TZS ${_formatAmount(totalDebt)}',
                                                      style: const TextStyle(
                                                        color: Colors.white,
                                                        fontSize: 16,
                                                        fontWeight: FontWeight.bold,
                                                      ),
                                                    ),
                                                  ],
                                                ),
                                              ),
                                              const SizedBox(height: 8),
                                              // Prepaid Account with View Transactions
                                              Builder(
                                                builder: (context) {
                                                  final prepaidBalance = summary?['prepaid_balance'];
                                                  if (prepaidBalance == null) {
                                                    return const SizedBox.shrink();
                                                  }
                                                  
                                                  return Container(
                                                    padding: const EdgeInsets.all(12),
                                                    decoration: BoxDecoration(
                                                      color: Colors.green.withOpacity(0.2),
                                                      borderRadius: BorderRadius.circular(8),
                                                      border: Border.all(color: Colors.green.withOpacity(0.5)),
                                                    ),
                                                    child: Column(
                                                      children: [
                                                        Row(
                                                          mainAxisAlignment: MainAxisAlignment.spaceBetween,
                                                          children: [
                                                            Row(
                                                              children: [
                                                                const Icon(Icons.account_balance_wallet, color: Colors.white, size: 20),
                                                                const SizedBox(width: 8),
                                                                Text(
                                                                  'Akaunti ya Malipo',
                                                                  style: TextStyle(
                                                                    color: Colors.white.withOpacity(0.9),
                                                                    fontSize: 13,
                                                                    fontWeight: FontWeight.w500,
                                                                  ),
                                                                ),
                                                              ],
                                                            ),
                                                            Text(
                                                              'TZS ${_formatAmount(prepaidBalance)}',
                                                              style: const TextStyle(
                                                                color: Colors.white,
                                                                fontSize: 14,
                                                                fontWeight: FontWeight.bold,
                                                              ),
                                                            ),
                                                          ],
                                                        ),
                                                        const SizedBox(height: 8),
                                                        SizedBox(
                                                          width: double.infinity,
                                                          child: OutlinedButton.icon(
                                                            onPressed: () {
                                                              Navigator.push(
                                                                context,
                                                                MaterialPageRoute(
                                                                  builder: (context) => PrepaidTransactionsScreen(
                                                                    studentId: widget.studentId,
                                                                  ),
                                                                ),
                                                              );
                                                            },
                                                            icon: const Icon(Icons.history, size: 16),
                                                            label: const Text('Angalia Matumizi'),
                                                            style: OutlinedButton.styleFrom(
                                                              foregroundColor: Colors.white,
                                                              side: const BorderSide(color: Colors.white),
                                                              padding: const EdgeInsets.symmetric(vertical: 8),
                                                            ),
                                                          ),
                                                        ),
                                                      ],
                                                    ),
                                                  );
                                                },
                                              ),
                                            ],
                                          );
                                        },
                                      ),
                                    ],
                                  ),
                                ),
                              ],
                            ],
                          ),
                        ),
                      // Opening Balance Card (similar to invoice cards)
                      Builder(
                        builder: (context) {
                          final openingBalance = feesData!['summary']?['opening_balance'];
                          if (openingBalance == null) {
                            return const SizedBox.shrink();
                          }
                          
                          return Padding(
                            padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                            child: _buildOpeningBalanceCard(openingBalance, trans),
                          );
                        },
                      ),
                      // Invoices List
                      ListView.builder(
                        shrinkWrap: true,
                        physics: const NeverScrollableScrollPhysics(),
                        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                        itemCount: (feesData!['invoices'] as List?)?.length ?? 0,
                        itemBuilder: (context, index) {
                          final invoice = feesData!['invoices'][index];
                          return _buildInvoiceCard(invoice, trans);
                        },
                      ),
                    ],
                  ),
                ),
              ),
    );
  }

  Widget _buildSummaryCard(String label, String value, IconData icon, Color iconColor) {
    return Container(
      padding: const EdgeInsets.symmetric(vertical: 8, horizontal: 6),
      decoration: BoxDecoration(
        color: Colors.white.withOpacity(0.2),
        borderRadius: BorderRadius.circular(8),
        border: Border.all(
          color: Colors.white.withOpacity(0.3),
          width: 1,
        ),
      ),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(icon, color: iconColor, size: 16),
          const SizedBox(height: 4),
          Text(
            value,
            style: const TextStyle(
              color: Colors.white,
              fontSize: 12,
              fontWeight: FontWeight.bold,
            ),
            maxLines: 1,
            overflow: TextOverflow.ellipsis,
            textAlign: TextAlign.center,
          ),
          const SizedBox(height: 2),
          Text(
            label,
            style: TextStyle(
              color: Colors.white.withOpacity(0.9),
              fontSize: 10,
            ),
            textAlign: TextAlign.center,
          ),
        ],
      ),
    );
  }

  Widget _buildInvoiceCard(Map<String, dynamic> invoice, AppTranslations trans) {
    final status = (invoice['status']?.toString() ?? 'pending').toLowerCase();
    final isPaid = status == 'paid';
    final statusColor = isPaid ? Colors.green : Colors.orange;
    final statusIcon = isPaid ? Icons.check_circle : Icons.pending;
    
    return Container(
      margin: const EdgeInsets.only(bottom: 16),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withOpacity(0.05),
            blurRadius: 10,
            offset: const Offset(0, 2),
          ),
        ],
      ),
      child: Material(
        color: Colors.transparent,
        child: InkWell(
          onTap: () {
            // TODO: Navigate to invoice details
          },
          borderRadius: BorderRadius.circular(16),
          child: Padding(
            padding: const EdgeInsets.all(20),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  children: [
                    Container(
                      padding: const EdgeInsets.all(12),
                      decoration: BoxDecoration(
                        color: statusColor.withOpacity(0.1),
                        borderRadius: BorderRadius.circular(12),
                      ),
                      child: Icon(statusIcon, color: statusColor, size: 24),
                    ),
                    const SizedBox(width: 16),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            (invoice['invoice_number']?.toString() ?? 'Invoice'),
                            style: const TextStyle(
                              fontSize: 18,
                              fontWeight: FontWeight.bold,
                            ),
                          ),
                          const SizedBox(height: 4),
                          Container(
                            padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                            decoration: BoxDecoration(
                              color: statusColor.withOpacity(0.1),
                              borderRadius: BorderRadius.circular(20),
                              border: Border.all(color: statusColor, width: 1.5),
                            ),
                            child: Text(
                              status.toUpperCase(),
                              style: TextStyle(
                                fontSize: 12,
                                fontWeight: FontWeight.bold,
                                color: statusColor,
                              ),
                            ),
                          ),
                        ],
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 20),
                _buildInvoiceRow(Icons.calendar_today, trans.get('period'), (invoice['period']?.toString() ?? 'N/A')),
                const SizedBox(height: 12),
                _buildInvoiceRow(Icons.category, trans.get('fee_group'), (invoice['fee_group']?.toString() ?? 'N/A')),
                // Show control number if LIPISHA is enabled and control number exists
                if (feesData!['lipisha_enabled'] == true && invoice['lipisha_control_number'] != null) ...[
                  const SizedBox(height: 12),
                  Container(
                    padding: const EdgeInsets.all(12),
                    decoration: BoxDecoration(
                      color: Colors.blue.shade50,
                      borderRadius: BorderRadius.circular(8),
                      border: Border.all(color: Colors.blue.shade200),
                    ),
                    child: Row(
                      children: [
                        Icon(Icons.qr_code, color: Colors.blue.shade700, size: 20),
                        const SizedBox(width: 8),
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(
                                'Nambari ya Udhibiti (LIPISHA)',
                                style: TextStyle(
                                  fontSize: 11,
                                  color: Colors.grey.shade600,
                                ),
                              ),
                              const SizedBox(height: 4),
                              Text(
                                invoice['lipisha_control_number'],
                                style: TextStyle(
                                  fontSize: 14,
                                  fontWeight: FontWeight.bold,
                                  color: Colors.blue.shade700,
                                ),
                              ),
                            ],
                          ),
                        ),
                      ],
                    ),
                  ),
                ],
                const SizedBox(height: 20),
                Container(
                  padding: const EdgeInsets.all(16),
                  decoration: BoxDecoration(
                    color: Colors.grey.shade50,
                    borderRadius: BorderRadius.circular(12),
                  ),
                  child: Row(
                    children: [
                      Expanded(
                        child: _buildAmountItem(
                          trans.get('total_fee'),
                          _formatAmount(invoice['total_amount']),
                          Colors.blue,
                        ),
                      ),
                      Container(width: 1, height: 40, color: Colors.grey.shade300),
                      Expanded(
                        child: _buildAmountItem(
                          trans.get('paid'),
                          _formatAmount(invoice['paid_amount']),
                          Colors.green,
                        ),
                      ),
                      Container(width: 1, height: 40, color: Colors.grey.shade300),
                      Expanded(
                        child: _buildAmountItem(
                          trans.get('balance'),
                          _formatAmount(invoice['due_amount']),
                          Colors.orange,
                        ),
                      ),
                    ],
                  ),
                ),
                const SizedBox(height: 16),
                // View Details Button
                SizedBox(
                  width: double.infinity,
                  child: ElevatedButton.icon(
                    onPressed: () {
                      Navigator.push(
                        context,
                        MaterialPageRoute(
                          builder: (context) => InvoiceDetailsPage(
                            studentId: widget.studentId,
                            invoiceId: invoice['id'],
                          ),
                        ),
                      );
                    },
                    icon: const Icon(Icons.visibility, size: 18),
                    label: const Text('Angalia Maelezo'),
                    style: ElevatedButton.styleFrom(
                      backgroundColor: Colors.blue.shade700,
                      foregroundColor: Colors.white,
                      padding: const EdgeInsets.symmetric(vertical: 12),
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(12),
                      ),
                    ),
                  ),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }

  Widget _buildOpeningBalanceCard(Map<String, dynamic> openingBalance, AppTranslations trans) {
    final balanceDue = openingBalance['balance_due'] ?? openingBalance['amount'] ?? 0.0;
    final amount = openingBalance['amount'] ?? 0.0;
    final paidAmount = openingBalance['paid_amount'] ?? 0.0;
    final openingDate = openingBalance['opening_date'];
    final controlNumber = openingBalance['lipisha_control_number'];
    final hasControlNumber = feesData!['lipisha_enabled'] == true && controlNumber != null;
    
    return Container(
      margin: const EdgeInsets.only(bottom: 16),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        boxShadow: [
          BoxShadow(
            color: Colors.orange.withOpacity(0.1),
            blurRadius: 10,
            offset: const Offset(0, 2),
          ),
        ],
        border: Border.all(color: Colors.orange.shade200, width: 1.5),
      ),
      child: Material(
        color: Colors.transparent,
        child: Padding(
          padding: const EdgeInsets.all(20),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                children: [
                  Container(
                    padding: const EdgeInsets.all(12),
                    decoration: BoxDecoration(
                      color: Colors.orange.withOpacity(0.1),
                      borderRadius: BorderRadius.circular(12),
                    ),
                    child: const Icon(Icons.account_balance, color: Colors.orange, size: 24),
                  ),
                  const SizedBox(width: 16),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        const Text(
                          'Salio la Mwanzo',
                          style: TextStyle(
                            fontSize: 18,
                            fontWeight: FontWeight.bold,
                          ),
                        ),
                        const SizedBox(height: 4),
                        Container(
                          padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                          decoration: BoxDecoration(
                            color: Colors.orange.withOpacity(0.1),
                            borderRadius: BorderRadius.circular(20),
                            border: Border.all(color: Colors.orange, width: 1.5),
                          ),
                          child: const Text(
                            'OPENING BALANCE',
                            style: TextStyle(
                              fontSize: 12,
                              fontWeight: FontWeight.bold,
                              color: Colors.orange,
                            ),
                          ),
                        ),
                      ],
                    ),
                  ),
                ],
              ),
              if (openingDate != null) ...[
                const SizedBox(height: 20),
                _buildInvoiceRow(Icons.calendar_today, 'Tarehe ya Mwanzo', _formatDateString(openingDate)),
              ],
              // Show control number if LIPISHA is enabled
              if (hasControlNumber) ...[
                const SizedBox(height: 12),
                Container(
                  padding: const EdgeInsets.all(12),
                  decoration: BoxDecoration(
                    color: Colors.blue.shade50,
                    borderRadius: BorderRadius.circular(8),
                    border: Border.all(color: Colors.blue.shade200),
                  ),
                  child: Row(
                    children: [
                      Icon(Icons.qr_code, color: Colors.blue.shade700, size: 20),
                      const SizedBox(width: 8),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              'Nambari ya Udhibiti (LIPISHA)',
                              style: TextStyle(
                                fontSize: 11,
                                color: Colors.grey.shade600,
                              ),
                            ),
                            const SizedBox(height: 4),
                            Text(
                              controlNumber,
                              style: TextStyle(
                                fontSize: 14,
                                fontWeight: FontWeight.bold,
                                color: Colors.blue.shade700,
                              ),
                            ),
                          ],
                        ),
                      ),
                    ],
                  ),
                ),
              ],
              const SizedBox(height: 20),
              Container(
                padding: const EdgeInsets.all(16),
                decoration: BoxDecoration(
                  color: Colors.grey.shade50,
                  borderRadius: BorderRadius.circular(12),
                ),
                child: Row(
                  children: [
                    Expanded(
                      child: _buildAmountItem(
                        'Kiasi cha Mwanzo',
                        _formatAmount(amount),
                        Colors.blue,
                      ),
                    ),
                    Container(width: 1, height: 40, color: Colors.grey.shade300),
                    Expanded(
                      child: _buildAmountItem(
                        'Imelipwa',
                        _formatAmount(paidAmount),
                        Colors.green,
                      ),
                    ),
                    Container(width: 1, height: 40, color: Colors.grey.shade300),
                    Expanded(
                      child: _buildAmountItem(
                        'Deni',
                        _formatAmount(balanceDue),
                        Colors.orange,
                      ),
                    ),
                  ],
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildInvoiceRow(IconData icon, String label, String value) {
    return Row(
      children: [
        Icon(icon, size: 18, color: Colors.grey.shade600),
        const SizedBox(width: 12),
        Text(
          label,
          style: TextStyle(
            fontSize: 14,
            color: Colors.grey.shade600,
          ),
        ),
        const Spacer(),
        Text(
          value,
          style: const TextStyle(
            fontSize: 14,
            fontWeight: FontWeight.w600,
          ),
        ),
      ],
    );
  }

  Widget _buildAmountItem(String label, String value, Color color) {
    return Column(
      children: [
        Text(
          'TSH',
          style: TextStyle(
            fontSize: 12,
            fontWeight: FontWeight.bold,
            color: color,
          ),
        ),
        const SizedBox(height: 4),
        Text(
          value,
          style: TextStyle(
            fontSize: 16,
            fontWeight: FontWeight.bold,
            color: color,
          ),
        ),
        Text(
          label,
          style: TextStyle(
            fontSize: 11,
            color: Colors.grey.shade600,
          ),
        ),
      ],
    );
  }

  String _formatDateString(String? dateString) {
    if (dateString == null) return 'N/A';
    try {
      final date = DateTime.parse(dateString);
      return '${date.day}/${date.month}/${date.year}';
    } catch (e) {
      return dateString;
    }
  }

  String _formatAmount(dynamic amount) {
    if (amount == null) return '0.00';
    try {
      final numValue = amount is int ? amount.toDouble() : (amount is double ? amount : double.tryParse(amount.toString()) ?? 0.0);
      final formatted = numValue.toStringAsFixed(2);
      // Add comma separators
      final parts = formatted.split('.');
      final integerPart = parts[0];
      final decimalPart = parts.length > 1 ? parts[1] : '00';
      
      // Add commas to integer part
      String formattedInteger = '';
      for (int i = integerPart.length - 1, count = 0; i >= 0; i--, count++) {
        if (count > 0 && count % 3 == 0) {
          formattedInteger = ',' + formattedInteger;
        }
        formattedInteger = integerPart[i] + formattedInteger;
      }
      
      return '$formattedInteger.$decimalPart';
    } catch (e) {
      return '0.00';
    }
  }

  String _formatPercentage(dynamic percentage) {
    if (percentage == null) return '0.0';
    try {
      final numValue = percentage is int ? percentage.toDouble() : (percentage is double ? percentage : double.tryParse(percentage.toString()) ?? 0.0);
      return numValue.toStringAsFixed(1);
    } catch (e) {
      return '0.0';
    }
  }

  double _getPercentageValue(dynamic percentage) {
    if (percentage == null) return 0.0;
    try {
      final numValue = percentage is int ? percentage.toDouble() : (percentage is double ? percentage : double.tryParse(percentage.toString()) ?? 0.0);
      return numValue / 100.0;
    } catch (e) {
      return 0.0;
    }
  }
}

