<?php
/**
 * Quiz Organizer
 *
 * @package quiz_organizer
 * @author  VERSION2 Inc.
 * @version $Id: locallib.php 295 2014-03-18 03:04:31Z yama $
 */

namespace quizorganizer;

require_once $CFG->dirroot . '/blocks/quiz_organizer/formitems.php';

class quizorganizer {
    const ITEMTYPE_ITEM = 0;
    const ITEMTYPE_GROUP = 1;

    const START_YEAR = 2008;
    const END_YEAR = 2020;

    const DEFAULTCOLUMNS = 'timeopen,timeclose,timelimit';
    const DEFAULTCONDITIONREPEAT = 3;
    const DEFAULTFEEDBACKREPEAT = 3;
    const DEFAULTUSERFIELDREPEAT = 3;

    /**
     *
     * @var \stdClass
     */
    public $course;

    public function __construct(\stdClass $course) {
        $this->course = $course;
    }

    /**
     *
     * @return \stdClass
     */
    public function get_setting() {
        global $DB, $USER;

        return $DB->get_record('block_quiz_organizer',
                array('course' => $this->course->id, 'userid' => $USER->id));
    }


    /**
     * @param int $start
     * @param int $end
     * @param mixed $default
     * @param string $format
     * @param int $step
     * @return string
     */
    public static function make_options($start, $end, $default = null, $format = '%d', $step = 1) {
        $html = '';
        for ($i = $start; $i <= $end; $i += $step) {
            if ($i == $default) {
                $selected = ' selected="selected"';
            } else {
                $selected = '';
            }

            if (is_array($format)) {
                $html .= '<option value="' . $i . '"' . $selected . '>' . $format[$i] . '</option>';
            } else {
                $html .= '<option value="' . $i . '"' . $selected . '>' . sprintf($format, $i)
                    . '</option>';
            }
        }

        return $html;
    }

    public static function get_date_selector($eltnam, $value = null, $dateorder = null, $checkboxforno = false) {
        $disabled='';
        if ($value == 0) {
            $value = time();
            $checked = ' checked="checked"';
        } else {
            $checked = '';
        }
        $tm = localtime($value, true);

        for ($i=1; $i<=12; $i++) {
            $months[$i] = userdate(gmmktime(12,0,0,$i,15,2000), '%b');
        }
        $select = array(
            'Y' => '<select name="' . $eltnam . '_year" id="' . $eltnam . '_year"' . $disabled . '>'
            . quizorganizer::make_options(1970, 2020, $tm['tm_year'] + 1900)
            . '</select> ',
            'M' => '<select name="' . $eltnam . '_month" id="' . $eltnam . '_month"' . $disabled . '>'
            . quizorganizer::make_options(1, 12, $tm['tm_mon'] + 1, $months)
            . '</select> ',
            'D' => '<select name="' . $eltnam . '_day" id="' . $eltnam . '_day"' . $disabled . '>'
            . quizorganizer::make_options(1, 31, $tm['tm_mday'])
            . '</select> ',
            'h' => '<select name="' . $eltnam . '_hour" id="' . $eltnam . '_hour"' . $disabled . '>'
            . quizorganizer::make_options(0, 23, $tm['tm_hour'], '%02d')
            . '</select> ',
            'm' => '<select name="' . $eltnam . '_min" id="' . $eltnam . '_min"' . $disabled . '>'
            . quizorganizer::make_options(
                0, 59, floor($tm['tm_min'] / 5) * 5, '%02d', 5)
            . '</select> ');
        if (empty($dateorder)) {
            $dateorder = get_string('dateorder', 'block_quiz_organizer');
        }
        $html = '';
        for ($i = 0; $i < strlen($dateorder); $i++) {
            $html .= $select[$dateorder[$i]];
        }

        if ($checkboxforno) {
            $html .= '<label><input type="checkbox" name="' . $eltnam . '_none" id="' . $eltnam . '_none"' . $checked . '/> ' . get_string('no') . '</label>';
        }

        return $html;
    }

    /**
     * @param object $quiz
     */
    public static function add_event($quiz) {
        quiz_update_events($quiz);

        // From course/mod.php
        $cm = get_coursemodule_from_instance('quiz', $quiz->id);
        add_to_log($quiz->course, "course", "update mod", "../mod/quiz/view.php?id=$cm->id", "quiz $quiz->id");
        add_to_log($quiz->course, 'quiz', "update", "view.php?id=$cm->id", $quiz->id, $cm->id);
    }

    public static function set_grade_item_category() {
        $items = \grade_item::fetch_all(array('itemtype'=>'mod', 'itemmodule'=>$fromform->modulename,
                                             'iteminstance'=>$fromform->instance, 'courseid'=>$course->id));

        // create parent category if requested and move to correct parent category
        if ($items and isset($fromform->gradecat)) {
            if ($fromform->gradecat == -1) {
                $grade_category = new grade_category();
                $grade_category->courseid = $course->id;
                $grade_category->fullname = $fromform->name;
                $grade_category->insert();
                if ($grade_item) {
                    $parent = $grade_item->get_parent_category();
                    $grade_category->set_parent($parent->id);
                }
                $fromform->gradecat = $grade_category->id;
            }
            foreach ($items as $itemid=>$unused) {
                $items[$itemid]->set_parent($fromform->gradecat);
                if ($itemid == $grade_item->id) {
                    // use updated grade_item
                    $grade_item = $items[$itemid];
                }
            }
        }
    }

