<?php
// capability.php -- HotCRP capability management
// Copyright (c) 2006-2018 Eddie Kohler; see LICENSE.

class CapabilityManager {
    private $dblink;
    private $prefix;

    function __construct($dblink, $prefix) {
        $this->dblink = $dblink;
        $this->prefix = $prefix;
    }

    static function encode($s) {
        return str_replace(["+", "/", "="], ["-a", "-b", ""], base64_encode($s));
    }

    static function decode($s) {
        return base64_decode(str_replace(["-a", "-b"], ["+", "/"], $s));
    }

    function create($capabilityType, $options = array()) {
        $contactId = get($options, "contactId", 0);
        if (!$contactId
            && ($user = get($options, "user"))) {
            if ($this->prefix === "U")
                $contactId = $user->contactdb_user()->contactDbId;
            else
                $contactId = $user->contactId;
        }
        $paperId = get($options, "paperId", 0);
        $timeExpires = get($options, "timeExpires", time() + 259200);
        $data = get($options, "data");
        $ok = false;

        for ($tries = 0; !$ok && $tries < 4; ++$tries)
            if (($salt = random_bytes(16)) !== false) {
                $result = Dbl::ql($this->dblink, "insert into Capability set capabilityType=?, contactId=?, paperId=?, timeExpires=?, salt=?, data=?", $capabilityType, $contactId, $paperId, $timeExpires, $salt, $data);
                $ok = $result && $result->affected_rows > 0;
            }

        if (!$ok)
            return false;
        return $this->prefix . "1" . self::encode($salt);
    }

    function check($capabilityText) {
        if (substr($capabilityText, 0, strlen($this->prefix) + 1) !== $this->prefix . "1")
            return false;
        $value = self::decode(substr($capabilityText, strlen($this->prefix) + 1));
        if (strlen($value) >= 2
            && ($result = Dbl::ql($this->dblink, "select * from Capability where salt=?", $value))
            && ($row = Dbl::fetch_first_object($result))
            && ($row->timeExpires == 0 || $row->timeExpires >= time()))
            return $row;
        else
            return false;
    }

    function delete($capdata) {
        assert(!$capdata || is_string($capdata->salt));
        if ($capdata)
            Dbl::ql($this->dblink, "delete from Capability where salt=?", $capdata->salt);
    }



    static function encode_capability_v0($prow, $capType) {
        // A capability has the following representation (. is concatenation):
        //    capFormat . paperId . capType . hashPrefix
        // capFormat -- Character denoting format (currently 0).
        // paperId -- Decimal representation of paper number.
        // capType -- Capability type (e.g. "a" for author view).
        // To create hashPrefix, calculate a SHA-1 hash of:
        //    capFormat . paperId . capType . paperCapVersion . capKey
        // where paperCapVersion is a decimal representation of the paper's
        // capability version (usually 0, but could allow conference admins
        // to disable old capabilities paper-by-paper), and capKey
        // is a random string specific to the conference, stored in Settings
        // under cap_key (created in load_settings).  Then hashPrefix
        // is the base-64 encoding of the first 8 bytes of this hash, except
        // that "+" is re-encoded as "-", "/" is re-encoded as "_", and
        // trailing "="s are removed.
        //
        // Any user who knows the conference's cap_key can construct any
        // capability for any paper.  Longer term, one might set each paper's
        // capVersion to a random value; but the only way to get cap_key is
        // database access, which would give you all the capVersions anyway.

        $key = $prow->conf->setting_data("cap_key");
        if (!$key) {
            $key = base64_encode(random_bytes(16));
            if (!$key || !$prow->conf->save_setting("cap_key", 1, $key))
                return false;
        }
        $start = "0" . $prow->paperId . $capType;
        $hash = sha1($start . $prow->capVersion . $key, true);
        $suffix = str_replace(["+", "/", "="], ["-", "_", ""],
                              base64_encode(substr($hash, 0, 8)));
        return $start . $suffix;
    }

    static function apply_old_author_view(Contact $user, $uf, $isadd) {
        if (($prow = $user->conf->fetch_paper(["paperId" => $uf->match_data[1]]))
            && ($uf->name === self::encode_capability_v0($prow, "a"))
            && !$user->conf->opt("disableCapabilities"))
            $user->set_capability($prow->paperId, $isadd ? "av" : null);
    }


    static function set_secret($dblink, $table, $where, $secret_column) {
        return Dbl::compare_and_swap($dblink,
            "select $secret_column from $table where $where", [],
            function ($value) {
                return $value === null ? random_bytes(16) : $value;
            },
            "update $table set $secret_column=?{desired} where $where and $secret_column is null", []);
    }

    static function encode_capability_v1($type /* ... */) {
        global $Now;
        $pw = $sw = [];
        foreach (func_get_args() as $i => $w) {
            if ($i === 0) {
                /* ignore */
            } else if (is_string($w)) {
                if (strpos($w, " ") !== false) {
                    throw new Exception("argument contains space");
                }
                $pw[] = $w;
            } else if (is_int($w)) {
                $pw[] = $w;
            } else if (is_array($w)) {
                if ($w[0] === "now") {
                    $pw[] = +strftime("%Y%j", $Now) - 2000000;
                } else if ($w[0] === "secret" && is_string($w[1])) {
                    $sw[] = $w[1];
                } else {
                    throw new Exception("unexpected argument");
                }
            } else if ($w instanceof PaperInfo) {
                $pw[] = $w->paperId;
                if ($w->paperSecret === null)
                    $w->paperSecret = self::set_secret($w->conf->dblink, "Paper", "paperId={$w->paperId}", "paperSecret");
                $sw[] = $w->paperSecret;
            } else if ($w instanceof ReviewInfo) {
                $pw[] = $w->reviewId;
                if ($w->reviewSecret === null)
                    $w->reviewSecret = self::set_secret($w->conf->dblink, "PaperReview", "paperId={$w->paperId} and reviewId={$w->reviewId}", "reviewSecret");
                $sw[] = $w->reviewSecret;
            } else if ($w instanceof Contact) {
                if ($w->contactId <= 0) {
                    throw new Exception("user argument has no id");
                }
                $pw[] = $w->contactId;
                if ($w->userSecret === null)
                    $w->userSecret = self::set_secret($w->conf->dblink, "ContactInfo", "contactId={$w->contactId}", "userSecret");
                $sw[] = $w->userSecret;
            } else {
                throw new Exception("unexpected argument");
            }
        }
        if (empty($sw)) {
            throw new Exception("no secret key");
        }
        $pw[] = substr(hash_hmac("sha256", $type . "-" . join(" ", $pw), join($sw, ""), true), 0, 16);
        return $type . "-" . self::encode(join(" ", $pw));
    }


    static function upgrade_capabilities_0($caps) {
        $ncaps = [];
        foreach ($caps as $pid => $a) {
            if ($pid === 0) {
                $ncaps = array_merge($ncaps, $a);
            } else if ($a === 1 && ctype_digit($pid)) {
                $ncaps[$pid] = "av";
            }
        }
        return empty($ncaps) ? null : $ncaps;
    }
}
