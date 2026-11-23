<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', ('James Heart Care')) — {{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,300;0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;1,9..40,400&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.1.0-rc.0/css/select2.min.css" rel="stylesheet">
    <style>
        :root {
            --sc-navy:      #0f1f3d;
            --sc-navy-mid:  #162545;
            --sc-navy-light:#1e3360;
            --sc-teal:      #0ab4b4;
            --sc-teal-dark: #089898;
            --sc-teal-glow: rgba(10,180,180,0.15);
            --sc-gold:      #f5a623;
            --sc-red:       #e84c4c;
            --sc-green:     #27c47a;
            --sc-amber:     #f5a623;
            --sc-bg:        #f0f4f8;
            --sc-surface:   #ffffff;
            --sc-border:    #e2e8f0;
            --sc-text:      #1a2942;
            --sc-muted:     #64748b;
            --sc-sidebar-w: 260px;
            --sc-sidebar-collapsed-w: 58px;
            --sc-header-h:  64px;
            --radius:       10px;
            --shadow:       0 2px 12px rgba(15,31,61,0.08);
            --shadow-md:    0 4px 24px rgba(15,31,61,0.12);
        }
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        html { font-size: 14px; }
        body { font-family: 'DM Sans', sans-serif; background: var(--sc-bg); color: var(--sc-text); display: flex; min-height: 100vh; }

        /* ── SIDEBAR ── */
        #sidebar {
            width: var(--sc-sidebar-w);
            background: var(--sc-navy);
            color: #fff;
            display: flex;
            flex-direction: column;
            position: fixed;
            top: 0; left: 0;
            height: 100vh;
            z-index: 100;
            transition: width .25s ease;
            overflow-x: hidden;
        }
        #sidebar.collapsed { width: var(--sc-sidebar-collapsed-w); }
        #sidebar.collapsed .brand-text,
        #sidebar.collapsed .nav-label,
        #sidebar.collapsed .nav-section,
        #sidebar.collapsed .sidebar-footer { display: none; }
        #sidebar.collapsed .nav-badge { display: none; }
        #sidebar.collapsed .nav-item-inner { justify-content: center; padding: 10px 0; }
        #sidebar.collapsed .nav-item-inner i { width: auto; font-size: 16px; }
        #sidebar.collapsed .sidebar-brand { justify-content: center; padding: 14px 0; min-height: var(--sc-header-h); }
        #sidebar.collapsed .brand-icon { margin-top: 0; }
        #main-wrap { transition: margin-left .25s ease; }
        #main-wrap.sidebar-collapsed { margin-left: var(--sc-sidebar-collapsed-w); }

        .sidebar-toggle-btn {
            width: 34px; height: 34px; border-radius: 8px;
            border: 1px solid var(--sc-border);
            background: var(--sc-surface);
            display: flex; align-items: center; justify-content: center;
            color: var(--sc-muted); cursor: pointer;
            transition: all .15s; flex-shrink: 0;
        }
        .sidebar-toggle-btn:hover { background: var(--sc-bg); color: var(--sc-navy); }

        .sidebar-brand {
            padding: 14px 16px;
            border-bottom: 1px solid rgba(255,255,255,.08);
            display: flex;
            align-items: flex-start;
            gap: 10px;
            min-height: var(--sc-header-h);
        }
        .brand-icon {
            width: 30px; height: 30px;
            background: linear-gradient(135deg, var(--sc-teal), #0891b2);
            border-radius: 8px;
            display: flex; align-items: center; justify-content: center;
            font-size: 14px;
            flex-shrink: 0;
            margin-top: 2px;
        }
        .brand-text { line-height: 1.3; min-width: 0; }
        .brand-text strong { font-size: 13px; font-weight: 700; display: block; }
        .brand-text span { font-size: 10px; opacity: .55; letter-spacing: .3px; display: block; margin-top: 3px; }

        .sidebar-scroll { flex: 1; overflow-y: auto; overflow-x: hidden; padding: 12px 0; }
        .sidebar-scroll::-webkit-scrollbar { width: 4px; }
        .sidebar-scroll::-webkit-scrollbar-thumb { background: rgba(255,255,255,.15); border-radius: 2px; }

        .nav-section { padding: 8px 16px 4px; font-size: 10px; text-transform: uppercase; letter-spacing: 1.2px; opacity: .5; font-weight: 600; cursor: pointer; display: flex; justify-content: space-between; align-items: center; user-select: none; }
        .nav-section:hover { opacity: .85; }
        .nav-section .nsc { font-size: 9px; transition: transform .2s; opacity: .7; flex-shrink:0; }
        .nav-section.ns-collapsed .nsc { transform: rotate(-90deg); }
        .ns-wrap { }
        .ns-handle { opacity:0; cursor:grab; font-size:10px; margin-right:5px; transition:opacity .15s; }
        .nav-section:hover .ns-handle { opacity:.55; }
        .ni-handle { opacity:0; cursor:grab; font-size:9px !important; width:10px !important; margin-right:3px; color:rgba(255,255,255,.35) !important; transition:opacity .15s; flex-shrink:0; }
        .nav-item:hover .ni-handle { opacity:1; }
        .nav-group { overflow: hidden; }
        .nav-group.ng-collapsed { display: none; }
        .sortable-ghost { opacity:.35; background:rgba(255,255,255,.06); border-radius:6px; }
        .sortable-chosen.ns-wrap { opacity:.9; }
        #sidebar.collapsed .nav-group.ng-collapsed { display:block !important; }
        #sidebar.collapsed .ns-handle, #sidebar.collapsed .ni-handle { display:none; }

        .nav-item { display: block; text-decoration: none; }
        .nav-item-inner {
            display: flex; align-items: center; gap: 12px;
            padding: 9px 18px 9px 20px;
            color: rgba(255,255,255,.7);
            font-size: 13.5px; font-weight: 500;
            transition: all .18s;
            border-left: 3px solid transparent;
            position: relative;
        }
        .nav-item-inner i { width: 18px; text-align: center; font-size: 14px; flex-shrink: 0; }
        .nav-item-inner:hover { color: #fff; background: rgba(255,255,255,.07); }
        .nav-item.active .nav-item-inner {
            color: var(--sc-teal);
            background: rgba(10,180,180,.12);
            border-left-color: var(--sc-teal);
        }
        .nav-badge {
            margin-left: auto;
            background: var(--sc-red);
            color: #fff;
            font-size: 10px;
            padding: 2px 6px;
            border-radius: 10px;
            font-weight: 600;
        }
        .nav-badge.amber { background: var(--sc-amber); color: var(--sc-navy); }

        .sidebar-footer {
            padding: 14px 20px;
            border-top: 1px solid rgba(255,255,255,.08);
            font-size: 12px;
            color: rgba(255,255,255,.5);
        }

        /* ── MAIN ── */
        #main-wrap { margin-left: var(--sc-sidebar-w); flex: 1; display: flex; flex-direction: column; min-height: 100vh; min-width: 0; overflow-x: hidden; }

        /* ── TOPBAR ── */
        #topbar {
            height: var(--sc-header-h);
            background: var(--sc-surface);
            border-bottom: 1px solid var(--sc-border);
            display: flex; align-items: center;
            padding: 0 24px;
            gap: 16px;
            position: sticky; top: 0; z-index: 50;
            box-shadow: 0 1px 8px rgba(0,0,0,.06);
        }
        .topbar-title { font-size: 17px; font-weight: 600; color: var(--sc-navy); flex: 1; }
        .topbar-search {
            display: flex; align-items: center; gap: 8px;
            background: var(--sc-bg);
            border: 1px solid var(--sc-border);
            border-radius: 8px;
            padding: 6px 14px;
            width: 280px;
        }
        .topbar-search input { border: none; background: transparent; outline: none; font-size: 13px; width: 100%; font-family: inherit; }
        .topbar-search i { color: var(--sc-muted); }
        .topbar-actions { display: flex; align-items: center; gap: 10px; }
        .topbar-btn {
            width: 36px; height: 36px; border-radius: 8px;
            border: 1px solid var(--sc-border);
            background: var(--sc-surface);
            display: flex; align-items: center; justify-content: center;
            color: var(--sc-muted); cursor: pointer;
            text-decoration: none;
            position: relative;
            transition: all .15s;
        }
        .topbar-btn:hover { background: var(--sc-bg); color: var(--sc-navy); }
        .topbar-btn .badge-dot {
            position: absolute; top: 6px; right: 6px;
            width: 7px; height: 7px; border-radius: 50%;
            background: var(--sc-red); border: 1.5px solid white;
        }
        .user-pill {
            display: flex; align-items: center; gap: 10px;
            padding: 5px 12px 5px 5px;
            background: var(--sc-bg);
            border: 1px solid var(--sc-border);
            border-radius: 30px;
            cursor: pointer;
            text-decoration: none;
            color: var(--sc-text);
        }
        .user-pill .avatar {
            width: 28px; height: 28px; border-radius: 50%;
            background: linear-gradient(135deg, var(--sc-teal), var(--sc-navy-light));
            display: flex; align-items: center; justify-content: center;
            color: #fff; font-size: 11px; font-weight: 600;
        }
        .user-pill .uname { font-size: 12.5px; font-weight: 500; }

        /* ── PAGE CONTENT ── */
        #page-content { flex: 1; padding: 24px; min-width: 0; overflow-x: hidden; }

        .page-header { display: flex; align-items: flex-start; gap: 16px; margin-bottom: 24px; }
        .page-header-left { flex: 1; }

        /* TABLE WRAP — always scrollable, works on every screen size */
        .table-wrap { overflow-x: auto; -webkit-overflow-scrolling: touch; }
        .page-header h1 { font-size: 22px; font-weight: 700; color: var(--sc-navy); }
        .page-header .breadcrumb { font-size: 12px; color: var(--sc-muted); margin-top: 3px; }
        .page-header .breadcrumb a { color: var(--sc-teal); text-decoration: none; }

        /* Embedded YouTube player (forum posts + DMs) */
        .yt-embed { display:block; position:relative; width:100%; max-width:480px; aspect-ratio:16/9; margin:6px 0; border-radius:10px; overflow:hidden; background:#000; }
        .yt-embed iframe { position:absolute; inset:0; width:100%; height:100%; border:0; }

        /* ── PATIENT CONTEXT BAR ── */
        .sc-patient-bar { background:var(--sc-surface); border-bottom:1px solid var(--sc-border); padding:0 24px; display:flex; align-items:center; min-height:38px; position:sticky; top:64px; z-index:49; overflow-x:auto; }
        .sc-patient-bar::-webkit-scrollbar { display:none; }
        .spb-name { display:flex; align-items:center; gap:6px; font-weight:700; font-size:13px; color:var(--sc-navy); white-space:nowrap; padding-right:14px; border-right:1px solid var(--sc-border); margin-right:8px; flex-shrink:0; }
        .spb-name a { color:inherit; text-decoration:none; }
        .spb-jhc { font-size:11px; color:var(--sc-muted); font-weight:600; font-family:monospace; }
        .spb-links { display:flex; gap:2px; }
        .spb-links a { display:inline-flex; align-items:center; gap:5px; padding:5px 11px; font-size:12px; color:var(--sc-muted); text-decoration:none; border-radius:6px; white-space:nowrap; font-weight:500; transition:background .12s,color .12s; }
        .spb-links a:hover { background:rgba(0,0,0,.04); color:var(--sc-navy); }
        .spb-links a.spb-active { color:var(--sc-teal); font-weight:700; background:rgba(10,180,180,.07); }
        [data-theme="dark"] .sc-patient-bar { background:var(--sc-surface); }
        [data-theme="dark"] .spb-name { color:var(--sc-text); }
        [data-theme="dark"] .spb-links a:hover { background:rgba(255,255,255,.06); color:var(--sc-text); }

        /* ── CARDS ── */
        .card {
            background: var(--sc-surface);
            border-radius: var(--radius);
            border: 1px solid var(--sc-border);
            box-shadow: var(--shadow);
            overflow: hidden;
        }
        .card-header {
            display: flex; align-items: center; gap: 12px;
            padding: 16px 20px;
            border-bottom: 1px solid var(--sc-border);
            font-weight: 600; font-size: 14px; color: var(--sc-navy);
        }
        .card-header i { color: var(--sc-teal); }
        .card-header .card-actions { margin-left: auto; display: flex; gap: 8px; }
        .card-body { padding: 20px; }
        .card-footer { padding: 12px 20px; border-top: 1px solid var(--sc-border); background: var(--sc-bg); }

        /* ── STAT CARDS ── */
        .stat-card {
            background: var(--sc-surface);
            border-radius: var(--radius);
            border: 1px solid var(--sc-border);
            padding: 20px;
            display: flex; gap: 16px; align-items: flex-start;
        }
        a.stat-card { transition: box-shadow .15s, transform .15s; }
        a.stat-card:hover { box-shadow: 0 4px 16px rgba(0,0,0,.10); transform: translateY(-2px); border-color: var(--sc-teal); }
        .stat-icon {
            width: 48px; height: 48px; border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 20px; flex-shrink: 0;
        }
        .stat-icon.teal   { background: var(--sc-teal-glow); color: var(--sc-teal); }
        .stat-icon.green  { background: rgba(39,196,122,.12); color: var(--sc-green); }
        .stat-icon.amber  { background: rgba(245,166,35,.12); color: var(--sc-amber); }
        .stat-icon.red    { background: rgba(232,76,76,.12); color: var(--sc-red); }
        .stat-icon.navy   { background: rgba(15,31,61,.08); color: var(--sc-navy); }
        .stat-value { font-size: 26px; font-weight: 700; color: var(--sc-navy); line-height: 1; }
        .stat-label { font-size: 12px; color: var(--sc-muted); margin-top: 4px; }
        .stat-sub { font-size: 11px; margin-top: 6px; }

        /* ── FORMS ── */
        .form-group { margin-bottom: 16px; }
        .form-label { display: block; font-size: 12.5px; font-weight: 600; color: var(--sc-navy); margin-bottom: 6px; }
        .form-label .req { color: var(--sc-red); margin-left: 2px; }
        .form-control {
            width: 100%; padding: 9px 12px; border: 1.5px solid var(--sc-border);
            border-radius: 7px; font-family: inherit; font-size: 13.5px;
            background: #fff; color: var(--sc-text);
            transition: border .15s, box-shadow .15s;
            outline: none;
        }
        .form-control:focus { border-color: var(--sc-teal); box-shadow: 0 0 0 3px rgba(10,180,180,.15); }
        .form-control.is-invalid { border-color: var(--sc-red); }
        .form-text { font-size: 11.5px; color: var(--sc-muted); margin-top: 4px; }
        .invalid-feedback { font-size: 11.5px; color: var(--sc-red); margin-top: 4px; }
        select.form-control { cursor: pointer; }
        textarea.form-control { resize: vertical; min-height: 80px; }
        .form-row { display: grid; gap: 16px; }
        .form-row.cols-2 { grid-template-columns: 1fr 1fr; }
        .form-row.cols-3 { grid-template-columns: 1fr 1fr 1fr; }
        .form-row.cols-4 { grid-template-columns: 1fr 1fr 1fr 1fr; }

        /* ── BUTTONS ── */
        .btn {
            display: inline-flex; align-items: center; gap: 7px;
            padding: 8px 18px; border-radius: 7px;
            font-size: 13.5px; font-weight: 600;
            cursor: pointer; border: none; text-decoration: none;
            transition: all .15s; white-space: nowrap;
            font-family: inherit;
        }
        .btn:disabled { opacity: .5; cursor: not-allowed; }
        .btn-primary { background: var(--sc-teal); color: #fff; }
        .btn-primary:hover { background: var(--sc-teal-dark); }
        .btn-navy { background: var(--sc-navy); color: #fff; }
        .btn-navy:hover { background: var(--sc-navy-light); }
        .btn-danger { background: var(--sc-red); color: #fff; }
        .btn-danger:hover { background: #d43c3c; }
        .btn-success { background: var(--sc-green); color: #fff; }
        .btn-amber { background: var(--sc-amber); color: var(--sc-navy); }
        .btn-outline { background: transparent; border: 1.5px solid var(--sc-border); color: var(--sc-muted); }
        .btn-outline:hover { border-color: var(--sc-teal); color: var(--sc-teal); }
        .btn-sm { padding: 5px 12px; font-size: 12px; }
        .btn-lg { padding: 12px 28px; font-size: 15px; }
        .btn-icon { width: 34px; height: 34px; padding: 0; display: inline-flex; align-items: center; justify-content: center; }

        /* ── TABLES ── */
        .table-wrap { overflow-x: auto; }
        .sc-table { width: 100%; border-collapse: collapse; font-size: 13px; }
        .sc-table th {
            background: var(--sc-bg); padding: 10px 14px;
            text-align: left; font-size: 11px; font-weight: 700;
            text-transform: uppercase; letter-spacing: .5px;
            color: var(--sc-muted); border-bottom: 2px solid var(--sc-border);
            white-space: nowrap;
        }
        .sc-table td { padding: 11px 14px; border-bottom: 1px solid var(--sc-border); vertical-align: middle; }
        .sc-table tr:hover td { background: rgba(10,180,180,.03); }
        .sc-table tr:last-child td { border-bottom: none; }

        /* ── BADGES ── */
        .badge {
            display: inline-flex; align-items: center; gap: 4px;
            padding: 3px 9px; border-radius: 20px;
            font-size: 11px; font-weight: 600;
        }
        .badge-teal    { background: var(--sc-teal-glow); color: var(--sc-teal); }
        .badge-green   { background: rgba(39,196,122,.12); color: #1b9e5c; }
        .badge-amber   { background: rgba(245,166,35,.15); color: #c47e0a; }
        .badge-red     { background: rgba(232,76,76,.12); color: #b52b2b; }
        .badge-navy    { background: rgba(15,31,61,.08); color: var(--sc-navy); }
        .badge-gray    { background: #f1f5f9; color: var(--sc-muted); }
        .badge-purple  { background: rgba(139,92,246,.12); color: #7c3aed; }

        /* ── TABS ── */
        .tab-nav {
            display: flex; gap: 2px; border-bottom: 2px solid var(--sc-border);
            margin-bottom: 20px; overflow-x: auto; flex-wrap: nowrap;
            scrollbar-width: thin; scrollbar-color: var(--sc-border) transparent;
        }
        .tab-nav::-webkit-scrollbar { height: 3px; }
        .tab-nav::-webkit-scrollbar-thumb { background: var(--sc-border); border-radius: 2px; }
        .tab-btn {
            padding: 9px 13px; font-size: 12.5px; font-weight: 600; white-space: nowrap;
            color: var(--sc-muted); background: transparent; border: none;
            cursor: pointer; border-bottom: 2px solid transparent; flex-shrink: 0;
            margin-bottom: -2px; transition: all .15s; font-family: inherit;
            display: flex; align-items: center; gap: 5px;
        }
        .tab-btn.active { color: var(--sc-teal); border-bottom-color: var(--sc-teal); }
        .tab-btn:hover:not(.active) { color: var(--sc-navy); }
        .tab-pane { display: none; }
        .tab-pane.active { display: block; }

        /* ── ALERTS ── */
        .alert { padding: 12px 16px; border-radius: 8px; font-size: 13px; margin-bottom: 16px; display: flex; gap: 10px; align-items: flex-start; }
        .alert i { margin-top: 1px; flex-shrink: 0; }
        .alert-success { background: rgba(39,196,122,.12); color: #15693a; border: 1px solid rgba(39,196,122,.3); }
        .alert-danger  { background: rgba(232,76,76,.1);  color: #922020; border: 1px solid rgba(232,76,76,.25); }
        .alert-warning { background: rgba(245,166,35,.12); color: #7a4f00; border: 1px solid rgba(245,166,35,.3); }
        .alert-info    { background: rgba(10,180,180,.1); color: #065e5e; border: 1px solid rgba(10,180,180,.25); }
        /* .alert-flash: blinks and stays 10 s (e.g. discharge blocked by the checklist) */
        .alert-flash { animation: alertFlash 1s ease-in-out 10; font-weight: 600; }
        @keyframes alertFlash { 0%,100% { background: rgba(232,76,76,.1); } 50% { background: #e84c4c; color: #fff; } }

        /* ── PATIENT HEADER STRIP ── */
        .patient-strip {
            background: linear-gradient(135deg, var(--sc-navy), var(--sc-navy-light));
            color: #fff; padding: 16px 24px;
            border-radius: var(--radius);
            display: flex; gap: 20px; align-items: center;
            margin-bottom: 20px;
        }
        /* Sticky variant — pins the patient strip to the top of the viewport while scrolling
           through tabs. Doctors keep allergies/age visible at all times. */
        .patient-strip.sticky {
            position: sticky; top: 8px; z-index: 30;
            box-shadow: 0 6px 18px rgba(15,31,61,.18);
        }
        .patient-strip .ps-avatar {
            width: 56px; height: 56px; border-radius: 50%;
            background: rgba(10,180,180,.3);
            display: flex; align-items: center; justify-content: center;
            font-size: 22px; font-weight: 700; color: var(--sc-teal);
            flex-shrink: 0;
        }
        .patient-strip .ps-name { font-size: 18px; font-weight: 700; }
        .patient-strip .ps-jhc_id { font-size: 12px; opacity: .7; font-family: 'DM Mono', monospace; }
        .patient-strip .ps-meta { display: flex; gap: 20px; font-size: 13px; opacity: .85; margin-top: 6px; }
        .patient-strip .ps-meta span { display: flex; align-items: center; gap: 5px; }
        .patient-strip .ps-actions { margin-left: auto; display: flex; gap: 8px; }

        /* ── STOCK STATUS ── */
        .stock-ok      { color: var(--sc-green); }
        .stock-low     { color: var(--sc-amber); }
        .stock-critical{ color: var(--sc-red); }
        .stock-out     { color: var(--sc-red); font-weight: 600; }

        /* ── UTILITIES ── */
        .grid { display: grid; gap: 20px; }
        .grid-2 { grid-template-columns: repeat(2, 1fr); }
        .grid-3 { grid-template-columns: repeat(3, 1fr); }
        .grid-4 { grid-template-columns: repeat(4, 1fr); }
        .flex { display: flex; }
        .flex-1 { flex: 1; }
        .items-center { align-items: center; }
        .gap-2 { gap: 8px; }
        .gap-3 { gap: 12px; }
        .text-muted { color: var(--sc-muted); }
        .text-small { font-size: 12px; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .fw-600 { font-weight: 600; }
        .fw-700 { font-weight: 700; }
        .mt-1 { margin-top: 4px; } .mt-2 { margin-top: 8px; } .mt-3 { margin-top: 12px; }
        .mb-1 { margin-bottom: 4px; } .mb-2 { margin-bottom: 8px; } .mb-3 { margin-bottom: 12px; } .mb-4 { margin-bottom: 20px; }
        .p-0 { padding: 0; }
        .hidden { display: none; }
        .mono { font-family: 'DM Mono', monospace; }

        /* ── MODAL ── */
        .modal-overlay {
            position: fixed; inset: 0; background: rgba(15,31,61,.6);
            display: none; align-items: center; justify-content: center; z-index: 200;
        }
        .modal-overlay.open { display: flex; }
        .modal-box {
            background: #fff; border-radius: 14px;
            width: 90%; max-width: 600px; max-height: 90vh;
            overflow-y: auto; box-shadow: var(--shadow-md);
        }
        .modal-box.modal-lg { max-width: 900px; }
        .modal-header { padding: 18px 22px; border-bottom: 1px solid var(--sc-border); display: flex; align-items: center; gap: 12px; }
        .modal-header h3 { font-size: 16px; font-weight: 700; flex: 1; }
        .modal-close { background: none; border: none; font-size: 18px; cursor: pointer; color: var(--sc-muted); line-height: 1; }
        .modal-body { padding: 22px; }
        .modal-footer { padding: 14px 22px; border-top: 1px solid var(--sc-border); display: flex; gap: 10px; justify-content: flex-end; }

        /* ── VITALS ROW ── */
        .vital-box {
            background: var(--sc-bg); border: 1px solid var(--sc-border);
            border-radius: 8px; padding: 12px; text-align: center;
        }
        .vital-box .vb-val { font-size: 20px; font-weight: 700; color: var(--sc-navy); }
        .vital-box .vb-unit { font-size: 10px; color: var(--sc-muted); }
        .vital-box .vb-label { font-size: 11px; color: var(--sc-muted); margin-top: 2px; }

        /* ── PRESCRIPTION TABLE ── */
        .rx-item {
            background: var(--sc-bg); border-radius: 8px;
            padding: 12px 16px; margin-bottom: 10px;
            border-left: 4px solid var(--sc-teal);
        }
        .rx-item .rx-name { font-weight: 700; font-size: 14px; }
        .rx-item .rx-details { font-size: 12px; color: var(--sc-muted); margin-top: 4px; }
        .rx-item .rx-side-effects { font-size: 11px; color: #b45309; background: rgba(245,166,35,.1); padding: 4px 8px; border-radius: 4px; margin-top: 6px; }

        /* ── TIMELINE ── */
        .timeline { position: relative; }
        .timeline::before { content: ''; position: absolute; left: 16px; top: 0; bottom: 0; width: 2px; background: var(--sc-border); }
        .timeline-item { position: relative; padding-left: 44px; margin-bottom: 20px; }
        .timeline-dot { position: absolute; left: 8px; top: 4px; width: 18px; height: 18px; border-radius: 50%; background: var(--sc-teal); border: 3px solid white; box-shadow: 0 0 0 2px var(--sc-teal); }
        .timeline-dot.gray { background: var(--sc-muted); box-shadow: 0 0 0 2px var(--sc-muted); }

        /* ── RESPONSIVE ── */
        @media (max-width: 1024px) {
            :root { --sc-sidebar-w: 240px; }
            .grid-4 { grid-template-columns: repeat(2, 1fr); }
        }

        /* Mobile sidebar backdrop */
        #sidebar-backdrop {
            display: none; position: fixed; inset: 0;
            background: rgba(15,31,61,.5); z-index: 99;
        }
        #sidebar-backdrop.open { display: block; }

        @media (max-width: 768px) {
            /* Sidebar: off-screen, slides in as overlay */
            #sidebar {
                transform: translateX(-100%);
                width: 280px !important;
                transition: transform .25s ease;
                box-shadow: none;
            }
            #sidebar.open { transform: translateX(0); box-shadow: 4px 0 24px rgba(0,0,0,.25); }
            #main-wrap { margin-left: 0 !important; }

            /* Topbar */
            #topbar { padding: 0 12px; gap: 8px; }
            .topbar-title { font-size: 14px; }
            .topbar-search { display: none; }
            .user-pill .uname { display: none; }
            .user-pill { padding: 4px; border-radius: 50%; }

            /* Page content */
            #page-content { padding: 14px 12px; }

            /* Flash messages */
            #main-wrap > div[style*="padding: 0 24px"] { padding: 0 12px !important; }

            /* Page header — title takes full row, actions wrap below compactly */
            .page-header { flex-wrap: wrap; gap: 8px; align-items: flex-start; }
            .page-header-left { flex: 0 0 100%; }
            .page-header .btn { font-size: 11.5px !important; padding: 5px 9px !important; white-space: nowrap; }
            .page-header > div[style*="display:flex"], .page-header > div[style*="display: flex"] { flex-wrap: wrap; }

            /* Grids → single column */
            .grid-2, .grid-3, .grid-4 { grid-template-columns: 1fr; }
            /* Stat cards: 2-up on tablet/mobile */
            .grid.grid-4 { grid-template-columns: repeat(2, 1fr); }

            /* Collapse any .grid with inline column definitions (billing sidebar, etc.) */
            .grid[style*="grid-template-columns"] { grid-template-columns: 1fr !important; }

            /* 4-column inline stat rows → 2 per row */
            [style*="grid-template-columns:repeat(4"] { grid-template-columns: repeat(2,1fr) !important; }
            [style*="grid-template-columns: repeat(4"] { grid-template-columns: repeat(2,1fr) !important; }

            /* Patient context bar: don't sticky on mobile (blocks content), allow horizontal scroll */
            .sc-patient-bar { position: relative !important; top: auto !important; flex-wrap: nowrap; }
            .spb-links { flex-wrap: nowrap; overflow-x: auto; }
            .spb-links::-webkit-scrollbar { display: none; }
            .spb-name { flex-shrink: 0; }

            /* Forms → single column */
            .form-row.cols-2,
            .form-row.cols-3,
            .form-row.cols-4 { grid-template-columns: 1fr; }

            /* Tables — horizontal scroll */
            .sc-table { font-size: 12px; }
            .sc-table th, .sc-table td { padding: 8px 10px; }

            /* Patient strip — stack */
            .patient-strip { flex-wrap: wrap; padding: 14px 16px; }
            .patient-strip .ps-meta { flex-wrap: wrap; gap: 8px; }
            .patient-strip .ps-actions { margin-left: 0; width: 100%; justify-content: flex-start; flex-wrap: wrap; }

            /* Cards */
            .card-body { padding: 14px; }
            .card-header { padding: 12px 14px; font-size: 13px; }

            /* Modals — full width */
            .modal-box { width: 96%; border-radius: 10px; }
            .modal-body { padding: 16px; }
            .modal-footer { padding: 12px 16px; flex-wrap: wrap; }

            /* Stat card text */
            .stat-value { font-size: 22px; }
            .stat-card { padding: 14px; }
            .stat-icon { width: 40px; height: 40px; font-size: 17px; }

            /* Tabs — scrollable */
            .tab-nav { gap: 0; }
            .tab-btn { padding: 8px 10px; font-size: 12px; }

            /* Buttons */
            .btn-lg { padding: 10px 20px; font-size: 14px; }

            /* Two-column layouts & fixed-sidebar layouts */
            div[style*="grid-template-columns:2fr 1fr"],
            div[style*="grid-template-columns: 2fr 1fr"],
            div[style*="grid-template-columns:1fr 2fr"],
            div[style*="grid-template-columns: 1fr 2fr"],
            div[style*="grid-template-columns:1fr 3"],
            div[style*="grid-template-columns: 1fr 3"],
            div[style*="grid-template-columns:3fr"],
            div[style*="grid-template-columns: 3fr"],
            div[style*="grid-template-columns:1fr 340px"],
            div[style*="grid-template-columns: 1fr 340px"],
            div[style*="grid-template-columns:1fr 320px"],
            div[style*="grid-template-columns: 1fr 320px"],
            div[style*="grid-template-columns:1fr 300px"],
            div[style*="grid-template-columns: 1fr 300px"],
            div[style*="grid-template-columns:1fr 360px"],
            div[style*="grid-template-columns: 1fr 360px"],
            div[style*="grid-template-columns:1fr 380px"],
            div[style*="grid-template-columns: 1fr 380px"] {
                grid-template-columns: 1fr !important;
            }
        }

        @media (max-width: 480px) {
            html { font-size: 13px; }
            .grid.grid-4 { grid-template-columns: 1fr; }
            #topbar { gap: 4px; }
            .hide-mobile { display: none; }
            #page-content { padding: 10px; }

            /* Topbar: smaller icon buttons */
            .topbar-btn { width: 32px; height: 32px; font-size: 13px; }
            .topbar-btn .badge-dot { top: -2px; right: -2px; }

            /* Page header buttons: icon + short label only — hide long text via ellipsis */
            .page-header .btn { max-width: 100px; overflow: hidden; text-overflow: ellipsis; }

            /* Stat cards single column */
            [style*="grid-template-columns:repeat(4"] { grid-template-columns: 1fr !important; }
            [style*="grid-template-columns: repeat(4"] { grid-template-columns: 1fr !important; }

            /* Tab nav: even more compact */
            .tab-btn { padding: 6px 8px; font-size: 11px; }

            /* Modals: full screen on tiny phones */
            .modal-box { width: 100% !important; border-radius: 10px 10px 0 0; margin-top: auto; }
            .modal-overlay { align-items: flex-end !important; }
        }

        /* ── DARK MODE ── */
        [data-theme="dark"] {
            --sc-bg:        #0f172a;
            --sc-surface:   #1e293b;
            --sc-border:    #334155;
            --sc-text:      #e2e8f0;
            --sc-muted:     #94a3b8;
            --sc-navy:      #1e293b;
            --sc-navy-mid:  #253555;
            --sc-navy-light:#2d4170;
        }
        [data-theme="dark"] body          { background: var(--sc-bg); color: var(--sc-text); }
        [data-theme="dark"] #sidebar      { background: #0f1f3d; }
        [data-theme="dark"] #topbar       { background: var(--sc-surface); border-color: var(--sc-border); }
        [data-theme="dark"] .card         { background: var(--sc-surface); border-color: var(--sc-border); }
        [data-theme="dark"] .card-footer  { background: var(--sc-bg); }
        [data-theme="dark"] .form-control { background: var(--sc-bg); color: var(--sc-text); border-color: var(--sc-border); }
        [data-theme="dark"] .topbar-search { background: var(--sc-bg); border-color: var(--sc-border); }
        [data-theme="dark"] .topbar-search input { color: var(--sc-text); }
        [data-theme="dark"] .topbar-btn   { background: var(--sc-surface); border-color: var(--sc-border); }
        [data-theme="dark"] .user-pill    { background: var(--sc-bg); border-color: var(--sc-border); color: var(--sc-text); }
        [data-theme="dark"] .sidebar-toggle-btn { background: var(--sc-surface); border-color: var(--sc-border); }
        [data-theme="dark"] .sc-table th  { background: var(--sc-bg); color: var(--sc-muted); border-color: var(--sc-border); }
        [data-theme="dark"] .sc-table td  { border-color: var(--sc-border); color: var(--sc-text); }
        [data-theme="dark"] .sc-table tr:hover td { background: rgba(10,180,180,.06); }
        [data-theme="dark"] .vital-box    { background: var(--sc-bg); border-color: var(--sc-border); }
        [data-theme="dark"] .modal-box    { background: var(--sc-surface); color: var(--sc-text); }
        [data-theme="dark"] .stat-card    { background: var(--sc-surface); border-color: var(--sc-border); }
        [data-theme="dark"] .rx-item      { background: var(--sc-bg); }
        [data-theme="dark"] #global-search-results { background: var(--sc-surface); border-color: var(--sc-border); }
        [data-theme="dark"] #global-search-results a { color: var(--sc-text); border-color: var(--sc-border); }
        [data-theme="dark"] select.form-control option { background: var(--sc-surface); }
        [data-theme="dark"] .patient-strip { background: linear-gradient(135deg, #0f1f3d, #1e3360); }
        [data-theme="dark"] .tab-nav      { border-color: var(--sc-border); }
        [data-theme="dark"] .card-header  { color: var(--sc-text); border-color: var(--sc-border); }
        [data-theme="dark"] .topbar-title { color: var(--sc-teal); }
        [data-theme="dark"] .page-header h1 { color: var(--sc-text); }
        [data-theme="dark"] .btn-outline  { border-color: var(--sc-border); color: var(--sc-muted); }
        [data-theme="dark"] .btn-outline:hover { border-color: var(--sc-teal); color: var(--sc-teal); }

        /* ── PRINT ── */
        @media print {
            #sidebar, #topbar, .no-print { display: none !important; }
            #main-wrap { margin-left: 0; }
            #page-content { padding: 0; }
            body { background: white; }
            .card { box-shadow: none; border: 1px solid #ddd; }
        }
    </style>
    @stack('styles')
</head>
<body>

<!-- Mobile sidebar backdrop -->
<div id="sidebar-backdrop"></div>

<!-- ── IPD CENSUS PANEL ── -->
<div id="census-backdrop" style="display:none;position:fixed;inset:0;background:rgba(15,31,61,.45);z-index:400;transition:opacity .2s" onclick="closeCensus()"></div>
<div id="census-panel" style="position:fixed;top:0;right:0;height:100vh;width:min(520px,95vw);background:var(--sc-surface);box-shadow:-4px 0 24px rgba(15,31,61,.18);z-index:401;transform:translateX(100%);transition:transform .25s cubic-bezier(.4,0,.2,1);display:flex;flex-direction:column">
    <div style="padding:13px 18px;border-bottom:1px solid var(--sc-border);display:flex;align-items:center;gap:12px;background:var(--sc-navy);flex-shrink:0">
        <i class="fa-solid fa-clipboard-list" style="color:var(--sc-teal);font-size:15px"></i>
        <div style="flex:1;font-weight:700;font-size:14px;color:#fff">IPD Daily Census</div>
        <a href="{{ route('ipd.census') }}" target="_blank" style="background:rgba(255,255,255,.12);border:none;color:#fff;padding:5px 12px;border-radius:6px;cursor:pointer;font-size:12px;display:flex;align-items:center;gap:6px;text-decoration:none"><i class="fa-solid fa-up-right-from-square"></i> Full Page</a>
        <button onclick="closeCensus()" style="background:rgba(255,255,255,.12);border:none;color:#fff;width:30px;height:30px;border-radius:7px;cursor:pointer;font-size:18px;line-height:1;display:flex;align-items:center;justify-content:center">&times;</button>
    </div>
    <div id="census-body" style="flex:1;overflow-y:auto;padding:16px 18px">
        <div style="text-align:center;padding:40px 0;color:var(--sc-muted)"><i class="fa-solid fa-spinner fa-spin" style="font-size:20px"></i></div>
    </div>
</div>

<!-- ── PATIENT QUICK-VIEW PANEL ── -->
<div id="pqv-backdrop" style="display:none;position:fixed;inset:0;background:rgba(15,31,61,.35);z-index:300;transition:opacity .2s" onclick="closePQV()"></div>
<div id="pqv-panel" style="position:fixed;top:0;right:0;height:100vh;width:380px;max-width:95vw;background:var(--sc-surface);box-shadow:-4px 0 24px rgba(15,31,61,.15);z-index:301;transform:translateX(100%);transition:transform .25s cubic-bezier(.4,0,.2,1);display:flex;flex-direction:column">
    <div id="pqv-header" style="padding:18px 20px;border-bottom:1px solid var(--sc-border);display:flex;align-items:center;gap:12px;background:linear-gradient(135deg,var(--sc-navy),var(--sc-navy-light))">
        <div id="pqv-avatar" style="width:46px;height:46px;border-radius:50%;background:rgba(10,180,180,.3);color:var(--sc-teal);display:flex;align-items:center;justify-content:center;font-size:18px;font-weight:700;flex-shrink:0"></div>
        <div style="flex:1;min-width:0">
            <div id="pqv-name" style="font-size:16px;font-weight:700;color:#fff;white-space:nowrap;overflow:hidden;text-overflow:ellipsis"></div>
            <div id="pqv-meta" style="font-size:11px;color:rgba(255,255,255,.6);margin-top:2px;font-family:'DM Mono',monospace"></div>
        </div>
        <button onclick="closePQV()" style="background:rgba(255,255,255,.12);border:none;color:#fff;width:30px;height:30px;border-radius:7px;cursor:pointer;font-size:16px;display:flex;align-items:center;justify-content:center;flex-shrink:0">&times;</button>
    </div>
    <div id="pqv-body" style="flex:1;overflow-y:auto;padding:18px 20px;display:flex;flex-direction:column;gap:14px">
        <div style="text-align:center;padding:40px 0;color:var(--sc-muted)"><i class="fa-solid fa-spinner fa-spin" style="font-size:20px"></i></div>
    </div>
    <div id="pqv-footer" style="padding:14px 20px;border-top:1px solid var(--sc-border);display:flex;gap:8px;flex-wrap:wrap"></div>
</div>

<!-- SIDEBAR -->
<aside id="sidebar">
    <div class="sidebar-brand">
        <div class="brand-icon"><i class="fa-solid fa-heart-pulse"></i></div>
        <div class="brand-text">
            <strong>{{ setting('clinic_name', 'James Heart Care Pvt. Ltd.') }}</strong>
            <span>Hospital Management</span>
        </div>
    </div>
    <div class="sidebar-scroll" id="sidebar-scroll">
        @php $u = auth()->user(); @endphp

        {{-- Dashboard — pinned, not draggable --}}
        <a href="{{ route('dashboard') }}" class="nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}" id="nav-dashboard">
            <div class="nav-item-inner"><i class="fa-solid fa-gauge-high"></i> Dashboard</div>
        </a>

        {{-- Recent Patients — JS-populated from localStorage --}}
        <div id="nsw-recent-pts" style="display:none">
            <div class="nav-section" style="cursor:default;pointer-events:none;user-select:none">Recent Patients</div>
            <div id="ng-recent-pts"></div>
        </div>
        <style>
            #ng-recent-pts .rec-x{flex:0 0 auto;display:inline-flex;align-items:center;justify-content:center;width:16px;height:16px;margin-left:2px;border-radius:3px;color:rgba(255,255,255,.35);font-size:11px;opacity:0;transition:opacity .12s,background .12s,color .12s}
            #ng-recent-pts .nav-item:hover .rec-x{opacity:1}
            #ng-recent-pts .rec-x:hover{background:rgba(255,255,255,.15);color:#fff}
        </style>

        {{-- Reception --}}
        @if($u->hasRole(['admin','doctor','nurse','receptionist']))
        <div class="ns-wrap" id="nsw-reception">
            <div class="nav-section" data-ns="reception" onclick="toggleNS('reception')">
                <span><i class="fa-solid fa-grip-vertical ns-handle" onclick="event.stopPropagation()"></i> Reception</span>
                <i class="fa-solid fa-chevron-down nsc"></i>
            </div>
            <div class="nav-group" id="ng-reception">
                <a href="{{ route('patients.index') }}" class="nav-item {{ request()->routeIs('patients.*') ? 'active' : '' }}">
                    <div class="nav-item-inner"><i class="fa-solid fa-users"></i> Patients</div>
                </a>
                <a href="{{ route('appointments.calendar') }}" class="nav-item {{ request()->routeIs('appointments.calendar') ? 'active' : '' }}">
                    <div class="nav-item-inner"><i class="fa-solid fa-calendar-days"></i> Appt Calendar</div>
                </a>
                <a href="{{ route('appointments.index') }}" class="nav-item {{ request()->routeIs('appointments.index','appointments.show','appointments.create','appointments.edit') ? 'active' : '' }}">
                    <div class="nav-item-inner">
                        <i class="fa-solid fa-calendar-check"></i> Appointments
                        @php $pendingAppts = \DB::table('appointments')->whereDate('appointment_date', today())->whereIn('status',['Scheduled','Confirmed'])->count(); @endphp
                        @if($pendingAppts > 0) <span class="nav-badge">{{ $pendingAppts }}</span> @endif
                    </div>
                </a>
                <a href="{{ route('opd.index') }}" class="nav-item {{ request()->routeIs('opd.index') ? 'active' : '' }}">
                    <div class="nav-item-inner">
                        <i class="fa-solid fa-list-ol"></i> Reception Queue
                        @php try { $opdWaiting = \DB::table('opd_tokens')->whereDate('token_date', today())->where('status','waiting')->count(); } catch(\Exception $e) { $opdWaiting = 0; } @endphp
                        @if($opdWaiting > 0) <span class="nav-badge amber">{{ $opdWaiting }}</span> @endif
                    </div>
                </a>
                <a href="{{ route('opd.directory') }}" class="nav-item {{ request()->routeIs('opd.directory') ? 'active' : '' }}">
                    <div class="nav-item-inner"><i class="fa-solid fa-hospital-user"></i> Find a Doctor</div>
                </a>
            </div>
        </div>
        @endif

        @php $licSvc = app(\App\Services\LicenseService::class); @endphp

        {{-- OPD (gated by license `opd` module) --}}
        @if($licSvc->isEnabled('opd') && $u->hasRole(['admin','doctor','nurse','receptionist']))
        <div class="ns-wrap" id="nsw-opd">
            <div class="nav-section" data-ns="opd" onclick="toggleNS('opd')">
                <span><i class="fa-solid fa-grip-vertical ns-handle" onclick="event.stopPropagation()"></i> OPD</span>
                <i class="fa-solid fa-chevron-down nsc"></i>
            </div>
            <div class="nav-group" id="ng-opd">
                @if($u->hasRole(['admin','doctor','nurse']))
                <a href="{{ route('triage.index') }}" class="nav-item {{ request()->routeIs('triage.*') ? 'active' : '' }}">
                    <div class="nav-item-inner">
                        <i class="fa-solid fa-notes-medical"></i> Nursing Station
                        @php try { $pendingTriage = \DB::table('opd_tokens')->whereDate('token_date',today())->where('is_triaged',false)->whereNotIn('status',['done','skipped'])->count(); } catch(\Exception $e) { $pendingTriage = 0; } @endphp
                        @if($pendingTriage > 0) <span class="nav-badge amber">{{ $pendingTriage }}</span> @endif
                    </div>
                </a>
                @endif
                @if($u->hasRole(['admin','doctor']))
                <a href="{{ route('opd.doctor') }}" class="nav-item {{ request()->routeIs('opd.doctor') ? 'active' : '' }}">
                    <div class="nav-item-inner"><i class="fa-solid fa-stethoscope"></i> Doctor's Queue</div>
                </a>
                @endif
                <a href="{{ route('encounters.index') }}" class="nav-item {{ request()->routeIs('encounters.*') ? 'active' : '' }}">
                    <div class="nav-item-inner"><i class="fa-solid fa-notes-medical"></i> Encounters</div>
                </a>
                <a href="{{ route('opd.referrals.index') }}" class="nav-item {{ request()->routeIs('opd.referrals.*') ? 'active' : '' }}">
                    <div class="nav-item-inner">
                        <i class="fa-solid fa-share-from-square"></i> Dept Referrals
                        @php try { $openReferrals = \DB::table('opd_referrals')->where('status', 'pending')->count(); } catch(\Exception $e) { $openReferrals = 0; } @endphp
                        @if($openReferrals > 0) <span class="nav-badge amber">{{ $openReferrals }}</span> @endif
                    </div>
                </a>
                @if($u->hasRole(['admin','nurse','receptionist']))
                <a href="{{ route('opd.register') }}" class="nav-item {{ request()->routeIs('opd.register') ? 'active' : '' }}">
                    <div class="nav-item-inner"><i class="fa-solid fa-clipboard-list"></i> OPD Register</div>
                </a>
                @endif
            </div>
        </div>
        @endif

        {{-- IPD (gated by license `ipd` module) --}}
        @if($licSvc->isEnabled('ipd') && $u->hasRole(['admin','doctor','nurse','receptionist']))
        <div class="ns-wrap" id="nsw-ipd">
            <div class="nav-section" data-ns="ipd" onclick="toggleNS('ipd')">
                <span><i class="fa-solid fa-grip-vertical ns-handle" onclick="event.stopPropagation()"></i> IPD</span>
                <i class="fa-solid fa-chevron-down nsc"></i>
            </div>
            <div class="nav-group" id="ng-ipd">
                <a href="{{ route('ipd.dashboard') }}" class="nav-item {{ request()->routeIs('ipd.dashboard') ? 'active' : '' }}">
                    <div class="nav-item-inner"><i class="fa-solid fa-gauge-high"></i> IPD Dashboard</div>
                </a>
                <a href="{{ route('admissions.index') }}" class="nav-item {{ request()->routeIs('admissions.*') ? 'active' : '' }}">
                    <div class="nav-item-inner">
                        <i class="fa-solid fa-bed-pulse"></i> Admissions
                        @php try { $currentAdmissions = \DB::table('admissions')->where('status','admitted')->count(); } catch(\Exception $e) { $currentAdmissions = 0; } @endphp
                        @if($currentAdmissions > 0)<span class="nav-badge" style="background:#dcfce7;color:#14532d">{{ $currentAdmissions }}</span>@endif
                    </div>
                </a>
                @if($u->hasRole(['admin','doctor','nurse']))
                <a href="{{ route('icu.board') }}" class="nav-item {{ request()->routeIs('icu.board','icu.chart') ? 'active' : '' }}">
                    <div class="nav-item-inner">
                        <i class="fa-solid fa-display"></i> ICU Board
                        @php try { $icuAlerts = \DB::table('ipd_vitals as v')->join('admissions as a', 'a.id', '=', 'v.admission_id')->where('a.status', 'admitted')->where('v.news2', '>=', 7)->whereRaw('v.recorded_at = (select max(x.recorded_at) from ipd_vitals x where x.admission_id = v.admission_id)')->distinct()->count('v.admission_id'); } catch(\Exception $e) { $icuAlerts = 0; } @endphp
                        @if($icuAlerts > 0)<span class="nav-badge" title="Patients with NEWS2 ≥ 7">{{ $icuAlerts }}</span>@endif
                    </div>
                </a>
                <a href="{{ route('icu.calculator') }}" class="nav-item {{ request()->routeIs('icu.calculator') ? 'active' : '' }}">
                    <div class="nav-item-inner"><i class="fa-solid fa-calculator"></i> Drug Calculator</div>
                </a>
                @endif
                <a href="{{ route('wards.bed-board') }}" class="nav-item {{ request()->routeIs('wards.bed-board') ? 'active' : '' }}">
                    <div class="nav-item-inner">
                        <i class="fa-solid fa-map"></i> Bed Board
                        @php try { $availBeds = \DB::table('beds')->where('is_active',true)->where('status','available')->count(); } catch(\Exception $e) { $availBeds = 0; } @endphp
                        @if($availBeds > 0)<span class="nav-badge" style="background:#dcfce7;color:#14532d">{{ $availBeds }}</span>@endif
                    </div>
                </a>
                <a href="{{ route('wards.index') }}" class="nav-item {{ request()->routeIs('wards.index','wards.show','wards.create','wards.edit') ? 'active' : '' }}">
                    <div class="nav-item-inner"><i class="fa-solid fa-bed"></i> Wards &amp; Beds</div>
                </a>
                <a href="{{ route('ipd.census') }}" class="nav-item {{ request()->routeIs('ipd.census') ? 'active' : '' }}">
                    <div class="nav-item-inner"><i class="fa-solid fa-clipboard-list"></i> Daily Census</div>
                </a>
            </div>
        </div>
        @endif

        {{-- Diagnostics (gated by license `lab` module — covers Lab, Radiology, ECG, Echo, OT) --}}
        @if($licSvc->isEnabled('lab') && $u->hasRole(['admin','doctor','nurse','lab']))
        <div class="ns-wrap" id="nsw-diagnostics">
            <div class="nav-section" data-ns="diagnostics" onclick="toggleNS('diagnostics')">
                <span><i class="fa-solid fa-grip-vertical ns-handle" onclick="event.stopPropagation()"></i> Diagnostics</span>
                <i class="fa-solid fa-chevron-down nsc"></i>
            </div>
            <div class="nav-group" id="ng-diagnostics">
                <a href="{{ route('lab.index') }}" class="nav-item {{ request()->routeIs('lab.*') ? 'active' : '' }}">
                    <div class="nav-item-inner">
                        <i class="fa-solid fa-flask-vial"></i> Laboratory
                        @php $pendingLabs = \DB::table('lab_orders')->whereIn('status',['Ordered','Collected','Processing'])->count(); @endphp
                        @if($pendingLabs > 0) <span class="nav-badge amber">{{ $pendingLabs }}</span> @endif
                    </div>
                </a>
                @if($u->hasRole(['admin','doctor','nurse']))
                <a href="{{ route('radiology.index') }}" class="nav-item {{ request()->routeIs('radiology.*') ? 'active' : '' }}">
                    <div class="nav-item-inner"><i class="fa-solid fa-x-ray"></i> Radiology</div>
                </a>
                <a href="{{ route('ecg.index') }}" class="nav-item {{ request()->routeIs('ecg.*') ? 'active' : '' }}">
                    <div class="nav-item-inner"><i class="fa-solid fa-heart-circle-bolt"></i> ECG</div>
                </a>
                @endif
                @if($u->hasRole(['admin','doctor']))
                <a href="{{ route('echo.index') }}" class="nav-item {{ request()->routeIs('echo.*') ? 'active' : '' }}">
                    <div class="nav-item-inner"><i class="fa-solid fa-wave-square"></i> Echo</div>
                </a>
                <a href="{{ route('ot.index') }}" class="nav-item {{ request()->routeIs('ot.*') ? 'active' : '' }}">
                    <div class="nav-item-inner"><i class="fa-solid fa-syringe"></i> OT Register</div>
                </a>
                @endif
            </div>
        </div>
        @endif

        {{-- Pharmacy (gated by license `pharmacy` module) --}}
        @if($licSvc->isEnabled('pharmacy') && $u->hasRole(['admin','doctor','nurse']))
        <div class="ns-wrap" id="nsw-pharmacy">
            <div class="nav-section" data-ns="pharmacy" onclick="toggleNS('pharmacy')">
                <span><i class="fa-solid fa-grip-vertical ns-handle" onclick="event.stopPropagation()"></i> Pharmacy</span>
                <i class="fa-solid fa-chevron-down nsc"></i>
            </div>
            <div class="nav-group" id="ng-pharmacy">
                <a href="{{ route('pharmacy.dashboard') }}" class="nav-item {{ request()->routeIs('pharmacy.dashboard') ? 'active' : '' }}">
                    <div class="nav-item-inner">
                        <i class="fa-solid fa-pills"></i> Pharmacy
                        @php $lowStk = \DB::table('pharmacy_stock')->where('is_active',true)->whereColumn('quantity','<=','min_stock_level')->where('quantity','>',0)->count(); @endphp
                        @if($lowStk > 0) <span class="nav-badge">{{ $lowStk }}</span> @endif
                    </div>
                </a>
                <a href="{{ route('pharmacy.stock-management') }}" class="nav-item {{ request()->routeIs('pharmacy.stock-management') || request()->routeIs('pharmacy.expired') || request()->routeIs('pharmacy.stock.bulk-remove*') || request()->routeIs('pharmacy.stock.removal-history') ? 'active' : '' }}">
                    <div class="nav-item-inner">
                        <i class="fa-solid fa-boxes-stacked"></i> Stock Management
                        @php $exp = \DB::table('pharmacy_stock')->where('is_active',true)->whereNotNull('expiry_date')->where('expiry_date','<',today())->count(); @endphp
                        @if($exp > 0)<span class="nav-badge" style="background:var(--sc-red)">{{ $exp }}</span>@endif
                    </div>
                </a>
                <a href="{{ route('pharmacy.interactions.index') }}" class="nav-item {{ request()->routeIs('pharmacy.interactions.*') ? 'active' : '' }}">
                    <div class="nav-item-inner"><i class="fa-solid fa-triangle-exclamation"></i> Drug Interactions</div>
                </a>
            </div>
        </div>
        @endif

        {{-- Billing (gated by license `billing` module) --}}
        @if($licSvc->isEnabled('billing') && $u->hasRole(['admin','receptionist','nurse']))
        <div class="ns-wrap" id="nsw-billing">
            <div class="nav-section" data-ns="billing" onclick="toggleNS('billing')">
                <span><i class="fa-solid fa-grip-vertical ns-handle" onclick="event.stopPropagation()"></i> Billing</span>
                <i class="fa-solid fa-chevron-down nsc"></i>
            </div>
            <div class="nav-group" id="ng-billing">
                <a href="{{ route('billing.index') }}" class="nav-item {{ request()->routeIs('billing.*') ? 'active' : '' }}">
                    <div class="nav-item-inner">
                        <i class="fa-solid fa-file-invoice-dollar"></i> Billing
                        @php try { $overdueBills = \DB::table('bills')->where('status','Unpaid')->whereDate('created_at','<=',today()->subDays(7))->count(); } catch(\Exception $e) { $overdueBills = 0; } @endphp
                        @if($overdueBills > 0)<span class="nav-badge">{{ $overdueBills }}</span>@endif
                    </div>
                </a>
                <a href="{{ route('packages.index') }}" class="nav-item {{ request()->routeIs('packages.*') ? 'active' : '' }}">
                    <div class="nav-item-inner"><i class="fa-solid fa-box-open"></i> Billing Packages</div>
                </a>
                <a href="{{ route('insurance.claims.index') }}" class="nav-item {{ request()->routeIs('insurance.*') ? 'active' : '' }}">
                    <div class="nav-item-inner"><i class="fa-solid fa-shield-halved"></i> Insurance / TPA</div>
                </a>
                <a href="{{ route('insurers.index') }}" class="nav-item {{ request()->routeIs('insurers.*') ? 'active' : '' }}">
                    <div class="nav-item-inner"><i class="fa-solid fa-building-columns"></i> Insurers &amp; TPAs</div>
                </a>
            </div>
        </div>
        @endif

        {{-- Accounts & GST (gated by license `accounts` module) --}}
        @if($licSvc->isEnabled('accounts') && $u->can('accounts.manage'))
        <div class="ns-wrap" id="nsw-accounts">
            <div class="nav-section" data-ns="accounts" onclick="toggleNS('accounts')">
                <span><i class="fa-solid fa-grip-vertical ns-handle" onclick="event.stopPropagation()"></i> Accounts</span>
                <i class="fa-solid fa-chevron-down nsc"></i>
            </div>
            <div class="nav-group" id="ng-accounts">
                <a href="{{ route('accounts.index') }}" class="nav-item {{ request()->routeIs('accounts.index') ? 'active' : '' }}">
                    <div class="nav-item-inner"><i class="fa-solid fa-book"></i> Dashboard</div>
                </a>
                <a href="{{ route('accounts.expenses.quick') }}" class="nav-item {{ request()->routeIs('accounts.expenses.quick') || request()->routeIs('accounts.expenses.store') ? 'active' : '' }}">
                    <div class="nav-item-inner"><i class="fa-solid fa-receipt"></i> Quick Expense</div>
                </a>
                <a href="{{ route('accounts.expenses.pay-supplier') }}" class="nav-item {{ request()->routeIs('accounts.expenses.pay-supplier*') ? 'active' : '' }}">
                    <div class="nav-item-inner"><i class="fa-solid fa-truck-fast"></i> Pay Supplier</div>
                </a>
                <a href="{{ route('accounts.expenses.salary') }}" class="nav-item {{ request()->routeIs('accounts.expenses.salary*') ? 'active' : '' }}">
                    <div class="nav-item-inner"><i class="fa-solid fa-money-bill-wave"></i> Pay Salary</div>
                </a>
                <a href="{{ route('accounts.payees.index') }}" class="nav-item {{ request()->routeIs('accounts.payees.*') ? 'active' : '' }}">
                    <div class="nav-item-inner"><i class="fa-solid fa-people-group"></i> Staff &amp; Payees</div>
                </a>
                <a href="{{ route('accounts.suppliers.index') }}" class="nav-item {{ request()->routeIs('accounts.suppliers.*') ? 'active' : '' }}">
                    <div class="nav-item-inner"><i class="fa-solid fa-truck-fast"></i> Suppliers</div>
                </a>
                <a href="{{ route('accounts.vouchers.index') }}" class="nav-item {{ request()->routeIs('accounts.vouchers.*') ? 'active' : '' }}">
                    <div class="nav-item-inner"><i class="fa-solid fa-file-lines"></i> Vouchers</div>
                </a>
                <a href="{{ route('accounts.ledgers.index') }}" class="nav-item {{ request()->routeIs('accounts.ledgers.*') ? 'active' : '' }}">
                    <div class="nav-item-inner"><i class="fa-solid fa-list-ul"></i> Ledgers</div>
                </a>
                <a href="{{ route('accounts.recurring.index') }}" class="nav-item {{ request()->routeIs('accounts.recurring.*') ? 'active' : '' }}">
                    <div class="nav-item-inner"><i class="fa-solid fa-repeat"></i> Recurring</div>
                </a>
                <a href="{{ route('accounts.reports.profit-loss') }}" class="nav-item {{ request()->routeIs('accounts.reports.profit-loss') ? 'active' : '' }}">
                    <div class="nav-item-inner"><i class="fa-solid fa-chart-line"></i> P&amp;L</div>
                </a>
                <a href="{{ route('accounts.reports.balance-sheet') }}" class="nav-item {{ request()->routeIs('accounts.reports.balance-sheet') ? 'active' : '' }}">
                    <div class="nav-item-inner"><i class="fa-solid fa-book-open"></i> Balance Sheet</div>
                </a>
                <a href="{{ route('accounts.reports.gstr1') }}" class="nav-item {{ request()->routeIs('accounts.reports.gstr*') || request()->routeIs('accounts.reports.hsn-summary') ? 'active' : '' }}">
                    <div class="nav-item-inner"><i class="fa-solid fa-file-invoice"></i> GST Returns</div>
                </a>
                <a href="{{ route('accounts.settings.edit') }}" class="nav-item {{ request()->routeIs('accounts.settings.*') ? 'active' : '' }}">
                    <div class="nav-item-inner"><i class="fa-solid fa-gear"></i> Settings</div>
                </a>
            </div>
        </div>
        @endif

        {{-- Forum & Messaging (gated by the `forum` paid module) --}}
        @if($licSvc->isEnabled('forum') && ($u->can('forum.view') || $u->can('messages.send')))
        <div class="ns-wrap" id="nsw-forum">
            <div class="nav-section" data-ns="forum" onclick="toggleNS('forum')">
                <span><i class="fa-solid fa-grip-vertical ns-handle" onclick="event.stopPropagation()"></i> Forum &amp; Messages</span>
                <i class="fa-solid fa-chevron-down nsc"></i>
            </div>
            <div class="nav-group" id="ng-forum">
                {{-- phpBB community forum (served at /forum) --}}
                <a href="{{ url('/forum') }}" class="nav-item">
                    <div class="nav-item-inner"><i class="fa-solid fa-users-rectangle"></i> Community Forum</div>
                </a>
                @if($u->can('forum.view'))
                <a href="{{ route('forum.index') }}" class="nav-item {{ request()->routeIs('forum.index') || request()->routeIs('forum.show') || request()->routeIs('forum.topic*') || request()->routeIs('forum.reply') || request()->routeIs('forum.post.*') ? 'active' : '' }}">
                    <div class="nav-item-inner"><i class="fa-solid fa-comments"></i> Message Boards</div>
                </a>
                @endif
                @if($u->can('messages.send'))
                <a href="{{ route('messages.inbox') }}" class="nav-item {{ request()->routeIs('messages.*') ? 'active' : '' }}">
                    <div class="nav-item-inner"><i class="fa-solid fa-envelope"></i> Direct Messages
                        <span id="msg-unread-badge" style="display:none;background:var(--sc-red);color:#fff;border-radius:10px;font-size:10px;font-weight:700;padding:1px 6px;margin-left:6px"></span>
                    </div>
                </a>
                @endif
                @if($u->can('forum.admin'))
                <a href="{{ route('forum.admin.index') }}" class="nav-item {{ request()->routeIs('forum.admin.*') ? 'active' : '' }}">
                    <div class="nav-item-inner"><i class="fa-solid fa-gear"></i> Boards Admin</div>
                </a>
                @endif
            </div>
        </div>
        @endif

        {{-- Notes --}}
        @if($u->hasRole(['admin','doctor','nurse','receptionist']))
        <div class="ns-wrap" id="nsw-notes">
            <div class="nav-section" data-ns="notes" onclick="toggleNS('notes')">
                <span><i class="fa-solid fa-grip-vertical ns-handle" onclick="event.stopPropagation()"></i> Notes</span>
                <i class="fa-solid fa-chevron-down nsc"></i>
            </div>
            <div class="nav-group" id="ng-notes">
                <a href="{{ route('notes.index') }}" class="nav-item {{ request()->routeIs('notes.*') ? 'active' : '' }}">
                    <div class="nav-item-inner"><i class="fa-solid fa-note-sticky"></i> Notes</div>
                </a>
            </div>
        </div>
        @endif

        {{-- Templates --}}
        @if($u->hasRole(['admin','doctor','nurse']))
        <div class="ns-wrap" id="nsw-templates">
            <div class="nav-section" data-ns="templates" onclick="toggleNS('templates')">
                <span><i class="fa-solid fa-grip-vertical ns-handle" onclick="event.stopPropagation()"></i> Templates</span>
                <i class="fa-solid fa-chevron-down nsc"></i>
            </div>
            <div class="nav-group" id="ng-templates">
                <a href="{{ route('charge-templates.index') }}" class="nav-item {{ request()->routeIs('charge-templates.*') ? 'active' : '' }}">
                    <div class="nav-item-inner"><i class="fa-solid fa-layer-group"></i> Charge Templates</div>
                </a>
                <a href="{{ route('encounter-templates.index') }}" class="nav-item {{ request()->routeIs('encounter-templates.*') ? 'active' : '' }}">
                    <div class="nav-item-inner"><i class="fa-solid fa-file-medical"></i> Encounter Templates</div>
                </a>
                <a href="{{ route('prescription-templates.index') }}" class="nav-item {{ request()->routeIs('prescription-templates.*') ? 'active' : '' }}">
                    <div class="nav-item-inner"><i class="fa-solid fa-prescription"></i> Rx Templates</div>
                </a>
                <a href="{{ route('consent-templates.index') }}" class="nav-item {{ request()->routeIs('consent-templates.*') ? 'active' : '' }}">
                    <div class="nav-item-inner"><i class="fa-solid fa-file-signature"></i> Consent Templates</div>
                </a>
                <a href="{{ route('diet-prescriptions.index') }}" class="nav-item {{ request()->routeIs('diet-prescriptions.*') ? 'active' : '' }}">
                    <div class="nav-item-inner"><i class="fa-solid fa-utensils"></i> Diet Prescriptions</div>
                </a>
            </div>
        </div>
        @endif

        {{-- Tools --}}
        @if($u->hasRole(['admin','doctor','nurse']))
        <div class="ns-wrap" id="nsw-tools">
            <div class="nav-section" data-ns="tools" onclick="toggleNS('tools')">
                <span><i class="fa-solid fa-grip-vertical ns-handle" onclick="event.stopPropagation()"></i> Tools</span>
                <i class="fa-solid fa-chevron-down nsc"></i>
            </div>
            <div class="nav-group" id="ng-tools">
                <a href="{{ route('tools.duplicates') }}" class="nav-item {{ request()->routeIs('tools.duplicates*') ? 'active' : '' }}">
                    <div class="nav-item-inner"><i class="fa-solid fa-clone"></i> Find Duplicates</div>
                </a>
            </div>
        </div>
        @endif

        {{-- Administration — admin.* + HR perms only (Reports/Certificates have their own sections).
             On installs where RESTRICT_ADMIN_TO_OWNER is set (e.g. the public demo at co.in),
             hide the section from anyone except the configured owner email. --}}
        @php
            $sc_ownerEmail = (string) config('app.restrict_admin_to_owner');
            $sc_isOwner = $sc_ownerEmail === '' || ($u && strcasecmp((string)$u->email, $sc_ownerEmail) === 0);
        @endphp
        @if($sc_isOwner && $u->canAny([
            'admin.section','admin.doctors','admin.staff','admin.users','admin.duty_roster',
            'admin.settings','admin.logs','admin.backup','admin.import','admin.referrals',
            'admin.modules','admin.licensing','admin.roles','admin.notifications',
            'hr.attendance','hr.adjustments','hr.payroll',
        ]))
        <div class="ns-wrap" id="nsw-administration">
            <div class="nav-section" data-ns="administration" onclick="toggleNS('administration')">
                <span><i class="fa-solid fa-grip-vertical ns-handle" onclick="event.stopPropagation()"></i> Administration</span>
                <i class="fa-solid fa-chevron-down nsc"></i>
            </div>
            <div class="nav-group" id="ng-administration">
                @php $hr = app(\App\Services\LicenseService::class)->isEnabled('hr'); @endphp

                @if($u->can('admin.doctors'))
                <a href="{{ route('doctors.index') }}" class="nav-item {{ request()->routeIs('doctors.*') ? 'active' : '' }}">
                    <div class="nav-item-inner"><i class="fa-solid fa-user-doctor"></i> Doctors</div>
                </a>
                <a href="{{ route('departments.index') }}" class="nav-item {{ request()->routeIs('departments.*') ? 'active' : '' }}">
                    <div class="nav-item-inner"><i class="fa-solid fa-hospital"></i> Departments</div>
                </a>
                <a href="{{ route('opd.fees.index') }}" class="nav-item {{ request()->routeIs('opd.fees.*') ? 'active' : '' }}">
                    <div class="nav-item-inner"><i class="fa-solid fa-indian-rupee-sign"></i> OPD Fee Rules</div>
                </a>
                @endif
                @if($u->can('admin.staff'))
                <a href="{{ route('staff.index') }}" class="nav-item {{ request()->routeIs('staff.*') ? 'active' : '' }}">
                    <div class="nav-item-inner"><i class="fa-solid fa-id-badge"></i> Staff</div>
                </a>
                @endif
                @if($u->can('admin.users'))
                <a href="{{ route('users.index') }}" class="nav-item {{ request()->routeIs('users.*') ? 'active' : '' }}">
                    <div class="nav-item-inner"><i class="fa-solid fa-users-gear"></i> Users</div>
                </a>
                @endif
                @if($hr && $u->can('hr.attendance'))
                <a href="{{ route('hr.attendance.index') }}" class="nav-item {{ request()->routeIs('hr.attendance.*') ? 'active' : '' }}">
                    <div class="nav-item-inner"><i class="fa-solid fa-calendar-check"></i> Attendance</div>
                </a>
                @endif
                @if($hr && $u->can('hr.adjustments'))
                <a href="{{ route('hr.adjustments.index') }}" class="nav-item {{ request()->routeIs('hr.adjustments.*') ? 'active' : '' }}">
                    <div class="nav-item-inner"><i class="fa-solid fa-coins"></i> Pay Adjustments</div>
                </a>
                @endif
                @if($hr && $u->can('hr.payroll'))
                <a href="{{ route('hr.payroll.index') }}" class="nav-item {{ request()->routeIs('hr.payroll.*') ? 'active' : '' }}">
                    <div class="nav-item-inner"><i class="fa-solid fa-money-check-dollar"></i> Payroll</div>
                </a>
                @endif
                @if($u->can('admin.logs'))
                <a href="{{ route('logs.index') }}" class="nav-item {{ request()->routeIs('logs.*') ? 'active' : '' }}">
                    <div class="nav-item-inner"><i class="fa-solid fa-clock-rotate-left"></i> Activity Logs</div>
                </a>
                @endif
                @if($u->can('admin.import'))
                <a href="{{ route('import.index') }}" class="nav-item {{ request()->routeIs('import.*') ? 'active' : '' }}">
                    <div class="nav-item-inner"><i class="fa-solid fa-file-import"></i> Import OpenEMR</div>
                </a>
                <a href="{{ route('export.index') }}" class="nav-item {{ request()->routeIs('export.*') ? 'active' : '' }}">
                    <div class="nav-item-inner"><i class="fa-solid fa-file-export"></i> Export to OpenEMR</div>
                </a>
                @endif
                @if($u->can('admin.referrals'))
                <a href="{{ route('referrals.index') }}" class="nav-item {{ request()->routeIs('referrals.*') ? 'active' : '' }}">
                    <div class="nav-item-inner"><i class="fa-solid fa-handshake"></i> Referrals</div>
                </a>
                @endif
                @if($u->can('admin.settings'))
                <a href="{{ route('settings.index') }}" class="nav-item {{ request()->routeIs('settings.*') ? 'active' : '' }}">
                    <div class="nav-item-inner"><i class="fa-solid fa-gear"></i> Settings</div>
                </a>
                @endif
                @if($u->can('admin.backup'))
                <a href="{{ route('admin.backup') }}" class="nav-item {{ request()->routeIs('admin.backup*') ? 'active' : '' }}">
                    <div class="nav-item-inner"><i class="fa-solid fa-database"></i> Backup &amp; Export</div>
                </a>
                @endif
                @if($u->can('admin.roles'))
                <a href="{{ route('admin.roles.index') }}" class="nav-item {{ request()->routeIs('admin.roles.*') ? 'active' : '' }}">
                    <div class="nav-item-inner"><i class="fa-solid fa-shield-halved"></i> Roles &amp; Permissions</div>
                </a>
                @endif
                @if($u->can('admin.modules'))
                <a href="{{ route('admin.modules.index') }}" class="nav-item {{ request()->routeIs('admin.modules*') ? 'active' : '' }}">
                    <div class="nav-item-inner"><i class="fa-solid fa-cubes"></i> Modules</div>
                </a>
                @endif
                @if($u->can('admin.notifications') && app(\App\Services\LicenseService::class)->isEnabled('notifications'))
                <a href="{{ route('admin.notifications.whatsapp') }}" class="nav-item {{ request()->routeIs('admin.notifications.*') ? 'active' : '' }}">
                    <div class="nav-item-inner"><i class="fa-brands fa-whatsapp"></i> WhatsApp Notifications</div>
                </a>
                @endif
                @if($u->can('admin.licensing') && strlen((string) config('modules.owner_key', '')) >= 16)
                <a href="{{ session('owner_authed') ? route('owner.modules.index') : route('owner.login') }}" class="nav-item {{ request()->is('owner*') ? 'active' : '' }}">
                    <div class="nav-item-inner"><i class="fa-solid fa-key" style="color:var(--sc-amber)"></i> License Management</div>
                </a>
                @endif
                @if($u->can('opd.queue') || $u->can('admin.section'))
                <a href="{{ route('opd.public-board') }}" class="nav-item" target="_blank">
                    <div class="nav-item-inner"><i class="fa-solid fa-tv"></i> TV Queue Board</div>
                </a>
                @endif
            </div>
        </div>
        @endif

        {{-- Reports — own section, gated on reports.view --}}
        @if($u->can('reports.view'))
        <div class="ns-wrap" id="nsw-reports">
            <div class="nav-section" data-ns="reports" onclick="toggleNS('reports')">
                <span><i class="fa-solid fa-grip-vertical ns-handle" onclick="event.stopPropagation()"></i> Reports</span>
                <i class="fa-solid fa-chevron-down nsc"></i>
            </div>
            <div class="nav-group" id="ng-reports">
                <a href="{{ route('reports.index') }}" class="nav-item {{ request()->routeIs('reports.index') ? 'active' : '' }}">
                    <div class="nav-item-inner"><i class="fa-solid fa-chart-bar"></i> Reports Overview</div>
                </a>
                <a href="{{ route('reports.doctor-revenue') }}" class="nav-item {{ request()->routeIs('reports.doctor-revenue') ? 'active' : '' }}">
                    <div class="nav-item-inner"><i class="fa-solid fa-user-doctor"></i> Doctor Revenue</div>
                </a>
                <a href="{{ route('reports.referral-performance') }}" class="nav-item {{ request()->routeIs('reports.referral-performance') ? 'active' : '' }}">
                    <div class="nav-item-inner"><i class="fa-solid fa-handshake"></i> Referral Performance</div>
                </a>
                <a href="{{ route('reports.occupancy') }}" class="nav-item {{ request()->routeIs('reports.occupancy') ? 'active' : '' }}">
                    <div class="nav-item-inner"><i class="fa-solid fa-bed-pulse"></i> Bed Occupancy</div>
                </a>
                <a href="{{ route('reports.revenue') }}" class="nav-item {{ request()->routeIs('reports.revenue') ? 'active' : '' }}">
                    <div class="nav-item-inner"><i class="fa-solid fa-chart-line"></i> Revenue Dashboard</div>
                </a>
                <a href="{{ route('reports.packages') }}" class="nav-item {{ request()->routeIs('reports.packages') ? 'active' : '' }}">
                    <div class="nav-item-inner"><i class="fa-solid fa-box"></i> Package Usage</div>
                </a>
                <a href="{{ route('reports.diagnoses') }}" class="nav-item {{ request()->routeIs('reports.diagnoses') ? 'active' : '' }}">
                    <div class="nav-item-inner"><i class="fa-solid fa-diagnoses"></i> Diagnosis Analytics</div>
                </a>
                <a href="{{ route('reports.day-end') }}" class="nav-item {{ request()->routeIs('reports.day-end') ? 'active' : '' }}">
                    <div class="nav-item-inner"><i class="fa-solid fa-cash-register"></i> Day-End Report</div>
                </a>
                <a href="{{ route('reports.outstanding-dues') }}" class="nav-item {{ request()->routeIs('reports.outstanding-dues') ? 'active' : '' }}">
                    <div class="nav-item-inner"><i class="fa-solid fa-triangle-exclamation"></i> Outstanding Dues</div>
                </a>
                <a href="{{ route('reports.follow-up') }}" class="nav-item {{ request()->routeIs('reports.follow-up') ? 'active' : '' }}">
                    <div class="nav-item-inner">
                        <i class="fa-solid fa-calendar-check"></i> Follow-Up Tracker
                        @php try { $overdueFollowUps = \App\Models\Encounter::whereNotNull('follow_up_date')->where('status','!=','cancelled')->whereDate('follow_up_date','<',today())->count(); } catch(\Exception $e) { $overdueFollowUps = 0; } @endphp
                        @if($overdueFollowUps > 0)<span class="nav-badge" style="background:#fef2f2;color:#991b1b">{{ $overdueFollowUps }}</span>@endif
                    </div>
                </a>
            </div>
        </div>
        @endif

        {{-- Certificates — own small section, gated on certificates.manage --}}
        @if($u->can('certificates.manage'))
        <div class="ns-wrap" id="nsw-certificates">
            <div class="nav-section" data-ns="certificates" onclick="toggleNS('certificates')">
                <span><i class="fa-solid fa-grip-vertical ns-handle" onclick="event.stopPropagation()"></i> Certificates</span>
                <i class="fa-solid fa-chevron-down nsc"></i>
            </div>
            <div class="nav-group" id="ng-certificates">
                <a href="{{ route('certificates.index') }}" class="nav-item {{ request()->routeIs('certificates.*') ? 'active' : '' }}">
                    <div class="nav-item-inner"><i class="fa-solid fa-file-certificate"></i> Certificates</div>
                </a>
            </div>
        </div>
        @endif

    </div>
    <div class="sidebar-footer">
        <div style="font-size:11px; margin-bottom:4px;">{{ now()->format('D, d M Y') }}</div>
        <div>v1.0.0 &nbsp;·&nbsp; {{ setting('clinic_name', 'James Heart Care Pvt. Ltd.') }}</div>
    </div>
</aside>

<!-- MAIN WRAP -->
<div id="main-wrap">
    <!-- TOPBAR -->
    <header id="topbar">
        <button id="sidebar-toggle" class="sidebar-toggle-btn" title="Toggle sidebar">
            <i class="fa-solid fa-bars"></i>
        </button>
        <div class="topbar-title">@yield('page-title', 'Dashboard')</div>

        <!-- Global Patient Search -->
        <div class="topbar-search" style="position:relative">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input type="text" id="global-search" placeholder="Search patients, encounters, bills…" autocomplete="off">
            <div id="global-search-results" style="position:absolute;top:100%;left:0;right:0;background:#fff;border:1px solid var(--sc-border);border-radius:8px;box-shadow:var(--shadow-md);z-index:200;display:none;max-height:300px;overflow-y:auto;margin-top:4px"></div>
        </div>

        <div class="topbar-actions">
            <a href="{{ route('patients.create') }}" class="btn btn-primary btn-sm">
                <i class="fa-solid fa-user-plus"></i> <span class="hide-mobile">New Patient</span>
            </a>
            <a href="{{ route('appointments.create') }}" class="topbar-btn" title="New Appointment">
                <i class="fa-solid fa-calendar-plus"></i>
            </a>
            <button id="dark-mode-toggle" class="topbar-btn" title="Toggle dark mode" onclick="toggleDarkMode()">
                <i class="fa-solid fa-moon" id="dark-mode-icon"></i>
            </button>
            <button class="topbar-btn" title="Keyboard shortcuts (?)" onclick="document.getElementById('shortcuts-modal').classList.add('open')">
                <i class="fa-solid fa-keyboard"></i>
            </button>
            <div class="topbar-btn" title="Notifications" id="notif-btn" style="position:relative;cursor:pointer" onclick="toggleNotifPanel(event)">
                <i class="fa-solid fa-bell"></i>
                <span class="badge-dot" id="notif-dot" style="display:none"></span>
                <span id="notif-count" style="position:absolute;top:-4px;right:-4px;background:var(--sc-red);color:#fff;border-radius:10px;font-size:9px;font-weight:700;padding:1px 5px;min-width:14px;text-align:center;display:none"></span>
                <div id="notif-panel" style="display:none;position:absolute;top:42px;right:0;width:360px;max-height:480px;background:var(--sc-surface);border:1px solid var(--sc-border);border-radius:10px;box-shadow:0 8px 24px rgba(15,31,61,.15);z-index:200;overflow:hidden;text-align:left">
                    <div style="padding:10px 14px;border-bottom:1px solid var(--sc-border);display:flex;align-items:center;justify-content:space-between">
                        <strong style="font-size:13px">Notifications</strong>
                        <span style="display:flex;gap:6px;align-items:center">
                            <button type="button" id="dm-popup-toggle" class="btn btn-icon btn-sm btn-outline" style="font-size:10px" onclick="toggleDmPopups(event)" title="Message pop-ups"><i class="fa-solid fa-comment-dots"></i></button>
                            <button type="button" class="btn btn-icon btn-sm btn-outline" style="font-size:10px" onclick="loadNotifications(event)" title="Refresh"><i class="fa-solid fa-rotate"></i></button>
                        </span>
                    </div>
                    <div id="notif-body" style="max-height:420px;overflow-y:auto">
                        <div style="padding:24px;text-align:center;color:var(--sc-muted);font-size:12px"><i class="fa-solid fa-spinner fa-spin"></i> Loading…</div>
                    </div>
                </div>
            </div>
            <a href="{{ route('profile.edit') }}" class="topbar-btn" title="My Profile" style="text-decoration:none;display:inline-flex;align-items:center">
                <i class="fa-solid fa-user-gear"></i>
            </a>
            <form method="POST" action="{{ route('logout') }}" style="margin:0">
                @csrf
                <button type="submit" class="user-pill" style="border:1px solid var(--sc-border)">
                    <div class="avatar">{{ strtoupper(substr(auth()->user()->name,0,2)) }}</div>
                    <span class="uname">{{ auth()->user()->name }}</span>
                </button>
            </form>
        </div>
    </header>

    <!-- FLASH MESSAGES -->
    <div style="padding: 0 24px; margin-top: 16px">
        @if(session('success'))
            <div class="alert alert-success"><i class="fa-solid fa-circle-check"></i> <div>{{ session('success') }}</div></div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger"><i class="fa-solid fa-circle-xmark"></i> <div>{{ session('error') }}</div></div>
        @endif
        @if($errors->any())
            <div class="alert alert-danger{{ $errors->has('checklist') ? ' alert-flash' : '' }}">
                <i class="fa-solid fa-triangle-exclamation"></i>
                <div>
                    @foreach($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>

    @yield('patient-context')

    <!-- PAGE CONTENT -->
    <main id="page-content">
        @yield('content')
    </main>
</div>

<!-- ── COMMAND PALETTE (Cmd/Ctrl+K) ── -->
<style>
#cmd-palette { align-items: flex-start; padding-top: 80px; }
#cmd-palette .modal-box { width: 92%; max-width: 640px; padding: 0; overflow: hidden; }
#cmd-palette .cp-input-wrap { display:flex; align-items:center; gap:10px; padding:14px 18px; border-bottom:1px solid var(--sc-border); }
#cmd-palette .cp-input-wrap input { flex:1; border:none; outline:none; font-size:15px; background:transparent; color:var(--sc-text); font-family:inherit; }
#cmd-palette .cp-input-wrap kbd { font-family:'DM Mono',monospace; font-size:11px; background:var(--sc-bg); border:1px solid var(--sc-border); border-radius:4px; padding:2px 6px; color:var(--sc-muted); }
#cmd-palette .cp-results { max-height: 60vh; overflow-y: auto; padding: 6px 0; }
#cmd-palette .cp-section-label { font-size:10.5px; text-transform:uppercase; letter-spacing:1px; color:var(--sc-muted); padding:10px 18px 4px; font-weight:600; }
#cmd-palette .cp-item { display:flex; align-items:center; gap:12px; padding:10px 18px; cursor:pointer; text-decoration:none; color:var(--sc-text); border-left:2px solid transparent; }
#cmd-palette .cp-item:hover, #cmd-palette .cp-item.active { background: rgba(10,180,180,.08); border-left-color: var(--sc-teal); }
#cmd-palette .cp-icon { width:30px; height:30px; border-radius:8px; background:rgba(0,0,0,.04); display:flex; align-items:center; justify-content:center; font-size:13px; flex-shrink:0; }
#cmd-palette .cp-label { font-weight:600; font-size:13px; }
#cmd-palette .cp-sub { font-size:11px; color:var(--sc-muted); }
#cmd-palette .cp-footer { display:flex; gap:14px; padding:8px 18px; border-top:1px solid var(--sc-border); background:var(--sc-bg); font-size:11px; color:var(--sc-muted); }
#cmd-palette .cp-footer kbd { font-family:'DM Mono',monospace; font-size:10px; background:var(--sc-surface); border:1px solid var(--sc-border); border-radius:4px; padding:1px 5px; color:var(--sc-text); }
[data-theme="dark"] #cmd-palette .cp-input-wrap kbd { background: var(--sc-bg); }
[data-theme="dark"] #cmd-palette .cp-footer { background: var(--sc-bg); }
[data-theme="dark"] #cmd-palette .cp-icon { background: var(--sc-bg); }
</style>
<div id="cmd-palette" class="modal-overlay">
    <div class="modal-box">
        <div class="cp-input-wrap">
            <i class="fa-solid fa-magnifying-glass" style="color:var(--sc-muted)"></i>
            <input type="text" id="cp-input" placeholder="Search patients, encounters, bills — or jump to a page…" autocomplete="off">
            <kbd>Esc</kbd>
        </div>
        <div class="cp-results" id="cp-results"></div>
        <div class="cp-footer">
            <span><kbd>↑</kbd> <kbd>↓</kbd> Navigate</span>
            <span><kbd>↵</kbd> Open</span>
            <span><kbd>Esc</kbd> Close</span>
        </div>
    </div>
</div>

<!-- Keyboard Shortcuts Modal -->
<div id="shortcuts-modal" class="modal-overlay">
    <div class="modal-box" style="max-width:480px">
        <div class="modal-header">
            <i class="fa-solid fa-keyboard" style="color:var(--sc-teal)"></i>
            <h3>Keyboard Shortcuts</h3>
            <button class="modal-close" onclick="document.getElementById('shortcuts-modal').classList.remove('open')">&times;</button>
        </div>
        <div class="modal-body">
            <table style="width:100%;border-collapse:collapse;font-size:13px">
                @foreach([
                    ['⌘ K / Ctrl K','Command Palette'],
                    ['/','Command Palette'],
                    ['n','Add entry (diary) / New Patient'],['b','New Bill'],['a','New Appointment'],
                    ['e','New Visit for this patient (on patient pages)'],
                    ['d','Dashboard'],['p','Patients List'],['?','Show Shortcuts'],['Esc','Close Modal'],
                ] as [$key,$desc])
                <tr style="border-bottom:1px solid var(--sc-border)">
                    <td style="padding:10px 0;width:80px">
                        <kbd style="background:var(--sc-bg);border:1.5px solid var(--sc-border);border-radius:5px;padding:3px 10px;font-family:'DM Mono',monospace;font-size:13px;font-weight:600">{{ $key }}</kbd>
                    </td>
                    <td style="padding:10px 0;color:var(--sc-text)">{{ $desc }}</td>
                </tr>
                @endforeach
            </table>
            <p style="font-size:11.5px;color:var(--sc-muted);margin-top:14px"><i class="fa-solid fa-info-circle"></i> Shortcuts are disabled when typing in input fields.</p>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.1.0-rc.0/js/select2.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.15.2/Sortable.min.js"></script>
<script>
// CSRF setup
$.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });

// Book Follow-up → navigates to /appointments/create with patient + doctor +
// suggested date pre-filled from the encounter, so staff can pick the exact
// time (defaults to 17:00) and confirm. Replaces the prior one-click
// auto-create which didn't let staff adjust the time.
window.scBookFollowUp = function (encId, btn) {
    if (btn) { btn.disabled = true; btn.dataset._t = btn.innerHTML; btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>'; }
    window.location.href = '{{ url("/encounters") }}/' + encId + '/book-followup';
};

// ── COLLAPSIBLE NAV SECTIONS ──
const NS_KEY  = 'sc_nav_sections';
const NS_ORD  = 'sc_nav_order';
const NS_ITEMS= 'sc_nav_items';

function toggleNS(id) {
    const grp = document.getElementById('ng-' + id);
    const hdr = document.querySelector('[data-ns="' + id + '"]');
    if (!grp) return;
    const collapsed = grp.classList.toggle('ng-collapsed');
    hdr.classList.toggle('ns-collapsed', collapsed);
    const state = JSON.parse(localStorage.getItem(NS_KEY) || '{}');
    state[id] = !collapsed;
    localStorage.setItem(NS_KEY, JSON.stringify(state));
}

function saveNavOrder() {
    const scroll = document.getElementById('sidebar-scroll');
    if (!scroll) return;
    const order = [...scroll.querySelectorAll(':scope > .ns-wrap')].map(w => w.id);
    localStorage.setItem(NS_ORD, JSON.stringify(order));
}

function saveNavItems() {
    const data = {};
    document.querySelectorAll('.nav-group').forEach(function(grp) {
        data[grp.id] = [...grp.querySelectorAll(':scope > .nav-item')].map(a => a.getAttribute('href') || '');
    });
    localStorage.setItem(NS_ITEMS, JSON.stringify(data));
}

document.addEventListener('DOMContentLoaded', function() {
    renderSidebarRecentPts();
    const scroll = document.getElementById('sidebar-scroll');

    // ── Restore section order ──
    const savedOrder = JSON.parse(localStorage.getItem(NS_ORD) || 'null');
    if (savedOrder && scroll) {
        const wraps = [...scroll.querySelectorAll(':scope > .ns-wrap')];
        const reordered = [];
        savedOrder.forEach(function(id) { const el = wraps.find(w => w.id === id); if (el) reordered.push(el); });
        wraps.forEach(function(w) { if (!reordered.includes(w)) reordered.push(w); });
        reordered.forEach(function(el) { scroll.appendChild(el); });
    }

    // ── Restore item order within groups ──
    const savedItems = JSON.parse(localStorage.getItem(NS_ITEMS) || 'null');
    if (savedItems) {
        Object.entries(savedItems).forEach(function([grpId, hrefs]) {
            const grp = document.getElementById(grpId);
            if (!grp) return;
            hrefs.forEach(function(href) {
                if (!href) return;
                const el = document.querySelector('.nav-group > .nav-item[href="' + href + '"]');
                if (el) grp.appendChild(el);
            });
        });
    }

    // ── Restore collapse state ──
    const state = JSON.parse(localStorage.getItem(NS_KEY) || '{}');
    document.querySelectorAll('[data-ns]').forEach(function(hdr) {
        const id  = hdr.getAttribute('data-ns');
        const grp = document.getElementById('ng-' + id);
        if (!grp) return;
        const hasActive = grp.querySelector('.nav-item.active');
        const open = hasActive ? true : (state[id] !== undefined ? state[id] : true);
        if (!open) { grp.classList.add('ng-collapsed'); hdr.classList.add('ns-collapsed'); }
    });

    // ── Inject item drag handles ──
    document.querySelectorAll('.nav-group .nav-item .nav-item-inner').forEach(function(inner) {
        const h = document.createElement('i');
        h.className = 'fa-solid fa-grip-vertical ni-handle';
        inner.insertBefore(h, inner.firstChild);
    });

    // ── SortableJS: section groups ──
    if (scroll && typeof Sortable !== 'undefined') {
        new Sortable(scroll, {
            handle: '.ns-handle',
            draggable: '.ns-wrap',
            animation: 180,
            ghostClass: 'sortable-ghost',
            chosenClass: 'sortable-chosen',
            onEnd: saveNavOrder
        });

        // ── SortableJS: items within / across groups ──
        document.querySelectorAll('.nav-group').forEach(function(grp) {
            new Sortable(grp, {
                handle: '.ni-handle',
                group: 'sc-nav-items',
                animation: 150,
                ghostClass: 'sortable-ghost',
                onEnd: saveNavItems
            });
        });
    }
});

// ── PATIENT QUICK-VIEW PANEL ──
function openPQV(quickViewUrl, label) {
    const panel    = document.getElementById('pqv-panel');
    const backdrop = document.getElementById('pqv-backdrop');
    const body     = document.getElementById('pqv-body');
    const footer   = document.getElementById('pqv-footer');
    document.getElementById('pqv-name').textContent = label;
    document.getElementById('pqv-avatar').textContent = label.charAt(0).toUpperCase();
    document.getElementById('pqv-meta').textContent = '';
    body.innerHTML = '<div style="text-align:center;padding:40px 0;color:var(--sc-muted)"><i class="fa-solid fa-spinner fa-spin" style="font-size:20px"></i></div>';
    footer.innerHTML = '';
    backdrop.style.display = 'block';
    setTimeout(() => { backdrop.style.opacity = '1'; }, 10);
    panel.style.transform = 'translateX(0)';
    document.body.style.overflow = 'hidden';

    fetch(quickViewUrl, { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } })
        .then(r => r.ok ? r.json() : null)
        .then(d => {
            if (!d) { body.innerHTML = '<div style="color:var(--sc-red);padding:20px">Failed to load patient data.</div>'; return; }

            const initials = d.name.split(' ').map(w => w[0]).join('').substring(0,2).toUpperCase();
            document.getElementById('pqv-avatar').textContent = initials;
            document.getElementById('pqv-name').textContent   = d.name;
            document.getElementById('pqv-meta').textContent   = d.jhc_id + ' · ' + d.age + ' · ' + (d.gender || '—');

            const fmt = n => '₹' + Number(n).toLocaleString('en-IN', {maximumFractionDigits:0});
            const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));

            let html = '';

            // Vitals row
            html += `<div style="display:flex;gap:8px;flex-wrap:wrap">`;
            if (d.blood_group) html += `<span style="background:rgba(232,76,76,.1);color:var(--sc-red);padding:4px 10px;border-radius:20px;font-size:12px;font-weight:600"><i class="fa-solid fa-droplet" style="margin-right:4px"></i>${esc(d.blood_group)}</span>`;
            if (d.phone)       html += `<span style="background:var(--sc-bg);padding:4px 10px;border-radius:20px;font-size:12px"><i class="fa-solid fa-phone" style="margin-right:4px;color:var(--sc-muted)"></i>${esc(d.phone)}</span>`;
            html += `</div>`;

            // Allergies
            if (d.allergies && d.allergies.length) {
                html += `<div style="background:rgba(232,76,76,.07);border:1px solid rgba(232,76,76,.2);border-radius:8px;padding:10px 14px">
                    <div style="font-size:11px;font-weight:700;color:var(--sc-red);margin-bottom:6px"><i class="fa-solid fa-triangle-exclamation" style="margin-right:4px"></i>ALLERGIES</div>
                    <div style="display:flex;flex-wrap:wrap;gap:6px">${d.allergies.map(a => `<span style="background:rgba(232,76,76,.12);color:#b52b2b;padding:2px 8px;border-radius:10px;font-size:12px">${esc(a)}</span>`).join('')}</div>
                </div>`;
            }

            // Current admission
            if (d.current_admission) {
                const adm = d.current_admission;
                html += `<a href="${esc(adm.url)}" style="display:block;background:rgba(10,180,180,.07);border:1px solid rgba(10,180,180,.25);border-radius:8px;padding:12px 14px;text-decoration:none;color:var(--sc-text)">
                    <div style="display:flex;align-items:center;gap:8px;margin-bottom:4px">
                        <i class="fa-solid fa-bed-pulse" style="color:var(--sc-teal)"></i>
                        <span style="font-size:12px;font-weight:700;color:var(--sc-teal)">CURRENTLY ADMITTED</span>
                        <span style="margin-left:auto;background:var(--sc-teal-glow);color:var(--sc-teal);padding:2px 8px;border-radius:10px;font-size:11px;font-weight:600">Day ${adm.days}</span>
                    </div>
                    <div style="font-size:12.5px;font-weight:600">${esc(adm.ward)}</div>
                    <div style="font-size:11px;color:var(--sc-muted)">Since ${esc(adm.date)} · Dr. ${esc(adm.doctor)}</div>
                </a>`;
            }

            // Last visit
            html += `<div style="background:var(--sc-bg);border-radius:8px;padding:12px 14px">
                <div style="font-size:11px;font-weight:700;color:var(--sc-muted);margin-bottom:6px;text-transform:uppercase;letter-spacing:.5px">Last Visit</div>`;
            if (d.last_encounter) {
                const enc = d.last_encounter;
                html += `<a href="${esc(enc.url)}" style="text-decoration:none;color:var(--sc-text)">
                    <div style="font-size:13px;font-weight:600">${esc(enc.date)}</div>
                    <div style="font-size:12px;color:var(--sc-muted)">Dr. ${esc(enc.doctor)}</div>
                    ${enc.reason && enc.reason !== '—' ? `<div style="font-size:11.5px;color:var(--sc-text);margin-top:4px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">${esc(enc.reason)}</div>` : ''}
                </a>`;
            } else {
                html += `<div style="font-size:12.5px;color:var(--sc-muted)">No visits on record</div>`;
            }
            html += `</div>`;

            // Outstanding balance
            html += `<div style="background:${d.outstanding_balance > 0 ? 'rgba(232,76,76,.07)' : 'rgba(39,196,122,.07)'};border:1px solid ${d.outstanding_balance > 0 ? 'rgba(232,76,76,.2)' : 'rgba(39,196,122,.2)'};border-radius:8px;padding:12px 14px;display:flex;align-items:center;gap:10px">
                <i class="fa-solid fa-file-invoice-dollar" style="color:${d.outstanding_balance > 0 ? 'var(--sc-red)' : 'var(--sc-green)'};font-size:18px"></i>
                <div>
                    <div style="font-size:12px;color:var(--sc-muted)">Outstanding Balance</div>
                    <div style="font-size:18px;font-weight:700;color:${d.outstanding_balance > 0 ? 'var(--sc-red)' : 'var(--sc-green)'}">${fmt(d.outstanding_balance)}</div>
                </div>
            </div>`;

            body.innerHTML = html;

            // Footer action buttons
            footer.innerHTML = `
                <a href="${esc(d.profile_url)}" class="btn btn-navy btn-sm" style="flex:1;justify-content:center"><i class="fa-solid fa-user"></i> Full Profile</a>
                <a href="${esc(d.new_bill_url)}" class="btn btn-outline btn-sm" title="New Bill"><i class="fa-solid fa-file-invoice-dollar"></i> Bill</a>
                <a href="${esc(d.opd_url)}" class="btn btn-outline btn-sm" title="OPD Token"><i class="fa-solid fa-list-ol"></i> OPD</a>
                <a href="${esc(d.admit_url)}" class="btn btn-outline btn-sm" title="Admit Patient"><i class="fa-solid fa-bed-pulse"></i> Admit</a>
            `;
        })
        .catch(() => { body.innerHTML = '<div style="color:var(--sc-red);padding:20px">Failed to load. Please try again.</div>'; });
}
function closePQV() {
    document.getElementById('pqv-panel').style.transform = 'translateX(100%)';
    const bd = document.getElementById('pqv-backdrop');
    bd.style.opacity = '0';
    setTimeout(() => { bd.style.display = 'none'; }, 250);
    document.body.style.overflow = '';
}

const CENSUS_PANEL_URL = '{{ route("ipd.census.panel") }}';
const CENSUS_FULL_URL  = '{{ route("ipd.census") }}';
function openCensus() {
    const panel    = document.getElementById('census-panel');
    const backdrop = document.getElementById('census-backdrop');
    const body     = document.getElementById('census-body');
    backdrop.style.display = 'block';
    requestAnimationFrame(() => { panel.style.transform = 'translateX(0)'; });
    document.body.style.overflow = 'hidden';
    body.innerHTML = '<div style="text-align:center;padding:40px 0;color:var(--sc-muted)"><i class="fa-solid fa-spinner fa-spin" style="font-size:20px"></i></div>';
    fetch(CENSUS_PANEL_URL)
        .then(r => r.text())
        .then(html => { body.innerHTML = html; })
        .catch(() => { body.innerHTML = '<div style="color:var(--sc-red);padding:20px">Failed to load census. <a href="' + CENSUS_FULL_URL + '" target="_blank">Open full page</a></div>'; });
}
function closeCensus() {
    document.getElementById('census-panel').style.transform = 'translateX(100%)';
    const bd = document.getElementById('census-backdrop');
    bd.style.opacity = '0';
    setTimeout(() => { bd.style.display = 'none'; bd.style.opacity = '1'; }, 260);
    document.body.style.overflow = '';
}

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') { closePQV(); closeCensus(); }
});

// Global search — patients, encounters, bills
const iconMap = { patient:'fa-user', encounter:'fa-stethoscope', bill:'fa-file-invoice-dollar' };
const colorMap = { patient:'var(--sc-teal)', encounter:'var(--sc-navy)', bill:'var(--sc-amber)' };
let searchTimer;
$('#global-search').on('input', function() {
    clearTimeout(searchTimer);
    const q = $(this).val().trim();
    if (q.length < 2) { $('#global-search-results').hide(); return; }
    searchTimer = setTimeout(() => {
        $.get('{{ route("global.search") }}', { q }, function(data) {
            const $r = $('#global-search-results');
            $r.empty();
            if (!data.length) {
                $r.html('<div style="padding:12px 16px;color:var(--sc-muted);font-size:13px">No results found for "' + q + '"</div>');
            } else {
                data.forEach(item => {
                    const icon  = iconMap[item.type]  || 'fa-circle';
                    const color = colorMap[item.type] || 'var(--sc-teal)';
                    if (item.type === 'patient' && item.quick_view_url) {
                        $r.append(`<div data-qv-url="${item.quick_view_url}" data-qv-label="${item.label}" style="display:flex;gap:12px;align-items:center;padding:10px 16px;cursor:pointer;border-bottom:1px solid var(--sc-border)" class="gs-patient-row">
                            <div style="width:32px;height:32px;border-radius:50%;background:rgba(0,0,0,.05);color:${color};display:flex;align-items:center;justify-content:center;font-size:13px;flex-shrink:0">
                                <i class="fa-solid ${icon}"></i>
                            </div>
                            <div style="flex:1;min-width:0">
                                <div style="font-weight:600;font-size:13px">${item.label}</div>
                                <div style="font-size:11px;color:var(--sc-muted)">${item.sub}</div>
                            </div>
                            <span style="font-size:10px;color:var(--sc-teal);white-space:nowrap"><i class="fa-solid fa-sidebar-flip"></i> Quick View</span>
                        </div>`);
                    } else {
                        $r.append(`<a href="${item.url}" style="display:flex;gap:12px;align-items:center;padding:10px 16px;text-decoration:none;color:var(--sc-text);border-bottom:1px solid var(--sc-border)">
                            <div style="width:32px;height:32px;border-radius:50%;background:rgba(0,0,0,.05);color:${color};display:flex;align-items:center;justify-content:center;font-size:13px;flex-shrink:0">
                                <i class="fa-solid ${icon}"></i>
                            </div>
                            <div>
                                <div style="font-weight:600;font-size:13px">${item.label}</div>
                                <div style="font-size:11px;color:var(--sc-muted)">${item.sub}</div>
                            </div>
                        </a>`);
                    }
                });
                // Patient row click → open quick view panel
                $r.find('.gs-patient-row').on('click', function() {
                    $('#global-search-results').hide();
                    $('#global-search').val('');
                    openPQV($(this).data('qv-url'), $(this).data('qv-label'));
                });
            }
            $r.show();
        });
    }, 300);
});
$(document).on('click', function(e) {
    if (!$(e.target).closest('.topbar-search').length) $('#global-search-results').hide();
});

// Tab system — panes are siblings of .tab-container, so search parentElement for them
document.querySelectorAll('.tab-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        const target = btn.dataset.tab;
        if (!target) return; // <a> nav links — let them navigate normally
        const container = btn.closest('.tab-container') || document;
        const paneRoot = container.parentElement || document;
        container.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
        paneRoot.querySelectorAll('.tab-pane').forEach(p => p.classList.remove('active'));
        btn.classList.add('active');
        const pane = paneRoot.querySelector('#tab-' + target) || document.getElementById('tab-' + target);
        if (pane) pane.classList.add('active');
    });
});

// Auto-dismiss alerts — but keep ones marked .alert-keep (e.g. one-time
// PINs the user must read/copy).
setTimeout(() => {
    document.querySelectorAll('.alert:not(.alert-keep):not(.alert-flash)').forEach(a => { a.style.opacity='0'; a.style.transition='opacity .5s'; setTimeout(()=>a.remove(), 500); });
}, 4000);
setTimeout(() => {
    document.querySelectorAll('.alert-flash:not(.alert-keep)').forEach(a => { a.style.opacity='0'; a.style.transition='opacity .5s'; setTimeout(()=>a.remove(), 500); });
}, 10000);

// Select2 init
$(document).ready(() => { $('.select2').select2({ theme: 'classic' }); });

// Sidebar scroll position persistence
(function() {
    const sc = document.querySelector('.sidebar-scroll');
    if (!sc) return;
    const saved = sessionStorage.getItem('sidebarScroll');
    if (saved) sc.scrollTop = parseInt(saved, 10);
    sc.addEventListener('scroll', () => sessionStorage.setItem('sidebarScroll', sc.scrollTop), { passive: true });
})();

// Sidebar collapse toggle
(function() {
    const sidebar   = document.getElementById('sidebar');
    const mainWrap  = document.getElementById('main-wrap');
    const toggleBtn = document.getElementById('sidebar-toggle');
    const backdrop  = document.getElementById('sidebar-backdrop');
    const isMobile  = () => window.innerWidth <= 768;

    function openMobileSidebar() {
        sidebar.classList.add('open');
        backdrop.classList.add('open');
        document.body.style.overflow = 'hidden';
    }
    function closeMobileSidebar() {
        sidebar.classList.remove('open');
        backdrop.classList.remove('open');
        document.body.style.overflow = '';
    }

    function applyCollapsed(collapsed) {
        if (isMobile()) {
            mainWrap.classList.remove('sidebar-collapsed');
        } else {
            sidebar.classList.toggle('collapsed', collapsed);
            mainWrap.classList.toggle('sidebar-collapsed', collapsed);
        }
    }

    const saved = localStorage.getItem('sidebarCollapsed') === 'true';
    applyCollapsed(saved);

    toggleBtn.addEventListener('click', function() {
        if (isMobile()) {
            sidebar.classList.contains('open') ? closeMobileSidebar() : openMobileSidebar();
        } else {
            const nowCollapsed = !sidebar.classList.contains('collapsed');
            localStorage.setItem('sidebarCollapsed', nowCollapsed);
            applyCollapsed(nowCollapsed);
        }
    });

    // Close sidebar on backdrop tap
    backdrop.addEventListener('click', closeMobileSidebar);

    // Close sidebar when a nav link is tapped on mobile
    sidebar.querySelectorAll('.nav-item').forEach(link => {
        link.addEventListener('click', function() {
            if (isMobile()) closeMobileSidebar();
        });
    });

    // Recalc on resize
    window.addEventListener('resize', function() {
        if (!isMobile()) {
            closeMobileSidebar();
            applyCollapsed(localStorage.getItem('sidebarCollapsed') === 'true');
        }
    });
})();

// ── RECENT PATIENTS (localStorage, cross-session) ──
// Max items is admin-configurable (Settings → Recent Patients in Sidebar).
var SC_RECENT_MAX = Math.max(1, Math.min(20, {{ (int) setting('recent_patients_count', 5) ?: 5 }}));
function escRec(s) {
    return String(s == null ? '' : s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
}
function scTrackPatient(id, name, jhcId, url) {
    try {
        let list = JSON.parse(localStorage.getItem('scRecentPatients_demo') || '[]');
        list = list.filter(x => x.id !== id);
        list.unshift({ id, name, jhcId, url });
        if (list.length > SC_RECENT_MAX) list.length = SC_RECENT_MAX;
        localStorage.setItem('scRecentPatients_demo', JSON.stringify(list));
        renderSidebarRecentPts();
    } catch(e) {}
}
function renderSidebarRecentPts() {
    try {
        let list = JSON.parse(localStorage.getItem('scRecentPatients_demo') || '[]');
        if (list.length > SC_RECENT_MAX) list = list.slice(0, SC_RECENT_MAX);
        const wrap = document.getElementById('nsw-recent-pts');
        const grp  = document.getElementById('ng-recent-pts');
        if (!wrap || !grp) return;
        if (!list.length) { wrap.style.display = 'none'; return; }
        wrap.style.display = '';
        grp.innerHTML = list.map(p => `<a href="${escRec(p.url)}" class="nav-item"><div class="nav-item-inner"><i class="fa-solid fa-user" style="color:rgba(255,255,255,.45)"></i><span style="flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">${escRec(p.name)}</span><span class="nav-badge" style="font-family:monospace;font-size:9px;background:rgba(255,255,255,.1);color:rgba(255,255,255,.5);font-weight:400">${escRec(p.jhcId)}</span><span class="rec-x" role="button" title="Remove from recent" onclick="return scRemoveRecentPt(event, ${p.id})"><i class="fa-solid fa-xmark"></i></span></div></a>`).join('');
    } catch(e) {}
}
function scRemoveRecentPt(ev, id) {
    ev.preventDefault();
    ev.stopPropagation();
    try {
        let list = JSON.parse(localStorage.getItem('scRecentPatients_demo') || '[]');
        list = list.filter(x => x.id !== id);
        localStorage.setItem('scRecentPatients_demo', JSON.stringify(list));
        renderSidebarRecentPts();
    } catch(e) {}
    return false;
}
</script>
@stack('scripts')
<script>
// ── DARK MODE ──
(function() {
    const theme = localStorage.getItem('scTheme') || 'light';
    if (theme === 'dark') {
        document.documentElement.setAttribute('data-theme', 'dark');
        const icon = document.getElementById('dark-mode-icon');
        if (icon) { icon.classList.remove('fa-moon'); icon.classList.add('fa-sun'); }
    }
})();
function toggleDarkMode() {
    const html = document.documentElement;
    const isDark = html.getAttribute('data-theme') === 'dark';
    const icon = document.getElementById('dark-mode-icon');
    if (isDark) {
        html.removeAttribute('data-theme');
        localStorage.setItem('scTheme', 'light');
        if (icon) { icon.classList.remove('fa-sun'); icon.classList.add('fa-moon'); }
    } else {
        html.setAttribute('data-theme', 'dark');
        localStorage.setItem('scTheme', 'dark');
        if (icon) { icon.classList.remove('fa-moon'); icon.classList.add('fa-sun'); }
    }
}

// ── KEYBOARD SHORTCUTS ──
document.addEventListener('keydown', function(e) {
    const tag = document.activeElement ? document.activeElement.tagName.toLowerCase() : '';
    const isTyping = ['input','textarea','select'].includes(tag) || document.activeElement.isContentEditable;

    // Escape closes any open modal
    if (e.key === 'Escape') {
        document.querySelectorAll('.modal-overlay.open').forEach(m => m.classList.remove('open'));
        return;
    }

    if (isTyping) return;

    const shortcuts = {
        'n': '/patients/create',
        'b': '/billing/create',
        'a': '/appointments/create',
        'd': '/',
        'p': '/patients',
        'e': '/appointments/create',
    };

    if (e.key === '?') {
        e.preventDefault();
        document.getElementById('shortcuts-modal').classList.toggle('open');
        return;
    }
    if (e.key === '/') {
        e.preventDefault();
        openCmdPalette();
        return;
    }
    if (shortcuts[e.key] && !e.ctrlKey && !e.metaKey && !e.altKey) {
        e.preventDefault();
        if (e.key === 'n' && typeof window.scNewEntryOverride === 'function') {
            window.scNewEntryOverride();
        } else if (e.key === 'e' && typeof window.scPatientVisitUrl === 'string') {
            window.location.href = window.scPatientVisitUrl;
        } else {
            window.location.href = shortcuts[e.key];
        }
    }
});

// Cmd/Ctrl+K — open command palette (works even when typing)
document.addEventListener('keydown', function(e) {
    if ((e.metaKey || e.ctrlKey) && (e.key === 'k' || e.key === 'K')) {
        e.preventDefault();
        openCmdPalette();
    }
});

// ── NOTIFICATION BELL ──
function toggleNotifPanel(e) {
    if (e) e.stopPropagation();
    const panel = document.getElementById('notif-panel');
    const opening = panel.style.display === 'none';
    panel.style.display = opening ? 'block' : 'none';
    if (opening) loadNotifications();
}
function loadNotifications(e) {
    if (e) e.stopPropagation();
    const body = document.getElementById('notif-body');
    body.innerHTML = '<div style="padding:24px;text-align:center;color:var(--sc-muted);font-size:12px"><i class="fa-solid fa-spinner fa-spin"></i> Loading…</div>';
    fetch('{{ route("notifications.feed") }}', { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }})
        .then(r => r.json()).then(data => {
            const items = data.items || [];
            const dot   = document.getElementById('notif-dot');
            const cnt   = document.getElementById('notif-count');
            if (items.length) { dot.style.display = ''; cnt.style.display = ''; cnt.textContent = items.length; }
            else              { dot.style.display = 'none'; cnt.style.display = 'none'; }
            if (!items.length) {
                body.innerHTML = '<div style="padding:30px;text-align:center;color:var(--sc-muted);font-size:12.5px"><i class="fa-solid fa-circle-check" style="color:var(--sc-green);font-size:18px;display:block;margin-bottom:6px"></i>All clear! Nothing needs attention.</div>';
                return;
            }
            body.innerHTML = items.map(it => `
                <a href="${it.url}" style="display:flex;gap:10px;padding:10px 14px;border-bottom:1px solid var(--sc-border);text-decoration:none;color:var(--sc-text);transition:background .15s" onmouseover="this.style.background='rgba(0,0,0,.03)'" onmouseout="this.style.background=''">
                    <div style="width:30px;height:30px;border-radius:8px;background:${it.color}1A;color:${it.color};display:flex;align-items:center;justify-content:center;font-size:12px;flex-shrink:0"><i class="fa-solid ${it.icon}"></i></div>
                    <div style="flex:1;min-width:0">
                        <div style="font-weight:600;font-size:12.5px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">${escapeNotif(it.label)}</div>
                        <div style="font-size:11px;color:var(--sc-muted)">${escapeNotif(it.sub)}${it.time ? ' · ' + escapeNotif(it.time) : ''}</div>
                    </div>
                </a>
            `).join('');
        })
        .catch(() => { body.innerHTML = '<div style="padding:24px;text-align:center;color:var(--sc-muted);font-size:12px">Failed to load.</div>'; });
}
function escapeNotif(s) {
    return String(s == null ? '' : s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
}

// Click outside the panel closes it
document.addEventListener('click', function(e) {
    const btn = document.getElementById('notif-btn');
    if (btn && !btn.contains(e.target)) {
        const panel = document.getElementById('notif-panel');
        if (panel) panel.style.display = 'none';
    }
});
// Initial badge fetch — show count on bell without opening
$(document).ready(function() {
    fetch('{{ route("notifications.feed") }}', { headers: { 'Accept': 'application/json' }})
        .then(r => r.ok ? r.json() : { items: [] })
        .then(data => {
            const n = (data.items || []).length;
            if (n > 0) {
                const dot = document.getElementById('notif-dot'); if (dot) dot.style.display = '';
                const cnt = document.getElementById('notif-count'); if (cnt) { cnt.style.display = ''; cnt.textContent = n; }
            }
        }).catch(() => {});

@if($licSvc->isEnabled('forum') && $u->can('messages.send'))
    // Messages unread count — sidebar badge + bell + light-green pop-up toast.
    // Polls every 20s (cheap COUNT + one latest row). The toast fires only when
    // the unread count RISES while you're on the page (new DM arrived), never on
    // the first load, and can be switched off via the bell's pop-up toggle.
    let lastDmCount = null;
    function refreshMsgUnread() {
        fetch('{{ route("messages.unread-count") }}', { headers: { 'Accept': 'application/json' }})
            .then(r => r.ok ? r.json() : { count: 0 })
            .then(d => {
                const n = parseInt(d.count || 0, 10);
                const b = document.getElementById('msg-unread-badge');
                if (b) { if (n > 0) { b.style.display = ''; b.textContent = n; } else { b.style.display = 'none'; } }
                // New message arrived since last poll → light-green bubble.
                if (lastDmCount !== null && n > lastDmCount && dmPopupsOn() && d.latest) {
                    showDmToast(d.latest);
                    loadNotifBadge();   // keep the bell count fresh too
                }
                lastDmCount = n;
            }).catch(() => {});
    }
    refreshMsgUnread();
    setInterval(refreshMsgUnread, 20000);
@endif
});

// ── DM POP-UP TOAST (light-green bubble) ──
function dmPopupsOn() { return localStorage.getItem('dmPopups') !== 'off'; }
function syncDmToggle() {
    const btn = document.getElementById('dm-popup-toggle');
    if (!btn) return;
    const on = dmPopupsOn();
    btn.style.opacity = on ? '1' : '.45';
    btn.title = on ? 'Message pop-ups: ON (click to mute)' : 'Message pop-ups: OFF (click to enable)';
}
function toggleDmPopups(e) {
    if (e) e.stopPropagation();
    localStorage.setItem('dmPopups', dmPopupsOn() ? 'off' : 'on');
    syncDmToggle();
}
function showDmToast(latest) {
    let wrap = document.getElementById('dm-toast-wrap');
    if (!wrap) {
        wrap = document.createElement('div');
        wrap.id = 'dm-toast-wrap';
        wrap.style.cssText = 'position:fixed;top:64px;right:18px;z-index:1200;display:flex;flex-direction:column;gap:8px;max-width:320px';
        document.body.appendChild(wrap);
    }
    if (!document.getElementById('dm-toast-style')) {
        const s = document.createElement('style'); s.id = 'dm-toast-style';
        s.textContent = '@keyframes dmToastIn{from{opacity:0;transform:translateX(18px)}to{opacity:1;transform:none}}';
        document.head.appendChild(s);
    }
    const t = document.createElement('div');
    t.style.cssText = 'background:#dcfce7;border:1px solid #86efac;color:#15803d;border-radius:10px;padding:10px 12px;box-shadow:0 6px 18px rgba(21,128,61,.18);font-size:12.5px;display:flex;gap:9px;align-items:flex-start;cursor:pointer;animation:dmToastIn .2s ease-out';
    t.onclick = () => { window.location = latest.url; };
    const from = escapeNotif(latest.from || 'Someone');
    const snip = escapeNotif(latest.snippet || '');
    t.innerHTML = `
        <i class="fa-solid fa-comment-dots" style="margin-top:1px"></i>
        <div style="flex:1;min-width:0">
            <div style="font-weight:700">New message from ${from}</div>
            ${snip ? `<div style="color:#166534;opacity:.85;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">${snip}</div>` : ''}
            <div style="margin-top:3px"><a href="javascript:void(0)" onclick="event.stopPropagation();localStorage.setItem('dmPopups','off');syncDmToggle();this.closest('#dm-toast-wrap').remove();" style="font-size:10.5px;color:#15803d;text-decoration:underline">Turn off pop-ups</a></div>
        </div>
        <button type="button" onclick="event.stopPropagation();this.parentElement.remove()" style="background:none;border:none;color:#15803d;cursor:pointer;font-size:13px;line-height:1">&times;</button>`;
    wrap.appendChild(t);
    setTimeout(() => { t.style.transition = 'opacity .3s'; t.style.opacity = '0'; setTimeout(() => t.remove(), 320); }, 8000);
}
// Re-fetch the bell badge (count of attention items, incl. unread DMs)
function loadNotifBadge() {
    fetch('{{ route("notifications.feed") }}', { headers: { 'Accept': 'application/json' }})
        .then(r => r.ok ? r.json() : { items: [] })
        .then(data => {
            const n = (data.items || []).length;
            const dot = document.getElementById('notif-dot');
            const cnt = document.getElementById('notif-count');
            if (n > 0) { if (dot) dot.style.display = ''; if (cnt) { cnt.style.display = ''; cnt.textContent = n; } }
            else       { if (dot) dot.style.display = 'none'; if (cnt) cnt.style.display = 'none'; }
        }).catch(() => {});
}
document.addEventListener('DOMContentLoaded', syncDmToggle);

// ── COMMAND PALETTE ──
const CP = {
    quickActions: [
        { label: 'New Patient',      sub: 'Register a new patient',  icon: 'fa-user-plus',     url: '{{ route("patients.create") }}',     keywords: 'new patient register' },
        { label: 'New Appointment',  sub: 'Schedule an appointment', icon: 'fa-calendar-plus', url: '{{ route("appointments.create") }}', keywords: 'new appointment booking' },
        { label: 'New Bill',         sub: 'Create a bill',           icon: 'fa-file-invoice-dollar', url: '{{ route("billing.create") }}',     keywords: 'new bill billing payment' },
        { label: 'OPD Token',        sub: 'Generate token',          icon: 'fa-list-ol',       url: '{{ route("opd.index") }}',           keywords: 'opd token queue' },
    ],
    nav: [
        { label: 'Dashboard',           icon: 'fa-gauge-high',      url: '{{ route("dashboard") }}',                 keywords: 'home dashboard' },
        { label: 'Patients',            icon: 'fa-users',           url: '{{ route("patients.index") }}',            keywords: 'patients list' },
        { label: 'Appointments',        icon: 'fa-calendar-check',  url: '{{ route("appointments.index") }}',        keywords: 'appointments' },
        { label: 'Encounter Templates', icon: 'fa-layer-group',     url: '{{ route("encounter-templates.index") }}', keywords: 'encounter templates' },
        { label: 'Pharmacy',            icon: 'fa-pills',           url: '{{ route("pharmacy.dashboard") }}',        keywords: 'pharmacy stock' },
        { label: 'Lab Order',           icon: 'fa-flask-vial',      url: '{{ route("lab.create-order") }}',          keywords: 'lab order tests panels' },
        { label: 'Reports',             icon: 'fa-chart-line',      url: '{{ route("reports.index") }}',             keywords: 'reports analytics revenue' },
        { label: 'Referrals',           icon: 'fa-user-doctor',     url: '{{ route("referrals.index") }}',           keywords: 'referrals' },
        { label: 'Certificates',        icon: 'fa-file-medical',    url: '{{ route("patients.index") }}',            keywords: 'certificates fitness medical' },
    ],
    activeIdx: 0,
    items: [],
    timer: null,
};

function openCmdPalette() {
    const m = document.getElementById('cmd-palette');
    m.classList.add('open');
    const inp = document.getElementById('cp-input');
    inp.value = '';
    setTimeout(() => inp.focus(), 50);
    cpRender();
}
function closeCmdPalette() {
    document.getElementById('cmd-palette').classList.remove('open');
}

function cpRecent() {
    try { return JSON.parse(sessionStorage.getItem('cpRecent_demo') || '[]'); } catch(e) { return []; }
}
function cpAddRecent(item) {
    const list = cpRecent().filter(x => x.url !== item.url);
    list.unshift(item);
    sessionStorage.setItem('cpRecent_demo', JSON.stringify(list.slice(0, 6)));
}

function cpRender(serverResults) {
    const r = document.getElementById('cp-results');
    const q = (document.getElementById('cp-input').value || '').trim().toLowerCase();
    const sections = [];

    if (!q) {
        try {
            const rp = JSON.parse(localStorage.getItem('scRecentPatients_demo') || '[]');
            if (rp.length) sections.push({ label: 'Recent Patients', items: rp.map(p => ({ label: p.name, sub: p.jhcId, icon: 'fa-user', url: p.url })) });
        } catch(e) {}
        const recent = cpRecent();
        if (recent.length) sections.push({ label: 'Recently visited', items: recent });
        sections.push({ label: 'Quick actions', items: CP.quickActions });
        sections.push({ label: 'Go to',         items: CP.nav });
    } else {
        // Server search results (patients/encounters/bills)
        if (serverResults && serverResults.length) {
            sections.push({
                label: 'From your records',
                items: serverResults.map(s => ({ label: s.label, sub: s.sub, icon: 'fa-' + (s.icon || '').replace(/^fa-/, ''), url: s.url }))
            });
        }
        // Local matches in actions/nav
        const tokens = q.split(/\s+/);
        const matches = (arr) => arr.filter(x => {
            const hay = (x.label + ' ' + (x.keywords || '') + ' ' + (x.sub || '')).toLowerCase();
            return tokens.every(t => hay.includes(t));
        });
        const qa = matches(CP.quickActions); if (qa.length) sections.push({ label: 'Quick actions', items: qa });
        const nv = matches(CP.nav);          if (nv.length) sections.push({ label: 'Go to',         items: nv });
    }

    if (!sections.length) {
        r.innerHTML = `<div style="padding:30px;text-align:center;color:var(--sc-muted);font-size:13px">No matches for "${escapeCp(q)}"</div>`;
        CP.items = [];
        return;
    }

    let html = '';
    let idx = 0;
    CP.items = [];
    sections.forEach(sec => {
        html += `<div class="cp-section-label">${escapeCp(sec.label)}</div>`;
        sec.items.forEach(it => {
            const itIdx = idx++;
            CP.items.push(it);
            html += `<a class="cp-item" data-idx="${itIdx}" href="${it.url}" data-label="${escapeCp(it.label)}" data-sub="${escapeCp(it.sub || '')}" data-icon="${escapeCp(it.icon || 'fa-circle')}">
                <div class="cp-icon"><i class="fa-solid ${escapeCp(it.icon || 'fa-circle')}"></i></div>
                <div style="flex:1;min-width:0"><div class="cp-label">${escapeCp(it.label)}</div>${it.sub ? `<div class="cp-sub">${escapeCp(it.sub)}</div>` : ''}</div>
            </a>`;
        });
    });
    r.innerHTML = html;
    CP.activeIdx = 0;
    cpHighlight();
    r.querySelectorAll('.cp-item').forEach(el => {
        el.addEventListener('click', function(e) {
            const it = { label: this.dataset.label, sub: this.dataset.sub, icon: this.dataset.icon, url: this.getAttribute('href') };
            cpAddRecent(it);
        });
    });
}

function cpHighlight() {
    const els = document.querySelectorAll('#cp-results .cp-item');
    els.forEach((el, i) => el.classList.toggle('active', i === CP.activeIdx));
    const active = els[CP.activeIdx];
    if (active) active.scrollIntoView({ block: 'nearest' });
}

function escapeCp(s) {
    return String(s == null ? '' : s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
}

document.addEventListener('keydown', function(e) {
    const m = document.getElementById('cmd-palette');
    if (!m || !m.classList.contains('open')) return;
    if (e.key === 'Escape') { closeCmdPalette(); return; }
    if (e.key === 'ArrowDown') { e.preventDefault(); if (CP.activeIdx < CP.items.length - 1) { CP.activeIdx++; cpHighlight(); } return; }
    if (e.key === 'ArrowUp')   { e.preventDefault(); if (CP.activeIdx > 0)                    { CP.activeIdx--; cpHighlight(); } return; }
    if (e.key === 'Enter')     {
        e.preventDefault();
        const it = CP.items[CP.activeIdx];
        if (it) { cpAddRecent(it); window.location.href = it.url; }
    }
});

document.addEventListener('input', function(e) {
    if (e.target.id !== 'cp-input') return;
    clearTimeout(CP.timer);
    const q = e.target.value.trim();
    if (q.length < 2) { cpRender(); return; }
    CP.timer = setTimeout(() => {
        $.get('{{ route("global.search") }}', { q }, function(data) { cpRender(data || []); });
    }, 250);
});

// Click outside the modal box closes the palette
document.getElementById('cmd-palette').addEventListener('click', function(e) {
    if (e.target.id === 'cmd-palette') closeCmdPalette();
});
</script>
</body>
</html>