    /**
     *
     * @param array[object] $oldfeedback
     * @param array[string] $newfeedbacktexts
     * @param array[float] $newfeedbackboundaries
     */
    public static function check_feedback_modified($oldfeedback, $newfeedbacktexts, $newfeedbackboundaries) {
        $feedbackrepeat = self::DEFAULTFEEDBACKREPEAT;

        //     $prevboundary = 100;
        //     for ($i = 0; $i < $feedbackrepeat - 1; $i++) {
        //         if ($newfeedbackboundaries[$i] >= $prevboundary)
            //             return false;
            //         $prevboundary = $newfeedbackboundaries[$i];
            //     }

            if (count(array_filter($newfeedbacktexts)) != count($oldfeedback))
                return true;

            for ($i = 0; $i < $feedbackrepeat; $i++) {
                if (strip_tags($oldfeedback[$i]->feedbacktext) != $newfeedbacktexts[$i]
                        || $i < $feedbackrepeat - 1 && $oldfeedback[$i]->mingrade != $newfeedbackboundaries[$i])
                    return true;
            }
        }

    /**
     * @param array $oldconditions
     * @param string $eltname
     * @return boolean
     */
    public static function check_condition_modified($oldconditions, $eltname) {
        global $DB;

        if (is_array($_POST[$eltname])) {
            foreach ($_POST[$eltname] as $newcondition) {
                if ($newcondition['conditiongradeitemid']) {
                    $sameconditionfound = false;
                    foreach ($oldconditions as $oldcondition) {
                        if ($oldcondition->gradeitemid == $newcondition['conditiongradeitemid']
                            && $oldcondition->grademin == $newcondition['conditiongrademin']
                            && $oldcondition->grademax == $newcondition['conditiongrademax']) {
                            $sameconditionfound = true;
                            break;
                        }
                    }
                    if (!$sameconditionfound) {
                        return true;
                    }
                }
            }

            foreach ($oldconditions as $oldcondition) {
                $sameconditionfound = false;
                foreach ($_POST[$eltname] as $newcondition) {
                    if ($oldcondition->gradeitemid == $newcondition['conditiongradeitemid']
                        && $oldcondition->grademin == $newcondition['conditiongrademin']
                        && $oldcondition->grademax == $newcondition['conditiongrademax']) {
                        $sameconditionfound = true;
                        break;
                    }
                }
                if (!$sameconditionfound) {
                    return true;
                }
            }
        }

        return false;
    }

    public static function penalty_enabled() {
        return class_exists('local_quizpenalty\quizpenalty');
    }

    public static function get_penalties($quizid) {
        global $DB;

        if ($rows = $DB->get_records('local_quizpenalty', array('quiz' => $quizid),
            'rulenumber')) {
            foreach ($rows as $row) {
                $penalties[$row->rulenumber] = $row;
            }

            return $penalties;
        }
        return false;
    }

    public static function get_new_penalties($quizid) {
        $eltprefix = 'q_' . $quizid . '_';

        $eltnam = $eltprefix . 'penaltylimitdate';
        $newlimitdate = mktime(
                optional_param($eltnam . '_hour', 0, PARAM_INT),
                optional_param($eltnam . '_min', 0, PARAM_INT),
                0,
                optional_param($eltnam . '_month', 0, PARAM_INT),
                optional_param($eltnam . '_day', 0, PARAM_INT),
                optional_param($eltnam . '_year', 0, PARAM_INT));
        $newshowlimitdate = optional_param($eltprefix . 'penaltyshowlimitdate', 0,
                PARAM_BOOL);

        for ($i = 0; $i < 4; $i++) {
            $penalties[$i] = new \stdClass();
            $penalties[$i]->quiz = $quizid;
            $penalties[$i]->rulenumber = $i;
            $penalties[$i]->limitdate = $newlimitdate;
            $penalties[$i]->daysafter
            = optional_param($eltprefix . 'penaltydays_' . $i, 0, PARAM_INT);
            $penalties[$i]->percentage
            = optional_param($eltprefix . 'penaltypercent_' . $i, 0, PARAM_INT);
            $penalties[$i]->penaltytype
            = optional_param($eltprefix . 'penaltytype_' . $i, 0, PARAM_ALPHA);
            $penalties[$i]->enabled
            = optional_param($eltprefix . 'penaltyenable_' . $i, 0, PARAM_BOOL);
            $penalties[$i]->showlimitdate = $newshowlimitdate;
        }

        return $penalties;
    }

    public static function compare_penalties($penalties, $newpenalties) {
        for ($i = 0; $i < 4; $i++) {
            if ($penalties[$i]->daysafter != $newpenalties[$i]->daysafter
                    || $penalties[$i]->percentage != $newpenalties[$i]->percentage
                    || $penalties[$i]->penaltytype != $newpenalties[$i]->penaltytype
                    || $penalties[$i]->enabled != $newpenalties[$i]->enabled
                    || $penalties[$i]->showlimitdate != $newpenalties[$i]->showlimitdate) {
                return true;
            }
        }
        return false;
    }

    /**
     *
     * @param \stdClass|int $courseorid
     * @return string[]
     */
    public static function get_course_section_options($courseorid) {
        global $DB;

        if (is_object($courseorid)) {
            $course = $courseorid;
        } else {
            $course = get_course($courseorid);
        }
        $modinfo = get_fast_modinfo($course);

        $sections = $modinfo->get_section_info_all();
        $sectionoptions = [];
        foreach ($sections as $section) {
            $sectionoptions[$section->id] = get_section_name($course, $section);
        }

        return $sectionoptions;
    }
}

abstract class page {
    /**
     *
     * @var \stdClass
     */
    protected $course;
    /**
     *
     * @var \moodle_url
     */
    protected $url;
    /**
     *
     * @var quizorganizer
     */
    protected $qo;

