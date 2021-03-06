<?php
/**
 * ELIS(TM): Enterprise Learning Intelligence Suite
 * Copyright (C) 2008-2014 Remote-Learner.net Inc (http://www.remote-learner.net)
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @package    local_elisprogram
 * @author     Remote-Learner.net Inc
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright  (C) 2008-2014 Remote-Learner.net Inc (http://www.remote-learner.net)
 *
 */

defined('MOODLE_INTERNAL') || die();

function xmldb_local_elisprogram_upgrade($oldversion = 0) {
    global $DB, $CFG;

    $dbman = $DB->get_manager();
    $result = true;

    // Always upon any upgrade, ensure ELIS scheduled tasks is in good health.
    if ($result) {
        require_once($CFG->dirroot.'/local/eliscore/lib/tasklib.php');
        elis_tasks_update_definition('local_elisprogram');
    }

    // Migrate language strings
    if ($result && $oldversion < 2014030701) {
        $migrator = new \local_elisprogram\install\migration\elis26();
        $result = $migrator->migrate_language_strings();
        upgrade_plugin_savepoint($result, 2014030701, 'local', 'elisprogram');
    }

    if ($result && $oldversion < 2014030703) {
        elis_tasks_uninstall('elis_program');
        upgrade_plugin_savepoint($result, 2014030703, 'local', 'elisprogram');
    }

    if ($result && $oldversion < 2014030704) {
        $DB->delete_records('events_handlers', array('component' => 'elis_program'));
        upgrade_plugin_savepoint($result, 2014030704, 'local', 'elisprogram');
    }

    if ($result && $oldversion < 2014030707) {
        // ELIS-9081: Migrate any dataroot /elis/program files to /local/elisprogram
        $olddatadir = $CFG->dataroot.'/elis/program';
        $newdatadir = $CFG->dataroot.'/local/elisprogram';
        if (file_exists($olddatadir) && !file_exists($newdatadir)) {
            $parentdir = dirname($newdatadir);
            if (!file_exists($parentdir)) {
                @mkdir($parentdir, 0777, true);
            }
            @rename($olddatadir, $newdatadir);
        } else if (!file_exists($newdatadir)) {
            @mkdir($newdatadir, 0777, true);
        }
        upgrade_plugin_savepoint($result, '2014030707', 'local', 'elisprogram');
    }

    return $result;
}
