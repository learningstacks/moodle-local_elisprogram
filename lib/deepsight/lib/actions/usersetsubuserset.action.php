<?php
/**
 * ELIS(TM): Enterprise Learning Intelligence Suite
 * Copyright (C) 2008-2013 Remote-Learner.net Inc (http://www.remote-learner.net)
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
 * @copyright  (C) 2013 Remote Learner.net Inc http://www.remote-learner.net
 * @author     James McQuillan <james.mcquillan@remote-learner.net>
 *
 */

require_once(elispm::lib('data/userset.class.php'));

/**
 * An action to assign a user to a program.
 */
class deepsight_action_usersetsubuserset_makesubset extends deepsight_action_confirm {
    /**
     * @var string The label to use for the action (will get overwritten with language string)
     */
    public $label = 'Make Subset';

    /**
     * @var string The icon CSS class to use for the action button.
     */
    public $icon = 'elisicon-assoc';

    /**
     * Constructor.
     * @param moodle_database $DB The active database connection.
     * @param string $name The unique name of the action to use.
     * @param string $descsingle The description when the confirmation is for a single element.
     * @param string $descmultiple The description when the confirmation is for the bulk list.
     */
    public function __construct(moodle_database &$DB, $name, $descsingle='', $descmultiple='') {
        parent::__construct($DB, $name);

        $this->label = get_string('ds_assign_as_subset', 'local_elisprogram');

        $curuserset = required_param('id', PARAM_INT);
        $curuserset = new userset($curuserset);
        $this->descsingle = (!empty($descsingle))
                ? $descsingle : get_string('ds_action_assign_subset', 'local_elisprogram', $curuserset->name);
        $this->descmultiple = (!empty($descmultiple))
                ? $descmultiple : get_string('ds_action_assign_subset_multi', 'local_elisprogram', $curuserset->name);
    }

    /**
     * Move incoming usersets to be a subuset of current userset.
     * @throws moodle_exception
     * @param array $elements An array of userset information to assign to the track.
     * @param bool $bulkaction Whether this is a bulk-action or not.
     * @return array An array to format as JSON and return to the Javascript.
     */
    protected function _respond_to_js(array $elements, $bulkaction) {
        global $DB, $USER;

        // The userset that will be the new parent set.
        $curusersetid = required_param('id', PARAM_INT);

        // Limit incoming usersets to possible-to-move usersets.
        $possiblesubsets = cluster_get_possible_sub_clusters($curusersetid);
        $elements = array_intersect_key($elements, $possiblesubsets);
        unset($possiblesubsets);

        // We need edit permissions.
        $perm = 'local/elisprogram:userset_edit';
        $userseteditctx = pm_context_set::for_user_with_capability('cluster', $perm, $USER->id);
        if ($userseteditctx->context_allowed($curusersetid, 'cluster') !== true) {
            throw new moodle_exception('not_permitted', 'local_elisprogram');
        }

        // Loop through requested elements to move. Check for permissions and do an sanity check on IDs and parent ID, then move.
        foreach ($elements as $tomoveusersetid => $label) {

            // Ensure user has edit perm on $tomoveusersetid.
            if ($userseteditctx->context_allowed($tomoveusersetid, 'cluster')) {
                $tomove = new userset($tomoveusersetid);
                $tomove->load();

                // The userset we're moving shouldn't be the userset we're moving below, and it shouldn't already be a child
                // of the new parent.
                if ($tomove->id !== $curusersetid && $tomove->parent !== $curusersetid) {
                    $tomove->parent = $curusersetid;
                    $tomove->save();
                }
            }
        }

        return array('result' => 'success', 'msg' => 'Success');
    }
}

/**
 * Provide a link to edit userset.
 */
class deepsight_action_usersetsubuserset_editlink extends deepsight_action_link {
    /**
     * @var string The label for the action.
     */
    public $label = 'Edit';

    /**
     * @var string The icon for the action.
     */
    public $icon = 'elisicon-edit';

    /**
     * @var string The link target (without query string)
     */
    public $baseurl = '/local/elisprogram/index.php';

    /**
     * @var array Query parameters for the link target
     */
    public $params = array(
        's' => 'clst',
        'action' => 'edit',
        'id' => '{element_id}',
    );
}

/**
 * Provide a link to manage track associations.
 */
class deepsight_action_usersetsubuserset_trackslink extends deepsight_action_link {
    /**
     * @var string The label for the action.
     */
    public $label = 'Tracks';

    /**
     * @var string The icon for the action.
     */
    public $icon = 'elisicon-track';

    /**
     * @var string The link target (without query string)
     */
    public $baseurl = '/local/elisprogram/index.php';

    /**
     * @var array Query parameters for the link target
     */
    public $params = array(
        's' => 'clsttrk',
        'id' => '{element_id}',
    );
}

