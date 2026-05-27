<?php
// index.php - Dashboard COMSUP + Alarmas en BD + Chat con BD + Tema Sincronizado
session_start();
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");

try {
    if (file_exists(__DIR__ . '/config/database.php')) {
        require_once __DIR__ . '/config/database.php';
    }
} catch (Exception $e) {}

$USER_NAME = $_SESSION['user_fullname'] ?? $_SESSION['user_name'] ?? 'Usuario';
$USER_CARGO = $_SESSION['user_cargo'] ?? 'Operador';
$USER_INITIAL = strtoupper(mb_substr($USER_NAME, 0, 1, 'UTF-8'));
$USER_FOTO = $_SESSION['user_foto'] ?? '';
$USER_ID = (int)($_SESSION['user_id'] ?? 0);

if (empty($USER_FOTO) && $USER_ID && isset($pdo)) {
    try {
        $stmt = $pdo->prepare("SELECT foto FROM usuarios WHERE id = ?");
        $stmt->execute([$USER_ID]);
        $dbFoto = $stmt->fetchColumn();
        if ($dbFoto) { $USER_FOTO = $dbFoto; $_SESSION['user_foto'] = $dbFoto; }
    } catch (Exception $e) {}
}

$USERS_FOTOS = [];
if (isset($pdo)) {
    try {
        $stmt = $pdo->query("SELECT nombre_completo, foto FROM usuarios WHERE foto IS NOT NULL AND foto != ''");
        $USERS_FOTOS = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    } catch (Exception $e) {}
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <script src="assets/js/theme.js"></script>
  <script>
    // Inicializar tema y exponer toggleTheme global
    document.addEventListener('DOMContentLoaded', () => {
      if (typeof COMSUP_Theme !== 'undefined') COMSUP_Theme.init();
    });
    function toggleTheme() {
      if (typeof COMSUP_Theme !== 'undefined' && typeof COMSUP_Theme.toggle === 'function') {
        COMSUP_Theme.toggle();
      }
    }
  </script>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Inicio - COMSUP RANAV</title>
  <link rel="stylesheet" href="assets/css/styles.css">
  <style>
    :root { 
      --bg: #0f1115; --bg2: #1a1d24; --bg3: #232730; --bg4: #2d323d; 
      --fg: #e6e6e6; --fd: #b8b8d1; --mt: #8b949e; 
      --ac: #4a89c9; --acd: rgba(74,137,201,0.15); --pu: #9b87f5; 
      --ok: #3cb880; --dg: #d95a6b; --wn: #e6b450; --bd: rgba(255,255,255,0.08);
      --chat-me-bg: rgba(52, 211, 153, 0.15) !important;
      --chat-me-border: #34d399 !important;
      --chat-me-name: #34d399 !important;
      --chat-other-bg: var(--bg4);
      --chat-other-border: var(--mt);
    }
    :root.light { 
      --bg: #f8f9fa; --bg2: #ffffff; --bg3: #f1f3f5; --bg4: #e9ecef; 
      --fg: #212529; --fd: #495057; --mt: #6c757d; 
      --ac: #3a7bd5; --acd: rgba(58,123,213,0.1); --pu: #845ef7; 
      --ok: #20c997; --dg: #fa5252; --wn: #f59f00; --bd: rgba(0,0,0,0.08);
      --chat-me-bg: rgba(16, 185, 129, 0.12) !important;
      --chat-me-border: #10b981 !important;
      --chat-me-name: #059669 !important;
      --chat-other-bg: #e9ecef;
      --chat-other-border: #6c757d;
    }
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    html, body { height: 100%; overflow: hidden; font-family: system-ui, -apple-system, sans-serif; background: var(--bg); color: var(--fg); transition: background 0.25s ease, color 0.25s ease; }
    
    .ly { display: grid; grid-template-columns: 260px 1fr; height: 100vh; overflow: hidden; }
    .mn { display: flex; flex-direction: column; height: 100%; overflow: hidden; }
    .nav-sidebar { background: var(--bg2); border-right: 1px solid var(--bd); display: flex; flex-direction: column; height: 100%; overflow: hidden; }
    .nav-header { padding: 1.2rem; border-bottom: 1px solid var(--bd); text-align: center; background: linear-gradient(180deg, var(--bg2) 0%, var(--bg) 100%); }
    .nav-header h2 { margin: 0; font-size: 1.4rem; color: var(--ac); font-weight: 700; }
    .nav-header p { margin: 0.2rem 0 0; font-size: 0.65rem; color: var(--mt); text-transform: uppercase; font-weight: 600; }
    .nav-scroll { flex: 1; overflow-y: auto; padding: 0.5rem 0; }
    .nav-group { margin-bottom: 0.5rem; }
    .nav-group-title { font-size: 0.7rem; font-weight: 700; color: var(--mt); text-transform: uppercase; padding: 0.6rem 1rem 0.4rem; cursor: pointer; display: flex; align-items: center; gap: 0.4rem; }
    .nav-group-title.open { color: var(--fg); border-bottom: 1px solid var(--bd); }
    .nav-group-title::after { content: '▼'; font-size: 0.6rem; margin-left: auto; transition: transform 0.2s; }
    .nav-group-title.open::after { transform: rotate(180deg); }
    .nav-items { display: none; flex-direction: column; padding: 0 0.5rem; }
    .nav-items.open { display: flex; }
    .nav-link { display: flex; align-items: center; gap: 0.5rem; padding: 0.5rem 0.8rem; color: var(--fd); text-decoration: none; font-size: 0.82rem; transition: 0.2s; border-radius: 6px; }
    .nav-link:hover { background: var(--acd); color: var(--ac); padding-left: 1rem; }
    .nav-footer { padding: 1rem 1.2rem; border-top: 1px solid var(--bd); background: var(--bg3); }
    .nav-footer h4 { font-size: 0.6rem; color: var(--mt); text-transform: uppercase; margin-bottom: 0.4rem; }
    .nav-footer a { display: block; padding: 0.3rem 0; color: var(--fd); text-decoration: none; font-size: 0.78rem; }
    .nav-footer a:hover { color: var(--ac); }
    .tb { padding: 0.5rem 1.2rem; border-bottom: 1px solid var(--bd); display: flex; align-items: center; gap: 0.8rem; background: var(--bg2); flex-shrink: 0; }
    .tb-title { font-weight: 700; font-size: 1.1rem; color: var(--fg); }
    .tb-r { margin-left: auto; display: flex; gap: 0.6rem; align-items: center; flex-wrap: wrap; }
    .btn-icon { width: 34px; height: 34px; border-radius: 10px; border: 1px solid var(--bd); background: var(--bg3); color: var(--fg); display: flex; align-items: center; justify-content: center; cursor: pointer; font-size: 0.95rem; transition: 0.2s; position: relative; }
    .btn-icon:hover { background: var(--acd); color: var(--ac); border-color: var(--ac); }
    .mob { display: none; }
    .user-pill { display: flex; align-items: center; gap: 0.5rem; padding: 0.2rem 0.6rem; background: var(--bg3); border: 1px solid var(--bd); border-radius: 20px; }
    .user-info div:first-child { font-weight: 600; font-size: 0.8rem; }
    .user-info div:last-child { font-size: 0.55rem; color: var(--mt); text-transform: uppercase; }
    .mini-clocks { display: flex; gap: 0.5rem; margin-right: 0.5rem; }
    .mini-clock-card { display: flex; align-items: center; gap: 0.4rem; background: var(--bg4); border: 1px solid var(--bd); border-radius: 6px; padding: 0.15rem 0.4rem; position: relative; }
    .mini-dot { width: 6px; height: 6px; background: var(--ok); border-radius: 50%; display: none; animation: pulse 2s infinite; }
    .mini-clock-card.active-reminder .mini-dot { display: block; }
    .mini-label { font-weight: 700; font-size: 0.65rem; color: var(--mt); }
    .mini-time { font-family: monospace; font-size: 0.85rem; font-weight: 700; color: var(--fg); }
    .btn-max-clock { background: none; border: none; color: var(--mt); cursor: pointer; font-size: 0.8rem; padding: 0.1rem; margin-left: 0.2rem; }
    .btn-max-clock:hover { color: var(--ac); }
    @keyframes pulse { 0%,100%{opacity:1} 50%{opacity:0.4} }
    .main-content { flex: 1; padding: 1.2rem; display: flex; flex-direction: column; overflow: hidden; background: var(--bg); }
    .dashboard-layout { display: flex; gap: 1.5rem; flex: 1; overflow: hidden; }
    .dashboard-left { flex: 1; display: flex; flex-direction: column; overflow-y: auto; padding-right: 0.5rem; gap: 1rem; min-width: 0; min-height: 0; }
    .dashboard-right { width: 400px; flex-shrink: 0; display: flex !important; flex-direction: column !important; height: 100% !important; overflow: hidden !important; gap: 0.8rem; }
    .dash-panels { display: grid; grid-template-columns: 1fr 1fr; gap: 0.8rem; flex: 1 1 auto !important; min-height: 0 !important; overflow: hidden !important; }
    #panelInfo, #panelChat { background: var(--bg3); border: 1px solid var(--bd); border-radius: 10px; display: flex !important; flex-direction: column !important; height: 100% !important; min-height: 250px !important; overflow: hidden !important; }
    .dash-panel.collapsed { display: none; }
    .dash-panel-header { display: flex; justify-content: space-between; align-items: center; padding: 0.6rem 0.8rem; background: var(--bg2); border-bottom: 1px solid var(--bd); flex-shrink: 0; cursor: default; }
    .dash-panel-header span { font-weight: 600; font-size: 0.9rem; display: flex; align-items: center; gap: 0.4rem; }
    .dash-panel-toggle { background: var(--bg4); border: 1px solid var(--bd); color: var(--mt); cursor: pointer; font-size: 0.9rem; width: 28px; height: 28px; border-radius: 6px; display: flex; align-items: center; justify-content: center; }
    .dash-panel-toggle:hover { color: var(--ac); }
    #panelInfo > .dash-panel-body, #panelChat > .dash-panel-body { display: flex !important; flex-direction: column !important; flex: 1 1 0% !important; min-height: 0 !important; padding: 0.5rem !important; gap: 0.4rem !important; overflow: hidden !important; }
    #infoList, #chatBox, #openReportsList { flex: 1 1 auto !important; min-height: 0 !important; overflow-y: auto !important; padding-right: 0.2rem !important; display: flex !important; flex-direction: column !important; gap: 0.25rem !important; }
    #panelInfo .dash-input-row, #panelChat .dash-input-row { display: flex !important; gap: 0.3rem !important; align-items: center !important; flex: 0 0 auto !important; padding-top: 0.3rem !important; border-top: 1px solid var(--bd) !important; }
    .dash-input-row input { flex: 1; background: var(--bg2); border: 1px solid var(--bd); padding: 0.4rem; border-radius: 6px; color: var(--fg); outline: none; font-size: 0.8rem; }
    .info-item { background: var(--bg4); padding: 0.4rem 0.6rem; border-radius: 6px; border-left: 3px solid var(--ac); }
    .info-meta { font-size: 0.6rem; color: var(--mt); display: flex; justify-content: space-between; margin-bottom: 0.1rem; }
    
    /* 💬 CHAT ESTILOS GARANTIZADOS */
    .chat-msg { padding: 0.4rem 0.6rem; border-radius: 8px; max-width: 90%; position: relative; display: flex; flex-direction: column; gap: 0.2rem; }
    .chat-msg.me { background: var(--chat-me-bg) !important; align-self: flex-end; border-bottom-right-radius: 2px; border-left: 3px solid var(--chat-me-border) !important; }
    .chat-msg.other { background: var(--chat-other-bg); align-self: flex-start; border-bottom-left-radius: 2px; border-left: 3px solid var(--chat-other-border); }
    .chat-msg.me .chat-user { color: var(--chat-me-name) !important; }
    .chat-msg.other .chat-user { color: var(--ac); }
    .chat-header { display: flex; justify-content: space-between; align-items: center; gap: 0.3rem; }
    .chat-user { font-size: 0.6rem; font-weight: 600; display: flex; align-items: center; gap: 0.2rem; }
    .chat-user img { width: 18px; height: 18px; border-radius: 50%; object-fit: cover; margin-right: 4px; border: 1px solid var(--bd); }
    .chat-avatar-fallback { display: inline-flex; align-items: center; justify-content: center; width: 18px; height: 18px; border-radius: 50%; background: var(--ac); color: #fff; font-size: 0.6rem; font-weight: 700; margin-right: 4px; }
    .chat-time { font-size: 0.5rem; color: var(--mt); white-space: nowrap; }
    .chat-text { font-size: 0.85rem; word-break: break-word; line-height: 1.3; }
    .chat-attach-list { display: flex; flex-wrap: wrap; gap: 0.3rem; margin-top: 0.2rem; }
    .chat-attach-item { background: rgba(0,0,0,0.1); padding: 0.2rem 0.4rem; border-radius: 4px; font-size: 0.7rem; }
    
    .emoji-grid { position: absolute; bottom: 45px; left: 10px; background: var(--bg3); border: 1px solid var(--bd); border-radius: 8px; padding: 0.3rem; display: grid; grid-template-columns: repeat(8, 1fr); gap: 2px; z-index: 50; box-shadow: 0 4px 12px rgba(0,0,0,0.4); display: none; }
    .emoji-item { cursor: pointer; font-size: 1rem; padding: 0.15rem; text-align: center; border-radius: 4px; }
    .emoji-item:hover { background: var(--acd); }
    .chat-tools { display: flex; gap: 0.2rem; align-items: center; }
    .stats-row { display: grid; grid-template-columns: repeat(2, 1fr); gap: 0.5rem; flex-shrink: 0; }
    .report-panel { background: var(--bg3); border: 1px solid var(--bd); border-radius: 10px; display: flex !important; flex-direction: column !important; flex: 1 !important; overflow: hidden !important; min-height: 0 !important; }
    .panel-header { padding: 0.6rem 0.8rem; border-bottom: 1px solid var(--bd); font-weight: 700; font-size: 0.85rem; display: flex; align-items: center; gap: 0.4rem; background: var(--bg2); flex-shrink: 0 !important; }
    #openReportsList::-webkit-scrollbar { width: 10px; }
    #openReportsList::-webkit-scrollbar-track { background: var(--bg2); border-radius: 4px; }
    #openReportsList::-webkit-scrollbar-thumb { background: var(--ac); border-radius: 4px; border: 2px solid var(--bg3); }
    .report-item { padding: 0.5rem 0.7rem; border-bottom: 1px solid var(--bd); cursor: pointer; }
    .report-item:hover { background: var(--acd); }
    .report-num { font-weight: 600; font-size: 0.85rem; }
    .report-sys { color: var(--mt); font-size: 0.75rem; margin-top: 0.1rem; }
    .report-meta { display: flex; justify-content: space-between; font-size: 0.7rem; margin-top: 0.2rem; }
    .badge-afecta { color: var(--dg); font-weight: 600; }
    .badge-no-afecta { color: var(--ok); font-weight: 600; }
    .empty-panel { text-align: center; padding: 1rem; color: var(--mt); font-size: 0.8rem; background: var(--bg2); border-radius: 6px; }
    .stat-card { background: var(--bg3); border: 1px solid var(--bd); border-radius: 8px; padding: 0.6rem; text-align: center; }
    .stat-val { font-size: 1.3rem; font-weight: 700; line-height: 1.1; }
    .stat-lbl { font-size: 0.6rem; color: var(--mt); text-transform: uppercase; margin-top: 0.1rem; }
    .modal { position: fixed; inset: 0; background: rgba(0,0,0,0.7); backdrop-filter: blur(4px); z-index: 1000; display: none; align-items: center; justify-content: center; }
    .modal.show { display: flex; }
    .modal-box { background: var(--bg3); border: 1px solid var(--bd); border-radius: 12px; padding: 1.2rem; width: 90%; max-width: 450px; max-height: 85vh; overflow-y: auto; }
    .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; padding-bottom: 0.6rem; border-bottom: 1px solid var(--bd); }
    .modal-header h3 { margin: 0; font-size: 1rem; }
    .modal-close { background: none; border: none; color: var(--mt); font-size: 1.4rem; cursor: pointer; }
    .modal-row { display: flex; flex-direction: column; gap: 0.3rem; margin-bottom: 0.5rem; }
    .modal-row label { font-size: 0.7rem; color: var(--mt); font-weight: 600; text-transform: uppercase; cursor: pointer; }
    .modal-row input, .modal-row select { background: var(--bg2); border: 1px solid var(--bd); border-radius: 6px; padding: 0.5rem; color: var(--fg); outline: none; width: 100%; }
    #alarmModal .modal-box { max-height: 95vh; height: auto; display: flex; flex-direction: column; padding: 1.2rem; }
    #alarmListContent { flex: 1; overflow-y: auto; max-height: calc(95vh - 200px); min-height: 80px; margin-bottom: 1rem; }
    #alarmListContent::-webkit-scrollbar { width: 6px; }
    #alarmListContent::-webkit-scrollbar-track { background: var(--bg2); border-radius: 4px; }
    #alarmListContent::-webkit-scrollbar-thumb { background: var(--ac); border-radius: 4px; }
    .alarm-row { display: flex; align-items: center; justify-content: space-between; background: var(--bg2); padding: 0.8rem; border-radius: 8px; margin-bottom: 0.5rem; border: 1px solid var(--bd); }
    .alarm-row.disabled { opacity: 0.5; }
    .alarm-left { display: flex; flex-direction: column; gap: 0.3rem; cursor: pointer; }
    .alarm-time { font-family: monospace; font-size: 1.2rem; font-weight: 700; color: var(--fg); }
    .alarm-info { font-size: 0.7rem; color: var(--mt); display: flex; align-items: center; gap: 0.3rem; }
    .alarm-days { display: flex; gap: 0.2rem; margin-top: 0.2rem; }
    .alarm-day { width: 18px; height: 18px; border-radius: 50%; font-size: 0.6rem; display: flex; align-items: center; justify-content: center; background: var(--bg4); color: var(--mt); }
    .alarm-day.active { background: var(--ac); color: #fff; font-weight: 700; }
    .toggle-switch { position: relative; display: inline-block; width: 40px; height: 22px; flex-shrink: 0; margin-left: 10px; }
    .toggle-switch input { opacity: 0; width: 0; height: 0; }
    .toggle-slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: var(--bg4); transition: .3s; border-radius: 22px; }
    .toggle-slider:before { position: absolute; content: ""; height: 16px; width: 16px; left: 3px; bottom: 3px; background-color: white; transition: .3s; border-radius: 50%; }
    .toggle-switch input:checked + .toggle-slider { background-color: var(--ac); }
    .toggle-switch input:checked + .toggle-slider:before { transform: translateX(18px); }
    #clockFullscreen { background: #000; position: fixed; inset: 0; z-index: 2000; display: none; flex-direction: column; align-items: center; justify-content: center; cursor: pointer; }
    #clockFullscreen.show { display: flex; }
    .fs-label { font-family: system-ui, sans-serif; font-size: 1.2rem; color: #888; margin-top: 2rem; text-transform: uppercase; letter-spacing: 0.15rem; }
    .fs-time { font-family: monospace; font-size: 12vw; font-weight: 800; color: #fff; text-shadow: 0 0 40px rgba(74,137,201,0.4); line-height: 1.1; }
    .fs-time.secondary { font-size: 8vw; color: #aaa; text-shadow: 0 0 20px rgba(255,255,255,0.1); }
    .fs-date { font-family: system-ui, sans-serif; font-size: 2.5vw; color: #888; margin-top: 1rem; text-transform: uppercase; letter-spacing: 0.2rem; }
    .fs-close { position: absolute; top: 20px; right: 30px; color: #555; font-size: 1.5rem; cursor: pointer; background: rgba(255,255,255,0.1); padding: 10px 20px; border-radius: 30px; transition: 0.3s; }
    .fs-close:hover { background: rgba(255,255,255,0.2); color: #fff; }
    .alarm-alert { background: var(--bg3); border: 2px solid var(--ac); color: var(--fg); padding: 2.5rem; border-radius: 24px; text-align: center; animation: pulseBorder 2s infinite; min-width: 320px; }
    @keyframes pulseBorder { 0%{box-shadow:0 0 0 0 rgba(74,137,201,0.4)} 70%{box-shadow:0 0 0 15px rgba(74,137,201,0)} 100%{box-shadow:0 0 0 0 rgba(74,137,201,0)} }
    .alarm-alert h2 { font-family: monospace; font-size: 4rem; margin: 0.5rem 0; color: var(--fg); }
    .alarm-alert p { font-size: 1.3rem; color: var(--fd); margin-bottom: 1.5rem; }
    .alarm-btn { background: var(--dg); color: #fff; border: none; padding: 0.9rem 2.5rem; border-radius: 50px; font-weight: 700; font-size: 1.1rem; cursor: pointer; transition: 0.2s; }
    .alarm-btn:hover { background: #c0392b; transform: scale(1.05); }
    .alarm-esc-hint { font-size: 0.8rem; color: var(--mt); margin-top: 1rem; opacity: 0.7; }
    .toast { position: fixed; bottom: 1rem; right: 1rem; background: var(--bg3); border: 1px solid var(--ac); padding: 0.6rem 1rem; border-radius: 8px; color: var(--fg); display: none; z-index: 2000; box-shadow: 0 5px 15px rgba(0,0,0,0.3); }
    @media(max-width: 1100px) {
      .dashboard-layout { flex-direction: column; height: auto; overflow: visible; }
      .dashboard-right { width: 100%; overflow: visible; padding-right: 0; flex: none; }
      .main-content { overflow: auto; }
      .ly { grid-template-columns: 1fr; }
      .nav-sidebar { position: fixed; left: -280px; top: 0; bottom: 0; z-index: 200; width: 260px; }
      .nav-sidebar.open { left: 0; }
      .mob { display: flex !important; }
      .mini-clocks { display: none; }
    }
  </style>
</head>
<body>
<!-- ✅ CONTENEDOR GRID PRINCIPAL (LAYOUT) -->
<div class="ly" id="app" style="display:none">
  
  <!-- ✅ SIDEBAR REUTILIZABLE (Primer hijo del grid -> Se queda a la IZQUIERDA) -->
  <?php require_once __DIR__ . '/includes/sidebar.php'; ?>
  <?php renderSidebar(); ?>
  <!-- ✅ FIN SIDEBAR -->

  <!-- ✅ CONTENIDO PRINCIPAL (Segundo hijo del grid -> Ocupa el resto) -->
  <div class="mn">
    <header class="tb">
      <button class="btn-icon mob" onclick="toggleSidebar()">☰</button>
      <div class="tb-title">📊 Panel de Control</div>
      <div class="tb-r">
        <div class="mini-clocks">
          <div class="mini-clock-card" id="mc-cuba" onclick="openAlarmModal('cuba')"><span class="mini-dot" id="dot-cuba"></span><span class="mini-label">🇨🇺 Cuba</span><span class="mini-time" id="clock-cuba">--:--</span><button class="btn-max-clock" onclick="event.stopPropagation(); openFullscreenClock()">⛶</button></div>
          <div class="mini-clock-card" id="mc-utc" onclick="openAlarmModal('utc')"><span class="mini-dot" id="dot-utc"></span><span class="mini-label">🌐 UTC</span><span class="mini-time" id="clock-utc">--:--</span><button class="btn-max-clock" onclick="event.stopPropagation(); openFullscreenClock()">⛶</button></div>
        </div>
        <button class="btn-icon" onclick="openAlarmList()">🔔</button>
        <button class="btn-icon" onclick="exportBackup()">💾</button>
        <button class="btn-icon" onclick="triggerImport()">📂</button>
        <button class="btn-icon" onclick="toggleTheme()" data-theme-toggle title="Cambiar Tema">🌙</button>
        <button class="btn-icon" onclick="window.location.href='configuracion.php'">⚙️</button>
        <div class="user-pill">
          <div class="user-info"><div id="uName"><?= htmlspecialchars($USER_NAME) ?></div><div id="uRole"><?= htmlspecialchars($USER_CARGO) ?></div></div>
          <button onclick="window.location.href='logout.php'" style="background:none;border:none;cursor:pointer;color:var(--mt);font-size:1.1rem;">🚪</button>
        </div>
      </div>
    </header>
    
    <main class="main-content">
      <div class="dashboard-layout">
        <div class="dashboard-left">
          <h1 class="welcome" style="font-size:1.3rem; font-weight:700; margin:0 0 0.5rem; color:var(--fg);">Bienvenido, <span id="welcomeName"><?= htmlspecialchars($USER_NAME) ?></span></h1>
          <div class="dash-panels">
            <!-- Panel Info -->
            <div class="dash-panel" id="panelInfo">
              <div class="dash-panel-header"><span>📢 Informaciones</span><button class="dash-panel-toggle" onclick="togglePanel('panelInfo')">➖</button></div>
              <div class="dash-panel-body"><div id="infoList"></div><div class="dash-input-row"><input type="text" id="infoInput" placeholder="Escribir información..."><button class="btn-icon" onclick="addInfo()">📤</button></div></div>
            </div>
            <!-- Panel Chat -->
            <div class="dash-panel" id="panelChat">
              <div class="dash-panel-header"><span>💬 Chat</span><button class="dash-panel-toggle" onclick="togglePanel('panelChat')">➖</button></div>
              <div class="dash-panel-body" style="position:relative;">
                <div id="chatBox"></div>
                <div class="emoji-grid" id="emojiPicker"></div>
                <div class="dash-input-row">
                  <div class="chat-tools"><button class="btn-icon" onclick="toggleEmojiPicker()">😊</button><button class="btn-icon" onclick="document.getElementById('chatFile').click()">📎</button></div>
                  <input type="file" id="chatFile" style="display:none" multiple accept="image/*,.pdf,.txt,.doc,.docx" onchange="previewFiles(this)">
                  <input type="text" id="chatInput" placeholder="Escribir mensaje..."><button class="btn-icon" onclick="sendChat()">📤</button>
                </div>
                <div id="filePreview" style="font-size:0.7rem;color:var(--mt);margin-top:0.2rem;"></div>
              </div>
            </div>
          </div>
        </div>
        
        <div class="dashboard-right">
          <div class="stats-row">
            <div class="stat-card"><div class="stat-val" style="color:var(--ac)" id="statOpenToday">0</div><div class="stat-lbl">Abiertos Hoy</div></div>
            <div class="stat-card"><div class="stat-val" style="color:var(--wn)" id="statOpenTotal">0</div><div class="stat-lbl">Abiertos Total</div></div>
            <div class="stat-card"><div class="stat-val" style="color:var(--ok)" id="statClosedToday">0</div><div class="stat-lbl">Cerrados Hoy</div></div>
            <div class="stat-card"><div class="stat-val" style="color:var(--dg)" id="statAfecta">0</div><div class="stat-lbl">Afectan Servicio</div></div>
          </div>
          <div class="report-panel">
            <div class="panel-header">📋 Detalle de Reportes</div>
            <div id="openReportsList"><div class="empty-panel">⏳ Cargando...</div></div>
          </div>
        </div>
      </div>
    </main>
  </div>
  <!-- ✅ FIN CONTENIDO PRINCIPAL -->
</div>
<!-- ✅ FIN CONTENEDOR GRID -->

<!-- Modales (sin cambios estructurales) -->
<div id="clockFullscreen" onclick="closeFullscreenClock()"><div class="fs-close" onclick="closeFullscreenClock()">Salir ✕</div><div class="fs-label">🇨🇺 HORA CUBA</div><div class="fs-time" id="fsTimeCuba">00:00:00</div><div style="height: 2px; background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent); width: 150px; margin: 2rem auto;"></div><div class="fs-label">🌐 HORA UTC</div><div class="fs-time secondary" id="fsTimeUTC">00:00:00</div><div class="fs-date" id="fsDate">Domingo, 1 de Enero</div></div>
<div class="modal" id="alarmModal"><div class="modal-box" style="max-width:500px;"><div class="modal-header"><h3 id="alarmModalTitle">🔔 Gestión de Alarmas</h3><button class="modal-close" onclick="closeAlarmModal()">✕</button></div><div id="alarmListContent"></div><button class="btn-icon" style="width:100%; height:auto; padding:0.8rem; font-size:1rem; font-weight:600; border-radius:8px;" onclick="addNewAlarm()">➕ Nueva Alarma</button></div></div>
<div class="modal" id="editAlarmModal"><div class="modal-box"><div class="modal-header"><h3>⏰ Configurar Alarma</h3><button class="modal-close" onclick="closeEditAlarm()">✕</button></div><input type="hidden" id="editAlarmId"><div class="modal-row"><label>hora</label><input type="time" id="editAlarmTime" required></div><div class="modal-row"><label>etiqueta</label><input type="text" id="editAlarmLabel"></div><div class="modal-row"><label>Tono</label><select id="editAlarmTone"><option value="beep">🔔 Pitido</option><option value="digital">📟 Digital</option><option value="gentle">🔔 Suave</option><option value="urgent">🚨 Urgente</option></select></div><div class="modal-row"><label>Días activos</label><div style="display:flex; gap:0.5rem; flex-wrap:wrap; margin-top:0.3rem;" id="editAlarmDays"></div></div><div style="display:flex; justify-content:flex-end; gap:0.5rem; margin-top:1rem;"><button class="btn" onclick="closeEditAlarm()" style="padding:0.5rem 1rem; background:var(--bg2); border:1px solid var(--bd); color:var(--fg); border-radius:6px; cursor:pointer;">Cancelar</button><button class="btn" onclick="saveAlarm()" style="padding:0.5rem 1rem; background:var(--ac); border:none; color:#fff; border-radius:6px; cursor:pointer; font-weight:600;">Guardar</button></div></div></div>
<div class="modal" id="alarmRingingModal"><div class="alarm-alert"><div style="font-size:1rem; color:var(--ac); margin-bottom:0.5rem;">🔔 ALARMA ACTIVADA</div><h2 id="ringingTime">00:00</h2><p id="ringingLabel">Sin etiqueta</p><button class="alarm-btn" onclick="stopAlarm()">DETENER</button><div class="alarm-esc-hint">Presiona ESC para detener</div></div></div>
<div class="toast" id="toast"><span id="toastMsg"></span></div>

<script>
  const CURRENT_USER = '<?= addslashes($USER_NAME) ?>';
  const USER_AVATAR = '<?= $USER_INITIAL ?>';
  const USER_FOTO = '<?= addslashes($USER_FOTO) ?>';
  const USER_ID = <?= $USER_ID ?>;
  const USERS_FOTOS = <?= json_encode($USERS_FOTOS) ?>;
  let chatFiles = [], alarms = [];
  let audioCtx = null, alarmInterval = null, ringingAlarmId = null, alarmTitleInterval = null;

  async function logAction(eventType, module, message, details = null) {
    try { await fetch('api/log_action.php', { method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify({ eventType, module, message, details }) }); } catch(e) {}
  }

  async function syncChatToDB(messages) {
    try {
      const newMessages = messages.filter(m => !m.db_id);
      if (newMessages.length === 0) return;
      for (const msg of newMessages) {
        await fetch('api/chat_sync.php?action=save', {
          method: 'POST', headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ user_id: USER_ID, user_name: CURRENT_USER, message: msg.txt, attachments: msg.files || null })
        });
        msg.db_id = true;
      }
    } catch (e) { console.warn('⚠️ Sync BD:', e.message); }
  }

  async function loadChatFromDB() {
    try {
      const res = await fetch('api/chat_sync.php?action=read&limit=100');
      const json = await res.json();
      if (json.success && Array.isArray(json.data)) {
        const dbMsg = json.data.map(m => ({ u: m.user_name, txt: m.message, t: new Date(m.timestamp).getTime(), files: m.attachments, db_id: m.id }));
        const local = safeGet('comsup_chat', []);
        safeSet('comsup_chat', [...dbMsg, ...local.filter(l => !l.db_id)].slice(-100));
        return true;
      }
    } catch (e) { console.warn('⚠️ Load BD:', e.message); }
    return false;
  }

  function esc(s) { return String(s||'').replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m])); }
  function showToast(msg) { const t=document.getElementById('toast'), m=document.getElementById('toastMsg'); if(m)m.textContent=msg; if(t){t.style.display='block'; setTimeout(()=>t.style.display='none',2500);} }
  function timeAgo(ts) { const d=(Date.now()-ts)/1000; return d<60?'Ahora':d<3600?Math.floor(d/60)+'m':d<86400?Math.floor(d/3600)+'h':'Hoy'; }
  function safeGet(key, fallback) { try { const raw = localStorage.getItem(key); return raw ? JSON.parse(raw) : (fallback ?? null); } catch(e) { return fallback ?? null; } }
  function safeSet(key, data) { 
    try { 
      if(key==='reportes_comsup'){ const ex=localStorage.getItem(key); if(ex){ try{ const p=JSON.parse(ex); if(Array.isArray(p.rows)&&p.rows.length>5 && Array.isArray(data.rows)&&data.rows.length===0){ showToast('🛡️ Borrado bloqueado'); return false; } }catch(e){} localStorage.setItem(key+'_backup_'+Date.now(), ex); const b=Object.keys(localStorage).filter(k=>k.startsWith(key+'_backup_')).sort(); while(b.length>3)localStorage.removeItem(b.shift()); } } 
      localStorage.setItem(key, JSON.stringify(data)); return true; 
    } catch(e){ return false; } 
  }

  function toggleGroup(id) { const el=document.getElementById(id); if(!el)return; el.classList.toggle('open'); const t=el.previousElementSibling; if(t)t.classList.toggle('open'); safeSet('nav_'+id, el.classList.contains('open')); }
  function toggleSidebar() { document.querySelector('.nav-sidebar')?.classList.toggle('open'); }
  function togglePanel(id) { const p=document.getElementById(id), b=p?.querySelector('.dash-panel-toggle'); if(!p||!b)return; p.classList.toggle('collapsed'); b.textContent=p.classList.contains('collapsed')?'➕':'➖'; safeSet(id,{state:p.classList.contains('collapsed')?'collapsed':'expanded'}); }
  function triggerImport() { document.getElementById('restoreInput')?.click(); }
  function exportBackup() { const d={}; for(let i=0;i<localStorage.length;i++){const k=localStorage.key(i); if(k&&!k.includes('backup_'))d[k]=localStorage.getItem(k);} const a=document.createElement('a'); a.href=URL.createObjectURL(new Blob([JSON.stringify(d,null,2)])); a.download='COMSUP_Backup_'+new Date().toISOString().slice(0,10)+'.json'; a.click(); showToast('📥 Backup'); }
  function restoreBackup(inp) { const f=inp.files[0]; if(!f)return; inp.value=''; const r=new FileReader(); r.onload=e=>{ try{const d=JSON.parse(e.target.result); Object.keys(d).forEach(k=>localStorage.setItem(k,d[k])); showToast('✅ Restaurado'); setTimeout(()=>location.reload(),800);}catch{showToast('❌ Inválido');} }; r.readAsText(f); }

  function renderInfo() { const l=(safeGet('comsup_info',[])||[]).slice(-12).reverse(), c=document.getElementById('infoList'); if(!c)return; c.innerHTML=l.length?l.map(i=>`<div class="info-item"><div class="info-meta"><span>👤 ${esc(i.u)}</span><span>🕒 ${timeAgo(i.t)}</span></div><div class="info-txt">${esc(i.txt)}</div></div>`).join(''):'<div style="text-align:center;color:var(--mt);padding:1rem;font-size:0.8rem">Sin informaciones aún</div>'; }
  function addInfo() { const i=document.getElementById('infoInput'); if(!i?.value.trim())return; const d=safeGet('comsup_info',[]); d.push({u:CURRENT_USER, txt:i.value.trim(), t:Date.now()}); safeSet('comsup_info',d); i.value=''; renderInfo(); logAction('info', 'dashboard', 'Info publicada: '+i.value.trim().substring(0,50)); }

  function renderChat() { 
    const b = document.getElementById('chatBox'), c = safeGet('comsup_chat', []); 
    if (!b) return; 
    if (!c.length) { b.innerHTML = '<div style="text-align:center;color:var(--mt);padding:1rem;font-size:0.8rem">💬 Chat local<br><small>Escribe para comenzar</small></div>'; return; } 
    const currentUserLower = CURRENT_USER.trim().toLowerCase();
    b.innerHTML = c.slice(-50).map(m => { 
      const msgUser = (m.u || '').trim().toLowerCase();
      const isMe = msgUser === currentUserLower;
      let attach = ''; 
      if (m.files?.length) attach = `<div class="chat-attach-list">${m.files.map(f => {
        const isImg = f?.data?.startsWith('image');
        return isImg ? `<img src="${f.data}" onclick="window.open(this.src)" style="max-width:100px;cursor:pointer;border-radius:4px;">` : `<div class="chat-attach-item">📄 ${esc(f.name||'')}</div>`;
      }).join('')}</div>`;
      const senderFoto = isMe ? USER_FOTO : (USERS_FOTOS[m.u] || null);
      const fallbackAvatar = isMe ? USER_AVATAR : (m.u?.charAt(0) || '?');
      const avatarHtml = senderFoto 
        ? `<img src="${senderFoto}" style="width:18px;height:18px;border-radius:50%;object-fit:cover;margin-right:4px;border:1px solid var(--bd);" onerror="this.style.display='none';this.nextElementSibling.style.display='inline-flex'"><span class="chat-avatar-fallback" style="display:none">${fallbackAvatar}</span>`
        : `<span class="chat-avatar-fallback">${fallbackAvatar}</span>`;
      return `<div class="chat-msg ${isMe ? 'me' : 'other'}">
        <div class="chat-header"><span class="chat-user">${avatarHtml}${esc(isMe ? 'Tú' : m.u || 'Usuario')}</span><span class="chat-time">${new Date(m.t).toLocaleTimeString([],{hour:'2-digit',minute:'2-digit'})}</span></div>
        <div class="chat-text">${esc(m.txt || '')}</div>${attach}
      </div>`; 
    }).join(''); 
    b.scrollTop = b.scrollHeight; 
  }

  async function sendChat() { 
    const i = document.getElementById('chatInput'); 
    if (!i?.value.trim() && !chatFiles.length) return; 
    const c = safeGet('comsup_chat', []); 
    const txt = i.value.trim();
    const files = chatFiles.map(f => ({ name: f.name, data: f.data }));
    const newMsg = { u: CURRENT_USER, txt, t: Date.now(), files };
    c.push(newMsg); 
    safeSet('comsup_chat', c.slice(-100)); 
    i.value = ''; chatFiles = []; updateFilePreview(); renderChat();
    logAction('chat', 'dashboard', 'Mensaje: ' + txt.substring(0,50), { archivos: files.map(f => f.name) });
    await syncChatToDB([newMsg]);
  }
  
  function previewFiles(inp) { 
    if(!inp.files.length) return; chatFiles = []; let loaded = 0;
    Array.from(inp.files).forEach(f => { 
      if(f.size > 2*1024*1024) { showToast('⚠️ Máx 2MB'); return; } 
      const r = new FileReader(); r.onload = e => { chatFiles.push({ data: e.target.result, name: f.name }); loaded++; if(loaded === inp.files.length) { updateFilePreview(); showToast(`📎 ${inp.files.length}`); } }; r.readAsDataURL(f); 
    }); inp.value = ''; 
  }
  function updateFilePreview() { const p=document.getElementById('filePreview'); if(!p)return; p.innerHTML=chatFiles.length ? chatFiles.map(f=>`<span class="chat-attach-item">📎 ${esc(f.name)}</span>`).join(' ') : ''; }
  function toggleEmojiPicker() { document.getElementById('emojiPicker').style.display = document.getElementById('emojiPicker').style.display==='grid'?'none':'grid'; }
  function insertEmoji(e) { document.getElementById('chatInput').value+=e; document.getElementById('emojiPicker').style.display='none'; document.getElementById('chatInput').focus(); }

  // === ALARMAS CON BASE DE DATOS ===
  const DAYS = ['L','M','X','J','V','S','D']; 
  let currentAlarmTZ = 'cuba'; 

  // ✅ CARGAR ALARMAS DESDE BD
  async function loadAlarmsFromDB() {
    try {
      const res = await fetch('api/alarms.php?t=' + Date.now(), { credentials: 'include' });
      const json = await res.json();
      if (json.success && Array.isArray(json.data)) {
        alarms = json.data.map(a => ({
          ...a,
          time: a.hora.substring(0,5),
          label: a.etiqueta || '',
          tone: a.tono || 'beep',
          days: a.frecuencia === 'diaria' ? [0,1,2,3,4,5,6] : 
                a.frecuencia === 'semanal' ? [new Date().getDay()] : [new Date().getDay()],
          active: !!a.activa,
          tz: 'cuba'
        }));
        return true;
      }
    } catch (e) { console.warn('⚠️ Error cargando alarmas BD:', e.message); }
    return false;
  }

  // ✅ GUARDAR/ACTUALIZAR ALARMA EN BD
  async function saveAlarmToDB(alarm) {
    try {
      const method = alarm.id ? 'PUT' : 'POST';
      const url = alarm.id ? `api/alarms.php?id=${alarm.id}` : 'api/alarms.php';
      const payload = {
        id: alarm.id || null,
        fecha: alarm.fecha || new Date().toISOString().split('T')[0],
        hora: alarm.time + ':00',
        frecuencia: alarm.days?.length === 7 ? 'diaria' : 
                  alarm.days?.length === 1 ? 'once' : 'semanal',
        tono: alarm.tone || 'beep',
        etiqueta: alarm.label || '',
        activa: alarm.active ? 1 : 0
      };
      const res = await fetch(url, {
        method,
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload),
        credentials: 'include'
      });
      const json = await res.json();
      return json.success;
    } catch (e) {
      console.error('❌ Error guardando alarma:', e.message);
      return false;
    }
  }

  // ✅ ELIMINAR ALARMA DE BD
  async function deleteAlarmFromDB(id) {
    try {
      const res = await fetch(`api/alarms.php?id=${id}`, { 
        method: 'DELETE', 
        credentials: 'include' 
      });
      const json = await res.json();
      return json.success;
    } catch (e) {
      console.error('❌ Error eliminando alarma:', e.message);
      return false;
    }
  }

  function checkAlarms(){ 
    const n=new Date(); 
    const h=n.toLocaleTimeString('en-GB',{timeZone:'America/Havana',hour12:false,hour:'2-digit',minute:'2-digit'}); 
    const d=(n.getDay()+6)%7; 
    if(!alarms.length)return; 
    alarms.forEach(a=>{ 
      if(a.active && a.time===h && n.getSeconds()===0 && a.days.includes(d)) triggerAlarm(a); 
    }); 
  }

  function triggerAlarm(a){ 
    if(ringingAlarmId)return; 
    ringingAlarmId=a.time; 
    document.getElementById('ringingTime').textContent=a.time; 
    document.getElementById('ringingLabel').textContent=a.label||'Alarma'; 
    document.getElementById('alarmRingingModal').classList.add('show'); 
    playAlarmTone(a.tone); 
    try{window.focus()}catch(e){} 
    let ot=document.title; 
    alarmTitleInterval=setInterval(()=>{
      if(!ringingAlarmId){clearInterval(alarmTitleInterval);document.title=ot;return;} 
      document.title=document.title.includes('🔔')?ot:"🔔 ALARMA COMSUP"
    },800); 
    if(Notification.permission==="granted")new Notification("⏰",{body:`${a.time} - ${a.label}`}); 
    else if(Notification.permission!=="denied")Notification.requestPermission().then(p=>{if(p==="granted")new Notification("⏰",{body:`${a.time} - ${a.label}`})}); 
  }

  function stopAlarm(){ 
    ringingAlarmId=null; 
    document.getElementById('alarmRingingModal').classList.remove('show'); 
    if(alarmInterval)clearInterval(alarmInterval); 
    if(alarmTitleInterval)clearInterval(alarmTitleInterval); 
    if(audioCtx){audioCtx.close();audioCtx=null;} 
  }

  function playAlarmTone(type){ 
    if(audioCtx)audioCtx.close(); 
    try{ 
      audioCtx=new(window.AudioContext||window.webkitAudioContext)(); 
      if(audioCtx.state==='suspended')audioCtx.resume(); 
      const o=audioCtx.createOscillator(), g=audioCtx.createGain(); 
      o.connect(g); g.connect(audioCtx.destination); 
      o.type=type==='digital'?'square':type==='urgent'?'sawtooth':'sine'; 
      o.frequency.value=type==='urgent'?600:type==='digital'?800:440; 
      g.gain.value=0.15; 
      o.start(); 
      let on=true; 
      alarmInterval=setInterval(()=>{ 
        if(!ringingAlarmId){clearInterval(alarmInterval);audioCtx?.close();return;} 
        g.gain.value=on?0.15:0; 
        o.frequency.value=type==='urgent'?(on?800:400):o.frequency.value; 
        on=!on; 
      }, type==='urgent'?250:450); 
    }catch(e){} 
  }

  function openAlarmModal(tz){ currentAlarmTZ=tz; openAlarmList(); }

  async function openAlarmList(){ 
    const loaded = await loadAlarmsFromDB();
    if (!loaded) {
      // Fallback a localStorage si BD falla
      const legacy = safeGet('comsup_alarms', []);
      if (legacy.length) {
        // Migrar alarmas antiguas a BD
        for (const a of legacy) {
          await saveAlarmToDB({
            time: a.time,
            label: a.label || '',
            tone: a.tone || 'beep',
            days: a.days || [new Date().getDay()],
            active: a.active !== false,
            fecha: new Date().toISOString().split('T')[0]
          });
        }
        localStorage.removeItem('comsup_alarms');
        await loadAlarmsFromDB();
      }
    }
    renderAlarms(); 
    document.getElementById('alarmModalTitle').textContent=`🔔 Alarmas (${currentAlarmTZ.toUpperCase()})`; 
    document.getElementById('alarmModal').classList.add('show'); 
  }

  function closeAlarmModal(){ document.getElementById('alarmModal').classList.remove('show'); }

  function renderAlarms(){ 
    document.getElementById('alarmListContent').innerHTML=alarms.map((a,i)=>`
      <div class="alarm-row ${a.active?'':'disabled'}">
        <div class="alarm-left" onclick="editAlarm(${i})">
          <div class="alarm-time">${a.time}</div>
          <div class="alarm-info">${a.label||'Sin etiqueta'} • ${a.tone||'beep'}</div>
          <div class="alarm-days">${DAYS.map((d,j)=>`<div class="alarm-day ${a.days.includes(j)?'active':''}">${d}</div>`).join('')}</div>
        </div>
        <label class="toggle-switch">
          <input type="checkbox" ${a.active?'checked':''} onchange="toggleAlarm(${i})">
          <span class="toggle-slider"></span>
        </label>
        <button onclick="deleteAlarm(${i})" style="background:none;border:none;color:var(--dg);cursor:pointer;margin-left:0.5rem;font-size:1.1rem;">🗑️</button>
      </div>
    `).join(''); 
  }

  function addNewAlarm(){ 
    document.getElementById('editAlarmId').value='new'; 
    document.getElementById('editAlarmTime').value='07:00'; 
    document.getElementById('editAlarmLabel').value=''; 
    document.getElementById('editAlarmTone').value='beep'; 
    document.getElementById('editAlarmDays').innerHTML=DAYS.map(d=>`<div class="alarm-day active" onclick="this.classList.toggle('active')">${d}</div>`).join(''); 
    document.getElementById('editAlarmModal').classList.add('show'); 
    closeAlarmModal(); 
  }

  function editAlarm(i){ 
    const a=alarms[i]; 
    document.getElementById('editAlarmId').value=a.id||'new'; 
    document.getElementById('editAlarmTime').value=a.time; 
    document.getElementById('editAlarmLabel').value=a.label||''; 
    document.getElementById('editAlarmTone').value=a.tone||'beep'; 
    document.getElementById('editAlarmDays').innerHTML=DAYS.map((d,j)=>`<div class="alarm-day ${a.days.includes(j)?'active':''}" onclick="this.classList.toggle('active')">${d}</div>`).join(''); 
    document.getElementById('editAlarmModal').classList.add('show'); 
    closeAlarmModal(); 
  }

  function closeEditAlarm(){ document.getElementById('editAlarmModal').classList.remove('show'); }

  async function saveAlarm(){ 
    const id=document.getElementById('editAlarmId').value, 
          t=document.getElementById('editAlarmTime').value, 
          l=document.getElementById('editAlarmLabel').value, 
          tn=document.getElementById('editAlarmTone').value, 
          ds=Array.from(document.querySelectorAll('#editAlarmDays .alarm-day')).map((el,i)=>el.classList.contains('active')?i:-1).filter(i=>i!==-1), 
          na={
            id: id!=='new'?parseInt(id):null,
            time:t,
            label:l,
            tone:tn,
            days:ds,
            active:true,
            tz:currentAlarmTZ,
            fecha: new Date().toISOString().split('T')[0]
          }; 
    const saved = await saveAlarmToDB(na);
    if(saved) { 
      showToast('✅ Guardada en BD'); 
      closeEditAlarm(); 
      await loadAlarmsFromDB(); 
      renderAlarms(); 
    } else {
      showToast('❌ Error al guardar');
    }
  }

  async function deleteAlarm(i){ 
    const alarm = alarms[i];
    if (!alarm.id) return showToast('⚠️ Solo alarmas de BD pueden eliminarse');
    if(confirm('¿Eliminar esta alarma permanentemente?')){
      const deleted = await deleteAlarmFromDB(alarm.id);
      if(deleted){ 
        showToast('🗑️ Eliminada'); 
        await loadAlarmsFromDB(); 
        renderAlarms(); 
      } else {
        showToast('❌ Error al eliminar');
      }
    } 
  }

  async function toggleAlarm(i){ 
    alarms[i].active=!alarms[i].active; 
    const saved = await saveAlarmToDB(alarms[i]);
    if(!saved) {
      alarms[i].active = !alarms[i].active; // Revertir si falló
      showToast('⚠️ No se pudo actualizar');
    }
    renderAlarms();
  }

  function updateClocks(){ 
    const n=new Date(); 
    const tC=document.getElementById('clock-cuba'), tU=document.getElementById('clock-utc'); 
    if(tC)tC.textContent=n.toLocaleTimeString('en-GB',{timeZone:'America/Havana',hour12:false,hour:'2-digit',minute:'2-digit'}); 
    if(tU)tU.textContent=n.toLocaleTimeString('en-GB',{timeZone:'UTC',hour12:false,hour:'2-digit',minute:'2-digit'}); 
    const fsC=document.getElementById('fsTimeCuba'), fsU=document.getElementById('fsTimeUTC'), fsD=document.getElementById('fsDate'); 
    if(fsC)fsC.textContent=n.toLocaleTimeString('es-ES',{timeZone:'America/Havana',hour12:false}); 
    if(fsU)fsU.textContent=n.toLocaleTimeString('es-ES',{timeZone:'UTC',hour12:false}); 
    if(fsD)fsD.textContent=n.toLocaleDateString('es-ES',{weekday:'long',year:'numeric',month:'long',day:'numeric'}); 
    checkAlarms(); 
  }

  function openFullscreenClock(){ document.getElementById('clockFullscreen').classList.add('show'); }
  function closeFullscreenClock(){ document.getElementById('clockFullscreen').classList.remove('show'); }
  
  document.addEventListener('keydown', e => { 
    if(e.key==='Escape'){ 
      if(document.getElementById('alarmRingingModal').classList.contains('show'))stopAlarm(); 
      else if(document.getElementById('clockFullscreen').classList.contains('show'))closeFullscreenClock(); 
      else if(document.getElementById('editAlarmModal').classList.contains('show'))closeEditAlarm(); 
      else if(document.getElementById('alarmModal').classList.contains('show'))closeAlarmModal(); 
    } 
  });

  function isOpen(v){const s=(v||'').toUpperCase();return s!=='CERRADO'&&s!=='RESUELTO';}
  function isTodayLocal(ds){if(!ds)return false; const d=new Date(ds+'T00:00:00'),n=new Date();return d.getFullYear()===n.getFullYear()&&d.getMonth()===n.getMonth()&&d.getDate()===n.getDate();}
  
  function renderReportSection(title, items, icon, count) { 
    if(!items.length) return `<div style="padding:0.6rem;color:var(--mt);font-size:0.8rem;border-bottom:1px dashed var(--bd);">✅ Sin ${title.toLowerCase()}</div>`; 
    const bc = title.includes('Abiertos') ? 'var(--ac)' : title.includes('Cerrados') ? 'var(--ok)' : 'var(--dg)'; 
    return `<div style="margin-bottom:1.2rem;"><div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:0.5rem;"><div style="font-weight:700;font-size:0.95rem;color:var(--fg);display:flex;align-items:center;gap:0.4rem;">${icon} ${title}</div><span style="background:${bc};color:#fff;padding:0.15rem 0.55rem;border-radius:12px;font-size:0.75rem;font-weight:700;">${count}</span></div>${items.map(r=>{const af=(r[6]||'').toUpperCase()==='S';return `<div class="report-item" style="${af?'background:rgba(217,90,107,0.08);border-left:3px solid var(--dg);':''} padding:0.4rem 0.6rem; margin-bottom:0.3rem; border-bottom:none; border-radius:4px;" onclick="window.location.href='reportes.php'"><div style="display:flex;justify-content:space-between;align-items:center;"><div class="report-num">${esc(r[0]||'S/N')}</div><span class="report-date">🕒 ${r[1]?new Date(r[1]+'T00:00:00').toLocaleDateString('es-CU'):'---'} ${String(r[2]||'').substring(0,5)}</span></div><div class="report-sys">${esc(r[4]||'Sin subsistema')} • ${esc(r[5]||'Sin equipo')}</div><div class="report-meta">${af?'<span class="badge-afecta">⚠️ Afecta</span>':'<span class="badge-no-afecta">✅ No afecta</span>'}</div></div>`;}).join('')}</div>`; 
  }

  function loadOpenReports() { 
    const box=document.getElementById('openReportsList'); 
    if(!box)return; 
    let rows=[]; 
    try{
      const d=safeGet('reportes_comsup',null); 
      if(d&&d.rows)rows=d.rows; 
      else{
        const db=safeGet('comsup_db',{}); 
        if(db.reportes&&db.reportes.rows)rows=db.reportes.rows;
      }
    }catch(e){} 
    if(!rows.length){
      box.innerHTML='<div class="empty-panel">📭 Sin datos registrados<br><small style="opacity:0.7"><a href="#" onclick="triggerImport()" style="color:var(--ac)">Restaurar backup</a></small></div>';
      return;
    } 
    const sO=document.getElementById('statOpenToday'), sC=document.getElementById('statClosedToday'), sT=document.getElementById('statOpenTotal'), sA=document.getElementById('statAfecta'); 
    if(sO)sO.textContent=rows.filter(x=>isOpen(x[7])&&isTodayLocal(x[1])).length; 
    if(sC)sC.textContent=rows.filter(x=>!isOpen(x[7])&&isTodayLocal(x[11])).length; 
    if(sT)sT.textContent=rows.filter(x=>isOpen(x[7])).length; 
    if(sA)sA.textContent=rows.filter(x=>isOpen(x[7])&&(x[6]||'').toUpperCase()==='S').length; 
    box.innerHTML=renderReportSection('Abiertos Hoy', rows.filter(x=>isOpen(x[7])&&isTodayLocal(x[1])), '🔴', sO?sO.textContent:'0')+renderReportSection('Cerrados Hoy', rows.filter(x=>!isOpen(x[7])&&isTodayLocal(x[11])), '🟢', sC?sC.textContent:'0')+renderReportSection('Abiertos Total', rows.filter(x=>isOpen(x[7])), '📊', sT?sT.textContent:'0'); 
  }

  async function initApp() {
    console.log('🚀 Iniciando Dashboard COMSUP...');
    const appEl = document.getElementById('app'); if(appEl) appEl.style.display='grid';
    const welcomeEl = document.getElementById('welcomeName'); if(welcomeEl) welcomeEl.textContent = CURRENT_USER.split(' ')[0];
    
    // Restaurar estado de paneles
    ['panelInfo','panelChat'].forEach(id=>{
      const p=document.getElementById(id),b=p?.querySelector('.dash-panel-toggle'),s=safeGet(id,{}); 
      if(s?.state==='collapsed'){p.classList.add('collapsed'); if(b)b.textContent='➕';}
    });
    
       
    // Inicializar emoji picker
    const ep = document.getElementById('emojiPicker'); 
    if(ep) ep.innerHTML=['😀','😂','😍','🤔','👍','👎','🔥','✅','❌','⚠️','💡','📎','📊','🖍️','🚀','🌙','☕','💻','📱','🔒','📢','🕒','🇨🇺','🌍','❤️','👋','🙏','💪','🎯','📈'].map(e=>`<div class="emoji-item" onclick="insertEmoji('${e}')">${e}</div>`).join('');
    
    // Cargar alarmas desde BD (con migración automática)
    const alarmsLoaded = await loadAlarmsFromDB();
    if (!alarmsLoaded) {
      // Fallback: intentar cargar de localStorage si BD falla
      const legacy = safeGet('comsup_alarms', []);
      if (legacy.length) {
        // Migrar alarmas antiguas a BD (una sola vez)
        for (const a of legacy) {
          await saveAlarmToDB({
            time: a.time,
            label: a.label || '',
            tone: a.tone || 'beep',
            days: a.days || [new Date().getDay()],
            active: a.active !== false,
            fecha: new Date().toISOString().split('T')[0]
          });
        }
        localStorage.removeItem('comsup_alarms'); // Limpiar legacy
        await loadAlarmsFromDB();
      }
    }
    
    // Relojes y alarmas
    updateClocks(); 
    setInterval(updateClocks, 1000);
    
    // Renderizar componentes
    renderInfo();
    await loadChatFromDB(); 
    renderChat();
    loadOpenReports();
    renderAlarms(); // Renderizar alarmas después de cargarlas
    
    // Event listeners
    document.getElementById('infoInput')?.addEventListener('keydown',e=>{if(e.key==='Enter'&&!e.shiftKey){e.preventDefault();addInfo();}});
    document.getElementById('chatInput')?.addEventListener('keydown',e=>{if(e.key==='Enter'&&!e.shiftKey){e.preventDefault();sendChat();}});
    
    // Escuchar cambios en storage para sincronización entre pestañas
    window.addEventListener('storage',e=>{
      if(e.key==='comsup_info')renderInfo(); 
      if(e.key==='comsup_chat')renderChat(); 
      if(e.key==='reportes_comsup')loadOpenReports(); 
      // Para alarmas, recargar desde BD para mantener consistencia
      if(e.key===null || e.key==='alarm_sync') loadAlarmsFromDB().then(()=>renderAlarms());
    });
    
    // Auto-backup de reportes cada 30 segundos
    setInterval(()=>{
      const r=localStorage.getItem('reportes_comsup'); 
      if(r){
        localStorage.setItem('reportes_comsup_autobackup_'+Date.now(),r); 
        const b=Object.keys(localStorage).filter(k=>k.startsWith('reportes_comsup_autobackup_')).sort(); 
        while(b.length>2)localStorage.removeItem(b.shift());
      }
    },30000);
    
    console.log('✅ COMSUP Dashboard cargado con alarmas en BD');
  }
  
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initApp); else initApp();
</script>
</body>
</html>