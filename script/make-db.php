<?php
require_once __DIR__ . '/../src/core.php';

try
{
	$path1 = tempnam(sys_get_temp_dir(), '');
	Database::loadDatabase($path1);
	R::freeze(false);
	R::exec('CREATE TABLE IF NOT EXISTS user (
		id INTEGER PRIMARY KEY,
		name VARCHAR(32) UNIQUE,
		picture_url VARCHAR(256),
		join_date VARCHAR(10), --TIMESTAMP
		mal_id INTEGER,
		comments INTEGER,
		posts INTEGER,
		birthday VARCHAR(10), --TIMESTAMP
		location VARCHAR(100),
		website VARCHAR(100),
		gender VARCHAR(1),
		processed TIMESTAMP,
		cool BOOLEAN,

		anime_views INTEGER,
		anime_days_spent FLOAT,
		anime_private BOOLEAN,
		manga_views INTEGER,
		manga_days_spent FLOAT,
		manga_private BOOLEAN
	)');

	R::exec('CREATE TABLE IF NOT EXISTS userfriend (
		id INTEGER PRIMARY KEY,
		user_id INTEGER,
		name VARCHAR(32),
		UNIQUE (user_id, name)
	)');
	R::exec('CREATE INDEX IF NOT EXISTS idx_userfriend_user_id ON userfriend (user_id)');

	R::exec('CREATE TABLE IF NOT EXISTS userhistory (
		id INTEGER PRIMARY KEY,
		user_id INTEGER,
		mal_id INTEGER,
		media VARCHAR(1),
		progress INTEGER,
		timestamp TIMESTAMP
	)');
	R::exec('CREATE INDEX IF NOT EXISTS idx_userhistory_user_id ON userhistory (user_id)');

	R::exec('CREATE TABLE IF NOT EXISTS usermedia (
		id INTEGER PRIMARY KEY,
		user_id INTEGER,
		mal_id INTEGER,
		media VARCHAR(1),
		score INTEGER,
		start_date VARCHAR(10), --TIMESTAMP
		end_date VARCHAR(10), --TIMESTAMP
		status VARCHAR(1),

		finished_episodes INTEGER,
		finished_chapters INTEGER,
		finished_volumes INTEGER
	)');
	R::exec('CREATE INDEX IF NOT EXISTS idx_usermedia_user_id ON usermedia (user_id)');
	R::exec('CREATE INDEX IF NOT EXISTS idx_usermedia_score ON usermedia(score)');
	R::exec('CREATE INDEX IF NOT EXISTS idx_usermedia_media_score ON usermedia(media,score)');
	R::exec('CREATE INDEX IF NOT EXISTS idx_usermedia_media_mal_id ON usermedia(media,mal_id)');

	foreach (Database::getAllDbNames() as $dbName)
	{
		$path2 = Config::$dbPath . DIRECTORY_SEPARATOR . $dbName;
		copy($path1, $path2);
	}
	unlink($path1);



	Database::loadDatabase('media.sqlite');
	R::freeze(false);
	R::exec('CREATE TABLE IF NOT EXISTS media (
		id INTEGER PRIMARY KEY,
		mal_id INTEGER,
		media VARCHAR(1),
		title VARCHAR(96),
		sub_type INTEGER,
        source INTEGER,
		picture_url VARCHAR(256),
		average_score FLOAT,
		average_score_users INTEGER,
		ranking INTEGER,
		popularity INTEGER,
		members INTEGER,
		favorites INTEGER,
		publishing_status VARCHAR(1),
		published_from VARCHAR(10), --TIMESTAMP
		published_to VARCHAR(10), --TIMESTAMP
		processed TIMESTAMP,
		franchise VARCHAR(10),

		duration INTEGER,
		episodes INTEGER,
		chapters INTEGER,
		volumes INTEGER,
		serialization_id INTEGER,
		serialization_name VARCHAR(32),

		UNIQUE (media, mal_id)
	)');

	R::exec('CREATE TABLE IF NOT EXISTS mediagenre (
		id INTEGER PRIMARY KEY,
		media_id INTEGER,
		mal_id INTEGER,
		name VARCHAR(30)
	)');
	R::exec('CREATE INDEX IF NOT EXISTS idx_mediagenre_media_id ON mediagenre (media_id)');

	R::exec('CREATE TABLE IF NOT EXISTS mediarelation (
		id INTEGER PRIMARY KEY,
		media_id INTEGER,
		mal_id INTEGER,
		media VARCHAR(1),
		type INTEGER
	)');
	R::exec('CREATE INDEX IF NOT EXISTS idx_mediarelation_media_id ON mediarelation (media_id)');

	R::exec('CREATE TABLE IF NOT EXISTS animeproducer (
		id INTEGER PRIMARY KEY,
		media_id INTEGER,
		mal_id INTEGER,
		name VARCHAR(32)
	)');
	R::exec('CREATE INDEX IF NOT EXISTS idx_animeproducer_media_id ON animeproducer (media_id)');

	R::exec('CREATE TABLE IF NOT EXISTS mangaauthor (
		id INTEGER PRIMARY KEY,
		media_id INTEGER,
		mal_id INTEGER,
		name VARCHAR(32)
	)');
	R::exec('CREATE INDEX IF NOT EXISTS idx_mangaauthor_media_id ON mangaauthor (media_id)');

	R::exec('CREATE TABLE IF NOT EXISTS mediarec (
		id INTEGER PRIMARY KEY,
		media_id INTEGER,
		mal_id INTEGER,
		count INTEGER
	)');
	R::exec('CREATE INDEX IF NOT EXISTS idx_mediarec_mediaid ON mediarec (media_id)');
}
catch (Exception $e)
{
	echo $e . PHP_EOL;
}
