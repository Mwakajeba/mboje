import 'package:flutter/material.dart';
import '../../services/requisition_service.dart';

class RequisitionDetailsScreen extends StatefulWidget {
  final int requisitionId;

  const RequisitionDetailsScreen({super.key, required this.requisitionId});

  @override
  State<RequisitionDetailsScreen> createState() => _RequisitionDetailsScreenState();
}

class _RequisitionDetailsScreenState extends State<RequisitionDetailsScreen> {
  bool _isLoading = true;
  Map<String, dynamic>? _requisition;

  @override
  void initState() {
    super.initState();
    _loadDetails();
  }

  Future<void> _loadDetails() async {
    setState(() => _isLoading = true);
    
    final result = await RequisitionService.getRequisitionDetails(widget.requisitionId);
    
    if (mounted) {
      setState(() {
        _isLoading = false;
        if (result['success']) {
          _requisition = result['data'];
        }
      });
    }
  }

  Color _getStatusColor(String status) {
    switch (status.toLowerCase()) {
      case 'pending':
        return const Color(0xFFFF9800);
      case 'approved':
        return const Color(0xFF4CAF50);
      case 'rejected':
        return const Color(0xFFF44336);
      case 'partially_issued':
        return const Color(0xFF2196F3);
      case 'fully_issued':
        return const Color(0xFF9C27B0);
      case 'completed':
        return const Color(0xFF00BCD4);
      default:
        return Colors.grey;
    }
  }

  IconData _getStatusIcon(String status) {
    switch (status.toLowerCase()) {
      case 'pending':
        return Icons.hourglass_empty;
      case 'approved':
        return Icons.check_circle;
      case 'rejected':
        return Icons.cancel;
      case 'partially_issued':
        return Icons.local_shipping;
      case 'fully_issued':
        return Icons.inventory;
      case 'completed':
        return Icons.done_all;
      default:
        return Icons.help;
    }
  }

  Color _getPriorityColor(String priority) {
    switch (priority.toLowerCase()) {
      case 'urgent':
        return const Color(0xFFF44336);
      case 'high':
        return const Color(0xFFFF9800);
      case 'normal':
        return const Color(0xFF4CAF50);
      case 'low':
        return const Color(0xFF9E9E9E);
      default:
        return Colors.grey;
    }
  }

