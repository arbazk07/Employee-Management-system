<?php /* Enterprise Dark Theme — shared CSS injected via include */ ?>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
<style>
:root {
  --sidebar-w:      250px;
  --bg-page:        #0F172A;
  --bg-card:        #1E293B;
  --text-base:      #F8FAFC;
  --text-muted:     #94A3B8;
  --text-soft:      #64748B;
  --border:         #334155;
  --border-hover:   #475569;
  --accent-blue:    #3B82F6;
  --accent-emerald: #10B981;
  --accent-amber:   #F59E0B;
  --accent-rose:    #EF4444;
  --accent-purple:  #8B5CF6;
  --radius-card: 10px;
  --radius-sm:    6px;
  --radius-pill:  99px;
  --shadow-sm:   0 1px 2px 0 rgba(0,0,0,.05);
  --shadow-card: 0 4px 6px -1px rgba(0,0,0,.1),0 2px 4px -1px rgba(0,0,0,.06);
}
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
body{font-family:'Inter',-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif;font-size:14px;background:var(--bg-page);color:var(--text-base);min-height:100vh;display:flex;overflow-x:hidden;-webkit-font-smoothing:antialiased;}
@keyframes fadeUp{from{opacity:0;transform:translateY(10px);}to{opacity:1;transform:translateY(0);}}
.fade-up{animation:fadeUp .4s ease-out both;}
.d1{animation-delay:.05s}.d2{animation-delay:.10s}.d3{animation-delay:.15s}
.d4{animation-delay:.20s}.d5{animation-delay:.25s}.d6{animation-delay:.30s}

