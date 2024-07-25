<?php

use core_completion\progress;
require_once(__DIR__.'/../../config.php');
require_once($CFG->libdir.'/externallib.php');
require_once($CFG->dirroot.'/user/lib.php');
require_once($CFG->dirroot.'/course/lib.php');
require_once($CFG->dirroot.'/user/profile/lib.php');

class local_custom_service_external extends external_api {

     public static function get_course_general_info_parameters() {
        return new external_function_parameters(
            array(
                'courseid' => new external_value(PARAM_TEXT, 'Course ID'),                
                'token' => new external_value(PARAM_TEXT, 'Token')                
            )
        );
    }

    public static function get_course_general_info($courseid, $token) {
        global $DB, $CFG;

        $url = $CFG->wwwroot.'/webservice/rest/server.php?wstoken='.$token.'&wsfunction=core_course_get_courses_by_field&moodlewsrestformat=json&field=id&value='.$courseid;

        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($curl);
        curl_close($curl);
        $res = json_decode($response);
        
        $output = array();
        foreach ($res->courses as $r) {
            $context = context_COURSE::instance($r->id);
            $c = count_enrolled_users($context,'');
            $creators = array();
            foreach ($r->contacts as $contact) {          
                $url = $CFG->wwwroot.'/webservice/rest/server.php?wstoken='.$token.'&wsfunction=core_user_get_course_user_profiles&moodlewsrestformat=json&userlist[0][userid]='.$contact->id.'&userlist[0][courseid]=1';

                $curl = curl_init($url);
                curl_setopt($curl, CURLOPT_URL, $url);
                curl_setopt($curl, CURLOPT_HEADER, false);
                curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
                $getProfile = curl_exec($curl);
                curl_close($curl);
                $get = json_decode($getProfile);

                $creator = [
                    'creatorname' => $get[0]->fullname,
                    'creatoravatar' => $get[0]->profileimageurl,
                ];
                
                $creators[]=$creator;  
            }    

            $info = [
                'categoryid' => $r->categoryid,
                'categoryname' => $r->categoryname,
                'id'=> $r->id,
                'fullname'=> $r->fullname,
                'star' => 5,
                'duration' => 5,
                'courseimage'=>$r->courseimage,
                'number_of_enrolled_users' => $c,       
                'summary'=>htmlspecialchars($r->summary),
                'skills' => 'abcd',
                'creators' =>  $creators
                //'creatorname' => $r->contacts[0]->fullname,
                //'creatoravatar' =>$get[0]->profileimageurl,
            ];    
            $output[]=$info;            
        }
        return $output;
    }

    public static function get_course_general_info_returns() {
       
       return new external_multiple_structure(
            new external_single_structure(
                array(
                    'categoryid' => new external_value(PARAM_TEXT, 'categoryid'),
                    'categoryname' => new external_value(PARAM_TEXT, 'categoryname'),
                    'id' => new external_value(PARAM_TEXT, 'id'),
                    'fullname' => new external_value(PARAM_TEXT, 'fullname'),
                    'star' => new external_value(PARAM_TEXT, 'star'),
                    'duration' => new external_value(PARAM_TEXT, 'duration'),
                    'courseimage' => new external_value(PARAM_TEXT, 'courseimage'),
                    'number_of_enrolled_users' => new external_value(PARAM_TEXT, 'number_of_enrolled_users'),
                    'summary' => new external_value(PARAM_TEXT, 'summary'),
                    'skills' => new external_value(PARAM_TEXT, 'skills'),
                    'creators' => new external_multiple_structure(
                            new external_single_structure(
                                  array(
                                    'creatorname' => new external_value(PARAM_TEXT, 'creatorname'),
                                    'creatoravatar' => new external_value(PARAM_TEXT, 'creatoravatar'),
                                  )      
                            )

                    )


                    //'creatorname' => new external_value(PARAM_TEXT, 'creatorname'),
                    //'creatoravatar' => new external_value(PARAM_TEXT, 'creatoravatar'),
                    //'timecreated' => new external_value(PARAM_TEXT, 'timecreated'),
                ), 'List of courses'
            )
        );           
    }




    public static function get_courses_general_info_parameters() {
        return new external_function_parameters(
            array(
                'categoryid' => new external_value(PARAM_TEXT, 'Category ID'),                
                'token' => new external_value(PARAM_TEXT, 'Token')                
            )
        );
    }

