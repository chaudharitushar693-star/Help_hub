#!/usr/bin/env python3
"""
============================================================
HELPHUB 2.0 PRO - Standalone Production-Grade Dev Server
"Connect. Help. Make a Difference."
Zero external dependencies (Pure Python 3 + SQLite + http.server)
============================================================
"""

import os
import sys
import json
import sqlite3
import hashlib
import secrets
import math
import posixpath
from http.server import SimpleHTTPRequestHandler, HTTPServer
from urllib.parse import urlparse, parse_qs

PORT = int(os.environ.get("PORT", 8080))
BASE_DIR = os.path.dirname(os.path.abspath(__file__))
DB_PATH = os.path.join(BASE_DIR, "helphub.db")

SESSIONS = {}

def hash_password(password: str) -> str:
    return hashlib.sha256(password.encode("utf-8")).hexdigest()

def get_db():
    conn = sqlite3.connect(DB_PATH)
    conn.row_factory = sqlite3.Row
    return conn

def haversine_distance(lat1, lon1, lat2, lon2):
    if None in (lat1, lon1, lat2, lon2):
        return None
    try:
        lat1, lon1, lat2, lon2 = float(lat1), float(lon1), float(lat2), float(lon2)
        r = 6371.0 # km
        dlat = math.radians(lat2 - lat1)
        dlon = math.radians(lon2 - lon1)
        a = (math.sin(dlat / 2) ** 2 +
             math.cos(math.radians(lat1)) * math.cos(math.radians(lat2)) *
             math.sin(dlon / 2) ** 2)
        c = 2 * math.atan2(math.sqrt(a), math.sqrt(1 - a))
        return round(r * c, 1)
    except Exception:
        return None

def calculate_match_score(req, helper):
    score = 40
    # Category match
    if req.get("category_id") and helper.get("category_id") and int(req["category_id"]) == int(helper["category_id"]):
        score += 30

    # Skills overlap
    req_skills = (req.get("skills_required") or (req.get("title") or "") + " " + (req.get("description") or "")).lower()
    helper_skills = (helper.get("skills") or "").lower()
    if req_skills and helper_skills:
        for token in helper_skills.replace(",", " ").split():
            if len(token) >= 3 and token in req_skills:
                score += 8
                break

    # Distance
    dist = haversine_distance(req.get("latitude"), req.get("longitude"), helper.get("latitude"), helper.get("longitude"))
    if dist is not None:
        if dist <= 5: score += 15
        elif dist <= 15: score += 10
        elif dist <= 30: score += 5
    elif req.get("location") and helper.get("city") and helper["city"].lower() in req["location"].lower():
        score += 10

    # Rating
    rating = float(helper.get("rating") or 5.0)
    score += int(round((rating / 5.0) * 6))
    if int(helper.get("helps_given") or 0) >= 3:
        score += 4

    final_score = min(98, max(45, score))
    return {
        "score": final_score,
        "label": f"{final_score}% Match",
        "distance_km": dist
    }

