// settings.js -- HotCRP JavaScript library for settings
// Copyright (c) 2006-2020 Eddie Kohler; see LICENSE.

function next_lexicographic_permutation(i, size) {
    var y = (i & -i) || 1, c = i + y, highbit = 1 << size;
    i = (((i ^ c) >> 2) / y) | c;
    if (i >= highbit) {
        i = ((i & (highbit - 1)) << 2) | 3;
        if (i >= highbit)
            i = false;
    }
    return i;
}


handle_ui.on("js-settings-option-type", function (event) {
    var issel = /^(?:selector|radio)/.test(this.value);
    foldup.call(this, null, {n: 4, f: !issel});
});

handle_ui.on("js-settings-show-option-property", function () {
    var prop = this.getAttribute("data-option-property"),
        $j = $(this).closest(".settings-opt").find(".is-option-" + prop);
    $j.removeClass("hidden");
    if (document.activeElement === this)
        $j.find("input, select, textarea").not("[type=hidden], :disabled").first().focus();
});

handle_ui.on("js-settings-option-move", function (event) {
    var odiv = $(this).closest(".settings-opt")[0];
    if (hasClass(this, "moveup") && odiv.previousSibling)
        odiv.parentNode.insertBefore(odiv, odiv.previousSibling);
    else if (hasClass(this, "movedown") && odiv.nextSibling)
        odiv.parentNode.insertBefore(odiv, odiv.nextSibling.nextSibling);
    else if (hasClass(this, "delete")) {
        var $odiv = $(odiv), x;
        if ($odiv.find(".settings-opt-id").val() === "new")
            $odiv.remove();
        else {
            tooltip.erase.call(this);
            $odiv.find(".settings-opt-fp").val("deleted").change();
            $odiv.find(".f-i, .entryi").each(function () {
                if (!$(this).find(".settings-opt-fp").length)
                    $(this).remove();
            });
            $odiv.find("input[type=text]").prop("disabled", true).css("text-decoration", "line-through");
            if ((x = this.getAttribute("data-option-exists")))
                $odiv.append('<div class="f-i"><em>This field will be deleted from the submission form and from ' + plural(x, 'submission') + '.</em></div>');
            else
                $odiv.append('<div class="f-i"><em>This field will be deleted from the submission form.</em></div>');
        }
    }
    settings_option_move_enable();
});

handle_ui.on("js-settings-option-new", function (event) {
    var h = $("#settings_newopt").attr("data-template");
    var next = 1;
    while ($("#optn_" + next).length)
        ++next;
    h = h.replace(/_0/g, "_" + next);
    var odiv = $(h).appendTo("#settings_opts");
    odiv.find(".need-autogrow").autogrow();
    odiv.find(".need-tooltip").each(tooltip);
    $("#optn_" + next)[0].focus();
    settings_option_move_enable();
});

function settings_option_move_enable() {
    $(".settings-opt .moveup, .settings-opt .movedown").prop("disabled", false);
    $(".settings-opt:first-child .moveup").prop("disabled", true);
    $(".settings-opt:last-child .movedown").prop("disabled", true);
    var index = 0;
    $(".settings-opt-fp").each(function () {
        if (this.value !== "deleted" && this.name !== "optfp_0") {
            ++index;
            if (this.value != index)
                $(this).val(index).change();
        }
    });
}


handle_ui.on("js-settings-banal-pagelimit", function (evt) {
    var s = $.trim(this.value),
        empty = s === "" || s.toUpperCase() === "N/A",
        $ur = $(this).closest(".has-fold").find(".settings-banal-unlimitedref");
    $ur.find("label").toggleClass("dim", empty);
    $ur.find("input").prop("disabled", empty);
    if (evt && evt.type === "change" && empty)
        $ur.find("input").prop("checked", false);
});


