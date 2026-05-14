import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:google_fonts/google_fonts.dart';
import '../../providers/language_provider.dart';
import '../../services/trips_service.dart';

/// Full fuel log form aligned with system: GL account, fuel card, paid-from bank.
class LogFuelScreen extends StatefulWidget {
  const LogFuelScreen({super.key, required this.trip});

  final Map<String, dynamic> trip;

  @override
  State<LogFuelScreen> createState() => _LogFuelScreenState();
}

class _LogFuelScreenState extends State<LogFuelScreen> {
  static const Color primary = Color(0xFF135BEC);
  final TripsService _tripsService = TripsService();

  List<Map<String, dynamic>> _glAccountsDiesel = [];
  List<Map<String, dynamic>> _glAccountsPetrol = [];
  List<Map<String, dynamic>> _bankAccounts = [];
  int? _defaultBankId;
  bool _loadingOptions = true;
  bool _paymentByCard = false;
  Map<String, dynamic>? _driverCard;

  final _litersController = TextEditingController();
  final _costPerLiterController = TextEditingController();
  final _totalCostController = TextEditingController();
  final _odometerController = TextEditingController();
  final _previousOdometerController = TextEditingController();
  final _fuelStationController = TextEditingController();
  final _fuelCardNumberController = TextEditingController();
  final _fuelCardTypeController = TextEditingController();
  final _receiptNumberController = TextEditingController();
  final _notesController = TextEditingController();

  String? _fuelType; // petrol, diesel
  int? _selectedGlAccountId;
  int? _selectedBankId;
  bool _fuelCardUsed = false;
  DateTime _dateFilled = DateTime.now();
  TimeOfDay _timeFilled = TimeOfDay.now();
  bool _submitting = false;

  @override
  void initState() {
    super.initState();
    _loadFuelOptions();
    _costPerLiterController.addListener(_recalcTotal);
    _litersController.addListener(_recalcTotal);
  }

  String? get _tripId => widget.trip['id']?.toString();

  @override
  void dispose() {
    _litersController.dispose();
    _costPerLiterController.dispose();
    _totalCostController.dispose();
    _odometerController.dispose();
    _previousOdometerController.dispose();
    _fuelStationController.dispose();
    _fuelCardNumberController.dispose();
    _fuelCardTypeController.dispose();
    _receiptNumberController.dispose();
    _notesController.dispose();
    super.dispose();
  }

  void _recalcTotal() {
    final liters = double.tryParse(_litersController.text.trim());
    final cost = double.tryParse(_costPerLiterController.text.trim());
    if (liters != null && cost != null && liters > 0) {
      _totalCostController.text = (liters * cost).toStringAsFixed(2);
    }
  }

  Future<void> _loadFuelOptions() async {
    final data = await _tripsService.fetchFuelOptions(tripId: _tripId);
    if (!mounted) return;
    setState(() {
      _loadingOptions = false;
      if (data != null) {
        _glAccountsDiesel = (data['gl_accounts_diesel'] as List?)
            ?.map((e) => Map<String, dynamic>.from(e as Map))
            .toList() ?? [];
        _glAccountsPetrol = (data['gl_accounts_petrol'] as List?)
            ?.map((e) => Map<String, dynamic>.from(e as Map))
            .toList() ?? [];
        _paymentByCard = data['payment_by_card'] == true;
        _driverCard = data['driver_card'] is Map ? Map<String, dynamic>.from(data['driver_card'] as Map) : null;
        if (_paymentByCard && _driverCard != null) {
          _bankAccounts = [_driverCard!];
          _defaultBankId = _driverCard!['id'] is int ? _driverCard!['id'] as int : int.tryParse(_driverCard!['id'].toString());
          _selectedBankId = _defaultBankId;
        } else {
          _bankAccounts = (data['bank_accounts'] as List?)
              ?.map((e) => Map<String, dynamic>.from(e as Map))
              .toList() ?? [];
          _defaultBankId = data['default_bank_account_id'] as int?;
          if (_defaultBankId != null && _selectedBankId == null) {
            _selectedBankId = _defaultBankId;
          }
        }
        // Previous odometer: auto-filled from last fuel log or trip/vehicle, not editable (like system)
        final prev = data['previous_odometer'];
        if (prev != null) {
          if (prev is num) {
            _previousOdometerController.text = prev.toStringAsFixed(prev is int ? 0 : 2);
          } else {
            _previousOdometerController.text = prev.toString();
          }
        }
      }
    });
  }

