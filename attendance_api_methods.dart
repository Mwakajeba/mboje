// Add these methods to your ParentApiService class in main.dart

  static Future<Map<String, dynamic>?> getStudentAttendanceStats(int studentId, {String? startDate, String? endDate}) async {
    try {
      final token = await getToken();
      if (token == null) return null;

      String url = '$baseUrl/students/$studentId/attendance/stats';
      final uri = Uri.parse(url);
      final queryParams = <String, String>{};
      
      if (startDate != null) queryParams['start_date'] = startDate;
      if (endDate != null) queryParams['end_date'] = endDate;
      
      final finalUri = queryParams.isEmpty ? uri : uri.replace(queryParameters: queryParams);

      final response = await http.get(
        finalUri,
        headers: {
          'Authorization': 'Bearer $token',
          'Accept': 'application/json',
        },
      );

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        if (data['success'] == true) {
          return data['data'];
        }
      }
      return null;
    } catch (e) {
      print('Error fetching attendance stats: $e');
      return null;
    }
  }

  static Future<List<dynamic>?> getStudentAttendance(int studentId, {String? startDate, String? endDate, int? limit}) async {
    try {
      final token = await getToken();
      if (token == null) return null;

      String url = '$baseUrl/students/$studentId/attendance';
      final uri = Uri.parse(url);
      final queryParams = <String, String>{};
      
      if (startDate != null) queryParams['start_date'] = startDate;
      if (endDate != null) queryParams['end_date'] = endDate;
      if (limit != null) queryParams['limit'] = limit.toString();
      
      final finalUri = queryParams.isEmpty ? uri : uri.replace(queryParameters: queryParams);

      final response = await http.get(
        finalUri,
        headers: {
          'Authorization': 'Bearer $token',
          'Accept': 'application/json',
        },
      );

      if (response.statusCode == 200) {
        final data = jsonDecode(response.body);
        if (data['success'] == true) {
          return List<dynamic>.from(data['data']);
        }
      }
      return null;
    } catch (e) {
      print('Error fetching attendance: $e');
      return null;
    }
  }