    /**
     *
     * @param string $url
     */
    public function __construct($url) {
        global $DB, $PAGE;

        $courseid = optional_param('course', 0, PARAM_INT);
        require_login($courseid);
        require_capability('mod/quiz:manage', \context_course::instance($courseid));

        $this->url = new \moodle_url($url, array('course' => $courseid));
        $PAGE->set_url($this->url);

        if (!($this->course = $DB->get_record('course', array('id' => $courseid)))) {
            print_error(get_string('choosecourse'));
        }

        $this->qo = new quizorganizer($this->course);
    }

    public abstract function execute();
}


abstract class form_group_item {
    const ITEM = 0;
    const GROUP = 1;

    /**
     * @var string
     */
    public $name;
    /**
     * @var string
     */
    public $label;
    /**
     * @var string
     */
    public $type;
    /**
     * @var array
     */
    public $options;
    /**
     * @var string
     */
    public $table;
    /**
     * @var string
     */
    public $dbcolumn;

    /**
     *
     * @return boolean
     */
    public function is_group() {
        return $this instanceof form_group;
    }

    /**
     *
     * @return array
     */
    public function to_array() {
        $a = array();

        if ($this->is_group()) {
            $a['type'] = self::GROUP;
        } else {
            $a['type'] = self::ITEM;
        }

        $a['name'] = $this->name;
        $a['label'] = $this->label;
        if ($this->type) {
            $a['columntype'] = $this->type;
        }
        if ($this->options) {
            $a['options'] = $this->options;
        }
        if ($this->table) {
            $a['table'] = $this->table;
        }
        $a['dbcolumn'] = $this->dbcolumn;

        return $a;
    }
}

class form_group extends form_group_item {
    /**
     *
     * @param string $name
     * @param string $label
     */
    public function __construct($name, $label) {
        $this->name = $name;
        $this->label = $label;
    }
}

abstract class form_item extends form_group_item {
    /**
     *
     * @var boolean
     */
    public $isbatchrow;
    /**
     *
     * @var \stdClass
     */
    protected $quiz;
    /**
     *
     * @var \stdClass
     */
    protected $cm;

    /**
     *
     * @param string $name
     * @param string $label
     * @param string $type
     * @param array $options
     * @param string $table
     */
    public function __construct($name, $label, $options = null, $table = 'quiz', $dbcolumn = null) {
        $this->name = $name;
        $this->label = $label;
        $this->options = $options;
        $this->type = substr(get_class($this), 24);

        if ($table) {
            $this->table = $table;
        } else {
            $this->table = 'quiz';
        }

        if ($dbcolumn) {
            $this->dbcolumn = $dbcolumn;
        } else {
            $this->dbcolumn = $this->name;
        }
    }

    public function set_data(\stdClass $quiz, \stdClass $cm) {
        $this->quiz = $quiz;
        $this->cm = $cm;
    }

    public function unset_data() {
        $this->quiz = null;
        $this->cm = null;
    }

    /**
     *
     * @return boolean
     */
    public function is_complex_value() {
        return false;
    }

    /**
     *
     * @return string
     */
    public function get_value() {
        if ($this->table == 'quiz' && $this->quiz) {
            return $this->quiz->{$this->dbcolumn};
        }
        if ($this->table == 'cm' && $this->cm) {
            return $this->cm->{$this->dbcolumn};
        }
        return null;
    }

    /**
     *
     * @return string
     */
    public function get_quiz_id() {
        if ($this->quiz) {
            return $this->quiz->id;
        }
        return 'batch';
    }

    /**
     *
     * @return string
     */
    public function get_element_name() {
        if ($this->quiz) {
            $id = $this->quiz->id;
        } else {
            $id = 'batch';
        }
        return implode('_', array('q', $id, $this->name));
    }

    /**
     *
     * @return string
     */
    public function get_form() {
        $eltname = $this->get_element_name();
        return \html_writer::empty_tag('input', array(
                'name' => $eltname,
                'id' => $eltname,
                'size' => 10,
                'value' => $this->get_value()
        ));
    }

    /**
     *
     * @return string
     */
    public function get_new_value() {
        return optional_param($this->get_element_name(), '', PARAM_TEXT);
    }
}

class form_item_text extends form_item {

}

class form_item_select extends form_item {
    /**
     * @param string $name
     * @param string $label
     * @param string $type
     * @param array $options
     * @param string $table
     */
    public function __construct($name, $label, $options = null, $table = 'quiz', $dbcolumn = null) {
        if (!is_array($options)) {
            throw new \coding_exception("$name のオプションが不正 ($options)");
        }

        parent::__construct($name, $label, $options, $table, $dbcolumn);
    }

    /**
     * @return string
     */
    public function get_form() {
        $eltname = $this->get_element_name();
        return \html_writer::select($this->options, $eltname, $this->get_value(), false, array(
                'id' => $eltname
        ));
    }
}

class form_item_selectyesno extends form_item_select {
    /**
     *
     * @var array
     */
    private static $yesnooptions;

    /**
     * @param string $name
     * @param string $label
     * @param string $type
     * @param array $options
     * @param string $table
     */
    public function __construct($name, $label, $options = null, $table = 'quiz', $dbcolumn = null) {
        if (!self::$yesnooptions) {
            self::$yesnooptions = array(
                    0 => get_string('no'),
                    1 => get_string('yes')
            );
        }

        parent::__construct($name, $label, self::$yesnooptions, $table, $dbcolumn);
    }
}

