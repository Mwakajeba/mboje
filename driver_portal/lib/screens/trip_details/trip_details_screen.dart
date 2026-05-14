import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:google_fonts/google_fonts.dart';
import '../../providers/language_provider.dart';
import '../../services/trips_service.dart';
import '../confirm_trip_start/confirm_trip_start_screen.dart';
import '../update_location/update_location_screen.dart';
import '../log_fuel/log_fuel_screen.dart';
import '../wallet/wallet_placeholder_screen.dart';
import '../settings/settings_screen.dart';

class TripDetailsScreen extends StatefulWidget {
  const TripDetailsScreen({super.key, this.trip});

  final Map<String, dynamic>? trip;

  @override
  State<TripDetailsScreen> createState() => _TripDetailsScreenState();
}

class _TripDetailsScreenState extends State<TripDetailsScreen> {
  static const Color primary = Color(0xFF135BEC);
  static const Color textPrimary = Color(0xFF111318);
  static const Color textSecondary = Color(0xFF616F89);
  final TripsService _tripsService = TripsService();
  bool _completing = false;
  Map<String, dynamic>? _trip;

  @override
  void initState() {
    super.initState();
    _trip = widget.trip;
  }

  @override
  void didUpdateWidget(covariant TripDetailsScreen oldWidget) {
    super.didUpdateWidget(oldWidget);
    if (widget.trip != oldWidget.trip) _trip = widget.trip;
  }

  Future<void> _refetchTrip() async {
    final tripId = _trip?['id']?.toString();
    if (tripId == null || tripId.isEmpty) return;
    final updated = await _tripsService.fetchTripById(tripId);
    if (mounted && updated != null) setState(() => _trip = updated);
  }

  @override
  Widget build(BuildContext context) {
    final trans = AppTranslations(Provider.of<LanguageProvider>(context).currentLanguage);

    return Scaffold(
      backgroundColor: const Color(0xFFF6F6F8),
      body: SafeArea(
        child: Column(
          children: [
            _buildAppBar(context, trans),
            Expanded(
              child: _buildTripDetailsContent(trans),
            ),
            _buildBottomNav(trans),
          ],
        ),
      ),
    );
  }

