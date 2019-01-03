<?php
// initweb.php -- HotCRP initialization for web scripts
// Copyright (c) 2006-2018 Eddie Kohler; see LICENSE.

require_once("init.php");
global $Conf, $Me, $Qreq;

// Check method: GET/HEAD/POST only, except OPTIONS is allowed for API calls
if ($_SERVER["REQUEST_METHOD"] !== "GET"
    && $_SERVER["REQUEST_METHOD"] !== "HEAD"
    && $_SERVER["REQUEST_METHOD"] !== "POST"
    && (Navigation::page() !== "api"
        || $_SERVER["REQUEST_METHOD"] !== "OPTIONS")) {
    header("HTTP/1.0 405 Method Not Allowed");
    exit;
}

// Collect $Qreq
$Qreq = make_qreq();
if (isset($Qreq->i))
    error_log("Request parameter with name=i");

// Check for redirect to https
if ($Conf->opt("redirectToHttps"))
    Navigation::redirect_http_to_https($Conf->opt("allowLocalHttp"));

// Check and fix zlib output compression
global $zlib_output_compression;
$zlib_output_compression = false;
if (function_exists("zlib_get_coding_type"))
    $zlib_output_compression = zlib_get_coding_type();
if ($zlib_output_compression) {
    header("Content-Encoding: $zlib_output_compression");
    header("Vary: Accept-Encoding", false);
}

// Mark as already expired to discourage caching, but allow the browser
// to cache for history buttons
header("Cache-Control: max-age=0,must-revalidate,private");

// Set up Content-Security-Policy if appropriate
$Conf->prepare_content_security_policy();

// Don't set up a session if $Me is false
if ($Me === false)
    return;


// Initialize user
function initialize_user() {
    global $Conf, $Me, $Now, $Qreq;

    // set up session
    if (isset($Conf->opt["sessionHandler"])) {
        $sh = $Conf->opt["sessionHandler"];
        $Conf->_session_handler = new $sh($Conf);
        session_set_save_handler($Conf->_session_handler, true);
    }
    set_session_name($Conf);
    $sn = session_name();

    // check CSRF token, using old value of session ID
    if ($Qreq->post && $sn) {
        if (isset($_COOKIE[$sn])) {
            $sid = $_COOKIE[$sn];
            $l = strlen($Qreq->post);
            if ($l >= 8 && $Qreq->post === substr($sid, strlen($sid) > 16 ? 8 : 0, $l))
                $Qreq->approve_post();
        } else if ($Qreq->post === "<empty-session>"
                   || $Qreq->post === ".empty") {
            $Qreq->approve_post();
        }
    }
    ensure_session(ENSURE_SESSION_ALLOW_EMPTY);

    // check "/u/" prefix and $_GET["i"]
    $nav = Navigation::get();
    if ($nav->shifted_path === ""
        && isset($_GET["i"])
        && isset($_SESSION["us"])
        && $_SERVER["REQUEST_METHOD"] === "GET") {
        $us = $_SESSION["us"];
        for ($i = 0; $i < count($us); ++$i) {
            if (strcasecmp($us[$i], $_GET["i"]) === 0) {
                $suf = "";
                if ($nav->page !== "index" || $nav->path !== "")
                    $suf .= $nav->page . $nav->php_suffix . $nav->path;
                $suf .= preg_replace_callback('/([?&;])i=[^&;#]*([&;]*)/', function ($m) {
                        return $m[2] === "" ? "" : $m[1];
                    }, $nav->query);
                Navigation::redirect_base("u/$i/$suf");
            }
        }
    }
    if (isset($_SESSION["us"])
        || ($nav->shifted_path !== "" && $nav->shifted_path !== "u/0/")) {
        $i = 0;
        if (substr($nav->shifted_path, 0, 2) === "u/")
            $i = (int) substr($nav->shifted_path, 2);
        $us = isset($_SESSION["us"]) ? $_SESSION["us"] : [];
        if ($i < count($us)) {
            if (strcasecmp($_SESSION["u"], $us[$i]) !== 0)
                $_SESSION["u"] = $us[$i];
        } else if ($_SERVER["REQUEST_METHOD"] === "GET") {
            $suf = "";
            if ($nav->page !== "index" || $nav->path !== "")
                $suf .= $nav->page . $nav->php_suffix . $nav->path;
            Navigation::redirect_base("u/0/{$suf}{$nav->query}");
        } else {
            header("HTTP/1.0 403 Permission Error");
            exit;
        }
    }

    // load current user
    $Me = null;
    if (!isset($_SESSION["u"]) && isset($_SESSION["trueuser"]))
        $_SESSION["u"] = $_SESSION["trueuser"]->email;
    $trueemail = isset($_SESSION["u"]) ? $_SESSION["u"] : null;
    if ($trueemail)
        $Me = $Conf->user_by_email($trueemail);
    if (!$Me)
        $Me = new Contact($trueemail ? (object) ["email" => $trueemail] : null);
    $Me = $Me->activate($Qreq);

    // redirect if disabled
    if ($Me->is_disabled()) {
        if ($nav->page === "api")
            json_exit(["ok" => false, "error" => "Your account is disabled."]);
        else if ($nav->page !== "index" && $nav->page !== "resetpassword")
            Navigation::redirect_site(hoturl_site_relative("index"));
    }

    // if bounced through login, add post data
    if (isset($_SESSION["login_bounce"][4])
        && $_SESSION["login_bounce"][4] <= $Now)
        unset($_SESSION["login_bounce"]);

    if (!$Me->is_empty()
        && isset($_SESSION["login_bounce"])
        && !isset($_SESSION["testsession"])) {
        $lb = $_SESSION["login_bounce"];
        if ($lb[0] == $Conf->dsn
            && $lb[2] !== "index"
            && $lb[2] == Navigation::page()) {
            foreach ($lb[3] as $k => $v)
                if (!isset($Qreq[$k]))
                    $Qreq[$k] = $v;
            $Qreq->set_annex("after_login", true);
        }
        unset($_SESSION["login_bounce"]);
    }

    // set $_SESSION["addrs"]
    if ($_SERVER["REMOTE_ADDR"]
        && (!$Me->is_empty()
            || isset($_SESSION["addrs"]))
        && (!isset($_SESSION["addrs"])
            || !is_array($_SESSION["addrs"])
            || $_SESSION["addrs"][0] !== $_SERVER["REMOTE_ADDR"])) {
        $as = [$_SERVER["REMOTE_ADDR"]];
        if (isset($_SESSION["addrs"]) && is_array($_SESSION["addrs"])) {
            foreach ($_SESSION["addrs"] as $a)
                if ($a !== $_SERVER["REMOTE_ADDR"] && count($as) < 5)
                    $as[] = $a;
        }
        $_SESSION["addrs"] = $as;
    }
}

initialize_user();
