<?php
namespace local_vtc;
use local_vtc\models\course_ext_info;

require_once(__DIR__ . '/../../../config.php');
class vtc_helper {
	/**
	 * We should always have comment block in the Q&A tab.
	 * This function will check if it's included and add if it's not.
	 *
	 * @return void
	 */
	public static function check_course_missing_block($courseid) {
		global $DB;
		$contextid = \context_course::instance($courseid)->id;
		$blockinstance = $DB->get_record('block_instances', array('blockname' => 'comments', 'parentcontextid' =>
			$contextid));
		if (!empty($blockinstance)) {
			if ($blockinstance->defaultregion != 'above-content') {
				$blockinstance->defaultregion = 'above-content';
				$DB->update_record('block_instances', $blockinstance);
			}
		} else {
			$now = time();
			$DB->insert_record('block_instances', array(
				'blockname' => 'comments',
				'parentcontextid' => $contextid,
				'showinsubcontexts' => 0,
				'requiredbytheme' => 0,
				'pagetypepattern' => 'course-view-*',
				'defaultregion' => 'above-content',
				'defaultweight' => 1,
				'timecreated' => $now,
				'timemodified' => $now
			));
		}
	}

	public static function init_course_ext_content($courseid) {
		global $DB;
		$data = $DB->get_record('course_ext_info', array('courseid' => $courseid));
		$course = $DB->get_record('course', array('id' => $courseid));
		if (empty($data)) {
			$now = time();
			$DB->insert_record('course_ext_info', array(
				'courseid' => $courseid,
				'vtclayoutenabled' => 1,
				'coursecontentsummary' => 'Nội dung bài giảng cho khóa học '.$course->fullname,
				'courseduration' => '100 giờ',
				'continuousupdate' => 1,
				'timecreated' => $now,
				'timemodified' => $now
			));
		}
	}
}
