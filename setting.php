<?php
/**
 * Quiz Organizer
 *
 * @package quiz_organizer
 * @author  VERSION2 Inc.
 * @version $Id: setting.php 196 2013-03-13 02:57:35Z yama $
 */

namespace quizorganizer;

require_once '../../config.php';
require_once $CFG->dirroot.'/blocks/quiz_organizer/locallib.php';

class page_setting extends page {
    public function execute() {
        $this->process();
        $this->view();
    }

    private function view() {
        global $CFG, $PAGE, $OUTPUT;

        $courseid = $this->course->id;

        $formitems = form_config::get_items();

        $title = get_string('pluginname', 'block_quiz_organizer');
        $PAGE->set_title($title);
        $PAGE->set_heading($title);

        if ($setting = $this->qo->get_setting()) {
            $activecolumns = explode(',', $setting->activecolumns);
            $conditionrepeat = $setting->conditionrepeat;
        } else {
            $activecolumns = explode(',', quizorganizer::DEFAULTCOLUMNS);
            $conditionrepeat = quizorganizer::DEFAULTCONDITIONREPEAT;
        }

        $PAGE->set_title(get_string('title', 'block_quiz_organizer'));

        $PAGE->navbar->add(get_string('title', 'block_quiz_organizer'),
                new \moodle_url('/blocks/quiz_organizer/quiz_organizer.php', array('course' => $this->course->id)));
        $PAGE->navbar->add(get_string('columnsetting', 'block_quiz_organizer'));

        $PAGE->requires->js_init_call('M.block_quiz_organizer.init_setting');

        echo $OUTPUT->header();
        echo $OUTPUT->heading(get_string('columnsetting', 'block_quiz_organizer'));

        echo '
  <form action="setting.php" method="post">
    <input type="hidden" name="data" value="1"/>
    <input type="hidden" name="course" value="' . $courseid . '"/>';
        $submitbuttons = $OUTPUT->container('
    <input type="submit" value="' . get_string('set', 'block_quiz_organizer') . '"/>
    <input type="submit" name="cancel" value="' . get_string('cancel') . '"/>
                ', 'settingformbuttons');
        echo $submitbuttons;

        $table = new \html_table();
        $table->attributes = array('class' => 'generaltable boxaligncenter');
        $table->head = array(
                \html_writer::checkbox('checkall', 1, false, '', array('id' => 'checkall')),
                get_string('columnstodisplayandedit', 'block_quiz_organizer')
        );

        foreach ($formitems as $item) {
            if ($item->is_group()) {
                $label = \html_writer::tag('strong', $item->label);
            } else {
                $label = $item->label;
            }

            $check = '';
            if (!$item->is_group()) {
                $checked = false;
                if (in_array($item->name, $activecolumns)) {
                    $checked = true;
                }
                $check = \html_writer::checkbox($item->name, 1, $checked, '',
                        array('class' => 'settingcheckbox'));
            }

            $table->data[] = array($check, $label);
        }

        echo \html_writer::table($table);

        echo $submitbuttons;
        echo '</form>';

        echo $OUTPUT->footer();
    }

    private function process() {
        global $DB, $USER;

        $formitems = form_config::get_items();
        $courseid = $this->course->id;

        if (optional_param('data', 0, PARAM_BOOL)) {
            if (!optional_param('cancel', 0, PARAM_BOOL)) {
                $activecolumns = array();
                foreach ($formitems as $item) {
                    if (!$item->is_group()
                            && optional_param($item->name, 0, PARAM_BOOL)) {
                        $activecolumns[] = $item->name;
                    }
                }

                if ($setting = $this->qo->get_setting()) {
                    $setting->activecolumns = implode(',', $activecolumns);
                    $setting->conditionrepeat = optional_param('conditionrepeat', quizorganizer::DEFAULTCONDITIONREPEAT, PARAM_INT);
                    $DB->update_record('block_quiz_organizer', $setting);
                } else {
                    $setting = new \stdClass();
                    $setting->course = $courseid;
                    $setting->userid = $USER->id;
                    $setting->activecolumns = implode(',', $activecolumns);
                    $setting->conditionrepeat = optional_param('conditionrepeat', quizorganizer::DEFAULTCONDITIONREPEAT, PARAM_INT);
                    $DB->insert_record('block_quiz_organizer', $setting);
                }
            }

            redirect(new \moodle_url('quiz_organizer.php',
            array('course' => $courseid/*, 'filtername' => $filtername*/)));
        }
    }
}

$page = new page_setting('/blocks/quiz_organizer/setting.php');
$page->execute();
