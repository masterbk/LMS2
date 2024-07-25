<?php
namespace report_vtcreport\chart;

defined('MOODLE_INTERNAL') || die;

//require_once($CFG->libdir.'/gradelib.php');

class charbarcoursescorestats{
    public $courseid;
    public $course;

    public function __construct($courseid){
        $this->courseid = $courseid;
        $this->getCourse();
    }

    private function getCourse(){
        if(!$this->courseid){
            throw new \InvalidArgumentException('charbarcoursescorestats::__construct: courseid invalid');
        }
        $course = get_course($this->courseid);
        if(!is_object($course)){
            throw new \InvalidArgumentException('charbarcoursescorestats::__construct: courseid invalid');
        }
        $this->course = $course;
    }

    public function getChartData(){
        $enroledusers = $this->getcourseuserenroled();
        if(count($enroledusers) <= 0){
            return $this->getchartdefaultdata();
        }
        $coursemodules = $this->getcoursemodules();
        if(count($coursemodules) <= 0){
            return $this->getchartdefaultdata();
        }
        $scoredata = $this->collectScoreData($coursemodules,$enroledusers);
        if(count($scoredata) <= 0){
            return $this->getchartdefaultdata();
        }


        $chartdatas = array();
        foreach ($scoredata as $key => $datas){
            $chartdata = array(
                'title' => $key,
                'data'  => array()
            );
            $chartdatalabel = array();
            $charttotalscore = array();
            $chartmediumscore = array();
            foreach ($datas as $key1 => $data){
                $chartdatalabel[] = $key1;
                $charttotalscore[] = $data['totalscore'];
                $chartmediumscore[] = $data['mediumscore'];
            }
            $chartdata['data'] = array(
                'series' => array(
                    'tong_diem' => array(
                        'label' => "Tổng Điểm",
                        'data' => $charttotalscore
                    ),
                    'diem_tb'=>array(
                        'label' => "Điểm TB",
                        'data' => $chartmediumscore
                    )
                ),
                'labels' => $chartdatalabel,
            );
            $chartdatas[] = $chartdata;
        }
        return $chartdatas;
    }

    private function collectScoreData(array $coursemodules, array $enroledusers){
        global $DB,$CFG;
        require_once($CFG->libdir.'/gradelib.php');
        require_once($CFG->libdir . '/adminlib.php');
        $cmcohorgradedata = array();

        foreach ($coursemodules as $key => $cm){

            $modname = $cm->modulename;
            $cminstance = $cm->instance;
            $grading = grade_get_grades($this->courseid, 'mod', $modname, $cminstance,array_column($enroledusers,'id'));

            if(!isset($grading->items[0])){
                return $this->getchartdefaultdata();
            }
            $gradingitems = $grading->items[0];
            $gradingitemsgrades = $gradingitems->grades;

            $useridadrray = array_keys($gradingitemsgrades);
            if(count($useridadrray) <= 0) continue;

            $useridadrraystr = implode(',',$useridadrray);
            $sql = "SELECT mdl_user.id as userid, mdl_cohort.id as cohortid ,  mdl_cohort.name as cohortname
                FROM mdl_user
                LEFT JOIN mdl_cohort_members ON mdl_user.id = mdl_cohort_members.userid
                LEFT JOIN mdl_cohort ON mdl_cohort_members.cohortid = mdl_cohort.id
                WHERE mdl_user.id IN ( {$useridadrraystr} )";
            $usercohortdata = $DB->get_records_sql($sql);
            if(count($usercohortdata) <=0 ) continue;

            $cohorgradedata=array();
            foreach ($usercohortdata as $key1 => $data){
                $cohortname = $data->cohortname ? $data->cohortname : "Không thuộc nhóm";
                if(!array_key_exists($cohortname,$cohorgradedata)){
                    $cohorgradedata[$cohortname] = array();
                    $cohorgradedata[$cohortname]['totalscore'] = 0;
                    $cohorgradedata[$cohortname]['numberofUser'] = 0;
                }
                $cohorgradedata[$cohortname]['cohortname'] = $cohortname;
                $cohorgradedata[$cohortname]['totalscore'] += $gradingitemsgrades[$data->userid]->grade;
                $cohorgradedata[$cohortname]['numberofUser'] += 1;
            }

            foreach ($cohorgradedata as $key2 => $data){
                $cohorgradedata[$key2]['mediumscore'] = $data['totalscore'] / $data['numberofUser'];
                $cohorgradedata[$key2]['mediumscore'] = number_format($cohorgradedata[$key2]['mediumscore'],2,'.','');
                $cohorgradedata[$key2]['totalscore'] = number_format($data['totalscore'],2,'.','');
            }

            $cmcohorgradedata[$gradingitems->name] = $cohorgradedata;

        }
        return $cmcohorgradedata;
    }

    private function getchartdefaultdata(){
        $chartdatas = array(
            array(
                'title' => "Không có dữ liệu",
                'data'  => array(
                    'series' => array(
                        'tong_diem' => array(
                            'label' => "Tổng Điểm",
                            'data' => [0 , 0, 0, 0]
                        ),
                        'diem_tb'=>array(
                            'label' => "Điểm TB",
                            'data' => [0 , 0, 0, 0]
                        )
                    ),
                    'labels' => array(
                        'Demo1' , 'Demo2' , 'Demo3' , 'Demo4'
                    )
                )
            )
        );
        return $chartdatas;
    }

    private function getcourseuserenroled(){
        global $DB;
        $role = $DB->get_record('role', array('shortname' => 'student'));
        $context = \context_course::instance($this->courseid);
        $listUsers = get_role_users($role->id, $context, 0, "u.id, u.lastname, u.firstname");
        return $listUsers;
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
        return $cmlist;
    }

}