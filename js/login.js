// Login form handler
document.getElementById('loginForm').addEventListener('submit', async function(e) {
  e.preventDefault();
  
  const email = document.getElementById('loginEmail').value;
  const password = document.getElementById('loginPassword').value;
  const remember = document.getElementById('remember').checked;

  // Show loading state
  const submitBtn = this.querySelector('button[type="submit"]');
  const originalText = submitBtn.textContent;
  submitBtn.textContent = 'Logging in...';
  submitBtn.disabled = true;

  try {
    const formData = new FormData();
    formData.append('email', email);
    formData.append('password', password);
    if (remember) formData.append('remember', '1');

    const response = await fetch('../php/login.php', {
      method: 'POST',
      body: formData,
      credentials: 'same-origin'
    });

    const data = await response.json();

    if (data.success) {
      showMessage(data.message, 'success');
      
      // Store user info if needed
      console.log('Login successful:', data);
      console.log('Redirecting to:', data.redirect);
      
      // Immediate redirect
      window.location.href = data.redirect;
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
  const form = document.getElementById('loginForm');
  form.insertBefore(msgDiv, form.firstChild);

  // Auto-remove success messages after 3 seconds
  if (type === 'success') {
    setTimeout(() => msgDiv.remove(), 3000);
  }
}

// Pre-fill email if remembered
window.addEventListener('DOMContentLoaded', function() {
  const rememberedEmail = getCookie('user_email');
  if (rememberedEmail) {
    document.getElementById('loginEmail').value = rememberedEmail;
    document.getElementById('remember').checked = true;
  }
});

// Cookie helper function
function getCookie(name) {
  const value = `; ${document.cookie}`;
  const parts = value.split(`; ${name}=`);
  if (parts.length === 2) return parts.pop().split(';').shift();
}