class form_item_datetime extends form_item {
    /**
     * @return string
     */
    public function get_form() {
        $value = $this->get_value();
        $eltname = $this->get_element_name();

        if ($value == 0) {
            $value = time();
//             $disabled = ' disabled="disabled"';
            $disabled = '';
            $checked = true;
        } else {
            $disabled = '';
            $checked = false;
        }
        $tm = localtime($value, true);

        for ($i=1; $i<=12; $i++) {
            $months[$i] = userdate(gmmktime(12,0,0,$i,15,2000), '%b');
        }
        $select = array(
                'Y' => '<select name="' . $eltname . '_year" id="' . $eltname . '_year"' . $disabled . '>'
                . quizorganizer::make_options(
                        quizorganizer::START_YEAR, quizorganizer::END_YEAR,
                        $tm['tm_year'] + 1900)
                . '</select> ',
                'M' => '<select name="' . $eltname . '_month" id="' . $eltname . '_month"' . $disabled . '>'
                . quizorganizer::make_options(1, 12, $tm['tm_mon'] + 1, $months)
                . '</select> ',
                'D' => '<select name="' . $eltname . '_day" id="' . $eltname . '_day"' . $disabled . '>'
                . quizorganizer::make_options(1, 31, $tm['tm_mday'])
                . '</select> ',
                'h' => '<select name="' . $eltname . '_hour" id="' . $eltname . '_hour"' . $disabled . '>'
                . quizorganizer::make_options(0, 23, $tm['tm_hour'], '%02d')
                . '</select> ',
                'm' => '<select name="' . $eltname . '_min" id="' . $eltname . '_min"' . $disabled . '>'
                . quizorganizer::make_options(0, 59, $tm['tm_min'], '%02d')
                . '</select> ');
        $dateorder = get_string('dateorder', 'block_quiz_organizer');
        $html = '';
        for ($i = 0; $i < strlen($dateorder); $i++) {
            $html .= $select[$dateorder[$i]];
        }
        $html .= \html_writer::checkbox($eltname.'_none', 1, $checked, get_string('no'), array(
                'class' => 'datetime-no',
                'id' => $eltname.'_none',
                'data-prefix' => '#'.$eltname
        ));

        return $html;
    }

    /**
     * @return string
     */
    public function get_new_value() {
        $eltname = $this->get_element_name();
        if (optional_param($eltname . '_none', 0, PARAM_BOOL)) {
            $newvalue = 0;
        } else {
            $t = mktime(
                    optional_param($eltname . '_hour', 0, PARAM_INT),
                    optional_param($eltname . '_min', 0, PARAM_INT),
                    0,
                    optional_param($eltname . '_month', 0, PARAM_INT),
                    optional_param($eltname . '_day', 0, PARAM_INT),
                    optional_param($eltname . '_year', 0, PARAM_INT));
            $newvalue = $t;
        }
        return $newvalue;
    }
}

class form_item_duration extends form_item {
    /**
     * @return string
     */
    public function get_form() {
        $eltname = $this->get_element_name();
        $value = $this->get_value();

        $units = array(
                86400 => get_string('days'),
                3600 => get_string('hours'),
                60 => get_string('minutes'),
                1 => get_string('seconds'));

        $seconds = $value;
        if ($seconds == 0) {
            $unit = 60;
        }
        foreach ($units as $unit => $notused) {
            if (fmod($seconds, $unit) == 0) {
                $number = $seconds / $unit;
                break;
            }
        }

        $html
        = \html_writer::empty_tag(
                'input',
                array('type' => 'text',
                        'name' => $eltname . '_number',
                        'id' => $eltname . '_number',
                        'value' => $number,
                        'size' => 3))
                        . ' ' . \html_writer::select($units, $eltname . '_timeunit', $unit, false,
                                array('id' => $eltname . '_timeunit'))
                                . ' ' . \html_writer::checkbox($eltname . '_enabled', 'on', $value, get_string('enable'),
                                        array('id' => $eltname . '_enabled'));
        return $html;
    }

    /**
     * @return string
     */
    public function get_new_value() {
        $eltname = $this->get_element_name();
        $newvalue = 0;
        if (optional_param($eltname . '_enabled', 0, PARAM_BOOL)) {
            $newvalue = optional_param($eltname . '_number', 0, PARAM_INT)
                * optional_param($eltname . '_timeunit', 0, PARAM_INT);
        }
        return $newvalue;
    }
}

class form_item_feedback extends form_item {
    /**
     * @return boolean
     */
    public function is_complex_value() {
        return true;
    }

    /**
     * @return string
     */
    public function get_form() {
        global $DB;

        $eltname = $this->get_element_name();
        $cm = $this->cm;
        $feedbackrepeat = quizorganizer::DEFAULTFEEDBACKREPEAT;

        if (!empty($cm)) {
            $quiz = $DB->get_record('quiz', array('id' => $cm->instance));
            $feedbacks = $DB->get_records('quiz_feedback', array('quizid' => $quiz->id), 'maxgrade DESC');
            $feedbacks = array_merge($feedbacks);

            if (count($feedbacks) > $feedbackrepeat) {
                $html = get_string('manyfeedbackset', 'block_quiz_organizer', $feedbackrepeat);
                break;
            }
        }
        $html = '<table class="layouttable">';
        for ($i = 0; $i < $feedbackrepeat; $i++) {
            $feedbacktext = $boundary = '';
            if (isset($feedbacks[$i])) {
                $feedbacktext = strip_tags($feedbacks[$i]->feedbacktext);
                if ($feedbacks[$i]->mingrade > 0)
                    $boundary = sprintf('%g', $feedbacks[$i]->mingrade);
            }
            $html .= '<tr><td>'.get_string('feedback', 'quiz').'</td>'.
                    '<td>'.\html_writer::tag('textarea', $feedbacktext, array(
                            'name' => $eltname.'_text['.$i.']',
                            'id' => $eltname.'_text_'.$i,
                            'cols' => 50,
                            'rows' => 5
                    )).'</td></tr>';
                    if ($i < $feedbackrepeat - 1) {
                        $boundarytext = '';
                        if ($boundary > 0)
                            $boundarytext = $boundary.'%';
                        $html .= '<tr><td>'.get_string('gradeboundary', 'quiz').'</td>'.
                                '<td>'.\html_writer::empty_tag('input', array(
                                        'type' => 'text',
                                        'name' => $eltname.'_boundaries['.$i.']',
                                        'id' => $eltname.'_boundaries_'.$i,
                                        'value' => $boundarytext,
                                        'size' => 5
                                )).'</td></tr>';
                    }
        }
        $html .= '</table>';

        return $html;
    }