/**
 * Provide a link to manage user associations.
 */
class deepsight_action_usersetsubuserset_userslink extends deepsight_action_link {
    /**
     * @var string The label for the action.
     */
    public $label = 'Users';

    /**
     * @var string The icon for the action.
     */
    public $icon = 'elisicon-user';

    /**
     * @var string The link target (without query string)
     */
    public $baseurl = '/local/elisprogram/index.php';

    /**
     * @var array Query parameters for the link target
     */
    public $params = array(
        's' => 'clstusr',
        'id' => '{element_id}',
    );
}

/**
 * Provide a link to manage program associations.
 */
class deepsight_action_usersetsubuserset_programslink extends deepsight_action_link {
    /**
     * @var string The label for the action.
     */
    public $label = 'Programs';

    /**
     * @var string The icon for the action.
     */
    public $icon = 'elisicon-program';

    /**
     * @var string The link target (without query string)
     */
    public $baseurl = '/local/elisprogram/index.php';

    /**
     * @var array Query parameters for the link target
     */
    public $params = array(
        's' => 'clstcur',
        'id' => '{element_id}',
    );
}

/**
 * An action to delete usersets.
 */
class deepsight_action_usersetsubuserset_delete extends deepsight_action_standard {
    /** @const string the corresponding javascript action */
    const TYPE = 'usersetsubset_delete';

    /** @var string the action label */
    public $label = 'Delete';

    /** @var string the action icon */
    public $icon = 'elisicon-remove elisiconcolored';

    /**
     * Constructor.
     * @param moodle_database $DB The active database connection.
     * @param string $name The unique name of the action to use.
     */
    public function __construct(moodle_database &$DB, $name) {
        parent::__construct($DB, $name);
        $this->label = ucwords(get_string('delete', 'local_elisprogram'));

        $this->descsingle = get_string('ds_action_usersetsubset_delete', 'local_elisprogram');
        $this->descmultiple = get_string('ds_action_usersetsubsets_delete', 'local_elisprogram');
    }

    /**
     * Provide options to the javascript.
     * @return array An array of options.
     */
    public function get_js_opts() {
        $opts = parent::get_js_opts();
        $opts['condition'] = $this->condition;
        $opts['opts']['actionurl'] = $this->endpoint;
        $opts['opts']['desc_single'] = $this->descsingle;
        $opts['opts']['desc_multiple'] = $this->descmultiple;
        $opts['opts']['mode'] = 'delete'; // TBD
        $opts['opts']['lang_bulk_confirm'] = get_string('ds_bulk_confirm', 'local_elisprogram');
        $opts['opts']['lang_working'] = get_string('ds_working', 'local_elisprogram');
        $opts['opts']['langdeletesubs'] = get_string('deletesubs', 'local_elisprogram');
        $opts['opts']['langpromotesubs'] = get_string('promotesubs', 'local_elisprogram');
        $opts['opts']['langyes'] = get_string('yes', 'moodle');
        $opts['opts']['langno'] = get_string('no', 'moodle');
        return $opts;
    }

    /**
     * Determine whether the current user can delete the userset.
     *
     * @param int $usersetid The ID of the main element. The is the ID of the 'one', in a 'many-to-one' association.
     * @return bool Whether the current user can delete the specified userset
     */
    protected function can_delete($usersetid) {
        global $USER;
        $clstctx = pm_context_set::for_user_with_capability('cluster', 'local/elisprogram:userset_delete', $USER->id);
        return $clstctx->context_allowed($usersetid, 'cluster');
    }

    /**
     * Delete the userset/subsets.
     * @param array $elements An array of userset information to delete.
     * @param bool $bulkaction Whether this is a bulk-action or not.
     * @return array An array to format as JSON and return to the Javascript.
     */
    protected function _respond_to_js(array $elements, $bulkaction) {
        $subsets = optional_param('subsets', 0, PARAM_INT);
        $failedops = [];
        foreach ($elements as $usersetid => $elem) {
            if (($userset = new userset($usersetid)) && $this->can_delete($usersetid)) {
                try {
                    $userset->load();
                    $userset->deletesubs = $subsets ? 1 : 0;
                    $userset->delete();
                } catch (Exception $e) {
                    if ($bulkaction === true) {
                        $failedops[] = $usersetid;
                    } else {
                        throw $e;
                    }
                }
            } else {
                $failedops[] = $usersetid;
            }
        }
        if (!empty($failedops)) {
            return [
                'result' => 'partialsuccess',
                'msg' => get_string('ds_action_generic_bulkfail', 'local_elisprogram'),
                'failedops' => $failedops,
            ];
        } else {
            return array('result' => 'success', 'msg' => 'Success');
        }
    }
}

