(function () {
	'use strict';
	if (!window.OC || !OC.currentUser) { return; }

	// Langue : preference Nextcloud de l'utilisateur, fournie en data-lang par le serveur.
	var appLang = 'en';
	var STR = {
		fr: {
			'Member management': 'Gestion des membres',
			'You can add or remove members in the groups assigned to you. You cannot create, edit or delete accounts.': 'Vous pouvez ajouter ou retirer des membres dans les groupes qui vous ont été attribués. Vous ne pouvez ni créer, ni modifier, ni supprimer des comptes.',
			'No group is assigned to you. Contact the administrator.': 'Aucun groupe ne vous est attribué. Contactez l’administrateur.',
			'Type to search for accounts…': 'Tapez pour rechercher des comptes…',
			'Search an internal/external account…': 'Rechercher un compte (interne/externe)…',
			'No account found.': 'Aucun compte trouvé.',
			'Add': 'Ajouter',
			'Remove': 'Retirer',
			'(already member)': '(déjà membre)',
			'(you)': '(vous)',
			'Member added to {group}.': 'Membre ajouté à {group}.',
			'Member removed from {group}.': 'Membre retiré de {group}.',
			'Error removing the member.': 'Erreur lors du retrait du membre.',
			'Error: user not found or not allowed.': 'Erreur : utilisateur inexistant ou non autorisé.',
			'You cannot remove yourself from a group.': 'Vous ne pouvez pas vous retirer vous-même d\'un groupe.'
		}
	};
	function L(k, vars) {
		var txt = (appLang === 'fr' && STR.fr[k]) ? STR.fr[k] : k;
		if (vars) {
			txt = txt.replace(/\{(\w+)\}/g, function (m, key) { return (vars[key] !== undefined) ? vars[key] : m; });
		}
		return txt;
	}

	var root = null;
	var statusEl = null;
	var hintEl = null;
	var titleEl = null;
	var membersByGid = {};
	// Les pretty URLs (/apps/...) ne sont pas resolues pour les apps custom :
	// on utilise explicitement /index.php/apps/memberadmin/...
	var BASE = (window.OC.webroot || '') + '/index.php/apps/memberadmin';
	var openDD = null;

	document.addEventListener('mousedown', function (ev) {
		if (openDD && !openDD.contains(ev.target)) { closeDD(); }
	});
	window.addEventListener('scroll', function () { if (openDD) { closeDD(); } }, true);
	window.addEventListener('resize', function () { if (openDD) { closeDD(); } });

	function showStatus(msg, ok) {
		if (!statusEl) { return; }
		statusEl.textContent = msg;
		statusEl.className = ok ? 'memberadmin-ok' : 'memberadmin-err';
	}

	function api(path, opts) {
		opts = opts || {};
		opts.headers = opts.headers || {};
		opts.headers.requesttoken = OC.requestToken;
		opts.headers['OCS-APIRequest'] = 'true';
		return fetch(BASE + path, opts).then(function (r) {
			return r.json().catch(function () { return {}; });
		});
	}

	function closeDD() {
		if (openDD) { openDD.style.display = 'none'; openDD = null; }
	}

	function removeMember(gid, userId) {
		api('/groups/' + encodeURIComponent(gid) + '/members/' + encodeURIComponent(userId), { method: 'DELETE' })
			.then(function (data) {
				if (data && data.ok) { showStatus(L('Member removed from {group}.', { group: gid }), true); load(); }
				else { showStatus(L('Error removing the member.'), false); }
			});
	}

	function addMemberName(gid, name) {
		name = (name || '').trim();
		if (!name) { return; }
		var body = new URLSearchParams();
		body.append('userId', name);
		api('/groups/' + encodeURIComponent(gid) + '/members', {
			method: 'POST',
			body: body,
			headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
		}).then(function (data) {
			if (data && data.ok) { showStatus(L('Member added to {group}.', { group: gid }), true); load(); }
			else if (data && data.error === 'self-removal') { showStatus(L('You cannot remove yourself from a group.'), false); }
			else { showStatus(L('Error: user not found or not allowed.'), false); }
		});
	}

	function debounce(fn, ms) {
		var t;
		return function () { var a = arguments, ctx = this; clearTimeout(t); t = setTimeout(function () { fn.apply(ctx, a); }, ms); };
	}

	function openDropdown(gid, input, term) {
		closeDD();
		var q = (term || '').trim();
		var dd = document.createElement('ul');
		dd.className = 'memberadmin-dd';
		if (q === '') {
			var hint = document.createElement('li');
			hint.className = 'memberadmin-dd-none';
			hint.textContent = L('Type to search for accounts…');
			dd.appendChild(hint);
			document.body.appendChild(dd);
			positionDD(dd, input);
			openDD = dd;
			return;
		}
		api('/users?term=' + encodeURIComponent(q)).then(function (users) {
			var cur = membersByGid[gid] || [];
			var shown = false;
			(users || []).forEach(function (u) {
				shown = true;
				var isMember = cur.indexOf(u) >= 0;
				var li = document.createElement('li');
				li.textContent = isMember ? (u + ' ' + L('(already member)')) : u;
				if (isMember) {
					li.className = 'memberadmin-dd-member';
				} else {
					li.addEventListener('mousedown', function (ev) {
						ev.preventDefault();
						addMemberName(gid, u);
						input.value = '';
						closeDD();
					});
				}
				dd.appendChild(li);
			});
			if (!shown) {
				var none = document.createElement('li');
				none.className = 'memberadmin-dd-none';
				none.textContent = L('No account found.');
				dd.appendChild(none);
			}
			document.body.appendChild(dd);
			positionDD(dd, input);
			openDD = dd;
		});
	}

	function positionDD(dd, input) {
		var r = input.getBoundingClientRect();
		dd.style.top = (r.bottom + 4) + 'px';
		dd.style.left = r.left + 'px';
		dd.style.minWidth = Math.max(r.width, 220) + 'px';
		dd.style.display = 'block';
	}

	function buildAddRow(grp) {
		var box = document.createElement('div');
		box.className = 'memberadmin-autobox';

		var input = document.createElement('input');
		input.type = 'text';
		input.placeholder = L('Search an internal/external account…');
		input.setAttribute('autocomplete', 'off');

		var btn = document.createElement('button');
		btn.className = 'button primary';
		btn.textContent = L('Add');
		btn.addEventListener('click', function () { addMemberName(grp.gid, input.value); input.value = ''; });

		input.addEventListener('focus', function () { openDropdown(grp.gid, input, input.value); });
		input.addEventListener('input', debounce(function () { openDropdown(grp.gid, input, input.value); }, 200));
		input.addEventListener('keydown', function (e) {
			if (e.key === 'Enter') { addMemberName(grp.gid, input.value); input.value = ''; closeDD(); }
			if (e.key === 'Escape') { closeDD(); }
		});

		box.appendChild(input);
		box.appendChild(btn);
		return box;
	}

	function renderCard(grp) {
		membersByGid[grp.gid] = grp.members || [];
		var card = document.createElement('div');
		card.className = 'memberadmin-card';

		var title = document.createElement('h3');
		title.textContent = grp.gid;
		card.appendChild(title);

		var list = document.createElement('ul');
		list.className = 'memberadmin-list';
		(grp.members || []).forEach(function (m) {
			var li = document.createElement('li');
			var span = document.createElement('span');
			span.textContent = (m === OC.currentUser) ? (m + ' ' + L('(you)')) : m;
			li.appendChild(span);
			if (m !== OC.currentUser) {
				var rm = document.createElement('button');
				rm.className = 'button memberadmin-x';
				rm.textContent = L('Remove');
				rm.addEventListener('click', function () { removeMember(grp.gid, m); });
				li.appendChild(rm);
			}
			list.appendChild(li);
		});
		card.appendChild(list);
		card.appendChild(buildAddRow(grp));
		return card;
	}

	function load() {
		api('/groups').then(function (groups) {
			if (titleEl) { titleEl.textContent = L('Member management'); }
			if (hintEl) { hintEl.textContent = L('You can add or remove members in the groups assigned to you. You cannot create, edit or delete accounts.'); }
			root.innerHTML = '';
			if (statusEl) { statusEl.className = 'hidden'; }
			if (!groups || !groups.length) {
				var p = document.createElement('p');
				p.textContent = L('No group is assigned to you. Contact the administrator.');
				root.appendChild(p);
				return;
			}
			groups.forEach(function (grp) { root.appendChild(renderCard(grp)); });
		});
	}

	function init() {
		root = document.getElementById('memberadmin-groups');
		statusEl = document.getElementById('memberadmin-status');
		hintEl = document.getElementById('memberadmin-app-hint');
		titleEl = document.getElementById('memberadmin-app-title');
		if (!root) {
			if ((init.tries = (init.tries || 0) + 1) < 120) {
				setTimeout(init, 250);
			}
			return;
		}
		var appEl = document.getElementById('memberadmin-app');
		if (appEl && appEl.dataset && appEl.dataset.lang) { appLang = appEl.dataset.lang; }
		else if (document.documentElement && document.documentElement.lang) { appLang = document.documentElement.lang; }
		if (String(appLang).toLowerCase().indexOf('fr') !== 0) { appLang = 'en'; }
		load();
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();
