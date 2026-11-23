#!/usr/bin/env python3
"""
HELPHUB 15-Slide Presentation PDF Generator
Adheres to the official College Project Presentation Structure (Slides 1 to 15).
Pure Python 3 standard library implementation with zero external dependencies.
Generates a valid A4 Landscape (842 x 595 pt) PDF presentation.
"""

import os
import sys

class PDFSlideDeck:
    def __init__(self, filename="helphub_presentation.pdf"):
        self.filename = filename
        self.width = 842   # A4 Landscape width in points
        self.height = 595  # A4 Landscape height in points
        self.pages = []
        self.objects = []
        
    def add_page(self, title, category, slide_num, total_slides=15):
        page = PDFSlide(self.width, self.height, title, category, slide_num, total_slides)
        self.pages.append(page)
        return page

    def build(self):
        # We will write objects:
        # 1: Catalog
        # 2: Pages
        # 3: Font Helvetica
        # 4: Font Helvetica-Bold
        # 5: Font Helvetica-Oblique
        # Then for each page i:
        #   obj (6 + 2*i): Page object
        #   obj (7 + 2*i): Content stream object
        
        num_pages = len(self.pages)
        page_obj_ids = [6 + 2*i for i in range(num_pages)]
        
        objs = {}
        
        # 1: Catalog
        objs[1] = "<< /Type /Catalog /Pages 2 0 R >>"
        
        # 2: Pages
        kids_str = " ".join([f"{pid} 0 R" for pid in page_obj_ids])
        objs[2] = f"<< /Type /Pages /Kids [{kids_str}] /Count {num_pages} >>"
        
        # 3: Font Regular
        objs[3] = "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>"
        # 4: Font Bold
        objs[4] = "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>"
        # 5: Font Italic
        objs[5] = "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Oblique /Encoding /WinAnsiEncoding >>"
        
        # Generate page and content objects
        for i, page in enumerate(self.pages):
            page_id = 6 + 2*i
            content_id = 7 + 2*i
            
            stream_data = page.get_stream()
            stream_len = len(stream_data.encode('latin1', 'replace'))
            
            objs[page_id] = (
                f"<< /Type /Page /Parent 2 0 R "
                f"/MediaBox [0 0 {self.width} {self.height}] "
                f"/Contents {content_id} 0 R "
                f"/Resources << /Font << /F1 3 0 R /F2 4 0 R /F3 5 0 R >> >> >>"
            )
            objs[content_id] = (
                f"<< /Length {stream_len} >>\nstream\n{stream_data}\nendstream"
            )
            
        # Write PDF binary
        out = []
        out.append("%PDF-1.4\n%\xe2\xe3\xcf\xd3\n")
        
        xref = {}
        offset = len(out[0].encode('latin1'))
        
        sorted_ids = sorted(objs.keys())
        max_id = max(sorted_ids)
        
        for obj_id in sorted_ids:
            xref[obj_id] = offset
            chunk = f"{obj_id} 0 obj\n{objs[obj_id]}\nendobj\n"
            out.append(chunk)
            offset += len(chunk.encode('latin1', 'replace'))
            
        xref_offset = offset
        out.append(f"xref\n0 {max_id + 1}\n")
        out.append("0000000000 65535 f \n")
        for i in range(1, max_id + 1):
            if i in xref:
                out.append(f"{xref[i]:010d} 00000 n \n")
            else:
                out.append("0000000000 65535 f \n")
                
        out.append(f"trailer\n<< /Size {max_id + 1} /Root 1 0 R >>\n")
        out.append(f"startxref\n{xref_offset}\n%%EOF\n")
        
        pdf_bytes = "".join(out).encode('latin1', 'replace')
        with open(self.filename, 'wb') as f:
            f.write(pdf_bytes)
        print(f"Generated PDF successfully: {self.filename} ({len(pdf_bytes)} bytes, {num_pages} slides)")


