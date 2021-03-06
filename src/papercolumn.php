<?php
// papercolumn.php -- HotCRP helper classes for paper list content
// Copyright (c) 2006-2020 Eddie Kohler; see LICENSE.

class PaperColumn extends Column {
    const OVERRIDE_NONE = 0;
    const OVERRIDE_FOLD_IFEMPTY = 1; // XXX backward compat
    const OVERRIDE_IFEMPTY = 1;
    const OVERRIDE_IFEMPTY_LINK = 2;
    const OVERRIDE_BOTH = 3;
    const OVERRIDE_FORCE = 4;
    const OVERRIDE_NONCONFLICTED = 16;
    /** @var int */
    public $override = 0;

    const PREP_SORT = -1;
    const PREP_FOLDED = 0; // value matters
    const PREP_VISIBLE = 1; // value matters

    /** @param object $cj */
    function __construct(Conf $conf, $cj) {
        parent::__construct($cj);
    }

    /** @param list<string> $decorations
     * @return PaperColumn */
    static function make(Conf $conf, $cj, $decorations = []) {
        if ($cj->callback[0] === "+") {
            $class = substr($cj->callback, 1);
            /** @phan-suppress-next-line PhanTypeExpectedObjectOrClassName */
            $pc = new $class($conf, $cj);
        } else {
            $pc = call_user_func($cj->callback, $conf, $cj);
        }
        foreach ($decorations as $decor) {
            $pc->add_decoration($decor);
        }
        return $pc;
    }
    /** @param string $msg
     * @param bool $is_default */
    static function column_error(Contact $user, $msg, $is_default = false) {
        $c = $user->conf->xt_context;
        if ($c instanceof PaperList) {
            $c->column_error($msg, $is_default);
        }
    }


    function mark_editable() {
    }
    final function add_user_sort_decoration($decor) {
        if (in_array($decor, ["name", "last", "first", "email", "affiliation"])) {
            return $this->__add_decoration($decor);
        } else {
            return false;
        }
    }

    function prepare(PaperList $pl, $visible) {
        return true;
    }
    function field_json(PaperList $pl) {
        $j = [
            "name" => $this->name,
            "title" => $this->header($pl, false),
            "position" => $this->position
        ];
        if ($this->className !== "pl_" . $this->name) {
            $j["className"] = $this->className;
        }
        if ($this->as_row) {
            $j["as_row"] = true;
        } else {
            $j["column"] = true;
            if ($this->has_statistics()) {
                $j["has_statistics"] = true;
            }
            if ($this->sort) {
                $j["sort_name"] = $this->sort_name();
            }
        }
        if (!$this->is_visible) {
            error_log("missing .. " . json_encode($j) . " .. " . debug_string_backtrace());
            $j["missing"] = true;
        }
        if ($this->has_content && !$this->is_visible) {
            error_log("loadable .. " . json_encode($j));
            $j["loadable"] = true;
        }
        if ($this->fold) {
            $j["foldnum"] = $this->fold;
        }
        if (!empty($this->decorations)) {
            $j["decorations"] = $this->decorations;
        }
        return $j;
    }

    /** @param int $sortindex */
    function prepare_sort(PaperList $pl, $sortindex) {
    }
    /** @return int */
    function compare(PaperInfo $a, PaperInfo $b, PaperList $pl) {
        error_log("unexpected " . get_class($this) . "::compare");
        return $a->paperId - $b->paperId;
    }

    /** @param list<PaperColumn> $columns */
    function analyze(PaperList $pl, $columns) {
    }

    /** @return string */
    function header(PaperList $pl, $is_text) {
        if (isset($this->title_html) && !$is_text) {
            return $this->title_html;
        } else if (isset($this->title)) {
            return $is_text ? $this->title : htmlspecialchars($this->title);
        } else if ($is_text) {
            return "<" . $this->name . ">";
        } else {
            return "&lt;" . htmlspecialchars($this->name) . "&gt;";
        }
    }
    /** @return string|false */
    function completion_name() {
        if (!$this->completion) {
            return false;
        } else if (is_string($this->completion)) {
            return $this->completion;
        } else {
            return $this->name;
        }
    }
    function sort_name() {
        $decor = $this->decorations;
        if (!empty($decor)) {
            $decor = array_diff($decor, ["down"]);
        }
        return empty($decor) ? $this->name : $this->name . " " . join(" ", $decor);
    }