handle_ui.on("js-settings-add-decision-type", function (event) {
    var $t = $("#settings-decision-types"), next = 1;
    while ($t.find("input[name=dec_name_" + next + "]").length)
        ++next;
    $("#settings-decision-type-notes").removeClass("hidden");
    var h = $("#settings-new-decision-type").html().replace(/_0/g, "_" + next),
        $r = $(h).appendTo($t);
    $r.find("input[type=text]").autogrow();
    $r.find("input[name=dec_name_" + next + "]")[0].focus();
});

handle_ui.on("js-settings-remove-decision-type", function (event) {
    var $r = $(this).closest("tr");
    $r.addClass("hidden").find("input[name^=dec_name]").val("");
    $r.find("select[name^=dec_class]").val("1");
    form_highlight($r.closest("form"));
});

handle_ui.on("js-settings-new-autosearch", function (event) {
    var odiv = $(this).closest(".settings_tag_autosearch")[0],
        h = $("#settings_newtag_autosearch").html(), next = 1;
    while ($("#tag_autosearch_t_" + next).length)
        ++next;
    h = h.replace(/_0/g, "_" + next);
    odiv = $(h).appendTo("#settings_tag_autosearch");
    odiv.find("input[type=text]").autogrow();
    $("#tag_autosearch_t_" + next)[0].focus();
});

handle_ui.on("js-settings-delete-autosearch", function (event) {
    var odiv = $(this).closest(".settings_tag_autosearch")[0];
    $(odiv).find("input[name^=tag_autosearch_q_]").val("");
    $(odiv).find("input[type=text]").prop("disabled", true).css("text-decoration", "line-through");
});

handle_ui.on("js-settings-add-track", function () {
    for (var i = 1; jQuery("#trackgroup" + i).length; ++i)
        /* do nothing */;
    $("#trackgroup" + (i - 1)).after("<div id=\"trackgroup" + i + "\" class=\"mg has-fold fold3o\"></div>");
    var $j = jQuery("#trackgroup" + i);
    $j.html(jQuery("#trackgroup0").html().replace(/_track0/g, "_track" + i));
    $j.find(".need-suggest").each(suggest);
    $j.find("input[name^=name]").focus();
});

handle_ui.on("js-settings-copy-topics", function () {
    var topics = [];
    $(this).closest(".has-copy-topics").find("[name^=top]").each(function () {
        topics.push(escape_entities(this.value));
    });
    var node = $("<textarea></textarea>").appendTo(document.body);
    node.val(topics.join("\n"));
    node[0].select();
    document.execCommand("copy");
    node.remove();
});


window.review_round_settings = (function ($) {
var added = 0;

function namechange() {
    var roundnum = this.id.substr(10), name = $.trim($(this).val());
    $("#rev_roundtag_" + roundnum + ", #extrev_roundtag_" + roundnum)
        .text(name === "" ? "(no name)" : name);
}

function add() {
    var i, h, j;
    for (i = 1; $("#roundname_" + i).length; ++i)
        /* do nothing */;
    $("#round_container").show();
    $("#roundtable").append($("#newround").html().replace(/\$/g, i));
    var $mydiv = $("#roundname_" + i).closest(".js-settings-review-round");
    $("#rev_roundtag").append('<option value="#' + i + '" id="rev_roundtag_' + i + '">(new round)</option>');
    $("#extrev_roundtag").append('<option value="#' + i + '" id="extrev_roundtag_' + i + '">(new round)</option>');
    $("#roundname_" + i).focus().on("input change", namechange);
}

function kill() {
    var divj = $(this).closest(".js-settings-review-round"),
        roundnum = divj.data("reviewRoundNumber"),
        vj = divj.find("input[name=deleteround_" + roundnum + "]"),
        ej = divj.find("input[name=roundname_" + roundnum + "]");
    if (vj.val()) {
        vj.val("");
        divj.find(".js-settings-review-round-deleted").remove();
        ej.prop("disabled", false);
        $(this).html("Delete round");
    } else {
        vj.val(1);
        ej.prop("disabled", true);
        $(this).html("Restore round").after('<strong class="js-settings-review-round-deleted" style="padding-left:1.5em;font-style:italic;color:red">&nbsp; Review round deleted</strong>');
    }
    divj.find("table").toggle(!vj.val());
    form_highlight("#settingsform");
}

return function () {
    $("#roundtable input[type=text]").on("input change", namechange);
    $("#settings_review_round_add").on("click", add);
    $("#roundtable").on("click", ".js-settings-review-round-delete", kill);
};
})($);


