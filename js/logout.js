// logout.js
// Adds logout behavior to elements with id `logoutBtn`, class `logout`, or `data-logout` attribute.
// Sends a POST to `../php/logout.php` (if present), clears storage, and redirects to the login page.

(function () {
	'use strict';

	const LOGIN_PAGE = '../html/Login.html';
	const LOGOUT_ENDPOINT = '../php/logout.php';

	function clearClientSession() {
		try {
			sessionStorage.clear();
			localStorage.clear();
			// remove cookies named 'PHPSESSID' if needed
			document.cookie = 'PHPSESSID=; Path=/; Expires=Thu, 01 Jan 1970 00:00:01 GMT;';
		} catch (e) {
			// silent
		}
	}

	function performLogout() {
		// Try to call server-side logout if available, otherwise still clear client and redirect.
		fetch(LOGOUT_ENDPOINT, {
			method: 'POST',
			credentials: 'same-origin',
			headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
			body: 'logout=1'
		}).then(response => {
			// ignore server response details; clear client and redirect
			clearClientSession();
			window.location.href = LOGIN_PAGE;
		}).catch(err => {
			// If fetch fails (no endpoint), still clear local data and redirect
			clearClientSession();
			window.location.href = LOGIN_PAGE;
		});
	}

	function attachListeners() {
		const selectors = ['#logoutBtn', '.logout', '[data-logout]'];
		const elements = selectors
			.map(s => Array.from(document.querySelectorAll(s)))
			.reduce((a, b) => a.concat(b), []);

		// de-duplicate
		const unique = Array.from(new Set(elements));
		if (unique.length === 0) return;

		unique.forEach(el => {
			el.addEventListener('click', function (e) {
				e.preventDefault();
				const confirmAttr = el.getAttribute('data-confirm');
				if (confirmAttr === 'false') {
					performLogout();
					return;
				}
				const message = el.getAttribute('data-confirm-message') || 'Are you sure you want to log out?';
				if (confirm(message)) {
					performLogout();
				}
			});
		});
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', attachListeners);
	} else {
		attachListeners();
	}
})();

