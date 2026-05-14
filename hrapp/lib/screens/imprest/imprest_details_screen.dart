import 'package:flutter/material.dart';
import '../../services/imprest_service.dart';
import '../retirement/create_retirement_screen.dart';

class ImprestDetailsScreen extends StatefulWidget {
  final int imprestId;

  const ImprestDetailsScreen({super.key, required this.imprestId});

  @override
  State<ImprestDetailsScreen> createState() => _ImprestDetailsScreenState();
}

class _ImprestDetailsScreenState extends State<ImprestDetailsScreen> {
  bool _isLoading = true;
  Map<String, dynamic>? _imprest;

  @override
  void initState() {
    super.initState();
    _loadDetails();
  }

  Future<void> _loadDetails() async {
    setState(() => _isLoading = true);
    
    final result = await ImprestService.getImprestDetails(widget.imprestId);
    
    if (mounted) {
      setState(() {
        _isLoading = false;
        if (result['success']) {
          _imprest = result['data'];
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
      case 'disbursed':
        return const Color(0xFF9C27B0);
      case 'rejected':
        return const Color(0xFFF44336);
      case 'liquidated':
        return const Color(0xFF00BCD4);
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
      case 'disbursed':
        return Icons.payments;
      case 'rejected':
        return Icons.cancel;
      case 'liquidated':
        return Icons.receipt_long;
      default:
        return Icons.help;
    }
  }

  @override
  Widget build(BuildContext context) {
    final status = _imprest?['status'] ?? 'pending';
    final statusColor = _getStatusColor(status);

    return Scaffold(
      backgroundColor: const Color(0xFFF5F7FA),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator())
          : _imprest == null
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
                                              _imprest!['request_number'] ?? 'N/A',
                                              style: const TextStyle(
                                                color: Colors.white,
                                                fontSize: 22,
                                                fontWeight: FontWeight.bold,
                                              ),
                                            ),
                                            const SizedBox(height: 4),
                                            Container(
                                              padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                                              decoration: BoxDecoration(
                                                color: Colors.white.withValues(alpha: 0.2),
                                                borderRadius: BorderRadius.circular(12),
                                              ),
                                              child: Text(
                                                status.toUpperCase(),
                                                style: const TextStyle(
                                                  color: Colors.white,
                                                  fontSize: 12,
                                                  fontWeight: FontWeight.bold,
                                                ),
                                              ),
                                            ),
                                          ],
                                        ),
                                      ),
                                    ],
                                  ),
                                  const SizedBox(height: 20),
                                  // Amount
                                  Container(
                                    padding: const EdgeInsets.all(16),
                                    decoration: BoxDecoration(
                                      color: Colors.white.withValues(alpha: 0.15),
                                      borderRadius: BorderRadius.circular(12),
                                    ),
                                    child: Row(
                                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                                      children: [
                                        Column(
                                          crossAxisAlignment: CrossAxisAlignment.start,
                                          children: [
                                            const Text('Requested Amount', style: TextStyle(color: Colors.white70, fontSize: 12)),
                                            Text(
                                              'TZS ${_formatNumber(_imprest!['amount_requested'] ?? 0)}',
                                              style: const TextStyle(color: Colors.white, fontSize: 24, fontWeight: FontWeight.bold),
                                            ),
                                          ],
                                        ),
                                        if ((_imprest!['disbursed_amount'] ?? 0) > 0)
                                          Column(
                                            crossAxisAlignment: CrossAxisAlignment.end,
                                            children: [
                                              const Text('Disbursed', style: TextStyle(color: Colors.white70, fontSize: 12)),
                                              Text(
                                                'TZS ${_formatNumber(_imprest!['disbursed_amount'] ?? 0)}',
                                                style: const TextStyle(color: Colors.white, fontSize: 18, fontWeight: FontWeight.bold),
                                              ),
                                            ],
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
                        padding: const EdgeInsets.all(16),
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            // Purpose & Description Card
                            _buildCard(
                              title: 'Request Details',
                              icon: Icons.description,
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  _buildDetailRow('Purpose', _imprest!['purpose'] ?? 'N/A'),
                                  if (_imprest!['description'] != null && _imprest!['description'].isNotEmpty)
                                    _buildDetailRow('Description', _imprest!['description']),
                                  _buildDetailRow('Department', _imprest!['department'] ?? 'N/A'),
                                  _buildDetailRow('Date Required', _imprest!['date_required'] ?? 'N/A'),
                                  _buildDetailRow('Requested By', _imprest!['employee'] ?? 'N/A'),
                                  _buildDetailRow('Created At', _imprest!['created_at'] ?? 'N/A'),
                                ],
                              ),
                            ),
                            const SizedBox(height: 16),

                            // Expense Items Card
                            _buildCard(
                              title: 'Expense Items',
                              icon: Icons.receipt_long,
                              child: Column(
                                children: [
                                  ...(_imprest!['items'] as List? ?? []).map((item) {
                                    return _buildItemRow(item);
                                  }),
                                  const Divider(height: 24),
                                  Row(
                                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                                    children: [
                                      const Text('Total', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
                                      Text(
                                        'TZS ${_formatNumber(_imprest!['amount_requested'] ?? 0)}',
                                        style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16, color: Color(0xFF1976D2)),
                                      ),
                                    ],
                                  ),
                                ],
                              ),
                            ),
                            const SizedBox(height: 16),

                            // Approval Timeline Card
                            _buildCard(
                              title: 'Approval Timeline',
                              icon: Icons.timeline,
                              child: Column(
                                children: [
                                  _buildTimelineItem(
                                    'Created',
                                    _imprest!['created_at'],
                                    _imprest!['created_by'],
                                    Icons.add_circle,
                                    const Color(0xFF1976D2),
                                    true,
                                  ),
                                  if (_imprest!['checked_at'] != null)
                                    _buildTimelineItem(
                                      'Checked',
                                      _imprest!['checked_at'],
                                      _imprest!['checked_by'],
                                      Icons.fact_check,
                                      const Color(0xFF2196F3),
                                      true,
                                    ),
                                  if (_imprest!['approved_at'] != null)
                                    _buildTimelineItem(
                                      'Approved',
                                      _imprest!['approved_at'],
                                      _imprest!['approved_by'],
                                      Icons.check_circle,
                                      const Color(0xFF4CAF50),
                                      true,
                                    ),
                                  if (_imprest!['rejected_at'] != null)
                                    _buildTimelineItem(
                                      'Rejected',
                                      _imprest!['rejected_at'],
                                      _imprest!['rejected_by'],
                                      Icons.cancel,
                                      const Color(0xFFF44336),
                                      true,
                                      subtitle: _imprest!['rejection_reason'],
                                    ),
                                  if (_imprest!['disbursed_at'] != null)
                                    _buildTimelineItem(
                                      'Disbursed',
                                      _imprest!['disbursed_at'],
                                      _imprest!['disbursed_by'],
                                      Icons.payments,
                                      const Color(0xFF9C27B0),
                                      false,
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
      floatingActionButton: _canRetire()
          ? FloatingActionButton.extended(
              onPressed: () async {
                final result = await Navigator.push(
                  context,
                  MaterialPageRoute(builder: (context) => const CreateRetirementScreen()),
                );
                if (result == true) {
                  _loadDetails();
                }
              },
              icon: const Icon(Icons.account_balance_wallet),
              label: const Text('Retire'),
              backgroundColor: Colors.blue.shade600,
            )
          : null,
    );
  }

  bool _canRetire() {
    final status = _imprest?['status'] ?? '';
    final hasLiquidation = _imprest?['has_liquidation'] ?? false;
    final disbursedAmount = _imprest?['disbursed_amount'] ?? 0;
    
    // Can retire if status is 'disbursed', no liquidation exists, and has disbursed amount
    return status == 'disbursed' && !hasLiquidation && disbursedAmount > 0;
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
                  color: const Color(0xFF1976D2).withValues(alpha: 0.1),
                  borderRadius: BorderRadius.circular(8),
                ),
                child: Icon(icon, color: const Color(0xFF1976D2), size: 20),
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
    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: const Color(0xFFF5F7FA),
        borderRadius: BorderRadius.circular(10),
      ),
      child: Row(
        children: [
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  item['account'] ?? 'N/A',
                  style: const TextStyle(fontWeight: FontWeight.w600, fontSize: 14),
                ),
                if (item['account_code'] != null && item['account_code'].isNotEmpty)
                  Text(
                    item['account_code'],
                    style: TextStyle(color: Colors.grey[600], fontSize: 12),
                  ),
                if (item['notes'] != null && item['notes'].isNotEmpty)
                  Padding(
                    padding: const EdgeInsets.only(top: 4),
                    child: Text(
                      item['notes'],
                      style: TextStyle(color: Colors.grey[600], fontSize: 12, fontStyle: FontStyle.italic),
                    ),
                  ),
              ],
            ),
          ),
          Text(
            'TZS ${_formatNumber(item['amount'] ?? 0)}',
            style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 14, color: Color(0xFF1976D2)),
          ),
        ],
      ),
    );
  }

  Widget _buildTimelineItem(String title, String? date, String? by, IconData icon, Color color, bool hasLine, {String? subtitle}) {
    return Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Column(
          children: [
            Container(
              padding: const EdgeInsets.all(8),
              decoration: BoxDecoration(
                color: color.withValues(alpha: 0.1),
                shape: BoxShape.circle,
              ),
              child: Icon(icon, color: color, size: 20),
            ),
            if (hasLine)
              Container(
                width: 2,
                height: 40,
                color: color.withValues(alpha: 0.3),
              ),
          ],
        ),
        const SizedBox(width: 12),
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(title, style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 14)),
              if (date != null)
                Text(date, style: TextStyle(color: Colors.grey[600], fontSize: 12)),
              if (by != null)
                Text('By: $by', style: TextStyle(color: Colors.grey[600], fontSize: 12)),
              if (subtitle != null)
                Container(
                  margin: const EdgeInsets.only(top: 4),
                  padding: const EdgeInsets.all(8),
                  decoration: BoxDecoration(
                    color: color.withValues(alpha: 0.1),
                    borderRadius: BorderRadius.circular(8),
                  ),
                  child: Text(subtitle, style: TextStyle(color: color, fontSize: 12)),
                ),
              const SizedBox(height: 16),
            ],
          ),
        ),
      ],
    );
  }

  String _formatNumber(dynamic number) {
    if (number == null) return '0';
    final num = double.tryParse(number.toString()) ?? 0;
    return num.toStringAsFixed(0).replaceAllMapped(
      RegExp(r'(\d{1,3})(?=(\d{3})+(?!\d))'),
      (Match m) => '${m[1]},',
    );
  }
}