    /** @return bool */
    function content_empty(PaperList $pl, PaperInfo $row) {
        return false;
    }
    /** @return string */
    function content(PaperList $pl, PaperInfo $row) {
        return "";
    }
    /** @return string */
    function text(PaperList $pl, PaperInfo $row) {
        return "";
    }

    /** @return bool */
    function has_statistics() {
        return false;
    }
    /** @return false|string */
    function statistic_html(PaperList $pl, $stat) {
        return false;
    }
}

class Id_PaperColumn extends PaperColumn {
    function __construct(Conf $conf, $cj) {
        parent::__construct($conf, $cj);
    }
    function compare(PaperInfo $a, PaperInfo $b, PaperList $pl) {
        return $a->paperId - $b->paperId;
    }
    function content(PaperList $pl, PaperInfo $row) {
        $href = $pl->_paperLink($row);
        return "<a href=\"$href\" class=\"pnum taghl\">#$row->paperId</a>";
    }
    function text(PaperList $pl, PaperInfo $row) {
        return (string) $row->paperId;
    }
}

class Selector_PaperColumn extends PaperColumn {
    private $selectall = false;
    function __construct(Conf $conf, $cj) {
        parent::__construct($conf, $cj);
    }
    function add_decoration($decor) {
        if ($decor === "selected") {
            $this->selectall = true;
            return $this->__add_decoration("selected");
        } else {
            return parent::add_decoration($decor);
        }
    }
    function header(PaperList $pl, $is_text) {
        if ($is_text) {
            return "Selected";
        } else {
            return '<input type="checkbox" class="uic js-range-click is-range-group" data-range-type="pap[]" aria-label="Select all">';
        }
    }
    protected function checked(PaperList $pl, PaperInfo $row) {
        return $pl->is_selected($row->paperId, $this->selectall);
    }
    function content(PaperList $pl, PaperInfo $row) {
        $pl->mark_has("sel");
        $c = "";
        if ($this->checked($pl, $row)) {
            $c .= ' checked';
        }
        return '<span class="pl_rownum fx6">' . $pl->count . '. </span>'
            . '<input type="checkbox" class="uic uikd js-range-click js-selector" name="pap[]" value="' . $row->paperId . '"' . $c . ' aria-label="#' . $row->paperId . '">';
    }
    function text(PaperList $pl, PaperInfo $row) {
        return $this->checked($pl, $row) ? "Y" : "N";
    }
}

class Title_PaperColumn extends PaperColumn {
    private $has_decoration = false;
    private $highlight = false;
    function __construct(Conf $conf, $cj) {
        parent::__construct($conf, $cj);
    }
    function prepare(PaperList $pl, $visible) {
        $this->has_decoration = $pl->user->can_view_tags(null)
            && $pl->conf->tags()->has_decoration;
        if ($this->has_decoration) {
            $pl->qopts["tags"] = 1;
        }
        $this->highlight = $pl->search->field_highlighter("title");
        return true;
    }
    function compare(PaperInfo $a, PaperInfo $b, PaperList $pl) {
        $collator = $a->conf->collator();
        return $collator->compare($a->title, $b->title);
    }
    function content(PaperList $pl, PaperInfo $row) {
        $t = '<a href="' . $pl->_paperLink($row) . '" class="ptitle taghl';

        if ($row->title !== "") {
            $highlight_text = Text::highlight($row->title, $this->highlight, $highlight_count);
        } else {
            $highlight_text = "[No title]";
            $highlight_count = 0;
        }

        if (!$highlight_count && ($format = $row->title_format())) {
            $pl->need_render = true;
            $t .= ' need-format" data-format="' . $format
                . '" data-title="' . htmlspecialchars($row->title);
        }

        $t .= '">' . $highlight_text . '</a>'
            . $pl->_contentDownload($row);

        if ($this->has_decoration && (string) $row->paperTags !== "") {
            if ($pl->row_tags_overridable !== ""
                && ($deco = $pl->tagger->unparse_decoration_html($pl->row_tags_overridable))) {
                $decx = $pl->tagger->unparse_decoration_html($pl->row_tags);
                if ($deco !== $decx) {
                    $t .= str_replace('class="tagdecoration"', 'class="tagdecoration fn5"', $decx)
                        . str_replace('class="tagdecoration"', 'class="tagdecoration fx5"', $deco);
                } else {
                    $t .= $deco;
                }
            } else if ($pl->row_tags !== "") {
                $t .= $pl->tagger->unparse_decoration_html($pl->row_tags);
            }
        }

        return $t;
    }
    function text(PaperList $pl, PaperInfo $row) {
        return $row->title;
    }
}