  List<Map<String, dynamic>> get _glAccountsForType {
    if (_fuelType == 'diesel') return _glAccountsDiesel;
    if (_fuelType == 'petrol') return _glAccountsPetrol;
    return _glAccountsDiesel.isNotEmpty ? _glAccountsDiesel : _glAccountsPetrol;
  }

  @override
  Widget build(BuildContext context) {
    final trans = AppTranslations(Provider.of<LanguageProvider>(context).currentLanguage);
    final trip = widget.trip;
    final tripNumber = trip['trip_number']?.toString() ?? '—';
    final vehicleName = trip['vehicle'] is Map
        ? (trip['vehicle']!['name'] ?? trip['vehicle']!['registration_number'])?.toString() ?? '—'
        : '—';

    return Scaffold(
      backgroundColor: const Color(0xFFF6F6F8),
      appBar: AppBar(
        title: Text(
          trans.get('log_fuel'),
          style: GoogleFonts.manrope(fontWeight: FontWeight.w700),
        ),
        backgroundColor: Colors.white,
        foregroundColor: primary,
        elevation: 0,
      ),
      body: _loadingOptions
          ? const Center(child: CircularProgressIndicator())
          : SingleChildScrollView(
              padding: const EdgeInsets.all(16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  _sectionTitle(trans, trans.language == 'sw' ? 'Safari na Gari' : 'Trip & Vehicle'),
                  _readOnlyCard('Trip', '#$tripNumber'),
                  const SizedBox(height: 8),
                  _readOnlyCard(trans.language == 'sw' ? 'Gari' : 'Vehicle', vehicleName),
                  const SizedBox(height: 20),
                  _sectionTitle(trans, trans.language == 'sw' ? 'Maelezo ya Mafuta' : 'Fuel Details'),
                  _dropdown(
                    label: trans.language == 'sw' ? 'Aina ya mafuta' : 'Fuel type',
                    value: _fuelType,
                    items: const ['petrol', 'diesel'],
                    labels: (trans.language == 'sw') ? ['Petrol', 'Diesel'] : ['Petrol', 'Diesel'],
                    onChanged: (v) => setState(() {
                      _fuelType = v;
                      _selectedGlAccountId = null;
                    }),
                  ),
                  const SizedBox(height: 12),
                  _textField(
                    controller: _litersController,
                    label: trans.language == 'sw' ? 'Lita' : 'Liters',
                    keyboardType: TextInputType.number,
                    required: true,
                  ),
                  const SizedBox(height: 12),
                  _textField(
                    controller: _costPerLiterController,
                    label: trans.language == 'sw' ? 'Gharama kwa lita' : 'Cost per liter',
                    keyboardType: TextInputType.number,
                  ),
                  const SizedBox(height: 12),
                  _textField(
                    controller: _totalCostController,
                    label: trans.language == 'sw' ? 'Jumla (TZS)' : 'Total (TZS)',
                    keyboardType: TextInputType.number,
                  ),
                  const SizedBox(height: 12),
                  _textField(
                    controller: _fuelStationController,
                    label: trans.language == 'sw' ? 'Kituo cha mafuta' : 'Fuel station',
                  ),
                  const SizedBox(height: 20),
                  _sectionTitle(trans, trans.language == 'sw' ? 'Odometer' : 'Odometer'),
                  _textField(
                    controller: _odometerController,
                    label: trans.language == 'sw' ? 'Odometer sasa' : 'Current odometer',
                    keyboardType: TextInputType.number,
                  ),
                  const SizedBox(height: 12),
                  _textField(
                    controller: _previousOdometerController,
                    label: trans.language == 'sw' ? 'Odometer iliyotangulia' : 'Previous odometer',
                    keyboardType: TextInputType.number,
                    readOnly: true,
                  ),
                  const SizedBox(height: 20),
                  _sectionTitle(trans, trans.language == 'sw' ? 'Kadi ya mafuta' : 'Fuel card'),
                  CheckboxListTile(
                    value: _fuelCardUsed,
                    onChanged: (v) => setState(() => _fuelCardUsed = v ?? false),
                    title: Text(
                      trans.language == 'sw' ? 'Nilitumia kadi ya mafuta' : 'I used fuel card',
                      style: GoogleFonts.manrope(fontSize: 14),
                    ),
                    controlAffinity: ListTileControlAffinity.leading,
                    activeColor: primary,
                  ),
                  const SizedBox(height: 20),
                  _sectionTitle(trans, trans.language == 'sw' ? 'GL na Benki' : 'GL & Bank'),
                  _glAccountDropdown(trans),
                  const SizedBox(height: 12),
                  if (_paymentByCard && _driverCard != null)
                    _driverCardDisplay(trans)
                  else
                    _bankDropdown(trans),
                  const SizedBox(height: 12),
                  _textField(
                    controller: _receiptNumberController,
                    label: trans.language == 'sw' ? 'Nambari ya risiti' : 'Receipt number',
                  ),
                  const SizedBox(height: 12),
                  Row(
                    children: [
                      Expanded(
                        child: _dateTimeChip(
                          icon: Icons.calendar_today,
                          label: '${_dateFilled.day}/${_dateFilled.month}/${_dateFilled.year}',
                          onTap: () async {
                            final d = await showDatePicker(
                              context: context,
                              initialDate: _dateFilled,
                              firstDate: DateTime(2020),
                              lastDate: DateTime.now().add(const Duration(days: 365)),
                            );
                            if (d != null) setState(() => _dateFilled = d);
                          },
                        ),
                      ),
                      const SizedBox(width: 12),
                      Expanded(
                        child: _dateTimeChip(
                          icon: Icons.access_time,
                          label: '${_timeFilled.hour.toString().padLeft(2, '0')}:${_timeFilled.minute.toString().padLeft(2, '0')}',
                          onTap: () async {
                            final t = await showTimePicker(
                              context: context,
                              initialTime: _timeFilled,
                            );
                            if (t != null) setState(() => _timeFilled = t);
                          },
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 12),
                  _textField(
                    controller: _notesController,
                    label: trans.language == 'sw' ? 'Maelezo' : 'Notes',
                    maxLines: 2,
                  ),
                  const SizedBox(height: 32),
                  SizedBox(
                    width: double.infinity,
                    height: 52,
                    child: FilledButton.icon(
                      onPressed: _submitting ? null : () => _submit(trans),
                      icon: _submitting
                          ? const SizedBox(
                              width: 22,
                              height: 22,
                              child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white),
                            )
                          : const Icon(Icons.save, size: 22),
                      label: Text(
                        trans.get('save'),
                        style: GoogleFonts.manrope(fontSize: 15, fontWeight: FontWeight.w700),
                      ),
                      style: FilledButton.styleFrom(
                        backgroundColor: primary,
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                      ),
                    ),
                  ),
                  const SizedBox(height: 24),
                ],
              ),
            ),
    );
  }

  Widget _sectionTitle(AppTranslations trans, String title) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 8),
      child: Text(
        title.toUpperCase(),
        style: GoogleFonts.manrope(
          fontSize: 11,
          fontWeight: FontWeight.w700,
          color: Colors.black54,
          letterSpacing: 0.5,
        ),
      ),
    );
  }

  Widget _readOnlyCard(String label, String value) {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(10),
        border: Border.all(color: Colors.grey.shade200),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            label,
            style: GoogleFonts.manrope(fontSize: 10, color: Colors.black54),
          ),
          const SizedBox(height: 4),
          Text(
            value,
            style: GoogleFonts.manrope(fontSize: 14, fontWeight: FontWeight.w600),
          ),
        ],
      ),
    );
  }

  Widget _dropdown({
    required String label,
    required String? value,
    required List<String> items,
    required List<String> labels,
    required ValueChanged<String?> onChanged,
  }) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 4),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(10),
        border: Border.all(color: Colors.grey.shade300),
      ),
      child: DropdownButtonHideUnderline(
        child: DropdownButton<String>(
          value: value,
          isExpanded: true,
          hint: Text(label, style: GoogleFonts.manrope(fontSize: 14, color: Colors.black54)),
          items: [
            for (int i = 0; i < items.length; i++)
              DropdownMenuItem(value: items[i], child: Text(labels[i], style: GoogleFonts.manrope(fontSize: 14))),
          ],
          onChanged: onChanged,
        ),
      ),
    );
  }

  Widget _glAccountDropdown(AppTranslations trans) {
    final list = _glAccountsForType;
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 4),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(10),
        border: Border.all(color: Colors.grey.shade300),
      ),
      child: DropdownButtonHideUnderline(
        child: DropdownButton<int>(
          value: _selectedGlAccountId,
          isExpanded: true,
          hint: Text(
            trans.language == 'sw' ? 'Chagua akaunti ya gharama (GL)' : 'Select expense account (GL)',
            style: GoogleFonts.manrope(fontSize: 14, color: Colors.black54),
          ),
          items: list.map((a) {
            final id = a['id'] is int ? a['id'] as int : int.tryParse(a['id'].toString());
            final code = a['account_code']?.toString() ?? '';
            final name = a['account_name']?.toString() ?? '';
            return DropdownMenuItem<int>(
              value: id,
              child: Text('$code - $name', style: GoogleFonts.manrope(fontSize: 13), overflow: TextOverflow.ellipsis),
            );
          }).toList(),
          onChanged: (v) => setState(() => _selectedGlAccountId = v),
        ),
      ),
    );
  }

  Widget _driverCardDisplay(AppTranslations trans) {
    final name = _driverCard!['name']?.toString() ?? '—';
    final acc = _driverCard!['account_number']?.toString();
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
      decoration: BoxDecoration(
        color: Colors.amber.shade50,
        borderRadius: BorderRadius.circular(10),
        border: Border.all(color: Colors.amber.shade200),
      ),
      child: Row(
        children: [
          Icon(Icons.credit_card, color: primary, size: 24),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  trans.language == 'sw' ? 'Lililipwa kwa kadi' : 'Pay from card',
                  style: GoogleFonts.manrope(fontSize: 10, color: Colors.black54),
                ),
                Text(
                  acc != null && acc.isNotEmpty ? '$name - $acc' : name,
                  style: GoogleFonts.manrope(fontSize: 14, fontWeight: FontWeight.w600),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _bankDropdown(AppTranslations trans) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 4),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(10),
        border: Border.all(color: Colors.grey.shade300),
      ),
      child: DropdownButtonHideUnderline(
        child: DropdownButton<int>(
          value: _selectedBankId,
          isExpanded: true,
          hint: Text(
            trans.language == 'sw' ? 'Lililipwa kutoka (benki)' : 'Paid from (bank)',
            style: GoogleFonts.manrope(fontSize: 14, color: Colors.black54),
          ),
          items: _bankAccounts.map((b) {
            final id = b['id'] is int ? b['id'] as int : int.tryParse(b['id'].toString());
            final name = b['name']?.toString() ?? '';
            final acc = b['account_number']?.toString();
            return DropdownMenuItem<int>(
              value: id,
              child: Text(acc != null && acc.isNotEmpty ? '$name - $acc' : name,
                  style: GoogleFonts.manrope(fontSize: 13), overflow: TextOverflow.ellipsis),
            );
          }).toList(),
          onChanged: (v) => setState(() => _selectedBankId = v),
        ),
      ),
    );
  }

  Widget _textField({
    required TextEditingController controller,
    required String label,
    TextInputType? keyboardType,
    bool required = false,
    int maxLines = 1,
    bool readOnly = false,
  }) {
    return TextField(
      controller: controller,
      readOnly: readOnly,
      decoration: InputDecoration(
        labelText: required ? '$label *' : label,
        border: OutlineInputBorder(borderRadius: BorderRadius.circular(10)),
        filled: true,
        fillColor: readOnly ? Colors.grey.shade100 : Colors.white,
        isDense: true,
        contentPadding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
      ),
      style: GoogleFonts.manrope(fontSize: 14),
      keyboardType: keyboardType,
      maxLines: maxLines,
    );
  }

  Widget _dateTimeChip({required IconData icon, required String label, required VoidCallback onTap}) {
    return Material(
      color: Colors.white,
      borderRadius: BorderRadius.circular(10),
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(10),
        child: Container(
          padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
          decoration: BoxDecoration(
            borderRadius: BorderRadius.circular(10),
            border: Border.all(color: Colors.grey.shade300),
          ),
          child: Row(
            children: [
              Icon(icon, size: 20, color: primary),
              const SizedBox(width: 10),
              Expanded(
                child: Text(
                  label,
                  style: GoogleFonts.manrope(fontSize: 14),
                  overflow: TextOverflow.ellipsis,
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Future<void> _submit(AppTranslations trans) async {
    final liters = double.tryParse(_litersController.text.trim());
    if (liters == null || liters <= 0) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(trans.language == 'sw' ? 'Ingiza lita' : 'Enter liters'), backgroundColor: Colors.orange),
      );
      return;
    }
    final tripId = widget.trip['id']?.toString();
    if (tripId == null) return;

    setState(() => _submitting = true);
    final totalCost = double.tryParse(_totalCostController.text.trim());
    final costPerLiter = double.tryParse(_costPerLiterController.text.trim());
    final result = await _tripsService.logFuel(
      tripId,
      litersFilled: liters,
      costPerLiter: costPerLiter,
      totalCost: totalCost,
      odometerReading: double.tryParse(_odometerController.text.trim()),
      previousOdometer: double.tryParse(_previousOdometerController.text.trim()),
      fuelStation: _fuelStationController.text.trim().isEmpty ? null : _fuelStationController.text.trim(),
      fuelType: _fuelType,
      glAccountId: _selectedGlAccountId,
      paidFromAccountId: _selectedBankId ?? _defaultBankId,
      fuelCardUsed: _fuelCardUsed,
      fuelCardNumber: _fuelCardNumberController.text.trim().isEmpty ? null : _fuelCardNumberController.text.trim(),
      fuelCardType: _fuelCardTypeController.text.trim().isEmpty ? null : _fuelCardTypeController.text.trim(),
      receiptNumber: _receiptNumberController.text.trim().isEmpty ? null : _receiptNumberController.text.trim(),
      dateFilled: '${_dateFilled.year}-${_dateFilled.month.toString().padLeft(2, '0')}-${_dateFilled.day.toString().padLeft(2, '0')}',
      timeFilled: '${_timeFilled.hour.toString().padLeft(2, '0')}:${_timeFilled.minute.toString().padLeft(2, '0')}',
      notes: _notesController.text.trim().isEmpty ? null : _notesController.text.trim(),
    );
    if (!mounted) return;
    setState(() => _submitting = false);
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(
          result['success'] == true
              ? (trans.language == 'sw' ? 'Mafuta yameandikwa.' : 'Fuel logged.')
              : (result['message']?.toString() ?? 'Error'),
        ),
        backgroundColor: result['success'] == true ? Colors.green : Colors.red,
      ),
    );
    if (result['success'] == true) Navigator.pop(context);
  }
}
