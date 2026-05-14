import 'package:flutter/material.dart';
import '../../services/retirement_service.dart';

class RetirementDetailsScreen extends StatefulWidget {
  final int id;

  const RetirementDetailsScreen({super.key, required this.id});

  @override
  State<RetirementDetailsScreen> createState() => _RetirementDetailsScreenState();
}

class _RetirementDetailsScreenState extends State<RetirementDetailsScreen> {
  bool _isLoading = true;
  Map<String, dynamic>? _retirement;

  @override
  void initState() {
    super.initState();
    _loadDetails();
  }

  Future<void> _loadDetails() async {
    setState(() => _isLoading = true);
    
    final result = await RetirementService.getRetirementDetails(widget.id);
    
    if (mounted) {
      setState(() {
        _isLoading = false;
        if (result['success']) {
          _retirement = result['data'];
        }
      });
    }
  }

  Color _getStatusColor(String status) {
    switch (status.toLowerCase()) {
      case 'pending':
        return const Color(0xFFFF9800);
      case 'checked':
        return const Color(0xFF2196F3);
      case 'approved':
        return const Color(0xFF4CAF50);
      case 'rejected':
        return const Color(0xFFF44336);
      default:
        return Colors.grey;
    }
  }

  IconData _getStatusIcon(String status) {
    switch (status.toLowerCase()) {
      case 'pending':
        return Icons.hourglass_empty;
      case 'checked':
        return Icons.fact_check;
      case 'approved':
        return Icons.check_circle;
      case 'rejected':
        return Icons.cancel;
      default:
        return Icons.help;
    }
  }

