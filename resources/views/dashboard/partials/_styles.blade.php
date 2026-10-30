@once
@push('styles')
<style>
    /* ---------- Dashboard module (KFS Royal Egyptian) ---------- */
    .dash-greet { display:flex; justify-content:space-between; align-items:flex-end; gap:1rem; flex-wrap:wrap; margin-bottom:1.25rem; }
    .dash-greet .meta { display:flex; flex-wrap:wrap; gap:.5rem; }
    .dash-greet .meta span { font-size:.78rem; color:var(--text-soft); border:1px solid var(--line); background:var(--white-soft); border-radius:4px; padding:.2rem .6rem; }
    .dash-greet .meta i { color:var(--egypt-gold); margin-inline-end:.3rem; }
    .gov-stats a.gs-item { color:inherit; transition:background .4s var(--ease); }
    .gov-stats a.gs-item:hover { background:var(--papyrus); }
    .gs-hint { font-size:.72rem; color:var(--muted); }

    /* bar charts (single series, thin marks, recessive axis) */
    .hbar { display:grid; grid-template-columns: 110px 1fr 56px; align-items:center; gap:.75rem; padding:.45rem 0; }
    .hbar + .hbar { border-top:1px solid var(--line); }
    .hbar .lbl { font-size:.83rem; color:var(--text); display:flex; align-items:center; gap:.4rem; }
    .hbar .track { height:10px; background:var(--papyrus); border-radius:2px; overflow:hidden; }
    .hbar .fill { height:100%; border-radius:0 4px 4px 0; background:var(--egypt-green); }
    .hbar .fill.success { background:var(--success); } .hbar .fill.pending { background:var(--warning); }
    .hbar .fill.info { background:var(--info); } .hbar .fill.error { background:var(--danger); }
    .hbar .fill.gold { background:var(--egypt-gold); }
    .hbar .val { font-size:.82rem; color:var(--charcoal); font-weight:700; text-align:left; font-variant-numeric:tabular-nums; }

    .vchart { position:relative; display:flex; align-items:flex-end; gap:12px; height:180px; padding:0 .25rem; border-bottom:1px solid var(--line); }
    .vchart .gl { position:absolute; inset-inline:0; border-top:1px dashed rgba(18,63,53,.08); pointer-events:none; }
    .vchart .col { flex:1; display:flex; flex-direction:column; align-items:center; justify-content:flex-end; height:100%; position:relative; cursor:default; }
    .vchart .bar { width:min(34px,70%); background:var(--egypt-green); border-radius:4px 4px 0 0; min-height:2px; transition:background .3s var(--ease); }
    .vchart .col:hover .bar, .vchart .col:focus .bar { background:var(--egypt-green-3); }
    .vchart .tip { position:absolute; bottom:calc(100% + 4px); background:var(--egypt-green-deep); color:var(--white-soft); font-size:.72rem; padding:.2rem .5rem; border-radius:4px; white-space:nowrap; opacity:0; pointer-events:none; transition:opacity .2s; border-bottom:2px solid var(--egypt-gold); }
    .vchart .col:hover .tip, .vchart .col:focus .tip { opacity:1; }
    .vchart-x { display:flex; gap:12px; padding:.4rem .25rem 0; }
    .vchart-x span { flex:1; text-align:center; font-size:.72rem; color:var(--text-soft); }

    .dash-list { list-style:none; margin:0; padding:0; }
    .dash-list li { display:flex; gap:.75rem; align-items:flex-start; padding:.7rem 1.25rem; border-bottom:1px solid var(--line); }
    .dash-list li:last-child { border-bottom:0; }
    .dash-list .t { font-size:.85rem; color:var(--charcoal); }
    .dash-list .s { font-size:.74rem; color:var(--muted); }

    .kpi-ring { --p:0; width:120px; height:120px; border-radius:50%; display:grid; place-items:center;
        background: conic-gradient(var(--egypt-green) calc(var(--p)*1%), var(--sand) 0); }
    .kpi-ring > div { width:96px; height:96px; border-radius:50%; background:var(--white-soft); display:grid; place-items:center; text-align:center; }
    .kpi-ring strong { font-size:1.6rem; color:var(--egypt-green); font-weight:800; line-height:1; }
    .kpi-ring small { font-size:.68rem; color:var(--text-soft); }

    .exec-band { background:var(--egypt-green-deep); color:var(--white-soft); border-radius:8px; padding:1.5rem 1.75rem; position:relative; overflow:hidden; isolation:isolate; }
    .exec-band::before { content:""; position:absolute; inset:0; z-index:-1; background-image:url("{{ asset('assets/img/egypt-pattern.svg') }}"); background-size:160px; opacity:.06; }
    .exec-band h3 { color:var(--white-soft); font-size:clamp(1.2rem,2.4vw,1.6rem); margin:0; }
    .exec-band p { color:rgba(254,253,252,.72); margin:.3rem 0 0; }
    .exec-band .btn-kfs-gold { color:var(--white-soft); }
    .exec-band .btn-kfs-gold:hover { background:var(--egypt-gold); color:var(--egypt-green-deep); }
    .table-kfs td .sub { display:block; font-size:.72rem; color:var(--muted); }
</style>
@endpush
@endonce
