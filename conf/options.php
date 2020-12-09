<?php
// test/options.php -- HotCRP conference options for test databases

global $Opt;
$Opt["dbName"] = "hotcrp_testdb";
$Opt["dbUser"] = "test_user";
$Opt["dbPassword"] = "example";
$Opt["dbHost"] = "db";
$Opt["shortName"] = "Testconf I";
$Opt["longName"] = "Test Conference I";
$Opt["paperSite"] = "http://localhost:8080/";
$Opt["passwordHmacKey"] = "6MFZ8fnvAudRVRn4CXsMNrqVjSvTZqrVBFLEBfxRvxvsEjWn";
$Opt["contactName"] = "Eddie Kohler";
$Opt["contactEmail"] = "ekohler@hotcrp.lcdf.org";
$Opt["sendEmail"] = false;
$Opt["debugShowSensitiveEmail"] = true;
$Opt["emailFrom"] = "you@example.com";
$Opt["smartScoreCompare"] = true;
$Opt["timezone"] = "America/New_York";
$Opt["sub_open"] = time() - 1000;
$Opt["sub_sub"] = time() + 1000000;