  @override
  Widget build(BuildContext context) {
    final status = _requisition?['status'] ?? 'pending';
    final statusColor = _getStatusColor(status);

    return Scaffold(
      backgroundColor: const Color(0xFFF5F7FA),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator())
          : _requisition == null
              ? _buildErrorState()
              : CustomScrollView(
                  slivers: [
                    // App Bar with gradient
                    SliverAppBar(
                      expandedHeight: 200,
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
                        background: Container(
                          decoration: BoxDecoration(
                            gradient: LinearGradient(
                              begin: Alignment.topLeft,
                              end: Alignment.bottomRight,
                              colors: [statusColor, statusColor.withValues(alpha: 0.8)],
                            ),
                          ),
                          child: SafeArea(
                            child: Padding(
                              padding: const EdgeInsets.all(20),
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                mainAxisAlignment: MainAxisAlignment.end,
                                children: [
                                  Row(
                                    children: [
                                      Container(
                                        padding: const EdgeInsets.all(12),
                                        decoration: BoxDecoration(
                                          color: Colors.white.withValues(alpha: 0.2),
                                          borderRadius: BorderRadius.circular(16),
                                        ),
                                        child: Icon(_getStatusIcon(status), color: Colors.white, size: 32),
                                      ),
                                      const SizedBox(width: 16),
                                      Expanded(
                                        child: Column(
                                          crossAxisAlignment: CrossAxisAlignment.start,
                                          children: [
                                            Text(
                                              _requisition!['requisition_number'] ?? 'N/A',
                                              style: const TextStyle(
                                                color: Colors.white,
                                                fontSize: 22,
                                                fontWeight: FontWeight.bold,
                                              ),
                                            ),
                                            const SizedBox(height: 4),
                                            Row(
                                              children: [
                                                Container(
                                                  padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                                                  decoration: BoxDecoration(
                                                    color: Colors.white.withValues(alpha: 0.2),
                                                    borderRadius: BorderRadius.circular(12),
                                                  ),
                                                  child: Text(
                                                    status.replaceAll('_', ' ').toUpperCase(),
                                                    style: const TextStyle(
                                                      color: Colors.white,
                                                      fontSize: 11,
                                                      fontWeight: FontWeight.bold,
                                                    ),
                                                  ),
                                                ),
                                                const SizedBox(width: 8),
                                                Container(
                                                  padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                                                  decoration: BoxDecoration(
                                                    color: _getPriorityColor(_requisition!['priority'] ?? 'normal').withValues(alpha: 0.3),
                                                    borderRadius: BorderRadius.circular(8),
                                                  ),
                                                  child: Text(
                                                    (_requisition!['priority'] ?? 'normal').toString().toUpperCase(),
                                                    style: const TextStyle(
                                                      color: Colors.white,
                                                      fontSize: 10,
                                                      fontWeight: FontWeight.bold,
                                                    ),
                                                  ),
                                                ),
                                              ],
                                            ),
                                          ],
                                        ),
                                      ),
                                    ],
                                  ),
                                  const SizedBox(height: 20),
                                  // Summary
                                  Container(
                                    padding: const EdgeInsets.all(16),
                                    decoration: BoxDecoration(
                                      color: Colors.white.withValues(alpha: 0.15),
                                      borderRadius: BorderRadius.circular(12),
                                    ),
                                    child: Row(
                                      mainAxisAlignment: MainAxisAlignment.spaceAround,
                                      children: [
                                        _buildSummaryItem('Items', '${(_requisition!['items'] as List?)?.length ?? 0}'),
                                        Container(width: 1, height: 40, color: Colors.white24),
                                        _buildSummaryItem('Requested', '${_calculateTotalRequested()}'),
                                        Container(width: 1, height: 40, color: Colors.white24),
                                        _buildSummaryItem('Issued', '${_calculateTotalIssued()}'),
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
                        padding: const EdgeInsets.all(16),
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            // Request Details Card
                            _buildCard(
                              title: 'Request Details',
                              icon: Icons.description,
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  _buildDetailRow('Purpose', _requisition!['purpose'] ?? 'N/A'),
                                  if (_requisition!['notes'] != null && _requisition!['notes'].isNotEmpty)
                                    _buildDetailRow('Notes', _requisition!['notes']),
                                  _buildDetailRow('Department', _requisition!['department'] ?? 'N/A'),
                                  _buildDetailRow('Required Date', _requisition!['required_date'] ?? 'N/A'),
                                  _buildDetailRow('Requested By', _requisition!['requested_by'] ?? 'N/A'),
                                  _buildDetailRow('Created At', _requisition!['created_at'] ?? 'N/A'),
                                ],
                              ),
                            ),
                            const SizedBox(height: 16),

                            // Items Card
                            _buildCard(
                              title: 'Requested Items',
                              icon: Icons.inventory_2,
                              child: Column(
                                children: [
                                  ...(_requisition!['items'] as List? ?? []).map((item) {
                                    return _buildItemRow(item);
                                  }),
                                ],
                              ),
                            ),
                            const SizedBox(height: 16),

                            // Approval History Card
                            if ((_requisition!['approvals'] as List?)?.isNotEmpty ?? false)
                              _buildCard(
                                title: 'Approval History',
                                icon: Icons.history,
                                child: Column(
                                  children: [
                                    ...(_requisition!['approvals'] as List? ?? []).map((approval) {
                                      return _buildApprovalItem(approval);
                                    }),
                                  ],
                                ),
                              ),
                            
                            // Rejection Reason
                            if (_requisition!['rejection_reason'] != null && _requisition!['rejection_reason'].isNotEmpty)
                              Container(
                                margin: const EdgeInsets.only(top: 16),
                                padding: const EdgeInsets.all(16),
                                decoration: BoxDecoration(
                                  color: const Color(0xFFF44336).withValues(alpha: 0.1),
                                  borderRadius: BorderRadius.circular(12),
                                  border: Border.all(color: const Color(0xFFF44336).withValues(alpha: 0.3)),
                                ),
                                child: Row(
                                  crossAxisAlignment: CrossAxisAlignment.start,
                                  children: [
                                    const Icon(Icons.warning, color: Color(0xFFF44336)),
                                    const SizedBox(width: 12),
                                    Expanded(
                                      child: Column(
                                        crossAxisAlignment: CrossAxisAlignment.start,
                                        children: [
                                          const Text(
                                            'Rejection Reason',
                                            style: TextStyle(
                                              fontWeight: FontWeight.bold,
                                              color: Color(0xFFF44336),
                                            ),
                                          ),
                                          const SizedBox(height: 4),
                                          Text(
                                            _requisition!['rejection_reason'],
                                            style: const TextStyle(color: Color(0xFF374151)),
                                          ),
                                        ],
                                      ),
                                    ),
                                  ],
                                ),
                              ),
                            
                            const SizedBox(height: 24),
                          ],
                        ),
                      ),
                    ),
                  ],
                ),
    );
  }

  Widget _buildSummaryItem(String label, String value) {
    return Column(
      children: [
        Text(label, style: const TextStyle(color: Colors.white70, fontSize: 12)),
        const SizedBox(height: 4),
        Text(
          value,
          style: const TextStyle(color: Colors.white, fontSize: 20, fontWeight: FontWeight.bold),
        ),
      ],
    );
  }

  double _calculateTotalRequested() {
    final items = _requisition?['items'] as List? ?? [];
    return items.fold(0.0, (sum, item) => sum + (item['quantity_requested'] ?? 0).toDouble());
  }

  double _calculateTotalIssued() {
    final items = _requisition?['items'] as List? ?? [];
    return items.fold(0.0, (sum, item) => sum + (item['quantity_issued'] ?? 0).toDouble());
  }

  Widget _buildErrorState() {
    return Center(
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          const Icon(Icons.error_outline, size: 64, color: Colors.grey),
          const SizedBox(height: 16),
          const Text('Failed to load details', style: TextStyle(fontSize: 18, color: Colors.grey)),
          const SizedBox(height: 16),
          ElevatedButton.icon(
            onPressed: _loadDetails,
            icon: const Icon(Icons.refresh),
            label: const Text('Retry'),
          ),
        ],
      ),
    );
  }

  Widget _buildCard({required String title, required IconData icon, required Widget child}) {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.05),
            blurRadius: 10,
            offset: const Offset(0, 4),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
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
                style: const TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: Color(0xFF1F2937)),
              ),
            ],
          ),
          const SizedBox(height: 16),
          child,
        ],
      ),
    );
  }

  Widget _buildDetailRow(String label, String value) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 12),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          SizedBox(
            width: 120,
            child: Text(label, style: TextStyle(color: Colors.grey[600], fontSize: 13)),
          ),
          Expanded(
            child: Text(value, style: const TextStyle(fontWeight: FontWeight.w500, fontSize: 14)),
          ),
        ],
      ),
    );
  }

  Widget _buildItemRow(Map<String, dynamic> item) {
    final qtyRequested = (item['quantity_requested'] ?? 0).toDouble();
    final qtyIssued = (item['quantity_issued'] ?? 0).toDouble();
    final progress = qtyRequested > 0 ? qtyIssued / qtyRequested : 0.0;

    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: const Color(0xFFF5F7FA),
        borderRadius: BorderRadius.circular(10),
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
                      item['product'] ?? 'N/A',
                      style: const TextStyle(fontWeight: FontWeight.w600, fontSize: 14),
                    ),
                    if (item['product_code'] != null && item['product_code'].isNotEmpty)
                      Text(
                        '${item['product_code']} • ${item['unit'] ?? 'pcs'}',
                        style: TextStyle(color: Colors.grey[600], fontSize: 12),
                      ),
                  ],
                ),
              ),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                decoration: BoxDecoration(
                  color: _getItemStatusColor(item['status'] ?? 'pending').withValues(alpha: 0.1),
                  borderRadius: BorderRadius.circular(8),
                ),
                child: Text(
                  (item['status'] ?? 'pending').toString().replaceAll('_', ' ').toUpperCase(),
                  style: TextStyle(
                    color: _getItemStatusColor(item['status'] ?? 'pending'),
                    fontSize: 10,
                    fontWeight: FontWeight.bold,
                  ),
                ),
              ),
            ],
          ),
          const SizedBox(height: 12),
          // Quantity Progress
          Row(
            children: [
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        Text('Qty: ${qtyRequested.toStringAsFixed(0)}', style: TextStyle(color: Colors.grey[600], fontSize: 12)),
                        Text('Issued: ${qtyIssued.toStringAsFixed(0)}', style: TextStyle(color: Colors.grey[600], fontSize: 12)),
                      ],
                    ),
                    const SizedBox(height: 6),
                    ClipRRect(
                      borderRadius: BorderRadius.circular(4),
                      child: LinearProgressIndicator(
                        value: progress.clamp(0.0, 1.0),
                        backgroundColor: Colors.grey[300],
                        valueColor: AlwaysStoppedAnimation<Color>(
                          progress >= 1.0 ? const Color(0xFF4CAF50) : const Color(0xFF2196F3),
                        ),
                        minHeight: 6,
                      ),
                    ),
                  ],
                ),
              ),
            ],
          ),
          if (item['notes'] != null && item['notes'].isNotEmpty)
            Padding(
              padding: const EdgeInsets.only(top: 8),
              child: Text(
                item['notes'],
                style: TextStyle(color: Colors.grey[600], fontSize: 12, fontStyle: FontStyle.italic),
              ),
            ),
        ],
      ),
    );
  }

  Color _getItemStatusColor(String status) {
    switch (status.toLowerCase()) {
      case 'pending':
        return const Color(0xFFFF9800);
      case 'approved':
        return const Color(0xFF4CAF50);
      case 'rejected':
        return const Color(0xFFF44336);
      case 'partially_issued':
        return const Color(0xFF2196F3);
      case 'fully_issued':
        return const Color(0xFF9C27B0);
      default:
        return Colors.grey;
    }
  }

  Widget _buildApprovalItem(Map<String, dynamic> approval) {
    final action = approval['action'] ?? 'pending';
    final isApproved = action == 'approved';
    final color = isApproved ? const Color(0xFF4CAF50) : const Color(0xFFF44336);

    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.05),
        borderRadius: BorderRadius.circular(10),
        border: Border.all(color: color.withValues(alpha: 0.2)),
      ),
      child: Row(
        children: [
          Container(
            padding: const EdgeInsets.all(8),
            decoration: BoxDecoration(
              color: color.withValues(alpha: 0.1),
              shape: BoxShape.circle,
            ),
            child: Icon(
              isApproved ? Icons.check : Icons.close,
              color: color,
              size: 20,
            ),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  'Level ${approval['level']} - ${approval['approver'] ?? 'N/A'}',
                  style: const TextStyle(fontWeight: FontWeight.w600, fontSize: 14),
                ),
                Text(
                  approval['action_date'] ?? '',
                  style: TextStyle(color: Colors.grey[600], fontSize: 12),
                ),
                if (approval['comments'] != null && approval['comments'].isNotEmpty)
                  Padding(
                    padding: const EdgeInsets.only(top: 4),
                    child: Text(
                      approval['comments'],
                      style: TextStyle(color: Colors.grey[700], fontSize: 12, fontStyle: FontStyle.italic),
                    ),
                  ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

