// Add this method to your ParentApiService class in main.dart

  static Future<Map<String, dynamic>?> getStudentAssignments(int studentId) async {
    try {
      final token = await getToken();
      if (token == null) return null;

      final url = '$baseUrl/students/$studentId/assignments';
      final response = await http.get(
        Uri.parse(url),
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
      print('Error fetching assignments: $e');
      return null;
    }
  }

