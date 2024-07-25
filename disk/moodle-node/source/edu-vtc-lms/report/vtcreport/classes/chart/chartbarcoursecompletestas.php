<?php


namespace report_vtcreport\chart;

defined('MOODLE_INTERNAL') || die;

class chartbarcoursecompletestas{
    public $courseid;
    public $course;

    public function __construct($courseid){
        $this->courseid = $courseid;
        $this->getCourse();
    }

    public function getChartData(){
        $enroledusers = $this->getcourseuserenroled();
        if(count($enroledusers)<=0){
            return $this->getChartDataDefault();
        }
        $coursecompleter = $this->getCourseCompleter();
        $coursecompleterid = array_column($coursecompleter,'userid');

        $cohortdatas = array();
        foreach ($enroledusers as $key => $enroleduser){
            $cohortname = $enroleduser->cohortname?$enroleduser->cohortname:"Không có nhóm";
            if(!array_key_exists($cohortname,$cohortdatas)){
                $cohortdatas[$cohortname]=array(
                    'cohortname'=>$cohortname,
                    'numberofcompleter'=> 0,
                    'numberofnotcompleter'=> 0,
                );
            }

            if(in_array($enroleduser->userid,$coursecompleterid)){
                $cohortdatas[$cohortname]['numberofcompleter'] += 1;
            }else{
                $cohortdatas[$cohortname]['numberofnotcompleter'] += 1;
            }
        }

        $seriesdatacompleter = array();
        $seriesdatanotcompleter = array();
        $labeldata = array();
        foreach ($cohortdatas as $key => $cohortdata){
            $enroler = $cohortdata['numberofcompleter'] + $cohortdata['numberofnotcompleter'];
            $temptseriesdatacompleter =     $cohortdata['numberofcompleter'] / $enroler * 100;
            $seriesdatacompleter[] = number_format($temptseriesdatacompleter,2,'.','');
            $seriesdatanotcompleter[] = number_format(100 - $temptseriesdatacompleter , 2,'.','');
            $labeldata[] = $cohortdata['cohortname'];
        }

        $chartdatas = array(
            'title' => "Thống kê số lượng hoàn thành đủ điều kiện",
            'data'  => array(
                'series' => array(
                    'hoan_thanh' => array(
                        'label' => "Hoàn thành",
                        'data' => $seriesdatacompleter
                    ),
                    'khong_hoan_thanh'=>array(
                        'label' => "Không hoàn thành",
                        'data' => $seriesdatanotcompleter
                    )
                ),
                'labels' => $labeldata
            )
        );
        return $chartdatas;
    }

    private function getChartDataDefault(){
        $chartdatas = array(
            'title' => "Không có dữ liệu",
            'data'  => array(
                'series' => array(
                    'hoan_thanh' => array(
                        'label' => "demo1",
                        'data' => [50,50,50,50]
                    ),
                    'khong_hoan_thanh'=>array(
                        'label' => "demo2",
                        'data' => [50,50,50,50]
                    )
                ),
                'labels' => ["demo","demo","demo","demo"]
            )
        );
        return $chartdatas;
    }

    private function getCourseCompleter(){
        global $DB;
        $sql = "SELECT userid
                FROM {course_completions}
                WHERE course = {$this->courseid} AND timecompleted IS NOT NULL";
        $usercohortdata = $DB->get_records_sql($sql);
        return $usercohortdata;
    }

    private function getCourse(){
        if(!$this->courseid){
            throw new \InvalidArgumentException('chartbarcoursecompletestas::__construct: courseid invalid');
        }
        $course = get_course($this->courseid);
        if(!is_object($course)){
            throw new \InvalidArgumentException('chartbarcoursecompletestas::__construct: courseid invalid');
        }
        $this->course = $course;
    }

    private function getCourseUserEnroled(){
        global $DB;
        $role = $DB->get_record('role', array('shortname' => 'student'));
        $context = \context_course::instance($this->courseid);
        $listUsers = get_role_users($role->id, $context, 0, "u.id, u.lastname, u.firstname");
        if( count($listUsers) <= 0 ){
            return array();
        }
        $listUsersId = array_column($listUsers,'id');
        $listUsersIdStr = implode(',',$listUsersId);
        $sql = "SELECT mdl_user.id as userid, mdl_cohort.id as cohortid ,  mdl_cohort.name as cohortname
                FROM mdl_user
                LEFT JOIN mdl_cohort_members ON mdl_user.id = mdl_cohort_members.userid
                LEFT JOIN mdl_cohort ON mdl_cohort_members.cohortid = mdl_cohort.id
                WHERE mdl_user.id IN ( {$listUsersIdStr} )";
        $usercohortdata = $DB->get_records_sql($sql);
        return $usercohortdata;
    }

}