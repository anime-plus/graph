<?php
require __DIR__ . '/../src/core.php';

foreach (Database::getAllDbNames() as $database)
{
    Database::attachDatabase($database);

    $users = R::getAll("SELECT `id` FROM user WHERE `join_date` IS NULL OR `processed` IS NULL OR `processed` < ?", [date('Y-m-d H:i:s', strtotime('-1 month'))]);

    foreach ($users as $user)
    {
        R::exec("DELETE FROM userfriend WHERE `user_id` = ?", [$user['id']]);

        R::exec("DELETE FROM userhistory WHERE `user_id` = ?", [$user['id']]);

        R::exec("DELETE FROM usermedia WHERE `user_id` = ?", [$user['id']]);

        R::exec("DELETE FROM user WHERE `id` = ?", [$user['id']]);
    }
}

$databases = [
    '00',
    '01',
    '02',
    '03',
    '04',
    '05',
    '06',
    '07',
    '08',
    '09',
    '0a',
    '0b',
    '0c',
    '0d',
    '0e',
    '0f',
    '10',
    '11',
    '12',
    '13',
    '14',
    '15',
    '16',
    '17',
    '18',
    '19',
    '1a',
    '1b',
    '1c',
    '1d',
    '1e',
    '1f',
    '20',
    '21',
    '22',
    '23',
    '24',
    '25',
    '26',
    '27',
    '28',
    '29',
    '2a',
    '2b',
    '2c',
    '2d',
    '2e',
    '2f',
    '30',
    '31',
    '32',
    '33',
    '34',
    '35',
    '36',
    '37',
    '38',
    '39',
    '3a',
    '3b',
    '3c',
    '3d',
    '3e',
    '3f',
];

foreach ($databases as $database)
{
    Database::loadDatabase('user-' . $database . '.sqlite');

    R::exec("VACUUM");
}

Database::loadDatabase('media.sqlite');

R::exec("VACUUM");
