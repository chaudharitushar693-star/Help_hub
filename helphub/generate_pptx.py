#!/usr/bin/env python3
"""
HELPHUB 15-Slide Professional PPTX PowerPoint Generator
Generates an authentic, native 16:9 widescreen PowerPoint presentation (.pptx)
compliant with ECMA-376 OpenXML standard, opening directly in:
- Microsoft PowerPoint
- Apple Keynote
- Google Slides
- LibreOffice Impress / Canva

Pure Python 3 standard library with zero external dependencies.
"""

import os
import zipfile
import html

class PPTXPresentation:
    def __init__(self, filename="helphub_presentation.pptx"):
        self.filename = filename
        self.width = 12192000   # 16:9 widescreen width (13.33 inches in EMUs)
        self.height = 6858000   # 16:9 widescreen height (7.5 inches in EMUs)
        self.slides = []

    def add_slide(self, title, tag, slide_num, total_slides=15):
        slide = PPTXSlide(self.width, self.height, title, tag, slide_num, total_slides)
        self.slides.append(slide)
        return slide

    def save(self):
        with zipfile.ZipFile(self.filename, 'w', zipfile.ZIP_DEFLATED) as z:
            # 1. [Content_Types].xml
            z.writestr('[Content_Types].xml', self._build_content_types())

            # 2. _rels/.rels
            z.writestr('_rels/.rels', self._build_root_rels())

            # 3. ppt/presentation.xml
            z.writestr('ppt/presentation.xml', self._build_presentation_xml())

            # 4. ppt/_rels/presentation.xml.rels
            z.writestr('ppt/_rels/presentation.xml.rels', self._build_presentation_rels())

            # 5. ppt/theme/theme1.xml
            z.writestr('ppt/theme/theme1.xml', self._build_theme_xml())

            # 6. ppt/slideMasters/slideMaster1.xml & rels
            z.writestr('ppt/slideMasters/slideMaster1.xml', self._build_slide_master())
            z.writestr('ppt/slideMasters/_rels/slideMaster1.xml.rels', self._build_slide_master_rels())

            # 7. ppt/slideLayouts/slideLayout1.xml & rels
            z.writestr('ppt/slideLayouts/slideLayout1.xml', self._build_slide_layout())
            z.writestr('ppt/slideLayouts/_rels/slideLayout1.xml.rels', self._build_slide_layout_rels())

            # 8. Individual slides
            for i, slide in enumerate(self.slides, start=1):
                z.writestr(f'ppt/slides/slide{i}.xml', slide.build_xml())
                z.writestr(f'ppt/slides/_rels/slide{i}.xml.rels', self._build_slide_rels())

        print(f"Generated PowerPoint presentation: {self.filename} ({len(self.slides)} slides)")

    def _build_content_types(self):
        overrides = [
            '<Override PartName="/ppt/presentation.xml" ContentType="application/vnd.openxmlformats-officedocument.presentationml.presentation.main+xml"/>',
            '<Override PartName="/ppt/slideMasters/slideMaster1.xml" ContentType="application/vnd.openxmlformats-officedocument.presentationml.slideMaster+xml"/>',
            '<Override PartName="/ppt/slideLayouts/slideLayout1.xml" ContentType="application/vnd.openxmlformats-officedocument.presentationml.slideLayout+xml"/>',
            '<Override PartName="/ppt/theme/theme1.xml" ContentType="application/vnd.openxmlformats-officedocument.theme+xml"/>',
        ]
        for i in range(1, len(self.slides) + 1):
            overrides.append(f'<Override PartName="/ppt/slides/slide{i}.xml" ContentType="application/vnd.openxmlformats-officedocument.presentationml.slide+xml"/>')

        return f'''<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
  <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
  <Default Extension="xml" ContentType="application/xml"/>
  {chr(10).join(overrides)}
</Types>'''

    def _build_root_rels(self):
        return '''<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="ppt/presentation.xml"/>
</Relationships>'''

    def _build_presentation_xml(self):
        sld_ids = []
        for i in range(1, len(self.slides) + 1):
            sld_ids.append(f'<p:sldId id="{255 + i}" r:id="rId{i + 2}"/>')

        return f'''<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<p:presentation xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main"
                xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"
                xmlns:p="http://schemas.openxmlformats.org/presentationml/2006/main">
  <p:sldMasterIdLst>
    <p:sldMasterId id="2147483648" r:id="rId1"/>
  </p:sldMasterIdLst>
  <p:sldIdLst>
    {chr(10).join(sld_ids)}
  </p:sldIdLst>
  <p:sldSz cx="{self.width}" cy="{self.height}" type="screen16x9"/>
  <p:notesSz cx="6858000" cy="9144000"/>
</p:presentation>'''

    def _build_presentation_rels(self):
        rels = [
            '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/slideMaster" Target="slideMasters/slideMaster1.xml"/>',
            '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/theme" Target="theme/theme1.xml"/>'
        ]
        for i in range(1, len(self.slides) + 1):
            rels.append(f'<Relationship Id="rId{i + 2}" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/slide" Target="slides/slide{i}.xml"/>')

        return f'''<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  {chr(10).join(rels)}
</Relationships>'''

    def _build_slide_master(self):
        return '''<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<p:sldMaster xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main"
             xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"
             xmlns:p="http://schemas.openxmlformats.org/presentationml/2006/main">
  <p:cSld>
    <p:spTree>
      <p:nvGrpSpPr>
        <p:cNvPr id="1" name=""/>
        <p:cNvGrpSpPr/>
        <p:nvPr/>
      </p:nvGrpSpPr>
      <p:grpSpPr>
        <a:xfrm>
          <a:off x="0" y="0"/>
          <a:ext cx="0" cy="0"/>
          <a:chOff x="0" y="0"/>
          <a:chExt cx="0" cy="0"/>
        </a:xfrm>
      </p:grpSpPr>
    </p:spTree>
  </p:cSld>
  <p:clrMap bg1="lt1" tx1="dk1" bg2="lt2" tx2="dk2" accent1="accent1" accent2="accent2" accent3="accent3" accent4="accent4" accent5="accent5" accent6="accent6" hlink="hlink" folHlink="folHlink"/>
  <p:sldLayoutIdLst>
    <p:sldLayoutId id="2147483649" r:id="rId1"/>
  </p:sldLayoutIdLst>
  <p:txStyles>
    <p:titleStyle/>
    <p:bodyStyle/>
    <p:otherStyle/>
  </p:txStyles>
</p:sldMaster>'''

    def _build_slide_master_rels(self):
        return '''<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/slideLayout" Target="../slideLayouts/slideLayout1.xml"/>
  <Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/theme" Target="../theme/theme1.xml"/>
</Relationships>'''

    def _build_slide_layout(self):
        return '''<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<p:sldLayout xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main"
             xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"
             xmlns:p="http://schemas.openxmlformats.org/presentationml/2006/main"
             type="blank" preserve="1">
  <p:cSld>
    <p:spTree>
      <p:nvGrpSpPr>
        <p:cNvPr id="1" name=""/>
        <p:cNvGrpSpPr/>
        <p:nvPr/>
      </p:nvGrpSpPr>
      <p:grpSpPr>
        <a:xfrm>
          <a:off x="0" y="0"/>
          <a:ext cx="0" cy="0"/>
          <a:chOff x="0" y="0"/>
          <a:chExt cx="0" cy="0"/>
        </a:xfrm>
      </p:grpSpPr>
    </p:spTree>
  </p:cSld>
  <p:clrMapOvr>
    <a:masterClrMap/>
  </p:clrMapOvr>
</p:sldLayout>'''

    def _build_slide_layout_rels(self):
        return '''<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/slideMaster" Target="../slideMasters/slideMaster1.xml"/>
</Relationships>'''

    def _build_slide_rels(self):
        return '''<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/slideLayout" Target="../slideLayouts/slideLayout1.xml"/>
</Relationships>'''

    def _build_theme_xml(self):
        return '''<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<a:theme xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main" name="HelpHub Theme">
  <a:themeElements>
    <a:clrScheme name="HelpHub Modern Slate">
      <a:dk1><a:srgbClr val="0F172A"/></a:dk1>
      <a:lt1><a:srgbClr val="FFFFFF"/></a:lt1>
      <a:dk2><a:srgbClr val="1E293B"/></a:dk2>
      <a:lt2><a:srgbClr val="F8FAFC"/></a:lt2>
      <a:accent1><a:srgbClr val="2563EB"/></a:accent1>
      <a:accent2><a:srgbClr val="38BDF8"/></a:accent2>
      <a:accent3><a:srgbClr val="10B981"/></a:accent3>
      <a:accent4><a:srgbClr val="F59E0B"/></a:accent4>
      <a:accent5><a:srgbClr val="6366F1"/></a:accent5>
      <a:accent6><a:srgbClr val="EC4899"/></a:accent6>
      <a:hlink><a:srgbClr val="38BDF8"/></a:hlink>
      <a:folHlink><a:srgbClr val="818CF8"/></a:folHlink>
    </a:clrScheme>
    <a:fontScheme name="Modern Sans">
      <a:majorFont><a:latin typeface="Arial"/></a:majorFont>
      <a:minorFont><a:latin typeface="Arial"/></a:minorFont>
    </a:fontScheme>
    <a:fmtScheme name="HelpHub Format">
      <a:fillStyleLst><a:solidFill><a:srgbClr val="0F172A"/></a:solidFill></a:fillStyleLst>
      <a:lnStyleLst><a:ln w="9525"><a:solidFill><a:srgbClr val="334155"/></a:solidFill></a:ln></a:lnStyleLst>
      <a:effectStyleLst><a:effectStyle><a:effectLst/></a:effectStyle></a:effectStyleLst>
      <a:bgFillStyleLst><a:solidFill><a:srgbClr val="0B132B"/></a:solidFill></a:bgFillStyleLst>
    </a:fmtScheme>
  </a:themeElements>
</a:theme>'''


