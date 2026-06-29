<?php // _head.php — include inside <head> for all extension pages ?>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<script src="https://cdn.tailwindcss.com"></script>
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
<style>
body{font-family:'DM Sans',sans-serif}
.mono{font-family:'DM Mono',monospace}
.sidebar-link{transition:all .15s ease}
.sidebar-link:hover{background:rgba(29,158,117,.07)}
.sidebar-link.active{background:rgba(29,158,117,.1);border-right:2px solid #1D9E75;color:#0F6E56}
.fade-in{animation:fadeIn .3s ease forwards}
@keyframes fadeIn{from{opacity:0;transform:translateY(6px)}to{opacity:1;transform:translateY(0)}}
.tag{display:inline-flex;align-items:center;font-size:11px;font-weight:500;padding:2px 8px;border-radius:20px}
.tag-disease{background:#FCEBEB;color:#A32D2D}
.tag-yield{background:#EAF3DE;color:#3B6D11}
.tag-soil{background:#FAEEDA;color:#854F0B}
.tag-water{background:#E6F1FB;color:#185FA5}
.tag-general{background:#F1EFE8;color:#5F5E5A}
.tag-pending{background:#FAEEDA;color:#854F0B}
.tag-approved,.tag-active{background:#E1F5EE;color:#0F6E56}
.tag-rejected{background:#FCEBEB;color:#A32D2D}
.tag-out,.tag-expired{background:#F1EFE8;color:#5F5E5A}
.scrollbar-hide::-webkit-scrollbar{display:none}
.field-label{font-size:12px;font-weight:500;color:#6b7280;margin-bottom:6px;display:block}
input[type="text"],input[type="search"],select,textarea{
    width:100%;border:1px solid #e5e7eb;border-radius:8px;
    padding:9px 12px;font-size:13px;font-family:'DM Sans',sans-serif;
    background:white;color:#374151;outline:none;transition:border-color .15s}
input[type="text"]:focus,input[type="search"]:focus,select:focus,textarea:focus{border-color:#1D9E75}
textarea{resize:vertical;line-height:1.7}
.btn-primary{display:inline-flex;align-items:center;gap:6px;padding:7px 16px;border-radius:8px;font-size:12px;font-weight:500;color:white;background:#1D9E75;border:none;cursor:pointer;transition:opacity .15s}
.btn-primary:hover{opacity:.9}
.btn-ghost{display:inline-flex;align-items:center;gap:6px;padding:7px 12px;border-radius:8px;font-size:12px;color:#6b7280;background:transparent;border:1px solid #e5e7eb;cursor:pointer;transition:background .15s}
.btn-ghost:hover{background:#f9fafb}
</style>
