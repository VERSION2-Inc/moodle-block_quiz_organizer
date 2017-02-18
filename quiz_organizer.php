<?php
/**
 * Quiz Organizer
 *
 * @package quiz_organizer
 * @author  VERSION2 Inc.
 * @version $Id: quiz_organizer.php 295 2014-03-18 03:04:31Z yama $
 */

namespace quizorganizer;

require_once '../../config.php';
require_once $CFG->dirroot . '/blocks/quiz_organizer/locallib.php';
require_once $CFG->libdir . '/tablelib.php';

class page_quizorganizer extends page {
    const HEADER_PER_ROWS = 5;

    private $filtername;
    private $section;
    private $select;
    private $params;
    private $activecolumns;

    public function execute() {
        $this->filtername = optional_param('filtername', '', PARAM_TEXT);
        $this->section = optional_param('section', 0, PARAM_INT);

        $this->select = 'q.course = :course';
        $this->params = array('course' => $this->course->id);
        if (strlen($this->filtername)) {
            $this->select .= ' AND q.name LIKE :name';
            $this->params['name'] = '%' . $this->filtername . '%';
        }
        if ($this->section) {
            $this->select .= ' AND cm.section = :section';
            $this->params['section'] = $this->section;
        }

        if ($setting = $this->qo->get_setting()) {
            $this->activecolumns = explode(',', $setting->activecolumns);
            $conditionrepeat = $setting->conditionrepeat;
        } else {
            $this->activecolumns = explode(',', quizorganizer::DEFAULTCOLUMNS);
            $conditionrepeat = quizorganizer::DEFAULTCONDITIONREPEAT;
        }
        $feedbackrepeat = quizorganizer::DEFAULTFEEDBACKREPEAT;

        $this->process();
        $this->view();
    }

    private function view() {
        global $DB, $PAGE, $OUTPUT;

        $filtername = $this->filtername;
        $activecolumns = $this->activecolumns;
        $select = $this->select;
        $params = $this->params;

        $formitems = form_config::get_items();

        require_capability('mod/quiz:manage', \context_course::instance($this->course->id));

        $strtitle = get_string('title', 'block_quiz_organizer');
        $PAGE->set_title($strtitle);
        $PAGE->set_heading($strtitle);
        $PAGE->navbar->add($strtitle);
        $PAGE->set_pagelayout('report');

        $PAGE->requires->js_init_call('M.block_quiz_organizer.init_organizer');

        echo $OUTPUT->header();
        echo $OUTPUT->heading($strtitle);

        if (!empty($modifiedquizzes)) {
            echo \html_writer::tag(
                    'div', get_string('numquizzesmodified', 'block_quiz_organizer', $modifiedquizzes),
                    array('class' => 'notifysuccess'));
        }

        echo $OUTPUT->single_button(
                new \moodle_url('/blocks/quiz_organizer/setting.php', array(
                        'course' => $this->course->id,
                        'filtername' => $this->filtername
                )),
                get_string('editcolumnstodisplayandedit', 'block_quiz_organizer'),
                'post',
                array()
        );

        $sectionoptions = quizorganizer::get_course_section_options($this->course);

        echo '
  <div class="generalbox">
  <form action="quiz_organizer.php" method="get">
    <input type="hidden" name="course" value="' . $this->course->id . '"/>
    ' . get_string('filterbyname', 'block_quiz_organizer') . '
    <input type="text" name="filtername" size="20"
      value="' . htmlspecialchars($filtername) . '"/><br>
              '.get_string('section').' '.\html_writer::select($sectionoptions, 'section', $this->section, get_string('all')).'<br>
    <input type="submit" value="' . get_string('show') . '"/>
    <input type="button" value="' . get_string('clear', 'block_quiz_organizer') . '"
      onclick="filtername.value=\'\';this.form.submit()"/>
  </form>
  </div>';

        echo '
  <form action="quiz_organizer.php" method="post" id="quizorg">
    <input type="hidden" name="data" value="1"/>
    <input type="hidden" name="course" value="' . $this->course->id . '"/>
    <input type="hidden" name="filtername" size="20"
      value="' . htmlspecialchars($filtername) . '"/>
              <input type="hidden" name="section" value="'.$this->section.'">
    <input type="submit" value="' . get_string('saveallsettings', 'block_quiz_organizer') . '"/>
    <input type="reset" value="' . get_string('reset') . '"/>'
            . ' ' . \html_writer::empty_tag(
                    'input',
                    array('type' => 'button',
                            'value' => get_string('redrawtable', 'block_quiz_organizer'),
                            'class' => 'resizetable'))
                            ;

        $table = new \flexible_table('block-quiz_organizer-quizzes');
        $table->define_baseurl($this->url);
        $table->set_attribute('class', 'generaltable generalbox quizorgtable');
        $tablecolumns = array('check', 'section', 'name');
        $tableheaders = array(
                \html_writer::checkbox(
                        '', '', false, '',
                        array('onclick' => 'M.block_quiz_organizer.check_quizzes(this.checked)',
                                'class' => 'checkall')),
                get_string('section'),
                get_string('name')
        );

        foreach ($formitems as $item) {
            if (in_array($item->name, $activecolumns)) {
                $tablecolumns[] = $item->name;
                $tableheaders[] = $item->label;
            }
        }

        $table->define_columns($tablecolumns);
        $table->define_headers($tableheaders);
        $table->sortable(true, 'section');
        $table->no_sorting('check');

        $table->setup();

        if ($quizzes = $this->get_quizzes($select, $params, $table->get_sql_sort())) {
            // 設定行
            $row = array('', '', '');
            //     foreach ($columns as $column) {
            foreach ($formitems as $item) {
                if ($item->is_group() || !in_array($item->name, $activecolumns)) {
                    continue;
                }

                $colname = $item->name;

                $item->isbatchrow = true;
                $ctl = $item->get_form();
                $item->isbatchrow = false;
                if ($ctl) {
                    $batchctl = $ctl
                    . \html_writer::empty_tag('br')
                    . \html_writer::empty_tag(
                            'input', array(
                                    'type' => 'button',
                                    'value' => ucfirst(get_string('copy')),
                                    'class' => 'copybutton',
                                    'data-name' => $colname,
                                    'data-type' => $item->type,
                            ));
                    $row[] = $batchctl;
                } else {
                    $row[] = '';
                }
            }
            $table->add_data($row);

            $middleheader = array_map(function ($a) {
                return \html_writer::tag('div', $a, array('class' => 'midhead'));
            }, $tableheaders);
            $rowcount = 0;

            // データ行
            foreach ($quizzes as $quiz) {
                $cm = get_coursemodule_from_instance('quiz', $quiz->id);

                $row = array(
                        \html_writer::checkbox(
                                'chk_' . $quiz->id, '', false, '',
                                array('id' => 'chk_' . $quiz->id,
                                        'class' => 'quizcheck')),
                        get_section_name($this->course, $quiz->section),
                        $quiz->name
                );

                //         foreach ($columns as $column) {
                foreach ($formitems as $item) {
                    //             if (in_array($column['name'], $activecolumns)) {
                    if (!in_array($item->name, $activecolumns)) {
                        continue;
                    }

                    //             if (!empty($column['table'])) {
                    //                 switch ($column['table']) {
                    //                     case 'cm':
                    //                         $value = $cm->{$column['dbcolumn']};
                    //                         break;
                    //                     case 'feedback':
                    //                     case 'condition':
                    //                     case 'penalties':
                    //                         $value = 0;
                    //                         break;
                    //                 }
                    //             } else {
                    //                 $value = $quiz->{$column['dbcolumn']};
                    //             }

                        $item->set_data($quiz, $cm);
                        $row[] = $item->get_form($quiz, $cm);
                        $item->unset_data();
                    }

                    $table->add_data($row);

                    if ($rowcount > 0 && $rowcount % self::HEADER_PER_ROWS == 0) {
                        $table->add_data($middleheader);
                    }
                    $rowcount++;
                }
                $table->add_data($middleheader);

                $table->finish_output();
            } else {
                echo '<div class="errorbox">' . get_string('noquizfound', 'block_quiz_organizer') . '</div>';
            }
        echo '
    <input type="submit" value="' . get_string('saveallsettings', 'block_quiz_organizer') . '"/>
    <input type="reset" value="' . get_string('reset') . '"/>
  </form>';

        echo $OUTPUT->footer();
    }