    public static function get_courses_general_info($categoryid, $token) {
        global $DB, $CFG;

        $url = $CFG->wwwroot.'/webservice/rest/server.php?wstoken='.$token.'&wsfunction=core_course_get_courses_by_field&moodlewsrestformat=json&field=category&value='.$categoryid;

        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($curl);
        curl_close($curl);
        $res = json_decode($response);
        
        $output = array();
        foreach ($res->courses as $r) {
            $context = context_COURSE::instance($r->id);
            $c = count_enrolled_users($context,'');
            $info = [
                'categoryid' => $r->categoryid,
                'categoryname' => $r->categoryname,
                'id'=> $r->id,
                'fullname'=> $r->fullname,
                'star' => 5,
                'duration' => 5,
                'courseimage'=>$r->courseimage,
                'number_of_enrolled_users' => $c,       
                
            ];    
            $output[]=$info;            
        }
        return $output;
    }

    public static function get_courses_general_info_returns() {
       
       return new external_multiple_structure(
            new external_single_structure(
                array(
                    'categoryid' => new external_value(PARAM_TEXT, 'categoryid'),
                    'categoryname' => new external_value(PARAM_TEXT, 'categoryname'),
                    'id' => new external_value(PARAM_TEXT, 'id'),
                    'fullname' => new external_value(PARAM_TEXT, 'fullname'),
                    'star' => new external_value(PARAM_TEXT, 'star'),
                    'duration' => new external_value(PARAM_TEXT, 'duration'),
                    'courseimage' => new external_value(PARAM_TEXT, 'courseimage'),
                    'number_of_enrolled_users' => new external_value(PARAM_TEXT, 'number_of_enrolled_users'),
                    //'timecreated' => new external_value(PARAM_TEXT, 'timecreated'),
                ), 'List of courses'
            )
        );           
    }


    public static function get_user_profile_parameters() {
        return new external_function_parameters(
            array(
                'username' => new external_value(PARAM_TEXT, 'Username')                
               // 'wstoken' => new external_value(PARAM_TEXT, 'Token')                
            )
        );
    }

    public static function get_user_profile($username) {
        global $DB;

        $user = $DB->get_record('user', array('username' => $username), '*');
        $context = context_COURSE::instance(2);
        $c=count_enrolled_users($context,'');

        if ($user) {
            $profile = [
                        'userid'=>$user->id,
                        'email' => $user->email,
                        'firstname'=> $user->firstname,
                        'lastname'=> $user->lastname,
                        'phone'=> $user->phone1,
                       // 'address'=> $user->address,
                         'address'=> $c,
                        ];    
        } else {
            $profile = [
                        'userid'=> 0,
                        'email' => null,
                        'firstname'=> null,
                        'lastname'=> null,
                        'phone'=> null,
                        'address'=> null,
                        ];
        }               
        
        return $profile;
    }

    public static function get_user_profile_returns() {
        return new external_single_structure(
                array(

                    'userid' => new external_value(PARAM_TEXT, 'id'),
                    'email' => new external_value(PARAM_TEXT, 'email'),
                    'firstname'=> new external_value(PARAM_TEXT, 'firstname'),
                    'lastname'=> new external_value(PARAM_TEXT, 'lastname'),
                    'phone'=>new external_value(PARAM_TEXT,'phone'),
                    'address'=>new external_value(PARAM_TEXT,'address'),
                )
            );
    }

    public static function get_user_certificates_parameters() {
        return new external_function_parameters(
            array(
                'username' => new external_value(PARAM_TEXT, 'Username')                
               // 'wstoken' => new external_value(PARAM_TEXT, 'Token')                
            )
        );
    }

    public static function get_user_certificates($username) {
        global $DB;

        $user = $DB->get_record('user', array('username' => $username), '*');

        if ($user) {

            $sort = 'ci.timecreated DESC';
            $sql = "SELECT c.id, c.name, co.fullname as coursename, ci.code, ci.timecreated
                  FROM {customcert} c
            INNER JOIN {customcert_issues} ci
                    ON c.id = ci.customcertid
            INNER JOIN {course} co
                    ON c.course = co.id
                 WHERE ci.userid = :userid
              ORDER BY $sort";

            $res = $DB->get_records_sql($sql, array('userid' => $user->id), 0, 100);
            $output = array();
            foreach ($res as $r) {
                    $o = array();       
                    $o['id'] = $r->id;
                    $o['coursename'] = $r->coursename;
                    $o['name']  = $r->name;
                    $o['code']  = $r->code;  
                    $o['timecreated'] = $r->timecreated;                   
                    $output[]=$o;
            }
        } else {
           
        }               
        
        return $output;
    }

