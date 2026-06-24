# AI Safety Integration (Mental Health) — PHP Backend

## What this setup includes
- Backend-protected chat API via PHP endpoints:
  - `/api/chat.php`
  - `/api/prompts.php`
- Input safety classification (crisis, harm, prompt-injection, clinical boundary).
- Crisis protocol responses for self-harm/violence intent.
- Output filtering to avoid diagnostic or prescribing language.
- Soft anti-abuse controls (no hard request cap): strict mode + small delay under suspicious load.
- Privacy-aware audit logging in `server/logs/safety-events.log`.
- Adaptive prompt generation based on user activity signals.

## Files
- `api/bootstrap.php` — shared safety logic, env loading, audit logging, Gemini caller.
- `api/chat.php` — main chat endpoint with safety pipeline.
- `api/prompts.php` — adaptive prompts endpoint.

## Quick start (PHP)
1. Copy env template:
   - `copy .env.example .env` (Windows)
2. Set your key in `.env`:
   - `GEMINI_API_KEY=your_key_here`
   - `GEMINI_MODEL=gemini-2.5-flash`
   - Get a free key at: https://aistudio.google.com/app/apikey
3. Serve the project with PHP (from project root):
   - `php -S localhost:8000`
4. Open:
   - `http://localhost:8000/chatbot.html`

If you use XAMPP/WAMP, place this project under your web root and browse to the same `chatbot.html` route.

## Always-available approach (your preference)
This project does **not** hard-block users with a strict request limit.
Instead it uses soft controls:
- suspicious activity triggers stricter safety mode,
- a short delay may be applied,
- supportive responses still continue.

## Important clinical boundaries
The assistant is supportive only and should not:
- diagnose conditions,
- prescribe medication,
- replace professional or emergency care.

If you deploy publicly, expand the crisis resources in `api/bootstrap.php` to include your target regions.
