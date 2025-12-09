// auth_check.js - Include this file in all dashboard pages
// This file checks if user is authenticated and has the correct role

(function() {
  'use strict';

  // Get current session
  const currentSession = JSON.parse(localStorage.getItem('currentSession') || 'null');

  // Check if user is logged in
  if (!currentSession || !currentSession.logged_in) {
    // User is not logged in, redirect to login page
    alert('Please login to access this page.');
    window.location.href = '../html/Login.php';
    return;
  }

  // Get the current page's required role from the page filename or data attribute
  const currentPage = window.location.pathname.split('/').pop();
  let requiredRole = null;

  // Determine required role based on page
  if (currentPage.includes('StuDashboard') || currentPage.includes('student')) {
    requiredRole = 'student';
  } else if (currentPage.includes('FDashboard') || currentPage.includes('faculty')) {
    requiredRole = 'faculty';
  } else if (currentPage.includes('Dashboard') && !currentPage.includes('Stu') && !currentPage.includes('F')) {
    requiredRole = 'fi';
  }

  // Check if user has the correct role
  if (requiredRole && currentSession.role !== requiredRole) {
    alert(`Access denied. This page is only accessible to ${requiredRole} users.`);
    
    // Redirect to correct dashboard
    switch(currentSession.role) {
      case 'student':
        window.location.href = 'StuDashboard.php';
        break;
      case 'faculty':
        window.location.href = 'FDashboard.php';
        break;
      case 'fi':
        window.location.href = 'Dashboard.php';
        break;
      default:
        window.location.href = '../html/Login.php';
    }
    return;
  }

  // Check session timeout (optional - 30 minutes)
  const loginTime = new Date(currentSession.loginTime);
  const currentTime = new Date();
  const timeDiff = (currentTime - loginTime) / (1000 * 60); // difference in minutes

  if (timeDiff > 30) {
    alert('Your session has expired. Please login again.');
    localStorage.removeItem('currentSession');
    window.location.href = '../html/Login.php';
    return;
  }

  // Update last activity time
  currentSession.lastActivity = new Date().toISOString();
  localStorage.setItem('currentSession', JSON.stringify(currentSession));

  // Display user info in dashboard (if elements exist)
  window.addEventListener('DOMContentLoaded', function() {
    const userNameElements = document.querySelectorAll('[data-user-name]');
    const userEmailElements = document.querySelectorAll('[data-user-email]');
    const userRoleElements = document.querySelectorAll('[data-user-role]');

    userNameElements.forEach(el => {
      el.textContent = `${currentSession.firstName} ${currentSession.lastName}`;
    });

    userEmailElements.forEach(el => {
      el.textContent = currentSession.email;
    });

    userRoleElements.forEach(el => {
      el.textContent = currentSession.role.toUpperCase();
    });
  });

  // Make session data available globally
  window.currentUser = currentSession;

})();

// Logout function - can be called from any dashboard
function logout() {
  if (confirm('Are you sure you want to logout?')) {
    // Clear session
    localStorage.removeItem('currentSession');
    
    // Redirect to login page
    window.location.href = '../html/Login.php';
  }
}

// Add logout to window object so it can be called from HTML
window.logout = logout;