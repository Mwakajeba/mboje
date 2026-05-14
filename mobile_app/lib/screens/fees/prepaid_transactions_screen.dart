import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../services/parent_api_service.dart';
import '../../providers/language_provider.dart';
import 'package:intl/intl.dart';

class PrepaidTransactionsScreen extends StatefulWidget {
  final int studentId;
  const PrepaidTransactionsScreen({super.key, required this.studentId});

  @override
  State<PrepaidTransactionsScreen> createState() => _PrepaidTransactionsScreenState();
}

class _PrepaidTransactionsScreenState extends State<PrepaidTransactionsScreen> {
  Map<String, dynamic>? transactionsData;
  bool isLoading = true;

  @override
  void initState() {
    super.initState();
    _loadTransactions();
  }

  Future<void> _loadTransactions() async {
    final data = await ParentApiService.getPrepaidAccountTransactions(widget.studentId);
    if (mounted) {
      setState(() {
        transactionsData = data;
        isLoading = false;
      });
    }
  }

  String _formatAmount(dynamic amount) {
    if (amount == null) return '0.00';
    try {
      final numValue = amount is int ? amount.toDouble() : (amount is double ? amount : double.tryParse(amount.toString()) ?? 0.0);
      final formatted = numValue.toStringAsFixed(2);
      final parts = formatted.split('.');
      final integerPart = parts[0];
      final decimalPart = parts.length > 1 ? parts[1] : '00';
      
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

  String _formatDate(String? dateString) {
    if (dateString == null) return 'N/A';
    try {
      final date = DateTime.parse(dateString);
      return DateFormat('dd/MM/yyyy HH:mm').format(date);
    } catch (e) {
      return dateString;
    }
  }

  Color _getTransactionTypeColor(String type) {
    switch (type) {
      case 'deposit':
        return Colors.green;
      case 'invoice_application':
        return Colors.orange;
      case 'withdrawal':
        return Colors.red;
      default:
        return Colors.grey;
    }
  }

  IconData _getTransactionTypeIcon(String type) {
    switch (type) {
      case 'deposit':
        return Icons.add_circle;
      case 'invoice_application':
        return Icons.payment;
      case 'withdrawal':
        return Icons.remove_circle;
      default:
        return Icons.info;
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
              colors: [Colors.green.shade700, Colors.green.shade500],
              begin: Alignment.topLeft,
              end: Alignment.bottomRight,
            ),
          ),
        ),
        title: const Text(
          'Matumizi ya Akaunti ya Malipo',
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
                    valueColor: AlwaysStoppedAnimation<Color>(Colors.green.shade700),
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
          : transactionsData == null
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
                          Icons.history,
                          size: 64,
                          color: Colors.grey.shade400,
                        ),
                      ),
                      const SizedBox(height: 24),
                      Text(
                        'Hakuna data ya matumizi',
                        style: TextStyle(
                          fontSize: 16,
                          color: Colors.grey.shade600,
                          fontWeight: FontWeight.w500,
                        ),
                      ),
                    ],
                  ),
                )
              : SingleChildScrollView(
                  child: Column(
                    children: [
                      // Account Summary Card
                      if (transactionsData!['account'] != null) ...[
                        Container(
                          margin: const EdgeInsets.all(12),
                          padding: const EdgeInsets.all(20),
                          decoration: BoxDecoration(
                            gradient: LinearGradient(
                              colors: [Colors.green.shade700, Colors.green.shade500],
                              begin: Alignment.topLeft,
                              end: Alignment.bottomRight,
                            ),
                            borderRadius: BorderRadius.circular(16),
                            boxShadow: [
                              BoxShadow(
                                color: Colors.green.withOpacity(0.3),
                                blurRadius: 10,
                                offset: const Offset(0, 4),
                              ),
                            ],
                          ),
                          child: Column(
                            children: [
                              const Text(
                                'Akaunti ya Malipo',
                                style: TextStyle(
                                  color: Colors.white,
                                  fontSize: 16,
                                  fontWeight: FontWeight.w600,
                                ),
                              ),
                              const SizedBox(height: 16),
                              Row(
                                mainAxisAlignment: MainAxisAlignment.spaceAround,
                                children: [
                                  _buildAccountStat(
                                    'Salio',
                                    _formatAmount(transactionsData!['account']?['credit_balance']),
                                    Colors.white,
                                  ),
                                  Container(width: 1, height: 40, color: Colors.white.withOpacity(0.3)),
                                  _buildAccountStat(
                                    'Jumla ya Amana',
                                    _formatAmount(transactionsData!['account']?['total_deposited']),
                                    Colors.white,
                                  ),
                                  Container(width: 1, height: 40, color: Colors.white.withOpacity(0.3)),
                                  _buildAccountStat(
                                    'Jumla ya Matumizi',
                                    _formatAmount(transactionsData!['account']?['total_used']),
                                    Colors.white,
                                  ),
                                ],
                              ),
                            ],
                          ),
                        ),
                      ],
                      // Transactions List
                      ListView.builder(
                        shrinkWrap: true,
                        physics: const NeverScrollableScrollPhysics(),
                        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                        itemCount: (transactionsData!['transactions'] as List?)?.length ?? 0,
                        itemBuilder: (context, index) {
                          final transaction = transactionsData!['transactions'][index];
                          return _buildTransactionCard(transaction);
                        },
                      ),
                    ],
                  ),
                ),
    );
  }

  Widget _buildAccountStat(String label, String value, Color color) {
    return Expanded(
      child: Column(
        children: [
          Text(
            'TZS',
            style: TextStyle(
              fontSize: 11,
              color: color.withOpacity(0.8),
              fontWeight: FontWeight.w500,
            ),
          ),
          const SizedBox(height: 4),
          Text(
            value,
            style: TextStyle(
              fontSize: 16,
              color: color,
              fontWeight: FontWeight.bold,
            ),
            textAlign: TextAlign.center,
          ),
          const SizedBox(height: 4),
          Text(
            label,
            style: TextStyle(
              fontSize: 11,
              color: color.withOpacity(0.9),
            ),
            textAlign: TextAlign.center,
          ),
        ],
      ),
    );
  }

  Widget _buildTransactionCard(Map<String, dynamic> transaction) {
    final type = transaction['type'] ?? '';
    final typeColor = _getTransactionTypeColor(type);
    final typeIcon = _getTransactionTypeIcon(type);
    final isDeposit = type == 'deposit';
    
    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(12),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withOpacity(0.05),
            blurRadius: 8,
            offset: const Offset(0, 2),
          ),
        ],
      ),
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Container(
                  padding: const EdgeInsets.all(8),
                  decoration: BoxDecoration(
                    color: typeColor.withOpacity(0.1),
                    borderRadius: BorderRadius.circular(8),
                  ),
                  child: Icon(typeIcon, color: typeColor, size: 20),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        transaction['type_label'] ?? type,
                        style: const TextStyle(
                          fontSize: 16,
                          fontWeight: FontWeight.bold,
                        ),
                      ),
                      const SizedBox(height: 4),
                      Text(
                        _formatDate(transaction['created_at_formatted'] ?? transaction['created_at']),
                        style: TextStyle(
                          fontSize: 12,
                          color: Colors.grey.shade600,
                        ),
                      ),
                    ],
                  ),
                ),
                Column(
                  crossAxisAlignment: CrossAxisAlignment.end,
                  children: [
                    Text(
                      isDeposit ? '+' : '-',
                      style: TextStyle(
                        fontSize: 18,
                        fontWeight: FontWeight.bold,
                        color: isDeposit ? Colors.green : Colors.orange,
                      ),
                    ),
                    Text(
                      'TZS ${_formatAmount(transaction['amount'])}',
                      style: TextStyle(
                        fontSize: 16,
                        fontWeight: FontWeight.bold,
                        color: isDeposit ? Colors.green : Colors.orange,
                      ),
                    ),
                  ],
                ),
              ],
            ),
            if (transaction['invoice_number'] != null) ...[
              const SizedBox(height: 12),
              Container(
                padding: const EdgeInsets.all(8),
                decoration: BoxDecoration(
                  color: Colors.blue.shade50,
                  borderRadius: BorderRadius.circular(6),
                ),
                child: Row(
                  children: [
                    Icon(Icons.receipt, size: 16, color: Colors.blue.shade700),
                    const SizedBox(width: 8),
                    Text(
                      'Invoice: ${transaction['invoice_number']}',
                      style: TextStyle(
                        fontSize: 12,
                        color: Colors.blue.shade700,
                        fontWeight: FontWeight.w500,
                      ),
                    ),
                  ],
                ),
              ),
            ],
            if (transaction['reference'] != null) ...[
              const SizedBox(height: 8),
              Text(
                'Rekebisha: ${transaction['reference']}',
                style: TextStyle(
                  fontSize: 12,
                  color: Colors.grey.shade600,
                ),
              ),
            ],
            if (transaction['notes'] != null && transaction['notes'].toString().isNotEmpty) ...[
              const SizedBox(height: 8),
              Text(
                'Maelezo: ${transaction['notes']}',
                style: TextStyle(
                  fontSize: 12,
                  color: Colors.grey.shade600,
                  fontStyle: FontStyle.italic,
                ),
              ),
            ],
            const SizedBox(height: 12),
            Container(
              padding: const EdgeInsets.all(8),
              decoration: BoxDecoration(
                color: Colors.grey.shade100,
                borderRadius: BorderRadius.circular(6),
              ),
              child: Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Text(
                    'Salio kabla:',
                    style: TextStyle(
                      fontSize: 12,
                      color: Colors.grey.shade700,
                    ),
                  ),
                  Text(
                    'TZS ${_formatAmount(transaction['balance_before'])}',
                    style: TextStyle(
                      fontSize: 12,
                      fontWeight: FontWeight.w600,
                      color: Colors.grey.shade700,
                    ),
                  ),
                  const SizedBox(width: 16),
                  Text(
                    'Salio baada:',
                    style: TextStyle(
                      fontSize: 12,
                      color: Colors.grey.shade700,
                    ),
                  ),
                  Text(
                    'TZS ${_formatAmount(transaction['balance_after'])}',
                    style: TextStyle(
                      fontSize: 12,
                      fontWeight: FontWeight.bold,
                      color: Colors.green.shade700,
                    ),
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}