/* SIDEBAR */
.sidebar{width:var(--sidebar-w);min-height:100vh;background:var(--bg-page);border-right:1px solid var(--border);position:fixed;top:0;left:0;display:flex;flex-direction:column;z-index:200;padding-bottom:1.5rem;}
.sidebar-brand{padding:1.5rem 1.25rem 1.25rem;}
.brand-logo{display:flex;align-items:center;gap:12px;}
.brand-icon{width:32px;height:32px;background:var(--accent-blue);border-radius:var(--radius-sm);display:flex;align-items:center;justify-content:center;flex-shrink:0;color:#fff;}
.brand-icon svg{fill:currentColor;width:18px;height:18px;}
.brand-text h1{font-size:15px;font-weight:600;color:var(--text-base);margin:0;}
.brand-text p{font-size:12px;color:var(--text-muted);margin:0;}
.sidebar-divider{height:1px;background:var(--border);margin:.5rem 1.25rem 1rem;}
.nav-section-label{padding:0 1.25rem .5rem;font-size:11px;font-weight:600;text-transform:uppercase;color:var(--text-soft);}
.sidebar-nav{list-style:none;padding:0;}
.nav-link-item{display:flex;align-items:center;gap:10px;padding:8px 12px;margin:2px 12px;border-radius:var(--radius-sm);color:var(--text-muted);text-decoration:none;font-size:13.5px;font-weight:500;transition:all .15s ease;}
.nav-link-item .ni{width:16px;height:16px;flex-shrink:0;fill:currentColor;}
.nav-link-item:hover{background:var(--bg-card);color:var(--text-base);}
.nav-link-item.active{background:rgba(59,130,246,.1);color:var(--accent-blue);}
.nav-link-item.active .ni{fill:var(--accent-blue);}
.sidebar-footer{margin-top:auto;padding:1rem 12px 0;border-top:1px solid var(--border);}

/* TOPBAR */
.topbar{position:sticky;top:0;z-index:100;height:64px;background:var(--bg-page);border-bottom:1px solid var(--border);padding:0 2rem;display:flex;align-items:center;justify-content:space-between;}
.topbar-title{font-size:16px;font-weight:600;color:var(--text-base);}
.topbar-sub{font-size:13px;color:var(--text-muted);margin-top:2px;}
.topbar-actions{display:flex;align-items:center;gap:16px;}
.icon-btn{width:34px;height:34px;border-radius:var(--radius-sm);border:1px solid var(--border);background:transparent;display:flex;align-items:center;justify-content:center;cursor:pointer;color:var(--text-muted);position:relative;transition:all .15s ease;}
.icon-btn:hover{background:var(--bg-card);color:var(--text-base);border-color:var(--border-hover);}
.notif-pip{position:absolute;top:6px;right:6px;width:6px;height:6px;background:var(--accent-rose);border-radius:50%;}
.user-pill{display:flex;align-items:center;gap:10px;padding:4px 12px 4px 4px;border-radius:var(--radius-pill);border:1px solid var(--border);background:transparent;cursor:pointer;transition:all .15s ease;}
.user-pill:hover{background:var(--bg-card);border-color:var(--border-hover);}
.avatar{width:26px;height:26px;border-radius:50%;background:var(--accent-blue);display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:600;color:#fff;flex-shrink:0;}
.u-name{font-size:13px;font-weight:500;color:var(--text-base);}
.u-role{font-size:11px;color:var(--text-muted);}

/* LAYOUT */
.main-wrap{margin-left:var(--sidebar-w);flex:1;display:flex;flex-direction:column;min-height:100vh;}
.page-body{padding:2rem;flex:1;max-width:1600px;margin:0 auto;width:100%;}
.page-header{margin-bottom:2rem;}
.page-header h2{font-size:22px;font-weight:600;color:var(--text-base);margin:0;}
.page-header p{font-size:14px;color:var(--text-muted);margin-top:6px;}

/* CARDS */
.stat-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:1rem;margin-bottom:2rem;}
.stat-card{background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius-card);padding:1.25rem;box-shadow:var(--shadow-sm);cursor:default;transition:border-color .15s ease;}
.stat-card:hover{border-color:var(--border-hover);}
.stat-top{display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:1rem;}
.stat-label{font-size:13px;font-weight:500;color:var(--text-muted);}
.stat-icon-wrap{width:32px;height:32px;border-radius:var(--radius-sm);display:flex;align-items:center;justify-content:center;}
.si-blue  {background:rgba(59,130,246,.1);color:var(--accent-blue);}
.si-green {background:rgba(16,185,129,.1);color:var(--accent-emerald);}
.si-amber {background:rgba(245,158,11,.1);color:var(--accent-amber);}
.si-purple{background:rgba(139,92,246,.1);color:var(--accent-purple);}
.stat-icon-wrap svg{width:16px;height:16px;fill:currentColor;}
.stat-value{font-size:28px;font-weight:600;color:var(--text-base);line-height:1;margin-bottom:8px;}
.stat-meta{font-size:13px;color:var(--text-soft);display:flex;align-items:center;gap:6px;}
.badge-up{background:rgba(16,185,129,.1);color:var(--accent-emerald);padding:2px 6px;border-radius:4px;font-size:12px;font-weight:500;}
.badge-warn{background:rgba(245,158,11,.1);color:var(--accent-amber);padding:2px 6px;border-radius:4px;font-size:12px;font-weight:500;}

