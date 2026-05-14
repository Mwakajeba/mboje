import 'package:flutter/material.dart';
import 'package:fl_chart/fl_chart.dart';
import 'package:google_fonts/google_fonts.dart';

class AcademicDashboard extends StatelessWidget {
  const AcademicDashboard({Key? key}) : super(key: key);

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xff0d1117),

      appBar: AppBar(
        backgroundColor: Colors.black,
        title: const Text("Academic Dashboard"),
        elevation: 0,
      ),

      body: SingleChildScrollView(
        padding: const EdgeInsets.all(15),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [

            // ================= TOP ROW =================
            Row(
              children: [
                Expanded(child: gradeCard()),
                const SizedBox(width: 12),
                Expanded(child: statCard("Total Average", "80%", Colors.blueAccent))
              ],
            ),
            const SizedBox(height: 12),

            Row(
              children: [
                Expanded(child: statCard("Class Ranking", "2/50", Colors.cyanAccent)),
                const SizedBox(width: 12),
                Expanded(child: statCard("Stream Ranking", "1/25", Colors.greenAccent, sub: "Improving")),
              ],
            ),
            const SizedBox(height: 18),

            // =============== MIDDLE CARDS =================
            Row(
              children: [
                Expanded(child: attendanceCard()),
                const SizedBox(width: 12),
                Expanded(child: behaviourCard())
              ],
            ),
            const SizedBox(height: 15),
            feeBalanceCard(),
            const SizedBox(height: 20),

            // =============== SUBJECT PERFORMANCE =================
            Text("Subject-Wise Performance",
                style: GoogleFonts.poppins(fontSize: 18, color: Colors.white, fontWeight: FontWeight.w600)),
            const SizedBox(height: 14),

            subjectTile("Mathematics", 0.85, "A", Colors.lightBlueAccent),
            subjectTile("English", 0.78, "B+", Colors.blue),
            subjectTile("Science", 0.90, "A", Colors.purpleAccent),
            subjectTile("Kiswahili", 0.72, "C+", Colors.orangeAccent),
            subjectTile("History", 0.70, "C+", Colors.deepPurpleAccent),

            const SizedBox(height: 30),

            // ================== LINE PERFORMANCE GRAPH =================
            SizedBox(
              height: 200,
              child: LineChart(LineChartData(
                minY: 60,
                maxY: 90,
                backgroundColor: Colors.transparent,
                borderData: FlBorderData(show: false),
                gridData: FlGridData(show: false),
                titlesData: FlTitlesData(
                  leftTitles: AxisTitles(sideTitles: SideTitles(showTitles: false)),
                  topTitles: AxisTitles(sideTitles: SideTitles(showTitles: false)),
                  rightTitles: AxisTitles(sideTitles: SideTitles(showTitles: false)),
                  bottomTitles: AxisTitles(
                    sideTitles: SideTitles(showTitles: true,getTitlesWidget: (value, meta) {
                      switch (value.toInt()) {
                        case 1: return text("Term 1");
                        case 2: return text("Term 2");
                        case 3: return text("Term 3");
                        case 4: return text("Midterm");
                        case 5: return text("Final");
                      }
                      return Container();
                    })
                  )
                ),
                lineBarsData: [
                  LineChartBarData(
                    isCurved: true,
                    barWidth: 4,
                    dotData: FlDotData(show: true),
                    color: Colors.blueAccent,
                    spots: const [
                      FlSpot(1, 72),
                      FlSpot(2, 75),
                      FlSpot(3, 78),
                      FlSpot(4, 83),
                      FlSpot(5, 88),
                    ],
                  )
                ],
              )),
            )
          ],
        ),
      ),
    );
  }

  // ================ UI COMPONENTS BELOW ===================

  Widget text(String t)=> Text(t,style:const TextStyle(color: Colors.white,fontSize: 11));

  Widget gradeCard(){
    return Container(
      padding: const EdgeInsets.all(20),
      decoration: box(),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.center,
        children: [
          CircularProgressIndicator(
            value: .82,
            strokeWidth: 7,
            backgroundColor: Colors.grey,
            color: Colors.blue,
          ),
          const SizedBox(height: 12),
          Text("A", style: GoogleFonts.poppins(
              color: Colors.white,fontSize: 36,fontWeight: FontWeight.bold))
        ],
      ),
    );
  }

  Widget statCard(String title,String value,Color color,{String? sub}){
    return Container(
      padding: const EdgeInsets.all(20),
      decoration: box(),
      child: Column(
        children: [
          Text(title,style: textStyle()),
          const SizedBox(height: 10),
          Text(value,style: GoogleFonts.poppins(fontSize:28,color:Colors.white,fontWeight:FontWeight.bold)),
          if(sub!=null) Text(sub,style:const TextStyle(color:Colors.green,fontSize:13)),
        ],
      ),
    );
  }

  Widget attendanceCard(){
    return Container(
      padding:const EdgeInsets.all(20),
      decoration: box(),
      child: Row(
        children: [
          Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text("94%",style:cardBig()),
              text("Attendance"),
              const SizedBox(height:5),
              text("5 Absent Days"),
              text("2 Late Arrivals"),
            ],
          )
        ],
      ),
    );
  }

  Widget behaviourCard(){
    return Container(
      padding: const EdgeInsets.all(20),
      decoration: box(),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text("Behaviour Score",style:textStyle()),
          const SizedBox(height:8),
          Text("4.2",style:cardBig()),
          const SizedBox(height:5),
          LinearProgressIndicator(color:Colors.blueAccent, value:.8),
          const SizedBox(height:6),
          text("Rewards: 3")
        ],
      ),
    );
  }

  Widget feeBalanceCard(){
    return Container(
      padding:const EdgeInsets.all(20),
      decoration: box(),
      child: Column(
        crossAxisAlignment:CrossAxisAlignment.start,
        children: [
          Text("Fee Balance",style:textStyle()),
          const SizedBox(height:10),
          Text("Tsh 150,000",style:cardBig().copyWith(color:Colors.redAccent)),
          text("Outstanding")
        ],
      ),
    );
  }

  Widget subjectTile(String subject,double percent,String grade,Color color){
    return Padding(
      padding: const EdgeInsets.only(bottom:10),
      child: Row(
        children: [
          Expanded(
            child: Column(
              crossAxisAlignment:CrossAxisAlignment.start,
              children:[
                Text(subject,style:textStyle()),
                const SizedBox(height:5),
                ClipRRect(
                  borderRadius: BorderRadius.circular(8),
                  child: LinearProgressIndicator(value:percent,color:color,backgroundColor:Colors.white12),
                )
              ],
            ),
          ),
          const SizedBox(width:10),
          Text(grade,style:const TextStyle(color:Colors.white,fontWeight:FontWeight.bold,fontSize:18)),
        ],
      ),
    );
  }

  // ============ STYLE SHORTCUTS ==============
  BoxDecoration box()=>BoxDecoration(
      color: const Color(0xff161b22),
      borderRadius: BorderRadius.circular(14));

  TextStyle textStyle()=>const TextStyle(color:Colors.white,fontSize:14);
  TextStyle cardBig()=>const TextStyle(color:Colors.white,fontSize:28,fontWeight:FontWeight.bold);
}

