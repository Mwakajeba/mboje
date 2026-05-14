import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import '../../services/requisition_service.dart';

class CreateRequisitionScreen extends StatefulWidget {
  const CreateRequisitionScreen({super.key});

  @override
  State<CreateRequisitionScreen> createState() => _CreateRequisitionScreenState();
}

class _CreateRequisitionScreenState extends State<CreateRequisitionScreen> {
  final _formKey = GlobalKey<FormState>();
  final _purposeController = TextEditingController();
  final _notesController = TextEditingController();
  final _searchController = TextEditingController();
  
  bool _isLoading = false;
  bool _isLoadingData = true;
  bool _isSearching = false;
  
  List<dynamic> _departments = [];
  List<dynamic> _inventoryItems = [];
  int? _selectedDepartmentId;
  String _selectedPriority = 'normal';
  DateTime _requiredDate = DateTime.now().add(const Duration(days: 1));
  
  List<Map<String, dynamic>> _items = [];

  final List<Map<String, String>> _priorities = [
    {'value': 'low', 'label': 'Low'},
    {'value': 'normal', 'label': 'Normal'},
    {'value': 'high', 'label': 'High'},
    {'value': 'urgent', 'label': 'Urgent'},
  ];

  @override
  void initState() {
    super.initState();
    _loadInitialData();
  }

  @override
  void dispose() {
    _purposeController.dispose();
    _notesController.dispose();
    _searchController.dispose();
    super.dispose();
  }

  Future<void> _loadInitialData() async {
    setState(() => _isLoadingData = true);
    
    final results = await Future.wait([
      RequisitionService.getDepartments(),
      RequisitionService.getInventoryItems(),
    ]);
    
    if (mounted) {
      setState(() {
        _isLoadingData = false;
        if (results[0]['success']) {
          _departments = results[0]['data'] ?? [];
        }
        if (results[1]['success']) {
          _inventoryItems = results[1]['data'] ?? [];
        }
      });
    }
  }

  Future<void> _searchItems(String query) async {
    if (query.length < 2) {
      final result = await RequisitionService.getInventoryItems();
      if (mounted && result['success']) {
        setState(() => _inventoryItems = result['data'] ?? []);
      }
      return;
    }

    setState(() => _isSearching = true);
    
    final result = await RequisitionService.getInventoryItems(search: query);
    
    if (mounted) {
      setState(() {
        _isSearching = false;
        if (result['success']) {
          _inventoryItems = result['data'] ?? [];
        }
      });
    }
  }

  void _addItem(Map<String, dynamic> product) {
    // Check if item already exists
    final existingIndex = _items.indexWhere((item) => item['inventory_item_id'] == product['id']);
    if (existingIndex != -1) {
      // Increment quantity
      final currentQty = double.tryParse(_items[existingIndex]['quantityController']?.text ?? '1') ?? 1;
      _items[existingIndex]['quantityController'].text = (currentQty + 1).toStringAsFixed(0);
      setState(() {});
      return;
    }

    setState(() {
      _items.add({
        'inventory_item_id': product['id'],
        'product_name': product['name'],
        'product_code': product['code'],
        'unit': product['unit'] ?? 'pcs',
        'quantityController': TextEditingController(text: '1'),
        'notesController': TextEditingController(),
      });
    });
  }

  void _removeItem(int index) {
    setState(() {
      _items[index]['quantityController']?.dispose();
      _items[index]['notesController']?.dispose();
      _items.removeAt(index);
    });
  }

  double get _totalQuantity {
    return _items.fold(0.0, (sum, item) {
      final qty = double.tryParse(item['quantityController']?.text ?? '0') ?? 0;
      return sum + qty;
    });
  }

