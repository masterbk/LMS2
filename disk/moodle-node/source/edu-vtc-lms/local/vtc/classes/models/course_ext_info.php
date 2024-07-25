<?php

namespace local_vtc\models;

require_once(__DIR__ . '/../../../../config.php');

define('TABLE_NAME', 'course_ext_info');

class course_ext_info {

	public $id;
	public $courseid;
	public $vtclayoutenabled;
	public $coursevideo;
	public $coursecontentsummary;
	public $courseduration;
	public $continuousupdate;
	public $timecreated;
	public $timemodified;
	public $references;

	/**
	 * @param $id
	 * @param $courseid
	 * @param $vtclayoutenabled
	 * @param $coursevideo
	 * @param $coursecontentsummary
	 * @param $courseduration
	 * @param $continousupdate
	 * @param $timecreated
	 * @param $timemodified
	 */
	public function __construct($id, $courseid, $vtclayoutenabled, $coursevideo, $coursecontentsummary, $courseduration, $continuousupdate, $references, $timecreated = null, $timemodified = null) {
		$this->id = $id;
		$this->courseid = $courseid;
		$this->vtclayoutenabled = $vtclayoutenabled;
		$this->coursevideo = $coursevideo;
		$this->coursecontentsummary = $coursecontentsummary;
		$this->courseduration = $courseduration;
		$this->continuousupdate = $continuousupdate;
		$this->references = $references;
		$this->timecreated = $timecreated;
		$this->timemodified = $timemodified;
	}

	public static function createInstance() {
		return new course_ext_info(null, null, null, null, null, null, null, null);
	}

	public function initByCourseId($courseId) {
		global $DB;
		$data = $DB->get_record(TABLE_NAME, array('courseid' => $courseId));
		if (!$data) return;
		$fields = ['id', 'courseid', 'vtclayoutenabled', 'coursevideo', 'coursecontentsummary', 'courseduration', 'continuousupdate', 'timecreated', 'timemodified'];
		foreach ($fields as $field) {
			$this->$field = $data->$field;
		}
	}

	/**
	 * Create or Update Course Ext. In the update case, the data will be updated by courseid (instead of the id)
	 *
	 * @return bool|int
	 * @throws \dml_exception
	 */
	public function save() {
		global $DB;
		$now = time();
		$courseid = $this->courseid;
		if (!empty($courseid)) {
			$updating = $DB->get_record(TABLE_NAME, array('courseid' => $courseid));
			if (empty($updating)) {
				$this->timecreated = $now;
				$this->timemodified = $now;
				if (is_array($this->coursecontentsummary)) $this->coursecontentsummary = $this->coursecontentsummary['text'];
				$data = $DB->insert_record(TABLE_NAME, $this->toStdClass());
			} else {
				$fields = ['vtclayoutenabled', 'coursevideo', 'coursecontentsummary', 'courseduration', 'continuousupdate'];
				foreach ($fields as $field) {
					if ($field === 'coursecontentsummary' && is_array($this->$field)) {
						$updating->$field = $this->$field['text'];
					} else {
						$updating->$field = $this->$field;
					}

				}
				$updating->timemodified = $now;
				$DB->update_record(TABLE_NAME, $updating);
			}
		}
		if ($this->references) {
			file_save_draft_area_files(
				$this->references,
				\context_course::instance($this->courseid)->id,
				'local_vtc',
				'references',
				$this->courseid,
				[
					'subdirs' => 0
				]
			);
		}
		return $data;
	}

	public function toStdClass() {
		return (object) array(
			'id' => $this->id,
			'courseid' => $this->courseid,
			'vtclayoutenabled' => $this->vtclayoutenabled,
			'coursevideo' => $this->coursevideo,
			'coursecontentsummary' => $this->coursecontentsummary,
			'courseduration' => $this->courseduration,
			'continuousupdate' => $this->continuousupdate,
			'timemodified' => $this->timemodified,
			'timecreated' => $this->timecreated
		);
	}

}
