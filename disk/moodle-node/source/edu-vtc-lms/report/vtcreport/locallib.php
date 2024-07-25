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
 * This file contains the moodle hooks for the assign module.
 *
 * It delegates most functions to the assignment class.
 *
 * @package   local_vtc
 * @copyright Videa
 * @author    Bob Nguyen
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

function validateCohortUserInCourse(int $courseid){
    global $DB;
    $getCourseCohortSql = "SELECT mdl_enrol.customint1 AS cohortid
                           FROM {enrol}
                           WHERE enrol = 'cohort' AND courseid = {$courseid}";
    $courseCohort = $DB->get_records_sql($getCourseCohortSql);

    if(count($courseCohort)>0){
        $courseCohortid = array_column($courseCohort,'cohortid');
        $courseCohortidstr = implode(',',$courseCohortid);
        $sqlUserCohortDuplicate =  "SELECT mdl_cohort_members.userid , COUNT(mdl_cohort_members.userid) as countcohord
                                    FROM mdl_cohort_members
                                    WHERE mdl_cohort_members.cohortid IN ({$courseCohortidstr})
                                    GROUP BY mdl_cohort_members.userid
                                    HAVING countcohord > 1";
        $userCohortDuplicate = $DB->get_records_sql($sqlUserCohortDuplicate);

        if(count($userCohortDuplicate)>0){
            $userCohortDuplicateId = array_column($userCohortDuplicate,'userid');
            $userCohortDuplicateIdStr = implode(',',$userCohortDuplicateId);
            $sqlUserCohortDuplicateInfor = "SELECT mdl_cohort_members.id , mdl_user.username, mdl_user.email , mdl_cohort.name as cohortname
                                            FROM mdl_cohort_members
                                            INNER JOIN mdl_user ON mdl_cohort_members.userid = mdl_user.id
                                            INNER JOIN mdl_cohort ON mdl_cohort_members.cohortid = mdl_cohort.id
                                            WHERE mdl_cohort_members.userid IN ({$userCohortDuplicateIdStr})";

            $userCohortDuplicateData = $DB->get_records_sql($sqlUserCohortDuplicateInfor);
            $message = '';
            foreach ($userCohortDuplicateData as $key => $data){
                $message = $message . "{$data->email} ( {$data->cohortname} ) , ";
            }
            throw new \InvalidArgumentException("VTC REPORT chỉ dành cho trường hơp các học viên chỉ có trong 1 nhóm duy nhất: \n {$message}");
        }
    }
}

function validateEnroledUserInCourse(int $courseid){
    global $DB;
    $sql = "SELECT COUNT(ue.id) as countuser
            FROM {user_enrolments} AS ue
            INNER JOIN {enrol} AS e ON ue.enrolid = e.id
            WHERE e.courseid = {$courseid}";
    $data = $DB->get_record_sql($sql);
    if($data->countuser<=0){
        throw new \InvalidArgumentException("VTC REPORT: \n khóa học chưa có học viên tham gia");
    }

}