    private function process() {
        global $DB;

        $formitems = form_config::get_items();

        //if (optional_param('data', 0, PARAM_BOOL)) {
        if (data_submitted()) {
            if ($quizzes = $this->get_quizzes($this->select, $this->params)) {
                $modifiedquizzes = 0;
                foreach ($quizzes as $quiz) {
                    $modified = array(
                            'quiz' => false,
                            'cm' => false,
                            'gradecat' => false,
                            'condition' => false);
                    $modifiedanytable = false;

                    if (!($cm = get_coursemodule_from_instance('quiz', $quiz->id))) {
                        continue;
                    }

                    foreach ($formitems as $item) {
                        if (in_array($item->name, $this->activecolumns)) {
                            if (!empty($item->table)) {
                                switch ($item->table) {
                                    case 'cm':
                                        $table = 'cm';
                                        $rowobj = $cm;
                                        break;
                                    case 'gradecat':
                                        quizorganizer::set_grade_item_category();
                                        break;
                                    case 'feedback':
                                    case 'condition':
                                    case 'penalties':
                                        $table = null;
                                        $rowobj = null;
                                        break;
                                    case 'quiz':
                                        $rowobj = $quiz;
                                }
                            } else {
                                $rowobj = $quiz;
                            }

                            $item->set_data($quiz, $cm);
                            $newvalue = $item->get_new_value();
                            $item->unset_data();

                            if ($rowobj && $newvalue != $rowobj->{$item->dbcolumn}) {
                                $rowobj->{$item->dbcolumn} = $newvalue;
                                $modified[$item->table] = true;
                            }
                        }
                    }

                    if ($modified['quiz']) {
                        $quiz->timemodified = time();
                        $DB->update_record('quiz', $quiz);
                        $modifiedanytable = true;

                        quizorganizer::add_event($quiz);
                    }
                    if ($modified['cm']) {
                        $DB->update_record('course_modules', $cm);
                        $modifiedanytable = true;

                        quizorganizer::add_event($quiz);
                    }
                    if ($modifiedanytable) {
                        $modifiedquizzes++;
                    }
                }
            }

            rebuild_course_cache($this->course->id);
        }
    }

    /**
     *
     * @param string $select
     * @param array $params
     * @param string $sort
     * @return \stdClass[]
     */
    private function get_quizzes($select, $params, $sort = null) {
        global $DB;

        $sql = '
                SELECT q.*, cs.section
                FROM {quiz} q
                    JOIN {course_modules} cm ON q.id = cm.instance
                    JOIN {modules} m ON cm.module = m.id
                    JOIN {course_sections} cs ON cm.section = cs.id
                WHERE m.name = \'quiz\'
                    AND '.$select;

        if ($sort) {
            $sql .= ' ORDER BY '.$sort;
        }

        return $DB->get_records_sql($sql, $params);
    }
}

$page = new page_quizorganizer('/blocks/quiz_organizer/quiz_organizer.php');
$page->execute();
