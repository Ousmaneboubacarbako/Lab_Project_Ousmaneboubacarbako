<?php
session_start();
require_once 'db_connection.php';
require_once 'session_check.php';

checkRole('student');

$student_id = $_SESSION['user_id'];

// This is a placeholder structure for feedback functionality
// In a real system, you would have a feedback table with columns like:
// id, student_id, course_id, faculty_id, feedback_text, rating, created_at

// For now, we'll show attendance notes as feedback
$feedback_query = "SELECT 
                   c.course_name, c.course_code,
                   s.session_title, s.session_date,
                   CONCAT(u.first_name, ' ', u.last_name) as instructor_name,
                   a.notes, a.marked_at
                   FROM attendance a
                   JOIN sessions s ON a.session_id = s.id
                   JOIN courses c ON s.course_id = c.id
                   LEFT JOIN users u ON c.instructor_id = u.id
                   WHERE a.student_id = $student_id 
                   AND a.notes IS NOT NULL 
                   AND a.notes != ''
                   ORDER BY s.session_date DESC";
$feedback_result = mysqli_query($conn, $feedback_query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Faculty Feedback</title>
    <link rel="stylesheet" href="../css/unified_dashboard_css.css">
</head>
<body>
    <header>
        <h1>Faculty Feedback</h1>
        <nav>
            <ul>
                <li><a href="/Lab%203%20assignment%20(3)/Lab_assignment_4/html/StuDashboard.html">Back to Dashboard</a></li>
            </ul>
        </nav>
    </header>

    <main>
        <section>
            <div class="content-box" style="max-width: 900px; margin: 0 auto;">
                <div class="card">
                    <h3>Your Feedback & Comments</h3>
                    <p style="color: #666;">View feedback and comments from your instructors about your attendance and performance.</p>
                </div>

                <?php if (mysqli_num_rows($feedback_result) > 0): ?>
                    <?php while($feedback = mysqli_fetch_assoc($feedback_result)): ?>
                    <div class="card mt-20">
                        <div style="border-left: 4px solid #667eea; padding-left: 15px;">
                            <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 10px;">
                                <div>
                                    <h4 style="margin: 0; color: #333;">
                                        <?php echo htmlspecialchars($feedback['course_name']); ?>
                                    </h4>
                                    <p style="margin: 5px 0; color: #666; font-size: 14px;">
                                        <strong>Session:</strong> <?php echo htmlspecialchars($feedback['session_title']); ?> | 
                                        <strong>Date:</strong> <?php echo date('M d, Y', strtotime($feedback['session_date'])); ?>
                                    </p>
                                    <p style="margin: 5px 0; color: #666; font-size: 14px;">
                                        <strong>Instructor:</strong> <?php echo htmlspecialchars($feedback['instructor_name']); ?>
                                    </p>
                                </div>
                                <span style="font-size: 12px; color: #999;">
                                    <?php echo date('M d, Y', strtotime($feedback['marked_at'])); ?>
                                </span>
                            </div>
                            <div style="background: #f8f9fa; padding: 12px; border-radius: 6px; margin-top: 10px;">
                                <p style="margin: 0; color: #555;">
                                    <?php echo htmlspecialchars($feedback['notes']); ?>
                                </p>
                            </div>
                        </div>
                    </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="card mt-20">
                        <div class="alert alert-info">
                            <strong>No Feedback Yet</strong><br>
                            You don't have any feedback from your instructors at this time. Feedback will appear here when your instructors add comments to your attendance records or provide performance evaluations.
                        </div>
                        
                        <div style="margin-top: 20px; padding: 15px; background: #f8f9fa; border-radius: 8px;">
                            <h4 style="margin-top: 0;">What to Expect:</h4>
                            <ul style="color: #666;">
                                <li>Instructors can add comments when marking your attendance</li>
                                <li>You'll receive feedback about your participation and performance</li>
                                <li>All feedback is meant to help you improve and succeed</li>
                                <li>Check back regularly for new updates</li>
                            </ul>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </main>
</body>
</html>