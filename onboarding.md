# Onboarding Flow

## Admin
An admin account is seeded directly into the database by the development team. The admin logs in through the standard login form and is routed to the admin dashboard based on their role.

## Professional (Psychiatrist / Psychologist / Nurse)
A professional registers through the signup form. Their account is created with status `pending`. An admin reviews their credentials in the admin dashboard and changes the status to `active`. Only active professionals can log in.

## Patient
A patient must be onboarded by a clinician. The clinician logs into their professional portal, navigates to a patient creation form, and enters the patient's name, email, and assigns a temporary password. The patient receives their login credentials, logs in, and is prompted to change their password on first login.