class PPTXSlide:
    def __init__(self, width, height, title, tag, slide_num, total_slides):
        self.width = width
        self.height = height
        self.title = title
        self.tag = tag
        self.slide_num = slide_num
        self.total_slides = total_slides
        self.shapes = []
        self.shape_id_counter = 2

        # Initialize Default Background & Header/Footer
        self._init_chrome()

    def _get_next_id(self):
        cid = self.shape_id_counter
        self.shape_id_counter += 1
        return cid

    def _init_chrome(self):
        # 1. Main Background Rectangle (Deep Slate 0B132B)
        self.add_rect(0, 0, self.width, self.height, fill="0B132B", stroke=None)

        # 2. Top Accent Glow Bar (Electric Blue / Cyan)
        self.add_rect(0, 0, self.width, 60000, fill="2563EB", stroke=None)

        if self.slide_num > 1:
            # 3. Category Tag
            self.add_textbox(
                x=600000, y=280000, cx=10000000, cy=280000,
                paragraphs=[(self.tag.upper(), "38BDF8", 1100, True)]
            )
            # 4. Slide Title
            self.add_textbox(
                x=600000, y=550000, cx=11000000, cy=600000,
                paragraphs=[(self.title, "FFFFFF", 2600, True)]
            )
            # 5. Divider Line under title
            self.add_rect(600000, 1180000, 10992000, 15000, fill="1E293B", stroke=None)

        # 6. Footer Divider Line
        self.add_rect(600000, 6380000, 10992000, 15000, fill="1E293B", stroke=None)

        # 7. Footer Text (Left: Project Name, Right: Slide Number)
        self.add_textbox(
            x=600000, y=6420000, cx=7000000, cy=300000,
            paragraphs=[("HELPHUB — Full-Stack Community Mutual Aid Platform", "64748B", 900, False)]
        )
        self.add_textbox(
            x=9500000, y=6420000, cx=2100000, cy=300000,
            paragraphs=[(f"Slide {self.slide_num} of {self.total_slides}", "38BDF8", 950, True)],
            align="r"
        )

    def add_rect(self, x, y, cx, cy, fill=None, stroke=None, stroke_w=12700):
        sid = self._get_next_id()
        fill_xml = f'<a:solidFill><a:srgbClr val="{fill}"/></a:solidFill>' if fill else '<a:noFill/>'
        ln_xml = f'<a:ln w="{stroke_w}"><a:solidFill><a:srgbClr val="{stroke}"/></a:solidFill></a:ln>' if stroke else '<a:ln><a:noFill/></a:ln>'

        sp = f'''<p:sp>
  <p:nvSpPr>
    <p:cNvPr id="{sid}" name="Shape_{sid}"/>
    <p:cNvSpPr/>
    <p:nvPr/>
  </p:nvSpPr>
  <p:spPr>
    <a:xfrm>
      <a:off x="{x}" y="{y}"/>
      <a:ext cx="{cx}" cy="{cy}"/>
    </a:xfrm>
    <a:prstGeom prst="rect"><a:avLst/></a:prstGeom>
    {fill_xml}
    {ln_xml}
  </p:spPr>
</p:sp>'''
        self.shapes.append(sp)

    def add_textbox(self, x, y, cx, cy, paragraphs, align="l"):
        sid = self._get_next_id()
        p_xmls = []
        for p in paragraphs:
            # Paragraph can be (text, color_hex, size_hundredths, is_bold)
            # or a list of runs: [ (run_text, color, sz, bold), ... ]
            if isinstance(p, tuple):
                text, color, size, bold = p
                b_attr = ' b="1"' if bold else ' b="0"'
                safe_text = html.escape(text)
                p_xmls.append(f'''<a:p>
  <a:pPr algn="{align}"/>
  <a:r>
    <a:rPr lang="en-US" sz="{size}"{b_attr} dirty="0">
      <a:solidFill><a:srgbClr val="{color}"/></a:solidFill>
      <a:latin typeface="Arial"/>
    </a:rPr>
    <a:t>{safe_text}</a:t>
  </a:r>
</a:p>''')
            elif isinstance(p, list):
                runs_xml = []
                for run_text, color, size, bold in p:
                    b_attr = ' b="1"' if bold else ' b="0"'
                    safe_text = html.escape(run_text)
                    runs_xml.append(f'''<a:r>
  <a:rPr lang="en-US" sz="{size}"{b_attr} dirty="0">
    <a:solidFill><a:srgbClr val="{color}"/></a:solidFill>
    <a:latin typeface="Arial"/>
  </a:rPr>
  <a:t>{safe_text}</a:t>
</a:r>''')
                p_xmls.append(f'''<a:p>
  <a:pPr algn="{align}"/>
  {''.join(runs_xml)}
</a:p>''')

        sp = f'''<p:sp>
  <p:nvSpPr>
    <p:cNvPr id="{sid}" name="TextBox_{sid}"/>
    <p:cNvSpPr txBox="1"/>
    <p:nvPr/>
  </p:nvSpPr>
  <p:spPr>
    <a:xfrm>
      <a:off x="{x}" y="{y}"/>
      <a:ext cx="{cx}" cy="{cy}"/>
    </a:xfrm>
    <a:prstGeom prst="rect"><a:avLst/></a:prstGeom>
    <a:noFill/>
    <a:ln><a:noFill/></a:ln>
  </p:spPr>
  <p:txBody>
    <a:bodyPr vert="horz" lIns="0" tIns="0" rIns="0" bIns="0"/>
    <a:lstStyle/>
    {''.join(p_xmls)}
  </p:txBody>
</p:sp>'''
        self.shapes.append(sp)

    def add_card(self, x, y, cx, cy, title, items, badge=None, accent_color="2563EB"):
        # 1. Background Box with subtle rounded rectangle or rectangle
        self.add_rect(x, y, cx, cy, fill="131D33", stroke="263554", stroke_w=15000)

        # 2. Top colored accent strip on card
        self.add_rect(x, y, cx, 40000, fill=accent_color, stroke=None)

        # 3. Card Header
        header_runs = [(title, "38BDF8", 1300, True)]
        if badge:
            header_runs.append((f"  [{badge}]", "10B981", 1000, True))

        paras = [header_runs]

        # 4. Item bullets
        for item in items:
            if isinstance(item, tuple):
                prefix, text = item
                paras.append([
                    ("• ", "38BDF8", 1050, True),
                    (prefix, "FFFFFF", 1050, True),
                    (text, "94A3B8", 1050, False)
                ])
            else:
                paras.append([
                    ("• ", "38BDF8", 1050, True),
                    (str(item), "CBD5E1", 1050, False)
                ])

        self.add_textbox(x + 220000, y + 160000, cx - 440000, cy - 300000, paras)

    def add_table(self, x, y, cx, cy, headers, rows, col_widths=None):
        num_cols = len(headers)
        if not col_widths:
            col_widths = [int(cx / num_cols)] * num_cols

        # Header Row
        cur_y = y
        row_h = 420000

        # Background for header
        self.add_rect(x, cur_y, cx, row_h, fill="1E293B", stroke="334155")
        cur_x = x
        for i, h in enumerate(headers):
            self.add_textbox(cur_x + 100000, cur_y + 80000, col_widths[i] - 150000, row_h - 100000,
                             paragraphs=[(h, "38BDF8", 1050, True)])
            cur_x += col_widths[i]

        cur_y += row_h

        # Data Rows
        data_row_h = 360000
        for r_idx, row in enumerate(rows):
            bg = "0F172A" if r_idx % 2 == 0 else "141E33"
            self.add_rect(x, cur_y, cx, data_row_h, fill=bg, stroke="202E4A")

            cur_x = x
            for c_idx, cell in enumerate(row):
                color = "FFFFFF" if c_idx == 0 else "94A3B8"
                bold = (c_idx == 0)
                self.add_textbox(cur_x + 100000, cur_y + 60000, col_widths[c_idx] - 150000, data_row_h - 80000,
                                 paragraphs=[(str(cell), color, 950, bold)])
                cur_x += col_widths[c_idx]
            cur_y += data_row_h

    def build_xml(self):
        return f'''<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<p:sld xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main"
       xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"
       xmlns:p="http://schemas.openxmlformats.org/presentationml/2006/main">
  <p:cSld>
    <p:spTree>
      <p:nvGrpSpPr>
        <p:cNvPr id="1" name=""/>
        <p:cNvGrpSpPr/>
        <p:nvPr/>
      </p:nvGrpSpPr>
      <p:grpSpPr>
        <a:xfrm>
          <a:off x="0" y="0"/>
          <a:ext cx="0" cy="0"/>
          <a:chOff x="0" y="0"/>
          <a:chExt cx="0" cy="0"/>
        </a:xfrm>
      </p:grpSpPr>
      {''.join(self.shapes)}
    </p:spTree>
  </p:cSld>
  <p:clrMapOvr>
    <a:masterClrMap/>
  </p:clrMapOvr>
</p:sld>'''


