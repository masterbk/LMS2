<?php


namespace report_vtcreport\chart;

defined('MOODLE_INTERNAL') || die;

class coursecompletereport{
    private $courseid;
    private $cohortid;
    private $username;
    private $course;
    private $page;
    private $enrolerLimitPage = 10;

    public function __construct(int $courseid, $cohortid = null, $username = '', $page = 1){
        $this->courseid = $courseid;
        $this->cohortid = $cohortid;
        $this->username = $username;
        $this->page = $page >= 1 ? ceil($page) : 1;
        $this->getCourse();
    }

    public function getReportData(){
        $enroledCohorts = $this->getCohortEnroledCourse();
        $enrolers = $this->getEnroler();
        $enrolerspagination = $this->getEnrolerPagination();
        $courseModules = $this->getcoursemodules();
        $cinfo = new \completion_info($this->course);
        $stt = 1;
        foreach ($enrolers as $key => $enroler){
            $enroler->stt = $stt;
            $stt++;

            // trạng thái hoàn thành khóa học
            $courseCompleteState = $cinfo->is_course_complete($enroler->id);
            $courseCompleteStateTitle = $courseCompleteState?"Hoàn Thành":"Chưa Hoàn Thành";
            $enrolers[$key]->coursecompletestate = $courseCompleteState;
            $enrolers[$key]->coursecompletetitle= $courseCompleteStateTitle;
            // lấy thông tin trạng thái và điểm course module
            $datacoursemodules = array();
            foreach ($courseModules as $key1 => $cm){
                // lấy thông tin trạng thái hoàn thành course module
                $cmdata = $cinfo->get_data($cm, false, $enroler->id);
                // lấy thông tin điểm
                $grading = grade_get_grades($this->courseid, 'mod', $cm->modulename, $cm->instance, $enroler->id);
                // thu thập thông tin
                $datacoursemodules[] = array(
                    'completionstate'=>$cmdata->completionstate,
                    'completionstatetitle'=>$cmdata->completionstate == 0 ? "Chưa Hoàn Thành" : "Hoàn Thành",
                    'timemodified'=>$cmdata->timemodified,
                    'timemodifiedformat'=>$cmdata->timemodified ? date("d-m-Y",$cmdata->timemodified) : "",
                    'grade' => $grading->items[0]->grades[$enroler->id]->grade ? number_format($grading->items[0]->grades[$enroler->id]->grade,2) : "0.00",
                );
            }
            $enrolers[$key]->coursemodules = $datacoursemodules;
        }
        return array(
            'enroledCohorts' => $enroledCohorts,
            'enrolers' => $enrolers,
            'enrolerspagination' => $enrolerspagination,
            'courseModules' => $courseModules
        );
    }

    private function getcoursemodules(){
        global $DB;
        $sql = "SELECT {course_modules}.id , {course_modules}.course , {course_modules}.module, {course_modules}.instance , {modules}.name as modulename
        FROM {course_modules}
        INNER JOIN {modules} ON {course_modules}.module = {modules}.id
        WHERE {course_modules}.course = {$this->courseid} 
        AND {course_modules}.visible = 1 
        AND {modules}.name in ('scorm','quiz')";
        $cmlist = $DB->get_records_sql($sql);
        $cmlist = array_values($cmlist);
        foreach ($cmlist as $key => $cm ){
            switch ($cm->modulename){
                case 'scorm':
                    $sql = "SELECT {scorm}.name
                            FROM {scorm} 
                            WHERE {scorm}.id = {$cm->instance}
                            AND course = {$this->courseid}";
                    $scorm = $DB->get_record_sql($sql);
                    $cmlist[$key]->moduleíntancename = $scorm->name;
                    break;
                case 'quiz':
                    $sql = "SELECT {quiz}.name
                            FROM {quiz} 
                            WHERE {quiz}.id = {$cm->instance}
                            AND course = {$this->courseid}";
                    $quiz = $DB->get_record_sql($sql);
                    $cmlist[$key]->moduleíntancename = $quiz->name;
                    break;
                default:
                    $cmlist[$key]->moduleíntancename = '';
                    break;
            }
        }
        return $cmlist;
    }

