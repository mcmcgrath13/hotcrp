<?php
// intrinsicvalue.php -- HotCRP helper class for paper options
// Copyright (c) 2006-2020 Eddie Kohler; see LICENSE.

// authors       assign_force, unparse_json, parse_web, parse_json
// contacts      assign_force, unparse_json, parse_web, parse_json
// topics        assign_force, unparse_json, parse_web, parse_json
//     -- automatically add new topics
// pc conflicts  assign_force, unparse_json, parse_web, parse_json
//     -- constrain_editable!

class SubmissionVersion_PaperOption extends PaperOption {
    function __construct($conf, $args) {
        parent::__construct($conf, $args);
    }
    function render(FieldRender $fr, PaperValue $ov) {
        $fr->table->render_submission_version($fr, $this);
    }
}

class Title_PaperOption extends PaperOption {
    function __construct($conf, $args) {
        parent::__construct($conf, $args);
    }
    function unparse_json(PaperValue $ov, PaperStatus $ps) {
        return (string) $ov->data();
    }
    function assign_force(PaperValue $ov) {
        if ((string) $ov->prow->title !== "") {
            $ov->set_value_data([1], [$ov->prow->title]);
        }
    }
    function parse_web(PaperInfo $prow, Qrequest $qreq) {
        return $this->parse_json_string(convert_to_utf8($qreq->title));
    }
    function parse_json(PaperInfo $prow, $j) {
        return $this->parse_json_string($prow, $j);
    }
    function echo_web_edit(PaperTable $pt, $ov, $reqov) {
        $this->echo_web_edit_text($pt, $ov, $reqov, ["no_format_description" => true]);
    }
    function render(FieldRender $fr, PaperValue $ov) {
        $fr->value = $ov->prow->title ? : "[No title]";
        $fr->value_format = $ov->prow->title_format();
    }
}

class Authors_PaperOption extends PaperOption {
    function __construct($conf, $args) {
        parent::__construct($conf, $args);
    }
    function value_messages(PaperValue $ov, MessageSet $ms) {
        assert(isset($ov->anno["intrinsic"]));
        $msg1 = $msg2 = false;
        foreach ($ov->prow->author_list() as $n => $au) {
            if (strpos($au->email, "@") === false
                && strpos($au->affiliation, "@") !== false) {
                $msg1 = true;
                $ms->warning_at("author" . ($n + 1), null);
            } else if ($au->firstName === "" && $au->lastName === ""
                       && $au->email === "" && $au->affiliation !== "") {
                $msg2 = true;
                $ms->warning_at("author" . ($n + 1), null);
            }
        }
        $max_authors = $this->conf->opt("maxAuthors");
        if (!$ov->prow->author_list()) {
            $ms->error_at("authors", "Entry required.");
            $ms->error_at("author1", false);
        }
        if ($max_authors > 0
            && count($ov->prow->author_list()) > $max_authors) {
            $ms->error_at("authors", $this->conf->_("Each submission can have at most %d authors.", $max_authors));
        }
        if ($msg1) {
            $ms->warning_at("authors", "You may have entered an email address in the wrong place. The first author field is for email, the second for name, and the third for affiliation.");
        }
        if ($msg2) {
            $ms->warning_at("authors", "Please enter a name and optional email address for every author.");
        }
    }
    function echo_web_edit(PaperTable $pt, $ov, $reqov) {
        $pt->echo_editable_authors($this);
    }
    function render(FieldRender $fr, PaperValue $ov) {
        $fr->table->render_authors($fr, $this);
    }
}