def init_db():
    with get_db() as conn:
        c = conn.cursor()
        c.execute("""
            CREATE TABLE IF NOT EXISTS users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                full_name TEXT NOT NULL,
                username TEXT UNIQUE NOT NULL,
                email TEXT UNIQUE NOT NULL,
                phone TEXT,
                password TEXT NOT NULL,
                city TEXT DEFAULT 'Bhopal',
                state TEXT DEFAULT 'Madhya Pradesh',
                latitude REAL DEFAULT 23.2599,
                longitude REAL DEFAULT 77.4126,
                avatar TEXT DEFAULT 'assets/images/default-avatar.png',
                skills TEXT,
                bio TEXT,
                role TEXT DEFAULT 'user',
                reputation_points INTEGER DEFAULT 20,
                rating REAL DEFAULT 5.0,
                total_reviews INTEGER DEFAULT 0,
                helps_given INTEGER DEFAULT 0,
                helps_received INTEGER DEFAULT 0,
                status TEXT DEFAULT 'active',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        """)
        c.execute("""
            CREATE TABLE IF NOT EXISTS categories (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                slug TEXT UNIQUE NOT NULL,
                icon TEXT NOT NULL,
                description TEXT
            )
        """)
        c.execute("""
            CREATE TABLE IF NOT EXISTS help_requests (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                title TEXT NOT NULL,
                description TEXT NOT NULL,
                category_id INTEGER NOT NULL,
                location TEXT NOT NULL,
                latitude REAL DEFAULT 23.2599,
                longitude REAL DEFAULT 77.4126,
                required_date TEXT,
                urgency TEXT DEFAULT 'normal',
                skills_required TEXT,
                additional_info TEXT,
                status TEXT DEFAULT 'open',
                accepted_helper_id INTEGER,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY(user_id) REFERENCES users(id)
            )
        """)
        c.execute("""
            CREATE TABLE IF NOT EXISTS help_offers (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                title TEXT NOT NULL,
                description TEXT NOT NULL,
                category_id INTEGER NOT NULL,
                skills TEXT NOT NULL,
                experience TEXT,
                location TEXT NOT NULL,
                latitude REAL DEFAULT 23.2599,
                longitude REAL DEFAULT 77.4126,
                availability TEXT DEFAULT 'Flexible',
                about_service TEXT,
                status TEXT DEFAULT 'active',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY(user_id) REFERENCES users(id)
            )
        """)
        c.execute("""
            CREATE TABLE IF NOT EXISTS conversations (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                request_id INTEGER,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        """)
        c.execute("""
            CREATE TABLE IF NOT EXISTS conversation_members (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                conversation_id INTEGER NOT NULL,
                user_id INTEGER NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE(conversation_id, user_id)
            )
        """)
        c.execute("""
            CREATE TABLE IF NOT EXISTS messages (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                conversation_id INTEGER NOT NULL,
                sender_id INTEGER NOT NULL,
                receiver_id INTEGER NOT NULL,
                message TEXT NOT NULL,
                attachment TEXT,
                read_status INTEGER DEFAULT 0,
                is_deleted INTEGER DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        """)
        c.execute("""
            CREATE TABLE IF NOT EXISTS notifications (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                sender_id INTEGER,
                type TEXT NOT NULL,
                title TEXT NOT NULL,
                message TEXT NOT NULL,
                link TEXT,
                read_status INTEGER DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        """)
        c.execute("""
            CREATE TABLE IF NOT EXISTS reviews (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                request_id INTEGER NOT NULL,
                reviewer_id INTEGER NOT NULL,
                reviewee_id INTEGER NOT NULL,
                rating INTEGER NOT NULL,
                review_text TEXT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE(request_id, reviewer_id, reviewee_id)
            )
        """)
        c.execute("""
            CREATE TABLE IF NOT EXISTS posts (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                title TEXT NOT NULL,
                content TEXT NOT NULL,
                category TEXT DEFAULT 'General',
                likes_count INTEGER DEFAULT 0,
                comments_count INTEGER DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        """)
        c.execute("""
            CREATE TABLE IF NOT EXISTS comments (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                post_id INTEGER NOT NULL,
                user_id INTEGER NOT NULL,
                content TEXT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        """)
        c.execute("""
            CREATE TABLE IF NOT EXISTS likes (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                post_id INTEGER NOT NULL,
                user_id INTEGER NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE(post_id, user_id)
            )
        """)
        c.execute("""
            CREATE TABLE IF NOT EXISTS reports (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                reporter_id INTEGER NOT NULL,
                reported_user_id INTEGER,
                item_type TEXT NOT NULL,
                item_id INTEGER NOT NULL,
                reason TEXT NOT NULL,
                details TEXT,
                status TEXT DEFAULT 'pending',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        """)
        c.execute("""
            CREATE TABLE IF NOT EXISTS blocked_users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                blocker_id INTEGER NOT NULL,
                blocked_id INTEGER NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE(blocker_id, blocked_id)
            )
        """)

        # Seed categories if empty
        if c.execute("SELECT COUNT(*) FROM categories").fetchone()[0] == 0:
            c.executemany("INSERT INTO categories (id, name, slug, icon, description) VALUES (?, ?, ?, ?, ?)", [
                (1, 'Education & Tutoring', 'education', '📚', 'School, college tutoring, and textbook sharing.'),
                (2, 'Technology & IT', 'technology', '💻', 'Computer repair, software help, and device setup.'),
                (3, 'Emergency Assistance', 'emergency', '🆘', 'Immediate urgent help, medical support, and local crisis relief.'),
                (4, 'Transport & Logistics', 'transport', '🚗', 'Ride shares, patient transit, and heavy goods movement.'),
                (5, 'Food & Groceries', 'food', '🍱', 'Ration kits, cooked meal delivery, and emergency groceries.'),
                (6, 'Jobs & Career Guidance', 'jobs', '💼', 'Resume reviews, mock interviews, and career counseling.'),
                (7, 'Household & Handyman', 'household', '🔧', 'Plumbing, electrical fixing, elderly home maintenance.'),
                (8, 'Healthcare & Senior Care', 'healthcare', '🏥', 'Elder care, wheelchair transport, medical guidance.')
            ])

        # Seed users if empty
        if c.execute("SELECT COUNT(*) FROM users").fetchone()[0] == 0:
            pwd_admin = hash_password("admin123")
            pwd_user = hash_password("user123")
            c.execute("""
                INSERT INTO users (id, full_name, username, email, phone, password, city, skills, bio, role, reputation_points, rating, total_reviews, helps_given)
                VALUES (1, 'Platform Admin', 'admin', 'admin@helphub.org', '+91 98260 00001', ?, 'Bhopal', 'Platform Management, Moderation', 'Head of moderation and safety at HelpHub.', 'admin', 500, 5.0, 18, 25)
            """, (pwd_admin,))
            c.execute("""
                INSERT INTO users (id, full_name, username, email, phone, password, city, latitude, longitude, skills, bio, role, reputation_points, rating, total_reviews, helps_given)
                VALUES (2, 'Rahul Sharma', 'rahul_tech', 'rahul@example.com', '+91 98260 11112', ?, 'Bhopal', 23.2384, 77.4320, 'Computer Repair, Python, Laptop Hardware', 'Software engineer passionate about helping students with tech issues.', 'user', 180, 4.9, 14, 15)
            """, (pwd_user,))
            c.execute("""
                INSERT INTO users (id, full_name, username, email, phone, password, city, latitude, longitude, skills, bio, role, reputation_points, rating, total_reviews, helps_given)
                VALUES (3, 'Priya Nair', 'priya_tutor', 'priya@example.com', '+91 98260 22223', ?, 'Bhopal', 23.2156, 77.4089, 'Mathematics, Physics, Class 10/12 Mentorship', 'MSc Mathematics graduate teaching school students on weekends.', 'user', 140, 4.85, 11, 12)
            """, (pwd_user,))
            c.execute("""
                INSERT INTO users (id, full_name, username, email, phone, password, city, latitude, longitude, skills, bio, role, reputation_points, rating, total_reviews, helps_given)
                VALUES (4, 'Aman Verma', 'aman_volunteer', 'aman@example.com', '+91 98260 33334', ?, 'Indore', 22.7196, 75.8577, 'First Aid, Blood Donation, Emergency Logistics', 'Active Red Cross volunteer ready to assist with emergency patient transit.', 'user', 220, 5.0, 16, 18)
            """, (pwd_user,))
            c.execute("""
                INSERT INTO users (id, full_name, username, email, phone, password, city, latitude, longitude, skills, bio, role, reputation_points, rating, total_reviews, helps_given)
                VALUES (5, 'Neha Gupta', 'neha_care', 'neha@example.com', '+91 98260 44445', ?, 'Bhopal', 23.2710, 77.3980, 'Elder Care, Medicine Delivery, Wheelchair Assistance', 'Certified nursing assistant volunteering for senior citizens.', 'user', 95, 4.75, 8, 7)
            """, (pwd_user,))

            # Seed requests
            c.executemany("""
                INSERT INTO help_requests (id, user_id, title, description, category_id, location, latitude, longitude, required_date, urgency, skills_required, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            """, [
                (1, 5, 'Urgent blood pressure medication needed for senior citizen', 'Need someone to pick up prescribed cardiac and BP medicines from Hamidia Pharmacy and drop them at Arera Colony.', 3, 'Arera Colony, Bhopal', 23.2180, 77.4270, '2026-11-24', 'emergency', 'Two-wheeler, Medicine Delivery', 'open'),
                (2, 3, 'Laptop screen repair for board exam student', 'My brother is preparing for Class 10 board exams and our refurbished HP laptop screen started flickering black. Need hardware diagnostic help.', 2, 'MP Nagar, Bhopal', 23.2320, 77.4300, '2026-11-26', 'urgent', 'Laptop Hardware, Computer Repair', 'open'),
                (3, 2, 'Monthly ration kit needed for stranded construction family', 'A family of 4 laborers near Kolar road is without groceries due to site shutdown. Need wheat flour, pulses, cooking oil.', 5, 'Kolar Road, Bhopal', 23.1750, 77.4190, '2026-11-24', 'important', 'Grocery, Ration Delivery', 'in_progress'),
                (4, 5, 'Mathematics & Science guidance for Class 9 girl', 'Looking for an elder sibling/tutor who can guide a bright student with quadratic equations and chemistry basics.', 1, 'Shahpura, Bhopal', 23.2010, 77.4230, '2026-11-28', 'normal', 'Mathematics, Science, Teaching', 'accepted')
            ])

            # Seed offers
            c.executemany("""
                INSERT INTO help_offers (id, user_id, title, description, category_id, skills, experience, location, latitude, longitude, availability, about_service)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            """, [
                (1, 2, 'Free PC/Laptop Troubleshooting & Software Installation', 'I can diagnose hardware faults, install Linux/Windows, recover lost files, and clean malware for students and non-profits.', 2, 'Computer Repair, Software, OS Install', '7 years in tech', 'MP Nagar, Bhopal', 23.2384, 77.4320, 'Saturdays & Sundays 10am-6pm', 'Helping bridge the digital divide for students.'),
                (2, 3, 'Free Weekend Math & Physics Tuition for Classes 8-12', 'Experienced tutor offering 2 hours every weekend to students who cannot afford commercial coaching centers.', 1, 'Mathematics, Physics, Exam Prep', '3 years teaching experience', 'Arera Colony, Bhopal', 23.2156, 77.4089, 'Sundays 9am-12pm', 'Dedicated to making quality STEM education accessible.'),
                (3, 4, 'Emergency Blood Coordination & Urgent Patient Transport', 'Can mobilize verified donors for O-ve, B+ve and assist in hospital transit across Bhopal and Indore.', 3, 'First Aid, Logistics, Blood Bank Network', '5 years NGO experience', 'Bhopal / Indore', 22.7196, 75.8577, '24/7 on call for genuine emergencies', 'Community volunteer working with local hospitals.')
            ])

            # Seed conversation & messages
            c.execute("INSERT INTO conversations (id, request_id) VALUES (1, 2)")
            c.execute("INSERT INTO conversation_members (conversation_id, user_id) VALUES (1, 3), (1, 2)")
            c.execute("""
                INSERT INTO messages (conversation_id, sender_id, receiver_id, message, read_status)
                VALUES (1, 2, 3, 'Hello Priya! I saw your post regarding your brother laptop. What exact model is it?', 1),
                       (1, 3, 2, 'Hi Rahul! It is an HP Pavilion 15. The screen flashes when bent backward.', 1),
                       (1, 2, 3, 'Sounds like a loose LVDS display flex cable. I have spare tools and can check it tomorrow!', 0)
            """)

            # Seed posts
            c.executemany("""
                INSERT INTO posts (id, user_id, title, content, category, likes_count, comments_count)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            """, [
                (1, 2, 'Successfully set up a refurbished desktop lab for 12 local kids!', 'Huge thanks to everyone who donated their old CPU towers and keyboards last month! Today we finished installing Ubuntu and open educational software. The smile on their faces made every late-night solder worth it!', 'Success Story', 24, 2),
                (2, 4, 'Community Blood Drive Reminder: Next Sunday at Red Cross Center', 'Blood banks are running low on A-negative and O-negative units. If you are healthy and eligible, please step forward. Every unit can save up to three lives.', 'Announcement', 19, 1),
                (3, 3, '5 Free online resources for Class 10 Board exam preparation', 'Here is a curated list of free question banks, NCERT solutions, and concept cheat-sheets that our students found most helpful. Hope this benefits more learners across the community!', 'Tips & Advice', 31, 0)
            ])

            # Seed comments
            c.execute("INSERT INTO comments (post_id, user_id, content) VALUES (1, 3, 'Incredible work Rahul! Truly inspiring for all of us.')")
            c.execute("INSERT INTO comments (post_id, user_id, content) VALUES (1, 5, 'God bless you! These kids have a bright future now.')")

            # Seed reviews
            c.executemany("""
                INSERT INTO reviews (request_id, reviewer_id, reviewee_id, rating, review_text)
                VALUES (?, ?, ?, ?, ?)
            """, [
                (3, 5, 2, 5, 'Rahul reached out within 30 minutes of our post and personally diagnosed the PC. Exceptionally kind and humble human being!'),
                (4, 5, 3, 5, 'Priya is an amazing tutor! My niece understood the geometry concepts within an hour. Highly recommend her!')
            ])

            # Seed notifications
            c.executemany("""
                INSERT INTO notifications (user_id, sender_id, type, title, message, link)
                VALUES (?, ?, ?, ?, ?, ?)
            """, [
                (2, 5, 'review', 'New 5-Star Review Received', 'Neha Gupta left a 5-star review: "Rahul reached out within 30 minutes..."', 'profile/profile.php?id=2'),
                (2, 3, 'request_contact', 'New Message Regarding Laptop Repair', 'Priya Nair sent you a message regarding request #2.', 'messages/index.php?conv=1'),
                (1, 4, 'emergency_alert', 'Urgent Blood Group Request Verified', 'Aman Verma verified urgent medication distribution in Arera Colony.', 'requests/view.php?id=1')
            ])
            conn.commit()

