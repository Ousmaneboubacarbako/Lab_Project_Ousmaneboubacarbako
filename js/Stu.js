// Student Dashboard JavaScript
document.addEventListener('DOMContentLoaded', async function() {
    await loadUserInfo();
    await loadCourses();
    await loadUpcomingSessions();
    await loadAttendanceStats();
    await loadCourseAttendance();
    await loadFeedback();
});

// Load user information
async function loadUserInfo() {
    try {
        const response = await fetch('../php/api_endpoints.php?action=get_user_info');
        const data = await response.json();
        
        if (data.success) {
            document.querySelector('h1').textContent = `Welcome, ${data.data.first_name}!`;
        }
    } catch (error) {
        console.error('Error loading user info:', error);
    }
}

// Load enrolled courses
async function loadCourses() {
    try {
        const response = await fetch('../php/api_endpoints.php?action=get_student_courses');
        const data = await response.json();
        
        if (data.success) {
            const courseList = document.getElementById('course-list');
            courseList.innerHTML = '';
            
            if (data.data.length === 0) {
                courseList.innerHTML = '<div class="alert alert-info">You are not enrolled in any courses yet.</div>';
                return;
            }
            
            data.data.forEach(course => {
                const courseCard = `
                    <div class="card">
                        <h3>${course.course_name}</h3>
                        <p>
                            <strong>Code:</strong> ${course.course_code} | 
                            <strong>Instructor:</strong> ${course.instructor_name || 'TBA'} | 
                            <strong>Credits:</strong> ${course.credits}
                        </p>
                        <p style="color: #666; font-size: 14px;">${course.description || ''}</p>
                    </div>
                `;
                courseList.innerHTML += courseCard;
            });
        }
    } catch (error) {
        console.error('Error loading courses:', error);
    }
}

// Load upcoming sessions
async function loadUpcomingSessions() {
    try {
        const response = await fetch('../php/api_endpoints.php?action=get_upcoming_sessions');
        const data = await response.json();
        
        if (data.success) {
            const scheduleData = document.getElementById('schedule-data');
            scheduleData.innerHTML = '';
            
            if (data.data.length === 0) {
                scheduleData.innerHTML = '<div class="alert alert-info">No upcoming sessions scheduled.</div>';
                return;
            }
            
            let tableHTML = `
                <table>
                    <thead>
                        <tr>
                            <th>Course</th>
                            <th>Session Title</th>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Location</th>
                        </tr>
                    </thead>
                    <tbody>
            `;
            
            data.data.forEach(session => {
                const date = new Date(session.session_date);
                const startTime = formatTime(session.start_time);
                const endTime = formatTime(session.end_time);
                
                tableHTML += `
                    <tr>
                        <td>${session.course_code}</td>
                        <td>${session.session_title}</td>
                        <td>${date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })}</td>
                        <td>${startTime} - ${endTime}</td>
                        <td>${session.location || 'TBA'}</td>
                    </tr>
                `;
            });
            
            tableHTML += '</tbody></table>';
            scheduleData.innerHTML = tableHTML;
        }
    } catch (error) {
        console.error('Error loading sessions:', error);
    }
}

// Load attendance statistics
async function loadMyAttendance() {
    try {
        const response = await fetch('../php/api_endpoints.php?action=get_student_attendance');
        const data = await response.json();
        
        if (data.success) {
            const stats = data.data;
            const statsCards = document.querySelectorAll('.stat-card h3');
            
            if (statsCards.length >= 4) {
                statsCards[2].textContent = stats.present_count || 0;
                statsCards[1].textContent = `${stats.attendance_rate || 0}%`;
            }
        }
    } catch (error) {
        console.error('Error loading attendance stats:', error);
    }
}

// Load course-wise attendance
async function loadCourseAttendance() {
    try {
        const response = await fetch('../php/api_endpoints.php?action=get_course_attendance');
        const data = await response.json();
        
        if (data.success) {
            const reportData = document.getElementById('report-data');
            reportData.innerHTML = '';
            
            if (data.data.length === 0) {
                reportData.innerHTML = '<div class="alert alert-info">No attendance records available yet.</div>';
                return;
            }
            
            let tableHTML = `
                <table>
                    <thead>
                        <tr>
                            <th>Course</th>
                            <th>Total Sessions</th>
                            <th>Present</th>
                            <th>Absent</th>
                            <th>Late</th>
                            <th>Attendance Rate</th>
                        </tr>
                    </thead>
                    <tbody>
            `;
            
            data.data.forEach(record => {
                const rate = record.attendance_rate || 0;
                const color = rate >= 75 ? 'green' : 'red';
                
                tableHTML += `
                    <tr>
                        <td>${record.course_code} - ${record.course_name}</td>
                        <td>${record.total_sessions}</td>
                        <td>${record.present_count}</td>
                        <td>${record.absent_count}</td>
                        <td>${record.late_count}</td>
                        <td><span style="color: ${color};">${rate}%</span></td>
                    </tr>
                `;
            });
            
            tableHTML += '</tbody></table>';
            reportData.innerHTML = tableHTML;
        }
    } catch (error) {
        console.error('Error loading course attendance:', error);
    }
}

// Load feedback
async function loadFeedback() {
    try {
        const response = await fetch('../php/api_endpoints.php?action=get_student_feedback');
        const data = await response.json();
        
        if (data.success) {
            const feedbackData = document.getElementById('feedback-data');
            
            if (data.data.length === 0) {
                feedbackData.innerHTML = `
                    <div class="alert alert-info">
                        <strong>No Feedback Yet</strong><br>
                        Faculty feedback will appear here once instructors provide comments.
                    </div>
                `;
            } else {
                feedbackData.innerHTML = `<p>You have ${data.data.length} feedback comments. Click below to view all.</p>`;
            }
        }
    } catch (error) {
        console.error('Error loading feedback:', error);
    }
}

// Helper function to format time
function formatTime(timeString) {
    const [hours, minutes] = timeString.split(':');
    const hour = parseInt(hours);
    const ampm = hour >= 12 ? 'PM' : 'AM';
    const displayHour = hour % 12 || 12;
    return `${displayHour}:${minutes} ${ampm}`;
}