class Status_PaperColumn extends PaperColumn {
    private $include_submitted;
    function __construct(Conf $conf, $cj) {
        parent::__construct($conf, $cj);
        $this->override = PaperColumn::OVERRIDE_BOTH;
    }
    function prepare(PaperList $pl, $visible) {
        $this->include_submitted = $pl->search->limit_expect_nonsubmitted();
        return true;
    }
    function prepare_sort(PaperList $pl, $sortindex) {
        foreach ($pl->rowset() as $row) {
            if ($row->outcome && $pl->user->can_view_decision($row)) {
                $row->{$this->uid} = $row->outcome;
            } else {
                $row->{$this->uid} = -10000;
            }
        }
    }
    function analyze(PaperList $pl, $columns) {
        foreach ($pl->rowset() as $row) {
            if ($row->outcome != 0 || $row->paperStorageId <= 1) {
                $t = ($pl->user->paper_status_info($row))[1];
                if (strlen($t) > 10 && strpos($t, " ") !== false) {
                    $this->className .= " pl-status-long";
                    break;
                }
            }
        }
    }
    function compare(PaperInfo $a, PaperInfo $b, PaperList $pl) {
        $x = $b->{$this->uid} - $a->{$this->uid};
        $x = $x ? : ($a->timeWithdrawn > 0 ? 1 : 0) - ($b->timeWithdrawn > 0 ? 1 : 0);
        $x = $x ? : ($b->timeSubmitted > 0 ? 1 : 0) - ($a->timeSubmitted > 0 ? 1 : 0);
        return $x ? : ($b->paperStorageId > 1 ? 1 : 0) - ($a->paperStorageId > 1 ? 1 : 0);
    }
    function content(PaperList $pl, PaperInfo $row) {
        $status_info = $pl->user->paper_status_info($row);
        if ($this->include_submitted || $status_info[0] !== "pstat_sub") {
            return "<span class=\"pstat $status_info[0]\">" . htmlspecialchars($status_info[1]) . "</span>";
        } else {
            return "";
        }
    }
    function text(PaperList $pl, PaperInfo $row) {
        $status_info = $pl->user->paper_status_info($row);
        return $status_info[1];
    }
}

class ReviewStatus_PaperColumn extends PaperColumn {
    private $round;
    function __construct(Conf $conf, $cj) {
        parent::__construct($conf, $cj);
        $this->override = PaperColumn::OVERRIDE_BOTH;
        $this->round = get($cj, "round", null);
    }
    function prepare(PaperList $pl, $visible) {
        if ($pl->user->privChair || $pl->user->is_reviewer() || $pl->conf->can_some_author_view_review()) {
            $pl->qopts["reviewSignatures"] = true;
            return true;
        } else {
            return false;
        }
    }
    private function data(PaperInfo $row, Contact $user) {
        $want_assigned = !$row->has_conflict($user) || $user->can_administer($row);
        $done = $started = 0;
        foreach ($row->reviews_by_id() as $rrow) {
            if ($user->can_view_review_assignment($row, $rrow)
                && ($this->round === null || $this->round === $rrow->reviewRound)) {
                if ($rrow->reviewStatus >= ReviewInfo::RS_COMPLETED) {
                    ++$done;
                    ++$started;
                } else if (($want_assigned
                            ? $rrow->reviewNeedsSubmit > 0
                            : $rrow->reviewStatus > 0)
                           && ($rrow->reviewType != REVIEW_EXTERNAL
                               || $row->conf->ext_subreviews < 2)) {
                    ++$started;
                }
            }
        }
        return [$done, $started];
    }
    function prepare_sort(PaperList $pl, $sortindex) {
        foreach ($pl->rowset() as $row) {
            if (!$pl->user->can_view_review_assignment($row, null)) {
                $row->{$this->uid} = -2147483647;
            } else {
                list($done, $started) = $this->data($row, $pl->user);
                $row->{$this->uid} = $done + $started / 1000.0;
            }
        }
    }
    function compare(PaperInfo $a, PaperInfo $b, PaperList $pl) {
        $av = $a->{$this->uid};
        $bv = $b->{$this->uid};
        return ($av < $bv ? 1 : ($av == $bv ? 0 : -1));
    }
    function header(PaperList $pl, $is_text) {
        $round_name = "";
        if ($this->round !== null) {
            $round_name = ($pl->conf->round_name($this->round) ? : "unnamed") . " ";
        }
        if ($is_text) {
            return "# {$round_name}Reviews";
        } else {
            return '<span class="need-tooltip" data-tooltip="# completed reviews / # assigned reviews" data-tooltip-dir="b">#&nbsp;' . $round_name . 'Reviews</span>';
        }
    }
    function content_empty(PaperList $pl, PaperInfo $row) {
        return !$pl->user->can_view_review_assignment($row, null);
    }
    function content(PaperList $pl, PaperInfo $row) {
        list($done, $started) = $this->data($row, $pl->user);
        return "<b>$done</b>" . ($done == $started ? "" : "/$started");
    }
    function text(PaperList $pl, PaperInfo $row) {
        list($done, $started) = $this->data($row, $pl->user);
        return $done . ($done == $started ? "" : "/$started");
    }
}

