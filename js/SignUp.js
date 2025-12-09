// Sign Up form handler
document.getElementById('signupForm').addEventListener('submit', async function(e) {
  e.preventDefault();
  
  const firstName = document.getElementById('firstName').value;
  const lastName = document.getElementById('lastName').value;
  const email = document.getElementById('email').value;
  const password = document.getElementById('password').value;
  const confirmPassword = document.getElementById('confirmPassword').value;

  // Client-side validation
  if (!validateName(firstName)) {
    showMessage('First name can only contain letters, spaces, hyphens, and apostrophes', 'error');
    return;
  }

  if (!validateName(lastName)) {
    showMessage('Last name can only contain letters, spaces, hyphens, and apostrophes', 'error');
    return;
  }

  if (password !== confirmPassword) {
    showMessage('Passwords do not match!', 'error');
    return;
  }

  if (password.length < 8) {
    showMessage('Password must be at least 8 characters long!', 'error');
    return;
  }

  // Show loading state
  const submitBtn = this.querySelector('button[type="submit"]');
  const originalText = submitBtn.textContent;
  submitBtn.textContent = 'Registering...';
  submitBtn.disabled = true;

  try {
    const formData = new FormData();
    formData.append('firstName', firstName);
    formData.append('lastName', lastName);
    formData.append('email', email);
    formData.append('password', password);
    formData.append('confirmPassword', confirmPassword);
    formData.append('role', document.getElementById('role').value);

    const response = await fetch('../php/signup_handler.php', {
      method: 'POST',
      body: formData
    });

    const data = await response.json();

    if (data.success) {
      showMessage(data.message, 'success');
      
      // Clear form
      document.getElementById('signupForm').reset();
      
      // Redirect to login after 2 seconds
      setTimeout(() => {
        window.location.href = data.redirect;
      }, 2000);
    } else {
      showMessage(data.message, 'error');
      submitBtn.textContent = originalText;
      submitBtn.disabled = false;
    }
  } catch (error) {
    console.error('Error:', error);
    showMessage('An error occurred. Please try again.', 'error');
    submitBtn.textContent = originalText;
    submitBtn.disabled = false;
  }
});

// Validate name function
function validateName(name) {
  return /^[a-zA-Z\s\-\']+$/.test(name);
}

// Show message function
function showMessage(message, type) {
  // Remove existing messages
  const existingMsg = document.querySelector('.message');
  if (existingMsg) existingMsg.remove();

  // Create new message
  const msgDiv = document.createElement('div');
  msgDiv.className = `message message-${type}`;
  msgDiv.textContent = message;
  msgDiv.style.cssText = `
    padding: 12px;
    margin: 15px 0;
    border-radius: 6px;
    font-size: 14px;
    ${type === 'success' ? 'background: #d4edda; color: #155724; border: 1px solid #c3e6cb;' : ''}
    ${type === 'error' ? 'background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb;' : ''}
  `;

  // Insert message
  const form = document.getElementById('signupForm');
  form.insertBefore(msgDiv, form.firstChild);

  // Auto-remove success messages after 3 seconds
  if (type === 'success') {
    setTimeout(() => msgDiv.remove(), 3000);
  }
}