    public static function get_user_certificates_returns() {

        return new external_multiple_structure(
            new external_single_structure(
                array(
                    'id' => new external_value(PARAM_TEXT, 'id'),
                    'coursename' => new external_value(PARAM_TEXT, 'coursename'),
                    'name' => new external_value(PARAM_TEXT, 'name'),
                    'code' => new external_value(PARAM_TEXT, 'code'),
                    'timecreated' => new external_value(PARAM_TEXT, 'timecreated'),
                ), 'List of categories'
            )
        );
 
    }

    public static function update_user_profile_parameters() {
        return new external_function_parameters(
            array(
                'username' => new external_value(PARAM_TEXT, 'Username'),                
                'newphone' => new external_value(PARAM_TEXT, 'New phone'),      
                'newdob' => new external_value(PARAM_TEXT, 'New DOB'),                                
                'newemail' => new external_value(PARAM_TEXT, 'New email'),   
                'newjob' => new external_value(PARAM_TEXT, 'New job'),            
                'newgender' => new external_value(PARAM_TEXT, 'New gender'),     
                'newaddress' => new external_value(PARAM_TEXT, 'New address')                    
                                   
            )
        );
    }

    public static function update_user_profile($username, $newphone, $newdob, $newemail, $newjob, $newgender, $newaddress) {

        global $DB;

        $user = $DB->get_record('user', array('username' => $username), '*');

        if ($user) {
            $user = $DB->get_record('user', array('username' => $username), '*');      
                
            $user->phone1 = $newphone;           
            $user->email = $newemail;          
            $user->address = $newaddress;     

            $DB->update_record('user', $user);

            $fields = profile_get_user_fields_with_data($user->id);

            $dobid = 0;
            $jobid = 0;
            $genderid = 0;

            foreach ($fields as $formfield) {
                if ($formfield->field->shortname == 'dob') {
                    $dobid = $formfield->fieldid;
                };
                if ($formfield->field->shortname == 'job') {
                    $jobid = $formfield->fieldid;
                };
                if ($formfield->field->shortname == 'gender') {
                    $genderid = $formfield->fieldid;
                };
                
            }
 
            $hasdob = $DB->record_exists('user_info_data', array('userid' => $user->id,'fieldid'=> $dobid));
            $hasjob = $DB->record_exists('user_info_data', array('userid' => $user->id,'fieldid'=> $jobid));
            $hasgender = $DB->record_exists('user_info_data', array('userid' => $user->id,'fieldid'=> $genderid));

            if ($hasdob) {
                $info = $DB->get_record('user_info_data', array('userid' => $user->id,'fieldid'=> $dobid), '*');
                $info->data = $newdob;
                $DB->update_record('user_info_data', $info);
            } else {
                $ins = new stdClass();
                $ins->userid = $user->id;
                $ins->fieldid = $dobid;
                $ins->data = $newdob;
                $ins->dataformat = 0;
                $createdob = $DB->insert_record('user_info_data', $ins, true, false);
            }

            if ($hasjob) {
                $info = $DB->get_record('user_info_data', array('userid' => $user->id,'fieldid'=> $jobid), '*');
                $info->data = $newjob;
                $DB->update_record('user_info_data', $info);
            } else {
                $ins = new stdClass();
                $ins->userid = $user->id;
                $ins->fieldid = $jobid;
                $ins->data = $newjob;
                $ins->dataformat = 0;
                $createdob = $DB->insert_record('user_info_data', $ins, true, false);
            }

            if ($hasgender) {
                $info = $DB->get_record('user_info_data', array('userid' => $user->id,'fieldid'=> $genderid), '*');
                $info->data = $newgender;
                $DB->update_record('user_info_data', $info);
            } else {
                $ins = new stdClass();
                $ins->userid = $user->id;
                $ins->fieldid = $genderid;
                $ins->data = $newgender;
                $ins->dataformat = 0;
                $createdob = $DB->insert_record('user_info_data', $ins, true, false);
            }

            $profile = [
                'status'=>200,    
                'message' => 'Profile information updated',  
            ];
        } else {
            $profile = [
                'status'=>200,
                'message' => 'User not found',  
            ];
        }         
       
        
        return $profile;
    }