    /**
     * @return string
     */
    public function get_new_value() {
        global $DB;

        $eltname = $this->get_element_name();
        $quiz = $this->quiz;
        $feedbackrepeat = quizorganizer::DEFAULTFEEDBACKREPEAT;

        $oldfeedback = $DB->get_records('quiz_feedback', array('quizid' => $quiz->id));
        $oldfeedback = array_merge($oldfeedback);
        if (count($oldfeedback) > $feedbackrepeat)
            break;

        $newfeedbacktexts = optional_param_array($eltname.'_text', array(), PARAM_TEXT);
        $newfeedbackboundaries = array_map(function($a) {
            return preg_replace('/[^0-9.]/', '', $a);
        }, optional_param_array($eltname.'_boundaries', '', PARAM_TEXT));

            if (quizorganizer::check_feedback_modified($oldfeedback, $newfeedbacktexts, $newfeedbackboundaries)) {
                $DB->delete_records('quiz_feedback', array('quizid' => $quiz->id));
                $prevboundary = 101;
                for ($i = 0; $i < $feedbackrepeat; $i++) {
                    $feedback = new \stdClass();
                    $feedback->quizid = $quiz->id;
                    $feedback->feedbacktext = '<p>'.$newfeedbacktexts[$i].'</p>';
                    $feedback->feedbacktextformat = FORMAT_HTML;
                    $feedback->maxgrade = $prevboundary;
                    if (!empty($newfeedbackboundaries[$i])) {
                        $feedback->mingrade = $prevboundary = $newfeedbackboundaries[$i];
                    } else {
                        $feedback->mingrade = 0;
                    }
                    $DB->insert_record('quiz_feedback', $feedback);
                }
            }
    }
}

class form_item_conditions extends form_item {
    private static $gradeitems;

    /**
     * @return boolean
     */
    public function is_complex_value() {
        return true;
    }

