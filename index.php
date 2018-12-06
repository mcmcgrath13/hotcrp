<?php
// index.php -- HotCRP home page
// Copyright (c) 2006-2018 Eddie Kohler; see LICENSE.

require_once("lib/navigation.php");

$page = Navigation::page();
if ($page === "u"
    && ($unum = Navigation::path_component(0)) !== false
    && ctype_digit($unum))
    $page = Navigation::shift_path_components(2);
if ($page !== "index") {
    if (is_readable("$page.php")
        /* The following is paranoia (currently can't happen): */
        && strpos($page, "/") === false) {
        include("$page.php");
        exit;
    } else if ($page === "images" || $page === "scripts" || $page === "stylesheets") {
        $_GET["file"] = $page . Navigation::path();
        include("cacheable.php");
        exit;
    } else {
        header("HTTP/1.0 404 Not Found");
        exit;
    }
}

require_once("src/initweb.php");
// handle signin/signout -- may change $Me
$Me = Home_Partial::signin_requests($Me, $Qreq);
// That also got rid of all disabled users.

$gex = new GroupedExtensions($Me, ["etc/homepartials.json"],
                             $Conf->opt("pagePartials"));
foreach ($gex->members("home") as $gj)
    $gex->request($gj, $Qreq, [$Me, $Qreq, $gex, $gj]);
$gex->start_render();
foreach ($gex->members("home") as $gj)
    $gex->render($gj, [$Me, $Qreq, $gex, $gj]);
$gex->end_render();

$Conf->footer();