  @override
  Widget build(BuildContext context) {
    final status = _retirement?['status'] ?? 'pending';
    final statusColor = _getStatusColor(status);

    return Scaffold(
      backgroundColor: const Color(0xFFF5F7FA),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator())
          : _retirement == null
              ? _buildErrorState()
              : CustomScrollView(
                  slivers: [
                    // App Bar with gradient
                    SliverAppBar(
                      expandedHeight: 220,
                      floating: false,
                      pinned: true,
                      backgroundColor: statusColor,
                      leading: IconButton(
                        icon: const Icon(Icons.arrow_back_ios, color: Colors.white),
                        onPressed: () => Navigator.pop(context),
                      ),
                      actions: [
                        IconButton(
                          icon: const Icon(Icons.refresh, color: Colors.white),
                          onPressed: _loadDetails,
                        ),
                      ],
                      flexibleSpace: FlexibleSpaceBar(
                        title: Text(
                          _retirement!['retirement_number'] ?? 'Retirement',
                          style: const TextStyle(color: Colors.white),
                        ),
                        background: Container(
                          decoration: BoxDecoration(
                            gradient: LinearGradient(
                              begin: Alignment.topLeft,
                              end: Alignment.bottomRight,
                              colors: [
                                statusColor,
                                statusColor.withValues(alpha: 0.8),
                              ],
                            ),
                          ),
                          child: SafeArea(
                            child: Padding(
                              padding: const EdgeInsets.all(20.0),
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                mainAxisAlignment: MainAxisAlignment.end,
                                children: [
                                  Container(
                                    padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                                    decoration: BoxDecoration(
                                      color: Colors.white.withValues(alpha: 0.2),
                                      borderRadius: BorderRadius.circular(20),
                                    ),
                                    child: Row(
                                      mainAxisSize: MainAxisSize.min,
                                      children: [
                                        Icon(
                                          _getStatusIcon(status),
                                          color: Colors.white,
                                          size: 18,
                                        ),
                                        const SizedBox(width: 8),
                                        Text(
                                          _retirement!['status_label'] ?? status,
                                          style: const TextStyle(
                                            color: Colors.white,
                                            fontWeight: FontWeight.bold,
                                          ),
                                        ),
                                      ],
                                    ),
                                  ),
                                ],
                              ),
                            ),
                          ),
                        ),
                      ),
                    ),
                    // Content
                    SliverToBoxAdapter(
                      child: Padding(
                        padding: const EdgeInsets.all(16.0),
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            // Summary Card
                            _buildSummaryCard(),
                            const SizedBox(height: 16),
                            // Items Section
                            _buildItemsSection(),
                            const SizedBox(height: 16),
                            // Timeline Section
                            _buildTimelineSection(),
                            const SizedBox(height: 16),
                            // Notes Section
                            if (_retirement!['retirement_notes'] != null && _retirement!['retirement_notes'].toString().isNotEmpty)
                              _buildNotesSection(),
                            const SizedBox(height: 24),
                          ],
                        ),
                      ),
                    ),
                  ],
                ),
    );
  }

  Widget _buildErrorState() {
    return Center(
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Icon(Icons.error_outline, size: 64, color: Colors.red.shade300),
          const SizedBox(height: 16),
          const Text(
            'Failed to load retirement details',
            style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold),
          ),
          const SizedBox(height: 8),
          ElevatedButton.icon(
            onPressed: _loadDetails,
            icon: const Icon(Icons.refresh),
            label: const Text('Retry'),
          ),
        ],
      ),
    );
  }

  Widget _buildSummaryCard() {
    return Card(
      elevation: 2,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
      child: Padding(
        padding: const EdgeInsets.all(20.0),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Icon(Icons.info_outline, color: Colors.blue.shade700),
                const SizedBox(width: 8),
                Text(
                  'Retirement Summary',
                  style: TextStyle(
                    fontSize: 18,
                    fontWeight: FontWeight.bold,
                    color: Colors.grey.shade800,
                  ),
                ),
              ],
            ),
            const Divider(height: 24),
            _buildInfoRow('Retirement Number', _retirement!['retirement_number'] ?? 'N/A'),
            _buildInfoRow('Imprest Request', _retirement!['imprest_request_number'] ?? 'N/A'),
            _buildInfoRow('Employee', _retirement!['employee'] ?? 'N/A'),
            _buildInfoRow('Department', _retirement!['department'] ?? 'N/A'),
            _buildInfoRow('Purpose', _retirement!['purpose'] ?? 'N/A'),
            const Divider(height: 24),
            _buildAmountRow(
              'Disbursed Amount',
              _retirement!['disbursed_amount'] ?? 0,
              Colors.grey.shade700,
            ),
            _buildAmountRow(
              'Total Amount Used',
              _retirement!['total_amount_used'] ?? 0,
              Colors.blue.shade700,
              isBold: true,
            ),
            _buildAmountRow(
              'Remaining Balance',
              _retirement!['remaining_balance'] ?? 0,
              Colors.green.shade700,
              isBold: true,
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildInfoRow(String label, String value) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 12),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          SizedBox(
            width: 120,
            child: Text(
              label,
              style: TextStyle(
                color: Colors.grey.shade600,
                fontSize: 14,
              ),
            ),
          ),
          Expanded(
            child: Text(
              value,
              style: const TextStyle(
                fontWeight: FontWeight.w500,
                fontSize: 14,
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildAmountRow(String label, dynamic amount, Color color, {bool isBold = false}) {
    final formattedAmount = _formatAmount(amount);
    return Padding(
      padding: const EdgeInsets.only(bottom: 12),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Text(
            label,
            style: TextStyle(
              color: Colors.grey.shade600,
              fontSize: isBold ? 15 : 14,
              fontWeight: isBold ? FontWeight.bold : FontWeight.normal,
            ),
          ),
          Text(
            'TZS $formattedAmount',
            style: TextStyle(
              color: color,
              fontSize: isBold ? 16 : 14,
              fontWeight: FontWeight.bold,
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildItemsSection() {
    final items = _retirement!['items'] as List<dynamic>? ?? [];
    
    return Card(
      elevation: 2,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
      child: Padding(
        padding: const EdgeInsets.all(20.0),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Icon(Icons.list_alt, color: Colors.blue.shade700),
                const SizedBox(width: 8),
                Text(
                  'Retirement Items',
                  style: TextStyle(
                    fontSize: 18,
                    fontWeight: FontWeight.bold,
                    color: Colors.grey.shade800,
                  ),
                ),
              ],
            ),
            const Divider(height: 24),
            if (items.isEmpty)
              Padding(
                padding: const EdgeInsets.symmetric(vertical: 16),
                child: Text(
                  'No items found',
                  style: TextStyle(color: Colors.grey.shade600),
                ),
              )
            else
              ...items.map((item) => _buildItemCard(item)),
          ],
        ),
      ),
    );
  }

  Widget _buildItemCard(Map<String, dynamic> item) {
    final variance = item['variance'] ?? 0.0;
    final varianceColor = variance >= 0 ? Colors.green : Colors.red;
    
    return Container(
      margin: const EdgeInsets.only(bottom: 16),
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Colors.grey.shade50,
        borderRadius: BorderRadius.circular(8),
        border: Border.all(color: Colors.grey.shade200),
      ),
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
                    const SizedBox(height: 4),
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
                padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
                decoration: BoxDecoration(
                  color: varianceColor.withValues(alpha: 0.1),
                  borderRadius: BorderRadius.circular(8),
                ),
                child: Text(
                  variance >= 0 ? '+${_formatAmount(variance)}' : _formatAmount(variance),
                  style: TextStyle(
                    color: varianceColor,
                    fontWeight: FontWeight.bold,
                    fontSize: 13,
                  ),
                ),
              ),
            ],
          ),
          const SizedBox(height: 12),
          Row(
            children: [
              Expanded(
                child: _buildItemAmountRow(
                  'Requested',
                  item['requested_amount'] ?? 0,
                  Colors.grey.shade700,
                ),
              ),
              Expanded(
                child: _buildItemAmountRow(
                  'Actual',
                  item['actual_amount'] ?? 0,
                  Colors.blue.shade700,
                ),
              ),
            ],
          ),
          if (item['description'] != null && item['description'].toString().isNotEmpty) ...[
            const SizedBox(height: 8),
            Text(
              item['description'],
              style: TextStyle(
                color: Colors.grey.shade700,
                fontSize: 13,
              ),
            ),
          ],
          if (item['notes'] != null && item['notes'].toString().isNotEmpty) ...[
            const SizedBox(height: 4),
            Text(
              'Notes: ${item['notes']}',
              style: TextStyle(
                color: Colors.grey.shade600,
                fontSize: 12,
                fontStyle: FontStyle.italic,
              ),
            ),
          ],
        ],
      ),
    );
  }

  Widget _buildItemAmountRow(String label, dynamic amount, Color color) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          label,
          style: TextStyle(
            color: Colors.grey.shade600,
            fontSize: 12,
          ),
        ),
        const SizedBox(height: 4),
        Text(
          'TZS ${_formatAmount(amount)}',
          style: TextStyle(
            color: color,
            fontWeight: FontWeight.bold,
            fontSize: 14,
          ),
        ),
      ],
    );
  }

  Widget _buildTimelineSection() {
    final timeline = _retirement!['timeline'] as List<dynamic>? ?? [];
    
    if (timeline.isEmpty) {
      return const SizedBox.shrink();
    }
    
    return Card(
      elevation: 2,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
      child: Padding(
        padding: const EdgeInsets.all(20.0),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Icon(Icons.timeline, color: Colors.blue.shade700),
                const SizedBox(width: 8),
                Text(
                  'Timeline',
                  style: TextStyle(
                    fontSize: 18,
                    fontWeight: FontWeight.bold,
                    color: Colors.grey.shade800,
                  ),
                ),
              ],
            ),
            const Divider(height: 24),
            ...timeline.asMap().entries.map((entry) {
              final index = entry.key;
              final event = entry.value;
              final isLast = index == timeline.length - 1;
              
              return _buildTimelineItem(event, isLast);
            }),
          ],
        ),
      ),
    );
  }

  Widget _buildTimelineItem(Map<String, dynamic> event, bool isLast) {
    Color eventColor;
    IconData eventIcon;
    
    switch (event['event']) {
      case 'Submitted':
        eventColor = Colors.orange;
        eventIcon = Icons.send;
        break;
      case 'Checked':
        eventColor = Colors.blue;
        eventIcon = Icons.fact_check;
        break;
      case 'Approved':
        eventColor = Colors.green;
        eventIcon = Icons.check_circle;
        break;
      case 'Rejected':
        eventColor = Colors.red;
        eventIcon = Icons.cancel;
        break;
      default:
        eventColor = Colors.grey;
        eventIcon = Icons.info;
    }
    
    return Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Column(
          children: [
            Container(
              width: 40,
              height: 40,
              decoration: BoxDecoration(
                color: eventColor.withValues(alpha: 0.1),
                shape: BoxShape.circle,
                border: Border.all(color: eventColor, width: 2),
              ),
              child: Icon(eventIcon, color: eventColor, size: 20),
            ),
            if (!isLast)
              Container(
                width: 2,
                height: 40,
                color: Colors.grey.shade300,
                margin: const EdgeInsets.only(top: 4),
              ),
          ],
        ),
        const SizedBox(width: 16),
        Expanded(
          child: Padding(
            padding: EdgeInsets.only(bottom: isLast ? 0 : 16),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  event['event'] ?? 'N/A',
                  style: const TextStyle(
                    fontWeight: FontWeight.bold,
                    fontSize: 15,
                  ),
                ),
                const SizedBox(height: 4),
                Text(
                  'By: ${event['by'] ?? 'N/A'}',
                  style: TextStyle(
                    color: Colors.grey.shade600,
                    fontSize: 13,
                  ),
                ),
                Text(
                  event['at'] ?? 'N/A',
                  style: TextStyle(
                    color: Colors.grey.shade500,
                    fontSize: 12,
                  ),
                ),
                if (event['comments'] != null && event['comments'].toString().isNotEmpty) ...[
                  const SizedBox(height: 4),
                  Container(
                    padding: const EdgeInsets.all(8),
                    decoration: BoxDecoration(
                      color: Colors.grey.shade100,
                      borderRadius: BorderRadius.circular(8),
                    ),
                    child: Text(
                      event['comments'],
                      style: TextStyle(
                        color: Colors.grey.shade700,
                        fontSize: 12,
                      ),
                    ),
                  ),
                ],
              ],
            ),
          ),
        ),
      ],
    );
  }

  Widget _buildNotesSection() {
    return Card(
      elevation: 2,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
      child: Padding(
        padding: const EdgeInsets.all(20.0),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Icon(Icons.notes, color: Colors.blue.shade700),
                const SizedBox(width: 8),
                Text(
                  'Notes',
                  style: TextStyle(
                    fontSize: 18,
                    fontWeight: FontWeight.bold,
                    color: Colors.grey.shade800,
                  ),
                ),
              ],
            ),
            const Divider(height: 24),
            Text(
              _retirement!['retirement_notes'],
              style: TextStyle(
                color: Colors.grey.shade700,
                fontSize: 14,
                height: 1.5,
              ),
            ),
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