window.review_form_settings = (function () {
var fieldorder, original, samples, stemplate, ttemplate,
    colors = ["sv", "Red to green", "svr", "Green to red",
              "sv-blpu", "Blue to purple", "sv-publ", "Purple to blue",
              "sv-viridis", "Purple to yellow", "sv-viridisr", "Yellow to purple"];

function get_fid(elt) {
    return elt.id.replace(/^.*_/, "");
}

function unparse_option(fieldj, idx) {
    if (fieldj.option_letter) {
        var cc = fieldj.option_letter.charCodeAt(0);
        return String.fromCharCode(cc + fieldj.options.length - idx);
    } else
        return idx.toString();
}

function options_to_text(fieldj) {
    var i, t = [];
    if (!fieldj.options)
        return "";
    for (i = 0; i != fieldj.options.length; ++i)
        t.push(unparse_option(fieldj, i + 1) + ". " + fieldj.options[i]);
    if (fieldj.option_letter)
        t.reverse();
    if (fieldj.allow_empty)
        t.push("No entry");
    if (t.length)
        t.push(""); // get a trailing newline
    return t.join("\n");
}

function option_class_prefix(fieldj) {
    var sv = fieldj.option_class_prefix || "sv";
    if (fieldj.option_letter)
        sv = colors[(colors.indexOf(sv) || 0) ^ 2];
    return sv;
}

function fill_order() {
    var i, c = $("#reviewform_container")[0], n;
    for (i = 1, n = c.firstChild; n; ++i, n = n.nextSibling)
        $(n).find(".rf_position").val(i);
    c = $("#reviewform_removedcontainer")[0];
    for (n = c.firstChild; n; n = n.nextSibling)
        $(n).find(".rf_position").val(0);
    form_highlight("#settingsform");
}

function fill_field1(sel, value, order) {
    var $j = $(sel).val(value);
    order && $j.attr("data-default-value", value);
}

function fill_field(fid, fieldj, order) {
    fieldj = fieldj || original[fid] || {};
    fill_field1("#rf_" + fid + "_name", fieldj.name || "", order);
    order && fill_field1("#rf_" + fid + "_position", fieldj.position || 0, order);
    fill_field1("#rf_" + fid + "_description", fieldj.description || "", order);
    fill_field1("#rf_" + fid + "_visibility", fieldj.visibility || "pc", order);
    fill_field1("#rf_" + fid + "_options", options_to_text(fieldj), order);
    fill_field1("#rf_" + fid + "_colorsflipped", fieldj.option_letter ? "1" : "", order);
    fill_field1("#rf_" + fid + "_colors", option_class_prefix(fieldj), order);
    fill_field1("#rf_" + fid + "_rounds", (fieldj.round_list || ["all"]).join(" "), order);
    $("#rf_" + fid + " textarea").trigger("change");
    $("#rf_" + fid + "_view").html("").append(create_field_view(fid, fieldj));
    $("#remove_" + fid).html(fieldj.has_any_nonempty ? "Delete from form and current reviews" : "Delete from form");
    return false;
}

function remove() {
    var $f = $(this).closest(".settings-revfield"),
        fid = $f.attr("data-revfield");
    $f.find(".rf_position").val(0);
    $f.detach().hide().appendTo("#reviewform_removedcontainer");
    $("#reviewform_removedcontainer").append('<div id="revfieldremoved_' + fid + '" class="settings-revfieldremoved"><span class="settings-revfn" style="text-decoration:line-through">' + escape_entities($f.find("#rf_" + fid + "_name").val()) + '</span>&nbsp; (field removed)</div>');
    fill_order();
}

var revfield_template = '<div id="rf_$" class="settings-revfield f-contain has-fold fold2c errloc_$" data-revfield="$">\
<a href="" class="q settings-field-folder">\
<span class="expander"><span class="in0 fx2">▼</span><span class="in1 fn2 need-tooltip" data-tooltip="Edit field" data-tooltip-dir="r">▶</span></span>\
</a>\
<div id="rf_$_view" class="settings-revfieldview fn2 ui js-foldup"></div>\
<div id="rf_$_edit" class="settings-revfieldedit fx2">\
  <div class="f-i">\
    <input name="rf_$_name" id="rf_$_name" type="text" size="50" style="font-weight:bold" placeholder="Field name" />\
  </div>\
  <div class="f-horizontal">\
    <div class="f-i">\
      <label for="rf_$_visibility">Visibility</label>\
      <span class="select"><select name="rf_$_visibility" id="rf_$_visibility" class="rf_visibility">\
        <option value="au">Visible to authors</option>\
        <option value="pc">Hidden from authors</option>\
        <option value="audec">Hidden from authors until decision</option>\
        <option value="admin">Administrators only</option>\
      </select></span>\
    </div>\
    <div class="f-i reviewrow_options">\
      <label for="rf_$_colors">Colors</label>\
      <span class="select"><select name="rf_$_colors" id="rf_$_colors" class="rf_colors"></select></span>\
<input type="hidden" name="rf_$_colorsflipped" id="rf_$_colorsflipped" value="" />\
    </div>\
    <div class="f-i reviewrow_rounds">\
      <label for="rf_$_rounds">Rounds</label>\
      <span class="select"><select name="rf_$_rounds" id="rf_$_rounds" class="rf_rounds"></select></span>\
    </div>\
  </div>\
  <div class="f-i">\
    <label for="rf_$_description">Description</label>\
    <textarea name="rf_$_description" id="rf_$_description" class="w-text need-tooltip" rows="2" data-tooltip-info="settings-review-form" data-tooltip-type="focus"></textarea></div>\
  <div class="f-i reviewrow_options">\
    <label for="rf_$_options">Choices</label>\
    <textarea name="rf_$_options" id="rf_$_options" class="w-text need-tooltip" rows="6" data-tooltip-info="settings-review-form" data-tooltip-type="focus"></textarea></div>\
  <div class="f-i">\
    <button id="rf_$_moveup" class="btn-sm rf_moveup" type="button">Move up</button><span class="sep"></span>\
<button id="rf_$_movedown" class="btn-sm rf_movedown" type="button">Move down</button><span class="sep"></span>\
<button id="rf_$_remove" class="btn-sm rf_remove" type="button">Delete from form</button><span class="sep"></span>\
<input type="hidden" name="rf_$_position" id="rf_$_position" class="rf_position" value="0" />\
  </div>\
</div></div>';

var revfieldview_template = '<div style="line-height:1.35">\
<span class="settings-revfn"></span>\
<span class="settings-revrounds"></span>\
<span class="field-visibility"></span>\
<div class="settings-revdata"></div>\
</div>';

tooltip.add_builder("settings-review-form", function (info) {
    return $.extend({
        dir: "h", content: $(/^description/.test(this.name) ? "#review_form_caption_description" : "#review_form_caption_options").html()
    }, info);
});

tooltip.add_builder("settings-option", function (info) {
    var x = "#option_caption_options";
    if (/^optn/.test(this.name))
        x = "#option_caption_name";
    else if (/^optecs/.test(this.name))
        x = "#option_caption_condition_search";
    return $.extend({dir: "h", content: $(x).html(), className: "gray"}, info);
});

function option_value_html(fieldj, value) {
    var t, n;
    if (!value || value < 0)
        return ["", "No entry"];
    t = '<span class="rev_num sv';
    if (value <= fieldj.options.length) {
        if (fieldj.options.length > 1)
            n = Math.floor((value - 1) * 8 / (fieldj.options.length - 1) + 1.5);
        else
            n = 1;
        t += " " + (fieldj.option_class_prefix || "sv") + n;
    }
    return [t + '">' + unparse_option(fieldj, value) + '.</span>',
            escape_entities(fieldj.options[value - 1] || "Unknown")];
}

function view_unfold(event) {
    var $f = $(event.target).closest(".settings-revfield");
    if ($f.hasClass("fold2c") || !form_differs($f))
        foldup.call(event.target, event, {n: 2});
    return false;
}

function field_visibility_text(visibility) {
    if ((visibility || "pc") === "pc")
        return "(hidden from authors)";
    else if (visibility === "admin")
        return "(administrators only)";
    else if (visibility === "secret")
        return "(secret)";
    else if (visibility === "audec")
        return "(hidden from authors until decision)";
    else
        return "";
}

function create_field_view(fid, fieldj) {
    var $f = $(revfieldview_template.replace(/\$/g, fid)), $x, i, j, x;
    $f.find(".settings-revfn").text(fieldj.name || "<unnamed>");

    $x = $f.find(".field-visibility");
    x = field_visibility_text(fieldj.visibility);
    x ? $x.text(x) : $x.remove();

    x = "";
    if ((fieldj.round_list || []).length == 1)
        x = "(" + fieldj.round_list[0] + " only)";
    else if ((fieldj.round_list || []).length > 1)
        x = "(" + commajoin(fieldj.round_list) + ")";
    $x = $f.find(".settings-revrounds");
    x ? $x.text(x) : $x.remove();

    if (fieldj.options) {
        x = [option_value_html(fieldj, 1).join(" "),
             option_value_html(fieldj, fieldj.options.length).join(" ")];
        fieldj.option_letter && x.reverse();
    } else
        x = ["Text field"];
    $f.find(".settings-revdata").html(x.join(" … "));

    return $f;
}

function move_field(event) {
    var isup = $(this).hasClass("rf_moveup"),
        $f = $(this).closest(".settings-revfield").detach(),
        fid = $f.attr("data-revfield"),
        pos = $f.find(".rf_position").val() | 0,
        $c = $("#reviewform_container")[0], $n, i;
    for (i = 1, $n = $c.firstChild;
         $n && i < (isup ? pos - 1 : pos + 1);
         ++i, $n = $n.nextSibling)
        /* nada */;
    $c.insertBefore($f[0], $n);
    fill_order();
}

function append_field(fid, pos) {
    var $f = $("#rf_" + fid), i, $j;
    $("#revfieldremoved_" + fid).remove();

    if ($f.length) {
        $f.detach().show().appendTo("#reviewform_container");
        fill_order();
        return;
    }

    $f = $(revfield_template.replace(/\$/g, fid));

    if (fid.charAt(0) === "s") {
        $j = $f.find(".rf_colors");
        for (i = 0; i < colors.length; i += 2)
            $j.append("<option value=\"" + colors[i] + "\">" + colors[i+1] + "</option>");
    } else
        $f.find(".reviewrow_options").remove();

    var rnames = [];
    for (i in hotcrp_status.revs || {})
        rnames.push(i);
    if (rnames.length > 1) {
        var v, j, text;
        $j = $f.find(".rf_rounds");
        for (i = 0; i < (1 << rnames.length) - 1;
             i = next_lexicographic_permutation(i, rnames.length)) {
            text = [];
            for (j = 0; j < rnames.length; ++j)
                if (i & (1 << j))
                    text.push(rnames[j]);
            if (!text.length)
                $j.append("<option value=\"all\">All rounds</option>");
            else if (text.length == 1)
                $j.append("<option value=\"" + text[0] + "\">" + text[0] + " only</option>");
            else
                $j.append("<option value=\"" + text.join(" ") + "\">" + commajoin(text) + "</option>");
        }
    } else {
        $f.find(".reviewrow_rounds").remove();
    }

    $f.find(".rf_remove").on("click", remove);
    $f.find(".rf_moveup, .rf_movedown").on("click", move_field);
    $f.appendTo("#reviewform_container");

    fill_field(fid, original[fid], true);
    $f.find(".need-tooltip").each(tooltip);
}

function rfs(data) {
    var i, fid, $j;
    original = data.fields;
    samples = data.samples;
    stemplate = data.stemplate;
    ttemplate = data.ttemplate;

    fieldorder = [];
    for (fid in original)
        if (original[fid].position)
            fieldorder.push(fid);
    fieldorder.sort(function (a, b) {
        return original[a].position - original[b].position;
    });

    // construct form
    for (i = 0; i != fieldorder.length; ++i)
        append_field(fieldorder[i], i + 1);
    $("#reviewform_container").on("click", "a.settings-field-folder", view_unfold);
    $("#reviewform_container").on("unfold", ".settings-revfield", function (evt, opts) {
        $(this).find("textarea").css("height", "auto").autogrow();
        $(this).find("input[type=text]").autogrow();
    });

    // highlight errors, apply request
    for (i in data.req || {}) {
        if (!$("#" + i).length)
            rfs.add(false, i.replace(/^.*_/, ""));
        $j = $("#" + i);
        if (!text_eq($j.val(), data.req[i])) {
            $j.val(data.req[i]);
            foldup.call($j[0], null, {n: 2, f: false});
        }
    }
    for (i in data.errf || {}) {
        $j = $("#" + i).closest(".f-i");
        if (!$j.length)
            $j = $(".errloc_" + i);
        $j.addClass("has-error");
        foldup.call($j[0], null, {n: 2, f: false});
    }
    form_highlight("#settingsform");
};

function add_field(fid) {
    fieldorder.push(fid);
    original[fid] = original[fid] || {};
    original[fid].position = fieldorder.length;
    append_field(fid, fieldorder.length);
    foldup.call($("#rf_" + fid)[0], null, {n: 2, f: false});
    $("#rf_" + fid + "_position").attr("data-default-value", "0");
    form_highlight("#settingsform");
    return true;
}

function add_dialog(fid, focus) {
    var $d, template = 0, has_options = fid.charAt(0) === "s";
    function render_template() {
        var $dtn = $d.find(".newreviewfield-template-name"),
            $dt = $d.find(".newreviewfield-template"),
            hc = new HtmlCollector;
        if (!template || !samples[template - 1] || !samples[template - 1].options != !has_options) {
            template = 0;
            $dtn.text("(Blank)");
        } else {
            var s = samples[template - 1];
            $d.find(".newreviewfield-template-name").text(s.selector);
            var hc = new HtmlCollector;
            hc.push('<div><span class="settings-revfn">' + text_to_html(s.name) + '</span>', '<hr class="c" /></div>');
            var x = field_visibility_text(s.visibility);
            if (x)
                hc.push('<span class="field-visibility">' + text_to_html(x) + '</span>');
            hc.pop();
            hc.push('<div class="settings-revhint">' + text_to_html(s.description || "") + '</div>');
            if (s.options) {
                x = [];
                for (var i = 1; i <= s.options.length; ++i)
                    x.push(i);
                if (s.option_letter)
                    x.reverse();
                hc.push('<table class="settings-revoptions"><tbody>', '</tbody></table>');
                for (var i = 0; i < x.length; ++i) {
                    var ov = option_value_html(s, x[i]);
                    hc.push('<tr><td class="nw">' + ov[0] + ' </td>' +
                            '<td>' + ov[1] + '</td></tr>');
                }
                if (s.allow_empty)
                    hc.push('<tr><td colspan="2">No entry</td></tr>');
                hc.pop();
            }
        }
        $dt.html(hc.render());
    }
    function submit(event) {
        add_field(fid);
        template && fill_field(fid, samples[template - 1], false);
        $("#rf_" + fid + "_name")[0].focus();
        $d.close();
        event.preventDefault();
    }
    function click() {
        if (this.name == "next" || this.name == "prev") {
            var dir = this.name == "next" ? 1 : -1;
            template += dir;
            if (template < 0)
                template = samples.length;
            while (template
                   && samples[template - 1]
                   && !samples[template - 1].options !== !has_options)
                template += dir;
            render_template();
        }
    }
    function change_template() {
        ++template;
        while (samples[template - 1] && !samples[template - 1].options != !has_options)
            ++template;
        render_template();
    }
    function create() {
        var hc = popup_skeleton();
        hc.push('<h2>' + (has_options ? "Add score field" : "Add text field") + '</h2>');
        hc.push('<p>Choose a template for the new field.</p>');
        hc.push('<table style="width:500px;max-width:90%;margin-bottom:2em"><tbody><tr>', '</tr></tbody></table>');
        hc.push('<td style="text-align:left"><button name="prev" type="button" class="need-tooltip" data-tooltip="Previous template">&lt;</button></td>');
        hc.push('<td class="newreviewfield-template-name" style="text-align:center"></td>');
        hc.push('<td style="text-align:right"><button name="next" type="button" class="need-tooltip" data-tooltip="Next template">&gt;</button></td>');
        hc.pop();
        hc.push('<div class="newreviewfield-template" style="width:500px;max-width:90%;min-height:6em"></div>');
        hc.push_actions(['<button type="submit" name="add" class="btn-primary want-focus">Create field</button>',
            '<button type="button" name="cancel">Cancel</button>']);
        $d = hc.show();
        render_template();
        $d.find(".newreviewfield-template-name").on("click", change_template);
        $d.on("click", "button", click);
        $d.find("form").on("submit", submit);
    }
    create();
}

rfs.add = function (has_options, fid) {
    if (fid)
        return add_field(fid);
    // prefer recently removed fields
    var i = 0, x = [];
    for (var $n = $("#reviewform_removedcontainer")[0].firstChild;
         $n && $n.hasAttribute("data-revfield"); $n = $n.nextSibling) {
        x.push([$n.getAttribute("data-revfield"), i]);
        ++i;
    }
    // otherwise prefer fields that have ever been defined
    for (fid in original)
        if ($.inArray(fid, fieldorder) < 0) {
            x.push([fid, i + (original[fid].name && original[fid].name !== "Field name" ? 0 : 1000)]);
            ++i;
        }
    // find a field
    x.sort(function (a, b) { return a[1] - b[1]; });
    for (i = 0; i != x.length; ++i)
        if (!has_options === (x[i][0].charAt(0) === "t"))
            return add_dialog(x[i][0]);
    // no field found, so add one
    var ffmt = has_options ? "s%02d" : "t%02d";
    for (i = 1; ; ++i) {
        fid = sprintf(ffmt, i);
        if ($.inArray(fid, fieldorder) < 0)
            break;
    }
    original[fid] = has_options ? stemplate : ttemplate;
    return add_dialog(fid);
};

return rfs;
})();


handle_ui.on("js-settings-resp-round-new", function () {
    var i, j;
    for (i = 1; jQuery("#response_" + i).length; ++i)
        /* do nothing */;
    jQuery("#response_n").before("<div id=\"response_" + i + "\" class=\"form-g\"></div>");
    j = jQuery("#response_" + i);
    j.html(jQuery("#response_n").html().replace(/_n\"/g, "_" + i + "\""));
    j.find("textarea").css({height: "auto"}).autogrow().val(jQuery("#response_n textarea").val());
    j.find(".need-suggest").each(suggest);
    return false;
});