    /**
     * @return string
     */
    public function get_form() {
        global $DB, $COURSE;

        $courseid = $COURSE->id;
        $conditionrepeat = quizorganizer::DEFAULTCONDITIONREPEAT;
        $eltname = $this->get_element_name();
        $cm = $this->cm;

        $html = \html_writer::tag('textarea', '', [
            'name' => 'availabilityconditionsjson',
            'id' => 'id_availabilityconditionsjson'
        ]);
            // The _cm variable may not be a proper cm_info, so get one from modinfo.
//             if ($this->_cm) {
//                 $modinfo = get_fast_modinfo($COURSE);
//                 $cm = $modinfo->get_cm($this->_cm->id);
//             } else {
//                 $cm = null;
//             }
            \core_availability\frontend::include_all_javascript($COURSE, $cm);


//         $conditions = null;
//         if (!empty($cm)) {
//             $conditions = $DB->get_records('course_modules_availability', array('coursemoduleid' => $cm->id));

//             if (count($conditions) > $conditionrepeat) {
//                 $html = get_string('manyconditionsset', 'block_quiz_organizer', $conditionrepeat);
//                 break;
//             }

//             $conditions = array_merge($conditions);
//         }

//         // Conditions based on grades
//         $gradeoptions = array();
//         //$items = \grade_item::fetch_all(array('courseid'=>$courseid));
//         if (!isset(self::$gradeitems)) {
//             self::$gradeitems = \grade_item::fetch_all(array('courseid'=>$courseid));
//             uasort(self::$gradeitems, function ($a, $b) {
//                 return $a->sortorder < $b->sortorder ? -1 : 1;
//             });
//         }
//         $items = self::$gradeitems;
//         //$items = $items ? $items : array();

// //         global $itemsShown;
// //         if (empty($itemsShown)) {
// //             var_dump($items);
// //             $itemsShown = true;
// //         }

//         foreach($items as $id=>$item) {
//             // Do not include grades for current item
//             if (!empty($cm) && $cm->instance == $item->iteminstance
//                     && $cm->modname == $item->itemmodule
//                     && $item->itemtype == 'mod') {
//                 continue;
//             }
//             $gradeoptions[$id] = $item->get_name();
//         }
//         $gradeoptions = array(0=>get_string('none','condition'))+$gradeoptions;

//         // $grouparray = array();
//         // $grouparray[] =& $mform->createElement('select','conditiongradeitemid','',$gradeoptions);
//         // $grouparray[] =& $mform->createElement('static', '', '',' '.get_string('grade_atleast','condition').' ');
//         // $grouparray[] =& $mform->createElement('text', 'conditiongrademin','',array('size'=>3));
//         // $grouparray[] =& $mform->createElement('static', '', '','% '.get_string('grade_upto','condition').' ');
//         // $grouparray[] =& $mform->createElement('text', 'conditiongrademax','',array('size'=>3));
//         // $grouparray[] =& $mform->createElement('static', '', '','%');
//         // $group = $mform->createElement('group','conditiongradegroup',
//         //                                get_string('gradecondition', 'condition'),$grouparray);
//         // Get version with condition info and store it so we don't ask
//         // twice
//         if(!empty($cm)) {
//             $ci = new \condition_info($cm, CONDITION_MISSING_EXTRATABLE);
//             $cm = $ci->get_full_course_module();
//             $count = count($cm->conditionsgrade)+1;
//         } else {
//             $count = 1;
//         }

//         $html = '';
//         for ($i = 0; $i < $conditionrepeat; $i++) {
//             $selectedgradeitemid = '';
//             $attr = array(
//                     'grademin' => array(
//                             'type' => 'text',
//                             'size' => 3,
//                             'name' => $eltname . '[' . $i . '][conditiongrademin]',
//                             'id' => $eltname . '_' . $i . '_conditiongrademin'),
//                     'grademax' => array(
//                             'type' => 'text',
//                             'size' => 3,
//                             'name' => $eltname . '[' . $i . '][conditiongrademax]',
//                             'id' => $eltname . '_' . $i . '_conditiongrademax'));
//             if (!empty($conditions[$i]->gradeitemid)) {
//                 $selectedgradeitemid = $conditions[$i]->gradeitemid;
//                 $attr['grademin']['value'] = $this->format_grade_value($conditions[$i]->grademin);
//                 $attr['grademax']['value'] = $this->format_grade_value($conditions[$i]->grademax);
//             }
//             $html .= \html_writer::select(
//                     $gradeoptions, $eltname . '[' . $i . '][conditiongradeitemid]', $selectedgradeitemid,
//                     false, array('id' => $eltname . '_' . $i . '_conditiongradeitemid'))
//                     . ' ' . get_string('grade_atleast', 'condition')
//                     . ' ' . \html_writer::empty_tag('input', $attr['grademin'])
//                     . ' ' . get_string('grade_upto', 'condition')
//                     . ' ' . \html_writer::empty_tag('input', $attr['grademax'])
//                     . ' %' . \html_writer::empty_tag('br');
//         }

//         $course = $DB->get_record('course', array('id' => $courseid));

//         // Conditions based on completion
//         $completion = new \completion_info($course);
//         if ($completion->is_enabled()) {
//             $completionoptions = array();
//             $modinfo = get_fast_modinfo($course);
//             foreach($modinfo->cms as $id=>$cm) {
//                 // Add each course-module if it:
//                 // (a) has completion turned on
//                 // (b) is not the same as current course-module
//                 if ($cm->completion && (empty($cm) || $cm->id != $id)) {
//                     $completionoptions[$id]=$cm->name;
//                 }
//             }
//             asort($completionoptions);
//             $completionoptions = array(0=>get_string('none','condition'))+$completionoptions;

//             $completionvalues=array(
//                     COMPLETION_COMPLETE=>get_string('completion_complete','condition'),
//                     COMPLETION_INCOMPLETE=>get_string('completion_incomplete','condition'),
//                     COMPLETION_COMPLETE_PASS=>get_string('completion_pass','condition'),
//                     COMPLETION_COMPLETE_FAIL=>get_string('completion_fail','condition'));

//             // $grouparray = array();
//             // $grouparray[] =& $mform->createElement('select','conditionsourcecmid','',$completionoptions);
//             // $grouparray[] =& $mform->createElement('select','conditionrequiredcompletion','',$completionvalues);
//             // $group = $mform->createElement('group','conditioncompletiongroup',
//             //                                get_string('completioncondition', 'condition'),$grouparray);

//             $count = empty($cm) ? 1 : count($cm->conditionscompletion)+1;
//             // $this->repeat_elements(array($group),$count,array(),
//             //                        'conditioncompletionrepeats','conditioncompletionadds',2,
//             //                        get_string('addcompletions','condition'),true);
//             // $mform->addHelpButton('conditioncompletiongroup[0]', 'completioncondition', 'condition');
//         }

        return $html;
    }

    /**
     * @return string
     */
    public function get_new_value() {
        global $DB;

        $cm = $this->cm;
        $eltname = $this->get_element_name();
        $conditionrepeat = quizorganizer::DEFAULTCONDITIONREPEAT;

        $oldconditions = $DB->get_records('course_modules_availability', array('coursemoduleid' => $cm->id));

        if (count($oldconditions) > $conditionrepeat) {
            break;
        }

        if (quizorganizer::check_condition_modified($oldconditions, $eltname)) {
            $DB->delete_records('course_modules_availability', array('coursemoduleid' => $cm->id));
            foreach ($_POST[$eltname] as $newcondition) {
                if ($newcondition['conditiongradeitemid']) {
                    if ($newcondition['conditiongrademin'] == '') {
                        $newcondition['conditiongrademin'] = null;
                    }
                    if ($newcondition['conditiongrademax'] == '') {
                        $newcondition['conditiongrademax'] = null;
                    }

                    $row = new \stdClass();
                    $row->coursemoduleid = $cm->id;
                    $row->gradeitemid = $newcondition['conditiongradeitemid'];
                    $row->grademin = $newcondition['conditiongrademin'];
                    $row->grademax = $newcondition['conditiongrademax'];
                    $DB->insert_record('course_modules_availability', $row);
                }
            }
            $modifiedanytable = true;
        }
    }

    /**
     *
     * @param float $grade
     * @return string
     */
    private function format_grade_value($grade) {
        if ($grade === null) {
            return '';
        }
        return sprintf('%g', $grade);
    }
}

