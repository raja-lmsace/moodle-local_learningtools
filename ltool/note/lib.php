<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * ltool plugin "Learning Tools notes" - library file.
 *
 * @package   ltool_note
 * @copyright bdecent GmbH 2021
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core_user\output\myprofile\tree;
defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/formslib.php');
require_once($CFG->dirroot. '/local/learningtools/lib.php');

/**
 * Define notes form.
 */
class ltool_email_popoutform extends moodleform {
    /**
     * Adds element to form
     */
    public function definition() {
        $mform = $this->_form;
        $course = $this->_customdata['course'];
        $contextlevel = $this->_customdata['contextlevel'];
        $pagetype = $this->_customdata['pagetype'];
        $pageurl = $this->_customdata['pageurl'];
        $user = $this->_customdata['user'];
        $pagetitle = $this->_customdata['pagetitle'];
        $itemtype = $this->_customdata['itemtype'];
        $itemid = $this->_customdata['itemid'];
        $popoutaction = isset($this->_customdata['popoutaction']) ?
        $this->_customdata['popoutaction'] : '';

        $mform->addElement('editor', 'ltnoteeditor', '', ['autosave' => false]);
        $mform->addElement('hidden', 'course');
        $mform->setType('course', PARAM_INT);
        $mform->setDefault('course', $course);
        $mform->addElement('hidden', 'contextlevel');
        $mform->setDefault('contextlevel', $contextlevel);
        $mform->setType('contextlevel', PARAM_INT);

        $mform->addElement('hidden', 'pagetype');
        $mform->setDefault('pagetype', $pagetype);
        $mform->setType('pagetype', PARAM_TEXT);

        $mform->addElement('hidden', 'pagetitle');
        $mform->setDefault('pagetitle', $pagetitle);
        $mform->setType('pagetitle', PARAM_TEXT);

        $mform->addElement('hidden', 'pageurl');
        $mform->setDefault('pageurl', $pageurl);
        $mform->setType('pageurl', PARAM_URL);

        $mform->addElement('hidden', 'user');
        $mform->setDefault('user', $user);
        $mform->setType('user', PARAM_INT);

        $mform->addElement('hidden', 'itemtype');
        $mform->setDefault('itemtype', $itemtype);
        $mform->setType('itemtype', PARAM_TEXT);

        $mform->addElement('hidden', 'itemid');
        $mform->setDefault('itemid', $itemid);
        $mform->setType('itemid', PARAM_INT);

        if ($popoutaction) {
            $this->add_action_buttons();
        }

    }
}

/**
 * Define user edit the notes form.
 */
class ltool_note_info extends moodleform {
    /**
     * Adds element to form
     */
    public function definition() {
        global $DB;

        $mform = $this->_form;
        $noteid = $this->_customdata['id'];
        $courseid = $this->_customdata['courseid'];
        $returnurl = $this->_customdata['returnurl'];

        $note = $DB->get_record('ltool_note_data', ['id' => $noteid]);
        $usernote = !empty($note->note) ? $note->note : '';
        $mform->addElement('editor', 'noteeditor', '')->setValue( ['text' => $usernote]);
        $mform->addElement('hidden', 'edit');
        $mform->setType('edit', PARAM_INT);
        $mform->setDefault('edit', $noteid);
        if ($courseid) {
            $mform->addElement('hidden', 'courseid');
            $mform->setType('courseid', PARAM_INT);
            $mform->setDefault('courseid', $courseid);
        }

        if ($returnurl) {
            $mform->addElement('hidden', 'returnurl');
            $mform->setType('returnurl', PARAM_URL);
            $mform->setDefault('returnurl', $returnurl);
        }
        $mform->addElement('hidden', 'sesskey');
        $mform->setType('sesskey', PARAM_ALPHANUMEXT);
        $mform->setDefault('sesskey', sesskey());
        $this->add_action_buttons();
    }
}
/**
 * Defines the ltool notes nodes for my profile navigation tree.
 *
 * @param \core_user\output\myprofile\tree $tree Tree object
 * @param stdClass $user user object
 * @param bool $iscurrentuser is the user viewing profile, current user ?
 * @param stdClass $course course object
 *
 * @return bool
 */