  Widget _buildAppBar(BuildContext context, AppTranslations trans) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
      decoration: BoxDecoration(
        color: Colors.white.withOpacity(0.9),
        border: Border(bottom: BorderSide(color: Colors.grey.shade100)),
      ),
      child: Row(
        children: [
          IconButton(
            onPressed: () => Navigator.pop(context),
            icon: const Icon(Icons.arrow_back),
            color: textPrimary,
          ),
          Expanded(
            child: Text(
              _trip != null && _trip!['trip_number'] != null
                  ? 'Trip #${_trip!['trip_number']}'
                  : 'Trip #TR-88293',
              style: GoogleFonts.manrope(
                fontSize: 15,
                fontWeight: FontWeight.w700,
                color: textPrimary,
              ),
              textAlign: TextAlign.center,
            ),
          ),
          Material(
            color: primary.withOpacity(0.1),
            borderRadius: BorderRadius.circular(24),
            child: InkWell(
              onTap: () {},
              borderRadius: BorderRadius.circular(24),
              child: const SizedBox(
                width: 40,
                height: 40,
                child: Icon(Icons.phone, color: primary, size: 22),
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildTripDetailsContent(AppTranslations trans) {
    return SingleChildScrollView(
      padding: const EdgeInsets.all(12),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          _tripInfoCard(trans),
          const SizedBox(height: 12),
          _sectionTitle(trans.get('vehicle_details'), 13),
          const SizedBox(height: 6),
          _assignedVehicleCard(trans),
          const SizedBox(height: 12),
          _sectionTitle(trans.get('operations'), 13),
          const SizedBox(height: 6),
          _operationsSection(trans),
          const SizedBox(height: 80),
        ],
      ),
    );
  }

  Widget _tripInfoCard(AppTranslations trans) {
    final trip = _trip;
    final client = (trip != null && trip['customer'] is Map) ? (trip['customer'] as Map)['name']?.toString() : null;
    final cargo = trip?['cargo_description']?.toString();
    final origin = trip?['origin_location']?.toString();
    final destination = trip?['destination_location']?.toString();
    final startDate = trip?['planned_start_date']?.toString();
    final endDate = trip?['planned_end_date']?.toString();
    final status = trip?['status']?.toString() ?? '';
    final approvalStatus = trip?['approval_status']?.toString();
    final approvalRequired = trip?['approval_required'] == true;
    final isInProgress = status == 'in_progress' || status == 'dispatched';
    final isCompleted = status == 'completed';
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Row(
          mainAxisAlignment: MainAxisAlignment.spaceBetween,
          children: [
            Text(
              trans.get('trip_info'),
              style: GoogleFonts.manrope(
                fontSize: 13,
                fontWeight: FontWeight.w800,
                color: textPrimary,
              ),
            ),
            if (approvalStatus != null) ...[
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                decoration: BoxDecoration(
                  color: approvalStatus == 'approved'
                      ? (approvalRequired ? Colors.green.shade100 : Colors.teal.shade50)
                      : approvalStatus == 'pending'
                          ? Colors.orange.shade100
                          : Colors.red.shade100,
                  borderRadius: BorderRadius.circular(999),
                ),
                child: Text(
                  approvalStatus == 'approved'
                      ? (approvalRequired
                          ? (trans.language == 'sw' ? 'Imethibitishwa' : 'Approved')
                          : (trans.language == 'sw' ? 'Imethibitishwa (Otomatiki)' : 'Approved (Auto)'))
                      : approvalStatus == 'pending'
                          ? (trans.language == 'sw' ? 'Inasubiri uthibitisho' : 'Pending approval')
                          : (trans.language == 'sw' ? 'Imekataliwa' : 'Rejected'),
                  style: GoogleFonts.manrope(
                    fontSize: 10,
                    fontWeight: FontWeight.w600,
                    color: textPrimary,
                  ),
                ),
              ),
              const SizedBox(width: 8),
            ],
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
              decoration: BoxDecoration(
                color: isCompleted
                    ? Colors.grey.shade200
                    : isInProgress
                        ? Colors.blue.shade100
                        : Colors.green.shade100,
                borderRadius: BorderRadius.circular(999),
              ),
              child: Text(
                isCompleted
                    ? (trans.language == 'sw' ? 'Imekamilika' : 'Completed')
                    : isInProgress
                        ? (trans.language == 'sw' ? 'Inaendelea' : 'In Progress')
                        : trans.get('on_schedule'),
                style: GoogleFonts.manrope(
                  fontSize: 9,
                  fontWeight: FontWeight.w700,
                  color: isCompleted
                      ? Colors.grey.shade700
                      : isInProgress
                          ? Colors.blue.shade700
                          : Colors.green.shade700,
                ),
              ),
            ),
          ],
        ),
        const SizedBox(height: 8),
        Container(
          padding: const EdgeInsets.all(10),
          decoration: BoxDecoration(
            color: Colors.white,
            borderRadius: BorderRadius.circular(8),
            border: Border.all(color: Colors.grey.shade100),
          ),
          child: Column(
            children: [
              _rowLabel(trans.get('client'), client ?? '—', 11),
              _rowLabel(trans.get('cargo'), cargo ?? '—', 11),
              const SizedBox(height: 8),
              Row(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Column(
                    children: [
                      Icon(Icons.location_on, size: 14, color: primary),
                      Container(
                        margin: const EdgeInsets.symmetric(vertical: 2),
                        width: 2,
                        height: 22,
                        decoration: BoxDecoration(
                          color: Colors.grey.shade300,
                          borderRadius: BorderRadius.circular(1),
                        ),
                      ),
                      Icon(Icons.flag, size: 14, color: Colors.grey.shade400),
                    ],
                  ),
                  const SizedBox(width: 8),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          origin ?? '—',
                          style: GoogleFonts.manrope(
                            fontSize: 11,
                            fontWeight: FontWeight.w700,
                            color: textPrimary,
                          ),
                        ),
                        const SizedBox(height: 2),
                        Row(
                          children: [
                            Icon(Icons.calendar_today, size: 9, color: primary),
                            const SizedBox(width: 4),
                            Text(
                              _formatDate(startDate) ?? '—',
                              style: GoogleFonts.manrope(
                                fontSize: 9,
                                color: textSecondary,
                              ),
                            ),
                          ],
                        ),
                        const SizedBox(height: 10),
                        Text(
                          destination ?? '—',
                          style: GoogleFonts.manrope(
                            fontSize: 11,
                            fontWeight: FontWeight.w700,
                            color: textPrimary,
                          ),
                        ),
                        const SizedBox(height: 2),
                        Row(
                          children: [
                            Icon(Icons.event_available, size: 9, color: primary),
                            const SizedBox(width: 4),
                            Text(
                              _formatDate(endDate) ?? '—',
                              style: GoogleFonts.manrope(
                                fontSize: 9,
                                color: textSecondary,
                              ),
                            ),
                          ],
                        ),
                      ],
                    ),
                  ),
                ],
              ),
            ],
          ),
        ),
      ],
    );
  }

  String? _formatDate(String? iso) {
    if (iso == null || iso.isEmpty) return null;
    try {
      final d = DateTime.tryParse(iso);
      if (d == null) return iso;
      return '${d.day}/${d.month}/${d.year} ${d.hour.toString().padLeft(2, '0')}:${d.minute.toString().padLeft(2, '0')}';
    } catch (_) {
      return iso;
    }
  }

  Widget _rowLabel(String label, String value, [double fontSize = 12]) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 4),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Text(
            label,
            style: GoogleFonts.manrope(
              fontSize: fontSize,
              fontWeight: FontWeight.w500,
              color: textSecondary,
            ),
          ),
          Flexible(
            child: Text(
              value,
              style: GoogleFonts.manrope(
                fontSize: fontSize,
                fontWeight: FontWeight.w700,
                color: textPrimary,
              ),
              overflow: TextOverflow.ellipsis,
              textAlign: TextAlign.right,
            ),
          ),
        ],
      ),
    );
  }

  Widget _sectionTitle(String title, [double fontSize = 15]) {
    return Text(
      title,
      style: GoogleFonts.manrope(
        fontSize: fontSize,
        fontWeight: FontWeight.w800,
        color: textPrimary,
      ),
    );
  }

  /// Gari aliyegawiwa kwenye safari hii (data halisi kutoka API)
  Widget _assignedVehicleCard(AppTranslations trans) {
    final trip = _trip;
    final vehicle = trip != null && trip['vehicle'] is Map
        ? trip['vehicle'] as Map<String, dynamic>
        : null;
    final name = vehicle?['name']?.toString();
    final regNo = vehicle?['registration_number']?.toString();
    final code = vehicle?['code']?.toString();
    final hasVehicle = (name != null && name.isNotEmpty) ||
        (regNo != null && regNo.isNotEmpty) ||
        (code != null && code.isNotEmpty);

    return Container(
      padding: const EdgeInsets.all(10),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(8),
        border: Border.all(color: Colors.grey.shade100),
      ),
      child: Row(
        children: [
          Container(
            width: 40,
            height: 40,
            decoration: BoxDecoration(
              color: primary.withOpacity(0.1),
              borderRadius: BorderRadius.circular(8),
            ),
            child: const Icon(Icons.directions_car, color: primary, size: 22),
          ),
          const SizedBox(width: 10),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  trans.get('assigned_vehicle'),
                  style: GoogleFonts.manrope(
                    fontSize: 9,
                    fontWeight: FontWeight.w700,
                    color: textSecondary,
                    letterSpacing: 0.5,
                  ),
                ),
                if (hasVehicle) ...[
                  const SizedBox(height: 2),
                  Text(
                    name ?? regNo ?? code ?? '—',
                    style: GoogleFonts.manrope(
                      fontSize: 12,
                      fontWeight: FontWeight.w700,
                      color: textPrimary,
                    ),
                  ),
                  if (regNo != null && regNo.isNotEmpty)
                    Text(
                      '${trans.get('registration_number')}: $regNo',
                      style: GoogleFonts.manrope(
                        fontSize: 10,
                        color: textSecondary,
                      ),
                    ),
                  if (code != null && code.isNotEmpty && code != regNo)
                    Text(
                      '${trans.get('vehicle_code')}: $code',
                      style: GoogleFonts.manrope(
                        fontSize: 10,
                        color: textSecondary,
                      ),
                    ),
                ] else
                  Text(
                    trans.get('no_vehicle_assigned'),
                    style: GoogleFonts.manrope(
                      fontSize: 11,
                      fontWeight: FontWeight.w600,
                      color: textSecondary,
                    ),
                  ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _operationsSection(AppTranslations trans) {
    final trip = _trip;
    final tripId = trip?['id']?.toString();
    final status = trip?['status']?.toString() ?? '';
    final canStart = tripId != null &&
        tripId.isNotEmpty &&
        status != 'in_progress' &&
        status != 'dispatched' &&
        status != 'completed';
    final canComplete =
        tripId != null && tripId.isNotEmpty && (status == 'in_progress' || status == 'dispatched');

    return Column(
      children: [
        if (canStart)
          SizedBox(
            width: double.infinity,
            child: FilledButton.icon(
              onPressed: () async {
                final result = await Navigator.push<bool>(
                  context,
                  MaterialPageRoute(
                    builder: (_) => ConfirmTripStartScreen(trip: trip),
                  ),
                );
                if (result == true && mounted) await _refetchTrip();
              },
              icon: const Icon(Icons.play_circle, size: 20),
              label: Text(
                trans.get('start_trip'),
                style: GoogleFonts.manrope(
                  fontSize: 12,
                  fontWeight: FontWeight.w700,
                ),
              ),
              style: FilledButton.styleFrom(
                backgroundColor: primary,
                padding: const EdgeInsets.symmetric(vertical: 12),
                shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(8),
                ),
              ),
            ),
          ),
        if (canStart) const SizedBox(height: 6),
        GridView.count(
          shrinkWrap: true,
          physics: const NeverScrollableScrollPhysics(),
          crossAxisCount: 3,
          mainAxisSpacing: 5,
          crossAxisSpacing: 5,
          childAspectRatio: 1.0,
          children: [
            _actionTile(
              Icons.my_location,
              trans.get('update_location'),
              primary,
              onTap: () => Navigator.push(
                context,
                MaterialPageRoute(builder: (_) => UpdateLocationScreen(trip: _trip)),
              ),
            ),
            _actionTile(
              Icons.schedule,
              trans.get('report_delay'),
              Colors.amber.shade700,
              onTap: canComplete ? () => _showReportDelayDialog(trans) : null,
            ),
            _actionTile(Icons.payments, trans.get('request_allowance'), Colors.green.shade700),
            _actionTile(
              Icons.local_gas_station,
              trans.get('log_fuel'),
              Colors.blue.shade700,
              onTap: canComplete && _trip != null ? () => Navigator.push(context, MaterialPageRoute(builder: (_) => LogFuelScreen(trip: _trip!))) : null,
            ),
            _actionTile(
              Icons.receipt_long,
              trans.get('add_expense'),
              Colors.purple.shade700,
              onTap: canComplete ? () => _showAddExpenseDialog(trans) : null,
            ),
            _actionTile(
              Icons.warning,
              trans.get('report_incident'),
              Colors.red,
              onTap: canComplete ? () => _showReportIncidentDialog(trans) : null,
            ),
          ],
        ),
        const SizedBox(height: 8),
        SizedBox(
          width: double.infinity,
          child: OutlinedButton.icon(
            onPressed: canComplete && !_completing
                ? () async {
                    setState(() => _completing = true);
                    final result = await _tripsService.completeTrip(tripId!);
                    if (!mounted) return;
                    setState(() => _completing = false);
                    if (result['success'] == true) {
                      ScaffoldMessenger.of(context).showSnackBar(
                        SnackBar(
                          content: Text(
                            trans.get('trip_completed_success'),
                          ),
                          backgroundColor: Colors.green,
                        ),
                      );
                      setState(() {});
                    } else {
                      ScaffoldMessenger.of(context).showSnackBar(
                        SnackBar(
                          content: Text(
                            result['message']?.toString() ?? 'Error',
                          ),
                          backgroundColor: Colors.red,
                        ),
                      );
                    }
                  }
                : null,
            icon: _completing
                ? const SizedBox(
                    width: 20,
                    height: 20,
                    child: CircularProgressIndicator(strokeWidth: 2),
                  )
                : const Icon(Icons.check_circle, size: 20),
            label: Text(
              trans.get('complete_trip'),
              style: GoogleFonts.manrope(
                fontSize: 12,
                fontWeight: FontWeight.w700,
              ),
            ),
            style: OutlinedButton.styleFrom(
              padding: const EdgeInsets.symmetric(vertical: 12),
              shape: RoundedRectangleBorder(
                borderRadius: BorderRadius.circular(8),
              ),
              side: BorderSide(color: Colors.grey.shade300),
            ),
          ),
        ),
      ],
    );
  }

  Widget _actionTile(IconData icon, String label, Color color, {VoidCallback? onTap}) {
    return Material(
      color: Colors.white,
      borderRadius: BorderRadius.circular(6),
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(6),
        child: Container(
          padding: const EdgeInsets.symmetric(horizontal: 4, vertical: 6),
          decoration: BoxDecoration(
            borderRadius: BorderRadius.circular(6),
            border: Border.all(color: Colors.grey.shade100),
          ),
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              Icon(icon, color: color, size: 18),
              const SizedBox(height: 2),
              Text(
                label,
                style: GoogleFonts.manrope(
                  fontSize: 8,
                  fontWeight: FontWeight.w700,
                  color: textPrimary,
                ),
                textAlign: TextAlign.center,
                maxLines: 2,
                overflow: TextOverflow.ellipsis,
              ),
            ],
          ),
        ),
      ),
    );
  }

  void _showReportDelayDialog(AppTranslations trans) {
    final tripId = _trip?['id']?.toString();
    if (tripId == null) return;
    final reasonController = TextEditingController();
    final minutesController = TextEditingController();
    final notesController = TextEditingController();
    showDialog(
      context: context,
      builder: (ctx) => AlertDialog(
        title: Text(trans.get('report_delay')),
        content: SingleChildScrollView(
          child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              TextField(
                controller: reasonController,
                decoration: InputDecoration(
                  labelText: trans.language == 'sw' ? 'Sababu' : 'Reason',
                  border: const OutlineInputBorder(),
                  isDense: true,
                  contentPadding: const EdgeInsets.symmetric(horizontal: 10, vertical: 8),
                ),
                maxLines: 2,
              ),
              const SizedBox(height: 8),
              TextField(
                controller: minutesController,
                decoration: InputDecoration(
                  labelText: trans.language == 'sw' ? 'Dakika (tarakimu)' : 'Minutes (number)',
                  border: const OutlineInputBorder(),
                  isDense: true,
                  contentPadding: const EdgeInsets.symmetric(horizontal: 10, vertical: 8),
                ),
                keyboardType: TextInputType.number,
              ),
              const SizedBox(height: 8),
              TextField(
                controller: notesController,
                decoration: InputDecoration(
                  labelText: trans.get('optional_comments'),
                  border: const OutlineInputBorder(),
                  isDense: true,
                  contentPadding: const EdgeInsets.symmetric(horizontal: 10, vertical: 8),
                ),
                maxLines: 2,
              ),
            ],
          ),
        ),
        actions: [
          TextButton(onPressed: () => Navigator.pop(ctx), child: Text(trans.get('cancel'))),
          FilledButton(
            onPressed: () async {
              if (reasonController.text.trim().isEmpty) return;
              Navigator.pop(ctx);
              final result = await _tripsService.reportDelay(
                tripId,
                reason: reasonController.text.trim(),
                estimatedDelayMinutes: int.tryParse(minutesController.text.trim()),
                notes: notesController.text.trim().isEmpty ? null : notesController.text.trim(),
              );
              if (!mounted) return;
              ScaffoldMessenger.of(context).showSnackBar(
                SnackBar(
                  content: Text(result['success'] == true
                      ? (trans.language == 'sw' ? 'Ucheleweshaji umeripotiwa.' : 'Delay reported.')
                      : result['message']?.toString() ?? 'Error'),
                  backgroundColor: result['success'] == true ? Colors.green : Colors.red,
                ),
              );
            },
            child: Text(trans.get('save')),
          ),
        ],
      ),
    );
  }

  void _showAddExpenseDialog(AppTranslations trans) {
    final tripId = _trip?['id']?.toString();
    if (tripId == null) return;
    final amountController = TextEditingController();
    final typeController = TextEditingController(text: 'other');
    final descController = TextEditingController();
    showDialog(
      context: context,
      builder: (ctx) => AlertDialog(
        title: Text(trans.get('add_expense')),
        content: SingleChildScrollView(
          child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              TextField(
                controller: amountController,
                decoration: InputDecoration(
                  labelText: trans.language == 'sw' ? 'Kiasi (TZS)' : 'Amount (TZS)',
                  border: const OutlineInputBorder(),
                  isDense: true,
                  contentPadding: const EdgeInsets.symmetric(horizontal: 10, vertical: 8),
                ),
                keyboardType: const TextInputType.numberWithOptions(decimal: true),
              ),
              const SizedBox(height: 8),
              TextField(
                controller: typeController,
                decoration: InputDecoration(
                  labelText: trans.language == 'sw' ? 'Aina (fuel, toll, allowance, other)' : 'Type (fuel, toll, allowance, other)',
                  border: const OutlineInputBorder(),
                  isDense: true,
                  contentPadding: const EdgeInsets.symmetric(horizontal: 10, vertical: 8),
                ),
              ),
              const SizedBox(height: 8),
              TextField(
                controller: descController,
                decoration: InputDecoration(
                  labelText: trans.language == 'sw' ? 'Maelezo' : 'Description',
                  border: const OutlineInputBorder(),
                  isDense: true,
                  contentPadding: const EdgeInsets.symmetric(horizontal: 10, vertical: 8),
                ),
                maxLines: 2,
              ),
            ],
          ),
        ),
        actions: [
          TextButton(onPressed: () => Navigator.pop(ctx), child: Text(trans.get('cancel'))),
          FilledButton(
            onPressed: () async {
              final amount = double.tryParse(amountController.text.trim());
              if (amount == null || amount <= 0) return;
              if (descController.text.trim().isEmpty) return;
              Navigator.pop(ctx);
              final result = await _tripsService.addExpense(
                tripId,
                amount: amount,
                costType: typeController.text.trim().isEmpty ? 'other' : typeController.text.trim(),
                description: descController.text.trim(),
              );
              if (!mounted) return;
              ScaffoldMessenger.of(context).showSnackBar(
                SnackBar(
                  content: Text(result['success'] == true
                      ? (trans.language == 'sw' ? 'Gharama imeongezwa.' : 'Expense added.')
                      : result['message']?.toString() ?? 'Error'),
                  backgroundColor: result['success'] == true ? Colors.green : Colors.red,
                ),
              );
            },
            child: Text(trans.get('save')),
          ),
        ],
      ),
    );
  }

  void _showReportIncidentDialog(AppTranslations trans) {
    final tripId = _trip?['id']?.toString();
    if (tripId == null) return;
    final descController = TextEditingController();
    String? severity = 'medium';
    showDialog(
      context: context,
      builder: (ctx) => StatefulBuilder(
        builder: (context, setDialogState) => AlertDialog(
          title: Text(trans.get('report_incident')),
          content: SingleChildScrollView(
            child: Column(
              mainAxisSize: MainAxisSize.min,
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                TextField(
                  controller: descController,
                  decoration: InputDecoration(
                    labelText: trans.language == 'sw' ? 'Maelezo' : 'Description',
                    border: const OutlineInputBorder(),
                    isDense: true,
                    contentPadding: const EdgeInsets.symmetric(horizontal: 10, vertical: 8),
                  ),
                  maxLines: 3,
                ),
                const SizedBox(height: 10),
                DropdownButtonFormField<String>(
                  value: severity,
                  decoration: InputDecoration(
                    labelText: trans.language == 'sw' ? 'Ukali' : 'Severity',
                    border: const OutlineInputBorder(),
                    isDense: true,
                    contentPadding: const EdgeInsets.symmetric(horizontal: 10, vertical: 8),
                  ),
                  items: const [
                    DropdownMenuItem(value: 'low', child: Text('Low')),
                    DropdownMenuItem(value: 'medium', child: Text('Medium')),
                    DropdownMenuItem(value: 'high', child: Text('High')),
                    DropdownMenuItem(value: 'critical', child: Text('Critical')),
                  ],
                  onChanged: (v) => setDialogState(() => severity = v),
                ),
              ],
            ),
          ),
          actions: [
            TextButton(onPressed: () => Navigator.pop(ctx), child: Text(trans.get('cancel'))),
            FilledButton(
              onPressed: () async {
                if (descController.text.trim().isEmpty) return;
                Navigator.pop(ctx);
                final result = await _tripsService.reportIncident(
                  tripId,
                  description: descController.text.trim(),
                  severity: severity,
                );
                if (!mounted) return;
                ScaffoldMessenger.of(context).showSnackBar(
                  SnackBar(
                    content: Text(result['success'] == true
                        ? (trans.language == 'sw' ? 'Tukio limeripotiwa.' : 'Incident reported.')
                        : result['message']?.toString() ?? 'Error'),
                    backgroundColor: result['success'] == true ? Colors.green : Colors.red,
                  ),
                );
              },
              child: Text(trans.get('save')),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildBottomNav(AppTranslations trans) {
    return Container(
      padding: EdgeInsets.only(
        left: 12,
        right: 12,
        top: 8,
        bottom: MediaQuery.of(context).padding.bottom + 8,
      ),
      decoration: BoxDecoration(
        color: Colors.white,
        border: Border(top: BorderSide(color: Colors.grey.shade200)),
      ),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceAround,
        children: [
          _navItem(0, Icons.home, trans.get('home')),
          _navItem(1, Icons.route, trans.get('trips')),
          _navItem(2, Icons.account_balance_wallet_outlined, trans.get('wallet')),
          _navItem(3, Icons.person_outline, trans.get('profile')),
        ],
      ),
    );
  }

  Widget _navItem(int index, IconData icon, String label) {
    const int tripsIndex = 1;
    final selected = index == tripsIndex;
    return GestureDetector(
      onTap: () {
        if (index == 0) {
          Navigator.popUntil(context, (route) => route.isFirst);
          return;
        }
        if (index == 2) {
          Navigator.push(
            context,
            MaterialPageRoute(builder: (_) => const WalletPlaceholderScreen()),
          );
          return;
        }
        if (index == 3) {
          Navigator.push(
            context,
            MaterialPageRoute(builder: (_) => const SettingsScreen()),
          );
          return;
        }
        // index == 1 (Trips): stay on this screen
      },
      behavior: HitTestBehavior.opaque,
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(
            icon,
            size: 22,
            color: selected ? primary : Colors.black38,
          ),
          const SizedBox(height: 2),
          Text(
            label,
            style: GoogleFonts.manrope(
              fontSize: 9,
              fontWeight: selected ? FontWeight.w700 : FontWeight.w500,
              color: selected ? primary : Colors.black38,
            ),
          ),
        ],
      ),
    );
  }
}