class Anonymity_PaperOption extends PaperOption {
    function __construct($conf, $args) {
        parent::__construct($conf, $args);
    }
    function unparse_json(PaperValue $ov, PaperStatus $ps) {
        if ($this->conf->subBlindOptional()) {
            return !!$ov->value;
        } else {
            return null;
        }
    }
    function assign_force(PaperValue $ov) {
        if (!$ov->prow->blind) {
            $ov->set_value_data([1], [null]);
        }
    }
    function parse_web(PaperInfo $prow, Qrequest $qreq) {
        $v = $prow->conf->subBlindOptional() && !$qreq->blind;
        return PaperValue::make($prow, $this, $v ? 1 : null);
    }
    function parse_json(PaperInfo $prow, $j) {
        if ($j === null || is_bool($j)) {
            return PaperValue::make($prow, $this, $j ? 1 : null);
        } else {
            return PaperValue::make_error($prow, $this, "Expected boolean.");
        }
    }
    function echo_web_edit(PaperTable $pt, $ov, $reqov) {
        $pt->echo_editable_anonymity($this);
    }
}

class Contacts_PaperOption extends PaperOption {
    function __construct($conf, $args) {
        parent::__construct($conf, $args);
    }
    function echo_web_edit(PaperTable $pt, $ov, $reqov) {
        $pt->echo_editable_contact_author($this);
    }
}

class Abstract_PaperOption extends PaperOption {
    function __construct($conf, $args) {
        $args["required"] = !$conf->opt("noAbstract");
        parent::__construct($conf, $args);
    }
    function unparse_json(PaperValue $ov, PaperStatus $ps) {
        $t = (string) $ov->data();
        return $this->required || $t !== "" ? $t : null;
    }
    function assign_force(PaperValue $ov) {
        if ((string) $ov->prow->abstract !== "") {
            $ov->set_value_data([1], [$ov->prow->abstract]);
        }
    }
    function parse_web(PaperInfo $prow, Qrequest $qreq) {
        return $this->parse_json_string(convert_to_utf8($qreq->abstract));
    }
    function parse_json(PaperInfo $prow, $j) {
        return $this->parse_json_string($prow, $j);
    }
    function echo_web_edit(PaperTable $pt, $ov, $reqov) {
        if ((int) $this->conf->opt("noAbstract") !== 1) {
            $this->echo_web_edit_text($pt, $ov, $reqov);
        }
    }
    function render(FieldRender $fr, PaperValue $ov) {
        if ($fr->for_page()) {
            $fr->table->render_abstract($fr, $this);
        } else {
            $text = $ov->prow->abstract;
            if (trim($text) !== "") {
                $fr->value = $text;
                $fr->value_format = $ov->prow->abstract_format();
            } else if (!$this->conf->opt("noAbstract")
                       && $fr->verbose()) {
                $fr->set_text("[No abstract]");
            }
        }
    }
}

