@php
    $sanitize = function($text, $default = '') {
        if (empty($text)) return $default;
        $search = ['Oncology AI', 'AI', 'kecerdasan buatan', 'ct scan', 'scan'];
        $replace = ['Specialist Care', 'Care & Diagnostics', 'solusi klinis profesional', 'kesehatan', 'kesehatan'];
        return str_ireplace($search, $replace, $text);
    };
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Orvella — Premium clinic consultation & specialist diagnostics platform. Connect with certified doctors, book consultations, and manage your health journey.">
    <title>Orvella — Premium Clinic & Specialist Consultation</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link href="https://fonts.googleapis.com/css2?family=Inter:ital,wght@0,300;0,400;0,500;0,600;0,700;1,400&family=Outfit:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        :root {
            --blue-primary: #2563EB; /* Premium Blue primary theme */
            --blue-deep: #1E40AF;    /* Deep Blue */
            --blue-soft: #EFF6FF;    /* Soft Blue backdrop */
            --teal: #06B6D4;         /* Turquoise/Cyan accent */
            --rose-accent: #E11D48;  /* Rose accent representing cervical cancer ribbon */
            --rose-soft: #FFF1F2;    /* Soft Rose backdrop */
            --navy: #0F172A;
            --text-muted: #64748B;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Inter', sans-serif;
            color: var(--navy);
            background: #FFFFFF;
            -webkit-font-smoothing: antialiased;
            overflow-x: hidden;
        }

        h1, h2, h3, h4, h5 { font-family: 'Outfit', sans-serif; }

        /* === NAVBAR === */
        .navbar {
            position: fixed;
            top: 0; left: 0; right: 0;
            z-index: 100;
            background: rgba(255,255,255,0.92);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(226,232,240,0.8);
            transition: all 0.3s ease;
        }
        .navbar.scrolled {
            box-shadow: 0 4px 24px rgba(0,0,0,0.06);
        }
        .nav-inner {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 24px;
            height: 72px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .logo {
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
        }
        .logo-icon {
            width: 40px; height: 40px;
            background: linear-gradient(135deg, var(--blue-primary), var(--rose-accent));
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-family: 'Outfit', sans-serif;
            font-weight: 900;
            font-size: 20px;
            box-shadow: 0 4px 12px rgba(13,148,136,0.35);
        }
        .logo-text {
            font-family: 'Outfit', sans-serif;
            font-weight: 800;
            font-size: 22px;
            color: var(--navy);
            letter-spacing: -0.5px;
        }
        .nav-links {
            display: flex;
            gap: 36px;
            list-style: none;
        }
        .nav-links a {
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            color: #475569;
            transition: color 0.2s;
        }
        .nav-links a:hover { color: var(--blue-primary); }
        .nav-cta {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .btn-outline {
            padding: 10px 22px;
            border: 1.5px solid #E2E8F0;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 600;
            color: #475569;
            text-decoration: none;
            transition: all 0.2s;
            background: white;
        }
        .btn-outline:hover {
            border-color: var(--blue-primary);
            color: var(--blue-primary);
            background: var(--blue-soft);
        }
        .btn-primary {
            padding: 10px 24px;
            background: linear-gradient(135deg, var(--blue-primary), var(--teal));
            border-radius: 10px;
            font-size: 14px;
            font-weight: 600;
            color: white;
            text-decoration: none;
            transition: all 0.25s;
            box-shadow: 0 4px 14px rgba(13,148,136,0.3);
        }
        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 8px 20px rgba(13,148,136,0.4);
        }

        /* === HERO === */
        .hero {
            padding: 160px 24px 100px;
            max-width: 1200px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 80px;
            align-items: center;
        }
        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: var(--rose-soft);
            border: 1px solid rgba(225,29,72,0.15);
            border-radius: 100px;
            padding: 6px 16px;
            font-size: 12px;
            font-weight: 600;
            color: var(--rose-accent);
            letter-spacing: 0.5px;
            margin-bottom: 24px;
        }
        .hero-badge .dot {
            width: 7px; height: 7px;
            border-radius: 50%;
            background: var(--rose-accent);
            animation: pulse-dot 2s infinite;
        }
        @keyframes pulse-dot {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.6; transform: scale(1.3); }
        }
        .hero h1 {
            font-size: clamp(38px, 5vw, 58px);
            font-weight: 800;
            line-height: 1.12;
            letter-spacing: -1.5px;
            color: #0F172A;
            margin-bottom: 20px;
        }
        .hero h1 .accent {
            background: linear-gradient(135deg, var(--blue-primary), var(--rose-accent));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .hero-sub {
            font-size: 17px;
            color: #64748B;
            line-height: 1.7;
            margin-bottom: 36px;
            max-width: 480px;
        }
        .hero-actions {
            display: flex;
            gap: 14px;
            align-items: center;
            flex-wrap: wrap;
            margin-bottom: 44px;
        }
        .btn-hero-primary {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 15px 32px;
            background: linear-gradient(135deg, var(--blue-primary), var(--blue-deep));
            color: white;
            border-radius: 14px;
            font-size: 15px;
            font-weight: 700;
            text-decoration: none;
            box-shadow: 0 8px 24px rgba(13,148,136,0.35);
            transition: all 0.25s;
        }
        .btn-hero-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 14px 32px rgba(13,148,136,0.45);
        }
        .btn-hero-secondary {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 15px 28px;
            background: white;
            color: #374151;
            border: 1.5px solid #E5E7EB;
            border-radius: 14px;
            font-size: 15px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.2s;
        }
        .btn-hero-secondary:hover {
            border-color: #CBD5E1;
            background: #F8FAFC;
        }
        .trust-bar {
            display: flex;
            align-items: center;
            gap: 24px;
            flex-wrap: wrap;
        }
        .trust-bar-label {
            font-size: 11px;
            font-weight: 600;
            color: #94A3B8;
            letter-spacing: 1px;
            text-transform: uppercase;
        }
        .trust-chips {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }
        .trust-chip {
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 12px;
            font-weight: 600;
            color: #475569;
        }
        .trust-chip svg { color: #10B981; }

        /* === HERO VISUAL === */
        .hero-visual {
            position: relative;
        }
        .hero-card-main {
            background: white;
            border: 1px solid #E2E8F0;
            border-radius: 24px;
            padding: 28px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.08);
            position: relative;
            overflow: hidden;
        }
        .hero-card-main::before {
            content: '';
            position: absolute;
            top: -60px; right: -60px;
            width: 200px; height: 200px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(37,99,235,0.06) 0%, transparent 70%);
        }
        .card-header-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        .card-title-sm {
            font-size: 13px;
            font-weight: 700;
            color: #0F172A;
            font-family: 'Outfit', sans-serif;
        }
        .badge-active {
            display: flex;
            align-items: center;
            gap: 5px;
            background: #ECFDF5;
            border: 1px solid #A7F3D0;
            border-radius: 100px;
            padding: 4px 10px;
            font-size: 11px;
            font-weight: 600;
            color: #059669;
        }
        .badge-active .dot-green {
            width: 6px; height: 6px;
            border-radius: 50%;
            background: #10B981;
            animation: pulse-dot 2s infinite;
        }
        .doctor-card {
            display: flex;
            align-items: center;
            gap: 14px;
            background: #F8FAFC;
            border: 1px solid #F1F5F9;
            border-radius: 16px;
            padding: 16px 18px;
            margin-bottom: 12px;
            transition: all 0.2s;
        }
        .doctor-card:hover {
            border-color: #BFDBFE;
            background: #EFF6FF;
            transform: translateX(3px);
        }
        .doc-avatar {
            width: 46px; height: 46px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Outfit', sans-serif;
            font-weight: 700;
            font-size: 16px;
            flex-shrink: 0;
        }
        .doc-avatar.blue { background: #EFF6FF; color: #2563EB; }
        .doc-avatar.teal { background: #F0FDFA; color: #0D9488; }
        .doc-info { flex: 1; }
        .doc-name { font-size: 14px; font-weight: 700; color: #0F172A; font-family: 'Outfit', sans-serif; }
        .doc-spec { font-size: 11px; font-weight: 500; color: #94A3B8; margin-top: 2px; }
        .slot-badge {
            background: #EFF6FF;
            border-radius: 8px;
            padding: 5px 10px;
            font-size: 11px;
            font-weight: 600;
            color: #2563EB;
            white-space: nowrap;
        }
        .card-mini-stats {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            margin-top: 16px;
        }
        .mini-stat {
            background: #F8FAFC;
            border: 1px solid #F1F5F9;
            border-radius: 14px;
            padding: 14px 16px;
            text-align: center;
        }
        .mini-stat .value {
            font-family: 'Outfit', sans-serif;
            font-size: 22px;
            font-weight: 800;
            color: #0F172A;
        }
        .mini-stat .value span { color: #2563EB; }
        .mini-stat .label { font-size: 11px; color: #94A3B8; font-weight: 500; margin-top: 2px; }

        /* Floating cards */
        .float-badge-1 {
            position: absolute;
            top: -20px;
            right: -28px;
            background: white;
            border: 1px solid #E2E8F0;
            border-radius: 14px;
            padding: 12px 16px;
            display: flex;
            align-items: center;
            gap: 10px;
            box-shadow: 0 8px 24px rgba(0,0,0,0.08);
            animation: float1 4s ease-in-out infinite;
        }
        .float-badge-2 {
            position: absolute;
            bottom: -24px;
            left: -32px;
            background: white;
            border: 1px solid #E2E8F0;
            border-radius: 14px;
            padding: 12px 16px;
            display: flex;
            align-items: center;
            gap: 10px;
            box-shadow: 0 8px 24px rgba(0,0,0,0.08);
            animation: float2 4.5s ease-in-out infinite;
        }
        @keyframes float1 {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-8px); }
        }
        @keyframes float2 {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(8px); }
        }
        .float-icon {
            width: 36px; height: 36px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .float-icon.green { background: #ECFDF5; color: #10B981; }
        .float-icon.blue { background: #EFF6FF; color: #2563EB; }
        .float-text strong { font-size: 13px; font-weight: 700; color: #0F172A; display: block; font-family: 'Outfit', sans-serif; }
        .float-text span { font-size: 11px; color: #94A3B8; font-weight: 500; }

        /* === SOCIAL PROOF STRIP === */
        .proof-strip {
            background: #F8FAFC;
            border-top: 1px solid #F1F5F9;
            border-bottom: 1px solid #F1F5F9;
            padding: 28px 24px;
        }
        .proof-inner {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 48px;
            flex-wrap: wrap;
        }
        .proof-item {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .proof-icon {
            width: 38px; height: 38px;
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            font-size: 18px;
        }
        .proof-icon.blue-soft { background: #EFF6FF; color: #2563EB; }
        .proof-icon.green-soft { background: #ECFDF5; color: #059669; }
        .proof-icon.purple-soft { background: #F5F3FF; color: #7C3AED; }
        .proof-text strong { font-family: 'Outfit', sans-serif; font-size: 20px; font-weight: 800; color: #0F172A; display: block; }
        .proof-text span { font-size: 12px; color: #94A3B8; font-weight: 500; }
        .proof-divider { width: 1px; height: 40px; background: #E2E8F0; }

        /* === FEATURES === */
        .features {
            padding: 100px 24px;
            max-width: 1200px;
            margin: 0 auto;
        }
        .section-label {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: #EFF6FF;
            border: 1px solid #BFDBFE;
            border-radius: 100px;
            padding: 6px 16px;
            font-size: 12px;
            font-weight: 600;
            color: #2563EB;
            letter-spacing: 0.5px;
            margin-bottom: 16px;
        }
        .section-title {
            font-size: clamp(28px, 4vw, 44px);
            font-weight: 800;
            color: #0F172A;
            letter-spacing: -1px;
            line-height: 1.15;
            margin-bottom: 14px;
        }
        .section-sub {
            font-size: 16px;
            color: #64748B;
            max-width: 520px;
            line-height: 1.7;
        }
        .section-header { margin-bottom: 56px; }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 24px;
        }
        .feature-card {
            background: white;
            border: 1px solid #E2E8F0;
            border-radius: 20px;
            padding: 32px 28px;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        .feature-card::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(135deg, rgba(13,148,136,0.03), transparent);
            opacity: 0;
            transition: opacity 0.3s;
        }
        .feature-card:hover {
            border-color: rgba(13,148,136,0.2);
            transform: translateY(-4px);
            box-shadow: 0 20px 40px rgba(13,148,136,0.08);
        }
        .feature-card:hover::before { opacity: 1; }
        .feat-icon {
            width: 52px; height: 52px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 22px;
        }
        .feat-icon.blue { background: var(--rose-soft); color: var(--rose-accent); }
        .feat-icon.teal { background: var(--blue-soft); color: var(--blue-primary); }
        .feat-icon.purple { background: #F5F3FF; color: #7C3AED; }
        .feat-icon.amber { background: #FFFBEB; color: #D97706; }
        .feature-card h3 { font-size: 18px; font-weight: 700; color: #0F172A; margin-bottom: 10px; }
        .feature-card p { font-size: 14px; color: #64748B; line-height: 1.65; }

        /* === HOW IT WORKS === */
        .how-section {
            background: #F8FAFC;
            padding: 100px 24px;
        }
        .how-inner {
            max-width: 1200px;
            margin: 0 auto;
        }
        .how-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 32px;
            margin-top: 56px;
        }
        .how-step {
            text-align: center;
        }
        .step-num {
            width: 56px; height: 56px;
            border-radius: 16px;
            background: linear-gradient(135deg, var(--blue-primary), var(--blue-deep));
            color: white;
            font-family: 'Outfit', sans-serif;
            font-size: 22px;
            font-weight: 800;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            box-shadow: 0 8px 20px rgba(13,148,136,0.3);
        }
        .how-step h3 { font-size: 18px; font-weight: 700; color: #0F172A; margin-bottom: 10px; }
        .how-step p { font-size: 14px; color: #64748B; line-height: 1.65; max-width: 260px; margin: 0 auto; }

        /* === TESTIMONIALS === */
        .testi-section {
            padding: 100px 24px;
            max-width: 1200px;
            margin: 0 auto;
        }
        .testi-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 24px;
            margin-top: 56px;
        }
        .testi-card {
            background: white;
            border: 1px solid #E2E8F0;
            border-radius: 20px;
            padding: 28px;
            transition: all 0.3s;
        }
        .testi-card:hover {
            border-color: #BFDBFE;
            box-shadow: 0 12px 32px rgba(37,99,235,0.07);
            transform: translateY(-3px);
        }
        .stars {
            display: flex;
            gap: 3px;
            margin-bottom: 16px;
        }
        .star { color: #F59E0B; font-size: 14px; }
        .testi-text {
            font-size: 14px;
            color: #475569;
            line-height: 1.7;
            margin-bottom: 22px;
            font-style: italic;
        }
        .testi-author {
            display: flex;
            align-items: center;
            gap: 12px;
            border-top: 1px solid #F1F5F9;
            padding-top: 18px;
        }
        .author-avatar {
            width: 40px; height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Outfit', sans-serif;
            font-weight: 700;
            font-size: 14px;
            flex-shrink: 0;
        }
        .author-avatar.blue { background: #EFF6FF; color: #2563EB; }
        .author-avatar.teal { background: #F0FDFA; color: #0D9488; }
        .author-avatar.purple { background: #F5F3FF; color: #7C3AED; }
        .author-name { font-size: 14px; font-weight: 700; color: #0F172A; font-family: 'Outfit', sans-serif; }
        .author-role { font-size: 12px; color: #94A3B8; font-weight: 500; }

        /* === CTA SECTION === */
        .cta-section {
            background: linear-gradient(135deg, var(--blue-deep) 0%, #0F172A 60%, var(--blue-primary) 100%);
            padding: 100px 24px;
            position: relative;
            overflow: hidden;
        }
        .cta-section::before {
            content: '';
            position: absolute;
            top: -50%; left: -20%;
            width: 600px; height: 600px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(20,184,166,0.15) 0%, transparent 60%);
        }
        .cta-section::after {
            content: '';
            position: absolute;
            bottom: -30%; right: -10%;
            width: 400px; height: 400px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(244,63,94,0.1) 0%, transparent 60%);
        }
        .cta-inner {
            max-width: 700px;
            margin: 0 auto;
            text-align: center;
            position: relative;
            z-index: 10;
        }
        .cta-inner h2 {
            font-size: clamp(28px, 4vw, 46px);
            font-weight: 800;
            color: white;
            letter-spacing: -1px;
            margin-bottom: 16px;
        }
        .cta-inner p {
            font-size: 17px;
            color: rgba(255,255,255,0.75);
            margin-bottom: 36px;
            line-height: 1.65;
        }
        .btn-cta {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 17px 38px;
            background: white;
            color: #1E40AF;
            border-radius: 14px;
            font-size: 16px;
            font-weight: 700;
            text-decoration: none;
            transition: all 0.25s;
            box-shadow: 0 8px 24px rgba(0,0,0,0.2);
            font-family: 'Outfit', sans-serif;
        }
        .btn-cta:hover {
            transform: translateY(-2px);
            box-shadow: 0 16px 36px rgba(0,0,0,0.25);
        }

        /* === CONTACT === */
        .contact-section {
            padding: 100px 24px;
            max-width: 1200px;
            margin: 0 auto;
        }
        .contact-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 64px;
            align-items: start;
            margin-top: 56px;
        }
        .contact-item {
            display: flex;
            gap: 16px;
            align-items: flex-start;
            margin-bottom: 28px;
        }
        .contact-icon {
            width: 44px; height: 44px;
            border-radius: 12px;
            background: #EFF6FF;
            color: #2563EB;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .contact-item h4 { font-size: 14px; font-weight: 700; color: #0F172A; margin-bottom: 4px; }
        .contact-item p { font-size: 14px; color: #64748B; }
        .contact-card {
            background: #F8FAFC;
            border: 1px solid #E2E8F0;
            border-radius: 24px;
            padding: 36px;
        }
        .contact-card h3 { font-size: 20px; font-weight: 700; color: #0F172A; margin-bottom: 12px; }
        .contact-card p { font-size: 14px; color: #64748B; line-height: 1.65; margin-bottom: 24px; }
        .cert-badges {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            border-top: 1px solid #E2E8F0;
            padding-top: 20px;
        }
        .cert-badge {
            display: flex;
            align-items: center;
            gap: 6px;
            background: white;
            border: 1px solid #E2E8F0;
            border-radius: 8px;
            padding: 7px 14px;
            font-size: 11px;
            font-weight: 600;
            color: #475569;
        }
        .cert-badge svg { color: #10B981; }

        /* === FOOTER === */
        .footer {
            background: #0F172A;
            padding: 60px 24px 32px;
            color: white;
        }
        .footer-inner {
            max-width: 1200px;
            margin: 0 auto;
        }
        .footer-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding-bottom: 36px;
            border-bottom: 1px solid rgba(255,255,255,0.08);
            margin-bottom: 28px;
            flex-wrap: wrap;
            gap: 20px;
        }
        .footer-logo-text {
            font-family: 'Outfit', sans-serif;
            font-weight: 800;
            font-size: 20px;
            color: white;
        }
        .footer-logo-sub { font-size: 12px; color: rgba(255,255,255,0.45); margin-top: 3px; font-weight: 400; }
        .footer-links {
            display: flex;
            gap: 32px;
        }
        .footer-links a {
            text-decoration: none;
            font-size: 13px;
            color: rgba(255,255,255,0.5);
            transition: color 0.2s;
            font-weight: 500;
        }
        .footer-links a:hover { color: rgba(255,255,255,0.9); }
        .footer-bottom {
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 12px;
        }
        .footer-copy { font-size: 13px; color: rgba(255,255,255,0.35); }
        .footer-secure {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 12px;
            color: rgba(255,255,255,0.4);
        }

        /* === SCROLL ANIMATION === */
        .anim-card {
            opacity: 0;
            transform: translateY(22px);
            transition: opacity 0.55s ease, transform 0.55s ease;
        }
        .anim-card.is-visible {
            opacity: 1;
            transform: translateY(0);
        }
        /* Stagger delays for grid children */
        .features-grid .anim-card:nth-child(1),
        .testi-grid .anim-card:nth-child(1),
        .how-grid .anim-card:nth-child(1) { transition-delay: 0s; }
        .features-grid .anim-card:nth-child(2),
        .testi-grid .anim-card:nth-child(2),
        .how-grid .anim-card:nth-child(2) { transition-delay: 0.1s; }
        .features-grid .anim-card:nth-child(3),
        .testi-grid .anim-card:nth-child(3),
        .how-grid .anim-card:nth-child(3) { transition-delay: 0.2s; }
        .features-grid .anim-card:nth-child(4) { transition-delay: 0.05s; }
        .features-grid .anim-card:nth-child(5) { transition-delay: 0.15s; }
        .features-grid .anim-card:nth-child(6) { transition-delay: 0.25s; }

        /* === RESPONSIVE === */
        @media (max-width: 1024px) {
            .hero { grid-template-columns: 1fr; gap: 48px; padding: 120px 24px 60px; }
            .features-grid { grid-template-columns: repeat(2, 1fr); }
            .testi-grid { grid-template-columns: 1fr 1fr; }
            .how-grid { grid-template-columns: 1fr; max-width: 400px; margin-left: auto; margin-right: auto; }
            .contact-grid { grid-template-columns: 1fr; gap: 40px; }
        }
        @media (max-width: 768px) {
            .nav-links { display: none; }
            .features-grid { grid-template-columns: 1fr; }
            .testi-grid { grid-template-columns: 1fr; }
            .proof-inner { gap: 24px; }
            .proof-divider { display: none; }
            .float-badge-1, .float-badge-2 { display: none; }
            .footer-top { flex-direction: column; }
            .footer-links { flex-wrap: wrap; gap: 16px; }
        }
        /* No-JS fallback: show everything */
        @media print {
            .anim-card { opacity: 1 !important; transform: none !important; }
        }
    </style>
</head>
<body>

    <!-- NAVBAR -->
    <nav class="navbar" id="mainNav">
        <div class="nav-inner">
            <a href="#" class="logo">
                <div class="logo-icon">O</div>
                <span class="logo-text">Orvella</span>
            </a>
            <ul class="nav-links">
                <li><a href="#features">Features</a></li>
                <li><a href="#how-it-works">How It Works</a></li>
                <li><a href="#testimonials">Testimonials</a></li>
                <li><a href="#contact">Contact</a></li>
            </ul>
            <div class="nav-cta">
                <a href="{{ route('login') }}" class="btn-primary">Sign In</a>
            </div>
        </div>
    </nav>

    <!-- HERO -->
    <section class="hero" id="home">
        <!-- Left: Text -->
        <div>
            <div class="hero-badge">
                <span class="dot"></span>
                🎗️ Cervical Cancer Care & Diagnostics
            </div>
            <h1>
                Early Detection.<br>
                <span class="accent">Specialist Care.</span><br>
                One Platform.
            </h1>
            <p class="hero-sub">
                Orvella connects you with certified Sp.OG specialists for dedicated cervical cancer screening, expert clinical diagnostics, and personalized treatment plans in a secure platform.
            </p>
            <div class="hero-actions">
                <a href="{{ route('login') }}" class="btn-hero-primary">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                    Access Patient Portal
                </a>
                <a href="#features" class="btn-hero-secondary">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polygon points="10 8 16 12 10 16 10 8"/></svg>
                    See Our Specialty
                </a>
            </div>
            <div class="trust-bar">
                <span class="trust-bar-label">Trusted by:</span>
                <div class="trust-chips">
                    <span class="trust-chip">
                        <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><polyline points="9 12 11 14 15 10"/></svg>
                        KEMENKES RI
                    </span>
                    <span class="trust-chip">
                        <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="6"/><path d="M15.477 12.89L17 22l-5-3-5 3 1.523-9.11"/></svg>
                        ISO 9001 Certified
                    </span>
                    <span class="trust-chip">
                        <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>
                        IDI Partner
                    </span>
                </div>
            </div>
        </div>

        <!-- Right: Visual -->
        <div class="hero-visual">
            <div style="position:relative; padding: 28px 28px 28px 8px;">
                <!-- Floating badges -->
                <div class="float-badge-1">
                    <div class="float-icon green">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                    </div>
                    <div class="float-text">
                        <strong>98% Accuracy</strong>
                        <span>Diagnostic rate</span>
                    </div>
                </div>
                <div class="float-badge-2">
                    <div class="float-icon blue">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                    </div>
                    <div class="float-text">
                        <strong>Screening Booking</strong>
                        <span>Available today</span>
                    </div>
                </div>

                <div class="hero-card-main">
                    <div class="card-header-row">
                        <span class="card-title-sm">Active Oncologists</span>
                        <div class="badge-active">
                            <span class="dot-green"></span>
                            Online
                        </div>
                    </div>

                    <div class="doctor-card">
                        <div class="doc-avatar blue">RM</div>
                        <div class="doc-info">
                            <div class="doc-name">Dr. Reza Mahendra, Sp.OG (K)</div>
                            <div class="doc-spec">Gynae-Oncology Specialist</div>
                        </div>
                        <div class="slot-badge">3 Slot</div>
                    </div>

                    <div class="doctor-card">
                        <div class="doc-avatar teal">SA</div>
                        <div class="doc-info">
                            <div class="doc-name">Dr. Sari Andini, Sp.OG</div>
                            <div class="doc-spec">Obstetrics & Gynecology</div>
                        </div>
                        <div class="slot-badge">2 Slot</div>
                    </div>

                    <div class="card-mini-stats">
                        <div class="mini-stat">
                            <div class="value">{{ $configs['stats_patients'] ?? '500' }}<span>+</span></div>
                            <div class="label">Patients Screened</div>
                        </div>
                        <div class="mini-stat">
                            <div class="value">{{ $configs['stats_doctors'] ?? '12' }}<span>+</span></div>
                            <div class="label">Oncologists</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- PROOF STRIP -->
    <div class="proof-strip">
        <div class="proof-inner">
            <div class="proof-item">
                <div class="proof-icon blue-soft">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                </div>
                <div class="proof-text">
                    <strong>{{ $configs['stats_patients'] ?? '500+' }}</strong>
                    <span>Registered Patients</span>
                </div>
            </div>
            <div class="proof-divider"></div>
            <div class="proof-item">
                <div class="proof-icon green-soft">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                </div>
                <div class="proof-text">
                    <strong>{{ $configs['stats_doctors'] ?? '12+' }}</strong>
                    <span>Certified Specialists</span>
                </div>
            </div>
            <div class="proof-divider"></div>
            <div class="proof-item">
                <div class="proof-icon purple-soft">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                </div>
                <div class="proof-text">
                    <strong>{{ $configs['stats_scans'] ?? '1,200+' }}</strong>
                    <span>Consultations Done</span>
                </div>
            </div>
            <div class="proof-divider"></div>
            <div class="proof-item">
                <div class="proof-icon blue-soft">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
                </div>
                <div class="proof-text">
                    <strong>98%</strong>
                    <span>Patient Satisfaction</span>
                </div>
            </div>
        </div>
    </div>

    <!-- FEATURES -->
    <section class="features" id="features">
        <div class="section-header">
            <div class="section-label">Why Orvella</div>
            <h2 class="section-title">Specialist Care for Cervical Cancer & Diagnostics</h2>
            <p class="section-sub">An integrated clinical ecosystem designed specifically for early cervical detection, medical record management, and expert Sp.OG oncological consultations.</p>
        </div>
        <div class="features-grid">
            <div class="feature-card anim-card">
                <div class="feat-icon blue">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.99 12 19.79 19.79 0 0 1 1.95 3.44a2 2 0 0 1 2-2.18h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                </div>
                <h3>Dedicated Pap & HPV Care</h3>
                <p>Consult instantly with certified gynaecologists for clinical guidance on Pap smears, HPV test results, and screening protocols.</p>
            </div>
            <div class="feature-card anim-card">
                <div class="feat-icon teal">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                </div>
                <h3>Advanced Visual Diagnostics</h3>
                <p>Expert diagnostic screening reports for cervix images, supporting doctors with fast, high-accuracy early cell abnormality reviews by Sp.OG specialists.</p>
            </div>
            <div class="feature-card anim-card">
                <div class="feat-icon purple">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                </div>
                <h3>Oncology Booking</h3>
                <p>Schedule offline or online diagnostic review appointments directly with leading Sp.OG sub-specialists in oncological gynaecology.</p>
            </div>
            <div class="feature-card anim-card">
                <div class="feat-icon amber">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><polyline points="9 12 11 14 15 10"/></svg>
                </div>
                <h3>HIPAA & ISO Compliant</h3>
                <p>High-grade privacy structures to secure patient health records, diagnostic scan photos, and oncologist consulting records.</p>
            </div>
            <div class="feature-card anim-card">
                <div class="feat-icon blue">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
                </div>
                <h3>Regular Screening Alerts</h3>
                <p>Automated periodic reminders for your routine Pap smear or HPV checkups based on age-group health guidelines.</p>
            </div>
            <div class="feature-card anim-card">
                <div class="feat-icon teal">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="6"/><path d="M15.477 12.89L17 22l-5-3-5 3 1.523-9.11"/></svg>
                </div>
                <h3>Verified Sp.OG Specialists</h3>
                <p>Every medical professional on Orvella is credential-verified and IDI-registered, guaranteeing qualified, expert oncological care.</p>
            </div>
        </div>
    </section>

    <!-- HOW IT WORKS -->
    <div class="how-section" id="how-it-works">
        <div class="how-inner">
            <div class="section-header" style="text-align:center">
                <div class="section-label" style="justify-content:center">Simple Process</div>
                <h2 class="section-title">Start your health journey<br>in 3 easy steps</h2>
            </div>
            <div class="how-grid">
                <div class="how-step anim-card">
                    <div class="step-num">1</div>
                    <h3>Create Your Account</h3>
                    <p>Register in minutes. Verify your identity securely and set up your personal health profile.</p>
                </div>
                <div class="how-step anim-card">
                    <div class="step-num">2</div>
                    <h3>Choose a Specialist</h3>
                    <p>Browse our verified doctors by specialty, availability, and patient ratings to find the best match.</p>
                </div>
                <div class="how-step anim-card">
                    <div class="step-num">3</div>
                    <h3>Get Expert Care</h3>
                    <p>Attend your consultation online, receive your diagnosis, and access your health reports instantly.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- TESTIMONIALS -->
    <section class="testi-section" id="testimonials">
        <div class="section-header" style="text-align:center">
            <div class="section-label" style="justify-content:center">Patient Stories</div>
            <h2 class="section-title">Trusted by patients<br>across Indonesia</h2>
        </div>
        <div class="testi-grid">
            <div class="testi-card anim-card">
                <div class="stars">★★★★★</div>
                <p class="testi-text">"I was skeptical at first, but Orvella exceeded my expectations. The consultation was smooth, the doctor was incredibly thorough, and I got my report within hours."</p>
                <div class="testi-author">
                    <div class="author-avatar blue">BW</div>
                    <div>
                        <div class="author-name">Budi Wahyono</div>
                        <div class="author-role">Patient, Jakarta</div>
                    </div>
                </div>
            </div>
            <div class="testi-card anim-card">
                <div class="stars">★★★★★</div>
                <p class="testi-text">"As a busy professional, Orvella is a lifesaver. I can consult with my cardiologist from my office without disrupting my schedule. Highly recommended!"</p>
                <div class="testi-author">
                    <div class="author-avatar teal">SR</div>
                    <div>
                        <div class="author-name">Sinta Rahayu</div>
                        <div class="author-role">Patient, Surabaya</div>
                    </div>
                </div>
            </div>
            <div class="testi-card anim-card">
                <div class="stars">★★★★★</div>
                <p class="testi-text">"The digital medical records feature is outstanding. All my family's health history in one place — organized, secure, and always accessible. This is the future of healthcare."</p>
                <div class="testi-author">
                    <div class="author-avatar purple">AM</div>
                    <div>
                        <div class="author-name">Ahmad Mulyadi</div>
                        <div class="author-role">Patient, Bandung</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA SECTION -->
    <section class="cta-section">
        <div class="cta-inner">
            <h2>Ready to take control of your health?</h2>
            <p>Join hundreds of patients who have transformed their healthcare experience with Orvella's premium platform.</p>
            <a href="{{ route('login') }}" class="btn-cta">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                Access Your Portal Now
            </a>
        </div>
    </section>

    <!-- CONTACT -->
    <section class="contact-section" id="contact">
        <div class="section-header">
            <div class="section-label">Get In Touch</div>
            <h2 class="section-title">We're here to help</h2>
            <p class="section-sub">Have questions about our platform or want to partner with Orvella? Our team is ready to assist you.</p>
        </div>
        <div class="contact-grid">
            <div>
                <div class="contact-item">
                    <div class="contact-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                    </div>
                    <div>
                        <h4>Email Address</h4>
                        <p>{{ $configs['contact_email'] ?? 'contact@orvella.com' }}</p>
                    </div>
                </div>
                <div class="contact-item">
                    <div class="contact-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.99 12 19.79 19.79 0 0 1 1.95 3.44a2 2 0 0 1 2-2.18h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                    </div>
                    <div>
                        <h4>Phone Line</h4>
                        <p>{{ $configs['contact_phone'] ?? '+62 21 5550 1234' }}</p>
                    </div>
                </div>
                <div class="contact-item">
                    <div class="contact-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                    </div>
                    <div>
                        <h4>Headquarters</h4>
                        <p>{{ $configs['contact_address'] ?? 'Jl. Healthcare Tech Kav. 82, Jakarta, Indonesia' }}</p>
                    </div>
                </div>
            </div>
            <div class="contact-card">
                <h3>Enterprise Grade Security & Compliance</h3>
                <p>We take healthcare data privacy seriously. Orvella is fully HIPAA compliant, utilizes AES-256 encryption at rest and TLS 1.3 in transit, ensuring patient data integrity remains absolute.</p>
                <div class="cert-badges">
                    <div class="cert-badge">
                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><polyline points="9 12 11 14 15 10"/></svg>
                        ISO 27001
                    </div>
                    <div class="cert-badge">
                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><polyline points="9 12 11 14 15 10"/></svg>
                        HIPAA Compliant
                    </div>
                    <div class="cert-badge">
                        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><polyline points="9 12 11 14 15 10"/></svg>
                        AES-256 Encrypted
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- FOOTER -->
    <footer class="footer">
        <div class="footer-inner">
            <div class="footer-top">
                <div>
                    <div style="display:flex;align-items:center;gap:10px;margin-bottom:6px;">
                        <div class="logo-icon" style="width:32px;height:32px;font-size:16px;border-radius:9px;">O</div>
                        <span class="footer-logo-text">Orvella</span>
                    </div>
                    <div class="footer-logo-sub">Premium Clinic & Specialist Consultation Platform</div>
                </div>
                <div class="footer-links">
                    <a href="#features">Features</a>
                    <a href="#how-it-works">How It Works</a>
                    <a href="#testimonials">Testimonials</a>
                    <a href="#contact">Contact</a>
                    <a href="{{ route('login') }}">Sign In</a>
                </div>
            </div>
            <div class="footer-bottom">
                <span class="footer-copy">© {{ date('Y') }} Orvella Healthcare Systems. All rights reserved.</span>
                <div class="footer-secure">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><polyline points="9 12 11 14 15 10"/></svg>
                    HIPAA Compliant · Secure · Trusted
                </div>
            </div>
        </div>
    </footer>

    <script>
        // Navbar scroll effect
        const nav = document.getElementById('mainNav');
        window.addEventListener('scroll', () => {
            if (window.scrollY > 20) {
                nav.classList.add('scrolled');
            } else {
                nav.classList.remove('scrolled');
            }
        });

        // Smooth scroll
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    const offset = 80;
                    const top = target.getBoundingClientRect().top + window.pageYOffset - offset;
                    window.scrollTo({ top, behavior: 'smooth' });
                }
            });
        });

        // Scroll animations — use CSS class so elements are always visible if JS fails
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-visible');
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.08 });

        document.querySelectorAll('.anim-card').forEach(el => {
            observer.observe(el);
        });
    </script>
</body>
</html>
