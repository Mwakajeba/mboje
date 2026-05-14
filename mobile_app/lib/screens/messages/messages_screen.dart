import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import 'package:shared_preferences/shared_preferences.dart';
import '../../services/parent_api_service.dart';
import '../../providers/language_provider.dart';
import '../../providers/theme_provider.dart';
import 'package:intl/intl.dart';

class MessagesPage extends StatefulWidget {
  const MessagesPage({super.key});

  @override
  State<MessagesPage> createState() => _MessagesPageState();
}

class _MessagesPageState extends State<MessagesPage> {
  bool isLoading = true;
  bool isLoadingMore = false;
  List<Map<String, dynamic>> notifications = [];
  int unreadCount = 0;
  int currentPage = 1;
  bool hasMore = true;
  String selectedFilter = 'all'; // all, unread, read
  int? selectedStudentId;

  @override
  void initState() {
    super.initState();
    _loadStudentId();
  }

  Future<void> _loadStudentId() async {
    final prefs = await SharedPreferences.getInstance();
    final id = prefs.getInt('selected_student_id');
    setState(() {
      selectedStudentId = id;
    });
    _loadNotifications();
    _loadUnreadCount();
  }

  Future<void> _loadNotifications({bool loadMore = false}) async {
    if (loadMore) {
      setState(() {
        isLoadingMore = true;
      });
    } else {
      setState(() {
        isLoading = true;
        currentPage = 1;
        hasMore = true;
      });
    }

    try {
      final data = await ParentApiService.getNotifications(
        studentId: selectedStudentId,
        page: currentPage,
        perPage: 20,
      );

      if (mounted && data != null) {
        final newNotifications = List<Map<String, dynamic>>.from(data['data'] ?? []);
        
        setState(() {
          if (loadMore) {
            notifications.addAll(newNotifications);
          } else {
            notifications = newNotifications;
          }
          
          hasMore = newNotifications.length == 20 && data['next_page_url'] != null;
          currentPage++;
          isLoading = false;
          isLoadingMore = false;
        });
      } else {
        setState(() {
          isLoading = false;
          isLoadingMore = false;
        });
      }
    } catch (e) {
      print('Error loading notifications: $e');
      if (mounted) {
        setState(() {
          isLoading = false;
          isLoadingMore = false;
        });
      }
    }
  }

  Future<void> _loadUnreadCount() async {
    try {
      final count = await ParentApiService.getUnreadNotificationsCount(
        studentId: selectedStudentId,
      );
      if (mounted) {
        setState(() {
          unreadCount = count ?? 0;
        });
      }
    } catch (e) {
      print('Error loading unread count: $e');
    }
  }

  Future<void> _markAsRead(int notificationId) async {
    final success = await ParentApiService.markNotificationAsRead(notificationId);
    if (success) {
      setState(() {
        final index = notifications.indexWhere((n) => n['id'] == notificationId);
        if (index != -1) {
          notifications[index]['is_read'] = true;
          notifications[index]['read_at'] = DateTime.now().toIso8601String();
          if (unreadCount > 0) unreadCount--;
        }
      });
    }
  }

  Future<void> _markAllAsRead() async {
    final success = await ParentApiService.markAllNotificationsAsRead(
      studentId: selectedStudentId,
    );
    if (success) {
      setState(() {
        for (var notification in notifications) {
          notification['is_read'] = true;
          notification['read_at'] = DateTime.now().toIso8601String();
        }
        unreadCount = 0;
      });
    }
  }

  String _getNotificationIcon(String type) {
    switch (type) {
      case 'invoice_created':
        return '💰';
      case 'exam_published':
        return '📝';
      case 'assignment_published':
        return '📋';
      case 'student_absent':
        return '❌';
      default:
        return '🔔';
    }
  }

  Color _getNotificationColor(String type) {
    switch (type) {
      case 'invoice_created':
        return Colors.orange;
      case 'exam_published':
        return Colors.blue;
      case 'assignment_published':
        return Colors.purple;
      case 'student_absent':
        return Colors.red;
      default:
        return Colors.grey;
    }
  }

  String _formatDate(String? dateString) {
    if (dateString == null) return '';
    try {
      final date = DateTime.parse(dateString);
      final now = DateTime.now();
      final difference = now.difference(date);

      if (difference.inDays == 0) {
        if (difference.inHours == 0) {
          if (difference.inMinutes == 0) {
            return 'Sasa hivi';
          }
          return 'Dakika ${difference.inMinutes} zilizopita';
        }
        return 'Saa ${difference.inHours} zilizopita';
      } else if (difference.inDays == 1) {
        return 'Jana';
      } else if (difference.inDays < 7) {
        return 'Siku ${difference.inDays} zilizopita';
      } else {
        return DateFormat('dd/MM/yyyy').format(date);
      }
    } catch (e) {
      return dateString;
    }
  }

