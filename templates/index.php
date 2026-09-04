<?php /** standalone page - le JS construit le contenu */ ?>
<div class="section" id="memberadmin-app" data-lang="<?php echo htmlspecialchars((string)($_['lang'] ?? 'en')); ?>">
	<h2 id="memberadmin-app-title"></h2>
	<p id="memberadmin-app-hint"></p>
	<div id="memberadmin-groups"></div>
	<p id="memberadmin-status" class="hidden"></p>
</div>
