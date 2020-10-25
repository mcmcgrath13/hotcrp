<?php
// test/cdb-options.php -- HotCRP conference options for test databases

global $Opt;
$Opt["dbName"] = "hotcrp_testdb";
$Opt["dbUser"] = "test_user";
$Opt["dbPassword"] = "example";
$Opt["dbHost"] = "db";
$Opt["shortName"] = "Testconf I";
$Opt["longName"] = "Test Conference I";
$Opt["passwordHmacKey"] = "W9dY7CVZPA2fFdVjNSVD66gMTbazagzHkm8ygL5uyxZnWA55";
$Opt["contactName"] = "Eddie Kohler";
$Opt["contactEmail"] = "ekohler@hotcrp.lcdf.org";
$Opt["sendEmail"] = false;
$Opt["debugShowSensitiveEmail"] = true;
$Opt["emailFrom"] = "you@example.com";