class Topics_PaperOption extends PaperOption {
    function __construct($conf, $args) {
        parent::__construct($conf, $args);
    }
    function unparse_json(PaperValue $ov, PaperStatus $ps) {
        assert(is_array($ov->data()));
        $r = [];
        foreach ($ov->data() as $tid) {
            if (empty($r))
                $tset = $this->conf->topic_set();
            $r[$tset[$tid]] = true;
        }
        return empty($r) ? null : (object) $r;
    }
    function assign_force(PaperValue $ov) {
        $ov->set_value_data([1], [$ov->prow->topic_list()]);
    }
    function parse_web(PaperInfo $prow, Qrequest $qreq) {
        $r = [];
        foreach ($user->conf->topic_set() as $tid => $tname) {
            if (+$qreq["top$tid"] > 0)
                $r[$tid] = true;
        }
        return PaperValue::make($prow, $this, 1, $r);
    }
    function parse_json(PaperInfo $prow, $j) {
        if (is_string($j)) {
            $j = explode("\n", $j);
        } else if ($j === false) {
            return PaperValue::make($prow, $this, 1, []);
        } else if (!is_object($j) && !is_array($j)) {
            return PaperValue::make_error($prow, $this, "Expected array of topic information.");
        }
        $r = [];
        $unknown_topics = [];
        $error_html = [];
        $tset = $this->conf->topic_set();
        foreach ((array) $j as $k => $v) {
            if ($k === "") {
                continue;
            }
            if (is_int($k) && is_string($v)) {
                $k = $v;
                $v = true;
            }
            $k = trim($k);
            if ($k === "") {
                // skip
            } else if ($v === true) {
                $tid = array_search($k, $tset->as_array(), true);
                if ($tid === false) {
                    $tmatches = [];
                    foreach ($tset as $tid => $tname) {
                        if (strcasecmp($k, $tname) === 0)
                            $tmatches[] = $tid;
                    }
                    if (empty($tmatches)) {
                        $unknown_topics[] = $k;
                    } else if (count($tmatches) === 1) {
                        $tid = $tmatches[0];
                    } else {
                        $error_html[] = "Topic “" . htmlspecialchars($k) . "” is ambiguous.";
                    }
                }
                if ($tid !== false) {
                    $r[$tid] = true;
                }
            } else {
                return PaperValue::make_error($prow, $this, "Expected array of topic information.");
            }
        }
        $ov = PaperValue::make($prow, $this, 1, $r);
        if (!empty($unknown_topics)) {
            $ov->anno["unknown_topics"] = $unknown_topics;
        }
        if (!empty($error_html)) {
            $ov->anno["error_html"] = $error_html;
        }
        return $ov;
    }
    function echo_web_edit(PaperTable $pt, $ov, $reqov) {
        $pt->echo_editable_topics($this);
    }
    function render(FieldRender $fr, PaperValue $ov) {
        $tlist = $ov->data();
        assert(is_array($tlist));
        if (empty($tlist)) {
            return;
        }
        $user = $fr->table->user;
        $interests = $user->topic_interest_map();
        $lenclass = count($tlist) < 4 ? "long" : "short";
        $tset = $this->conf->topic_set();
        $ts = [];
        foreach ($tlist as $tid) {
            $t = '<li class="topicti';
            if ($interests) {
                $t .= ' topic' . get($interests, $tid, 0);
            }
            $x = $tset->unparse_name_html($tid);
            if ($user->isPC) {
                $x = Ht::link($x, hoturl("search", ["q" => "topic:" . SearchWord::quote($tset[$tid])]), ["class" => "qq"]);
            }
            $ts[] = $t . '">' . $x . '</li>';
            $lenclass = TopicSet::max_topici_lenclass($lenclass, $tset[$tid]);
        }
        $fr->title = $this->title(count($ts));
        $fr->set_html('<ul class="topict topict-' . $lenclass . '">' . join("", $ts) . '</ul>');
        $fr->value_long = true;
    }
}