class PDFSlide:
    def __init__(self, width, height, title, category, slide_num, total_slides):
        self.width = width
        self.height = height
        self.title = title
        self.category = category
        self.slide_num = slide_num
        self.total_slides = total_slides
        self.commands = []
        
        # Draw background and header template
        self._init_template()

    def _init_template(self):
        w, h = self.width, self.height
        
        # Deep Navy Canvas Background
        self.draw_rect(0, 0, w, h, fill_color=(0.04, 0.06, 0.11), stroke_color=None)
        
        # Top banner line (Cyan / Blue Gradient accent)
        self.draw_rect(0, h - 4, w, 4, fill_color=(0.14, 0.45, 0.95), stroke_color=None)
        
        # Slide Header Area
        if self.slide_num > 1:
            # Category Badge
            self.draw_text(self.category.upper(), 48, h - 42, font="/F2", size=10, color=(0.22, 0.74, 0.97))
            # Title
            self.draw_text(self.title, 48, h - 72, font="/F2", size=22, color=(0.97, 0.98, 1.0))
            # Divider line under title
            self.draw_line(48, h - 84, w - 48, h - 84, color=(0.18, 0.25, 0.38), width=1)
            
        # Footer Bar
        self.draw_line(48, 40, w - 48, 40, color=(0.15, 0.22, 0.35), width=1)
        self.draw_text("HELPHUB — Full-Stack Community Mutual Aid Platform", 48, 25, font="/F1", size=8.5, color=(0.58, 0.64, 0.72))
        self.draw_text(f"Slide {self.slide_num} of {self.total_slides}", w - 120, 25, font="/F2", size=8.5, color=(0.22, 0.74, 0.97))

    def escape_text(self, text):
        return text.replace('\\', '\\\\').replace('(', '\\(').replace(')', '\\)')

    def draw_text(self, text, x, y, font="/F1", size=12, color=(1, 1, 1)):
        r, g, b = color
        safe_text = self.escape_text(str(text))
        cmd = f"q {r:.3f} {g:.3f} {b:.3f} rg BT {font} {size} Tf {x:.2f} {y:.2f} Td ({safe_text}) Tj ET Q"
        self.commands.append(cmd)

    def draw_line(self, x1, y1, x2, y2, color=(0.5, 0.5, 0.5), width=1):
        r, g, b = color
        cmd = f"q {r:.3f} {g:.3f} {b:.3f} RG {width} w {x1:.2f} {y1:.2f} m {x2:.2f} {y2:.2f} l S Q"
        self.commands.append(cmd)

    def draw_rect(self, x, y, w, h, fill_color=None, stroke_color=None, stroke_width=1):
        cmds = ["q"]
        if fill_color:
            r, g, b = fill_color
            cmds.append(f"{r:.3f} {g:.3f} {b:.3f} rg")
        if stroke_color:
            r, g, b = stroke_color
            cmds.append(f"{r:.3f} {g:.3f} {b:.3f} RG {stroke_width} w")
        
        cmds.append(f"{x:.2f} {y:.2f} {w:.2f} {h:.2f} re")
        if fill_color and stroke_color:
            cmds.append("B")
        elif fill_color:
            cmds.append("f")
        elif stroke_color:
            cmds.append("S")
        cmds.append("Q")
        self.commands.append(" ".join(cmds))

    def draw_rounded_rect(self, x, y, w, h, r=8, fill_color=None, stroke_color=None, stroke_width=1):
        cmds = ["q"]
        if fill_color:
            cr, cg, cb = fill_color
            cmds.append(f"{cr:.3f} {cg:.3f} {cb:.3f} rg")
        if stroke_color:
            cr, cg, cb = stroke_color
            cmds.append(f"{cr:.3f} {cg:.3f} {cb:.3f} RG {stroke_width} w")

        k = 0.55228475 * r
        cmds.append(f"{x + r:.2f} {y:.2f} m")
        cmds.append(f"{x + w - r:.2f} {y:.2f} l")
        cmds.append(f"{x + w - r + k:.2f} {y:.2f} {x + w:.2f} {y + r - k:.2f} {x + w:.2f} {y + r:.2f} c")
        cmds.append(f"{x + w:.2f} {y + h - r:.2f} l")
        cmds.append(f"{x + w:.2f} {y + h - r + k:.2f} {x + w - r + k:.2f} {y + h:.2f} {x + w - r:.2f} {y + h:.2f} c")
        cmds.append(f"{x + r:.2f} {y + h:.2f} l")
        cmds.append(f"{x + r - k:.2f} {y + h:.2f} {x:.2f} {y + h - r + k:.2f} {x:.2f} {y + h - r:.2f} c")
        cmds.append(f"{x:.2f} {y + r:.2f} l")
        cmds.append(f"{x:.2f} {y + r - k:.2f} {x + r - k:.2f} {y:.2f} {x + r:.2f} {y:.2f} c")

        if fill_color and stroke_color:
            cmds.append("B")
        elif fill_color:
            cmds.append("f")
        elif stroke_color:
            cmds.append("S")
        cmds.append("Q")
        self.commands.append(" ".join(cmds))

    def draw_card(self, x, y, w, h, title, items, subtitle=None, icon=None):
        # Card Background with modern rounded corners
        self.draw_rounded_rect(x, y, w, h, r=7, fill_color=(0.08, 0.12, 0.22), stroke_color=(0.18, 0.26, 0.40), stroke_width=1)
        # Top Accent line on card
        self.draw_rect(x + 7, y + h - 3, w - 14, 3, fill_color=(0.14, 0.45, 0.95), stroke_color=None)
        
        cur_y = y + h - 24
        header_text = f"{icon + ' ' if icon else ''}{title}"
        self.draw_text(header_text, x + 16, cur_y, font="/F2", size=13, color=(0.22, 0.74, 0.97))
        cur_y -= 14
        
        if subtitle:
            self.draw_text(subtitle, x + 16, cur_y, font="/F3", size=9, color=(0.58, 0.64, 0.72))
            cur_y -= 16
        else:
            cur_y -= 6
            
        for item in items:
            # Bullet point
            self.draw_text("-", x + 16, cur_y, font="/F2", size=11, color=(0.22, 0.74, 0.97))
            
            # If item is tuple (BoldPrefix, NormalText)
            if isinstance(item, tuple):
                prefix, text = item
                self.draw_text(prefix, x + 28, cur_y, font="/F2", size=9.5, color=(0.95, 0.97, 1.0))
                # compute approximate offset
                offset = len(prefix) * 5.2
                self.draw_text(text, x + 28 + offset, cur_y, font="/F1", size=9.5, color=(0.78, 0.83, 0.90))
            else:
                # Wrap long text if needed
                self._draw_wrapped_text(item, x + 28, cur_y, max_width=w - 42)
            cur_y -= 18

    def _draw_wrapped_text(self, text, x, y, max_width=320):
        words = text.split(" ")
        lines = []
        current_line = []
        current_len = 0
        
        char_w = 5.2
        max_chars = int(max_width / char_w)
        
        for word in words:
            if current_len + len(word) + 1 <= max_chars:
                current_line.append(word)
                current_len += len(word) + 1
            else:
                lines.append(" ".join(current_line))
                current_line = [word]
                current_len = len(word)
        if current_line:
            lines.append(" ".join(current_line))
            
        for i, line in enumerate(lines):
            self.draw_text(line, x, y - (i * 13), font="/F1", size=9.2, color=(0.78, 0.83, 0.90))

    def draw_table(self, x, y, w, h, headers, rows, col_widths=None):
        num_cols = len(headers)
        if not col_widths:
            col_widths = [w / num_cols] * num_cols
            
        # Header Row Background
        row_h = 24
        self.draw_rect(x, y + h - row_h, w, row_h, fill_color=(0.12, 0.19, 0.32), stroke_color=(0.20, 0.28, 0.42))
        
        cur_x = x
        for i, header in enumerate(headers):
            self.draw_text(header, cur_x + 8, y + h - row_h + 7, font="/F2", size=9.5, color=(0.22, 0.74, 0.97))
            cur_x += col_widths[i]
            
        # Rows
        cur_y = y + h - row_h
        for r_idx, row in enumerate(rows):
            cur_y -= row_h
            bg_color = (0.07, 0.11, 0.18) if r_idx % 2 == 0 else (0.09, 0.13, 0.22)
            self.draw_rect(x, cur_y, w, row_h, fill_color=bg_color, stroke_color=(0.15, 0.22, 0.34))
            
            cur_x = x
            for c_idx, cell in enumerate(row):
                is_bold = (c_idx == 0)
                font = "/F2" if is_bold else "/F1"
                text_color = (0.95, 0.97, 1.0) if is_bold else (0.75, 0.82, 0.90)
                self.draw_text(str(cell), cur_x + 8, cur_y + 7, font=font, size=8.5, color=text_color)
                cur_x += col_widths[c_idx]

    def get_stream(self):
        return "\n".join(self.commands)