function ltool_note_myprofile_navigation(tree $tree, $user, $iscurrentuser, $course) {
    global $PAGE, $USER, $DB;
    $userid = optional_param('id', 0, PARAM_INT);
    $context = context_system::instance();
    if (ltool_note_is_note_status()) {
        if ($iscurrentuser) {
            if (!empty($course)) {
                $coursecontext = context_course::instance($course->id);
                $noteurl = new moodle_url('/local/learningtools/ltool/note/list.php',
                    ['courseid' => $course->id, 'userid' => $userid]);
                $notenode = new core_user\output\myprofile\node('learningtools', 'note',
                    get_string('coursenotes', 'local_learningtools'), null, $noteurl);
                $tree->add_node($notenode);
            } else {
                if (has_capability('ltool/note:viewownnote', $context)) {
                    $noteurl = new moodle_url('/local/learningtools/ltool/note/list.php');
                    $notenode = new core_user\output\myprofile\node('learningtools', 'note',
                        get_string('note', 'local_learningtools'), null, $noteurl);
                    $tree->add_node($notenode);
                }
            }
        } else {

            if (local_learningtools_is_parentforchild($user->id, 'ltool/note:viewnote')) {
                $params = ['userid' => $user->id];
                $title = get_string('note', 'local_learningtools');
                if (!empty($course)) {
                    $params['courseid'] = $course->id;
                    $title = get_string('coursenotes', 'local_learningtools');
                }
                $noteurl = new moodle_url('/local/learningtools/ltool/note/list.php', $params);
                $notenode = new core_user\output\myprofile\node('learningtools', 'note', $title, null, $noteurl);
                $tree->add_node($notenode);
                return true;
            } else if (!empty($course) && !empty($userid)) {
                $coursecontext = context_course::instance($course->id);
                if (has_capability('ltool/note:viewnote', $coursecontext)) {
                    $noteurl = new moodle_url('/local/learningtools/ltool/note/list.php',
                        ['courseid' => $course->id, 'userid' => $userid, 'teacher' => 1]);
                    $notenode = new core_user\output\myprofile\node('learningtools', 'note',
                        get_string('coursenotes', 'local_learningtools'), null, $noteurl);
                    $tree->add_node($notenode);
                }
            }

        }
    }
    return true;
}

/**
 * Load the user page notes form
 * @param array $args page arguments
 * @return string Display the html note editor form.
 */
function ltool_note_output_fragment_get_note_form($args) {

    global $PAGE, $COURSE, $USER, $CFG;

    $PAGE->set_url(new moodle_url('/'));

    require_once($CFG->dirroot.'/lib/form/editor.php');
    require_once($CFG->dirroot . '/lib/editorlib.php');
    $editorhtml = '';
    $editor = editors_get_preferred_editor();

    // Generate a unique ID for the editor to prevent content caching.
    $editorid = "usernotes_" . time();
    $editor->use_editor($editorid, ['autosave' => false]);

    $editor->set_text('');

    $editorhtml .= \html_writer::start_tag('div', ['class' => 'ltoolusernotes']);
    $editorhtml .= \html_writer::start_tag('form', ['method' => 'post', 'action' => $args['pageurl'], 'class' => 'mform']);

    $editorhtml .= \html_writer::tag('textarea', '',
        ['id' => $editorid, 'name' => 'ltnoteeditor', 'class' => 'form-group', 'rows' => 20, 'cols' => 100]);

    $editorhtml .= \html_writer::tag('input', '', [
        'type' => 'hidden',
        'name' => 'course',
        'value' => $args['course'],
    ]);

    $editorhtml .= \html_writer::tag('input', '', [
        'type' => 'hidden',
        'name' => 'itemtype',
        'value' => isset($args['itemtype']) ? $args['itemtype'] : '',
    ]);

    $editorhtml .= \html_writer::tag('input', '', [
        'type' => 'hidden',
        'name' => 'itemid',
        'value' => isset($args['itemid']) ? $args['itemid'] : 0,
    ]);

    $editorhtml .= \html_writer::tag('input', '', [
        'type' => 'hidden',
        'name' => 'contextid',
        'value' => $args['contextid'],
    ]);

    $editorhtml .= \html_writer::tag('input', '', [
        'type' => 'hidden',
        'name' => 'contextlevel',
        'value' => $args['contextlevel'],
    ]);

    $editorhtml .= \html_writer::tag('input', '', [
        'type' => 'hidden',
        'name' => 'pagetype',
        'value' => $args['pagetype'],
    ]);

    $editorhtml .= \html_writer::tag('input', '', [
        'type' => 'hidden',
        'name' => 'pagetitle',
        'value' => $args['pagetitle'],
    ]);

    $editorhtml .= \html_writer::tag('input', '', [
        'type' => 'hidden',
        'name' => 'pageurl',
        'value' => $args['pageurl'],
    ]);

    $editorhtml .= \html_writer::tag('input', '', [
        'type' => 'hidden',
        'name' => 'user',
        'value' => $args['user'],
    ]);

    $editorhtml .= \html_writer::end_tag('form');
    $editorhtml .= \html_writer::end_tag('div');
    $editorhtml .= ltool_note_load_context_notes($args);
    return $editorhtml;
}

