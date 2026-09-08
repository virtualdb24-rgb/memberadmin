<?php

declare(strict_types=1);

namespace OCA\MemberAdmin\Service;

use OCP\IConfig;

/**
 * Table d'autorisation : proprietaire -> liste de groupes qu'il peut gerer.
 * Stockee dans la configuration de l'app (JSON) et administree par occ.
 */
class Allowlist {
	public const APP = 'memberadmin';
	public const KEY = 'allowed';
	public const KEY_ROLES = 'roles';

	private IConfig $config;

	public function __construct(IConfig $config) {
		$this->config = $config;
	}

	private function map(): array {
		$raw = (string)$this->config->getAppValue(self::APP, self::KEY, '{}');
		$m = json_decode($raw, true);
		return is_array($m) ? $m : [];
	}

	private function save(array $m): void {
		$this->config->setAppValue(self::APP, self::KEY, json_encode($m));
	}

	/** Groupes que cet utilisateur est autorise a gerer. */
	public function groupsFor(string $uid): array {
		$m = $this->map();
		$g = $m[$uid] ?? [];
		return array_values(array_unique(array_filter(array_map('strval', is_array($g) ? $g : []))));
	}

	/* ----- Roles : membres d'un groupe-role gerent les groupes d'un prefixe ----- */

	/** Carte roles : role_gid => liste de prefixes */
	public function rolesMap(): array {
		$raw = (string)$this->config->getAppValue(self::APP, self::KEY_ROLES, '{}');
		$m = json_decode($raw, true);
		return is_array($m) ? $m : [];
	}

	private function saveRoles(array $m): void {
		$this->config->setAppValue(self::APP, self::KEY_ROLES, json_encode($m));
	}

	public function roleAddPrefix(string $role, string $prefix): void {
		$m = $this->rolesMap();
		$m[$role] = array_values(array_unique(array_merge($m[$role] ?? [], [$prefix])));
		$this->saveRoles($m);
	}

	public function roleRemovePrefix(string $role, string $prefix): void {
		$m = $this->rolesMap();
		if (!isset($m[$role])) {
			return;
		}
		$m[$role] = array_values(array_diff($m[$role], [$prefix]));
		if (empty($m[$role])) {
			unset($m[$role]);
		}
		$this->saveRoles($m);
	}

	public function roleList(string $role = ''): array {
		$m = $this->rolesMap();
		return $role === '' ? $m : ($m[$role] ?? []);
	}

	public function isAllowed(string $uid, string $gid): bool {
		return in_array($gid, $this->groupsFor($uid), true);
	}

	public function grant(string $uid, string $gid): void {
		if ($gid === 'admin' || $gid === '') {
			return;
		}
		$m = $this->map();
		$cur = $m[$uid] ?? [];
		$cur[] = $gid;
		$m[$uid] = array_values(array_unique($cur));
		$this->save($m);
	}

	public function revoke(string $uid, string $gid): void {
		$m = $this->map();
		if (!isset($m[$uid])) {
			return;
		}
		$m[$uid] = array_values(array_diff($m[$uid], [$gid]));
		if (empty($m[$uid])) {
			unset($m[$uid]);
		}
		$this->save($m);
	}

	/**
	 * Import CSV : chaque ligne "owner;group".
	 *
	 * @param list<array{0:string,1:string}> $rows
	 */
	public function import(array $rows): int {
		$m = $this->map();
		$n = 0;
		foreach ($rows as $row) {
			if (count($row) < 2) {
				continue;
			}
			$uid = trim((string)$row[0]);
			$gid = trim((string)$row[1]);
			if ($uid === '' || $gid === '' || $gid === 'admin') {
				continue;
			}
			$cur = $m[$uid] ?? [];
			$cur[] = $gid;
			$m[$uid] = array_values(array_unique($cur));
			$n++;
		}
		$this->save($m);
		return $n;
	}
}