    public static function update_user_profile_returns() {
        return new external_single_structure(
                array(

                    'status' => new external_value(PARAM_TEXT, 'status'),
                    'message' => new external_value(PARAM_TEXT, 'message'),
                )
            );
    }

    public static function change_user_lang_parameters() {
        return new external_function_parameters(
            array(
                'username' => new external_value(PARAM_TEXT, 'Username'),                
                'newlang' => new external_value(PARAM_TEXT, 'New language'),                
                
            )
        );
    }

    public static function change_user_lang($username, $newlang) {
        global $DB;       

        $user = $DB->get_record('user', array('username' => $username), '*');

        if ($user) {
                            
            $user->lang = $newlang;
            
            $DB->update_record('user', $user);
            $profile = [
                'status'=>200,
                'message' => 'Language changed',  
            ];
        } else {
            $profile = [
                'status'=>401,
                'message' => 'User not found',  
            ];
        }         
       
        
        return $profile;
    }

    public static function change_user_lang_returns() {
        return new external_single_structure(
                array(

                    'status' => new external_value(PARAM_TEXT, 'status'),
                    'message' => new external_value(PARAM_TEXT, 'message'),
             
                )
            );
    }


    public static function change_user_password_parameters() {
        return new external_function_parameters(
            array(
                'username' => new external_value(PARAM_TEXT, 'Username'),                
                'currentpassword' => new external_value(PARAM_TEXT, 'Current password'),                
                'newpassword' => new external_value(PARAM_TEXT, 'New password')                
            )
        );
    }

    public static function change_user_password($username, $currentpassword, $newpassword) {
        global $DB;

        $user = $DB->get_record('user', array('username' => $username), '*');

        if ($user) {

            $hashedpassword = hash_internal_user_password($newpassword);
            $DB->set_field('user', 'password', $hashedpassword, array('id'=>$user->id));

            $profile = [
                        'status'=>200,
                        'message' => 'Password changed',                        
                        ];    
        } else {
            $profile = [
                        'status'=>401,
                        'message' => 'User not found',  
                        ];
        }               
        
        return $profile;
    }

    public static function change_user_password_returns() {
        return new external_single_structure(
                array(

                    'status' => new external_value(PARAM_TEXT, 'status'),
                    'message' => new external_value(PARAM_TEXT, 'message'),
             
                )
            );
    }


    public static function remove_user_account_parameters() {
        return new external_function_parameters(
            array(
                'username' => new external_value(PARAM_TEXT, 'Username'),                                
            )
        );
    }

    public static function remove_user_account($username) {
        global $DB;
        
        $user = $DB->get_record('user', array('username' => $username), '*');      
                
        //$user->deleted = 1;
        $DB->update_record('user', $user);
        
            $profile = [
                        'status'=>200,
                        'message' => 'Used removed',                        
                        ];    
        
        return $profile;
    }

    public static function remove_user_account_returns() {
        return new external_single_structure(
                array(

                    'status' => new external_value(PARAM_TEXT, 'status'),
                    'message' => new external_value(PARAM_TEXT, 'message'),
             
                )
            );
    }


        
    public static function update_courses_lti_parameters() {
        return new external_function_parameters(
            array(
                'courseids' => new external_value(PARAM_TEXT, 'Course Ids')                
            )
        );
    }
    public static function update_courses_lti($courseids) {
        global $DB,$CFG;
        $lti_updated = [];
        $status = false;
        //print_object($courseids);
        $sql = "SELECT cm.id as moduleid,cm.instance ltiid,cm.section as section,lt.name as ltiname,lt.grade as grade,lt.timecreated,lt.timemodified,c.id as courseid,gd.id as category
            FROM {course} c 
            JOIN {course_modules} cm ON c.id = cm.course 
            JOIN {lti} lt ON cm.instance = lt.id 
            JOIN {grade_categories} gd ON gd.courseid = c.id
            WHERE cm.module =15 AND c.id in (".$courseids.")";
        $modules = $DB->get_records_sql($sql);
        $all_module = array();$v = count($modules);
        $count = 0;
        foreach ($modules as $key => $value) {
            $v = $key;
            if($DB->record_exists('grade_items',array('courseid'=>$value->courseid,'categoryid'=>$value->category,'itemtype'=>'mod','itemmodule'=>'lti','iteminstance'=>$value->ltiid))){
                //$all_module[] = $value;
            }else{
                $new_grade_item = new stdClass();
                $new_grade_item->courseid = $value->courseid;
                $new_grade_item->categoryid = $value->category;
                $new_grade_item->itemname = $value->ltiname;
                $new_grade_item->itemtype = 'mod';
                $new_grade_item->itemmodule = 'lti';
                $new_grade_item->iteminstance = $value->ltiid;
                $new_grade_item->itemnumber = 0;
                $new_grade_item->grademax = $value->grade;
                $new_grade_item->timecreated = $value->timecreated;
                $new_grade_item->timemodified = $value->timemodified;

                $insert_new_gradeitem = $DB->insert_record('grade_items',$new_grade_item);
                $count++;
            }
        }
        
        $lti_updated = [
                        'ids'=>$courseids,
                        'v' => $v,
                        'message'=>'Success Hello',
                        'updated'=>$count
                        ];
        return $lti_updated;
    }
    public static function update_courses_lti_returns() {
        return new external_single_structure(
                array(

                    'ids' => new external_value(PARAM_TEXT, 'course ids'),
                    'v' => new external_value(PARAM_TEXT, 'v'),
                    'message'=> new external_value(PARAM_TEXT, 'success message'),
                    'updated'=>new external_value(PARAM_TEXT,'Items Updated')
                )
            );
    }

