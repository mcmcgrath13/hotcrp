<?php
// conflict.php -- HotCRP conflict type class
// Copyright (c) 2008-2020 Eddie Kohler; see LICENSE.

class Conflict {
    private $conf;
    private $_desc_map;

    const GENERAL = 2;
    const PINNED = 3;
    const PLACEHOLDER = 62;

    static private $typedesc = [4 => "Advisor/advisee",
                                2 => "Recent collaborator",
                                6 => "Institutional",
                                8 => "Personal",
                                10 => "Other"];
    static private $type_json = [0 => false,
                                 2 => "collaborator",
                                 4 => "advisor",
                                 6 => "institution",
                                 8 => "personal",
                                 10 => "other",
                                 CONFLICT_AUTHOR => "author",
                                 CONFLICT_CONTACTAUTHOR => "author"];

    static function is_conflicted($ct) {
        return $ct > 0;
    }
    static function is_author($ct) {
        return $ct >= CONFLICT_AUTHOR;
    }
    static function is_pinned($ct) {
        return ($ct & 1) && $ct < CONFLICT_AUTHOR;
    }
    static function set_pinned($ct, $pinned) {
        if ($ct >= CONFLICT_AUTHOR || self::is_pinned($ct) === !!$pinned) {
            return $ct;
        } else if ($pinned) {
            return self::PINNED;
        } else {
            return self::GENERAL;
        }
    }

    function __construct(Conf $conf) {
        $this->conf = $conf;
    }
    function basic_conflict_types() {
        return array_keys(self::$typedesc);
    }
    function parse_assignment($text, $default_yes) {
        // Returns a conflict type; never is_author
        if (is_bool($text)) {
            return $text ? $default_yes : 0;
        }
        $text = strtolower(trim($text));
        if (($pinned = str_starts_with($text, "pinned ") ? 1 : 0)) {
            $text = substr($text, 7);
        }
        if ($text === "none") {
            return 0 + $pinned;
        } else if (($b = friendly_boolean($text)) !== null) {
            if ($pinned) {
                return $b ? self::PINNED : 1;
            } else {
                return $b ? $default_yes : 0;
            }
        } else if ($text === "conflict") {
            return $pinned ? self::PINNED : $default_yes;
        } else if ($text === "collab" || $text === "collaborator" || $text === "recent collaborator") {
            return self::GENERAL + $pinned /* 2 */;
        } else if ($text === "advisor" || $text === "student" || $text === "advisor/student" || $text === "advisee") {
            return 4 + $pinned;
        } else if ($text === "institution" || $text === "institutional") {
            return 6 + $pinned;
        } else if ($text === "personal") {
            return 8 + $pinned;
        } else if ($text === "other") {
            return 10 + $pinned;
        } else if ($text === "confirmed" || $text === "chair-confirmed" || $text === "pinned") {
            return self::PINNED;
        } else {
            return false;
        }
    }
    function parse_json($j) {
        if (is_bool($j)) {
            return $j ? self::GENERAL : 0;
        } else if (is_int($j)) {
            $pinned = $j >= 0 && $j < CONFLICT_AUTHOR && ($j & 1) === 1;
            if (isset(self::$type_json[$j - $pinned])) {
                return $j;
            } else {
                return false;
            }
        } else if (is_string($j)) {
            return $this->parse_assignment($j, self::GENERAL);
        } else {
            return false;
        }
    }

    private function description_map() {
        if ($this->_desc_map === null) {
            $this->_desc_map = [];
            foreach ([0 => "No conflict",
                      CONFLICT_AUTHOR => "Author",
                      CONFLICT_CONTACTAUTHOR => "Contact"] as $n => $t) {
                $this->_desc_map[$n] = $this->conf->_c("conflict_type", $t);
            }
            foreach (self::$typedesc as $n => $t) {
                $this->_desc_map[$n] = $this->conf->_c("conflict_type", $t);
            }
        }
        return $this->_desc_map;
    }
    function unparse_text($ct) {
        $ct = self::set_pinned(min($ct, CONFLICT_CONTACTAUTHOR), false);
        $tm = $this->description_map();
        return $tm[$ct] ?? $tm[self::GENERAL];
    }
    function unparse_csv($ct) {
        if ($ct <= self::GENERAL + 1) {
            return $ct <= 1 ? "N" : "Y";
        } else {
            return $this->unparse_text($ct);
        }
    }
    function unparse_html($ct) {
        return htmlspecialchars($this->unparse_text($ct));
    }
    function unparse_json($ct) {
        if (isset(self::$type_json[$ct])) {
            return self::$type_json[$ct];
        } else if (self::is_pinned($ct) && isset(self::$type_json[$ct - 1])) {
            return "pinned " + ($ct <= 1 ? "none" : self::$type_json[$ct - 1]);
        } else {
            assert(isset(self::$type_json[$ct]));
            return self::$type_json[$ct];
        }
    }
    function unparse_assignment($ct) {
        $j = $this->unparse_json($ct);
        return $j === false ? "none" : $j;
    }
}
