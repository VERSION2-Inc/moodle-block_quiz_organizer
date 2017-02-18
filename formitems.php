<?php
namespace quizorganizer;

defined('MOODLE_INTERNAL') || die();

require_once $CFG->dirroot . '/blocks/quiz_organizer/locallib.php';
require_once $CFG->libdir . '/gradelib.php';
// require_once $CFG->libdir . '/conditionlib.php';
require_once $CFG->dirroot . '/mod/quiz/locallib.php';

class form_config {
    /**
     *
     * @return form_group_item[]
     */
    public static function get_items() {
        global $CFG, $COURSE;

        $items[] = new form_group('general', get_string('general', 'form'));
        $items[] = new form_item_selectyesno('showdescription', get_string('showdescription'), null, 'cm');

        $items[] = new form_group('timing', get_string('timing', 'quiz'));
        $items[] = new form_item_datetime('timeopen', get_string('quizopen', 'quiz'));
        if (quizorganizer::penalty_enabled()) {
            $items[] = new form_item_penalties('penalties', get_string('penalties', 'local_quizpenalty'), null, 'penalties');
        }
        $items[] = new form_item_datetime('timeclose', get_string('quizclose', 'quiz'));
        $items[] = new form_item_duration('timelimit', get_string('timelimit', 'quiz'));
        $items[] = new form_item_select('overduehandling', get_string('overduehandling', 'quiz'),
                quiz_get_overdue_handling_options());
        $items[] = new form_item_duration('graceperiod', get_string('graceperiod', 'quiz'));

        $items[] = new form_group('grade', get_string('grade'));
//         $items[] = new form_item_select('gradecat', get_string('gradecategoryonmodform', 'grades'),
//                 grade_get_categories_menu($COURSE->id), 'gradecat');
        $attemptoptions = array('0' => get_string('unlimited'));
        for ($i = 1; $i <= QUIZ_MAX_ATTEMPT_OPTION; $i++) {
            $attemptoptions[$i] = $i;
        }
        $items[] = new form_item_select('attempts', get_string('attemptsallowed', 'quiz'), $attemptoptions);
        $items[] = new form_item_select('grademethod', get_string('grademethod', 'quiz'),
                quiz_get_grading_options());

        $items[] = new form_group('layout', get_string('layout', 'quiz'));
        $items[] = new form_item_select('shufflequestions', get_string('questionorder', 'quiz'),
                array(
                        0 => get_string('asshownoneditscreen', 'quiz'),
                        1 => get_string('shuffledrandomly', 'quiz')
                )
        );
        $pageoptions = array();
        $pageoptions[0] = get_string('neverallononepage', 'quiz');
        $pageoptions[1] = get_string('everyquestion', 'quiz');
        for ($i = 2; $i <= QUIZ_MAX_QPP_OPTION; ++$i) {
            $pageoptions[$i] = get_string('everynquestions', 'quiz', $i);
        }
        $items[] = new form_item_select('questionsperpage', get_string('newpage', 'quiz'), $pageoptions);
        $items[] = new form_item_select('navmethod', get_string('navmethod', 'quiz'),
                quiz_get_navigation_options());

        $items[] = new form_group('questionbehaviour', get_string('questionbehaviour', 'quiz'));
        $items[] = new form_item_selectyesno('shuffleanswers', get_string('shufflewithin', 'quiz'));
        $items[] = new form_item_select('preferredbehaviour', get_string('howquestionsbehave', 'question'),
                \question_engine::get_behaviour_options(''));
        $items[] = new form_item_selectyesno('attemptonlast', get_string('eachattemptbuildsonthelast', 'quiz'));

        $items[] = new form_group('display', get_string('display', 'form'));
        $items[] = new form_item_selectyesno('showuserpicture', get_string('showuserpicture', 'quiz'));
        $options = array();
        for ($i = 0; $i <= QUIZ_MAX_DECIMAL_OPTION; $i++) {
            $options[$i] = $i;
        }
        $items[] = new form_item_select('decimalpoints', get_string('decimalplaces', 'quiz'), $options);
        $options = array(-1 => get_string('sameasoverall', 'quiz'));
        for ($i = 0; $i <= QUIZ_MAX_Q_DECIMAL_OPTION; $i++) {
            $options[$i] = $i;
        }
        $items[] = new form_item_select('questiondecimalpoints', get_string('decimalplacesquestion', 'quiz'), $options);
        $items[] = new form_item_selectyesno('showblocks', get_string('showblocks', 'quiz'));

        $items[] = new form_group('security', get_string('extraattemptrestrictions', 'quiz'));
        $items[] = new form_item_text('quizpassword', get_string('requirepassword', 'quiz'), null, null, 'password');
        $items[] = new form_item_text('subnet', get_string('requiresubnet', 'quiz'), null, null, 'subnet');
        $items[] = new form_item_duration('delay1', get_string('delay1st2nd', 'quiz'));
        $items[] = new form_item_duration('delay2', get_string('delaylater', 'quiz'));
        $items[] = new form_item_select('browsersecurity', get_string('browsersecurity', 'quiz'),
                \quiz_access_manager::get_browser_security_choices());

        $items[] = new form_group('overallfeedback', get_string('overallfeedback', 'quiz'));
        $items[] = new form_item_feedback('feedback', get_string('feedback'), null, 'feedback');

        $items[] = new form_group('modstandardels', get_string('modstandardels', 'form'));
        $items[] = new form_item_select('groupmode', get_string('groupmode', 'group'),
                array(
                        NOGROUPS       => get_string('groupsnone'),
                        SEPARATEGROUPS => get_string('groupsseparate'),
                        VISIBLEGROUPS  => get_string('groupsvisible')
                ),
                'cm'
        );
        $items[] = new form_item_select('groupingid', get_string('grouping', 'group'),
                self::get_grouping_options(), 'cm');
        $items[] = new form_item_select('visible', get_string('visible'),
                array(
                        1 => get_string('show'),
                        0 => get_string('hide')
                ),
                'cm'
        );
        $items[] = new form_item_text('cmidnumber', get_string('idnumbermod'), null, 'cm', 'idnumber');

        // if (!empty($CFG->enableavailability)) {
        //     $items[] = new form_group('availabilityconditions', get_string('availabilityconditions', 'condition'));
        //     $items[] = new form_item_datetime('availablefrom', get_string('availablefrom', 'condition'), null, 'cm');
        //     $items[] = new form_item_datetime('availableuntil', get_string('availableuntil', 'condition'), null, 'cm');
        //     $items[] = new form_item_conditions('conditiongrade', get_string('gradecondition', 'condition'), null, 'condition');
        //     $items[] = new form_item_userfields('conditionfield', get_string('userfield', 'condition'), null, 'condition');
        //     $items[] = new form_item_select('showavailability', get_string('showavailability', 'condition'),
        //             array(
        //                     CONDITION_STUDENTVIEW_SHOW => get_string('showavailability_show', 'condition'),
        //                     CONDITION_STUDENTVIEW_HIDE => get_string('showavailability_hide', 'condition')
        //             ),
        //             'cm'
        //     );
        // }

        return $items;
    }

    /**
     *
     * @return array
     */
    public static function get_items_array() {
        $items = self::get_items();

        $columns = array_map(function ($item) {
            return $item->to_array();
        }, $items);

        return $columns;
    }

    /**
     *
     * @return array
     */
    private static function get_grouping_options() {
        global $DB, $COURSE;

        $options = array();
        $options[0] = get_string('none');
        if ($groupings = $DB->get_records('groupings', array('courseid'=>$COURSE->id))) {
            foreach ($groupings as $grouping) {
                $options[$grouping->id] = format_string($grouping->name);
            }
        }

        return $options;
    }
}