/* TABLE CARD */
.table-card{background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius-card);box-shadow:var(--shadow-sm);margin-bottom:2rem;}
.table-card-header{padding:1.25rem;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;}
.table-card-header h4{font-size:16px;font-weight:600;color:var(--text-base);margin:0;}
.table-card-header p{font-size:13px;color:var(--text-muted);margin-top:4px;}
.table{background-color:transparent!important;--bs-table-bg:transparent;margin-bottom:0!important;border-collapse:collapse!important;}
.table th,.table td{color:#E2E8F0!important;border:1px solid var(--border)!important;background-color:transparent!important;padding:12px 16px!important;vertical-align:middle;}
.table thead th{color:#F8FAFC!important;font-weight:600;border-bottom:1px solid var(--border)!important;background-color:transparent!important;}
table.dataTable{border-collapse:collapse!important;margin-top:0!important;margin-bottom:0!important;}
table.dataTable thead th,table.dataTable thead td,table.dataTable tbody td{border-top:1px solid var(--border)!important;border-bottom:1px solid var(--border)!important;}
table.dataTable.no-footer{border-bottom:1px solid var(--border)!important;}
.dataTables_wrapper .dataTables_info,.dataTables_wrapper .dataTables_length,.dataTables_wrapper .dataTables_filter,.dataTables_wrapper .dataTables_length label,.dataTables_wrapper .dataTables_filter label{color:#94A3B8!important;font-size:13px;font-weight:400;}
.dataTables_wrapper .dataTables_length label,.dataTables_wrapper .dataTables_filter label{display:flex;align-items:center;gap:8px;}
.dataTables_wrapper .dataTables_filter input,.dataTables_wrapper .dataTables_length select{background-color:#0F172A!important;color:#F8FAFC!important;border:1px solid var(--border)!important;outline:none!important;box-shadow:none!important;padding:4px 10px;height:32px;border-radius:var(--radius-sm);font-size:13px;transition:border-color .15s;}
.dataTables_wrapper .dataTables_filter input:focus,.dataTables_wrapper .dataTables_length select:focus{border-color:var(--accent-blue)!important;}
.dataTables_wrapper .dataTables_paginate{padding-top:0!important;}
.page-item .page-link{background-color:#1E293B!important;color:#F8FAFC!important;border:1px solid var(--border)!important;font-size:13px;box-shadow:none!important;padding:5px 12px;transition:all .15s ease;}
.page-item:not(.active):not(.disabled) .page-link:hover{background-color:#0F172A!important;border-color:var(--border-hover)!important;}
.page-item.active .page-link{background-color:var(--accent-blue)!important;border-color:var(--accent-blue)!important;color:#fff!important;font-weight:600!important;}
.page-item.disabled .page-link{background-color:#1E293B!important;color:#64748B!important;}
.table tbody tr{transition:background-color .15s ease;}
.table tbody tr:hover td{background-color:#0F172A!important;}
.emp-cell{display:flex;align-items:center;gap:12px;font-weight:500;}
.emp-av{width:28px;height:28px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:600;flex-shrink:0;}
.av-blue {background:rgba(59,130,246,.15);color:var(--accent-blue);}
.av-green{background:rgba(16,185,129,.15);color:var(--accent-emerald);}
.av-amber{background:rgba(245,158,11,.15);color:var(--accent-amber);}
.av-rose {background:rgba(239,68,68,.15);color:var(--accent-rose);}
.av-purp {background:rgba(139,92,246,.15);color:var(--accent-purple);}
.dept-pill{display:inline-block;padding:2px 8px;border-radius:4px;font-size:12px;font-weight:500;background:var(--bg-page);border:1px solid var(--border);color:var(--text-muted);}
.action-btn{padding:4px 10px;border-radius:4px;border:1px solid var(--border);font-size:12px;font-weight:500;cursor:pointer;transition:all .15s ease;background:transparent;color:var(--text-muted);}
.action-btn:hover{background:var(--bg-page);color:var(--text-base);border-color:var(--border-hover);}
.btn-danger-action{color:var(--accent-rose)!important;}
.btn-danger-action:hover{border-color:var(--accent-rose)!important;background:rgba(239,68,68,.08)!important;}
.btn-primary-action{color:var(--accent-blue)!important;}
.btn-primary-action:hover{border-color:var(--accent-blue)!important;background:rgba(59,130,246,.08)!important;}
.dt-toolbar{padding:1rem 1.25rem;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;}
.dt-bottom{padding:1rem 1.25rem;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;}

/* FORM ELEMENTS */
.form-label{color:var(--text-muted);font-size:13px;font-weight:500;margin-bottom:6px;}
.form-control,.form-select{background:var(--bg-page)!important;border:1px solid var(--border)!important;color:var(--text-base)!important;border-radius:var(--radius-sm)!important;font-size:14px;padding:8px 12px;transition:border-color .15s;}
.form-control:focus,.form-select:focus{border-color:var(--accent-blue)!important;box-shadow:0 0 0 3px rgba(59,130,246,.1)!important;outline:none;}
.form-control::placeholder{color:var(--text-soft);}
.form-select option{background:var(--bg-card);color:var(--text-base);}
.btn-primary-ems{background:var(--accent-blue);color:#fff;border:none;border-radius:var(--radius-sm);padding:8px 18px;font-size:14px;font-weight:500;cursor:pointer;transition:background .15s;}
.btn-primary-ems:hover{background:#2563EB;}
.btn-ghost-ems{background:transparent;color:var(--text-muted);border:1px solid var(--border);border-radius:var(--radius-sm);padding:8px 18px;font-size:14px;font-weight:500;cursor:pointer;transition:all .15s;}
.btn-ghost-ems:hover{background:var(--bg-card);color:var(--text-base);}

/* TOOLTIPS */
.tooltip-inner{background-color:#0F172A;border:1px solid var(--border);color:var(--text-base);font-size:12px;padding:6px 10px;}
.tooltip.bs-tooltip-top .tooltip-arrow::before{border-top-color:var(--border);}

/* SWEETALERT2 */
.swal2-popup{border-radius:8px!important;font-family:'Inter',sans-serif!important;background:var(--bg-card)!important;border:1px solid var(--border)!important;color:var(--text-base)!important;padding:1.5rem!important;box-shadow:0 10px 25px -5px rgba(0,0,0,.3)!important;}
.swal2-title{color:var(--text-base)!important;font-size:18px!important;font-weight:600!important;}
.swal2-html-container{color:var(--text-muted)!important;font-size:14px!important;margin:1em 0!important;}
.swal2-actions{margin-top:1.5em!important;gap:8px!important;}
.swal2-confirm{background:var(--accent-blue)!important;color:#fff!important;border-radius:6px!important;font-weight:500!important;padding:8px 16px!important;font-size:14px!important;box-shadow:none!important;}
.swal2-confirm:hover{background:#2563EB!important;}
.swal2-cancel{border-radius:6px!important;background:transparent!important;font-weight:500!important;color:var(--text-base)!important;border:1px solid var(--border)!important;padding:8px 16px!important;font-size:14px!important;box-shadow:none!important;}
.swal2-cancel:hover{background:var(--bg-page)!important;}

/* SECTION */
.section-heading{display:flex;align-items:center;justify-content:space-between;margin-bottom:1rem;}
.section-heading h4{font-size:16px;font-weight:600;color:var(--text-base);margin:0;}
.view-link{font-size:13px;font-weight:500;color:var(--text-muted);text-decoration:none;padding:4px 8px;border-radius:4px;transition:all .15s;}
.view-link:hover{background:var(--bg-card);color:var(--text-base);border:1px solid var(--border);padding:3px 7px;}

/* ALERT/FLASH */
.flash-success{background:rgba(16,185,129,.1);border:1px solid rgba(16,185,129,.3);color:var(--accent-emerald);border-radius:var(--radius-sm);padding:10px 16px;margin-bottom:1.5rem;font-size:13px;}
.flash-error  {background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.3);color:var(--accent-rose);border-radius:var(--radius-sm);padding:10px 16px;margin-bottom:1.5rem;font-size:13px;}

@media(max-width:1200px){
  .stat-grid,.charts-row,.projects-grid{grid-template-columns:repeat(2,1fr);}
  .split-row{grid-template-columns:1fr;}
}
@media(max-width:768px){
  .sidebar{transform:translateX(-100%);}
  .main-wrap{margin-left:0;}
}
</style>
