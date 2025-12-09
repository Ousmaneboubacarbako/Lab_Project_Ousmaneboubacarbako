// Faculty Dashboard JavaScript
document.addEventListener('DOMContentLoaded', async function() {
    await loadUserInfo();
    await loadCourses();
    await loadUpcomingSessions();
    await loadAttendanceOverview();
});

// Load user information
async function loadUserInfo() {
    try {
        const response = await fetch('../php/api_endpoints.php?action=get_user_info');
        const data = await response.json();
        
        if (data.success) {
            document.querySelector('h1').textContent = `Welcome, Dr. ${data.data.last_name}!`;
        }
    } catch (error) {
        console.error('Error loading user info:', error);
    }
}

// Load faculty courses
async function loadCourses() {
    try {
        const response = await fetch('../php/api_endpoints.php?action=get_faculty_courses');
        const data = await response.json();
        
        if (data.success) {
            const courseList = document.getElementById('course-list');
            courseList.innerHTML = '';
            
            if (data.data.length === 0) {
                courseList.innerHTML = '<div class="alert alert-info">No courses assigned yet.</div>';
                return;
            }
            
            data.data.forEach(course => {
                const courseCard = `
                    <div class="card">
                        <div class="flex justify-between align-center">
                            <div>
                                <h3>${course.course_name}</h3>
                                <p>Course Code: ${course.course_code} | 
                                   Students: ${course.student_count} | 
                                   Sessions: ${course.session_count}</p>
                            </div>
                            <div>
                                <a href="/Lab%203%20assignment%20(3)/Lab_assignment_4/html/MarkAttend.html?course_id=${course.id}">
                                    <button style="padding: 8px 16px; margin: 0 5px;">Create Session</button>
                                </a>
                                <a href="/Lab%203%20assignment%20(3)/Lab_assignment_4/html/ViewAttendance.html?course_id=${course.id}">
                                    <button class="secondary" style="padding: 8px 16px; margin: 0 5px;">View Attendance</button>
                                </a>
                            </div>
                        </div>
                    </div>
                `;
                courseList.innerHTML += courseCard;
            });
            
            // Update stats
            const statsCards = document.querySelectorAll('.stat-card h3');
            if (statsCards.length >= 1) {
                statsCards[0].textContent = data.data.length;
            }
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
            const sessionData = document.getElementById('session-data');
            sessionData.innerHTML = '';
            
            if (data.data.length === 0) {
                sessionData.innerHTML = '<div class="alert alert-info">No upcoming sessions scheduled.</div>';
                
                // Update stat
                const statsCards = document.querySelectorAll('.stat-card h3');
                if (statsCards.length >= 2) {
                    statsCards[1].textContent = '0';
                }
                return;
            }
            
            // Update stat
            const statsCards = document.querySelectorAll('.stat-card h3');
            if (statsCards.length >= 2) {
                statsCards[1].textContent = data.data.length;
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
                            <th>Actions</th>
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
                        <td>
                            <a href="/Lab%203%20assignment%20(3)/Lab_assignment_4/html/MarkAttend.html?session_id=${session.id}">
                                <button style="padding: 6px 12px;">Mark Attendance</button>
                            </a>
                        </td>
                    </tr>
                `;
            });
            
            tableHTML += '</tbody></table>';
            sessionData.innerHTML = tableHTML;
        }
    } catch (error) {
        console.error('Error loading sessions:', error);
    }
}

// Load attendance overview
async function loadAttendanceOverview() {
    try {
        const response = await fetch('../php/api_endpoints.php?action=get_faculty_attendance_overview');
        const data = await response.json();
        
        if (data.success) {
            const reportData = document.getElementById('report-data');
            reportData.innerHTML = '';
            
            if (data.data.length === 0) {
                reportData.innerHTML = '<div class="alert alert-info">No attendance records yet.</div>';
                return;
            }
            
            let tableHTML = `
                <table>
                    <thead>
                        <tr>
                            <th>Course</th>
                            <th>Total Students</th>
                            <th>Total Records</th>
                            <th>Present Count</th>
                            <th>Attendance Rate</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
            `;
            
            data.data.forEach(record => {
                tableHTML += `
                    <tr>
                        <td>${record.course_name}</td>
                        <td>${record.total_students}</td>
                        <td>${record.total_records}</td>
                        <td>${record.present_count}</td>
                        <td>${record.attendance_rate}%</td>
                        <td>
                            <a href="generate_attendance_report.php?course_name=${encodeURIComponent(record.course_name)}">
                                <button style="padding: 6px 12px;">View Details</button>
                            </a>
                        </td>
                    </tr>
                `;
            });
            
            tableHTML += '</tbody></table>';
            reportData.innerHTML = tableHTML;
        }
    } catch (error) {
        console.error('Error loading attendance overview:', error);
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