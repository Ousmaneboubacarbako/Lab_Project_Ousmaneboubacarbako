// auth_check_js.js - Client-side authentication and role-based access control

// Role to dashboard mapping
const dashboardMap = {
    'admin': '/Lab%203%20assignment%20(3)/Lab_assignment_4/html/admin_dashboard.html',
    'faculty': '/Lab%203%20assignment%20(3)/Lab_assignment_4/html/FDashboard.html',
    'student': '/Lab%203%20assignment%20(3)/Lab_assignment_4/html/StuDashboard.html',
    'fi': '/Lab%203%20assignment%20(3)/Lab_assignment_4/html/Dashboard.html'
};

// Page access rules - define which roles can access which pages
const pageAccessRules = {
    'admin_dashboard.html': ['admin'],
    'FDashboard.html': ['admin', 'faculty'],
    'StuDashboard.html': ['admin', 'student'],
    'Dashboard.html': ['admin', 'fi'],
    'MarkAttend.html': ['admin', 'faculty', 'fi'],
    'ViewAttendance.html': ['admin', 'faculty', 'fi'],
    'StuPerform.html': ['admin', 'faculty', 'fi'],
    'ViewGrades.html': ['admin', 'student'],
    'FeedBack.html': ['admin', 'student']
};

// Check authentication status
async function checkAuth() {
    try {
        const response = await fetch('../php/check_session.php', {
            method: 'GET',
            credentials: 'same-origin'
        });
        
        const data = await response.json();
        
        if (!data.authenticated) {
            // Not logged in - redirect to login
            window.location.href = '/Lab 3 assignment (3)/Lab 3 assignment/html/Login.html';
            return null;
        }
        
        return data.user;
    } catch (error) {
        console.error('Auth check failed:', error);
        window.location.href = '/Lab 3 assignment (3)/Lab 3 assignment/html/Login.html';
        return null;
    }
}

// Check if user has access to current page
async function checkPageAccess() {
    const user = await checkAuth();
    
    if (!user) {
        return; // Already redirected to login
    }
    
    // Get current page filename
    const currentPage = window.location.pathname.split('/').pop();
    
    // Check if page has access rules
    if (pageAccessRules[currentPage]) {
        const allowedRoles = pageAccessRules[currentPage];
        
        if (!allowedRoles.includes(user.role)) {
            // User doesn't have access - redirect to their dashboard
            alert('Access Denied! You do not have permission to access this page.');
            window.location.href = dashboardMap[user.role];
            return;
        }
    }
    
    // User has access - populate user info on page
    populateUserInfo(user);
}

// Populate user information on the page
function populateUserInfo(user) {
    // Update welcome message
    const welcomeElements = document.querySelectorAll('[data-user-name]');
    welcomeElements.forEach(el => {
        el.textContent = `${user.first_name} ${user.last_name}`;
    });
    
    // Update email
    const emailElements = document.querySelectorAll('[data-user-email]');
    emailElements.forEach(el => {
        el.textContent = user.email;
    });
    
    // Update role
    const roleElements = document.querySelectorAll('[data-user-role]');
    roleElements.forEach(el => {
        el.textContent = user.role.charAt(0).toUpperCase() + user.role.slice(1);
    });
    
    // Store user data globally
    window.currentUser = user;
}

// Redirect user to their appropriate dashboard
function redirectToUserDashboard(role) {
    if (dashboardMap[role]) {
        window.location.href = dashboardMap[role];
    } else {
        window.location.href = '/Lab 3 assignment (3)/Lab 3 assignment/html/Login.html';
    }
}

// Check if user is on login/signup page when already authenticated
async function checkIfAlreadyLoggedIn() {
    const currentPage = window.location.pathname.split('/').pop();
    
    // Only check on login/signup pages
    if (currentPage === 'Login.html' || currentPage === 'Sign Up.html') {
        try {
            // Add small delay to avoid interfering with login redirect
            await new Promise(resolve => setTimeout(resolve, 100));
            
            const response = await fetch('../php/check_session.php', {
                method: 'GET',
                credentials: 'same-origin',
                cache: 'no-cache'
            });
            
            const data = await response.json();
            
            if (data.authenticated) {
                // Already logged in - redirect to dashboard
                console.log('User already authenticated, redirecting to dashboard');
                window.location.href = data.dashboard;
            }
        } catch (error) {
            // Ignore errors on login/signup pages
            console.log('Not authenticated or error checking:', error);
        }
    }
}

// Initialize authentication check when page loads
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function() {
        const currentPage = window.location.pathname.split('/').pop();
        
        // Check if already logged in (for login/signup pages)
        if (currentPage === 'Login.html' || currentPage === 'Sign Up.html') {
            checkIfAlreadyLoggedIn();
        } else {
            // Check access for protected pages
            checkPageAccess();
        }
    });
} else {
    const currentPage = window.location.pathname.split('/').pop();
    
    if (currentPage === 'Login.html' || currentPage === 'Sign Up.html') {
        checkIfAlreadyLoggedIn();
    } else {
        checkPageAccess();
    }
}