class Authors_PaperColumn extends PaperColumn {
    private $aufull;
    private $anonau;
    private $highlight;
    private $ianno;
    function __construct(Conf $conf, $cj) {
        parent::__construct($conf, $cj);
    }
    function add_decoration($decor) {
        if ($decor === "full" || $decor === "short") {
            $this->aufull = $decor === "full";
            return $this->__add_decoration($this->aufull ? "full" : null, ["full"]);
        } else if ($decor === "anon" || $decor === "noanon") {
            $this->anonau = $decor === "anon";
            return $this->__add_decoration($this->anonau ? "anon" : "noanon", ["anon", "noanon"]);
        } else {
            return parent::add_user_sort_decoration($decor) || parent::add_decoration($decor);
        }
    }
    function prepare(PaperList $pl, $visible) {
        $this->aufull = $this->aufull ?? $pl->viewing("aufull");
        $this->anonau = $this->anonau ?? $pl->viewing("anonau");
        $this->highlight = $pl->search->field_highlighter("authorInformation");
        return $pl->user->can_view_some_authors();
    }
    function field_json(PaperList $pl) {
        $j = parent::field_json($pl);
        $j["aufull"] = $this->aufull;
        return $j;
    }
    function prepare_sort(PaperList $pl, $sortindex) {
        $this->ianno = Contact::parse_sortspec($pl->conf, $this->decorations);
    }
    function compare(PaperInfo $a, PaperInfo $b, PaperList $pl) {
        $au1 = $pl->user->allow_view_authors($a) ? $a->author_list() : [];
        $au2 = $pl->user->allow_view_authors($b) ? $b->author_list() : [];
        if (empty($au1) && empty($au2)) {
            return 0;
        } else if (empty($au1) || empty($au2)) {
            return empty($au1) ? 1 : -1;
        }
        for ($i = 0; $i < count($au1) && $i < count($au2); ++$i) {
            $s1 = Contact::make_sorter($au1[$i], $this->ianno);
            $s2 = Contact::make_sorter($au2[$i], $this->ianno);
            if (($v = strnatcasecmp($s1, $s2)) !== 0) {
                return $v;
            }
        }
        if (count($au1) === count($au2)) {
            return 0;
        } else {
            return count($au1) < count($au2) ? -1 : 1;
        }
    }
    private function affiliation_map($row) {
        $nonempty_count = 0;
        $aff = [];
        '@phan-var list<string> $aff';
        foreach ($row->author_list() as $i => $au) {
            if ($i !== 0 && $au->affiliation === $aff[$i - 1]) {
                $aff[$i - 1] = null;
            }
            $aff[] = $au->affiliation;
            $nonempty_count += ($au->affiliation !== "");
        }
        if ($nonempty_count != 0 && $nonempty_count != count($aff)) {
            foreach ($aff as &$affx) {
                if ($affx === "") {
                    $affx = "unaffiliated";
                }
            }
        }
        return $aff;
    }
    function content_empty(PaperList $pl, PaperInfo $row) {
        return !$pl->user->allow_view_authors($row);
    }
    function content(PaperList $pl, PaperInfo $row) {
        $out = [];
        if (!$this->highlight && !$this->aufull) {
            foreach ($row->author_list() as $au) {
                $out[] = $au->name_h(NAME_P|NAME_I);
            }
            $t = join(", ", $out);
        } else {
            $affmap = $this->affiliation_map($row);
            $aus = $affout = [];
            $any_affhl = false;
            foreach ($row->author_list() as $i => $au) {
                $name = Text::highlight($au->name(), $this->highlight, $didhl);
                if (!$this->aufull
                    && ($first = htmlspecialchars($au->firstName))
                    && (!$didhl || substr($name, 0, strlen($first)) === $first)
                    && ($initial = Text::initial($first)) !== "") {
                    $name = $initial . substr($name, strlen($first));
                }
                $aus[] = $name;
                if ($affmap[$i] !== null) {
                    $out[] = join(", ", $aus);
                    $affout[] = Text::highlight($affmap[$i], $this->highlight, $didhl);
                    $any_affhl = $any_affhl || $didhl;
                    $aus = [];
                }
            }
            // $affout[0] === "" iff there are no nonempty affiliations
            if (($any_affhl || $this->aufull)
                && !empty($out)
                && $affout[0] !== "") {
                foreach ($out as $i => &$x) {
                    $x .= ' <span class="auaff">(' . $affout[$i] . ')</span>';
                }
            }
            $t = join($any_affhl || $this->aufull ? "; " : ", ", $out);
        }
        if ($pl->conf->submission_blindness() !== Conf::BLIND_NEVER
            && !$pl->user->can_view_authors($row)) {
            $t = '<div class="fx2">' . $t . '</div>';
        }
        return $t;
    }
    function text(PaperList $pl, PaperInfo $row) {
        if (!$pl->user->can_view_authors($row) && !$this->anonau) {
            return "";
        }
        $out = [];
        if (!$this->aufull) {
            foreach ($row->author_list() as $au) {
                $out[] = $au->name(NAME_P|NAME_I);
            }
            return join("; ", $out);
        } else {
            $affmap = $this->affiliation_map($row);
            $aus = [];
            foreach ($row->author_list() as $i => $au) {
                $aus[] = $au->name();
                if ($affmap[$i] !== null) {
                    $aff = ($affmap[$i] !== "" ? " ($affmap[$i])" : "");
                    $out[] = commajoin($aus) . $aff;
                    $aus = [];
                }
            }
            return join("; ", $out);
        }
    }
}

