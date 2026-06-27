# Solace Sphere Onboarding Guide

## Folder Structure

```
solacesphere/
  auth/          login, signup, password reset pages (public)
  patient/       patient tools: dashboard, mood, journal, breathing, etc.
  professional/  clinician dashboard, schedule, notes, patient detail
  admin/         admin dashboard for user management and verification
  api/           PHP backend endpoints (login, signup, admin, etc.)
  database/      schema.sql and seed script
  server/logs/   safety event logs and abuse state
  styles.css     global stylesheet
  auth.js        client-side role guard and navigation
```

## How to Run the Application

1. Open a terminal and go to the project folder (wherever you placed it on your machine).

2. Copy `.env.example` to `.env` and open `.env` in a text editor:
   `cp .env.example .env`

3. Fill in your database credentials and Gemini API key in `.env`:
   ```
   DB_HOST=127.0.0.1
   DB_NAME=solace_db
   DB_USER=your_mysql_username
   DB_PASS=your_mysql_password
   DB_PORT=3306
   GEMINI_API_KEY=your_gemini_api_key_here
   GEMINI_MODEL=gemini-2.5-flash
   ```

4. Make sure MySQL is running. Then import the database schema:
   `mysql -u your_mysql_username -p solace_db < database/schema.sql`
   (It will prompt for your password.)

5. Seed the admin account and demo users:
   `php database/seed.php`

6. Start the PHP development server:
   `php -S localhost:8000`

7. Open a browser and go to `http://localhost:8000/landing%20page.html`
   (If you are using a different port, replace 8000 with your port.)

---

## Pre seeded Accounts

| Role         | Email                       | Password  | Status   |
|--------------|-----------------------------|-----------|----------|
| Admin        | admin@solacesphere.com      | admin123  | active   |
| Patient      | joy@example.com             | password  | active   |
| Professional | sarah.wilson@example.com    | password  | pending  |
| Professional | ahmed.khan@example.com      | password  | pending  |

---

## Step by Step Walkthrough for Each Role

---

### PATIENT FLOW (Self Registration)

**Step 1: Visit the Landing Page**
1. Open `http://localhost:8000/landing%20page.html` in your browser.
2. You see the Solace Sphere home page. There is a navigation bar at the top with links: Home, Dashboard, Support & Tools (dropdown), Appointments, Chatbot, Sign Up, Login, Profile.
3. There is a "Get Started" button and three cards below: Self-Help Tools, Anonymous Support, Wellness Resources.

**Step 2: Sign Up**
1. Click "Sign Up" in the navigation bar. The URL changes to `http://localhost:8000/auth/signup.html`.
2. You see a signup form on the right side and an image on the left.
3. Fill in:
   - Name: `Jane Doe`
   - Email: `jane@example.com`
   - Password: must include at least one capital letter, one number, and one special character (e.g. `Password1!`)
   - Confirm Password: type the same password
4. As you type the password, you see a strength bar and a checklist showing capital letter, number, and special character requirements.
5. Click the "CREATE ACCOUNT" button.
6. The form sends your data to `api/signup.php`. If the server is running, your account is saved to the database. If the server is offline, the app falls back to localStorage.
7. After success, the page redirects to `/patient/dashboard.html`.

**Step 3: Patient Dashboard**
1. You arrive at the patient dashboard. It shows:
   - A welcome section with your name and a greeting.
   - A "How are you feeling today?" mood check in card.
   - Quick tool pills: Breathing, Mood, Journal, Affirmations.
   - Recommended resources section with 3 resource cards.
   - A floating chatbot button in the bottom right.
2. The navigation bar now shows: Home, Dashboard, Support & Tools, Appointments, Chatbot, Profile (shows your name).

**Step 4: Track Your Mood**
1. Click the "Mood" tool pill or navigate to `http://localhost:8000/patient/mood.html`.
2. You see a mood tracker page with emoji faces or mood options.
3. Select how you are feeling and optionally add a note.
4. Your mood entry is saved to localStorage.

**Step 5: Write a Journal Entry**
1. Click "Journal" from the tool pills or navigate to `http://localhost:8000/patient/journal.html`.
2. You see a journal page with a text area.
3. Write your thoughts and click "Save Entry".
4. The journal entry is saved to localStorage.

**Step 6: Use Guided Breathing**
1. Click "Breathing" from the tool pills or navigate to `http://localhost:8000/patient/breathing.html`.
2. You see a guided breathing exercise with an animated circle.
3. Follow the inhale, hold, exhale prompts.

**Step 7: Read Affirmations**
1. Click "Affirmations" or navigate to `http://localhost:8000/patient/affirmations.html`.
2. You see positive affirmations. You can click to generate a new one.

**Step 8: Browse Resources**
1. Click "Resources" in the Support & Tools dropdown or navigate to `http://localhost:8000/patient/resources.html`.
2. You see wellness articles organized by category (Anxiety, Depression, Stress, Mindfulness, Sleep).
3. Click any resource to read it.

