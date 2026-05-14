import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import '../../services/retirement_service.dart';

class CreateRetirementScreen extends StatefulWidget {
  const CreateRetirementScreen({super.key});

  @override
  State<CreateRetirementScreen> createState() => _CreateRetirementScreenState();
}

class _CreateRetirementScreenState extends State<CreateRetirementScreen> {
  final _formKey = GlobalKey<FormState>();
  final _notesController = TextEditingController();
  
  bool _isLoading = false;
  bool _isLoadingData = true;
  bool _showForm = false;
  
  List<dynamic> _eligibleImprests = [];
  Map<String, dynamic>? _selectedImprest;
  List<Map<String, dynamic>> _retirementItems = [];

  @override
  void initState() {
    super.initState();
    _loadEligibleImprests();
  }

  @override
  void dispose() {
    for (var item in _retirementItems) {
      item['actualAmountController']?.dispose();
      item['descriptionController']?.dispose();
      item['notesController']?.dispose();
    }
    _notesController.dispose();
    super.dispose();
  }

  Future<void> _loadEligibleImprests() async {
    setState(() => _isLoadingData = true);
    
    final result = await RetirementService.getEligibleImprestForRetirement();
    
    if (mounted) {
      setState(() {
        _isLoadingData = false;
        if (result['success']) {
          _eligibleImprests = result['data'] ?? [];
        }
      });
    }
  }

  void _selectImprest(Map<String, dynamic> imprest) {
    setState(() {
      _selectedImprest = imprest;
      _showForm = true;
      
      // Initialize retirement items from imprest items
      _retirementItems = (imprest['imprest_items'] as List? ?? []).map((item) {
        return {
          'chart_account_id': item['chart_account_id'],
          'account_name': item['account_name'],
          'account_code': item['account_code'],
          'requested_amount': item['amount'] ?? 0.0,
          'actual_amount': item['amount'] ?? 0.0, // Default to requested amount
          'actualAmountController': TextEditingController(text: (item['amount'] ?? 0.0).toString()),
          'description': item['notes'] ?? '',
          'descriptionController': TextEditingController(text: item['notes'] ?? ''),
          'notesController': TextEditingController(),
        };
      }).toList();
    });
  }

  void _goBack() {
    setState(() {
      _showForm = false;
      _selectedImprest = null;
      _retirementItems = [];
    });
  }

  double get _totalActualAmount {
    return _retirementItems.fold(0.0, (sum, item) {
      final amount = double.tryParse(item['actualAmountController']?.text ?? '0') ?? 0;
      return sum + amount;
    });
  }

  double get _totalRequestedAmount {
    return _retirementItems.fold(0.0, (sum, item) => sum + (item['requested_amount'] ?? 0.0));
  }

  Future<void> _submitRetirement() async {
    if (!_formKey.currentState!.validate()) return;
    
    if (_selectedImprest == null) {
      _showError('Please select an imprest request');
      return;
    }
    
    if (_retirementItems.isEmpty) {
      _showError('No retirement items found');
      return;
    }

    // Validate all items
    for (int i = 0; i < _retirementItems.length; i++) {
      final actualAmount = double.tryParse(_retirementItems[i]['actualAmountController']?.text ?? '0') ?? 0;
      if (actualAmount < 0) {
        _showError('Actual amount for item ${i + 1} cannot be negative');
        return;
      }
      final description = _retirementItems[i]['descriptionController']?.text?.trim() ?? '';
      if (description.isEmpty) {
        _showError('Please enter a description for item ${i + 1}');
        return;
      }
    }

    setState(() => _isLoading = true);

    final itemsData = _retirementItems.map((item) => {
      'chart_account_id': item['chart_account_id'],
      'requested_amount': item['requested_amount'] ?? 0.0,
      'actual_amount': double.tryParse(item['actualAmountController']?.text ?? '0') ?? 0,
      'description': item['descriptionController']?.text?.trim() ?? '',
      'notes': item['notesController']?.text?.trim(),
    }).toList();

    final result = await RetirementService.createRetirement(
      imprestRequestId: _selectedImprest!['id'],
      retirementItems: itemsData,
      retirementNotes: _notesController.text.trim(),
    );

    if (mounted) {
      setState(() => _isLoading = false);
      
      if (result['success']) {
        _showSuccess(result['message'] ?? 'Retirement submitted successfully');
        Future.delayed(const Duration(seconds: 1), () {
          Navigator.pop(context, true);
        });
      } else {
        _showError(result['message'] ?? 'Failed to submit retirement');
      }
    }
  }

