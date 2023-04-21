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
 * Contains the class for fetching the important dates in mod_assign for a given module instance and a user.
 *
 * @package   mod_assign
 * @copyright 2021 Shamim Rezaie <shamim@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare(strict_types=1);

namespace mod_assign;

use core\activity_dates;
use PhpXmlRpc\Helper\Date;

/**
 * Class for fetching the important dates in mod_assign for a given module instance and a user.
 *
 * @copyright 2021 Shamim Rezaie <shamim@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class dates extends activity_dates {

    /**
     * Returns a list of important dates in mod_assign
     *
     * @return array
     */
    protected function get_dates(): array {
        global $CFG;

        require_once($CFG->dirroot . '/mod/assign/locallib.php');

        $course = get_course($this->cm->course);
        $context = \context_module::instance($this->cm->id);
        $assign = new \assign($context, $this->cm, $course);


        $timeopen = $this->cm->customdata['allowsubmissionsfromdate'] ?? null;
        $timedue = $this->cm->customdata['duedate'] ?? null;
        //TODO: make this timecreated instead? database gets mad, I guess 'assign' doesn't include a 'timecreated' field despite there very much being one
        $timeposted = $this->cm->customdata['timemodified'] ?? null;
        $timeclose = $this->cm->customdata['cutoffdate'] ?? null;

        $activitygroup = groups_get_activity_group($this->cm, true);
        if ($activitygroup) {
            if ($assign->can_view_grades()) {
                $groupoverride = \cache::make('mod_assign', 'overrides')->get("{$this->cm->instance}_g_{$activitygroup}");
                if (!empty($groupoverride->allowsubmissionsfromdate)) {
                    $timeopen = $groupoverride->allowsubmissionsfromdate;
                }
                if (!empty($groupoverride->duedate)) {
                    $timedue = $groupoverride->duedate;
                }
                if (!empty($groupoverride->cutoffdate)) {
                    $timeclose = $groupoverride->cutoffdate;
                }
            }
        }

        $now = time();
        $dates = [];

        if ($timeopen) {
            $openlabelid = $timeopen > $now ? 'activitydate:submissionsopen' : 'activitydate:submissionsopened';
            $date = [
                'dataid' => 'allowsubmissionsfromdate',
                'label' => get_string($openlabelid, 'mod_assign'),
                'timestamp' => (int) $timeopen,
            ];
            if ($course->relativedatesmode && $assign->can_view_grades()) {
                $date['relativeto'] = $course->startdate;
            }
            $dates[] = $date;
        } else if($timeposted){
            $date = [
                'dataid' => 'allowsubmissionsfromdate',
                'label' => get_string('activitydate:submissionsopened', 'mod_assign'),
                'timestamp' => (int) $timeposted,
                'invisible' => true,
            ];
            if ($course->relativedatesmode && $assign->can_view_grades()) {
                $date['relativeto'] = $course->startdate;
            }
            $dates[] = $date;
        }

        if ($timedue) {
            $date = [
                'dataid' => 'duedate',
                'label' => get_string('activitydate:submissionsdue', 'mod_assign'),
                'timestamp' => (int) $timedue,
                'align' => 'right',
            ];
            if ($course->relativedatesmode && $assign->can_view_grades()) {
                $date['relativeto'] = $course->startdate;
            }
            $dates[] = $date;
        }

        if ($timeclose) {
            $closelabelid = $timeclose > $now ? 'activitydate:submissionsclose' : 'activitydate:submissionsclosed';
            $date = [
                'dataid' => 'closedate',
                'label' => get_string($closelabelid, 'mod_assign'),
                'timestamp' => (int) $timeclose,
                'align' => 'right',
            ];
            if ($course->relativedatesmode && $assign->can_view_grades()) {
                $date['relativeto'] = $course->startdate;
            }
            $dates[] = $date;
        }

        return $dates;
    }

    //TODO: comments
    protected function get_countdown(): array{
        $dates = $this->get_dates();
        if($dates[0]['dataid'] != 'allowsubmissionsfromdate' || sizeOf($dates) < 2){
            return [];
        }

        $countdown = [];
        $opentime = $dates[0]['timestamp'];//TODO: rename variables
        $time2 = $dates[1]['timestamp'];
        $current_time = time();
        // if($current_time < $opentime){ //Combined ifs
        //     return [];
        // }
        //TODO: after closing, make invisible
        $progress1 = number_format(($time2-$current_time)/($time2-$opentime)*100) . "%";
        if($current_time < $opentime || ($current_time > $time2 && sizeOf($dates) < 3)){
            return [];
        } else if ($current_time > $time2){
            $countdown['countdown_color'] = "red";
            $time3 = $dates[2]['timestamp'];
            $progress2 = number_format(($time3-$current_time)/($time3-$time2)*100) . "%";
            $countdown['countdown'] = $progress2;
            if($current_time > $time3){
                $countdown = [];
            }
        } else if($time2-$current_time < 86400){//TODO: constant (variable)
            $countdown['countdown_color'] = "yellow";
            $countdown['countdown'] = $progress1;
        } else{
            $countdown['countdown_color'] = "green";
            $countdown['countdown'] = $progress1;
        }

        return $countdown;
    }
}