  List<Map<String, dynamic>> get _filteredNotifications {
    switch (selectedFilter) {
      case 'unread':
        return notifications.where((n) => n['is_read'] == false).toList();
      case 'read':
        return notifications.where((n) => n['is_read'] == true).toList();
      default:
        return notifications;
    }
  }

  @override
  Widget build(BuildContext context) {
    final languageProvider = Provider.of<LanguageProvider>(context);
    final themeProvider = Provider.of<ThemeProvider>(context);
    final trans = AppTranslations(languageProvider.currentLanguage);
    final isDark = themeProvider.isDarkMode;

    return Scaffold(
      backgroundColor: isDark ? const Color(0xFF101115) : Colors.grey.shade50,
      appBar: AppBar(
        title: Text(
          trans.get('messages'),
          style: const TextStyle(
            fontSize: 22,
            fontWeight: FontWeight.bold,
          ),
        ),
        backgroundColor: isDark ? const Color(0xFF16181F) : Colors.blue.shade700,
        foregroundColor: Colors.white,
        elevation: 0,
        actions: [
          if (unreadCount > 0)
            IconButton(
              icon: Stack(
                children: [
                  const Icon(Icons.check_circle_outline),
                  Positioned(
                    right: 0,
                    top: 0,
                    child: Container(
                      padding: const EdgeInsets.all(4),
                      decoration: const BoxDecoration(
                        color: Colors.red,
                        shape: BoxShape.circle,
                      ),
                      constraints: const BoxConstraints(
                        minWidth: 16,
                        minHeight: 16,
                      ),
                      child: Text(
                        unreadCount > 9 ? '9+' : '$unreadCount',
                        style: const TextStyle(
                          color: Colors.white,
                          fontSize: 10,
                          fontWeight: FontWeight.bold,
                        ),
                        textAlign: TextAlign.center,
                      ),
                    ),
                  ),
                ],
              ),
              onPressed: _markAllAsRead,
              tooltip: 'Mark all as read',
            ),
          IconButton(
            icon: const Icon(Icons.refresh),
            onPressed: () {
              _loadNotifications();
              _loadUnreadCount();
            },
          ),
        ],
      ),
      body: Column(
        children: [
          // Filter Tabs
          Container(
            color: isDark ? const Color(0xFF16181F) : Colors.white,
            child: Row(
              children: [
                Expanded(
                  child: _buildFilterTab('all', trans.get('all'), isDark),
                ),
                Expanded(
                  child: _buildFilterTab('unread', trans.get('pending'), isDark),
                ),
                Expanded(
                  child: _buildFilterTab('read', trans.get('submitted'), isDark),
                ),
              ],
            ),
          ),
          
          // Notifications List
          Expanded(
            child: isLoading
                ? Center(
                    child: CircularProgressIndicator(
                      color: isDark ? Colors.blue.shade400 : Colors.blue.shade700,
                    ),
                  )
                : _filteredNotifications.isEmpty
                    ? Center(
                        child: Column(
                          mainAxisAlignment: MainAxisAlignment.center,
                          children: [
                            Icon(
                              Icons.notifications_none,
                              size: 64,
                              color: isDark ? Colors.grey.shade600 : Colors.grey.shade400,
                            ),
                            const SizedBox(height: 16),
                            Text(
                              selectedFilter == 'unread'
                                  ? 'Hakuna arifa zisizosomwa'
                                  : selectedFilter == 'read'
                                      ? 'Hakuna arifa zilizosomwa'
                                      : 'Hakuna arifa',
                              style: TextStyle(
                                fontSize: 16,
                                color: isDark ? Colors.grey.shade400 : Colors.grey.shade600,
                              ),
                            ),
                          ],
                        ),
                      )
                    : RefreshIndicator(
                        onRefresh: () async {
                          await _loadNotifications();
                          await _loadUnreadCount();
                        },
                        child: ListView.builder(
                          itemCount: _filteredNotifications.length + (hasMore ? 1 : 0),
                          itemBuilder: (context, index) {
                            if (index == _filteredNotifications.length) {
                              if (isLoadingMore) {
                                return const Center(
                                  child: Padding(
                                    padding: EdgeInsets.all(16.0),
                                    child: CircularProgressIndicator(),
                                  ),
                                );
                              }
                              return Center(
                                child: TextButton(
                                  onPressed: () => _loadNotifications(loadMore: true),
                                  child: Text('Load More'),
                                ),
                              );
                            }

                            final notification = _filteredNotifications[index];
                            return _buildNotificationCard(notification, isDark, trans);
                          },
                        ),
                      ),
          ),
        ],
      ),
    );
  }

