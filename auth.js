(function () {
  const STORAGE_KEY = "solaceCurrentUser";
  const ROLE_HOME = {
    patient: "dashboard.html",
    professional: "clinician-dashboard.html",
    admin: "admin-dashboard.html"
  };

  const PUBLIC_PAGES = new Set([
    "index.html",
    "landing page.html",
    "login.html",
    "signup.html",
    "forgot-password.html",
    "reset-password.html",
    "verify-reset-token.php",
    "send-reset-email.php"
  ]);

  const PAGE_RULES = [
    { pages: ["dashboard.html", "profile.html", "appointment.html", "resources.html", "personal toolbox.html", "community.html", "chatbot.html", "mood.html", "journal.html", "affirmations.html", "breathing.html", "personal-progress.html"], roles: ["patient"] },
    { pages: ["clinician-dashboard.html", "schedule-manager.html", "clinical-notes.html", "patient-detail.html"], roles: ["professional"] },
    { pages: ["admin-dashboard.html"], roles: ["admin"] }
  ];

  const NAV_RULES = [
    { href: "dashboard.html", roles: ["patient"] },
    { href: "profile.html", roles: ["patient"] },
    { href: "appointment.html", roles: ["patient", "professional"] },
    { href: "resources.html", roles: ["patient"] },
    { href: "personal toolbox.html", roles: ["patient"] },
    { href: "community.html", roles: ["patient"] },
    { href: "chatbot.html", roles: ["patient"] },
    { href: "mood.html", roles: ["patient"] },
    { href: "journal.html", roles: ["patient"] },
    { href: "affirmations.html", roles: ["patient"] },
    { href: "breathing.html", roles: ["patient"] },
    { href: "clinician-dashboard.html", roles: ["professional"] },
    { href: "schedule-manager.html", roles: ["professional"] },
    { href: "clinical-notes.html", roles: ["professional"] },
    { href: "patient-detail.html", roles: ["professional"] },
    { href: "admin-dashboard.html", roles: ["admin"] }
  ];

  function getCurrentFile() {
    const path = window.location.pathname.split("/").pop() || "index.html";
    return decodeURIComponent(path).toLowerCase();
  }

  function getCurrentUser() {
    try {
      return JSON.parse(localStorage.getItem(STORAGE_KEY) || "null");
    } catch (error) {
      return null;
    }
  }

  function saveCurrentUser(user) {
    localStorage.setItem(STORAGE_KEY, JSON.stringify(user));
  }

  function getRoleHome(role) {
    return ROLE_HOME[role] || ROLE_HOME.patient;
  }

  function isAllowedPage(role, fileName) {
    const matchedRule = PAGE_RULES.find(rule => rule.pages.includes(fileName));
    if (!matchedRule) return true;
    return matchedRule.roles.includes(role);
  }

  function redirectToHome(role) {
    window.location.replace(getRoleHome(role));
  }

  function redirectToLogin(nextPage) {
    const url = nextPage ? `login.html?next=${encodeURIComponent(nextPage)}` : "login.html";
    window.location.replace(url);
  }

  function hideUnauthorizedNavLinks(role) {
    const nav = document.querySelector("header nav");
    if (!nav) return;

    nav.querySelectorAll("a").forEach(link => {
      const href = (link.getAttribute("href") || "").trim();
      if (!href || href === "#") return;

      const cleanHref = href.split("?")[0].split("#")[0].toLowerCase();
      const rule = NAV_RULES.find(item => item.href === cleanHref);
      if (rule && !rule.roles.includes(role)) {
        link.style.display = "none";
      }
    });

    document.querySelectorAll(".dropdown").forEach(dropdown => {
      const visibleLink = Array.from(dropdown.querySelectorAll("a")).some(link => link.style.display !== "none");
      if (!visibleLink) dropdown.style.display = "none";
    });
  }

  function updateAuthNav(currentUser) {
    const nav = document.querySelector("header nav");
    if (!nav) return;

    // Hide all explicit Sign Up links when a user is logged in
    const signupLinks = nav.querySelectorAll('.signup-link');
    signupLinks.forEach(a => {
      a.style.display = currentUser ? "none" : "";
    });

    if (!currentUser) return;

    // Replace Profile link with a dropdown split into sections
    const profileLink = nav.querySelector('a[href="profile.html"]');
    if (profileLink && !profileLink.closest('.dropdown') && !nav.querySelector('.dropdown[data-generated]')) {
      const userName = (currentUser && (currentUser.displayName || currentUser.name || currentUser.username)) ? (currentUser.displayName || currentUser.name || currentUser.username) : 'Profile';

      const dropdown = document.createElement('div');
      dropdown.className = 'dropdown';
      dropdown.setAttribute('data-generated', 'true');

      const toggle = document.createElement('a');
      toggle.href = '#';
      toggle.className = 'dropdown-toggle';
      toggle.textContent = userName;

      const menu = document.createElement('div');
      menu.className = 'dropdown-menu';

      const items = [
        { href: 'profile.html', text: 'View Profile' },
        { href: 'profile.html#edit-profile', text: 'Edit Profile' },
        { href: 'personal-progress.html', text: 'Personal Progress' },
        { href: 'profile.html#settings', text: 'Settings' }
      ];

      items.forEach(it => {
        const a = document.createElement('a');
        a.href = it.href;
        a.textContent = it.text;
        menu.appendChild(a);
      });

      const logout = document.createElement('a');
      logout.href = '#';
      logout.id = 'logout-link';
      logout.textContent = 'Logout';
      menu.appendChild(logout);

      dropdown.appendChild(toggle);
      dropdown.appendChild(menu);

      // Replace the original profile link with the generated dropdown
      profileLink.parentNode.replaceChild(dropdown, profileLink);

      // Prevent default on toggle
      toggle.addEventListener('click', function (e) { e.preventDefault(); });

      // Logout handler
      logout.addEventListener('click', function (e) {
        e.preventDefault();
        localStorage.removeItem(STORAGE_KEY);
        window.location.replace('index.html');
      });
    }
  }

  function initRoleGuard() {
    const fileName = getCurrentFile();
    const currentUser = getCurrentUser();
    const role = currentUser && currentUser.role ? currentUser.role : null;

    if (currentUser && !currentUser.role) {
      currentUser.role = "patient";
      saveCurrentUser(currentUser);
    }

    if (role && (fileName === "login.html" || fileName === "signup.html")) {
      redirectToHome(role);
      return;
    }

    const protectedPage = PAGE_RULES.some(rule => rule.pages.includes(fileName));
    if (protectedPage && !currentUser) {
      redirectToLogin(fileName);
      return;
    }

    if (protectedPage && currentUser && !isAllowedPage(role, fileName)) {
      redirectToHome(role || "patient");
      return;
    }

    if (!PUBLIC_PAGES.has(fileName) && !currentUser) {
      redirectToLogin(fileName);
      return;
    }

    if (currentUser) {
      hideUnauthorizedNavLinks(role || "patient");
      try { updateAuthNav(currentUser); } catch (e) { /* ignore */ }
    }

  }

  window.SolaceAuth = {
    getCurrentUser,
    saveCurrentUser,
    getRoleHome,
    getCurrentRole: () => {
      const user = getCurrentUser();
      return user && user.role ? user.role : "patient";
    },
    setRole: role => {
      const user = getCurrentUser() || {};
      user.role = role;
      saveCurrentUser(user);
      return user;
    }
  };

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initRoleGuard);
  } else {
    initRoleGuard();
  }
})();