  Future<void> _selectDate() async {
    final picked = await showDatePicker(
      context: context,
      initialDate: _requiredDate,
      firstDate: DateTime.now(),
      lastDate: DateTime.now().add(const Duration(days: 365)),
      builder: (context, child) {
        return Theme(
          data: Theme.of(context).copyWith(
            colorScheme: const ColorScheme.light(
              primary: Color(0xFF00897B),
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
      setState(() => _requiredDate = picked);
    }
  }

  Future<void> _submitRequest() async {
    if (!_formKey.currentState!.validate()) return;
    
    if (_items.isEmpty) {
      _showError('Please add at least one item');
      return;
    }
    
    // Validate items
    for (int i = 0; i < _items.length; i++) {
      final qty = double.tryParse(_items[i]['quantityController']?.text ?? '0') ?? 0;
      if (qty <= 0) {
        _showError('Please enter a valid quantity for ${_items[i]['product_name']}');
        return;
      }
    }

    setState(() => _isLoading = true);

    final itemsData = _items.map((item) => {
      'inventory_item_id': item['inventory_item_id'],
      'quantity_requested': double.tryParse(item['quantityController']?.text ?? '0') ?? 0,
      'item_notes': item['notesController']?.text ?? '',
    }).toList();

    final result = await RequisitionService.createStoreRequisition(
      departmentId: _selectedDepartmentId,
      purpose: _purposeController.text.trim(),
      notes: _notesController.text.trim(),
      requiredDate: _requiredDate.toIso8601String().split('T')[0],
      priority: _selectedPriority,
      items: itemsData,
    );

    if (mounted) {
      setState(() => _isLoading = false);
      
      if (result['success']) {
        _showSuccess(result['message'] ?? 'Requisition created successfully');
        Navigator.pop(context, true);
      } else {
        _showError(result['message'] ?? 'Failed to create requisition');
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

  void _showItemPicker() {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (context) => _buildItemPickerSheet(),
    );
  }

  Widget _buildItemPickerSheet() {
    return StatefulBuilder(
      builder: (context, setSheetState) {
        return Container(
          height: MediaQuery.of(context).size.height * 0.75,
          decoration: const BoxDecoration(
            color: Colors.white,
            borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
          ),
          child: Column(
            children: [
              // Handle
              Container(
                margin: const EdgeInsets.only(top: 12),
                width: 40,
                height: 4,
                decoration: BoxDecoration(
                  color: Colors.grey[300],
                  borderRadius: BorderRadius.circular(2),
                ),
              ),
              // Header
              Padding(
                padding: const EdgeInsets.all(16),
                child: Row(
                  children: [
                    const Icon(Icons.inventory_2, color: Color(0xFF00897B)),
                    const SizedBox(width: 12),
                    const Text(
                      'Select Items',
                      style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
                    ),
                    const Spacer(),
                    IconButton(
                      onPressed: () => Navigator.pop(context),
                      icon: const Icon(Icons.close),
                    ),
                  ],
                ),
              ),
              // Search
              Padding(
                padding: const EdgeInsets.symmetric(horizontal: 16),
                child: TextField(
                  controller: _searchController,
                  onChanged: (value) {
                    _searchItems(value);
                    setSheetState(() {});
                  },
                  decoration: InputDecoration(
                    hintText: 'Search items...',
                    prefixIcon: const Icon(Icons.search),
                    suffixIcon: _isSearching
                        ? const Padding(
                            padding: EdgeInsets.all(12),
                            child: SizedBox(
                              width: 20,
                              height: 20,
                              child: CircularProgressIndicator(strokeWidth: 2),
                            ),
                          )
                        : null,
                    border: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(12),
                      borderSide: BorderSide(color: Colors.grey[300]!),
                    ),
                    filled: true,
                    fillColor: const Color(0xFFF5F7FA),
                  ),
                ),
              ),
              const SizedBox(height: 12),
              // Items List
              Expanded(
                child: _inventoryItems.isEmpty
                    ? Center(
                        child: Column(
                          mainAxisAlignment: MainAxisAlignment.center,
                          children: [
                            Icon(Icons.inventory_2_outlined, size: 48, color: Colors.grey[400]),
                            const SizedBox(height: 12),
                            Text('No items found', style: TextStyle(color: Colors.grey[600])),
                          ],
                        ),
                      )
                    : ListView.builder(
                        padding: const EdgeInsets.symmetric(horizontal: 16),
                        itemCount: _inventoryItems.length,
                        itemBuilder: (context, index) {
                          final item = _inventoryItems[index];
                          final isAdded = _items.any((i) => i['inventory_item_id'] == item['id']);
                          
                          return Container(
                            margin: const EdgeInsets.only(bottom: 8),
                            decoration: BoxDecoration(
                              color: isAdded ? const Color(0xFF00897B).withValues(alpha: 0.1) : Colors.white,
                              borderRadius: BorderRadius.circular(12),
                              border: Border.all(
                                color: isAdded ? const Color(0xFF00897B) : Colors.grey[200]!,
                              ),
                            ),
                            child: ListTile(
                              contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
                              leading: Container(
                                padding: const EdgeInsets.all(10),
                                decoration: BoxDecoration(
                                  color: const Color(0xFF00897B).withValues(alpha: 0.1),
                                  borderRadius: BorderRadius.circular(10),
                                ),
                                child: const Icon(Icons.inventory_2, color: Color(0xFF00897B)),
                              ),
                              title: Text(
                                item['name'] ?? 'N/A',
                                style: const TextStyle(fontWeight: FontWeight.w600),
                              ),
                              subtitle: Text(
                                '${item['code'] ?? ''} • ${item['unit'] ?? 'pcs'}',
                                style: TextStyle(color: Colors.grey[600], fontSize: 12),
                              ),
                              trailing: isAdded
                                  ? const Icon(Icons.check_circle, color: Color(0xFF00897B))
                                  : IconButton(
                                      onPressed: () {
                                        _addItem(item);
                                        setSheetState(() {});
                                        setState(() {});
                                      },
                                      icon: const Icon(Icons.add_circle_outline, color: Color(0xFF00897B)),
                                    ),
                              onTap: () {
                                _addItem(item);
                                setSheetState(() {});
                                setState(() {});
                              },
                            ),
                          );
                        },
                      ),
              ),
              // Done Button
              Padding(
                padding: const EdgeInsets.all(16),
                child: SizedBox(
                  width: double.infinity,
                  child: ElevatedButton(
                    onPressed: () => Navigator.pop(context),
                    style: ElevatedButton.styleFrom(
                      backgroundColor: const Color(0xFF00897B),
                      padding: const EdgeInsets.symmetric(vertical: 16),
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                    ),
                    child: Text(
                      'Done (${_items.length} items)',
                      style: const TextStyle(color: Colors.white, fontWeight: FontWeight.w600, fontSize: 16),
                    ),
                  ),
                ),
              ),
            ],
          ),
        );
      },
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFF5F7FA),
      appBar: AppBar(
        backgroundColor: const Color(0xFF00897B),
        elevation: 0,
        leading: IconButton(
          icon: const Icon(Icons.close, color: Colors.white),
          onPressed: () => Navigator.pop(context),
        ),
        title: const Text(
          'New Requisition',
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
                    // Summary Card
                    Container(
                      width: double.infinity,
                      padding: const EdgeInsets.all(16),
                      decoration: BoxDecoration(
                        gradient: const LinearGradient(
                          colors: [Color(0xFF00897B), Color(0xFF004D40)],
                          begin: Alignment.topLeft,
                          end: Alignment.bottomRight,
                        ),
                        borderRadius: BorderRadius.circular(14),
                        boxShadow: [
                          BoxShadow(
                            color: const Color(0xFF00897B).withValues(alpha: 0.2),
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
                                  'Total Items',
                                  style: TextStyle(color: Colors.white70, fontSize: 12),
                                ),
                                const SizedBox(height: 4),
                                Text(
                                  '${_items.length}',
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
                            width: 1,
                            height: 40,
                            color: Colors.white24,
                          ),
                          Expanded(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.end,
                              children: [
                                const Text(
                                  'Total Quantity',
                                  style: TextStyle(color: Colors.white70, fontSize: 12),
                                ),
                                const SizedBox(height: 4),
                                Text(
                                  _totalQuantity.toStringAsFixed(0),
                                  style: const TextStyle(
                                    color: Colors.white,
                                    fontSize: 22,
                                    fontWeight: FontWeight.bold,
                                  ),
                                ),
                              ],
                            ),
                          ),
                        ],
                      ),
                    ),
                    const SizedBox(height: 24),

                    // Basic Information Section
                    _buildSectionHeader('Basic Information', Icons.info_outline),
                    const SizedBox(height: 12),
                    
                    // Purpose Field
                    _buildTextField(
                      controller: _purposeController,
                      label: 'Purpose',
                      hint: 'Enter the purpose of this requisition',
                      icon: Icons.description,
                      validator: (value) {
                        if (value == null || value.trim().isEmpty) {
                          return 'Please enter a purpose';
                        }
                        return null;
                      },
                    ),
                    const SizedBox(height: 16),

                    // Department & Priority Row
                    Row(
                      children: [
                        Expanded(
                          child: _buildDropdownField<int>(
                            label: 'Department',
                            hint: 'Select',
                            value: _selectedDepartmentId,
                            items: _departments.map((dept) {
                              return DropdownMenuItem<int>(
                                value: dept['id'],
                                child: Text(dept['name'] ?? '', overflow: TextOverflow.ellipsis),
                              );
                            }).toList(),
                            onChanged: (value) => setState(() => _selectedDepartmentId = value),
                            icon: Icons.business,
                          ),
                        ),
                        const SizedBox(width: 12),
                        Expanded(
                          child: _buildDropdownField<String>(
                            label: 'Priority',
                            hint: 'Select',
                            value: _selectedPriority,
                            items: _priorities.map((p) {
                              return DropdownMenuItem<String>(
                                value: p['value'],
                                child: Text(p['label']!),
                              );
                            }).toList(),
                            onChanged: (value) => setState(() => _selectedPriority = value ?? 'normal'),
                            icon: Icons.flag,
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 16),
                    
                    // Date & Notes Row
                    _buildDateField(),
                    const SizedBox(height: 16),
                    
                    // Notes Field
                    _buildTextField(
                      controller: _notesController,
                      label: 'Notes (Optional)',
                      hint: 'Enter additional notes',
                      icon: Icons.notes,
                      maxLines: 2,
                    ),
                    const SizedBox(height: 24),

                    // Items Section
                    _buildSectionHeader('Requested Items', Icons.inventory_2),
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
                        onPressed: _showItemPicker,
                        icon: const Icon(Icons.add_circle_outline),
                        label: const Text('Add Items from Store'),
                        style: OutlinedButton.styleFrom(
                          foregroundColor: const Color(0xFF00897B),
                          side: const BorderSide(color: Color(0xFF00897B), width: 1.5),
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
            color: const Color(0xFF00897B).withValues(alpha: 0.1),
            borderRadius: BorderRadius.circular(8),
          ),
          child: Icon(icon, color: const Color(0xFF00897B), size: 20),
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
        validator: validator,
        decoration: InputDecoration(
          labelText: label,
          hintText: hint,
          prefixIcon: Icon(icon, color: const Color(0xFF00897B)),
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
          prefixIcon: Icon(icon, color: const Color(0xFF00897B)),
          border: OutlineInputBorder(
            borderRadius: BorderRadius.circular(12),
            borderSide: BorderSide.none,
          ),
          filled: true,
          fillColor: Colors.white,
          contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
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
            const Icon(Icons.calendar_today, color: Color(0xFF00897B)),
            const SizedBox(width: 16),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    'Required Date',
                    style: TextStyle(fontSize: 12, color: Colors.grey[600]),
                  ),
                  const SizedBox(height: 4),
                  Text(
                    '${_requiredDate.day}/${_requiredDate.month}/${_requiredDate.year}',
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
                padding: const EdgeInsets.all(8),
                decoration: BoxDecoration(
                  color: const Color(0xFF00897B).withValues(alpha: 0.1),
                  borderRadius: BorderRadius.circular(8),
                ),
                child: const Icon(Icons.inventory_2, color: Color(0xFF00897B), size: 20),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      item['product_name'] ?? 'N/A',
                      style: const TextStyle(fontWeight: FontWeight.w600, fontSize: 14),
                    ),
                    Text(
                      '${item['product_code'] ?? ''} • ${item['unit']}',
                      style: TextStyle(color: Colors.grey[600], fontSize: 12),
                    ),
                  ],
                ),
              ),
              IconButton(
                onPressed: () => _removeItem(index),
                icon: const Icon(Icons.delete_outline, color: Color(0xFFE53935)),
                tooltip: 'Remove item',
              ),
            ],
          ),
          const SizedBox(height: 12),
          
          // Quantity & Notes Row
          Row(
            children: [
              Expanded(
                flex: 2,
                child: TextFormField(
                  controller: item['quantityController'],
                  keyboardType: TextInputType.number,
                  inputFormatters: [FilteringTextInputFormatter.allow(RegExp(r'[\d.]'))],
                  onChanged: (_) => setState(() {}),
                  decoration: InputDecoration(
                    labelText: 'Quantity',
                    suffixText: item['unit'],
                    border: OutlineInputBorder(borderRadius: BorderRadius.circular(10)),
                    contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 12),
                  ),
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                flex: 3,
                child: TextFormField(
                  controller: item['notesController'],
                  decoration: InputDecoration(
                    labelText: 'Notes (Optional)',
                    border: OutlineInputBorder(borderRadius: BorderRadius.circular(10)),
                    contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 12),
                  ),
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }
}