    private function getEnroler($cohortid = 0){
        global $DB;
        $limitfrom = ( $this->page - 1 ) * $this->enrolerLimitPage;

        if($cohortid){

        }else{
            $role = $DB->get_record('role', array('shortname' => 'student'));
            $context = \context_course::instance($this->courseid);
            //$listUsers = get_role_users($role->id, $context, 0, "u.id, u.username, u.lastname, u.firstname, u.email",null,true,'',$limitfrom,$this->enrolerLimitPage);

            $sql = "SELECT DISTINCT {user_enrolments}.userid as id , 
                {user}.username as username,
                {user}.firstname as firstname,
                {user}.lastname as lastname,
                {user}.email as email,
                {role_assignments}.roleid as roleid
                FROM {user_enrolments}
                INNER JOIN {enrol} ON {user_enrolments}.enrolid = {enrol}.id
                INNER JOIN {role_assignments} ON {user_enrolments}.userid = {role_assignments}.userid
                INNER JOIN {user} ON {user_enrolments}.userid = {user}.id
                LEFT  JOIN {cohort_members} ON {user_enrolments}.userid = {cohort_members}.userid 
                WHERE {enrol}.courseid = {$this->courseid} 
                AND {role_assignments}.contextid = {$context->id} 
                AND {role_assignments}.roleid = {$role->id}";
            if($this->cohortid) $sql .= " AND {cohort_members}.cohortid = {$this->cohortid}";
            if($this->username) $sql .= " AND {user}.username like '%{$this->username}%'";
            $listUsers = $DB->get_records_sql($sql,NULL,$limitfrom,$this->enrolerLimitPage);

            $listUsers = array_values($listUsers);
            return $listUsers;
        }
    }

    private function getEnrolerPagination(){
        global $DB;
        $role = $DB->get_record('role', array('shortname' => 'student'));
        $context = \context_course::instance($this->courseid);
        $sql = "SELECT COUNT( DISTINCT {user_enrolments}.userid ) AS countuser
                FROM {user_enrolments}
                INNER JOIN {enrol} ON {user_enrolments}.enrolid = {enrol}.id
                INNER JOIN {role_assignments} ON {user_enrolments}.userid = {role_assignments}.userid
                INNER JOIN {user} ON {user_enrolments}.userid = {user}.id
                LEFT  JOIN {cohort_members} ON {user_enrolments}.userid = {cohort_members}.userid 
                WHERE {enrol}.courseid = {$this->courseid} 
                AND {role_assignments}.contextid = {$context->id} 
                AND {role_assignments}.roleid = {$role->id}";
        if($this->cohortid) $sql .= " AND {cohort_members}.cohortid = {$this->cohortid}";
        if($this->username) $sql .= " AND {user}.username like '%{$this->username}%'";
        $result = $DB->get_record_sql($sql);
        // số lượng phần tử
        $countuser = $result->countuser;
        // tổng số page
        $pages = ceil($countuser / $this->enrolerLimitPage);
        // link page
        $urlParam = array('course'=>$this->courseid);
        if($this->cohortid) $urlParam['cohort'] = $this->cohortid;
        if($this->username) $urlParam['username'] = $this->username;
        $url=new \moodle_url("/report/vtcreport/coursecompletereport.php",$urlParam);
        // gen html
        $html=$this->createPaginationHTML($pages,(int)$this->page,1,$url);
        return $html;
    }

