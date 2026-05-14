import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import '../../services/imprest_service.dart';

class CreateImprestScreen extends StatefulWidget {
  const CreateImprestScreen({super.key});

  @override
  State<CreateImprestScreen> createState() => _CreateImprestScreenState();
}

class _CreateImprestScreenState extends State<CreateImprestScreen> {
  final _formKey = GlobalKey<FormState>();
  final _purposeController = TextEditingController();
  final _descriptionController = TextEditingController();
  
  bool _isLoading = false;
  bool _isLoadingData = true;
  
  List<dynamic> _departments = [];
  List<dynamic> _expenseAccounts = [];
  int? _selectedDepartmentId;
  DateTime _dateRequired = DateTime.now().add(const Duration(days: 1));
  
  List<Map<String, dynamic>> _items = [];

  @override
  void initState() {
    super.initState();
    _loadInitialData();
  }

  @override
  void dispose() {
    _purposeController.dispose();
    _descriptionController.dispose();
    super.dispose();
  }

  Future<void> _loadInitialData() async {
    setState(() => _isLoadingData = true);
    
    final results = await Future.wait([
      ImprestService.getDepartments(),
      ImprestService.getExpenseAccounts(),
    ]);
    
    if (mounted) {
      setState(() {
        _isLoadingData = false;
        if (results[0]['success']) {
          _departments = results[0]['data'] ?? [];
        }
        if (results[1]['success']) {
          _expenseAccounts = results[1]['data'] ?? [];
        }
      });
    }
  }

  void _addItem() {
    setState(() {
      _items.add({
        'chart_account_id': null,
        'amount': 0.0,
        'notes': '',
        'amountController': TextEditingController(),
        'notesController': TextEditingController(),
      });
    });
  }

  void _removeItem(int index) {
    setState(() {
      _items[index]['amountController']?.dispose();
      _items[index]['notesController']?.dispose();
      _items.removeAt(index);
    });
  }

  double get _totalAmount {
    return _items.fold(0.0, (sum, item) {
      final amount = double.tryParse(item['amountController']?.text ?? '0') ?? 0;
      return sum + amount;
    });
  }

  Future<void> _selectDate() async {
    final picked = await showDatePicker(
      context: context,
      initialDate: _dateRequired,
      firstDate: DateTime.now(),
      lastDate: DateTime.now().add(const Duration(days: 365)),
      builder: (context, child) {
        return Theme(
          data: Theme.of(context).copyWith(
            colorScheme: const ColorScheme.light(
              primary: Color(0xFF1976D2),
              onPrimary: Colors.white,
              surface: Colors.white,
              onSurface: Color(0xFF1F2937),
            ),
          ),
          child: child!,
        );
      },
    );
    
    if (picked != null) {
      setState(() => _dateRequired = picked);
    }
  }

  Future<void> _submitRequest() async {
    if (!_formKey.currentState!.validate()) return;
    
    if (_selectedDepartmentId == null) {
      _showError('Please select a department');
      return;
    }
    
    if (_items.isEmpty) {
      _showError('Please add at least one expense item');
      return;
    }
    
    // Validate items
    for (int i = 0; i < _items.length; i++) {
      if (_items[i]['chart_account_id'] == null) {
        _showError('Please select an expense account for item ${i + 1}');
        return;
      }
      final amount = double.tryParse(_items[i]['amountController']?.text ?? '0') ?? 0;
      if (amount <= 0) {
        _showError('Please enter a valid amount for item ${i + 1}');
        return;
      }
    }

    setState(() => _isLoading = true);

    final itemsData = _items.map((item) => {
      'chart_account_id': item['chart_account_id'],
      'amount': double.tryParse(item['amountController']?.text ?? '0') ?? 0,
      'notes': item['notesController']?.text ?? '',
    }).toList();

    final result = await ImprestService.createImprest(
      departmentId: _selectedDepartmentId!,
      purpose: _purposeController.text.trim(),
      description: _descriptionController.text.trim(),
      dateRequired: _dateRequired.toIso8601String().split('T')[0],
      items: itemsData,
    );

    if (mounted) {
      setState(() => _isLoading = false);
      
      if (result['success']) {
        _showSuccess(result['message'] ?? 'Imprest request created successfully');
        Navigator.pop(context, true);
      } else {
        _showError(result['message'] ?? 'Failed to create imprest request');
      }
    }
  }

