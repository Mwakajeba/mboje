class ApiConfig {
  // For local development:
  // - Android Emulator: Use 'http://10.0.2.2:8000/api'
  // - Edge/Chrome (Web/Desktop) on Windows: Use 'http://127.0.0.1:8000/api'
  // - iOS Simulator: Use 'http://localhost:8000/api' or 'http://127.0.0.1:8000/api'
  // - Physical Device: Use your computer's local IP (e.g., 'http://192.168.1.100:8000/api')
  //   Find your IP: Windows (ipconfig) or Mac/Linux (ifconfig)
  
  // Default to Edge/Web on Windows
  static const String baseUrl = 'http://127.0.0.1:8000/api';
  
  // Alternative URLs (uncomment the one you need):
  // static const String baseUrl = 'http://10.0.2.2:8000/api';   // Android Emulator
  // static const String baseUrl = 'http://localhost:8000/api';  // iOS Simulator
  // static const String baseUrl = 'http://192.168.1.XXX:8000/api';  // Physical device (replace XXX with your IP)
  
  // API Endpoints - Auth
  static const String login = '/hr/login';
  static const String logout = '/hr/logout';
  static const String me = '/hr/me';
  static const String updateProfile = '/hr/profile';
  static const String changePassword = '/hr/change-password';
  static const String forgotPassword = '/hr/forgot-password';
  static const String verifyOtp = '/hr/verify-otp';
  static const String resetPassword = '/hr/reset-password';
  
  // API Endpoints - Dashboard
  static const String dashboard = '/hr/dashboard';
  
  // API Endpoints - Leave
  static const String leaveBalances = '/hr/leave/balances';
  static const String leaveTypes = '/hr/leave/types';
  static const String leaveRequests = '/hr/leave/requests';
  static const String applyLeave = '/hr/leave/apply';
  
  // API Endpoints - Attendance & Payslips
  static const String attendance = '/hr/attendance';
  static const String payslips = '/hr/payslips';
  
  // API Endpoints - Imprest Management
  static const String imprestList = '/hr/imprest';
  static const String imprestCreate = '/hr/imprest';
  static const String expenseAccounts = '/hr/expense-accounts';
  static const String departments = '/hr/departments';
  
  // API Endpoints - Retirement Management
  static const String retirementList = '/hr/retirement';
  static const String retirementCreate = '/hr/retirement';
  static const String eligibleImprestForRetirement = '/hr/retirement/eligible-imprest';
  
  // API Endpoints - Store Requisition
  static const String storeRequisitions = '/hr/store-requisitions';
  static const String inventoryItems = '/hr/inventory-items';
  
  // API Endpoints - Manager Approvals
  static const String pendingApprovals = '/hr/approvals/pending';
  static String approveImprest(int approvalId) => '/hr/approvals/imprest/$approvalId/approve';
  static String rejectImprest(int approvalId) => '/hr/approvals/imprest/$approvalId/reject';
  static String approveRequisition(int id) => '/hr/approvals/requisition/$id/approve';
  static String rejectRequisition(int id) => '/hr/approvals/requisition/$id/reject';
  
  // API Endpoints - Notifications
  static const String notifications = '/hr/notifications';
  static const String unreadNotificationsCount = '/hr/notifications/unread-count';
  static String markNotificationRead(int id) => '/hr/notifications/$id/read';
  static const String markAllNotificationsRead = '/hr/notifications/read-all';
  
  // Helper method to get full URL
  static String getUrl(String endpoint) {
    return baseUrl + endpoint;
  }
  
  // Helper for imprest details
  static String imprestDetails(int id) => '/hr/imprest/$id';
  
  // Helper for store requisition details
  static String storeRequisitionDetails(int id) => '/hr/store-requisitions/$id';
  
  // Helper for retirement details
  static String retirementDetails(int id) => '/hr/retirement/$id';
}