class Collab_PaperColumn extends PaperColumn {
    function __construct(Conf $conf, $cj) {
        parent::__construct($conf, $cj);
        $this->override = PaperColumn::OVERRIDE_IFEMPTY;
    }
    function prepare(PaperList $pl, $visible) {
        return !!$pl->conf->setting("sub_collab") && $pl->user->can_view_some_authors();
    }
    function content_empty(PaperList $pl, PaperInfo $row) {
        return !$row->has_nonempty_collaborators()
            || !$pl->user->allow_view_authors($row);
    }
    function content(PaperList $pl, PaperInfo $row) {
        $x = "";
        foreach (explode("\n", $row->collaborators()) as $c) {
            if ($c !== "") {
                $x .= ($x === "" ? "" : "; ") . trim($c);
            }
        }
        return Text::highlight($x, $pl->search->field_highlighter("collaborators"));
    }
    function text(PaperList $pl, PaperInfo $row) {
        $x = "";
        foreach (explode("\n", $row->collaborators()) as $c) {
            $x .= ($x === "" ? "" : ", ") . trim($c);
        }
        return $x;
    }
}

class Abstract_PaperColumn extends PaperColumn {
    function __construct(Conf $conf, $cj) {
        parent::__construct($conf, $cj);
    }
    function content_empty(PaperList $pl, PaperInfo $row) {
        return $row->abstract_text() === "";
    }
    function content(PaperList $pl, PaperInfo $row) {
        $ab = $row->abstract_text();
        $t = Text::highlight($ab, $pl->search->field_highlighter("abstract"), $highlight_count);
        $klass = strlen($t) > 190 ? "pl_longtext" : "pl_shorttext";
        if (!$highlight_count && ($format = $row->abstract_format())) {
            $pl->need_render = true;
            $t = '<div class="' . $klass . ' need-format" data-format="'
                . $format . '.plx">' . $t . '</div>';
        } else {
            $t = '<div class="' . $klass . ' format0">' . Ht::format0_html($t) . '</div>';
        }
        return $t;
    }
    function text(PaperList $pl, PaperInfo $row) {
        return $row->abstract_text();
    }
}