/**
 * Page user exist notes info.
 * @param array $args page arguments.
 * @return string display the html exist notes list.
 */
function ltool_note_load_context_notes($args) {
    $editorhtml = '';
    $context = context_system::instance();
    if (ltool_note_get_userpage_countnotes($args) && has_capability('ltool/note:viewownnote', $context)) {
        $editorhtml .= \html_writer::start_tag('div', ['class' => 'list-context-existnotes']);
        $editorhtml .= ltool_note_get_contextuser_notes($args);
        $editorhtml .= \html_writer::end_tag('div');
    }
    return $editorhtml;
}

/**
 * Get the user in page context notes info.
 * @param array $args page arguments list.
 * @return string return to html the user notes.
 */
function ltool_note_get_contextuser_notes($args) {
    global $DB, $OUTPUT;
    $context = context_system::instance();
    $reports = [];
    $template = [];
    $listrecords = [];
    $sql = "SELECT * FROM {ltool_note_data}
    WHERE userid = ? AND
    contextid = ? AND ";
    $params = [
        $args['user'],
        $args['contextid'],
    ];
    if (isset($args['itemtype']) && !empty($args['itemtype'])) {
        $sql .= " itemtype = ? AND itemid = ? AND ";
        $params[] = $args['itemtype'];
        $params[] = $args['itemid'];
    } else {
        $sql .= " itemtype = '' AND ";
    }
    $params[] = $args['pageurl'];
    $sql .= $DB->sql_compare_text('pageurl', 255). " = " . $DB->sql_compare_text('?', 255) .
    " ORDER BY timecreated DESC";
    $records = $DB->get_records_sql($sql, $params);
    $cnt = 1;
    if (!empty($records)) {
        foreach ($records as $record) {
            $time = floor($record->timecreated / DAYSECS);
            if (isset($listrecords[$time])) {
                $listrecords[$time]['notesgroup'][] = $record->id;
            } else {
                $listrecords[$time]['notesgroup'] = [$record->id];
            }
        }
        foreach ($listrecords as $time => $listrecord) {
            $res = [];
            $notes = [];
            if (isset($listrecord['notesgroup'])) {
                list($dbsql, $dbparam) = $DB->get_in_or_equal($listrecord['notesgroup'], SQL_PARAMS_NAMED);
                $notesrecords = $DB->get_records_sql("SELECT * FROM {ltool_note_data}
                    WHERE id $dbsql ORDER BY timecreated desc", $dbparam);
                if (!empty($notesrecords)) {
                    foreach ($notesrecords as $note) {
                        $list['note'] = !empty($note->note) ? $note->note : '';
                        $notetime = !empty($note->timemodified) ? $note->timemodified : $note->timecreated;
                        $list['time'] = userdate(($notetime), get_string("baseformat", "local_learningtools"), '', false);
                        if (has_capability('ltool/note:manageownnote', $context)) {
                            $returnparams = ['returnurl' => $args['pageurl']];
                            $list['delete'] = ltool_note_delete_note_record($note, $returnparams);
                            $list['edit'] = ltool_note_edit_note_record($note, $returnparams);
                        }
                        $notes[] = $list;
                    }
                }
                $res['notes'] = $notes;
                $res['title'] = userdate(($time * DAYSECS), get_string('strftimemonthdateyear', 'local_learningtools'), '', false);
                $res['range'] = $cnt.'-block';
                $res['active'] = ($cnt == 1) ? true : false;
            }
            $reports[] = $res;
            $cnt++;
        }
    }
    $template['records'] = $reports;
    $template['usernotes'] = true;
    return $OUTPUT->render_from_template('ltool_note/usernotes', $template);
}

/**
 * Save the user notes.
 * @param int $contextid contextid
 * @param array $data page data
 * @return int save notes status
 */
function ltool_note_user_save_notes($contextid, $data) {
    global $DB, $PAGE, $USER;
    $context = context::instance_by_id($contextid, MUST_EXIST);
    $PAGE->set_context($context);
    if (!PHPUNIT_TEST) {
        if (!confirm_sesskey()) {
            return '';
        }
    }
    $record = new stdclass();
    $record->userid = $USER->id;
    $record->course = $data['course'];
    $record->contextlevel = $data['contextlevel'];
    $record->contextid = $contextid;
    if ($record->contextlevel == CONTEXT_MODULE) {
        $record->coursemodule = local_learningtools_get_coursemodule_id($record);
    } else {
        $record->coursemodule = 0;
    }
    $record->pagetitle = $data['pagetitle'];
    $record->pagetype = $data['pagetype'];
    $record->pageurl = $data['pageurl'];
    $itemtype = isset($data['itemtype']) ? $data['itemtype'] : '';
    $itemid = isset($data['itemid']) ? $data['itemid'] : 0;
    $record->itemtype = $itemtype;
    $record->itemid = $itemid;
    $record->note = format_text($data['ltnoteeditor'], FORMAT_HTML);
    $record->timecreated = time();

    $notesrecord = $DB->insert_record('ltool_note_data', $record);
    $eventcourseid = local_learningtools_get_eventlevel_courseid($context, $data['course']);
    // Add event to user create the note.
    $event = \ltool_note\event\ltnote_created::create([
        'objectid' => $notesrecord,
        'courseid' => $eventcourseid,
        'context' => $context,
        'other' => [
            'pagetype' => $data['pagetype'],
        ],
    ]);
    $event->trigger();

    $sql = "SELECT COUNT(*)
    FROM {ltool_note_data}
    WHERE " . $DB->sql_compare_text('pageurl', 255). " = " . $DB->sql_compare_text('?', 255) ."
    AND pagetype = ?
    AND userid = ?";
    $params = [
        $data['pageurl'],
        $data['pagetype'],
        $data['user'],
    ];

    if (!empty($itemtype)) {
        $sql .= " AND itemtype = ? AND itemid = ?";
        $params[] = $itemtype;
        $params[] = $itemid;
    }

    $pageusernotes = $DB->count_records_sql($sql, $params);
    return $pageusernotes;
}

/**
 * Get notes edit records
 * @param object $row record
 * @param array $params page url params
 * @return string edit note html
 */
function ltool_note_edit_note_record($row, $params = []) {
    global $OUTPUT;
    $stredit = get_string('edit');
    $buttons = [];
    $returnurl = new moodle_url('/local/learningtools/ltool/note/editlist.php');
    $optionyes = ['edit' => $row->id, 'sesskey' => sesskey()];
    $optionyes = array_merge($optionyes, $params);
    $url = new moodle_url($returnurl, $optionyes);
    $buttons[] = \html_writer::link($url, $OUTPUT->pix_icon('t/edit', $stredit));
    $buttonhtml = implode(' ', $buttons);
    return $buttonhtml;

}

/**
 * Get notes delete records
 * @param object $row note record
 * @param array $params page url params
 * @return string delete note html
 */
function ltool_note_delete_note_record($row, $params = []) {

    global $OUTPUT;
    $strdelete = get_string('delete');
    $buttons = [];
    $returnurl = new moodle_url('/local/learningtools/ltool/note/deletelist.php');
    $optionyes = ['delete' => $row->id, 'sesskey' => sesskey()];
    $optionyes = array_merge($optionyes, $params);
    $url = new moodle_url($returnurl, $optionyes);
    $buttons[] = \html_writer::link($url, $OUTPUT->pix_icon('t/delete', $strdelete));
    $buttonhtml = implode(' ', $buttons);
    return $buttonhtml;
}
/**
 * Access the delete note user capability.
 * @param int $id note id.
 * @return bool|string return status.
 */
function ltool_note_require_deletenote_cap($id) {
    global $DB, $USER;

    $context = context_system::instance();
    $returnurl = new moodle_url('/my');
    $currentrecord = $DB->get_record('ltool_note_data', ['id' => $id]);
    if (!empty($currentrecord)) {
        if ($currentrecord->userid == $USER->id) {
            if (has_capability('ltool/note:manageownnote', $context)) {
                return true;
            }
        } else {
            if (has_capability('ltool/note:managenote', $context)) {
                return true;
            }
        }
    }
    return redirect($returnurl);
}

/**
 * Get the user pagenotes
 * @param array $args page info
 * @return int page user notes.
 */
function ltool_note_get_userpage_countnotes($args) {
    global $DB;
    $sql = "SELECT COUNT(*)
        FROM {ltool_note_data}
        WHERE " . $DB->sql_compare_text('pageurl', 255). " = " . $DB->sql_compare_text('?', 255) ."
        AND pagetype = ?
        AND userid = ?";
    $params = [
        $args['pageurl'],
        $args['pagetype'],
        $args['user'],
    ];
    if (isset($args['itemtype']) && !empty($args['itemtype'])) {
        $sql .= " AND itemtype = ? AND itemid = ?";
        $params[] = $args['itemtype'];
        $params[] = $args['itemid'];
    } else {
        $sql .= " AND itemtype = '' AND itemid = 0";
    }
    return $DB->count_records_sql($sql, $params);
}

/**
 * Check capability to show notes.
 * @return bool notes status
 */
function ltool_note_check_view_notes() {
    $viewnote = false;
    $context = context_system::instance();
    if (has_capability('ltool/note:viewownnote', $context) && ltool_note_is_note_status()) {
        $viewnote = true;
    }
    return $viewnote;
}

/**
 * Load notes js files.
 * @return void
 */
function ltool_note_load_js_config() {
    global $COURSE, $PAGE, $USER, $CFG;
    // Create config data.
    $config = [
        'course' => $COURSE->id,
        'contextlevel' => $PAGE->context->contextlevel,
        'pagetype' => $PAGE->pagetype,
        'pagetitle' => $PAGE->title,
        'pageurl' => local_learningtools_clean_mod_assign_userlistid($PAGE->url->out(false), $PAGE->cm),
        'user' => $USER->id,
        'contextid' => $PAGE->context->id,
        'title' => $PAGE->title,
        'heading' => $PAGE->heading,
        'sesskey' => sesskey(),
        'noteheading' => get_string('mynotes', 'local_learningtools'),
    ];

    // Add theme URL if needed.
    if (isset($CFG->theme)) {
        $themeconfig = theme_config::load($CFG->theme);
        $themeurls = $themeconfig->css_urls($PAGE);
        if (!empty($themeurls)) {
            $config['themeurl'] = $themeurls[0]->out(false);
        }
    }

    // Set the configuration for the module.
    $PAGE->requires->js_call_amd('ltool_note/learningnote', 'init', [$PAGE->context->id]);

    // Register the configuration.
    $PAGE->requires->data_for_js('ltool_note_config', $config);
}

/**
 * Learning tools template function.
 * @param array $templatecontent template content
 * @return string display html content.
 */
function ltool_note_render_template($templatecontent) {
    global $OUTPUT;
    return $OUTPUT->render_from_template('ltool_note/note', $templatecontent);
}

/**
 * Check the note status.
 * @return bool
 */
function ltool_note_is_note_status() {
    global $DB;
    $noterecord = $DB->get_record('local_learningtools_products', ['shortname' => 'note']);
    if (isset($noterecord->status) && !empty($noterecord->status)) {
        return true;
    }
    return false;
}
/**
 * Check the note view capability.
 * @return bool|redirect status
 */
function ltool_note_require_note_status() {
    if (!ltool_note_is_note_status()) {
        $url = new moodle_url('/my');
        redirect($url);
    }
    return true;
}

/**
 * Delete the course notes.
 * @param int $courseid course id.
 */
function ltool_note_delete_course_note($courseid) {
    global $DB;
    if ($DB->record_exists('ltool_note_data', ['course' => $courseid])) {
        $DB->delete_records('ltool_note_data', ['course' => $courseid]);
    }
}

/**
 * Delete the course notes.
 * @param int $module course moudleid
 */
function ltool_note_delete_module_note($module) {
    global $DB;

    if ($DB->record_exists('ltool_note_data', ['coursemodule' => $module])) {
        $DB->delete_records('ltool_note_data', ['coursemodule' => $module]);
    }
}

/**
 * Get the Notes course module include with section.
 * @param object $data instance of the page.
 * @param object $record notes record
 * @return string instance of coursemodule name.
 */
function ltool_note_get_module_coursesection($data, $record) {
    $coursename = local_learningtools_get_course_name($data->courseid);
    $section = local_learningtools_get_mod_section($data->courseid, $data->coursemodule);
    $modulename = $record->pagetitle;
    return $coursename.' / '. $section. ' / '. $modulename;
}

/**
 * Get the Notes content designer chapter name include with section.
 * @param object $data instance of the page.
 * @param object $record notes record
 * @return string instance of chapter name.
 */
function local_learningtools_get_chapter_name($data, $record) {
    global $DB;
    $coursename = local_learningtools_get_course_name($data->courseid);
    $section = local_learningtools_get_mod_section($data->courseid, $data->coursemodule);
    $chaptertitle = '';
    if ($chapter = $DB->get_record('cdelement_chapter', ['id' => $record->itemid])) {
        $chaptertitle = (!empty($chapter->title) ? $chapter->title : '');
    }
    $modulename = $record->pagetitle . " | " . $chaptertitle;
    return $coursename.' / '. $section. ' / '. $modulename;
}

/**
 * Get the notes content for the nots listing page.
 * @param object $args
 * @return string
 */
function ltool_note_output_fragment_get_notes_contents($args) {
    global $PAGE;

    $pageurl = new \moodle_url($args['pageurl']);;
    $courseid = $pageurl->get_param('id');
    $filter = $pageurl->get_param('filter');

    if ($filter == 'section') {
        $sectionid = $pageurl->get_param('sectionid');
    } else if ($filter == 'activity') {
        $activity = $pageurl->get_param('activity');
    }

    $output = $PAGE->get_renderer('local_learningtools');
    $noteslist = new \ltool_note\output\notes_list($courseid, $sectionid, $activity, '', $filter, true);
    return $output->render($noteslist);
}

/**
 * Fragment output for notes list with search functionality.
 * @param array $args
 * @return string
 */
function ltool_note_output_fragment_get_notes_list($args) {
    global $PAGE;

    $args = (object) $args;
    $context = $args->context;

    $PAGE->set_context($context);

    $courseid = $args->courseid ?? 0;
    $search = $args->search ?? '';
    $sectionid = $args->sectionid ?? 0;
    $activity = $args->activity ?? 0;
    $filter = $args->filter ?? '';
    $print = $args->print ?? false;

    $noteslist = new \ltool_note\output\notes_list($courseid, $sectionid, $activity, $search, $filter, $print);
    $renderer = $PAGE->get_renderer('local_learningtools');

    return $renderer->render($noteslist);
}