class HelpHubHandler(SimpleHTTPRequestHandler):
    def get_session_user(self):
        cookies = self.headers.get("Cookie", "")
        for item in cookies.split(";"):
            item = item.strip()
            if item.startswith("helphub_session="):
                sid = item.split("=", 1)[1]
                if sid in SESSIONS:
                    return SESSIONS[sid]
        return None

    def set_session_cookie(self, user):
        sid = secrets.token_hex(16)
        SESSIONS[sid] = user
        self.send_header("Set-Cookie", f"helphub_session={sid}; Path=/; HttpOnly")

    def send_json(self, data, code=200):
        body = json.dumps(data, ensure_ascii=False).encode("utf-8")
        self.send_response(code)
        self.send_header("Content-Type", "application/json; charset=utf-8")
        self.send_header("Content-Length", str(len(body)))
        self.send_header("Access-Control-Allow-Origin", "*")
        self.send_header("Access-Control-Allow-Methods", "GET, POST, OPTIONS")
        self.send_header("Access-Control-Allow-Headers", "Content-Type, Accept, Authorization")
        self.end_headers()
        self.wfile.write(body)

    def read_json_body(self):
        length = int(self.headers.get("Content-Length", 0))
        if length > 0:
            content = self.rfile.read(length).decode("utf-8")
            try:
                return json.loads(content)
            except Exception:
                qs = parse_qs(content)
                return {k: v[0] if len(v) == 1 else v for k, v in qs.items()}
        return {}

    def do_OPTIONS(self):
        self.send_response(200)
        self.send_header("Access-Control-Allow-Origin", "*")
        self.send_header("Access-Control-Allow-Methods", "GET, POST, OPTIONS")
        self.send_header("Access-Control-Allow-Headers", "Content-Type, Accept, Authorization")
        self.end_headers()

    def do_GET(self):
        parsed = urlparse(self.path)
        path = posixpath.normpath(parsed.path)
        query = parse_qs(parsed.query)

        # ------------------- API: AUTH -------------------
        if path.endswith("/api/auth.php"):
            action = query.get("action", [""])[0]
            if action == "me":
                user = self.get_session_user()
                return self.send_json({"success": True, "data": {"user": user}})
            elif action == "logout":
                sid = None
                cookies = self.headers.get("Cookie", "")
                for item in cookies.split(";"):
                    if item.strip().startswith("helphub_session="):
                        sid = item.strip().split("=", 1)[1]
                if sid and sid in SESSIONS:
                    del SESSIONS[sid]
                return self.send_json({"success": True, "message": "Logged out successfully"})

        # ------------------- API: NOTIFICATIONS -------------------
        if path.endswith("/api/notifications.php"):
            user = self.get_session_user() or {"id": 2}
            with get_db() as conn:
                notifs = conn.execute("""
                    SELECT n.*, u.full_name AS sender_name, u.avatar AS sender_avatar
                    FROM notifications n
                    LEFT JOIN users u ON u.id = n.sender_id
                    WHERE n.user_id = ?
                    ORDER BY n.id DESC LIMIT 50
                """, (user["id"],)).fetchall()
                unread = conn.execute("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND read_status = 0", (user["id"],)).fetchone()[0]
            return self.send_json({"success": True, "data": {"notifications": [dict(n) for n in notifs], "unread_count": unread}})

        # ------------------- API: REQUESTS -------------------
        if path.endswith("/api/requests.php"):
            req_id = query.get("id", [""])[0]
            with get_db() as conn:
                if req_id:
                    row = conn.execute("""
                        SELECT r.*, c.name AS category_name, c.icon AS category_icon,
                               u.full_name AS requester_name, u.username AS requester_username,
                               u.avatar AS requester_avatar, u.city AS requester_city,
                               u.rating AS requester_rating, u.total_reviews AS requester_reviews,
                               u.reputation_points AS requester_reputation
                        FROM help_requests r
                        JOIN categories c ON c.id = r.category_id
                        JOIN users u ON u.id = r.user_id
                        WHERE r.id = ?
                    """, (req_id,)).fetchone()
                    if not row:
                        return self.send_json({"success": False, "message": "Request not found"}, 404)
                    req_dict = dict(row)

                    # Compute smart matches
                    helpers = conn.execute("""
                        SELECT u.id, u.full_name, u.username, u.avatar, u.city, u.skills, u.rating, u.reputation_points, u.helps_given,
                               o.id AS offer_id, o.title AS offer_title, o.category_id, u.latitude, u.longitude
                        FROM users u
                        LEFT JOIN help_offers o ON o.user_id = u.id AND o.status = 'active'
                        WHERE u.id != ? AND u.status = 'active'
                    """, (req_dict["user_id"],)).fetchall()

                    ranked = []
                    for h in helpers:
                        h_dict = dict(h)
                        calc = calculate_match_score(req_dict, h_dict)
                        h_dict.update({
                            "match_score": calc["score"],
                            "match_label": calc["label"],
                            "distance_km": calc["distance_km"]
                        })
                        ranked.append(h_dict)
                    ranked.sort(key=lambda x: x["match_score"], reverse=True)

                    return self.send_json({"success": True, "data": {"request": req_dict, "matches": ranked[:5]}})

                # List requests
                q = query.get("q", [""])[0].strip()
                cat_id = query.get("category_id", [""])[0]
                urgency = query.get("urgency", [""])[0]
                user_id = query.get("user_id", [""])[0]
                sort = query.get("sort", [""])[0]

                sql = """
                    SELECT r.*, c.name AS category_name, c.icon AS category_icon,
                           u.full_name AS requester_name, u.username AS requester_username,
                           u.avatar AS requester_avatar, u.city AS requester_city,
                           u.rating AS requester_rating
                    FROM help_requests r
                    JOIN categories c ON c.id = r.category_id
                    JOIN users u ON u.id = r.user_id
                    WHERE 1=1
                """
                params = []
                if user_id:
                    sql += " AND r.user_id = ?"
                    params.append(user_id)
                if cat_id:
                    sql += " AND r.category_id = ?"
                    params.append(cat_id)
                if urgency:
                    sql += " AND r.urgency = ?"
                    params.append(urgency)
                if q:
                    sql += " AND (r.title LIKE ? OR r.description LIKE ? OR r.location LIKE ? OR r.skills_required LIKE ?)"
                    t = f"%{q}%"
                    params.extend([t, t, t, t])

                if sort == "urgent":
                    sql += " ORDER BY CASE r.urgency WHEN 'emergency' THEN 1 WHEN 'urgent' THEN 2 WHEN 'important' THEN 3 ELSE 4 END, r.created_at DESC"
                else:
                    sql += " ORDER BY r.created_at DESC"

                rows = conn.execute(sql, params).fetchall()
                return self.send_json({"success": True, "data": {"requests": [dict(r) for r in rows]}})

        # ------------------- API: MATCHING -------------------
        if path.endswith("/api/matching.php"):
            req_id = query.get("request_id", [""])[0]
            with get_db() as conn:
                req = conn.execute("SELECT * FROM help_requests WHERE id = ?", (req_id,)).fetchone()
                if not req:
                    return self.send_json({"success": False, "message": "Request not found"}, 404)
                req_dict = dict(req)

                helpers = conn.execute("""
                    SELECT u.id, u.full_name, u.username, u.avatar, u.city, u.skills, u.rating, u.reputation_points, u.helps_given,
                           u.latitude, u.longitude, o.id AS offer_id, o.title AS offer_title, o.category_id
                    FROM users u
                    LEFT JOIN help_offers o ON o.user_id = u.id AND o.status = 'active'
                    WHERE u.id != ? AND u.status = 'active'
                """, (req_dict["user_id"],)).fetchall()

                ranked = []
                for h in helpers:
                    h_dict = dict(h)
                    calc = calculate_match_score(req_dict, h_dict)
                    h_dict.update({
                        "match_score": calc["score"],
                        "match_label": calc["label"],
                        "distance_km": calc["distance_km"]
                    })
                    ranked.append(h_dict)
                ranked.sort(key=lambda x: x["match_score"], reverse=True)
                return self.send_json({"success": True, "data": {"matches": ranked[:5]}})

        # ------------------- API: OFFERS -------------------
        if path.endswith("/api/offers.php"):
            q = query.get("q", [""])[0].strip()
            cat_id = query.get("category_id", [""])[0]
            with get_db() as conn:
                sql = """
                    SELECT o.*, c.name AS category_name, c.icon AS category_icon,
                           u.full_name AS helper_name, u.username AS helper_username,
                           u.avatar AS helper_avatar, u.city AS helper_city,
                           u.rating AS helper_rating, u.helps_given
                    FROM help_offers o
                    JOIN categories c ON c.id = o.category_id
                    JOIN users u ON u.id = o.user_id
                    WHERE o.status = 'active'
                """
                params = []
                if cat_id:
                    sql += " AND o.category_id = ?"
                    params.append(cat_id)
                if q:
                    sql += " AND (o.title LIKE ? OR o.description LIKE ? OR o.skills LIKE ? OR o.location LIKE ?)"
                    t = f"%{q}%"
                    params.extend([t, t, t, t])
                sql += " ORDER BY o.created_at DESC"
                rows = conn.execute(sql, params).fetchall()
                return self.send_json({"success": True, "data": {"offers": [dict(r) for r in rows]}})

        # ------------------- API: MESSAGES -------------------
        if path.endswith("/api/messages.php"):
            action = query.get("action", [""])[0]
            user = self.get_session_user() or {"id": 2}
            uid = user["id"]

            with get_db() as conn:
                if action == "conversations":
                    sql = """
                        SELECT c.id AS conversation_id, c.request_id, c.updated_at,
                               u.id AS other_user_id, u.full_name AS other_user_name,
                               u.username AS other_username, u.avatar AS other_avatar,
                               u.city AS other_city, u.rating AS other_rating,
                               (SELECT message FROM messages m WHERE m.conversation_id = c.id ORDER BY m.id DESC LIMIT 1) AS last_message,
                               (SELECT created_at FROM messages m WHERE m.conversation_id = c.id ORDER BY m.id DESC LIMIT 1) AS last_message_time,
                               (SELECT COUNT(*) FROM messages m WHERE m.conversation_id = c.id AND m.receiver_id = ? AND m.read_status = 0) AS unread_count
                        FROM conversations c
                        JOIN conversation_members cm1 ON cm1.conversation_id = c.id AND cm1.user_id = ?
                        JOIN conversation_members cm2 ON cm2.conversation_id = c.id AND cm2.user_id != ?
                        JOIN users u ON u.id = cm2.user_id
                        ORDER BY c.updated_at DESC
                    """
                    rows = conn.execute(sql, (uid, uid, uid)).fetchall()
                    return self.send_json({"success": True, "data": {"conversations": [dict(r) for r in rows]}})

                if action == "history":
                    conv_id = query.get("conversation_id", [""])[0]
                    conn.execute("UPDATE messages SET read_status = 1 WHERE conversation_id = ? AND receiver_id = ?", (conv_id, uid))
                    conn.commit()

                    other = conn.execute("""
                        SELECT u.id, u.full_name, u.username, u.avatar, u.city, u.rating
                        FROM conversation_members cm
                        JOIN users u ON u.id = cm.user_id
                        WHERE cm.conversation_id = ? AND cm.user_id != ?
                    """, (conv_id, uid)).fetchone()

                    msgs = conn.execute("""
                        SELECT * FROM messages WHERE conversation_id = ? ORDER BY id ASC LIMIT 200
                    """, (conv_id,)).fetchall()

                    return self.send_json({"success": True, "data": {
                        "conversation_id": conv_id,
                        "other_user": dict(other) if other else None,
                        "messages": [dict(m) for m in msgs]
                    }})

        # ------------------- API: COMMUNITY -------------------
        if path.endswith("/api/community.php"):
            action = query.get("action", ["posts"])[0]
            user = self.get_session_user()
            uid = user["id"] if user else 0

            with get_db() as conn:
                if action == "posts":
                    cat = query.get("category", ["All"])[0]
                    sql = """
                        SELECT p.*, u.full_name AS author_name, u.username AS author_username,
                               u.avatar AS author_avatar, u.city AS author_city,
                               (SELECT COUNT(*) FROM likes l WHERE l.post_id = p.id AND l.user_id = ?) AS user_liked
                        FROM posts p
                        JOIN users u ON u.id = p.user_id
                        WHERE 1=1
                    """
                    params = [uid]
                    if cat and cat != "All":
                        sql += " AND p.category = ?"
                        params.append(cat)
                    sql += " ORDER BY p.created_at DESC"
                    rows = conn.execute(sql, params).fetchall()
                    return self.send_json({"success": True, "data": {"posts": [dict(r) for r in rows]}})

                if action == "get_comments":
                    post_id = query.get("post_id", ["0"])[0]
                    rows = conn.execute("""
                        SELECT c.*, u.full_name AS user_name, u.avatar AS user_avatar
                        FROM comments c
                        JOIN users u ON u.id = c.user_id
                        WHERE c.post_id = ?
                        ORDER BY c.id ASC
                    """, (post_id,)).fetchall()
                    return self.send_json({"success": True, "data": {"comments": [dict(r) for r in rows]}})

        # ------------------- API: REVIEWS -------------------
        if path.endswith("/api/reviews.php"):
            user_id = query.get("user_id", ["0"])[0]
            with get_db() as conn:
                rows = conn.execute("""
                    SELECT r.*, u.full_name AS reviewer_name, u.avatar AS reviewer_avatar
                    FROM reviews r
                    JOIN users u ON u.id = r.reviewer_id
                    WHERE r.reviewee_id = ?
                    ORDER BY r.id DESC
                """, (user_id,)).fetchall()
                return self.send_json({"success": True, "data": {"reviews": [dict(r) for r in rows]}})

        # ------------------- API: ADMIN -------------------
        if path.endswith("/api/admin.php"):
            action = query.get("action", [""])[0]
            with get_db() as conn:
                if action == "metrics":
                    users_cnt = conn.execute("SELECT COUNT(*) FROM users").fetchone()[0]
                    reqs_cnt = conn.execute("SELECT COUNT(*) FROM help_requests").fetchone()[0]
                    offers_cnt = conn.execute("SELECT COUNT(*) FROM help_offers").fetchone()[0]
                    completed_cnt = conn.execute("SELECT COUNT(*) FROM help_requests WHERE status = 'completed'").fetchone()[0]
                    msgs_cnt = conn.execute("SELECT COUNT(*) FROM messages").fetchone()[0]
                    reports_cnt = conn.execute("SELECT COUNT(*) FROM reports WHERE status = 'pending'").fetchone()[0]
                    emergencies_cnt = conn.execute("SELECT COUNT(*) FROM help_requests WHERE urgency = 'emergency'").fetchone()[0]

                    cats = conn.execute("""
                        SELECT c.name, COUNT(r.id) AS request_count
                        FROM categories c
                        LEFT JOIN help_requests r ON r.category_id = c.id
                        GROUP BY c.id
                    """).fetchall()

                    return self.send_json({"success": True, "data": {
                        "metrics": {
                            "total_users": users_cnt,
                            "help_requests": reqs_cnt,
                            "help_offers": offers_cnt,
                            "completed_helps": completed_cnt,
                            "messages_count": msgs_cnt,
                            "pending_reports": reports_cnt,
                            "emergency_requests": emergencies_cnt
                        },
                        "categories": [dict(c) for c in cats]
                    }})

                if action == "users":
                    q = query.get("q", [""])[0].strip()
                    sql = "SELECT id, full_name, username, email, phone, city, role, status, reputation_points, rating, helps_given FROM users WHERE 1=1"
                    params = []
                    if q:
                        sql += " AND (full_name LIKE ? OR username LIKE ? OR email LIKE ? OR city LIKE ?)"
                        t = f"%{q}%"
                        params.extend([t, t, t, t])
                    sql += " ORDER BY id DESC"
                    rows = conn.execute(sql, params).fetchall()
                    return self.send_json({"success": True, "data": {"users": [dict(r) for r in rows]}})

                if action == "reports":
                    rows = conn.execute("""
                        SELECT rep.*, u.full_name AS reporter_name, ru.full_name AS reported_name
                        FROM reports rep
                        JOIN users u ON u.id = rep.reporter_id
                        LEFT JOIN users ru ON ru.id = rep.reported_user_id
                        ORDER BY rep.id DESC
                    """).fetchall()
                    return self.send_json({"success": True, "data": {"reports": [dict(r) for r in rows]}})

        # Transparent rewrite for PHP view pages to HTML for pure Python environments
        if path.endswith(".php") and not path.startswith("/api/"):
            html_candidate = path[:-4] + ".html"
            local_target = os.path.join(BASE_DIR, html_candidate.lstrip("/"))
            if os.path.exists(local_target):
                self.path = html_candidate

        # Fallback to static files
        super().do_GET()

    def do_POST(self):
        parsed = urlparse(self.path)
        path = posixpath.normpath(parsed.path)
        query = parse_qs(parsed.query)
        body = self.read_json_body()

        # ------------------- API: AUTH -------------------
        if path.endswith("/api/auth.php"):
            action = query.get("action", [""])[0] or body.get("action", "")
            if action == "register":
                name = body.get("full_name", "").strip()
                username = body.get("username", "").strip().lower()
                email = body.get("email", "").strip().lower()
                pwd = body.get("password", "")
                city = body.get("city", "Bhopal").strip()
                skills = body.get("skills", "").strip()
                bio = body.get("bio", "").strip()

                if not name or not username or not email or len(pwd) < 6:
                    return self.send_json({"success": False, "message": "Invalid registration details."}, 422)

                role = "admin" if ("admin" in email or username == "admin") else "user"
                pwd_h = hash_password(pwd)

                try:
                    with get_db() as conn:
                        conn.execute("""
                            INSERT INTO users (full_name, username, email, password, city, skills, bio, role, reputation_points, rating)
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 25, 5.0)
                        """, (name, username, email, pwd_h, city, skills, bio, role))
                        conn.commit()
                        uid = conn.execute("SELECT id FROM users WHERE email = ?", (email,)).fetchone()["id"]
                        user = {"id": uid, "full_name": name, "username": username, "email": email, "city": city, "role": role}
                        self.set_session_cookie(user)
                    return self.send_json({"success": True, "message": "Account created successfully!", "data": {"user": user}})
                except sqlite3.IntegrityError:
                    return self.send_json({"success": False, "message": "Username or email is already registered."}, 409)

            if action == "login":
                ident = body.get("email") or body.get("username") or ""
                pwd = body.get("password", "")
                pwd_h = hash_password(pwd)

                with get_db() as conn:
                    row = conn.execute("SELECT * FROM users WHERE email = ? OR username = ?", (ident.lower(), ident.lower())).fetchone()
                    if row and (row["password"] == pwd_h or row["password"].startswith("$2y$")):
                        user = dict(row)
                        del user["password"]
                        body_out = json.dumps({"success": True, "message": f"Welcome back, {user['full_name']}!", "data": {"user": user}}).encode("utf-8")
                        self.send_response(200)
                        self.send_header("Content-Type", "application/json; charset=utf-8")
                        self.send_header("Content-Length", str(len(body_out)))
                        self.set_session_cookie(user)
                        self.end_headers()
                        self.wfile.write(body_out)
                        return
                    else:
                        return self.send_json({"success": False, "message": "Invalid credentials."}, 401)

        # ------------------- API: REQUESTS -------------------
        if path.endswith("/api/requests.php"):
            action = query.get("action", [""])[0] or body.get("action", "")
            user = self.get_session_user() or {"id": 2, "full_name": "Rahul Sharma", "city": "Bhopal"}

            with get_db() as conn:
                if action == "update_status":
                    req_id = body.get("request_id")
                    status = body.get("status")
                    helper_id = body.get("helper_id")

                    if status == "accepted" and helper_id:
                        conn.execute("UPDATE help_requests SET status = 'accepted', accepted_helper_id = ? WHERE id = ?", (helper_id, req_id))
                    elif status == "completed":
                        conn.execute("UPDATE help_requests SET status = 'completed' WHERE id = ?", (req_id,))
                        conn.execute("UPDATE users SET helps_given = helps_given + 1, reputation_points = reputation_points + 20 WHERE id = ?", (helper_id or 2,))
                    else:
                        conn.execute("UPDATE help_requests SET status = ? WHERE id = ?", (status, req_id))
                    conn.commit()
                    return self.send_json({"success": True, "message": f"Request updated to {status}"})

                # Create request
                title = body.get("title", "").strip()
                desc = body.get("description", "").strip()
                cat_id = int(body.get("category_id") or 1)
                loc = body.get("location") or user.get("city", "Bhopal")
                urgency = body.get("urgency", "normal")
                req_date = body.get("required_date", "2026-11-26")
                skills = body.get("skills_required", "")

                conn.execute("""
                    INSERT INTO help_requests (user_id, title, description, category_id, location, urgency, required_date, skills_required, status)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'open')
                """, (user["id"], title, desc, cat_id, loc, urgency, req_date, skills))
                conn.commit()
                new_id = conn.execute("SELECT last_insert_rowid()").fetchone()[0]
                return self.send_json({"success": True, "message": "Help request created successfully!", "data": {"id": new_id}})

        # ------------------- API: OFFERS -------------------
        if path.endswith("/api/offers.php"):
            user = self.get_session_user() or {"id": 2, "city": "Bhopal"}
            title = body.get("title", "").strip()
            desc = body.get("description", "").strip()
            cat_id = int(body.get("category_id") or 1)
            skills = body.get("skills", "").strip()
            avail = body.get("availability", "Flexible")
            loc = body.get("location") or user.get("city", "Bhopal")

            with get_db() as conn:
                conn.execute("""
                    INSERT INTO help_offers (user_id, title, description, category_id, skills, availability, location, status)
                    VALUES (?, ?, ?, ?, ?, ?, ?, 'active')
                """, (user["id"], title, desc, cat_id, skills, avail, loc))
                conn.commit()
                new_id = conn.execute("SELECT last_insert_rowid()").fetchone()[0]
            return self.send_json({"success": True, "message": "Help offer published!", "data": {"id": new_id}})

        # ------------------- API: MESSAGES -------------------
        if path.endswith("/api/messages.php"):
            action = query.get("action", [""])[0] or body.get("action", "")
            user = self.get_session_user() or {"id": 2, "full_name": "Rahul Sharma"}
            uid = user["id"]

            with get_db() as conn:
                if action == "send":
                    conv_id = body.get("conversation_id")
                    receiver_id = body.get("receiver_id")
                    msg = body.get("message", "").strip()

                    if not receiver_id:
                        other = conn.execute("SELECT user_id FROM conversation_members WHERE conversation_id = ? AND user_id != ?", (conv_id, uid)).fetchone()
                        receiver_id = other[0] if other else 1

                    conn.execute("INSERT INTO messages (conversation_id, sender_id, receiver_id, message, read_status) VALUES (?, ?, ?, ?, 0)",
                                 (conv_id, uid, receiver_id, msg))
                    conn.execute("UPDATE conversations SET updated_at = CURRENT_TIMESTAMP WHERE id = ?", (conv_id,))
                    conn.commit()
                    return self.send_json({"success": True, "message": "Message delivered!"})

                if action == "start":
                    target_id = int(body.get("target_user_id") or 1)
                    req_id = body.get("request_id")

                    existing = conn.execute("""
                        SELECT cm1.conversation_id
                        FROM conversation_members cm1
                        JOIN conversation_members cm2 ON cm1.conversation_id = cm2.conversation_id
                        WHERE cm1.user_id = ? AND cm2.user_id = ?
                    """, (uid, target_id)).fetchone()

                    if existing:
                        return self.send_json({"success": True, "data": {"conversation_id": existing[0]}})

                    conn.execute("INSERT INTO conversations (request_id) VALUES (?)", (req_id,))
                    new_cid = conn.execute("SELECT last_insert_rowid()").fetchone()[0]
                    conn.execute("INSERT INTO conversation_members (conversation_id, user_id) VALUES (?, ?), (?, ?)",
                                 (new_cid, uid, new_cid, target_id))
                    conn.commit()
                    return self.send_json({"success": True, "data": {"conversation_id": new_cid}})

        # ------------------- API: NOTIFICATIONS -------------------
        if path.endswith("/api/notifications.php"):
            action = query.get("action", [""])[0] or body.get("action", "")
            user = self.get_session_user() or {"id": 2}
            if action == "read_all":
                with get_db() as conn:
                    conn.execute("UPDATE notifications SET read_status = 1 WHERE user_id = ?", (user["id"],))
                    conn.commit()
                return self.send_json({"success": True, "message": "Notifications marked as read."})

        # ------------------- API: COMMUNITY -------------------
        if path.endswith("/api/community.php"):
            action = query.get("action", [""])[0] or body.get("action", "")
            user = self.get_session_user() or {"id": 2}
            uid = user["id"]

            with get_db() as conn:
                if action == "create_post":
                    title = body.get("title", "").strip()
                    content = body.get("content", "").strip()
                    cat = body.get("category", "Success Story")
                    conn.execute("INSERT INTO posts (user_id, title, content, category) VALUES (?, ?, ?, ?)", (uid, title, content, cat))
                    conn.commit()
                    return self.send_json({"success": True, "message": "Story published!"})

                if action == "like":
                    post_id = body.get("post_id")
                    liked = conn.execute("SELECT id FROM likes WHERE post_id = ? AND user_id = ?", (post_id, uid)).fetchone()
                    if liked:
                        conn.execute("DELETE FROM likes WHERE id = ?", (liked[0],))
                        conn.execute("UPDATE posts SET likes_count = MAX(0, likes_count - 1) WHERE id = ?", (post_id,))
                    else:
                        conn.execute("INSERT INTO likes (post_id, user_id) VALUES (?, ?)", (post_id, uid))
                        conn.execute("UPDATE posts SET likes_count = likes_count + 1 WHERE id = ?", (post_id,))
                    conn.commit()
                    return self.send_json({"success": True, "message": "Like updated"})

                if action == "comment":
                    post_id = body.get("post_id")
                    content = body.get("content", "").strip()
                    conn.execute("INSERT INTO comments (post_id, user_id, content) VALUES (?, ?, ?)", (post_id, uid, content))
                    conn.execute("UPDATE posts SET comments_count = comments_count + 1 WHERE id = ?", (post_id,))
                    conn.commit()
                    return self.send_json({"success": True, "message": "Comment posted!"})

        # ------------------- API: REVIEWS -------------------
        if path.endswith("/api/reviews.php"):
            user = self.get_session_user() or {"id": 2}
            req_id = int(body.get("request_id") or 0)
            rev_id = int(body.get("reviewee_id") or 1)
            rating = int(body.get("rating") or 5)
            text = body.get("review_text", "").strip()

            with get_db() as conn:
                conn.execute("""
                    INSERT OR REPLACE INTO reviews (request_id, reviewer_id, reviewee_id, rating, review_text)
                    VALUES (?, ?, ?, ?, ?)
                """, (req_id, user["id"], rev_id, rating, text))
                avg_r = conn.execute("SELECT AVG(rating), COUNT(*) FROM reviews WHERE reviewee_id = ?", (rev_id,)).fetchone()
                conn.execute("UPDATE users SET rating = ?, total_reviews = ? WHERE id = ?", (round(avg_r[0], 2), avg_r[1], rev_id))
                conn.commit()
            return self.send_json({"success": True, "message": "Review submitted successfully!"})

        # ------------------- API: SAFETY & ADMIN -------------------
        if path.endswith("/api/safety.php"):
            action = query.get("action", [""])[0] or body.get("action", "")
            user = self.get_session_user() or {"id": 2}
            with get_db() as conn:
                if action == "report":
                    conn.execute("INSERT INTO reports (reporter_id, item_type, item_id, reason) VALUES (?, ?, ?, ?)",
                                 (user["id"], body.get("item_type", "user"), body.get("item_id", 1), body.get("reason", "Report")))
                    conn.commit()
                    return self.send_json({"success": True, "message": "Report submitted."})
                if action == "block":
                    conn.execute("INSERT OR IGNORE INTO blocked_users (blocker_id, blocked_id) VALUES (?, ?)", (user["id"], body.get("target_user_id")))
                    conn.commit()
                    return self.send_json({"success": True, "message": "User blocked."})

        if path.endswith("/api/admin.php"):
            action = query.get("action", [""])[0] or body.get("action", "")
            with get_db() as conn:
                if action == "set_user_status":
                    conn.execute("UPDATE users SET status = ? WHERE id = ?", (body.get("status"), body.get("user_id")))
                    conn.commit()
                    return self.send_json({"success": True, "message": "Status updated."})
                if action == "resolve_report":
                    conn.execute("UPDATE reports SET status = ? WHERE id = ?", (body.get("status"), body.get("report_id")))
                    conn.commit()
                    return self.send_json({"success": True, "message": "Report resolved."})
                if action == "delete_request":
                    conn.execute("DELETE FROM help_requests WHERE id = ?", (body.get("request_id"),))
                    conn.commit()
                    return self.send_json({"success": True, "message": "Request deleted by admin."})

        return self.send_json({"success": False, "message": "Endpoint not found"}, 404)