class ReviewerType_PaperColumn extends PaperColumn {
    protected $contact;
    private $not_me;
    private $rrow_key;
    private $basicheader = false;
    function __construct(Conf $conf, $cj) {
        parent::__construct($conf, $cj);
        if (isset($cj->user)) {
            $this->contact = $conf->pc_member_by_email($cj->user);
        }
    }
    function add_decoration($decor) {
        if ($decor === "basicheader") {
            $this->basicheader = true;
            return $this->__add_decoration($decor);
        } else {
            return parent::add_decoration($decor);
        }
    }
    function contact() {
        return $this->contact;
    }
    function prepare(PaperList $pl, $visible) {
        $this->contact = $this->contact ?? $pl->reviewer_user();
        $this->not_me = $this->contact->contactXid !== $pl->user->contactXid;
        return true;
    }
    const F_CONFLICT = 1;
    const F_LEAD = 2;
    const F_SHEPHERD = 4;
    /** @return array{?PaperListReviewAnalysis,int} */
    private function analysis(PaperList $pl, PaperInfo $row) {
        $rrow = $row->review_of_user($this->contact);
        if ($rrow && (!$this->not_me || $pl->user->can_view_review_identity($row, $rrow))) {
            $ranal = $pl->make_review_analysis($rrow, $row);
        } else {
            $ranal = null;
        }
        if ($ranal && $ranal->rrow->reviewStatus < ReviewInfo::RS_DELIVERED) {
            $pl->mark_has("need_review");
        }
        $flags = 0;
        if ($row->has_conflict($this->contact)
            && (!$this->not_me || $pl->user->can_view_conflicts($row))) {
            $flags |= self::F_CONFLICT;
        }
        if ($row->leadContactId === $this->contact->contactXid
            && (!$this->not_me || $pl->user->can_view_lead($row))) {
            $flags |= self::F_LEAD;
        }
        if ($row->shepherdContactId === $this->contact->contactXid
            && (!$this->not_me || $pl->user->can_view_shepherd($row))) {
            $flags |= self::F_SHEPHERD;
        }
        return [$ranal, $flags];
    }
    function prepare_sort(PaperList $pl, $sortindex) {
        $k = $this->uid;
        foreach ($pl->rowset() as $row) {
            list($ranal, $flags) = $this->analysis($pl, $row);
            if ($ranal && $ranal->rrow->reviewType) {
                $row->$k = 2 * $ranal->rrow->reviewType;
                if ($ranal->rrow->reviewStatus >= ReviewInfo::RS_COMPLETED) {
                    $row->$k += 1;
                }
            } else {
                $row->$k = ($flags & self::F_CONFLICT ? -2 : 0);
            }
            if ($flags & self::F_LEAD) {
                $row->$k += 30;
            }
            if ($flags & self::F_SHEPHERD) {
                $row->$k += 60;
            }
        }
    }
    function compare(PaperInfo $a, PaperInfo $b, PaperList $pl) {
        return $b->{$this->uid} - $a->{$this->uid};
    }
    function header(PaperList $pl, $is_text) {
        if (!$this->not_me || $this->basicheader) {
            return "Review";
        } else if ($is_text) {
            return $pl->user->reviewer_text_for($this->contact) . " review";
        } else {
            return $pl->user->reviewer_html_for($this->contact) . "<br>review";
        }
    }
    function content(PaperList $pl, PaperInfo $row) {
        list($ranal, $flags) = $this->analysis($pl, $row);
        $t = "";
        if ($ranal) {
            $t = $ranal->icon_html(true);
        } else if ($flags & self::F_CONFLICT) {
            $t = review_type_icon(-1);
        }
        $x = [];
        if ($flags & self::F_LEAD) {
            $x[] = review_lead_icon();
        }
        if ($flags & self::F_SHEPHERD) {
            $x[] = review_shepherd_icon();
        }
        if (!empty($x) || ($ranal && $ranal->round)) {
            $c = ["pl_revtype"];
            $t && ($c[] = "hasrev");
            ($flags & (self::F_LEAD | self::F_SHEPHERD)) && ($c[] = "haslead");
            $ranal && $ranal->round && ($c[] = "hasround");
            $t && ($x[] = $t);
            return '<div class="' . join(" ", $c) . '">' . join('&nbsp;', $x) . '</div>';
        } else {
            return $t;
        }
    }
    function text(PaperList $pl, PaperInfo $row) {
        list($ranal, $flags) = $this->analysis($pl, $row);
        $t = [];
        if ($flags & self::F_LEAD) {
            $t[] = "Lead";
        }
        if ($flags & self::F_SHEPHERD) {
            $t[] = "Shepherd";
        }
        if ($ranal) {
            $t[] = $ranal->icon_text();
        }
        if ($flags & self::F_CONFLICT) {
            $t[] = "Conflict";
        }
        return empty($t) ? "" : join("; ", $t);
    }
}

