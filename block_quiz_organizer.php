<?php
/**
 * Quiz Organizer
 *
 * @package quiz_organizer
 * @author  VERSION2 Inc.
 * @version $Id: block_quiz_organizer.php 284 2014-02-26 18:20:41Z yama $
 */

class block_quiz_organizer extends block_base {
    function init() {
        $this->title = get_string('pluginname', 'block_quiz_organizer');
        $this->version = 2012022400;
    }

    function applicable_formats() {
        return array('course' => true);
    }

    function get_content() {
        global $CFG, $COURSE, $OUTPUT;

        if ($this->content !== null) {
            return $this->content;
        }

        $this->content = new stdClass();
        $this->content->text = '';
        $this->content->footer = '';

        $courseid = $COURSE->id;
        if (has_capability('mod/quiz:manage', context_course::instance($courseid))) {
            $modinfo = get_fast_modinfo($courseid);
            $sections = $modinfo->get_section_info_all();
            $sectionmenu = [];
            foreach ($sections as $section) {
                $sectionmenu[$section->id] = get_section_name($courseid, $section);
            }

            $url = new moodle_url('/blocks/quiz_organizer/quiz_organizer.php', ['course' => $courseid]);
            $html = html_writer::tag(
                'div',
                $OUTPUT->action_link(
                    new moodle_url('/blocks/quiz_organizer/quiz_organizer.php',
                                   array('course' => $COURSE->id)),
                    $OUTPUT->pix_icon('i/edit', '')
                    . ' ' . get_string('doquizsetting', 'block_quiz_organizer')))
                .get_string('section')
                .$OUTPUT->single_select($url, 'section', $sectionmenu)
                . html_writer::start_tag(
                    'form',
                    array('action' => $CFG->wwwroot . '/blocks/quiz_organizer/quiz_organizer.php',
                          'method' => 'get'))
                . html_writer::empty_tag(
                    'input',
                    array('type' => 'hidden',
                          'name' => 'course',
                          'value' => $COURSE->id))
                . $OUTPUT->pix_icon('i/search', '')
                . ' ' . get_string('filterbyname', 'block_quiz_organizer')
                . html_writer::empty_tag('br')
                . html_writer::empty_tag(
                    'input',
                    array('type' => 'text',
                          'name' => 'filtername',
                          'size' => 15))
                . html_writer::empty_tag(
                    'input',
                    array('type' => 'submit',
                          'value' => get_string('show')))
                . html_writer::end_tag('form');
            $this->content->text = $html;
        }

        return $this->content;
    }
}