**Step 9: Use the Community**
1. Click "Community" in the Support & Tools dropdown or navigate to `http://localhost:8000/patient/community.html`.
2. You see community posts from other users.
3. You can write your own post and like others' posts.

**Step 10: Chat with the AI**
1. Click the floating chatbot button (bottom right) or navigate to `http://localhost:8000/patient/chatbot.html`.
2. You see a chat interface. Type a message like "I feel anxious" and press Enter.
3. The AI responds with coping strategies. If the Gemini API key is configured, it uses the Gemini model. Otherwise, it falls back to local canned responses.

**Step 11: View Your Profile and Progress**
1. Click your name in the navigation bar (Profile dropdown).
2. Select "View Profile" to see your account details and wellness focus.
3. Select "Personal Progress" to see charts of your mood trends, journal streaks, and sleep logs.

---

### PROFESSIONAL FLOW (Clinician / Psychologist / Psychiatrist)

**Step 1: Sign Up**
1. Go to `http://localhost:8000/auth/signup.html`.
2. Fill in your details (name, email, password with capital letter + number + special character).
3. Professional accounts normally require an invite token to set the role to "professional". For testing:
   - The signup form always creates a "patient" account by default.
   - To test the professional flow, use the pre seeded accounts below.

**Step 2: Log In (Using Pre seeded Professional Account)**
1. Go to `http://localhost:8000/auth/login.html`.
2. Select "Professional" from the role dropdown.
3. Enter email: `sarah.wilson@example.com` and password: `password`.
4. Click "LOG IN".
5. If the account status is "pending", you will see an error: "Your account is pending verification. Please wait for admin approval."

**Step 3: Wait for Admin Approval**
1. You cannot log in until an admin changes your status from "pending" to "active".
2. Ask an admin to approve your account (see Admin Flow below).

**Step 4: Log In After Approval**
1. Once the admin approves your account, go back to `http://localhost:8000/auth/login.html`.
2. Select "Professional" from the role dropdown.
3. Enter your email and password.
4. Click "LOG IN".
5. You are redirected to `/professional/clinician-dashboard.html`.

**Step 5: Clinical Dashboard**
1. You see the Clinical Overview page with:
   - "Onboard New Patient" form at the top.
   - High Priority Alerts section showing patients with low mood or high chat activity.
   - Patient Status table with columns: Patient Name, Last Logged Mood, Most Recent Activity, Risk Level, Action.
   - Mood Distribution donut chart on the right.
2. The navigation bar shows: Home, Dashboard, Support & Tools, Appointments, Clinical (active), Profile.

**Step 6: Onboard a New Patient (Clinician Initiated Registration)**
1. In the "Onboard New Patient" section at the top of the Clinical Dashboard:
   - Enter Patient Name: `Mike Johnson`
   - Enter Email: `mike@example.com`
   - Enter Temporary Password: `TempPass1!`
2. Click "Create Patient" button.
3. The form sends a POST request to `api/professional/create-patient.php`.
4. If successful, you see a green message: "Patient Mike Johnson created. They can log in with the provided credentials."
5. The patient account is created in the database with role=`patient` and status=`active`.

**Step 7: View Patient Details**
1. In the Patient Status table, click "View Full Profile" next to any patient.
2. You are taken to `/patient/profile.html?patient=ID` which shows the patient's information.
3. Note: The patient detail page currently shows mock data for all patients.

**Step 8: Schedule Manager**
1. Click "Schedule Manager" in the clinical action buttons or navigate to `/professional/schedule-manager.html`.
2. You see a weekly calendar view (mock data).

**Step 9: Clinical Notes**
1. Click "Clinical Notes" in the clinical action buttons or navigate to `/professional/clinical-notes.html`.
2. You see a notes interface (mock data).

---

### ADMIN FLOW

**Step 1: Log In**
1. Go to `http://localhost:8000/auth/login.html`.
2. Select "Admin" from the role dropdown.
3. Enter email: `admin@solacesphere.com` and password: `admin123`.
4. Click "LOG IN".
5. You are redirected to `/admin/admin-dashboard.html`.

**Step 2: Admin Dashboard**
1. You see the Admin Dashboard with sections:
   - User Management: search and manage all users.
   - Professional Verification: review pending professional accounts.
   - Resource Library CMS: add wellness resources (local mock only).
   - System Health Monitor: shows live status info.
   - Database Management: informational section.
2. The navigation bar shows: Home, Dashboard, Support & Tools, Appointments, Clinical, Admin (active), Profile.

**Step 3: Review Pending Professionals**
1. Look at the "Professional Verification" section. It lists all professionals with status "pending".
2. You should see:
   - Dr. Sarah Wilson (sarah.wilson@example.com)
   - Dr. Ahmed Khan (ahmed.khan@example.com)
