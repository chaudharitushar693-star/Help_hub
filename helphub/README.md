# HELPHUB 2.0 PRO — Social Help & Community Mutual Aid Platform
> **“Connect. Help. Make a Difference.”**

A production-grade, full-stack community assistance platform connecting neighbors in urgent need with local volunteers and skilled mentors. Features live private chat, smart helper matching algorithm, reputation points, mutual review workflows, community social feed, interactive emergency map, and an administrative moderation portal.

---

## ⚡ Instant One-Command Terminal Launch (Easiest)

Python 3 is already installed on macOS and requires **zero external packages or setup**. Open your Mac Terminal and paste:

```bash
cd /Users/tusharchaudhari/.gemini/antigravity/scratch/helphub && python3 server.py
```

Then open your browser:

- 🏠 **Main Portal**: [http://localhost:8080/](http://localhost:8080/)
- 🔍 **Find Help Board**: [http://localhost:8080/requests/view.php](http://localhost:8080/requests/view.php)
- 🆘 **Emergency Assistance**: [http://localhost:8080/requests/create.php?urgency=emergency](http://localhost:8080/requests/create.php?urgency=emergency)
- 🤝 **Browse Help Offers**: [http://localhost:8080/offers/view.php](http://localhost:8080/offers/view.php)
- 💬 **Live Chat & Messaging**: [http://localhost:8080/messages/index.php](http://localhost:8080/messages/index.php)
- 🌟 **Community Feed**: [http://localhost:8080/community/index.php](http://localhost:8080/community/index.php)
- 👤 **User Dashboard**: [http://localhost:8080/dashboard/index.php](http://localhost:8080/dashboard/index.php)
- ⚡ **Admin Control Center**: [http://localhost:8080/admin/](http://localhost:8080/admin/)
- 📊 **PowerPoint Presentation (.pptx)**: [http://localhost:8080/helphub_presentation.pptx](http://localhost:8080/helphub_presentation.pptx)
- 📄 **Presentation Document (.pdf)**: [http://localhost:8080/helphub_presentation.pdf](http://localhost:8080/helphub_presentation.pdf)
- 🎓 **Interactive College Presentation Deck (15 Slides)**: [http://localhost:8080/presentation.html](http://localhost:8080/presentation.html)

---

## 🎓 College Project Presentation (15 Slides Structure)

An authentic, professional 15-slide presentation package adhering strictly to university major capstone guidelines:

1. **PowerPoint File (.pptx)**: Native 16:9 widescreen presentation compatible with Microsoft PowerPoint, Apple Keynote, and Google Slides.
   - Mac terminal open command:
     ```bash
     open /Users/tusharchaudhari/.gemini/antigravity/scratch/helphub/helphub_presentation.pptx
     ```
2. **PDF Document (.pdf)**: High-resolution landscape PDF with rounded cards, badges, and comparison tables.
   - Mac terminal open command:
     ```bash
     open /Users/tusharchaudhari/.gemini/antigravity/scratch/helphub/helphub_presentation.pdf
     ```
3. **Interactive Web Slide Deck**: Fullscreen presentation mode, speaker notes, and live app demo link.
   - Mac terminal open command:
     ```bash
     open /Users/tusharchaudhari/.gemini/antigravity/scratch/helphub/presentation.html
     ```

### Presentation Slide Index (15 Slides)
- **Slide 1:** Title Slide (Project Title, Tagline, Student, Roll No, Guide)
- **Slide 2:** Introduction (Civic-tech mutual aid concept & vision)
- **Slide 3:** Problem Statement (Crisis fragmentation, scams, privacy leaks)
- **Slide 4:** Objectives (Functional, algorithmic, trust, engineering goals)
- **Slide 5:** Existing System (Comparison with WhatsApp & TaskRabbit)
- **Slide 6:** Proposed System (5-step mutual aid lifecycle & advantages)
- **Slide 7:** Technology Used (HTML5/CSS3/ES6+, PHP 8/Python 3, MySQL/SQLite)
- **Slide 8:** System Requirements (Hardware, software, zero-dependency)
- **Slide 9:** System Design (3-tier MVC, 17 tables in 3NF, state machine)
- **Slide 10:** Modules Description (Auth, Requests, Matcher, Chat, Emergency, Admin)
- **Slide 11:** Implementation (Haversine math formula, SQLi defense, routes)
- **Slide 12:** Output / Results (Empirical verification matrix: 8 tests passed)
- **Slide 13:** Conclusion (Milestones, technical learnings, societal impact)
- **Slide 14:** Future Scope (WebSockets, AI emergency triage, Flutter mobile apps)
- **Slide 15:** References (Academic books, IEEE research papers, web standards)

---

## 🔑 Pre-Seeded Test Accounts

| Role | Name & Specialization | Email | Password |
|---|---|---|---|
| **Admin** | Platform Administrator | `admin@helphub.org` | `admin123` |
| **Member** | Rahul Sharma *(Computer & Laptop Repair)* | `rahul@example.com` | `user123` |
| **Member** | Priya Nair *(Math & Physics Tutor)* | `priya@example.com` | `user123` |
| **Member** | Aman Verma *(First Aid & Emergency Blood)* | `aman@example.com` | `user123` |
| **Member** | Neha Gupta *(Senior Care & Medication)* | `neha@example.com` | `user123` |

---

## 🌟 Architecture & Features

### 1. Smart Help Matching Engine
- Automatically scores suitability (0-99%) using:
  - Category alignment (+30 pts)
  - Skill keyword overlap (+20 pts)
  - Proximity via Haversine distance formula (< 5km = +15 pts, < 15km = +10 pts)
  - Mutual rating and community reputation bonus
- Real match badges displayed (e.g. `96% Match`, `89% Match`) on both request views and user dashboards.

### 2. Private Messaging & Live Chat
- WhatsApp / LinkedIn style split-pane desktop & full-screen mobile chat.
- Live AJAX polling (zero page refresh when sending messages).
- Direct "Contact" button on all request cards, offer cards, and profile headers.
- Unread counter badges on navbar and conversation sidebar.
- Safety reporting and user blocking.

### 3. Request Lifecycle & Reputation System
- Status transitions: `Open` ➔ `In Discussion` ➔ `Accepted` ➔ `In Progress` ➔ `Completed` ➔ `Rate Experience`.
- Mutual 1-5 star ratings with written reviews; prevents duplicate reviews.
- Reputation points awarded automatically (+20 pts for completing help, +10 pts for posting requests, +5 pts for helpful reviews).
- Community badges: 🌱 New Helper, ⭐ Trusted Helper, 🏆 Community Hero, 💎 Top Volunteer.

### 4. Interactive Aid Map
- OpenStreetMap integration via Leaflet.js with zero API keys required.
- Visual pins: 🟢 Available Helper, 🔴 Help Needed, 🟡 Urgent / Emergency.

### 5. Community Social Feed
- Share success stories, neighborhood announcements, and volunteer experiences.
- Likes, interactive comments, and safety moderation.

### 6. Full-Stack Dual Backend Support
- **Standalone Mode (`server.py`)**: Pure Python 3 + SQLite (`helphub.db`) with complete REST API parity.
- **Production Mode (PHP 8 + MySQL)**: Standard PDO architecture using `database.sql` and `config/database.php`.

---

## 🐘 PHP 8 + MySQL (XAMPP / MAMP / Apache) Deployment

1. Copy the `helphub` folder to your web root (`htdocs/` in XAMPP or `www/` in WampServer).
2. Open phpMyAdmin (`http://localhost/phpmyadmin`) and import `database.sql`.
3. Verify database settings in `config/database.php`:
   ```php
   $DB_HOST = '127.0.0.1';
   $DB_NAME = 'helphub';
   $DB_USER = 'root';
   $DB_PASS = '';
   ```
4. Access via `http://localhost/helphub/`.
