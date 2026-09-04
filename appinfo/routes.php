<?php
declare(strict_types=1);
return [
	'routes' => [
		['name' => 'page#index', 'url' => '/', 'verb' => 'GET'],
		['name' => 'member#index', 'url' => '/groups', 'verb' => 'GET'],
		['name' => 'member#search', 'url' => '/users', 'verb' => 'GET'],
		['name' => 'member#add', 'url' => '/groups/{gid}/members', 'verb' => 'POST'],
		['name' => 'member#remove', 'url' => '/groups/{gid}/members/{userId}', 'verb' => 'DELETE'],
	],
];