  Widget _buildFilterTab(String filter, String label, bool isDark) {
    final isSelected = selectedFilter == filter;
    return InkWell(
      onTap: () {
        setState(() {
          selectedFilter = filter;
        });
      },
      child: Container(
        padding: const EdgeInsets.symmetric(vertical: 12),
        decoration: BoxDecoration(
          border: Border(
            bottom: BorderSide(
              color: isSelected
                  ? (isDark ? Colors.blue.shade400 : Colors.blue.shade700)
                  : Colors.transparent,
              width: 2,
            ),
          ),
        ),
        child: Text(
          label,
          textAlign: TextAlign.center,
          style: TextStyle(
            color: isSelected
                ? (isDark ? Colors.blue.shade400 : Colors.blue.shade700)
                : (isDark ? Colors.grey.shade400 : Colors.grey.shade600),
            fontWeight: isSelected ? FontWeight.bold : FontWeight.normal,
          ),
        ),
      ),
    );
  }

  Widget _buildNotificationCard(Map<String, dynamic> notification, bool isDark, AppTranslations trans) {
    final type = notification['type'] ?? '';
    final isRead = notification['is_read'] == true;
    final color = _getNotificationColor(type);
    
    return Container(
      margin: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
      decoration: BoxDecoration(
        color: isDark ? const Color(0xFF16181F) : Colors.white,
        borderRadius: BorderRadius.circular(12),
        border: Border.all(
          color: isRead
              ? Colors.transparent
              : (isDark ? color.withOpacity(0.5) : color.withOpacity(0.3)),
          width: isRead ? 0 : 2,
        ),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withOpacity(isDark ? 0.3 : 0.05),
            blurRadius: 10,
            offset: const Offset(0, 2),
          ),
        ],
      ),
      child: InkWell(
        onTap: () {
          if (!isRead) {
            _markAsRead(notification['id']);
          }
          // TODO: Navigate to relevant screen based on notification type
        },
        borderRadius: BorderRadius.circular(12),
        child: Padding(
          padding: const EdgeInsets.all(16),
          child: Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Container(
                width: 48,
                height: 48,
                decoration: BoxDecoration(
                  color: color.withOpacity(0.1),
                  borderRadius: BorderRadius.circular(12),
                ),
                child: Center(
                  child: Text(
                    _getNotificationIcon(type),
                    style: const TextStyle(fontSize: 24),
                  ),
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      children: [
                        Expanded(
                          child: Text(
                            notification['title'] ?? '',
                            style: TextStyle(
                              fontSize: 16,
                              fontWeight: isRead ? FontWeight.w500 : FontWeight.bold,
                              color: isDark ? const Color(0xFFE4E5E6) : Colors.black87,
                            ),
                          ),
                        ),
                        if (!isRead)
                          Container(
                            width: 8,
                            height: 8,
                            decoration: const BoxDecoration(
                              color: Colors.blue,
                              shape: BoxShape.circle,
                            ),
                          ),
                      ],
                    ),
                    const SizedBox(height: 4),
                    Text(
                      notification['message'] ?? '',
                      style: TextStyle(
                        fontSize: 14,
                        color: isDark ? Colors.grey.shade400 : Colors.grey.shade700,
                      ),
                      maxLines: 2,
                      overflow: TextOverflow.ellipsis,
                    ),
                    const SizedBox(height: 8),
                    Row(
                      children: [
                        if (notification['student'] != null)
                          Container(
                            padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                            decoration: BoxDecoration(
                              color: color.withOpacity(0.1),
                              borderRadius: BorderRadius.circular(8),
                            ),
                            child: Text(
                              notification['student']['name'] ?? '',
                              style: TextStyle(
                                fontSize: 12,
                                color: color,
                                fontWeight: FontWeight.w500,
                              ),
                            ),
                          ),
                        const Spacer(),
                        Text(
                          _formatDate(notification['created_at']),
                          style: TextStyle(
                            fontSize: 12,
                            color: isDark ? Colors.grey.shade500 : Colors.grey.shade600,
                          ),
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
    );
  }
}