class form_item_userfields extends form_item {
    /**
     * @return boolean
     */
    public function is_complex_value() {
        return true;
    }

    /**
     * @return string
     */
    public function get_form() {
        global $DB;

        $strnone = '('.get_string('none').')';

        // Conditions based on user fields
        $operators = \condition_info::get_condition_user_field_operators();
        $useroptions = \condition_info::get_condition_user_fields();
        \collatorlib::asort($useroptions);

        $useroptions = array(0 => $strnone) + $useroptions;

        $userfieldrepeat = quizorganizer::DEFAULTUSERFIELDREPEAT;

        if ($this->cm) {
            $oldfields = $DB->get_records('course_modules_avail_fields', array('coursemoduleid' => $this->cm->id));

            if (count($oldfields) > $userfieldrepeat) {
                return get_string('manyfieldsset', 'block_quiz_organizer', $userfieldrepeat);
            }

            $oldfields = array_merge($oldfields);
        }

        $o = '';
        for ($i = 0; $i < $userfieldrepeat; $i++) {
            if (isset($oldfields[$i])) {
                $default = array(
                        'conditionfield' => $oldfields[$i]->userfield,
                        'conditionfieldoperator' => $oldfields[$i]->operator,
                        'conditionfieldvalue' => $oldfields[$i]->value
                );
            } else {
                $default = array(
                        'conditionfield' => '',
                        'conditionfieldoperator' => '',
                        'conditionfieldvalue' => ''
                );
            }

            $o .= $this->get_select($useroptions, $i, 'conditionfield', $default['conditionfield']);
            $o .= ' '.$this->get_select($operators, $i, 'conditionfieldoperator', $default['conditionfieldoperator']);
            $o .= ' '.$this->get_input($i, 'conditionfieldvalue', $default['conditionfieldvalue']);
            $o .= \html_writer::empty_tag('br');
        }

        return $o;
    }

    public function get_new_value() {
        global $DB;

        $newfields = $_POST[$this->get_element_name()];
        if (1) {
            $DB->delete_records('course_modules_avail_fields', array('coursemoduleid' => $this->cm->id));

            foreach ($newfields as $newfield) {
                if ($newfield['conditionfield']) {
                    $row = (object)array(
                            'coursemoduleid' => $this->cm->id,
                            'userfield' => $newfield['conditionfield'],
                            'operator' => $newfield['conditionfieldoperator'],
                            'value' => $newfield['conditionfieldvalue']
                    );
                    $DB->insert_record('course_modules_avail_fields', $row);
                }
            }
        }
    }

    /**
     *
     * @param array $options
     * @param int $i
     * @param string $field
     * @param string $value
     * @return string
     */
    private function get_select(array $options, $i, $field, $value = '') {
        $eltname = $this->get_element_name();
        $name = $eltname.'['.$i.']['.$field.']';
        $id = $eltname.'_'.$i.'_'.$field;

        return \html_writer::select($options, $name, $value, false, array('id' => $id));
    }

    /**
     *
     * @param int $i
     * @param string $field
     * @param string $value
     * @return string
     */
    private function get_input($i, $field, $value = '') {
        $eltname = $this->get_element_name();
        $name = $eltname.'['.$i.']['.$field.']';
        $id = $eltname.'_'.$i.'_'.$field;

        return \html_writer::empty_tag('input', array(
                'type' => 'text',
                'name' => $name,
                'id' => $id,
                'size' => 10,
                'value' => $value
        ));
    }

    private function is_modified() {
        global $DB;

        $oldfields = $DB->get_records('course_modules_avail_fields', array('coursemoduleid' => $this->cm->id));
        $newfields = $_POST[$this->get_element_name()];

        if (is_array($newfields)) {
            foreach ($newfields as $newfield) {
                if ($newfield['conditionfield']) {
//                     foreach ()
                }
            }
        }
    }
}

class form_item_penalties extends form_item {
    /**
     * @return boolean
     */
    public function is_complex_value() {
        return true;
    }

