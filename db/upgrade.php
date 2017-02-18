<?php
/**
 * Quiz Organizer
 *
 * @package quiz_organizer
 * @author  VERSION2 Inc.
 * @version $Id: upgrade.php 186 2013-03-06 20:41:41Z yama $
 */

function xmldb_block_quiz_organizer_upgrade($oldversion = 0) {
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2012022200) {
        $table = new xmldb_table('block_quiz_organizer');
        $field = new xmldb_field('conditionrepeat', XMLDB_TYPE_INTEGER, 2, XMLDB_UNSIGNED, XMLDB_NOTNULL, null, 0);

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_block_savepoint(true, 2012022200, 'quiz_organizer');
    }

    return true;
}