class AssignReview_PaperColumn extends ReviewerType_PaperColumn {
    function __construct(Conf $conf, $cj) {
        parent::__construct($conf, $cj);
    }
    function prepare(PaperList $pl, $visible) {
        return parent::prepare($pl, $visible) && $pl->user->is_manager();
    }
    function header(PaperList $pl, $is_text) {
        if ($is_text) {
            return $pl->user->reviewer_text_for($this->contact) . " assignment";
        } else {
            return $pl->user->reviewer_html_for($this->contact) . "<br>assignment";
        }
    }
    function content_empty(PaperList $pl, PaperInfo $row) {
        return !$pl->user->allow_administer($row);
    }
    function content(PaperList $pl, PaperInfo $row) {
        $ci = $row->contact_info($this->contact);
        if ($ci->conflictType >= CONFLICT_AUTHOR) {
            return '<span class="author">Author</span>';
        }
        if ($ci->conflictType > CONFLICT_MAXUNCONFLICTED) {
            $rt = -1;
        } else {
            $rt = min(max($ci->reviewType, 0), REVIEW_META);
        }
        $pl->need_render = true;
        $t = '<span class="need-assignment-selector';
        if (!$this->contact->can_accept_review_assignment_ignore_conflict($row)
            && $rt <= 0) {
            $t .= " conflict";
        }
        return $t . '" data-assignment="' . $this->contact->contactId . ' ' . $rt . '"></span>';
    }
}

class TagList_PaperColumn extends PaperColumn {
    private $editable;
    function __construct(Conf $conf, $cj, $editable = false) {
        parent::__construct($conf, $cj);
        $this->override = PaperColumn::OVERRIDE_FORCE;
        $this->editable = $editable;
    }
    function mark_editable() {
        $this->editable = true;
    }
    function prepare(PaperList $pl, $visible) {
        if (!$pl->user->can_view_tags(null)) {
            return false;
        }
        if ($visible) {
            $pl->qopts["tags"] = 1;
        }
        if ($visible && $this->editable) {
            $pl->has_editable_tags = true;
        }
        return true;
    }
    function field_json(PaperList $pl) {
        $j = parent::field_json($pl);
        $j["highlight_tags"] = $pl->search->highlight_tags();
        if ($pl->conf->tags()->has_votish) {
            $j["votish_tags"] = array_values(array_map(function ($t) { return $t->tag; }, $pl->conf->tags()->filter("votish")));
        }
        return $j;
    }
    function content_empty(PaperList $pl, PaperInfo $row) {
        return !$pl->user->can_view_tags($row);
    }
    function content(PaperList $pl, PaperInfo $row) {
        if ($this->editable) {
            $pl->row_attr["data-tags-editable"] = 1;
        }
        if ($this->editable || $pl->row_tags !== "" || $pl->row_tags_overridable !== "") {
            $pl->need_render = true;
            return '<span class="need-tags"></span>';
        } else {
            return "";
        }
    }
    function text(PaperList $pl, PaperInfo $row) {
        return $pl->tagger->unparse_hashed($row->sorted_viewable_tags($pl->user));
    }
}

class ScoreGraph_PaperColumn extends PaperColumn {
    /** @var Contact */
    protected $contact;
    protected $not_me;
    protected $score_sort;
    /** @var ReviewField */
    protected $format_field;
    function __construct(Conf $conf, $cj) {
        parent::__construct($conf, $cj);
    }
    function add_decoration($decor) {
        if (($d = ListSorter::canonical_short_score_sort($decor))) {
            $this->score_sort = $d;
            return $this->__add_decoration(ListSorter::canonical_long_score_sort($d),
                                           ListSorter::long_score_sort_list());
        } else {
            return parent::add_decoration($decor);
        }
    }
    function score_sort(PaperList $pl) {
        $this->score_sort = $this->score_sort ?? ListSorter::default_score_sort($pl->user);
        return $this->score_sort;
    }
    function prepare(PaperList $pl, $visible) {
        $this->contact = $pl->reviewer_user();
        $this->not_me = $this->contact->contactId !== $pl->user->contactId;
        if ($visible && $this->not_me
            && (!$pl->user->privChair || $pl->conf->has_any_manager())) {
            $pl->qopts["reviewSignatures"] = true;
        }
        if ($visible === PaperColumn::PREP_SORT) {
            $this->score_sort($pl);
        }
    }
    /** @return array<int,int> */
    function score_values(PaperList $pl, PaperInfo $row) {
        throw new Exception("score_values not defined");
    }
    protected function set_sort_fields(PaperList $pl, PaperInfo $row) {
        $k = $this->uid;
        $avgk = $k . "avg";
        $s = $this->score_values($pl, $row);
        if ($s !== null) {
            $scoreinfo = new ScoreInfo($s, true);
            $cid = $this->contact->contactId;
            if ($this->not_me
                && !$row->can_view_review_identity_of($cid, $pl->user)) {
                $cid = 0;
            }
            $row->$k = $scoreinfo->sort_data($this->score_sort, $cid);
            $row->$avgk = $scoreinfo->mean();
        } else {
            $row->$k = $row->$avgk = null;
        }
    }
    function prepare_sort(PaperList $pl, $sortindex) {
        foreach ($pl->rowset() as $row) {
            self::set_sort_fields($pl, $row);
        }
    }
    function compare(PaperInfo $a, PaperInfo $b, PaperList $pl) {
        $k = $this->uid;
        if (!($x = ScoreInfo::compare($b->$k, $a->$k, -1))) {
            $k .= "avg";
            $x = ScoreInfo::compare($b->$k, $a->$k);
        }
        return $x;
    }
    function content(PaperList $pl, PaperInfo $row) {
        $values = $this->score_values($pl, $row);
        if (empty($values)) {
            return "";
        }
        $pl->need_render = true;
        $cid = $this->contact->contactId;
        if ($this->not_me && !$row->can_view_review_identity_of($cid, $pl->user)) {
            $cid = 0;
        }
        return $this->format_field->unparse_graph($values, 1, $values[$cid] ?? null);
    }
    function text(PaperList $pl, PaperInfo $row) {
        $values = array_map([$this->format_field, "unparse_value"],
                            $this->score_values($pl, $row));
        return join(" ", array_values($values));
    }
}