    /**
     * @return string
     */
    public function get_form() {
        $id = $this->get_quiz_id();
        $value = $this->get_value();
        $eltprefix = 'q_'.$id.'_';

        if ($penalties = quizorganizer::get_penalties($id)) {
            $value = $penalties[0]->limitdate;
        }

        $eltnam = $eltprefix . 'penaltylimitdate';
        $disabled='';
        if ($value == 0) {
            $value = time();
            $checked = ' checked="checked"';
        } else {
            $checked = '';
        }
        $tm = localtime($value, true);

        for ($i=1; $i<=12; $i++) {
            $months[$i] = userdate(gmmktime(12,0,0,$i,15,2000), '%b');
        }
        $select = array(
                'Y' => '<select name="' . $eltnam . '_year" id="' . $eltnam . '_year"' . $disabled . '>'
                . quizorganizer::make_options(1970, 2020, $tm['tm_year'] + 1900)
                . '</select> ',
                'M' => '<select name="' . $eltnam . '_month" id="' . $eltnam . '_month"' . $disabled . '>'
                . quizorganizer::make_options(1, 12, $tm['tm_mon'] + 1, $months)
                . '</select> ',
                'D' => '<select name="' . $eltnam . '_day" id="' . $eltnam . '_day"' . $disabled . '>'
                . quizorganizer::make_options(1, 31, $tm['tm_mday'])
                . '</select> ',
                'h' => '<select name="' . $eltnam . '_hour" id="' . $eltnam . '_hour"' . $disabled . '>'
                . quizorganizer::make_options(0, 23, $tm['tm_hour'], '%02d')
                . '</select> ',
                'm' => '<select name="' . $eltnam . '_min" id="' . $eltnam . '_min"' . $disabled . '>'
                . quizorganizer::make_options(
                        0, 59, floor($tm['tm_min'] / 5) * 5, '%02d', 5)
                . '</select> ');
        $dateorder = get_string('dateorder', 'block_quiz_organizer');
        $html = get_string('limitdate', 'local_quizpenalty') . ' ';
        $html .= quizorganizer::get_date_selector($eltnam, $value);
        if ($this->isbatchrow) {
            $strnochange = get_string('nochange', 'block_quiz_organizer');
            $subeltname = $eltnam.'_nochange';
            $html .= \html_writer::checkbox($subeltname, 1, true, $strnochange,
                    array('id' => $subeltname));
        }

        // 各締切
        for ($i = 0; $i < 4; $i++) {
            $html .= '<br/>' . get_string('limit', 'local_quizpenalty') . ' ' . ($i + 1) . ' ';
            $eltnam = $eltprefix . 'penaltydays_' . $i;
            $daysopt = array_merge(
                    array(0 => get_string('immediately', 'local_quizpenalty')),
                    array_combine(range(1, 60), range(1, 60)));
            $html .= '<select name="' . $eltnam . '" id="' . $eltnam . '">';
            foreach ($daysopt as $k => $v) {
                if ($penalties && $k == $penalties[$i]->daysafter) {
                    $selected = ' selected="selected"';
                } else {
                    $selected = '';
                }
                $html .= '<option value="' . $k . '"' . $selected . '>' . $v . '</option>';
            }
            $html .= '</select> ' . get_string('days', 'local_quizpenalty');
            $eltnam = $eltprefix . 'penaltypercent_' . $i;
            $percentopt = array(0, 10, 20, 25, 30, 33, 40, 50, 60, 67, 70,
                    75, 80, 90, 100);
            $percentopt = array_combine($percentopt, $percentopt);
            $html .= ' <select name="' . $eltnam . '" id="' . $eltnam . '">';
            foreach ($percentopt as $k => $v) {
                if ($penalties && $k == $penalties[$i]->percentage) {
                    $selected = ' selected="selected"';
                } else {
                    $selected = '';
                }
                $html .= '<option value="' . $k . '"' . $selected . '>' . $v . '</option>';
            }
            $html .= '</select> ' . get_string('percentoff', 'local_quizpenalty', '');
            $eltnam = $eltprefix . 'penaltytype_' . $i;
            $typeopt = array(
                    'total' => get_string('total', 'local_quizpenalty'),
                    'student' => get_string('student', 'local_quizpenalty')
            );
            $html .= ' <select name="' . $eltnam . '" id="' . $eltnam . '">';
            foreach ($typeopt as $k => $v) {
                if ($penalties && $k == $penalties[$i]->penaltytype) {
                    $selected = ' selected="selected"';
                } else {
                    $selected = '';
                }
                $html .= '<option value="' . $k . '"' . $selected . '>' . $v . '</option>';
            }
            $html .= '</select>';
            $eltnam = $eltprefix . 'penaltyenable_' . $i;
            if ($penalties && $penalties[$i]->enabled) {
                $checked = ' checked="checked"';
            } else {
                $checked = '';
            }
            $html .= ' <input type="checkbox" name="' . $eltnam . '" id="' . $eltnam . '"' . $checked . '/> ' . get_string('enabled', 'quiz') . '';
        }

        $eltnam = $eltprefix . 'penaltyshowlimitdate';
        $opts = array(get_string('no'), get_string('yes'));
        $html .= '<br/>' . get_string('showlimitdate', 'local_quizpenalty') . ' <select name="' . $eltnam . '" id="' . $eltnam . '">';
        if ($penalties) {
            $showlimitdate = $penalties[0]->showlimitdate;
        } else {
            $showlimitdate = 1;
        }
        foreach ($opts as $k => $v) {
            if ($k == $showlimitdate) {
                $selected = ' selected="selected"';
            } else {
                $selected = '';
            }
            $html .= '<option value="' . $k . '"' . $selected . '>' . $v . '</option>';
        }
        $html .= '</select>';

        return $html;
    }

    /**
     * @return string
     */
    public function get_new_value() {
        global $DB;

        $quiz = $this->quiz;

        if ($penalties = quizorganizer::get_penalties($quiz->id)) {
            $value = $penalties[0]->limitdate;
        }
        $newpenalties = quizorganizer::get_new_penalties($quiz->id);

        $eltprefix = 'q_' . $quiz->id . '_';
        $eltnam = $eltprefix . 'penaltylimitdate';
        $newlimitdate = mktime(
                optional_param($eltnam . '_hour', 0, PARAM_INT),
                optional_param($eltnam . '_min', 0, PARAM_INT),
                0,
                optional_param($eltnam . '_month', 0, PARAM_INT),
                optional_param($eltnam . '_day', 0, PARAM_INT),
                optional_param($eltnam . '_year', 0, PARAM_INT));

        $penaltymodified = false;

        if (empty($penalties[0])) {
            $penaltymodified = true;
        } else {
            if ($penalties[0]->limitdate != $newlimitdate) {
                $penaltymodified = true;
            } else {
                $penaltymodified = quizorganizer::compare_penalties(
                        $penalties, $newpenalties);
            }
        }

        if ($penaltymodified) {
            $DB->delete_records('local_quizpenalty', array('quiz' => $quiz->id));

            for ($i = 0; $i < 4; $i++) {
                $DB->insert_record('local_quizpenalty', $newpenalties[$i]);
            }
        }
    }
}