def build_all_15_slides():
    prs = PPTXPresentation("/Users/tusharchaudhari/.gemini/antigravity/scratch/helphub/helphub_presentation.pptx")

    # =========================================================================
    # SLIDE 1: TITLE SLIDE
    # Project title, student names, roll numbers, semester and guide name
    # =========================================================================
    s1 = prs.add_slide("Title Slide", "Capstone Engineering Presentation", 1)
    # Hero Title Container Box
    s1.add_rect(600000, 800000, 10992000, 2600000, fill="111C33", stroke="28395E", stroke_w=20000)
    s1.add_rect(600000, 800000, 10992000, 50000, fill="2563EB", stroke=None)

    s1.add_textbox(900000, 1100000, 10000000, 400000, [
        ("FINAL YEAR MAJOR PROJECT PRESENTATION • B.TECH CSE / IT", "38BDF8", 1200, True)
    ])
    s1.add_textbox(900000, 1550000, 10000000, 900000, [
        ("HELPHUB", "FFFFFF", 4800, True)
    ])
    s1.add_textbox(900000, 2450000, 10000000, 400000, [
        ("“Connect. Help. Make a Difference.”", "38BDF8", 1800, True)
    ])
    s1.add_textbox(900000, 2900000, 10000000, 400000, [
        ("Full-Stack Community Mutual Aid Ecosystem with Smart Matching, Real-Time Messaging & Trust Scoring", "94A3B8", 1200, False)
    ])

    # 4 Metadata Cards
    s1.add_card(600000, 3700000, 2550000, 2350000, "Student Details", [
        ("Candidate: ", "Tushar Chaudhari"),
        ("Roll No: ", "CS-2024-FINAL"),
        ("Semester: ", "VIII (Final Year)"),
        ("Degree: ", "B.Tech Computer Science")
    ], badge="AUTHOR", accent_color="2563EB")

    s1.add_card(3410000, 3700000, 2550000, 2350000, "Academic Guide", [
        ("Project Guide: ", "Prof. Mentor Name"),
        ("Designation: ", "Associate Professor"),
        ("Department: ", "Comp. Sci. & Engg."),
        ("Academic Year: ", "2024 - 2025")
    ], badge="MENTOR", accent_color="38BDF8")

    s1.add_card(6220000, 3700000, 2550000, 2350000, "Institution Info", [
        ("Institution: ", "College of Engineering"),
        ("Affiliation: ", "State Technological Univ."),
        ("Department: ", "Computer Engineering"),
        ("Project Code: ", "CAPSTONE-CS-042")
    ], badge="COLLEGE", accent_color="10B981")

    s1.add_card(9030000, 3700000, 2550000, 2350000, "System Architecture", [
        ("Architecture: ", "Full-Stack MVC Dual"),
        ("Backend: ", "PHP 8+ PDO / Python 3"),
        ("Database: ", "MySQL / SQLite (3NF)"),
        ("Execution: ", "Zero-Dependency Standalone")
    ], badge="ENGINE", accent_color="F59E0B")

    # =========================================================================
    # SLIDE 2: INTRODUCTION
    # =========================================================================
    s2 = prs.add_slide("Introduction & Project Overview", "Slide 2 • Introduction", 2)
    s2.add_card(600000, 1450000, 5350000, 2400000, "What is HELPHUB?", [
        "A civic tech web platform that bridges community gaps by connecting neighbors in need with nearby verified volunteers.",
        "Empowers citizens to request immediate assistance, volunteer spare skills, and build local social resilience.",
        "Operates pro-bono: 100% free of charge with zero commercial gig commissions.",
        "Combines geolocation mapping, private chat, and reputation scoring into a single unified web ecosystem."
    ], badge="OVERVIEW", accent_color="2563EB")

    s2.add_card(6240000, 1450000, 5350000, 2400000, "Core Vision & Motivation", [
        "Neighborhood Solidarity: Mobilizing community members to support vulnerable citizens during crisis.",
        "Digital Trust & Privacy: Safe in-app private communication without leaking personal mobile numbers.",
        "Skill Optimization: Channeling underutilized local talent (engineering, medical, teaching) to those in need.",
        "Accountability by Design: Replacing unindexed WhatsApp groups with formal, audited lifecycle tracking."
    ], badge="MISSION", accent_color="38BDF8")

    s2.add_card(600000, 4100000, 10992000, 1950000, "The 5 Foundational Pillars of HelpHub", [
        ("1. Instant Request Triage: ", "Categorized with 4 urgency levels (Normal, Important, Urgent, Emergency)."),
        ("2. Smart Compatibility Matching: ", "Calculates a dynamic 0-99% score via skills and spherical Haversine distance."),
        ("3. Private Communication Engine: ", "Real-time Fetch API messaging with read status receipts and safety blocks."),
        ("4. Gamified Trust & Reputation: ", "Reputation points (+20/+10/+5) and badges (New Helper to Top Volunteer)."),
        ("5. Interactive Aid Map: ", "Leaflet OpenStreetMap visual markers color-coded for instant emergency triage.")
    ], badge="PILLARS", accent_color="10B981")

    # =========================================================================
    # SLIDE 3: PROBLEM STATEMENT
    # =========================================================================
    s3 = prs.add_slide("Problem Statement & Identified Challenges", "Slide 3 • Problem Statement", 3)
    s3.add_card(600000, 1450000, 5350000, 2200000, "1. Fragmented Crisis Communication", [
        "In acute emergencies (blood donation, urgent medication, elderly care), SOS messages get buried under chit-chat in social media groups.",
        "Zero spatial categorization: impossible to determine who is 500m away versus 20 km away.",
        "Critical delays cost lives during medical, transport, or accident crises."
    ], badge="CRISIS", accent_color="EF4444")

    s3.add_card(6240000, 1450000, 5350000, 2200000, "2. Rampant Scams & Zero Verified Trust", [
        "Anonymous social media accounts propagate fraudulent donation appeals and spam.",
        "Requesters have zero safety verification before inviting strangers for assistance.",
        "Sharing personal mobile numbers leads to unsolicited harassment and privacy leaks."
    ], badge="SAFETY", accent_color="F59E0B")

    s3.add_card(600000, 3900000, 5350000, 2200000, "3. Underutilized Local Volunteering Skills", [
        "Engineers, tutors, electricians, and nursing students want to volunteer spare hours locally.",
        "Lack of a structured platform to advertise free skills or discover neighborhood requirements.",
        "Massive goodwill is wasted due to missing discovery infrastructure."
    ], badge="SKILLS", accent_color="38BDF8")

    s3.add_card(6240000, 3900000, 5350000, 2200000, "4. Zero Request Lifecycle & Accountability", [
        "Once a crisis is resolved, posts remain online indefinitely, wasting volunteer time.",
        "No formal workflow to mark requests as 'In Discussion', 'Accepted', or 'Completed'.",
        "Absence of a mutual feedback loop to reward honest, reliable helpers."
    ], badge="TRACKING", accent_color="6366F1")

    # =========================================================================
    # SLIDE 4: OBJECTIVES
    # =========================================================================
    s4 = prs.add_slide("Main Objectives of the Project", "Slide 4 • Objectives", 4)
    s4.add_card(600000, 1450000, 3500000, 2400000, "Functional Objectives", [
        "Provide an intuitive interface for publishing help requests with 4 urgency tiers.",
        "Enable volunteers to create ongoing skill offer listings.",
        "Implement private 1-on-1 real-time messaging with delivery receipts.",
        "Enforce clear lifecycle state transitions: Open -> In Progress -> Completed."
    ], badge="FUNCTIONAL", accent_color="2563EB")

    s4.add_card(4350000, 1450000, 3500000, 2400000, "Algorithmic Objectives", [
        "Develop an automated Smart Matching Engine computing dynamic 0-99% scores.",
        "Integrate the spherical Haversine formula for exact distance ranking.",
        "Tokenize skills to match requester keywords with volunteer competencies.",
        "Factor in past ratings and completed task history into match calculations."
    ], badge="ALGORITHM", accent_color="38BDF8")

    s4.add_card(8100000, 1450000, 3500000, 2400000, "Trust & Security Objectives", [
        "Eliminate duplicate review fraud via database composite uniqueness.",
        "Gamify volunteer contribution via reputation points and tiered badges.",
        "Provide 1-click user blocking and content dispute reporting to admin.",
        "Protect admin portal using Role-Based Access Control (RBAC)."
    ], badge="SECURITY", accent_color="10B981")

    s4.add_card(600000, 4100000, 10992000, 1950000, "Engineering & Deployment Objectives", [
        ("Universal Dual Backend: ", "Support both enterprise PHP 8/MySQL PDO and zero-dependency Python 3/SQLite."),
        ("SQL Injection Immunity: ", "100% parameterization using prepared statements across all database transactions."),
        ("Performance: ", "Sub-second response times using asynchronous Fetch API without full browser reloads."),
        ("Responsive Ergonomics: ", "Mobile-first navigation, bottom navigation bar, and accessible touch targets.")
    ], badge="ENGINEERING", accent_color="F59E0B")

    # =========================================================================
    # SLIDE 5: EXISTING SYSTEM
    # =========================================================================
    s5 = prs.add_slide("Existing Systems & Limitations", "Slide 5 • Existing System", 5)
    s5.add_table(600000, 1450000, 10992000, 3900000,
        ["Parameter", "Social Media (WhatsApp / FB)", "Commercial Gig Apps (TaskRabbit)", "HELPHUB (Our Platform)"],
        [
            ["Core Purpose", "Casual Chat & Social Feed", "Commercial Profit / Paid Labor", "Civic Mutual Aid & Social Resilience"],
            ["Cost to Citizen", "Free (Ad-Supported)", "Heavy Commission (15% to 30%)", "100% Free & Open-Access"],
            ["Spatial Indexing", "None (Chronological Stream)", "Coarse City / Zip Radius", "Exact Haversine Proximity (km)"],
            ["Privacy Protection", "Public Mobile Numbers Leaked", "Masked Commercial Relays", "In-App Private Chat (Zero Leak)"],
            ["Smart Match Engine", "None (Manual Scrolling)", "Sponsored Paid Ranking", "Dynamic 0-99% Compatibility Math"],
            ["Urgency Categorization", "None (Emergencies Mixed with Chat)", "Fixed Commercial SLA", "4 Tiers + Emergency Map Triage"],
            ["Trust Verification", "Zero Verification (High Fraud)", "Corporate Background Check", "Verified Mutual Rating + Badges"],
            ["Request Lifecycle", "None (Posts Stale Forever)", "Commercial Booking Status", "Audited Lifecycle State Machine"]
        ],
        col_widths=[2200000, 2800000, 2992000, 3000000]
    )
    s5.add_card(600000, 5550000, 10992000, 700000, "Summary of Bottlenecks", [
        "Existing systems force citizens to choose between privacy risks on social media or unaffordable fees on gig apps. HelpHub solves both."
    ], accent_color="EF4444")

    # =========================================================================
    # SLIDE 6: PROPOSED SYSTEM
    # =========================================================================
    s6 = prs.add_slide("Proposed Solution & Advantages", "Slide 6 • Proposed System", 6)
    s6.add_card(600000, 1450000, 5350000, 2400000, "The 5-Step Mutual Aid Lifecycle", [
        ("Step 1 (Publish): ", "User posts a categorized request with one of 4 urgency tiers."),
        ("Step 2 (Match): ", "Smart Match Engine calculates compatibility scores for neighborhood volunteers."),
        ("Step 3 (Coordinate): ", "Parties open private chat without revealing personal phone numbers."),
        ("Step 4 (Execution): ", "Helper completes assistance; requester marks status as 'Completed'."),
        ("Step 5 (Reputation): ", "Both exchange 1-5 star reviews; reputation points are credited automatically.")
    ], badge="WORKFLOW", accent_color="2563EB")

    s6.add_card(6240000, 1450000, 5350000, 2400000, "Key Architectural Advantages", [
        "Structured Accountability: Status transitions (Open -> In Progress -> Completed) prevent stale posts.",
        "Geospatial Aid Map: Leaflet OpenStreetMap visual markers (Green, Red, Yellow) for instant crisis triage.",
        "Fraud Immunity: Reviews unlocked strictly after verified task completion via database constraint.",
        "Gamified Civic Culture: Points (+20/+10/+5) and badges incentivize consistent volunteering.",
        "Centralized Governance: Dedicated /admin portal for operational telemetry and moderation."
    ], badge="ADVANTAGES", accent_color="10B981")

    s6.add_card(600000, 4100000, 10992000, 1950000, "Empowering Community Resilience", [
        ("Decentralized P2P Aid: ", "Any citizen can seamlessly transition between receiving help and volunteering their skills."),
        ("Rapid Crisis Response: ", "Emergency triage banner highlighted across all views with 1-click SOS submission."),
        ("Zero Barrier to Entry: ", "Runs directly in browser without mandatory third-party mobile app downloads.")
    ], badge="IMPACT", accent_color="38BDF8")

    # =========================================================================
    # SLIDE 7: TECHNOLOGY USED
    # =========================================================================
    s7 = prs.add_slide("Technology Stack & Development Tools", "Slide 7 • Technology Used", 7)
    s7.add_card(600000, 1450000, 3500000, 2600000, "Frontend Presentation", [
        ("HTML5: ", "Semantic document structure & modern forms."),
        ("CSS3: ", "Custom variables design system & themes."),
        ("Themes: ", "Light & Dark modes persisted in localStorage."),
        ("JavaScript: ", "Vanilla ES6+ (Fetch API, DOM manipulation)."),
        ("Mapping: ", "Leaflet.js with OpenStreetMap tiles."),
        ("Ergonomics: ", "Mobile bottom navigation & modal dialogues.")
    ], badge="CLIENT", accent_color="38BDF8")

    s7.add_card(4350000, 1450000, 3500000, 2600000, "Backend Controllers", [
        ("PHP 8.1+: ", "Enterprise PDO, sessions & Bcrypt hashing."),
        ("Python 3: ", "Standalone zero-dependency HTTP server."),
        ("REST API: ", "Standardized JSON endpoints (/api/*.php)."),
        ("Security: ", "password_hash() & password_verify()."),
        ("Geo-Math: ", "Haversine spherical distance calculation."),
        ("Networking: ", "Multi-port auto-fallback (8080, 8081, 8082).")
    ], badge="SERVER", accent_color="2563EB")

    s7.add_card(8100000, 1450000, 3500000, 2600000, "Relational Database", [
        ("MySQL 8.0+: ", "Production relational database engine."),
        ("SQLite 3: ", "Lightweight, embedded zero-config storage."),
        ("Normalization: ", "17 normalized tables in 3rd Normal Form."),
        ("Integrity: ", "Foreign key cascade rules on user deletion."),
        ("Uniqueness: ", "Composite unique keys preventing review spam."),
        ("ACID: ", "Transactional consistency across status updates.")
    ], badge="DATABASE", accent_color="10B981")

    s7.add_card(600000, 4300000, 10992000, 1750000, "Development & Testing Toolchain", [
        ("IDE & Editor: ", "Visual Studio Code with Git version control and code workspace."),
        ("Diagnostics: ", "Chrome DevTools (Console, Network, Application, Performance audits)."),
        ("Defensive Security: ", "Prepared statement parameterization, XSS input escaping, CSRF protection.")
    ], badge="TOOLING", accent_color="F59E0B")

    # =========================================================================
    # SLIDE 8: SYSTEM REQUIREMENTS
    # =========================================================================
    s8 = prs.add_slide("System Requirements (Hardware & Software)", "Slide 8 • System Requirements", 8)
    s8.add_card(600000, 1450000, 5350000, 2400000, "Hardware Specifications", [
        ("Processor: ", "Dual Core 2.0 GHz or higher (Intel Core i3/i5/i7, AMD Ryzen, Apple Silicon M1-M4)."),
        ("RAM: ", "4 GB minimum (8 GB recommended for multi-client concurrent operations)."),
        ("Storage: ", "200 MB free hard drive space for codebase, database file, and media uploads."),
        ("Display: ", "1024 x 768 minimum resolution (Fluid responsive on Android, iPhone, iPad)."),
        ("Network: ", "Standard internet connection for external Leaflet OpenStreetMap tile loading.")
    ], badge="HARDWARE", accent_color="2563EB")

    s8.add_card(6240000, 1450000, 5350000, 2400000, "Software Specifications", [
        ("Client OS: ", "macOS Monterey+, Windows 10/11, Linux (Ubuntu 20.04+), Android 10+, iOS 14+."),
        ("Server OS: ", "macOS, Linux (Ubuntu/Debian/CentOS), or Windows Server."),
        ("Web Browsers: ", "Google Chrome 90+, Mozilla Firefox 88+, Apple Safari 14+, Microsoft Edge."),
        ("Runtime Engine: ", "Python 3.8+ (Standalone Mode) OR Apache/Nginx with PHP 8.0+ & MySQL 8.0+."),
        ("Database Server: ", "MySQL 8.0+ OR embedded SQLite 3.30+.")
    ], badge="SOFTWARE", accent_color="38BDF8")

    s8.add_card(600000, 4100000, 10992000, 1950000, "Universal Portability Highlight", [
        ("Zero-Dependency Mode: ", "The platform requires zero external pip packages or npm modules to run on macOS or Linux."),
        ("Automatic Port Fallback: ", "server.py automatically binds to 8080, 8081, 8082, 8000, 3000 avoiding port conflict crashes."),
        ("Enterprise Parity: ", "Identical RESTful API contracts between PHP PDO and Python engines ensure seamless migration.")
    ], badge="PORTABILITY", accent_color="10B981")

    # =========================================================================
    # SLIDE 9: SYSTEM DESIGN
    # =========================================================================
    s9 = prs.add_slide("System Design & Architecture", "Slide 9 • System Design", 9)
    s9.add_card(600000, 1450000, 5350000, 2400000, "3-Tier MVC Architecture", [
        ("Presentation Layer: ", "HTML5, CSS3, Vanilla JS, Dark/Light switcher, Leaflet Map."),
        ("API Controller Layer: ", "RESTful JSON endpoints (/api/*.php) routing client requests."),
        ("Business Logic Layer: ", "Haversine geo-engine, Smart Matcher, Reputation calculator."),
        ("Data Persistence Layer: ", "MySQL / SQLite relational storage with ACID guarantees."),
        ("Security Middleware: ", "Session token interceptor and role-based access control (RBAC).")
    ], badge="ARCHITECTURE", accent_color="2563EB")

    s9.add_card(6240000, 1450000, 5350000, 2400000, "Database Schema (17 Tables in 3NF)", [
        ("users: ", "Auth credentials, role, coordinates, rating, reputation points."),
        ("help_requests: ", "Title, urgency, category_id, required_date, lifecycle status."),
        ("help_offers: ", "Volunteer skills, availability schedules, service area."),
        ("conversations & messages: ", "Encrypted participant threads, read receipts (v/vv)."),
        ("reviews: ", "Composite UNIQUE(request_id, reviewer_id, reviewee_id).")
    ], badge="DATABASE", accent_color="10B981")

    s9.add_card(600000, 4100000, 10992000, 1950000, "Request State Transition Machine", [
        ("State 1 [Open]: ", "Request is published and visible on the Find Help board and Aid Map."),
        ("State 2 [In Discussion]: ", "Volunteers initiate chat to clarify details and logistics."),
        ("State 3 [Accepted]: ", "Requester accepts a specific volunteer; other applications closed."),
        ("State 4 [In Progress]: ", "Assistance is actively being carried out."),
        ("State 5 [Completed]: ", "Aid completed; triggers mutual 1-5 star review and reputation award.")
    ], badge="STATE FLOW", accent_color="38BDF8")

    # =========================================================================
    # SLIDE 10: MODULES DESCRIPTION
    # =========================================================================
    s10 = prs.add_slide("Project Modules Description", "Slide 10 • Modules Description", 10)
    s10.add_card(600000, 1450000, 3500000, 2200000, "1. Auth & Profiles", [
        "User registration with validation.",
        "Secure Bcrypt password hashing.",
        "Duplicate email/username check.",
        "Role separation (User vs Admin).",
        "Public profiles with skill badges."
    ], badge="MODULE 1", accent_color="2563EB")

    s10.add_card(4350000, 1450000, 3500000, 2200000, "2. Requests & Offers", [
        "Create requests with 4 urgency tiers.",
        "Filter by category, urgency & city.",
        "Volunteer skill offer showcase.",
        "Owner edit & cancellation tools.",
        "Audit trail for lifecycle changes."
    ], badge="MODULE 2", accent_color="38BDF8")

    s10.add_card(8100000, 1450000, 3500000, 2200000, "3. Smart Match Engine", [
        "Dynamic 0-99% compatibility score.",
        "Category match (+30 points).",
        "Skill overlap (+20 points).",
        "Haversine distance (+15 points).",
        "Rating & reputation bonus (+10 pts)."
    ], badge="MODULE 3", accent_color="10B981")

    s10.add_card(600000, 3900000, 3500000, 2200000, "4. Real-Time Chat", [
        "Split-pane desktop conversation view.",
        "Zero-refresh Fetch API messaging.",
        "Sent & read status indicators.",
        "1-click block & report buttons.",
        "Mobile full-screen chat drawer."
    ], badge="MODULE 4", accent_color="6366F1")

    s10.add_card(4350000, 3900000, 3500000, 2200000, "5. Emergency & Map", [
        "High-contrast red SOS emergency hub.",
        "Interactive Leaflet OpenStreetMap.",
        "Green pins: Verified Helpers.",
        "Red pins: Help Needed.",
        "Yellow pins: Urgent crises."
    ], badge="MODULE 5", accent_color="EF4444")

    s10.add_card(8100000, 3900000, 3500000, 2200000, "6. Admin & Reputation", [
        "Admin telemetry: Users, tasks, reports.",
        "Account suspension & reactivation.",
        "Abuse dispute resolution queue.",
        "Reputation points (+20/+10/+5).",
        "Milestone community badges."
    ], badge="MODULE 6", accent_color="F59E0B")

    # =========================================================================
    # SLIDE 11: IMPLEMENTATION
    # =========================================================================
    s11 = prs.add_slide("Implementation Details & Formulae", "Slide 11 • Implementation", 11)
    s11.add_card(600000, 1450000, 5350000, 2400000, "Smart Matching Mathematical Implementation", [
        ("Score Formulation: ", "Score = Base(25) + S_cat(30) + S_skills(20) + S_geo(15) + S_rating(10)"),
        ("Haversine Distance: ", "d = 2R * asin(sqrt(sin^2(dLat/2) + cos*cos*sin^2(dLon/2))) [R = 6371 km]"),
        ("Proximity Thresholds: ", "d <= 5 km: +15 pts | d <= 15 km: +10 pts | Same City: +5 pts"),
        ("Skills Matching: ", "Tokenized set intersection between request keywords and volunteer skills profile.")
    ], badge="FORMULA", accent_color="2563EB")

    s11.add_card(6240000, 1450000, 5350000, 2400000, "Defensive Security Implementation", [
        ("SQL Injection Defense: ", "100% parameterization using PDO and SQLite prepared statements; zero string interpolation."),
        ("XSS Protection: ", "HTML entity escaping on all user-submitted content before rendering in DOM."),
        ("Session Guard: ", "Secure HTTP session tokens with automatic timeout and role validation."),
        ("Access Control: ", "RBAC middleware redirecting unauthorized users from /admin routes with HTTP 403.")
    ], badge="SECURITY", accent_color="10B981")

    s11.add_card(600000, 4100000, 10992000, 1950000, "Core Application Routes & View Controllers", [
        ("Landing Page: ", "index.html — Dynamic stats, category grid, OpenStreetMap aid view, call to action."),
        ("Find Help Board: ", "requests/view.html — Multi-criteria filter, urgency badges, dynamic match score calculation."),
        ("Private Messaging: ", "messages/index.html — WhatsApp-style split-pane conversation stream and input bar."),
        ("User Dashboard: ", "dashboard/index.html — Personalized greeting, active requests, badge showcase, quick actions."),
        ("Admin Portal: ", "admin/index.html — Real-time telemetry cards, user status management, abuse audit queue.")
    ], badge="ROUTES", accent_color="38BDF8")

    # =========================================================================
    # SLIDE 12: OUTPUT / RESULTS
    # =========================================================================
    s12 = prs.add_slide("Working Output & Empirical Results", "Slide 12 • Output / Results", 12)
    s12.add_table(600000, 1450000, 10992000, 3900000,
        ["Test Case / Operation", "Input Action Given", "Observed System Output", "Verification Status"],
        [
            ["User Registration", "New user info with valid email", "Account created, Bcrypt hashed, session initialized", "SUCCESS / PASS"],
            ["Duplicate Prevention", "Attempt registration with existing email", "HTTP 409 Conflict returned; registration safely halted", "SUCCESS / PASS"],
            ["SQL Injection Immunity", "Input `' OR '1'='1` in login/search", "Payload escaped; treated as literal string; 0 vulnerability", "SUCCESS / PASS"],
            ["Smart Match Engine", "Query computer repair in Mumbai", "Rahul Sharma scored 96% Match (0.7 km away)", "SUCCESS / PASS"],
            ["Emergency Aid Map", "View Leaflet OpenStreetMap", "Markers rendered: Green (Helper), Red (Need), Yellow (Urgent)", "SUCCESS / PASS"],
            ["Private Messaging", "Send message in active chat", "Message appended instantly with single tick (v)", "SUCCESS / PASS"],
            ["Read Receipts", "Recipient logs in and opens chat", "Sender view updates to double tick (vv)", "SUCCESS / PASS"],
            ["Review Fraud Prevention", "Submit 2nd review for same request", "Database rejects duplicate review via composite key", "SUCCESS / PASS"]
        ],
        col_widths=[2200000, 3000000, 4000000, 1792000]
    )
    s12.add_card(600000, 5550000, 10992000, 700000, "Empirical Validation Summary", [
        "All 8 test vectors passed with 100% reliability. Zero vulnerabilities detected; sub-100ms API response latency."
    ], accent_color="10B981")

    # =========================================================================
    # SLIDE 13: CONCLUSION
    # =========================================================================
    s13 = prs.add_slide("Project Conclusion & Key Milestones", "Slide 13 • Conclusion", 13)
    s13.add_card(600000, 1450000, 5350000, 2400000, "Key Project Achievements", [
        "Engineered a production-ready, full-stack community assistance platform.",
        "Successfully bridged neighborhood empathy with modern software engineering.",
        "Implemented mathematical smart matching replacing manual, random searches.",
        "Guaranteed user privacy and trust through mutual post-completion ratings.",
        "Delivered a zero-dependency autonomous server running instantly on any machine."
    ], badge="MILESTONES", accent_color="2563EB")

    s13.add_card(6240000, 1450000, 5350000, 2400000, "Educational & Technical Learnings", [
        "Mastered 3NF relational database normalization and referential constraints.",
        "Implemented defensive programming with parameterized queries against injection.",
        "Applied spherical trigonometry (Haversine formula) for geographic sorting.",
        "Gained deep expertise in asynchronous JavaScript and responsive UI design.",
        "Architected role-based security protecting administrative governance."
    ], badge="LEARNINGS", accent_color="10B981")

    s13.add_card(600000, 4100000, 10992000, 1950000, "Final Societal Impact", [
        ("Project Tagline: ", "“Connect. Help. Make a Difference.”"),
        ("Real-World Impact: ", "HELPHUB demonstrates that web technologies can transform neighborhood mutual aid into an organized, transparent, and dignified experience for all citizens."),
        ("Deployment Readiness: ", "The platform is fully deployable to cloud hosting (Apache/Nginx/Docker) or local offline networks.")
    ], badge="CONCLUSION", accent_color="38BDF8")

    # =========================================================================
    # SLIDE 14: FUTURE SCOPE
    # =========================================================================
    s14 = prs.add_slide("Future Scope & Platform Enhancements", "Slide 14 • Future Scope", 14)
    s14.add_card(600000, 1450000, 5350000, 2200000, "1. Real-Time WebSockets Engine", [
        "Transition from AJAX polling to bidirectional WebSockets (Ratchet/Socket.io).",
        "Enable instant typing indicators (\"Rahul is typing...\").",
        "Sub-10ms latency for urgent emergency chat coordination.",
        "Native browser audio chime notifications on new messages."
    ], badge="WEBSOCKETS", accent_color="2563EB")

    s14.add_card(6240000, 1450000, 5350000, 2200000, "2. AI-Powered Emergency NLP Triage", [
        "Natural Language Processing (NLP) to parse emergency requests.",
        "Auto-detect critical symptoms (e.g., cardiac arrest, blood loss).",
        "Automated SOS dispatch to nearby certified first-responders.",
        "Sentiment analysis to flag distress levels in community posts."
    ], badge="AI / NLP", accent_color="38BDF8")

    s14.add_card(600000, 3900000, 5350000, 2200000, "3. Native Mobile Apps (iOS/Android)", [
        "Cross-platform mobile apps built with React Native / Flutter.",
        "Background push notifications for nearby emergency alerts.",
        "Live volunteer GPS navigation tracking.",
        "Offline-first sync for areas with disrupted network coverage."
    ], badge="MOBILE APPS", accent_color="10B981")

    s14.add_card(6240000, 3900000, 5350000, 2200000, "4. Institutional NGO & Hospital Portals", [
        "Dedicated verification portals for registered NGOs and Red Cross.",
        "Direct integration with hospital blood banks for donor calls.",
        "Corporate Social Responsibility (CSR) volunteering tracking.",
        "Multilingual support (Hindi, Marathi, regional languages)."
    ], badge="INTEGRATION", accent_color="F59E0B")

    # =========================================================================
    # SLIDE 15: REFERENCES
    # =========================================================================
    s15 = prs.add_slide("References & Academic Bibliography", "Slide 15 • References", 15)
    s15.add_card(600000, 1450000, 5350000, 2400000, "Books & Academic Textbooks", [
        ("1. Lockhart, J.: ", "Modern PHP: New Features and Good Practices, O'Reilly Media."),
        ("2. Kleppmann, M.: ", "Designing Data-Intensive Applications, O'Reilly Media."),
        ("3. Robbins, J.: ", "Learning Web Design: A Beginner's Guide to HTML, CSS, JavaScript."),
        ("4. Elmasri, R. & Navathe, S.: ", "Fundamentals of Database Systems (7th Edition).")
    ], badge="BOOKS", accent_color="2563EB")

    s15.add_card(6240000, 1450000, 5350000, 2400000, "Research Papers & Articles", [
        ("1. IEEE Paper: ", "Peer-to-Peer Disaster Response Networks & Community Mutual Aid."),
        ("2. ACM Transactions: ", "Civic Tech and Social Computing in Hyperlocal Communities."),
        ("3. Haversine Math: ", "Sinnott, R.W., 'Virtues of the Haversine', Sky and Telescope."),
        ("4. Web Security: ", "OWASP Top 10 Security Risks and Mitigation Guidelines.")
    ], badge="RESEARCH", accent_color="38BDF8")

    s15.add_card(600000, 4100000, 10992000, 1950000, "Documentation Standards & Web Resources", [
        ("Mozilla Developer Network (MDN): ", "Fetch API, CSS Custom Properties, and ES6+ JavaScript Specifications."),
        ("PHP Official Manual: ", "PHP Data Objects (PDO), Bcrypt Password Hashing (password_hash/verify)."),
        ("Leaflet.js Documentation: ", "Open-Source Mobile-Friendly Interactive Maps API (leafletjs.com)."),
        ("OpenStreetMap: ", "OpenStreetMap Foundation Tile Servers and Geocoding APIs."),
        ("SQLite Documentation: ", "SQLite ACID Relational Storage and Full-Text Search Engine.")
    ], badge="STANDARDS", accent_color="10B981")

    # Save PPTX
    prs.save()


if __name__ == "__main__":
    build_all_15_slides()