class Score_PaperColumn extends ScoreGraph_PaperColumn {
    public $score;
    function __construct(Conf $conf, $cj) {
        parent::__construct($conf, $cj);
        $this->override = PaperColumn::OVERRIDE_IFEMPTY;
        $this->format_field = $conf->checked_review_field($cj->review_field_id);
        $this->score = $this->format_field->id;
    }
    function prepare(PaperList $pl, $visible) {
        $bound = $pl->user->permissive_view_score_bound($pl->search->limit_author());
        if ($this->format_field->view_score <= $bound) {
            return false;
        }
        if ($visible) {
            $pl->qopts["scores"][$this->score] = true;
        }
        parent::prepare($pl, $visible);
        return true;
    }
    /** return array<int,int> */
    function score_values(PaperList $pl, PaperInfo $row) {
        $fid = $this->format_field->id;
        $row->ensure_review_score($this->format_field);
        $scores = [];
        $vs = $this->format_field->view_score;
        foreach ($row->viewable_submitted_reviews_by_user($pl->user) as $rrow) {
            if (isset($rrow->$fid)
                && $rrow->$fid
                && ($vs >= VIEWSCORE_PC || $vs > $pl->user->view_score_bound($row, $rrow)))
                $scores[$rrow->contactId] = $rrow->$fid;
        }
        return $scores;
    }
    function content_empty(PaperList $pl, PaperInfo $row) {
        // Do not use score_values to determine content emptiness, since
        // that would load the scores from the DB -- even for folded score
        // columns.
        return !$row->may_have_viewable_scores($this->format_field, $pl->user);
    }

    static function user_visible_fields($name, Contact $user) {
        if ($name === "scores") {
            $fs = $user->conf->all_review_fields();
        } else {
            $fs = [$user->conf->find_review_field($name)];
        }
        $vsbound = $user->permissive_view_score_bound();
        return array_filter($fs, function ($f) use ($vsbound) {
            return $f && $f->has_options && $f->displayed && $f->view_score > $vsbound;
        });
    }
    static function expand($name, Contact $user, $xfj, $m) {
        return array_map(function ($f) use ($xfj) {
            $cj = (array) $xfj;
            $cj["name"] = $f->search_keyword();
            $cj["review_field_id"] = $f->id;
            $cj["title"] = $f->search_keyword();
            $cj["title_html"] = $f->web_abbreviation();
            return (object) $cj;
        }, self::user_visible_fields($name, $user));
    }
    static function completions(Contact $user, $fxt) {
        if (!$user->can_view_some_review()) {
            return [];
        }
        $vsbound = $user->permissive_view_score_bound();
        $cs = array_map(function ($f) {
            return $f->search_keyword();
        }, array_filter($user->conf->all_review_fields(), function ($f) use ($vsbound) {
            return $f->has_options && $f->displayed && $f->view_score > $vsbound;
        }));
        if (!empty($cs)) {
            array_unshift($cs, "scores");
        }
        return $cs;
    }
}