class PCConflicts_PaperOption extends PaperOption {
    function __construct($conf, $args) {
        parent::__construct($conf, $args);
    }
    function value_messages(PaperValue $ov, MessageSet $ms) {
        if (($ov->prow->outcome <= 0
             || ($ms->user && !$ms->user->can_view_decision($ov->prow)))
            && $this->conf->setting("sub_pcconf")) {
            $r = $ov->data();
            assert(is_array($r));
            $ts = [];
            foreach ($this->conf->full_pc_members() as $p) {
                if (!isset($r[$p->contactId])
                    // XXX explicit non-conflict
                    && $ov->prow->potential_conflict($p)) {
                    $n = Text::name_html($p);
                    $ts[] = Ht::link($n, "#pcc{$p->contactId}", ["class" => "uu"]);
                }
            }
            if (!empty($ts)) {
                $ms->warning_at("pcconf", $this->conf->_("You may have missed conflicts of interest with %s. Please verify that all conflicts are correctly marked.", commajoin($ts, "and")) . $this->conf->_(" Hover over “possible conflict” labels for more information."));
            }
        }
    }
    function unparse_json(PaperValue $ov, PaperStatus $ps) {
        $confset = $this->conf->conflict_types();
        $pcm = $this->conf->pc_members();
        $j = [];
        foreach ($ov->data() as $id => $ct) {
            if (($ctname = $confset->unparse_json($ct)))
                $j[$pcm[$id]->email] = $ctname;
        }
        return empty($j) ? null : (object) $j;
    }
    function assign_force(PaperValue $ov) {
        $r = [];
        foreach ($ov->prow->pc_conflicts() as $id => $cflt) {
            $r[$id] = $cflt->conflictType;
        }
        $pcm = $this->conf->pc_members();
        uksort($r, function ($a, $b) use ($pcm) {
            return $pcm[$a]->sort_position - $pcm[$b]->sort_position;
        });
        $ov->set_value_data([1], [$r]);
    }
    function parse_web(PaperInfo $prow, Qrequest $qreq) {
        if (!$this->conf->setting("sub_pcconf")) {
            return false;
        } else {
            $j = [];
            foreach ($this->conf->pc_members() as $id => $pc) {
                if (($ctype = intval($qreq["pcc$id"]))) {
                    $j[$id] = $ctype;
                }
            }
            return PaperOption::make($prow, $this, 1, $j);
        }
    }
    function parse_json(PaperInfo $prow, $j) {
        if (!$user->conf->setting("sub_pcconf")) {
            return false;
        } else if (!is_object($j) && !is_array($j)) {
            return PaperValue::make_error($prow, $this, "Expected array of conflict information.");
        } else {
            $confset = $this->conf->conflict_types();
            $r = $bad = [];
            foreach ((array) $j as $k => $v) {
                if (is_int($k) && is_object($v) && isset($v->email)) {
                    $k = $v->email;
                }
                if (is_object($v) && isset($v->conflict)) {
                    $v = $v->conflict;
                }
                if (is_string($k)
                    && ($pc = $this->conf->pc_member_by_email($k))) {
                    $ctn = $confset->parse_json($v);
                    if ($ctn !== false && $ctn !== 0) {
                        $r[$pc->contactId] = $ctn;
                    } else if ($ctn === false) {
                        $bad[] = "Expected PC conflict type for $k.";
                    }
                } else if (is_string($k)) {
                    $bad[] = "No such PC member " . htmlspecialchars($k) . ".";
                } else {
                    return PaperValue::make_error($prow, $this, "Expected array of conflict information.");
                }
            }
            $pcm = $this->conf->pc_members();
            uksort($r, function ($a, $b) use ($pcm) {
                return $pcm[$a]->sort_position - $pcm[$b]->sort_position;
            });
            $ov = PaperValue::make($prow, $this, 1, $r);
            if ($bad) {
                $ov->anno["error_html"] = $bad;
            }
            return $ov;
        }
    }
    function echo_web_edit(PaperTable $pt, $ov, $reqov) {
        // XXX use $ov and $reqov
        $pt->echo_editable_pc_conflicts($this);
    }
}

class Collaborators_PaperOption extends PaperOption {
    function __construct($conf, $args) {
        parent::__construct($conf, $args);
    }
    function value_messages(PaperValue $ov, MessageSet $ms) {
        if ($this->conf->setting("sub_collab")
            && !$this->value_present($ov)
            && ($ov->prow->outcome <= 0 || ($ms->user && !$ms->user->can_view_decision($ov->prow)))) {
            $ms->warning_at("collaborators", $this->conf->_("Enter the authors’ external conflicts of interest. If none of the authors have external conflicts, enter “None”."));
        }
    }
    function unparse_json(PaperValue $ov, PaperStatus $ps) {
        return $ov->data();
    }
    function assign_force(PaperValue $ov) {
        if ((string) $ov->prow->collaborators !== "") {
            $ov->set_value_data([1], [$ov->prow->collaborators]);
        }
    }
    function parse_web(PaperInfo $prow, Qrequest $qreq) {
        return $this->parse_json_string(convert_to_utf8($qreq->collaborators));
    }
    function parse_json(PaperInfo $prow, $j) {
        return $this->parse_json_string($prow, $j);
    }
    function echo_web_edit(PaperTable $pt, $ov, $reqov) {
        if ($this->conf->setting("sub_collab")
            && ($pt->editable !== "f" || $pt->user->can_administer($pt->prow))) {
            $this->echo_web_edit_text($pt, $ov, $reqov, ["no_format_description" => true, "no_spellcheck" => true]);
        }
    }
    function render(FieldRender $fr, PaperValue $ov) {
        // XXX
    }
}