    public static function update_courses_sections_parameters() {
        
        return new external_function_parameters(
            array(
                'courseids' => new external_value(PARAM_TEXT, 'Course Ids')
            )
        );
    }
    public static function update_courses_sections($courseids) {
        
        global $DB,$CFG;
        require_once($CFG->libdir . '/filelib.php');
        require_once($CFG->dirroot . '/course/lib.php');
        
        $course = $DB->get_record('course', array('id' => $courseids), '*', MUST_EXIST);
        $sections = $DB->get_records('course_sections', array('course' => $courseids));
        
        $count = 0;

        foreach ($sections as $key => $value) {
            $section = $DB->get_record('course_sections', array('id' => $key), '*', MUST_EXIST);

            $data = new stdClass();
            $data->id = $section->id;
            $data->name = $section->summary;
            $data->availability = '{"op":"&","c":[],"showc":[]}';

            //check if section is empty-then update
            if($section->name == NULL){
                $done = course_update_section($course, $section, $data);
            }
            $count ++;
        }
        
        $lti_updated = [
                        'ids'=>$courseids,
                        'message'=>'Success',
                        'updated'=>$count
                        ];
        return $lti_updated;
    }
    public static function update_courses_sections_returns() {
        return new external_single_structure(
                array(
                    'ids' => new external_value(PARAM_TEXT, 'course ids'),
                    'message'=> new external_value(PARAM_TEXT, 'success message'),
                    'updated'=>new external_value(PARAM_TEXT,'Items Updated')
                )
            );
    }





    public static function unenrol_bulk_users_parameters() {
        return new external_function_parameters(
            array(
                'categoryids' => new external_value(PARAM_TEXT, 'Category Ids'),
                'roleid' => new external_value(PARAM_TEXT, 'Role Ids')
            )
        );
    }
    public static function unenrol_bulk_users($categoryids, $roleid) {
        // echo $categoryids;
        global $DB,$CFG;
        require_once($CFG->libdir . '/filelib.php');
        require_once($CFG->dirroot . '/course/lib.php'); 
        require_once($CFG->dirroot . '/enrol/locallib.php'); 
        require_once($CFG->dirroot . '/enrol/externallib.php'); 
        
        $sql = "DELETE ue FROM mdl_user_enrolments ue
        JOIN mdl_enrol e ON (e.id = ue.enrolid)
        JOIN mdl_course course ON (course.id = e.courseid )
        JOIN mdl_context c ON (c.contextlevel = 50 AND c.instanceid = e.courseid)
        JOIN mdl_role_assignments ra ON (ra.contextid = c.id  AND ra.userid = ue.userid AND ra.roleid=$roleid)
        WHERE course.category IN (?)
        ";
            //echo $categoryids;
            $param=explode(',',$categoryids);
            //print_r($param);
            $result = $DB->execute($sql,$param);
            if($result) {
                $response = [
                    'message'=>'Success'                        
                    ];

            }else{
                $response = [
                    'message'=>'Failed'                        
                    ];

            }        
            
        return $response;
    }
    public static function unenrol_bulk_users_returns() {
        return new external_single_structure(
                array(                   
                    'message'=> new external_value(PARAM_TEXT, 'success message')                   
                )
            );
    }

}