3. Next to each, there are two buttons: "Approve" and "Reject".

**Step 4: Approve a Professional**
1. Click "Approve" next to Dr. Sarah Wilson.
2. The page sends a POST request to `api/admin/verify.php` with `action: approve`.
3. The database updates her status from "pending" to "active".
4. The page refreshes and she no longer appears in the pending list.
5. Dr. Sarah Wilson can now log in.

**Step 5: Search and Manage Users**
1. In the "User Management" section, use the search field to find users by name or email.
2. Each user shows their name, email, role badge, and status badge.
3. Click "Update Role" next to a user to change their role (patient, professional, admin).
4. Click "Deactivate" to change a user's status to "deactivated".

**Step 6: Add a Resource (Local Only)**
1. In the "Resource Library CMS" section, fill in the Title and select a Category.
2. Click "Add New Resource".
3. The resource is added to the local list on the page (not persisted to the database).

---

## User Stories

### Story 1: Patient Self Registration and Mood Tracking

As a new visitor, I want to create my own patient account and track my mood so that I can begin my self guided wellness journey.

How to test:
1. Go to `http://localhost:8000/landing%20page.html`.
2. Click "Sign Up" in the top navigation bar.
3. Fill in Name, Email, and a strong Password on the signup form.
4. Click "CREATE ACCOUNT".
5. You are redirected to the patient dashboard at `/patient/dashboard.html`.
6. Click the "Mood" tool pill.
7. Select a mood and optionally add a note.
8. Click save. The mood entry is stored in your browser.

### Story 2: Professional Registration and Admin Verification

As a mental health professional, I want to register and have my account approved by an admin before I can access the clinical dashboard, so that only verified clinicians can use the system.

How to test:
1. Go to `http://localhost:8000/auth/signup.html`.
2. Create an account (the default role is "patient", so for this test use the pre seeded professional account).
3. Go to `http://localhost:8000/auth/login.html`, select "Professional" from the dropdown, enter `sarah.wilson@example.com` / `password`, and click "LOG IN".
4. You see an error: "Your account is pending verification."
5. Open a new incognito window, go to `http://localhost:8000/auth/login.html`, select "Admin", enter `admin@solacesphere.com` / `admin123`, and click "LOG IN".
6. In the Professional Verification section, click "Approve" next to Dr. Sarah Wilson.
7. Go back to the first window, refresh the login page, select "Professional", enter `sarah.wilson@example.com` / `password`, and click "LOG IN".
8. You are now redirected to the Clinical Dashboard at `/professional/clinician-dashboard.html`.

### Story 3: Clinician Onboards a Patient

As a verified clinician, I want to create a patient account with a temporary password so that my patient can log in and access the platform.

How to test:
1. Log in as a professional (follow Story 2 first).
2. On the Clinical Dashboard at `/professional/clinician-dashboard.html`, find the "Onboard New Patient" section at the top.
3. Enter Patient Name: `Test Patient`, Email: `test@patient.com`, Temporary Password: `TestPass1!`.
4. Click "Create Patient".
5. You see a green success message confirming the patient was created.
6. Log out (click Profile > Logout).
7. Go to `http://localhost:8000/auth/login.html`, select "Patient", enter `test@patient.com` / `TestPass1!`, and click "LOG IN".
8. You are redirected to the patient dashboard at `/patient/dashboard.html` where you can use all patient tools.

### Story 4: Patient Uses Self Help Tools

As a patient, I want to use guided breathing, journaling, and affirmations so that I can manage my mental wellness on my own.

How to test:
1. Log in as a patient (e.g. `joy@example.com` / `password`).
2. On the dashboard at `/patient/dashboard.html`, click the "Breathing" tool pill.
3. Follow the animated breathing guide.
4. Click the back arrow to return to the Personal Toolbox or navigate to `/patient/journal.html`.
5. Write a journal entry and save it.
6. Navigate to `/patient/affirmations.html` and read the affirmations.
7. Navigate to `/patient/chatbot.html` and type "I feel stressed" to get an AI coping response.
8. Navigate to `/patient/community.html` and write an anonymous support post.

### Story 5: Admin Manages Users and Verifies Professionals

As an admin, I want to view all users, search for specific accounts, update roles, and approve or reject professional registrations.

How to test:
1. Log in as admin: `admin@solacesphere.com` / `admin123`.
2. On the Admin Dashboard at `/admin/admin-dashboard.html`, see the User Management section.
3. Type "sarah" in the search field. Only matching users appear.
4. In the Professional Verification section, click "Reject" next to Dr. Ahmed Khan.
5. His status changes to "deactivated" in the database.
6. In the User Management section, click "Deactivate" next to Joy A. (the test patient).
7. Her status changes to "deactivated" and the status badge updates to reflect the change.