    private function createPaginationHTML($pages, $page, $step, $url) {
        $str = '<ul class="mt-1 pagination demo d-flex flex-row" style="gap:10px">';
        $active = '';
        $pageCutLow = $page - $step <= 0 ? 1 : $page - $step;
        $pageCutHigh = $page + $step >= $pages ? $pages : $page + $step;
        if ($pages == 0) return '';
        // Show the Previous button only if you are on a page other than the first
        if ($page > 1) {
            $str .= '<li class="page-item previours" data-page="' . ($page - 1) . '">' .
                '<a href="' . $url . '&page=' . ($page - 1) . '" class="page-link" ><i class="fa-solid fa-chevron-left" style="font-size: 16px"></i><span class="sr-only">Previous</span></a></li>';
        }
        // Show all the pagination elements if there are less than 6 pages total
        if ($pages < 6) {
            for ($p = 1; $p <= $pages; $p++) {
                $active = $page === $p ? "active" : "no";
                $str .= '<li class="page-item ' . $active . '" data-page="' . $p . '">' .
                    '<a href="' . $url . '&page=' . $p . '" class="page-link" >' . $p . '</a></li>';
            }
        } else {
            // Show the very first page followed by a "..." at the beginning of the
            // pagination section (after the Previous button)
            if ($page > $step + 1) {
                $str .= '<li class="no page-item" data-page="1">' .
                    '<a href="' . $url . '&page=1" class="page-link">1</a></li>';
                if ($page > $step + 2) {
                    $str .= '<li class="out-of-range">' .
                        '<a class="page-link">...</a></li>';
                }
            }
            // Determine how many pages to show before the current page index
            if ($page === $pages) {
                $pageCutLow -= 2;
            } else if ($page === $pages - 1) {
                $pageCutLow -= 1;
            }
            // Output the indexes for pages that fall inside the range of pageCutLow
            // and pageCutHigh
            for ($p = $pageCutLow; $p <= $pageCutHigh; $p++) {
                if ($p === 0) {
                    $p += 1;

                }
                if ($p > $pages) {
                    continue;
                }
                $active = $page === $p ? "active" : "no";
                $str .= '<li class="page-item ' . $active . '" data-page="' . $p . '">' .
                    '<a href="' . $url . '&page=' . $p . '" class="page-link">' . $p . '</a></li>';
            }
            // Show the very last page preceded by a "..." at the end of the pagination
            // section (before the Next button)
            if ($page < $pages - $step) {
                if ($page < $pages - $step - 1) {
                    $str .= '<li class="out-of-range">' .
                        '<a href="' . $url . '" class="page-link">...</a></li>';
                }
                $str .= '<li class="page-item no" data-page="' . $pages . '">' .
                    ' <a href="' . $url . '&page=' . $pages . '" class="page-link">' . $pages . '</a></li>';
            }
        }
        // Show the Next button only if you are on a page other than the last
        if ($page < $pages) {
            $str .= '<li class="page-item next no" data-page="' . ($page + 1) . '">' .
                '<a href="' . $url . '&page=' . ($page + 1) . '" class="page-link"><i class="fa-solid fa-chevron-right" style="font-size: 16px"></i><span class="sr-only">Next</span></a></li>';
        }
        $str .= '</ul>';
        return $str;
        // Return the pagination string to be outputted in the pug templates
    }


    private function getCourse(){
        if(!$this->courseid){
            throw new \InvalidArgumentException('coursecompletereport::__construct: courseid invalid');
        }
        $course = get_course($this->courseid);
        if(!is_object($course)){
            throw new \InvalidArgumentException('coursecompletereport::__construct: courseid invalid');
        }
        $this->course = $course;
    }

    private function getCohortEnroledCourse(){
        global $DB;
        $sql = "SELECT mdl_cohort.id, mdl_cohort.name
                FROM mdl_enrol 
                INNER JOIN mdl_cohort ON mdl_enrol.customint1 = mdl_cohort.id
                WHERE mdl_enrol.enrol = 'cohort' AND mdl_enrol.courseid = {$this->courseid}";
        $result = $DB->get_records_sql($sql);
        $defaul = new \stdClass();
        $defaul->id = null;
        $defaul->name = "Lớp: Tất cả";

        $result = array_merge(array($defaul),$result);
        foreach ($result as $key => $value){
            $result[$key]->selected = false;
            if($value->id == $this->cohortid){
                $result[$key]->selected = true;
            }
        }
        return $result;
    }

}