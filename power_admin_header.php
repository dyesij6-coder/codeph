<?php
if (defined('POWER_ADMIN_HEADER_RENDERED')) { return; } define('POWER_ADMIN_HEADER_RENDERED', true);
$current = basename($_SERVER['SCRIPT_NAME']);
if (!function_exists('isActive')) {
    function isActive($file){
        global $current;
        return $current === $file ? 'active' : '';
    }
}
$userName = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'PowerAdmin';
?>
<script>
// Ensure admin theme CSS is loaded even if page forgot the link
(function(){
    var hasTheme = document.querySelector('link[href*="admin_theme.css"]');
    if (!hasTheme) {
        var link = document.createElement('link');
        link.rel = 'stylesheet';
        link.href = 'assets/admin_theme.css';
        document.head.appendChild(link);
    }
})();
</script>
<div class="topbar">
	<div class="brand"><i class="fa-solid fa-shield-halved"></i> Power Admin</div>
	<div class="actions">
		<div class="search">
			<i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
			<input type="search" placeholder="Search…" aria-label="Search">
		</div>
		<button class="btn btn-ghost" id="themeToggle" title="Toggle theme"><i class="fa-solid fa-moon"></i></button>
		<div class="user" id="userMenu" style="position:relative">
			<button class="btn btn-ghost"><i class="fa-solid fa-user"></i> <?= htmlspecialchars($userName) ?></button>
			<div class="menu" role="menu" style="position:absolute;right:0;top:calc(100% + 8px);background:#fff;border:1px solid #e2e8f0;border-radius:10px;box-shadow:0 10px 24px rgba(0,0,0,.08);min-width:200px;display:none">
				<a href="admin_dashboard.php" style="display:block;padding:10px 12px;color:#0f172a;text-decoration:none"><i class="fa-solid fa-tachometer-alt"></i> Dashboard</a>
				<a href="power_admin_users.php" style="display:block;padding:10px 12px;color:#0f172a;text-decoration:none"><i class="fa-solid fa-users"></i> Users</a>
				<a href="login.php" style="display:block;padding:10px 12px;color:#0f172a;text-decoration:none"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
			</div>
		</div>
	</div>
</div>
<aside class="sidebar">
		<div class="sidebar-head">
			<div class="logo">NEUST Gabaldon</div>
			<button class="toggle" id="sidebarToggle" title="Collapse"><i class="fa-solid fa-bars"></i></button>
		</div>
		<a class="nav-link <?= isActive('admin_dashboard.php') ?>" href="admin_dashboard.php"><i class="fas fa-tachometer-alt"></i> <span class="nav-text">Dashboard</span></a>
		<a class="nav-link <?= isActive('power_admin_announcement.php') ?>" href="power_admin_announcement.php"><i class="fas fa-bullhorn"></i> <span class="nav-text">Announcements</span></a>
		<a class="nav-link <?= isActive('power_admin_users.php') ?>" href="power_admin_users.php"><i class="fas fa-users"></i> <span class="nav-text">Users</span></a>
		<a class="nav-link <?= isActive('power_admin_grievance_queue.php') ?>" href="power_admin_grievance_queue.php"><i class="fas fa-exclamation-triangle"></i> <span class="nav-text">Grievance Queue</span></a>
		<div class="nav-group" id="manageAdmin">
			<button class="nav-link nav-group-toggle" type="button"><span><i class="fas fa-user-shield"></i> <span class="nav-text">Manage Admin</span></span> <span class="caret"><i class="fa-solid fa-chevron-down"></i></span></button>
			<div class="subnav">
				<a class="nav-link sub <?= isActive('admin_list.php') ?>" href="admin_list.php"><i class="fas fa-list"></i> <span class="nav-text">Admin List</span></a>
				<a class="nav-link sub <?= isActive('add_admin.php') ?>" href="add_admin.php"><i class="fas fa-user-plus"></i> <span class="nav-text">Add Admin</span></a>
			</div>
		</div>
		<a class="nav-link <?= isActive('power_admin_reports.php') ?>" href="power_admin_reports.php"><i class="fas fa-chart-line"></i> <span class="nav-text">Reports</span></a>
		<a class="nav-link" href="login.php"><i class="fas fa-sign-out-alt"></i> <span class="nav-text">Logout</span></a>
	</aside>
<script>
(function(){
	const btn = document.getElementById('themeToggle');
	const prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
	const saved = localStorage.getItem('admin-theme');
	if (saved === 'dark' || (!saved && prefersDark)) document.body.classList.add('theme-dark');
	const setIcon = ()=>{ if(!btn) return; btn.innerHTML = document.body.classList.contains('theme-dark')? '<i class="fa-solid fa-sun"></i>' : '<i class="fa-solid fa-moon"></i>'; };
	if (btn) { setIcon(); btn.addEventListener('click', ()=>{ document.body.classList.toggle('theme-dark'); localStorage.setItem('admin-theme', document.body.classList.contains('theme-dark')?'dark':'light'); setIcon(); }); }
	const user = document.getElementById('userMenu');
	if (user) { user.querySelector('button').addEventListener('click', ()=> user.classList.toggle('active')); document.addEventListener('click', (e)=>{ if (!e.target.closest('#userMenu')) user.classList.remove('active'); }); }
    const sidebarBtn = document.getElementById('sidebarToggle');
    if (sidebarBtn) { sidebarBtn.addEventListener('click', function(){ document.body.classList.toggle('sidebar-collapsed'); }); }
    const group = document.getElementById('manageAdmin');
    if (group) {
        const toggle = group.querySelector('.nav-group-toggle');
        toggle.addEventListener('click', function(){ group.classList.toggle('open'); });
    }
})();
</script>