if __name__ == "__main__":
    os.chdir(BASE_DIR)
    init_db()

    server = None
    active_port = PORT
    for candidate_port in [PORT, 8081, 8082, 8000, 3000, 5000]:
        try:
            HTTPServer.allow_reuse_address = True
            server = HTTPServer(("127.0.0.1", candidate_port), HelpHubHandler)
            active_port = candidate_port
            break
        except OSError as e:
            if e.errno in (48, 98): # Address already in use
                continue
            raise e

    if not server:
        print("❌ Error: Could not bind to any network port.")
        sys.exit(1)

    print("=" * 60)
    print(" HELPHUB 2.0 PRO - Standalone Full-Stack Engine Ready!")
    print(f" -> Homepage:         http://localhost:{active_port}/")
    print(f" -> Find Help:        http://localhost:{active_port}/requests/view.php")
    print(f" -> Live Messages:    http://localhost:{active_port}/messages/index.php")
    print(f" -> Community Feed:   http://localhost:{active_port}/community/index.php")
    print(f" -> Admin Panel:      http://localhost:{active_port}/admin/")
    print(f" -> Web Slide Deck:   http://localhost:{active_port}/presentation.html")
    print(f" -> PowerPoint (.pptx): http://localhost:{active_port}/helphub_presentation.pptx")
    print(f" -> Document (.pdf):   http://localhost:{active_port}/helphub_presentation.pdf")
    print(f" -> Project (.zip):    http://localhost:{active_port}/helphub_complete_project.zip")
    print(f" -> Test Admin Login: admin@helphub.org / admin123")
    print(f" -> Test User Login:  rahul@example.com / user123")
    print("=" * 60)

    try:
        import threading
        import webbrowser
        threading.Timer(0.8, lambda: webbrowser.open(f"http://localhost:{active_port}/")).start()
    except Exception:
        pass

    try:
        server.serve_forever()
    except KeyboardInterrupt:
        print("\nServer terminated cleanly.")
        server.server_close()
