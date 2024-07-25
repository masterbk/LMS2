<?php


namespace theme_moove\layout;

class home{
    public $categoriesData = array();
    public $coursesData = array();

    public $countAccess = 0;
    public $countVisitor = 0;
    public $countStudent = 0;
    public $countActivity = 0;
    public $countTeacher = 0;

    public function __construct(){
        $this->categoriesData = $this->getCategorieData();
        $this->coursesData = $this->getCoursesData();
        $this->countStudent = $this->getCountStudent();
        $this->countActivity = $this->getCountActivity();
        $this->countTeacher = $this->getCountTeacher();
        $this->countVisitor = $this->getCountVisitor();
        $this->countAccess = 0;
        // face data

        $this->countVisitor += 840200;
        $this->countStudent += 79000;
        $this->countActivity += 1132;
        $this->countTeacher += 200;
    }


    /** Lấy số lượng giảng viên
     * @return mixed
     * @throws \dml_exception
     */
    private function getCountVisitor(){
        global $DB;
        $sqlCountVisitor = 'SELECT * FROM {count_visitor}';
        $countVisitor = $DB->get_record_sql($sqlCountVisitor);
        return $countVisitor->count_visitor;
    }

    /** Lấy số lượng giảng viên
     * @return mixed
     * @throws \dml_exception
     */
    private function getCountTeacher(){
        global $DB;
        $sqlCountTeacher = 'SELECT COUNT(id) AS countid FROM {role_assignments} WHERE roleid = 3';
        $countTeacher = $DB->get_record_sql($sqlCountTeacher);
        return isset($countTeacher->countid)?$countTeacher->countid:0;
    }

    /** lấy số hoạt động của khóa học
     * @return mixed
     * @throws \dml_exception
     */
    private function getCountActivity(){
        global $DB;
        $sqlCountActivity = 'SELECT COUNT(id) AS countid FROM {course_modules};';
        $countActivity = $DB->get_record_sql($sqlCountActivity);
        return isset($countActivity->countid)?$countActivity->countid:0;
    }

    /** Lấy số user đã học
     * @return int
     * @throws \dml_exception
     */
    private function getCountStudent(){
        global $DB;
        $sqlCountStudent = 'SELECT COUNT(id) AS countid FROM {role_assignments} WHERE roleid = 5';
        $countLearned = $DB->get_record_sql($sqlCountStudent);
        return isset($countLearned->countid)?$countLearned->countid:0;
    }

    /** Lấy data course categories
     * @return array
     * @throws \dml_exception
     */
    private function getCategorieData(){
        global $DB;
        $now = time();
        $getCategoriesSql = "SELECT * from {course_categories} where parent =0 and visible =1 order by sortorder ASC";
        $categories = $DB->get_records_sql($getCategoriesSql);
        $listCategories = array();
        foreach ($categories as $key => $category){
            $getSubCategoriesSql = "SELECT id FROM {course_categories} where parent = :id and visible = 1 order by sortorder ASC";
            $subCategories = $DB->get_records_sql($getSubCategoriesSql, array('id' => $category->id));
            $listSubCategoriesId = count($subCategories) <= 0 ? "0" : implode(",", array_column($subCategories,'id'));
            $getTotalCourseSql = "SELECT count(id) as countid FROM {course} WHERE visible != 0 and (startdate < :time1 and ( enddate > :time2 or enddate =0 )) and category IN ( {$listSubCategoriesId} ) order by sortorder ASC";
            $getTotalCourse = $DB->get_record_sql($getTotalCourseSql, array('time1' => $now, 'time2' => $now));

            $listCategories[] = [
                'id'          => $category->id,
                'name'        => $category->name,
                'image'       => themeMooveGetCourseCategoryPicture($category->id),
                'countcourse' => $getTotalCourse->countid
            ];
        }
        return $listCategories;
    }

    /** Lấy course data
     * @return array
     * @throws \dml_exception
     */
    private function getCoursesData(){
        $courses = $this->getCourses();

        $coursesData = array();
        foreach ($courses as $key => $course){
            $course->url = \course_get_url($course->id)->out(false);
            $course->courseimage = getThumbnailCourse($course);
            if (\core_component::get_plugin_directory('tool', 'courserating')) {
                $course->rating = ratingCourse($course->id);
            }
            $customLabel = getCustomField('context_course', $course->id, 'label');
            $customTime = getCustomField('context_course', $course->id, 'time');
            $course->customfield = array_merge($customLabel, $customTime);
            if(in_array($course->id,[289,247,244,241])){
                unset($courses[$key]);
                $coursesData[] = $course;
            }
        }
        $coursesData = array_merge($courses,$coursesData);
        $coursesData = array_values($coursesData);
        return $coursesData;
    }

    private function getCourses(){
        global $DB;
        $customfieldOutstanding = $this->getCustomfieldOutstanding();
        if(!$customfieldOutstanding) return array();
        $getCourseSql = "SELECT mdl_course.* 
                         FROM {customfield_data}
                         INNER JOIN {context} ON {customfield_data}.contextid = {context}.id
                         INNER JOIN {course} ON {customfield_data}.instanceid = {course}.id
                         WHERE {context}.contextlevel = :contextlevel 
                         AND {customfield_data}.fieldid = :fieldid
                         AND {customfield_data}.intvalue = :fielddata
                         AND {course}.visible = :coursevisible
                         LIMIT 15";
        $courses = $DB->get_records_sql($getCourseSql, array(
            'fieldid' => $customfieldOutstanding->id,
            'fielddata' => 1,
            'contextlevel' => 50,
            'coursevisible' => 1
        ));
        return $courses;
    }

    private function getCustomfieldOutstanding(){
        global $DB;
        $getFieldSql = 'SELECT * FROM {customfield_field} WHERE shortname=:shortname';
        $field = $DB->get_record_sql($getFieldSql, array('shortname' => "label"));
        return $field;
    }
}