  void _showError(String message) {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Row(
          children: [
            const Icon(Icons.error_outline, color: Colors.white),
            const SizedBox(width: 12),
            Expanded(child: Text(message)),
          ],
        ),
        backgroundColor: const Color(0xFFE53935),
        behavior: SnackBarBehavior.floating,
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
      ),
    );
  }

  void _showSuccess(String message) {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Row(
          children: [
            const Icon(Icons.check_circle, color: Colors.white),
            const SizedBox(width: 12),
            Expanded(child: Text(message)),
          ],
        ),
        backgroundColor: const Color(0xFF4CAF50),
        behavior: SnackBarBehavior.floating,
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFF5F7FA),
      appBar: AppBar(
        backgroundColor: const Color(0xFF1976D2),
        elevation: 0,
        leading: IconButton(
          icon: const Icon(Icons.close, color: Colors.white),
          onPressed: () => Navigator.pop(context),
        ),
        title: const Text(
          'New Imprest Request',
          style: TextStyle(color: Colors.white, fontWeight: FontWeight.w600),
        ),
        actions: [
          if (!_isLoadingData)
            TextButton.icon(
              onPressed: _isLoading ? null : _submitRequest,
              icon: _isLoading
                  ? const SizedBox(
                      width: 20,
                      height: 20,
                      child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white),
                    )
                  : const Icon(Icons.send, color: Colors.white),
              label: Text(
                _isLoading ? 'Submitting...' : 'Submit',
                style: const TextStyle(color: Colors.white, fontWeight: FontWeight.w600),
              ),
            ),
        ],
      ),
      body: _isLoadingData
          ? const Center(child: CircularProgressIndicator())
          : Form(
              key: _formKey,
              child: SingleChildScrollView(
                padding: const EdgeInsets.all(16),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    // Total Amount Card
                    Container(
                      width: double.infinity,
                      padding: const EdgeInsets.all(16),
                      decoration: BoxDecoration(
                        gradient: const LinearGradient(
                          colors: [Color(0xFF1976D2), Color(0xFF0D47A1)],
                          begin: Alignment.topLeft,
                          end: Alignment.bottomRight,
                        ),
                        borderRadius: BorderRadius.circular(14),
                        boxShadow: [
                          BoxShadow(
                            color: const Color(0xFF1976D2).withValues(alpha: 0.2),
                            blurRadius: 10,
                            offset: const Offset(0, 4),
                          ),
                        ],
                      ),
                      child: Row(
                        children: [
                          Expanded(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                const Text(
                                  'Total Amount',
                                  style: TextStyle(color: Colors.white70, fontSize: 12),
                                ),
                                const SizedBox(height: 4),
                                Text(
                                  'TZS ${_formatNumber(_totalAmount)}',
                                  style: const TextStyle(
                                    color: Colors.white,
                                    fontSize: 22,
                                    fontWeight: FontWeight.bold,
                                  ),
                                ),
                              ],
                            ),
                          ),
                          Container(
                            padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                            decoration: BoxDecoration(
                              color: Colors.white.withValues(alpha: 0.2),
                              borderRadius: BorderRadius.circular(10),
                            ),
                            child: Text(
                              '${_items.length} item${_items.length != 1 ? 's' : ''}',
                              style: const TextStyle(color: Colors.white, fontSize: 13, fontWeight: FontWeight.w600),
                            ),
                          ),
                        ],
                      ),
                    ),
                    const SizedBox(height: 24),

                    // Basic Information Section
                    _buildSectionHeader('Basic Information', Icons.info_outline),
                    const SizedBox(height: 12),
                    
                    // Department Dropdown
                    _buildDropdownField(
                      label: 'Department',
                      hint: 'Select department',
                      value: _selectedDepartmentId,
                      items: _departments.map((dept) {
                        return DropdownMenuItem<int>(
                          value: dept['id'],
                          child: Text(dept['name'] ?? ''),
                        );
                      }).toList(),
                      onChanged: (value) => setState(() => _selectedDepartmentId = value),
                      icon: Icons.business,
                    ),
                    const SizedBox(height: 16),
                    
                    // Purpose Field
                    _buildTextField(
                      controller: _purposeController,
                      label: 'Purpose',
                      hint: 'Enter the purpose of this imprest',
                      icon: Icons.description,
                      validator: (value) {
                        if (value == null || value.trim().isEmpty) {
                          return 'Please enter a purpose';
                        }
                        return null;
                      },
                    ),
                    const SizedBox(height: 16),
                    
                    // Description Field
                    _buildTextField(
                      controller: _descriptionController,
                      label: 'Description (Optional)',
                      hint: 'Enter additional details',
                      icon: Icons.notes,
                      maxLines: 3,
                    ),
                    const SizedBox(height: 16),
                    
                    // Date Required
                    _buildDateField(),
                    const SizedBox(height: 24),

                    // Expense Items Section
                    _buildSectionHeader('Expense Items', Icons.receipt_long),
                    const SizedBox(height: 12),
                    
                    // Items List
                    ..._items.asMap().entries.map((entry) {
                      return _buildItemCard(entry.key, entry.value);
                    }),
                    
                    // Add Item Button
                    Container(
                      width: double.infinity,
                      margin: const EdgeInsets.only(top: 8),
                      child: OutlinedButton.icon(
                        onPressed: _addItem,
                        icon: const Icon(Icons.add_circle_outline),
                        label: const Text('Add Expense Item'),
                        style: OutlinedButton.styleFrom(
                          foregroundColor: const Color(0xFF1976D2),
                          side: const BorderSide(color: Color(0xFF1976D2), width: 1.5),
                          padding: const EdgeInsets.symmetric(vertical: 14),
                          shape: RoundedRectangleBorder(
                            borderRadius: BorderRadius.circular(12),
                          ),
                        ),
                      ),
                    ),
                    
                    const SizedBox(height: 24), // Space for submit button in app bar
                  ],
                ),
              ),
            ),
    );
  }

  Widget _buildSectionHeader(String title, IconData icon) {
    return Row(
      children: [
        Container(
          padding: const EdgeInsets.all(8),
          decoration: BoxDecoration(
            color: const Color(0xFF1976D2).withValues(alpha: 0.1),
            borderRadius: BorderRadius.circular(8),
          ),
          child: Icon(icon, color: const Color(0xFF1976D2), size: 20),
        ),
        const SizedBox(width: 12),
        Text(
          title,
          style: const TextStyle(
            fontSize: 18,
            fontWeight: FontWeight.bold,
            color: Color(0xFF1F2937),
          ),
        ),
      ],
    );
  }

  Widget _buildTextField({
    required TextEditingController controller,
    required String label,
    required String hint,
    required IconData icon,
    int maxLines = 1,
    String? Function(String?)? validator,
    TextInputType? keyboardType,
    List<TextInputFormatter>? inputFormatters,
  }) {
    return Container(
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(12),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.05),
            blurRadius: 10,
            offset: const Offset(0, 4),
          ),
        ],
      ),
      child: TextFormField(
        controller: controller,
        maxLines: maxLines,
        keyboardType: keyboardType,
        inputFormatters: inputFormatters,
        validator: validator,
        decoration: InputDecoration(
          labelText: label,
          hintText: hint,
          prefixIcon: Icon(icon, color: const Color(0xFF1976D2)),
          border: OutlineInputBorder(
            borderRadius: BorderRadius.circular(12),
            borderSide: BorderSide.none,
          ),
          filled: true,
          fillColor: Colors.white,
          contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 16),
        ),
      ),
    );
  }

  Widget _buildDropdownField<T>({
    required String label,
    required String hint,
    required T? value,
    required List<DropdownMenuItem<T>> items,
    required void Function(T?) onChanged,
    required IconData icon,
  }) {
    return Container(
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(12),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.05),
            blurRadius: 10,
            offset: const Offset(0, 4),
          ),
        ],
      ),
      child: DropdownButtonFormField<T>(
        value: value,
        items: items,
        onChanged: onChanged,
        decoration: InputDecoration(
          labelText: label,
          hintText: hint,
          prefixIcon: Icon(icon, color: const Color(0xFF1976D2)),
          border: OutlineInputBorder(
            borderRadius: BorderRadius.circular(12),
            borderSide: BorderSide.none,
          ),
          filled: true,
          fillColor: Colors.white,
          contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 16),
        ),
        isExpanded: true,
      ),
    );
  }

  Widget _buildDateField() {
    return GestureDetector(
      onTap: _selectDate,
      child: Container(
        padding: const EdgeInsets.all(16),
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(12),
          boxShadow: [
            BoxShadow(
              color: Colors.black.withValues(alpha: 0.05),
              blurRadius: 10,
              offset: const Offset(0, 4),
            ),
          ],
        ),
        child: Row(
          children: [
            const Icon(Icons.calendar_today, color: Color(0xFF1976D2)),
            const SizedBox(width: 16),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    'Date Required',
                    style: TextStyle(fontSize: 12, color: Colors.grey[600]),
                  ),
                  const SizedBox(height: 4),
                  Text(
                    '${_dateRequired.day}/${_dateRequired.month}/${_dateRequired.year}',
                    style: const TextStyle(fontSize: 16, fontWeight: FontWeight.w500),
                  ),
                ],
              ),
            ),
            const Icon(Icons.arrow_drop_down, color: Colors.grey),
          ],
        ),
      ),
    );
  }

  Widget _buildItemCard(int index, Map<String, dynamic> item) {
    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: const Color(0xFFE5E7EB)),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.03),
            blurRadius: 8,
            offset: const Offset(0, 2),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Header
          Row(
            children: [
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                decoration: BoxDecoration(
                  color: const Color(0xFF1976D2).withValues(alpha: 0.1),
                  borderRadius: BorderRadius.circular(8),
                ),
                child: Text(
                  'Item ${index + 1}',
                  style: const TextStyle(
                    color: Color(0xFF1976D2),
                    fontWeight: FontWeight.w600,
                    fontSize: 12,
                  ),
                ),
              ),
              const Spacer(),
              IconButton(
                onPressed: () => _removeItem(index),
                icon: const Icon(Icons.delete_outline, color: Color(0xFFE53935)),
                tooltip: 'Remove item',
              ),
            ],
          ),
          const SizedBox(height: 12),
          
          // Expense Account Dropdown
          DropdownButtonFormField<int>(
            value: item['chart_account_id'],
            items: _expenseAccounts.map((account) {
              return DropdownMenuItem<int>(
                value: account['id'],
                child: Text(
                  account['display'] ?? account['name'] ?? '',
                  overflow: TextOverflow.ellipsis,
                ),
              );
            }).toList(),
            onChanged: (value) {
              setState(() {
                _items[index]['chart_account_id'] = value;
              });
            },
            decoration: InputDecoration(
              labelText: 'Expense Account',
              hintText: 'Select account',
              border: OutlineInputBorder(borderRadius: BorderRadius.circular(10)),
              contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 12),
            ),
            isExpanded: true,
          ),
          const SizedBox(height: 12),
          
          // Amount Field
          TextFormField(
            controller: item['amountController'],
            keyboardType: TextInputType.number,
            inputFormatters: [FilteringTextInputFormatter.allow(RegExp(r'[\d.]'))],
            onChanged: (_) => setState(() {}),
            decoration: InputDecoration(
              labelText: 'Amount (TZS)',
              hintText: 'Enter amount',
              prefixText: 'TZS ',
              border: OutlineInputBorder(borderRadius: BorderRadius.circular(10)),
              contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 12),
            ),
          ),
          const SizedBox(height: 12),
          
          // Notes Field
          TextFormField(
            controller: item['notesController'],
            maxLines: 2,
            decoration: InputDecoration(
              labelText: 'Notes (Optional)',
              hintText: 'Add notes for this item',
              border: OutlineInputBorder(borderRadius: BorderRadius.circular(10)),
              contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 12),
            ),
          ),
        ],
      ),
    );
  }

  String _formatNumber(double number) {
    return number.toStringAsFixed(0).replaceAllMapped(
      RegExp(r'(\d{1,3})(?=(\d{3})+(?!\d))'),
      (Match m) => '${m[1]},',
    );
  }
}