  void _showError(String message) {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(message),
        backgroundColor: Colors.red,
        duration: const Duration(seconds: 3),
      ),
    );
  }

  void _showSuccess(String message) {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(message),
        backgroundColor: Colors.green,
        duration: const Duration(seconds: 2),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: CustomScrollView(
        slivers: [
          SliverAppBar(
            expandedHeight: 150,
            floating: false,
            pinned: true,
            elevation: 0,
            leading: IconButton(
              icon: const Icon(Icons.arrow_back, color: Colors.white),
              onPressed: () {
                if (_showForm) {
                  _goBack();
                } else {
                  Navigator.pop(context);
                }
              },
            ),
            flexibleSpace: FlexibleSpaceBar(
              background: Container(
                decoration: BoxDecoration(
                  gradient: LinearGradient(
                    begin: Alignment.topLeft,
                    end: Alignment.bottomRight,
                    colors: [
                      Colors.blue.shade700,
                      Colors.blue.shade500,
                      Colors.blue.shade400,
                    ],
                  ),
                ),
                child: SafeArea(
                  child: Padding(
                    padding: const EdgeInsets.fromLTRB(16, 40, 16, 12),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      mainAxisAlignment: MainAxisAlignment.center,
                      children: [
                        Row(
                          children: [
                            Container(
                              padding: const EdgeInsets.all(10),
                              decoration: BoxDecoration(
                                color: Colors.white.withValues(alpha: 0.2),
                                borderRadius: BorderRadius.circular(12),
                              ),
                              child: const Icon(Icons.account_balance_wallet_rounded, color: Colors.white, size: 24),
                            ),
                            const SizedBox(width: 12),
                            Expanded(
                              child: Text(
                                _showForm ? 'Create Retirement' : 'Select Imprest',
                                style: const TextStyle(
                                  color: Colors.white,
                                  fontSize: 20,
                                  fontWeight: FontWeight.bold,
                                ),
                              ),
                            ),
                          ],
                        ),
                        const SizedBox(height: 4),
                        Text(
                          _showForm ? 'Enter actual amounts and submit' : 'Choose an imprest request to retire',
                          style: TextStyle(
                            color: Colors.white.withValues(alpha: 0.8),
                            fontSize: 12,
                          ),
                        ),
                      ],
                    ),
                  ),
                ),
              ),
            ),
          ),
          SliverToBoxAdapter(
            child: _isLoadingData
                ? const Padding(
                    padding: EdgeInsets.all(32.0),
                    child: Center(child: CircularProgressIndicator()),
                  )
                : _showForm
                    ? _buildForm()
                    : _buildImprestSelection(),
          ),
        ],
      ),
    );
  }

  Widget _buildImprestSelection() {
    if (_eligibleImprests.isEmpty) {
      return Padding(
        padding: const EdgeInsets.all(32.0),
        child: Center(
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              Icon(Icons.info_outline, size: 64, color: Colors.grey.shade400),
              const SizedBox(height: 16),
              Text(
                'No Eligible Imprest Requests',
                style: TextStyle(
                  fontSize: 18,
                  fontWeight: FontWeight.bold,
                  color: Colors.grey.shade700,
                ),
              ),
              const SizedBox(height: 8),
              Text(
                'You need to have disbursed imprest requests\nthat haven\'t been retired yet',
                textAlign: TextAlign.center,
                style: TextStyle(
                  fontSize: 14,
                  color: Colors.grey.shade600,
                ),
              ),
            ],
          ),
        ),
      );
    }

    return Padding(
      padding: const EdgeInsets.all(16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            'Select Imprest Request',
            style: TextStyle(
              fontSize: 18,
              fontWeight: FontWeight.bold,
              color: Colors.grey.shade800,
            ),
          ),
          const SizedBox(height: 16),
          ...(_eligibleImprests.map((imprest) => Card(
            margin: const EdgeInsets.only(bottom: 12),
            child: InkWell(
              onTap: () => _selectImprest(imprest),
              child: Padding(
                padding: const EdgeInsets.all(16),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      children: [
                        Expanded(
                          child: Text(
                            imprest['request_number'] ?? 'N/A',
                            style: const TextStyle(
                              fontWeight: FontWeight.bold,
                              fontSize: 16,
                            ),
                          ),
                        ),
                        Text(
                          'TZS ${_formatAmount(imprest['disbursed_amount'] ?? 0)}',
                          style: TextStyle(
                            fontWeight: FontWeight.bold,
                            fontSize: 16,
                            color: Colors.blue.shade700,
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 8),
                    Text(
                      imprest['purpose'] ?? 'N/A',
                      style: TextStyle(
                        color: Colors.grey.shade600,
                        fontSize: 14,
                      ),
                    ),
                    const SizedBox(height: 4),
                    Text(
                      'Department: ${imprest['department'] ?? 'N/A'}',
                      style: TextStyle(
                        color: Colors.grey.shade500,
                        fontSize: 12,
                      ),
                    ),
                    Text(
                      'Disbursed: ${imprest['disbursed_at'] ?? 'N/A'}',
                      style: TextStyle(
                        color: Colors.grey.shade500,
                        fontSize: 12,
                      ),
                    ),
                  ],
                ),
              ),
            ),
          ))),
        ],
      ),
    );
  }

  Widget _buildForm() {
    return Form(
      key: _formKey,
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Summary Card
            Container(
              padding: const EdgeInsets.all(16),
              decoration: BoxDecoration(
                color: Colors.blue.shade50,
                borderRadius: BorderRadius.circular(12),
                border: Border.all(color: Colors.blue.shade200),
              ),
              child: Column(
                children: [
                  Row(
                    children: [
                      Icon(Icons.info_outline, color: Colors.blue.shade700, size: 20),
                      const SizedBox(width: 8),
                      Text(
                        'Retirement Summary',
                        style: TextStyle(
                          fontWeight: FontWeight.bold,
                          color: Colors.blue.shade900,
                          fontSize: 14,
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 12),
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Text('Requested:', style: TextStyle(color: Colors.grey.shade700, fontSize: 13)),
                      Text(
                        'TZS ${_formatAmount(_totalRequestedAmount)}',
                        style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13),
                      ),
                    ],
                  ),
                  const SizedBox(height: 4),
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Text('Actual:', style: TextStyle(color: Colors.grey.shade700, fontSize: 13)),
                      Text(
                        'TZS ${_formatAmount(_totalActualAmount)}',
                        style: TextStyle(
                          fontWeight: FontWeight.bold,
                          fontSize: 13,
                          color: Colors.blue.shade700,
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 4),
                  Divider(color: Colors.blue.shade200),
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Text('Variance:', style: TextStyle(color: Colors.grey.shade700, fontSize: 13, fontWeight: FontWeight.bold)),
                      Text(
                        'TZS ${_formatAmount(_totalActualAmount - _totalRequestedAmount)}',
                        style: TextStyle(
                          fontWeight: FontWeight.bold,
                          fontSize: 14,
                          color: (_totalActualAmount - _totalRequestedAmount) >= 0 ? Colors.green : Colors.red,
                        ),
                      ),
                    ],
                  ),
                ],
              ),
            ),
            const SizedBox(height: 24),
            Text(
              'Retirement Items',
              style: TextStyle(
                fontSize: 18,
                fontWeight: FontWeight.bold,
                color: Colors.grey.shade800,
              ),
            ),
            const SizedBox(height: 16),
            ...(_retirementItems.asMap().entries.map((entry) {
              final index = entry.key;
              final item = entry.value;
              final variance = (double.tryParse(item['actualAmountController']?.text ?? '0') ?? 0) - (item['requested_amount'] ?? 0.0);
              
              return Card(
                margin: const EdgeInsets.only(bottom: 16),
                child: Padding(
                  padding: const EdgeInsets.all(16),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Row(
                        children: [
                          Expanded(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Text(
                                  item['account_name'] ?? 'N/A',
                                  style: const TextStyle(
                                    fontWeight: FontWeight.bold,
                                    fontSize: 15,
                                  ),
                                ),
                                Text(
                                  'Code: ${item['account_code'] ?? 'N/A'}',
                                  style: TextStyle(
                                    color: Colors.grey.shade600,
                                    fontSize: 12,
                                  ),
                                ),
                              ],
                            ),
                          ),
                          Container(
                            padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                            decoration: BoxDecoration(
                              color: variance >= 0 ? Colors.green.shade50 : Colors.red.shade50,
                              borderRadius: BorderRadius.circular(8),
                            ),
                            child: Text(
                              variance >= 0 ? '+${_formatAmount(variance)}' : _formatAmount(variance),
                              style: TextStyle(
                                color: variance >= 0 ? Colors.green.shade700 : Colors.red.shade700,
                                fontWeight: FontWeight.bold,
                                fontSize: 12,
                              ),
                            ),
                          ),
                        ],
                      ),
                      const SizedBox(height: 12),
                      Row(
                        children: [
                          Expanded(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Text(
                                  'Requested Amount',
                                  style: TextStyle(
                                    color: Colors.grey.shade600,
                                    fontSize: 12,
                                  ),
                                ),
                                const SizedBox(height: 4),
                                Text(
                                  'TZS ${_formatAmount(item['requested_amount'] ?? 0)}',
                                  style: const TextStyle(
                                    fontWeight: FontWeight.bold,
                                    fontSize: 14,
                                  ),
                                ),
                              ],
                            ),
                          ),
                          Expanded(
                            child: TextFormField(
                              controller: item['actualAmountController'],
                              decoration: InputDecoration(
                                labelText: 'Actual Amount *',
                                prefixText: 'TZS ',
                                border: OutlineInputBorder(
                                  borderRadius: BorderRadius.circular(8),
                                ),
                                filled: true,
                                fillColor: Colors.grey.shade50,
                              ),
                              keyboardType: const TextInputType.numberWithOptions(decimal: true),
                              inputFormatters: [
                                FilteringTextInputFormatter.allow(RegExp(r'^\d+\.?\d{0,2}')),
                              ],
                              validator: (value) {
                                if (value == null || value.isEmpty) {
                                  return 'Required';
                                }
                                final amount = double.tryParse(value) ?? 0;
                                if (amount < 0) {
                                  return 'Cannot be negative';
                                }
                                return null;
                              },
                              onChanged: (value) => setState(() {}),
                            ),
                          ),
                        ],
                      ),
                      const SizedBox(height: 12),
                      TextFormField(
                        controller: item['descriptionController'],
                        decoration: InputDecoration(
                          labelText: 'Description *',
                          hintText: 'What was this amount used for?',
                          border: OutlineInputBorder(
                            borderRadius: BorderRadius.circular(8),
                          ),
                          filled: true,
                          fillColor: Colors.grey.shade50,
                        ),
                        maxLines: 2,
                        validator: (value) {
                          if (value == null || value.trim().isEmpty) {
                            return 'Please enter description';
                          }
                          return null;
                        },
                      ),
                      const SizedBox(height: 8),
                      TextFormField(
                        controller: item['notesController'],
                        decoration: InputDecoration(
                          labelText: 'Notes (Optional)',
                          hintText: 'Additional notes...',
                          border: OutlineInputBorder(
                            borderRadius: BorderRadius.circular(8),
                          ),
                          filled: true,
                          fillColor: Colors.grey.shade50,
                        ),
                        maxLines: 2,
                      ),
                    ],
                  ),
                ),
              );
            })),
            const SizedBox(height: 16),
            TextFormField(
              controller: _notesController,
              decoration: InputDecoration(
                labelText: 'Retirement Notes (Optional)',
                hintText: 'Additional notes about this retirement...',
                border: OutlineInputBorder(
                  borderRadius: BorderRadius.circular(8),
                ),
                filled: true,
                fillColor: Colors.grey.shade50,
              ),
              maxLines: 3,
            ),
            const SizedBox(height: 24),
            SizedBox(
              width: double.infinity,
              height: 50,
              child: ElevatedButton(
                onPressed: _isLoading ? null : _submitRetirement,
                style: ElevatedButton.styleFrom(
                  backgroundColor: Colors.blue.shade600,
                  shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(12),
                  ),
                ),
                child: _isLoading
                    ? const SizedBox(
                        width: 20,
                        height: 20,
                        child: CircularProgressIndicator(
                          strokeWidth: 2,
                          valueColor: AlwaysStoppedAnimation<Color>(Colors.white),
                        ),
                      )
                    : const Text(
                        'Submit Retirement',
                        style: TextStyle(
                          fontSize: 16,
                          fontWeight: FontWeight.bold,
                          color: Colors.white,
                        ),
                      ),
              ),
            ),
            const SizedBox(height: 24),
          ],
        ),
      ),
    );
  }

  String _formatAmount(dynamic amount) {
    if (amount == null) return '0.00';
    final double numericValue =
        amount is num ? (amount as num).toDouble() : double.tryParse(amount.toString()) ?? 0.0;
    return numericValue.toStringAsFixed(2).replaceAllMapped(
      RegExp(r'(\d{1,3})(?=(\d{3})+(?!\d))'),
      (Match m) => '${m[1]},',
    );
  }
}