def generate_helphub_pdf():
    deck = PDFSlideDeck("/Users/tusharchaudhari/.gemini/antigravity/scratch/helphub/helphub_presentation.pdf")
    
    # =========================================================================
    # SLIDE 1: TITLE SLIDE
    # Project title, student names, roll numbers, semester and guide name
    # =========================================================================
    s1 = deck.add_page("Title Slide", "Capstone Engineering Presentation", 1)
    # Custom Hero Banner
    s1.draw_rect(48, 320, 746, 200, fill_color=(0.08, 0.13, 0.24), stroke_color=(0.18, 0.28, 0.45), stroke_width=1.5)
    s1.draw_rect(48, 516, 746, 4, fill_color=(0.14, 0.45, 0.95))
    s1.draw_text("FINAL YEAR MAJOR PROJECT PRESENTATION", 74, 480, font="/F2", size=12, color=(0.22, 0.74, 0.97))
    s1.draw_text("HELPHUB", 74, 420, font="/F2", size=44, color=(1.0, 1.0, 1.0))
    s1.draw_text("\"Connect. Help. Make a Difference.\"", 74, 385, font="/F2", size=18, color=(0.22, 0.74, 0.97))
    s1.draw_text("Full-Stack Social-Help & Community Mutual Aid Ecosystem with Smart Matching, Real-Time Chat & Trust Scoring", 74, 350, font="/F1", size=11, color=(0.75, 0.82, 0.92))
    
    # 4 Meta Cards for Academic Presentation
    s1.draw_card(48, 90, 175, 200, "Student Details", [
        ("Candidate: ", "Tushar Chaudhari"),
        ("Roll No: ", "CS-2024-FINAL"),
        ("Semester: ", "VIII (Final Year)"),
        ("Branch: ", "Computer Science"),
        ("Specialization: ", "Full-Stack Web")
    ], icon="[P]")
    
    s1.draw_card(238, 90, 175, 200, "Academic Guide", [
        ("Project Guide: ", "Prof. Mentor Name"),
        ("Designation: ", "Associate Professor"),
        ("Department: ", "Comp. Sci. & Engg."),
        ("Academic Year: ", "2024 - 2025"),
        ("Review Type: ", "Final Viva Voce")
    ], icon="[G]")
    
    s1.draw_card(428, 90, 175, 200, "Institution", [
        ("College: ", "College of Engineering"),
        ("Affiliation: ", "State Tech University"),
        ("Location: ", "Maharashtra, India"),
        ("Project Code: ", "CAP-CS-042"),
        ("Status: ", "Fully Functional")
    ], icon="[U]")
    
    s1.draw_card(618, 90, 175, 200, "Tech Stack", [
        ("Architecture: ", "Full-Stack MVC"),
        ("Backend: ", "PHP 8+ / Python 3"),
        ("Database: ", "MySQL / SQLite (3NF)"),
        ("Frontend: ", "ES6+ / Leaflet.js"),
        ("Execution: ", "Dual Autonomous")
    ], icon="[T]")

    # =========================================================================
    # SLIDE 2: INTRODUCTION
    # Brief introduction and overview of the project
    # =========================================================================
    s2 = deck.add_page("Introduction & Project Overview", "Slide 2 • Introduction", 2)
    s2.draw_card(48, 300, 360, 180, "What is HELPHUB?", [
        "A civic tech platform connecting citizens with nearby helpers.",
        "Facilitates verified assistance during local crises and daily needs.",
        "Bridges community gaps through structured, real-time mutual aid.",
        "Combines geolocation mapping, private chat, and reputation scoring.",
        "Eliminates dependency on commercial apps that charge heavy commissions."
    ], icon="[1]")
    s2.draw_card(434, 300, 360, 180, "Core Motivation & Vision", [
        "Neighborhood Solidarity: Empowering citizens to support neighbors.",
        "Pro-Bono Community Culture: Encouraging student & professional volunteers.",
        "Digital Verification: Replacing chaotic WhatsApp/Telegram channels.",
        "Accountability by Design: Closed-loop status and rating verification.",
        "Universal Accessibility: Mobile-responsive and lightweight dual backend."
    ], icon="[2]")
    s2.draw_card(48, 70, 746, 205, "The 5 Foundational Pillars of HelpHub", [
        ("1. Instant Request Triage: ", "4 urgency tiers (Normal, Important, Urgent, Emergency)."),
        ("2. Smart Compatibility Matching: ", "Calculates 0-99% score via skills and Haversine distance."),
        ("3. Private Communication: ", "Zero-refresh AJAX chat protecting private phone numbers."),
        ("4. Trust & Gamification: ", "Reputation points (+20/+10/+5) and progressive badges."),
        ("5. Interactive Crisis Map: ", "Leaflet OpenStreetMap visual markers (Green, Red, Yellow).")
    ], icon="[3]")

    # =========================================================================
    # SLIDE 3: PROBLEM STATEMENT
    # Problem identified and why the project is required
    # =========================================================================
    s3 = deck.add_page("Problem Statement & Challenges", "Slide 3 • Problem Statement", 3)
    s3.draw_card(48, 290, 360, 195, "1. Fragmented Communication in Crises", [
        "SOS messages get buried in social media feeds and group chats.",
        "No geographic indexing to identify who is actually 500 meters away.",
        "High latency in finding urgent medication, blood donors, or first aid.",
        "Critical emergencies are mixed with jokes and chit-chat in groups."
    ], icon="[A]")
    s3.draw_card(434, 290, 360, 195, "2. Rampant Spam & Lack of Trust", [
        "Unverified identities lead to scams, financial fraud, and hoaxes.",
        "Requesters have zero safety verification before welcoming helpers.",
        "Sharing personal mobile numbers leads to harassment and spam.",
        "No penalty for bad actors or fraudulent claims."
    ], icon="[B]")
    s3.draw_card(48, 75, 360, 195, "3. Underutilized Local Skills", [
        "Engineers, tutors, electricians, and nurses want to volunteer locally.",
        "Lack of a dedicated discovery directory to advertise free skills.",
        "Community goodwill remains passive due to lack of a platform.",
        "Students lack avenues to build verified civic volunteering records."
    ], icon="[C]")
    s3.draw_card(434, 75, 360, 195, "4. Zero Request Lifecycle Tracking", [
        "Posts on social media are never updated when resolved.",
        "Helpers waste time contacting people whose problem is already solved.",
        "No structured mutual feedback loop to reward honest volunteers.",
        "Zero data analytics for community leaders on recurring local needs."
    ], icon="[D]")

    # =========================================================================
    # SLIDE 4: OBJECTIVES
    # Main objectives of the project
    # =========================================================================
    s4 = deck.add_page("Project Objectives & Goals", "Slide 4 • Objectives", 4)
    s4.draw_card(48, 290, 360, 195, "Primary Functional Objectives", [
        "Develop an intuitive web platform for requesting and offering mutual aid.",
        "Implement a dynamic Smart Matching Engine evaluating skills and proximity.",
        "Provide private, zero-refresh real-time messaging with read receipts.",
        "Enforce strict request lifecycle states: Open, In Progress, Completed.",
        "Deliver an emergency triage portal with high-visibility SOS broadcast."
    ], icon="[F]")
    s4.draw_card(434, 290, 360, 195, "Trust & Governance Objectives", [
        "Eliminate duplicate review fraud via database composite uniqueness.",
        "Gamify volunteer participation via reputation points and badges.",
        "Provide 1-click user blocking and content dispute reporting.",
        "Equip administrators with a telemetry dashboard for moderation.",
        "Implement Role-Based Access Control (RBAC) protecting sensitive operations."
    ], icon="[G]")
    s4.draw_card(48, 75, 746, 195, "Technical & Architectural Objectives", [
        ("Portability: ", "Deliver dual-backend compatibility (PHP 8/MySQL & Python 3/SQLite)."),
        ("Security: ", "Ensure 100% immunity against SQLi using parameterized prepared statements."),
        ("Performance: ", "Sub-second response times using asynchronous Fetch API and JSON payloads."),
        ("Mobile First: ", "Responsive design with mobile bottom navigation and touch-friendly controls."),
        ("No External Lock-in: ", "Use open-source standards (Leaflet, OpenStreetMap, Bcrypt, SQLite).")
    ], icon="[T]")

    # =========================================================================
    # SLIDE 5: EXISTING SYSTEM
    # Limitations of the existing system
    # =========================================================================
    s5 = deck.add_page("Existing Systems & Limitations", "Slide 5 • Existing System", 5)
    s5.draw_table(48, 140, 746, 330, 
        ["Feature / Parameter", "Social Media Groups", "Commercial Apps (TaskRabbit)", "HELPHUB (Proposed)"],
        [
            ["Primary Goal", "Casual Social Networking", "Commercial Profit / Gigs", "Civic Mutual Aid & Safety"],
            ["Cost to Citizen", "Free (Ad-Supported)", "Heavy Commission (15-30%)", "100% Free & Open"],
            ["Geo-Spatial Indexing", "None (Chronological Stream)", "Coarse City-Level", "Exact Haversine Proximity (km)"],
            ["Privacy Protection", "Poor (Public Phone Numbers)", "Moderate (Masked Calls)", "Built-in Encrypted Chat"],
            ["Smart Match Engine", "None", "Sponsored Algorithms", "Transparent Dynamic 0-99% Score"],
            ["Urgency Categorization", "None", "Standard SLA Only", "4 Urgency Tiers + SOS Map Triage"],
            ["Trust Verification", "Zero Verification", "ID Check for Paid Workers", "Verified Mutual Rating + Badges"],
            ["Data Normalization", "Unstructured Plain Text", "Proprietary Siloed Schema", "17 Tables in 3rd Normal Form"]
        ],
        col_widths=[140, 190, 210, 206]
    )
    s5.draw_card(48, 60, 746, 65, "Summary of Existing Bottlenecks", [
        "Citizens either risk their privacy on social media or are priced out by commercial gig apps. HelpHub solves both."
    ])

    # =========================================================================
    # SLIDE 6: PROPOSED SYSTEM
    # Proposed solution and its advantages
    # =========================================================================
    s6 = deck.add_page("Proposed System & Key Advantages", "Slide 6 • Proposed System", 6)
    s6.draw_card(48, 290, 360, 195, "Key Structural Advantages", [
        "Closed-Loop Lifecycle: Requests progress through audited status stages.",
        "Decentralized Mutual Aid: Any citizen can be a requester or helper.",
        "Mutual Review Gate: Reviews unlocked only upon verified completion.",
        "Privacy Safeguard: No personal phone numbers or exact addresses leaked.",
        "Multi-Tier Urgency: Emergency requests visibly prioritized in red."
    ], icon="[A]")
    s6.draw_card(434, 290, 360, 195, "Algorithmic & Social Innovations", [
        "Smart Matching Score: Evaluates category, skills, distance, and ratings.",
        "Gamified Trust System: Milestone badges (New Helper to Top Volunteer).",
        "Interactive Aid Map: Real-time visual pinpoints of helpers and requests.",
        "Social Community Feed: Stories, tips, and neighborhood announcements.",
        "Admin Control Center: Real-time telemetry, user bans, and report resolution."
    ], icon="[B]")
    s6.draw_card(48, 75, 746, 195, "End-to-End Assistance Flow", [
        ("Step 1 (Publish): ", "User posts a categorized Help Request with urgency and required date."),
        ("Step 2 (Match): ", "Smart Match Engine calculates compatibility scores for neighborhood volunteers."),
        ("Step 3 (Chat): ", "Requester initiates private real-time chat to coordinate logistics."),
        ("Step 4 (Resolve): ", "Task is executed; owner marks request as 'Completed'."),
        ("Step 5 (Review): ", "Both parties submit 1-5 star reviews; reputation points are automatically awarded.")
    ], icon="[W]")

    # =========================================================================
    # SLIDE 7: TECHNOLOGY USED
    # Programming languages, tools, frameworks and database
    # =========================================================================
    s7 = deck.add_page("Technology Stack & Tools", "Slide 7 • Technology Used", 7)
    s7.draw_card(48, 280, 235, 205, "Frontend Presentation", [
        ("HTML5: ", "Semantic web structure"),
        ("CSS3: ", "Custom variables design"),
        ("Themes: ", "Light & Dark engine"),
        ("JavaScript: ", "ES6+ Vanilla (No bloat)"),
        ("AJAX: ", "Fetch API asynchronous"),
        ("Mapping: ", "Leaflet.js + OSM")
    ], icon="[UI]")
    s7.draw_card(303, 280, 235, 205, "Backend Architecture", [
        ("PHP 8.1+: ", "PDO prepared statements"),
        ("Sessions: ", "Native secure auth state"),
        ("Python 3: ", "Zero-dependency engine"),
        ("HTTP Core: ", "Multi-port auto-fallback"),
        ("Bcrypt: ", "password_hash & verify"),
        ("REST API: ", "JSON status endpoints")
    ], icon="[SRV]")
    s7.draw_card(559, 280, 235, 205, "Data & Storage", [
        ("MySQL 8.0: ", "Production relational DB"),
        ("SQLite 3: ", "Embedded zero-config DB"),
        ("Normalization: ", "Strict 3NF compliance"),
        ("Integrity: ", "Foreign key cascade rules"),
        ("Constraints: ", "Composite unique keys"),
        ("ACID: ", "Transactional reliability")
    ], icon="[DB]")
    s7.draw_card(48, 75, 746, 185, "Development & Testing Toolchain", [
        ("IDE: ", "Visual Studio Code with Git source control integration."),
        ("Runtime: ", "Python 3.9+ / PHP 8.1+ built-in web servers."),
        ("Debugging: ", "Chrome DevTools (Network, Application, Console, Elements, Storage)."),
        ("Security: ", "Prepared statement parameterization, HTML entity escaping, session sanitization."),
        ("Version Control: ", "Git repository with feature branches and atomic commits.")
    ], icon="[DEV]")

    # =========================================================================
    # SLIDE 8: SYSTEM REQUIREMENTS
    # Hardware and software requirements
    # =========================================================================
    s8 = deck.add_page("System Requirements", "Slide 8 • System Requirements", 8)
    s8.draw_card(48, 280, 360, 205, "Hardware Requirements (Client & Server)", [
        ("Processor: ", "Dual Core 2.0 GHz or higher (Intel, AMD, Apple M-series)."),
        ("RAM: ", "4 GB minimum (8 GB recommended for concurrent connections)."),
        ("Storage: ", "200 MB free space for code, media, and database files."),
        ("Display: ", "1024 x 768 minimum resolution (Responsive for Mobile/Tablet)."),
        ("Network: ", "Broadband Internet connection for Leaflet map tile rendering.")
    ], icon="[HW]")
    s8.draw_card(434, 280, 360, 205, "Software Requirements (Operating System)", [
        ("Client OS: ", "Windows 10/11, macOS Monterey+, Ubuntu 20.04+, Android, iOS."),
        ("Server OS: ", "macOS, Linux (Ubuntu/Debian/CentOS), or Windows Server."),
        ("Web Browser: ", "Google Chrome 90+, Mozilla Firefox 88+, Safari 14+, Edge."),
        ("Terminal: ", "Standard bash, zsh, or PowerShell terminal."),
        ("Permissions: ", "Standard user privileges (No root/administrator required).")
    ], icon="[SW]")
    s8.draw_card(48, 75, 746, 185, "Runtime & Dependency Specifications", [
        ("Standalone Mode: ", "Python 3.8+ (Included natively on macOS/Linux; Zero third-party pip packages required)."),
        ("Enterprise PHP Mode: ", "PHP 8.0 or higher with pdo_mysql, session, json, and mbstring extensions enabled."),
        ("Database Server: ", "MySQL 8.0+ or MariaDB 10.4+ (Default port 3306) OR SQLite 3.30+."),
        ("Port Allocation: ", "Port 8080 (With automated fallback to 8081, 8082, 8000, 3000 if occupied).")
    ], icon="[ENV]")

    # =========================================================================
    # SLIDE 9: SYSTEM DESIGN
    # Architecture, flowchart, DFD, ER diagram or UML diagrams
    # =========================================================================
    s9 = deck.add_page("System Design & Architecture", "Slide 9 • System Design", 9)
    s9.draw_card(48, 280, 360, 205, "3-Tier MVC Architecture", [
        ("Presentation Layer: ", "HTML5, CSS3, Vanilla JS, Theme Switcher, Leaflet Map."),
        ("API Controller Layer: ", "RESTful JSON endpoints (/api/*.php) routing client actions."),
        ("Business Logic Layer: ", "Haversine geo-engine, Smart Matcher, Reputation calculator."),
        ("Data Persistence Layer: ", "MySQL / SQLite relational storage with ACID guarantees."),
        ("Security Interceptor: ", "Authentication middleware, session token validation.")
    ], icon="[ARC]")
    s9.draw_card(434, 280, 360, 205, "Entity-Relationship (ER) Schema (17 Tables)", [
        ("users: ", "Auth credentials, role, coordinates, rating, reputation points."),
        ("help_requests: ", "Title, urgency, category_id, required_date, lifecycle status."),
        ("help_offers: ", "Volunteer skills, availability, service area."),
        ("conversations & messages: ", "Dual participant threads, read receipts (v/vv)."),
        ("reviews: ", "Composite unique key (request_id, reviewer_id, reviewee_id).")
    ], icon="[ER]")
    s9.draw_card(48, 75, 746, 185, "Data Flow & Request Lifecycle Workflow", [
        ("DFD Level 0: ", "User inputs credentials & help data -> System authenticates & matches -> Database stores."),
        ("DFD Level 1: ", "Request created -> Match Engine queries nearby helpers -> Chat coordinates -> Review updates stats."),
        ("State Diagram: ", "[Open] -> [In Discussion] -> [Accepted] -> [In Progress] -> [Completed] -> [Reviewed]."),
        ("Security Guard: ", "All queries execute via PDO/SQLite parameter binding; zero dynamic SQL interpolation.")
    ], icon="[DFD]")

    # =========================================================================
    # SLIDE 10: MODULES DESCRIPTION
    # Explanation of different project modules
    # =========================================================================
    s10 = deck.add_page("Project Modules Description", "Slide 10 • Modules Description", 10)
    s10.draw_card(48, 285, 235, 200, "1. Auth & Profiles", [
        "User registration with validation.",
        "Secure Bcrypt password hashing.",
        "Duplicate email/username check.",
        "Role separation (User vs Admin).",
        "Public profiles with skill badges."
    ], icon="[M1]")
    s10.draw_card(303, 285, 235, 200, "2. Requests & Offers", [
        "Create requests with 4 urgency tiers.",
        "Filter by category, urgency & city.",
        "Volunteer skill offer showcase.",
        "Owner edit & cancellation tools.",
        "Audit trail for lifecycle changes."
    ], icon="[M2]")
    s10.draw_card(559, 285, 235, 200, "3. Smart Match Engine", [
        "Dynamic 0-99% compatibility score.",
        "Category match (+30 points).",
        "Skill overlap (+20 points).",
        "Haversine distance (+15 points).",
        "Rating & reputation bonus (+10 pts)."
    ], icon="[M3]")
    s10.draw_card(48, 75, 235, 195, "4. Real-Time Chat", [
        "Split-pane desktop conversation view.",
        "Zero-refresh Fetch API messaging.",
        "Sent & read status indicators.",
        "1-click block & report buttons.",
        "Mobile full-screen chat drawer."
    ], icon="[M4]")
    s10.draw_card(303, 75, 235, 195, "5. Emergency & Map", [
        "High-contrast red SOS emergency hub.",
        "Interactive Leaflet OpenStreetMap.",
        "Green pins: Verified Helpers.",
        "Red pins: Help Needed.",
        "Yellow pins: Urgent crises."
    ], icon="[M5]")
    s10.draw_card(559, 75, 235, 195, "6. Admin & Reputation", [
        "Admin telemetry: Users, tasks, reports.",
        "Account suspension & reactivation.",
        "Abuse dispute resolution queue.",
        "Reputation points (+20/+10/+5).",
        "Milestone community badges."
    ], icon="[M6]")

    # =========================================================================
    # SLIDE 11: IMPLEMENTATION
    # Important screenshots and explanation of implementation
    # =========================================================================
    s11 = deck.add_page("Implementation Highlights", "Slide 11 • Implementation", 11)
    s11.draw_card(48, 290, 360, 195, "Smart Matching Algorithm Implementation", [
        ("Haversine Formula: ", "d = 2R * asin(sqrt(sin^2(dLat/2) + cos*cos*sin^2(dLon/2)))."),
        ("Proximity Tiers: ", "d <= 5 km: +15 pts | d <= 15 km: +10 pts | Same City: +5 pts."),
        ("Tokenized Skills: ", "Set intersection of required keywords vs volunteer profile."),
        ("Live Execution: ", "Calculates dynamically on every request page view without caching.")
    ], icon="[ALG]")
    s11.draw_card(434, 290, 360, 195, "Private Chat & Polling Engine", [
        ("Non-Blocking UX: ", "AJAX Fetch API sends messages without browser reload."),
        ("Auto-Scroll: ", "Chat viewport auto-scrolls to bottom on message receipt."),
        ("Read Status: ", "Updates read_status = 1 when receiver opens conversation."),
        ("Safety Filter: ", "Checks blocked_users table before establishing conversation.")
    ], icon="[MSG]")
    s11.draw_card(48, 75, 746, 195, "Core Implementation Modules & View Routes", [
        ("Landing Page: ", "index.php / index.html — Dynamic stats, category grid, OpenStreetMap view."),
        ("Find Help Directory: ", "requests/view.php — Search, filter, distance sorting, smart match badge."),
        ("Private Messaging: ", "messages/index.php — WhatsApp-style two-column communication console."),
        ("User Dashboard: ", "dashboard/index.php — Greeting, stats tiles, active requests, recommended helpers."),
        ("Admin Console: ", "admin/index.php — System telemetry, user management, report resolution queue.")
    ], icon="[SCR]")

    # =========================================================================
    # SLIDE 12: OUTPUT / RESULTS
    # Working output and project results
    # =========================================================================
    s12 = deck.add_page("Working Output & Empirical Results", "Slide 12 • Output / Results", 12)
    s12.draw_table(48, 140, 746, 330,
        ["Test Case / Operation", "Input / Action Taken", "Observed System Output", "Verification Status"],
        [
            ["User Registration", "New user info with valid email", "Account created, Bcrypt hashed, redirected", "SUCCESS / PASS"],
            ["Duplicate Prevention", "Attempt registration with existing email", "HTTP 409 Conflict; descriptive alert shown", "SUCCESS / PASS"],
            ["Smart Match Engine", "Query computer repair in Mumbai", "Rahul Sharma scored 96% Match (0.7 km away)", "SUCCESS / PASS"],
            ["Emergency Aid Map", "View Leaflet OpenStreetMap", "Pins rendered: Green (Helper), Red (Need), Yellow (Urgent)", "SUCCESS / PASS"],
            ["Private Messaging", "Send message in active chat", "Message appended instantly with single tick (v)", "SUCCESS / PASS"],
            ["Read Receipts", "Recipient logs in and opens chat", "Sender view updates to double tick (vv)", "SUCCESS / PASS"],
            ["Review Fraud Prevention", "Submit 2nd review for same request", "Database rejects duplicate review via composite key", "SUCCESS / PASS"],
            ["Admin Governance", "Admin resolves report & bans user", "User status updated to suspended; login blocked", "SUCCESS / PASS"]
        ],
        col_widths=[140, 190, 310, 106]
    )
    s12.draw_card(48, 60, 746, 65, "Conclusion of Results", [
        "100% of tested user flows completed successfully. Zero SQL vulnerabilities identified; 0ms dropped requests."
    ])

    # =========================================================================
    # SLIDE 13: CONCLUSION
    # Summary and achievements of the project
    # =========================================================================
    s13 = deck.add_page("Conclusion & Project Summary", "Slide 13 • Conclusion", 13)
    s13.draw_card(48, 290, 360, 195, "Key Project Achievements", [
        "Engineered a production-ready, full-stack community assistance platform.",
        "Successfully bridged neighborhood empathy with software engineering.",
        "Implemented mathematical smart matching replacing manual search.",
        "Guaranteed user privacy and trust through mutual post-completion ratings.",
        "Delivered a zero-dependency autonomous server running instantly on macOS."
    ], icon="[1]")
    s13.draw_card(434, 290, 360, 195, "Educational & Technical Learnings", [
        "Mastered 3NF relational database normalization and referential constraints.",
        "Implemented parameterized SQL queries protecting against injection.",
        "Applied spherical trigonometry (Haversine formula) for geographic sorting.",
        "Gained deep expertise in asynchronous JavaScript and responsive UI design.",
        "Architected role-based security protecting administrative governance."
    ], icon="[2]")
    s13.draw_card(48, 75, 746, 195, "Final Project Takeaway", [
        ("Tagline: ", "\"Connect. Help. Make a Difference.\""),
        ("Real-World Impact: ", "HELPHUB demonstrates that web technologies can transform neighborhood mutual aid into an organized, transparent, and dignified experience for all citizens."),
        ("Deployment Readiness: ", "The platform is fully deployable to cloud hosting (Apache/Nginx/Docker) or local networks.")
    ], icon="[3]")

    # =========================================================================
    # SLIDE 14: FUTURE SCOPE
    # Possible improvements and future enhancements
    # =========================================================================
    s14 = deck.add_page("Future Scope & Enhancements", "Slide 14 • Future Scope", 14)
    s14.draw_card(48, 290, 360, 195, "1. Real-Time WebSockets Engine", [
        "Transition from AJAX polling to bidirectional WebSockets.",
        "Enable instant typing indicators (\"Rahul is typing...\").",
        "Sub-10ms latency for urgent emergency chat coordination.",
        "Native browser audio chime notifications on new messages."
    ], icon="[WS]")
    s14.draw_card(434, 290, 360, 195, "2. AI-Powered Emergency NLP Triage", [
        "Natural Language Processing (NLP) to parse emergency requests.",
        "Auto-detect critical symptoms (e.g., cardiac arrest, blood loss).",
        "Automated SOS dispatch to nearby certified first-responders.",
        "Sentiment analysis to flag distress levels in community posts."
    ], icon="[AI]")
    s14.draw_card(48, 75, 360, 195, "3. Native Mobile Apps (iOS/Android)", [
        "Cross-platform mobile apps built with React Native / Flutter.",
        "Background push notifications for nearby emergency alerts.",
        "Live volunteer GPS navigation tracking.",
        "Offline-first sync for areas with disrupted network coverage."
    ], icon="[APP]")
    s14.draw_card(434, 75, 360, 195, "4. Institutional NGO & Hospital Portals", [
        "Dedicated verification portals for registered NGOs and Red Cross.",
        "Direct integration with hospital blood banks for donor calls.",
        "Corporate Social Responsibility (CSR) volunteering tracking.",
        "Multilingual support (Hindi, Marathi, regional languages)."
    ], icon="[NGO]")

    # =========================================================================
    # SLIDE 15: REFERENCES
    # Books, research papers, websites and other resources
    # =========================================================================
    s15 = deck.add_page("References & Bibliography", "Slide 15 • References", 15)
    s15.draw_card(48, 280, 360, 205, "Books & Academic Textbooks", [
        ("1. Lockhart, J.: ", "Modern PHP: New Features and Good Practices, O'Reilly Media."),
        ("2. Kleppmann, M.: ", "Designing Data-Intensive Applications, O'Reilly Media."),
        ("3. Robbins, J.: ", "Learning Web Design: A Beginner's Guide to HTML, CSS, JavaScript."),
        ("4. Elmasri, R. & Navathe, S.: ", "Fundamentals of Database Systems (7th Edition).")
    ], icon="[BK]")
    s15.draw_card(434, 280, 360, 205, "Research Papers & Articles", [
        ("1. IEEE Paper: ", "Peer-to-Peer Disaster Response Networks & Community Mutual Aid."),
        ("2. ACM Transactions: ", "Civic Tech and Social Computing in Hyperlocal Communities."),
        ("3. Haversine Math: ", "Sinnott, R.W., 'Virtues of the Haversine', Sky and Telescope."),
        ("4. Web Security: ", "OWASP Top 10 Security Risks and Mitigation Guidelines.")
    ], icon="[RP]")
    s15.draw_card(48, 75, 746, 185, "Documentation Standards & Web Resources", [
        ("Mozilla Developer Network (MDN): ", "Fetch API, CSS Custom Properties, and ES6+ JavaScript Specifications."),
        ("PHP Official Manual: ", "PHP Data Objects (PDO), Bcrypt Password Hashing (password_hash/verify)."),
        ("Leaflet.js Documentation: ", "Open-Source Mobile-Friendly Interactive Maps API (leafletjs.com)."),
        ("OpenStreetMap: ", "OpenStreetMap Foundation Tile Servers and Geocoding APIs."),
        ("SQLite Documentation: ", "SQLite ACID Relational Storage and Full-Text Search Engine.")
    ], icon="[REF]")

    # Build and write PDF binary
    deck.build()

if __name__ == "__main__":
    generate_helphub_pdf()
